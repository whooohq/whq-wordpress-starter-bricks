<?php
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery -- Direct $wpdb query is required for this operation.
// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching -- some query operations need to always receive the most up-to-date or actual data directly from the database, reducing the risk of serving stale information.
if (!defined('ABSPATH')) die('No direct access allowed');

class UpdraftPlus_Search_Replace {

	private $columns = array();

	private $current_row = 0;

	private $use_wpdb = false;

	private $use_mysqli = false;

	private $wpdb_obj = null;

	private $mysql_dbh = null;

	protected $max_recursion = 0;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		add_action('updraftplus_restore_db_pre', array($this, 'updraftplus_restore_db_pre'));
		$this->max_recursion = apply_filters('updraftplus_search_replace_max_recursion', 20);
	}

	/**
	 * This function is called via the filter updraftplus_restore_db_pre it sets up the search and replace database objects
	 *
	 * @return void
	 */
	public function updraftplus_restore_db_pre() {
		global $wpdb, $updraftplus_restorer;
		
		$this->use_wpdb = $updraftplus_restorer->use_wpdb();
		$this->wpdb_obj = $wpdb;

		$mysql_dbh = false;
		$use_mysqli = false;

		if (!$this->use_wpdb) {
			// We have our own extension which drops lots of the overhead on the query
			$wpdb_obj = $updraftplus_restorer->get_db_object();
			// Was that successful?
			if (!$wpdb_obj->is_mysql || !$wpdb_obj->ready) {
				$this->use_wpdb = true;
			} else {
				$this->wpdb_obj = $wpdb_obj;
				$mysql_dbh = $wpdb_obj->updraftplus_get_database_handle();
				$use_mysqli = $wpdb_obj->updraftplus_use_mysqli();
			}
		}

		$this->mysql_dbh = $mysql_dbh;
		$this->use_mysqli = $use_mysqli;
	}

	/**
	 * The engine
	 *
	 * @param string|array $search    - a string or array of things to search for
	 * @param string|array $replace   - a string or array of things to replace the search terms with
	 * @param array        $tables    - an array of tables
	 * @param integer      $page_size - the page size
	 */
	public function icit_srdb_replacer($search, $replace, $tables, $page_size) {

		if (!is_array($tables)) return false;

		global $wpdb, $updraftplus;

		$report = array(
			'tables' => 0,
			'rows' => 0,
			'change' => 0,
			'updates' => 0,
			'start' => microtime(true),
			'end' => microtime(true),
			'errors' => array(),
		);

		$page_size = (empty($page_size) || !is_numeric($page_size)) ? 5000 : $page_size;

		foreach ($tables as $table => $stripped_table) {

			$report['tables']++;

			if ($search === $replace) {
				$updraftplus->log("No search/replace required: would-be search and replacement are identical");
				continue;
			}

			$this->columns = array();

			$print_line = __('Search and replacing table:', 'updraftplus').' '.$table;

			$updraftplus->check_db_connection($this->wpdb_obj, true);

			// Get a list of columns in this table
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- DESCRIBE is a DDL command that cannot be parameterized; $table is a SQL identifier sanitized using backquote().
			$fields = $wpdb->get_results('DESCRIBE '.UpdraftPlus_Manipulation_Functions::backquote($table), ARRAY_A);

			$prikey_field = false;
			foreach ($fields as $column) {
				$primary_key = ('PRI' == $column['Key']) ? true : false;
				if ($primary_key) $prikey_field = $column['Field'];
				if ('posts' == $stripped_table && 'guid' == $column['Field']) {
					$updraftplus->log('Skipping search/replace on GUID column in posts table');
					continue;
				}
				$this->columns[$column['Field']] = $primary_key;
			}

			// Count the number of rows we have in the table if large we'll split into blocks, This is a mod from Simon Wheatley

			// InnoDB does not do count(*) quickly. You can use an index for more speed - see: http://www.cloudspace.com/blog/2009/08/06/fast-mysql-innodb-count-really-fast/

			$where = '';
			// Opportunity to use internal knowledge on tables which may be huge
			if ('postmeta' == $stripped_table && ((is_array($search) && 0 === strpos($search[0], 'http')) || (is_string($search) && 0 === strpos($search, 'http')))) {
				$where = " WHERE meta_value LIKE '%http%'";
			}

			$count_rows_sql = 'SELECT COUNT(*) FROM '.$table;
			if ($prikey_field) $count_rows_sql .= " USE INDEX (PRIMARY)";
			$count_rows_sql .= $where;

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- $count_rows_sql is constructed from safe table identifiers; SQL identifiers cannot be parameterized with $wpdb->prepare().
			$row_countr = $wpdb->get_results($count_rows_sql, ARRAY_N);

			// If that failed, try this
			if (false !== $prikey_field && $wpdb->last_error) {
				$escaped_table_name = UpdraftPlus_Database_Utility::escape_table_name($table);
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- $escaped_table_name and $prikey_field are SQL identifiers; identifiers cannot be parameterized with $wpdb->prepare(), table name is safely escaped via escape_table_name().
				$row_countr = $wpdb->get_results("SELECT COUNT(*) FROM $escaped_table_name USE INDEX ($prikey_field)".$where, ARRAY_N);
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name is safely escaped via escape_table_name().
				if ($wpdb->last_error) $row_countr = $wpdb->get_results("SELECT COUNT(*) FROM $escaped_table_name", ARRAY_N);
			}

			$row_count = $row_countr[0][0];
			/* translators: %d: Number of rows. */
			$print_line .= ': '.sprintf(__('rows: %d', 'updraftplus'), $row_count);
			$updraftplus->log($print_line, 'notice-restore', 'restoring-table-'.$table);
			$updraftplus->log('Search and replacing table: '.$table.": rows: ".$row_count);

			if (0 == $row_count) continue;

			for ($on_row = 0; $on_row <= $row_count; $on_row = $on_row+$page_size) {

				$this->current_row = 0;

				if ($on_row>0) $updraftplus->log_e("Searching and replacing reached row: %d", $on_row);

				// Grab the contents of the table
				list($data, $page_size) = $this->fetch_sql_result($table, $on_row, $page_size, $where);
				// $sql_line is calculated here only for the purpose of logging errors
				// $where might contain a %, so don't place it inside the main parameter

				$sql_line = sprintf('SELECT * FROM %s LIMIT %d, %d', $table.$where, $on_row, $on_row+$page_size);

				// Our strategy here is to minimise memory usage if possible; to process one row at a time if we can, rather than reading everything into memory
				if ($this->use_wpdb) {

					if ($wpdb->last_error) {
						$report['errors'][] = $this->print_error($sql_line);
					} else {
						foreach ($data as $row) {
							$rowrep = $this->process_row($table, $row, $search, $replace, $stripped_table);
							$report['rows']++;
							$report['updates'] += $rowrep['updates'];
							$report['change'] += $rowrep['change'];
							foreach ($rowrep['errors'] as $err) $report['errors'][] = $err;
						}
					}
				} else {
					if (false === $data) {
						$report['errors'][] = $this->print_error($sql_line);
					} elseif (true !== $data && null !== $data) {
						if ($this->use_mysqli) {
							while ($row = mysqli_fetch_array($data)) { // phpcs:ignore WordPress.DB.RestrictedFunctions.mysql_mysqli_fetch_array -- Using mysqli directly for streaming large result sets during restore to avoid high memory usage with $wpdb
								$rowrep = $this->process_row($table, $row, $search, $replace, $stripped_table);
								$report['rows']++;
								$report['updates'] += $rowrep['updates'];
								$report['change'] += $rowrep['change'];
								foreach ($rowrep['errors'] as $err) $report['errors'][] = $err;
							}
							mysqli_free_result($data); // phpcs:ignore WordPress.DB.RestrictedFunctions.mysql_mysqli_free_result -- Using mysqli directly for streaming large result sets during restore to avoid high memory usage with $wpdb
						} else {
							// phpcs:ignore PHPCompatibility.Extensions.RemovedExtensions.mysql_DeprecatedRemoved, WordPress.DB.RestrictedFunctions.mysql_mysql_fetch_array -- Ignore removed extension compatibility, direct mysql function used for low-level database operations outside of $wpdb.
							while ($row = mysql_fetch_array($data)) {
								$rowrep = $this->process_row($table, $row, $search, $replace, $stripped_table);
								$report['rows']++;
								$report['updates'] += $rowrep['updates'];
								$report['change'] += $rowrep['change'];
								foreach ($rowrep['errors'] as $err) $report['errors'][] = $err;
							}
							@mysql_free_result($data); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged, PHPCompatibility.Extensions.RemovedExtensions.mysql_DeprecatedRemoved, WordPress.DB.RestrictedFunctions.mysql_mysql_free_result -- If an error occurs during mysql free result and it fails to free result, it will not impact anything at all. mysql_* function used in the scenario in which the mysqli extension doesn't exist.
						}
					}
				}

			}

		}

		$report['end'] = microtime(true);

		return $report;
	}

	/**
	 * This function will get data from the passed in table ready to be search and replaced
	 *
	 * @param string  $table     - the table name
	 * @param integer $on_row    - the row to start from
	 * @param integer $page_size - the page size
	 * @param string  $where     - the where condition
	 *
	 * @return array - an array of data or an array with a false value
	 */
	private function fetch_sql_result($table, $on_row, $page_size, $where = '') {

		$sql_line = sprintf('SELECT * FROM %s%s LIMIT %d, %d', $table, $where, $on_row, $page_size);

		global $updraftplus;
		$updraftplus->check_db_connection($this->wpdb_obj, true);

		if ($this->use_wpdb) {
			global $wpdb;
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- $sql_line is a SELECT query built from safe table identifiers and paginated offsets; SQL identifiers and LIMIT/OFFSET values cannot be parameterized with $wpdb->prepare().
			$data = $wpdb->get_results($sql_line, ARRAY_A);
			if (!$wpdb->last_error) return array($data, $page_size);
		} else {
			if ($this->use_mysqli) {
				// phpcs:ignore WordPress.DB.RestrictedFunctions.mysql_mysqli_query -- Direct mysqli call required; this uses a dedicated low-level database handle ($this->mysql_dbh) outside $wpdb for search-replace operations.
				$data = mysqli_query($this->mysql_dbh, $sql_line);
			} else {
				// phpcs:ignore PHPCompatibility.Extensions.RemovedExtensions.mysql_DeprecatedRemoved, WordPress.DB.RestrictedFunctions.mysql_mysql_query -- Ignore removed extension compatibility, direct mysql function used for low-level database operations outside of $wpdb.
				$data = mysql_query($sql_line, $this->mysql_dbh);
			}
			if (false !== $data) return array($data, $page_size);
		}
		
		if (5000 <= $page_size) return $this->fetch_sql_result($table, $on_row, 2000, $where);
		if (2000 <= $page_size) return $this->fetch_sql_result($table, $on_row, 500, $where);

		// At this point, $page_size should be 500; and that failed
		return array(false, $page_size);

	}

	/**
	 * This function will process a single row from the database calling recursive_unserialize_replace to search and replace the data found in the search and replace arrays
	 *
	 * @param string $table          - the current table we are working on
	 * @param array  $row            - the current row we are working on
	 * @param array  $search         - an array of things to search for
	 * @param array  $replace        - an array of things to replace the search terms with
	 * @param string $stripped_table - the stripped table
	 *
	 * @return array - returns an array report which includes changes made and any errors
	 */
	private function process_row($table, $row, $search, $replace, $stripped_table) {

		global $updraftplus, $wpdb, $updraftplus_restorer;

		$report = array('change' => 0, 'errors' => array(), 'updates' => 0);

		$this->current_row++;
		
		$update_sql = array();
		$where_sql = array();
		$upd = false;

		foreach ($this->columns as $column => $primary_key) {
		
			// Don't search/replace these
			if (('options' == $stripped_table && 'option_value' == $column && !empty($row['option_name']) && 'updraft_remotesites' == $row['option_name']) || ('sitemeta' == $stripped_table && 'meta_value' == $column && !empty($row['meta_key']) && 'updraftplus_options' == $row['meta_key'])) {
				continue;
			}
		
			$edited_data = $data_to_fix = $row[$column];
			$successful = false;

			// We catch errors/exceptions so that they're not fatal. Once saw a fatal ("Cannot access empty property") on "if (is_a($value, '__PHP_Incomplete_Class')) {" (not clear what $value has to be to cause that).
			try {
				// Run a search replace on the data that'll respect the serialisation.
				$edited_data = $this->recursive_unserialize_replace($search, $replace, $data_to_fix);
				$successful = true;
			} catch (Exception $e) {
				$log_message = 'An Exception ('.get_class($e).') occurred during the recursive search/replace. Exception message: '.$e->getMessage().' (Code: '.$e->getCode().', line '.$e->getLine().' in '.$e->getFile().')';
				$report['errors'][] = $log_message;
				error_log($log_message);
				$updraftplus->log($log_message);
				/* translators: 1: Exception class, 2: Exception message. */
				$updraftplus->log(sprintf(__('A PHP exception (%1$s) has occurred: %2$s', 'updraftplus'), get_class($e), $e->getMessage()), 'warning-restore');
			} catch (Error $e) {// phpcs:ignore PHPCompatibility.Classes.NewClasses.errorFound -- The Error class does not exist in PHP below 5.6.
				$log_message = 'A PHP Fatal error (recoverable, '.get_class($e).') occurred during the recursive search/replace. Exception message: Error message: '.$e->getMessage().' (Code: '.$e->getCode().', line '.$e->getLine().' in '.$e->getFile().')';
				$report['errors'][] = $log_message;
				error_log($log_message);
				$updraftplus->log($log_message);
				/* translators: 1: Fatal error class, 2: Error message. */
				$updraftplus->log(sprintf(__('A PHP fatal error (%1$s) has occurred: %2$s', 'updraftplus'), get_class($e), $e->getMessage()), 'warning-restore');
			}

			// Something was changed
			if ($successful && $edited_data != $data_to_fix) {
				$report['change']++;
				$ed = $edited_data;
				$wpdb->escape_by_ref($ed);
				// Undo breakage introduced in WP 4.8.3 core
				if (is_callable(array($wpdb, 'remove_placeholder_escape'))) $ed = $wpdb->remove_placeholder_escape($ed);
				$update_sql[] = UpdraftPlus_Manipulation_Functions::backquote($column) . ' = "' . $ed . '"';
				$upd = true;
			}

			if ($primary_key) {
				$df = $data_to_fix;
				$wpdb->escape_by_ref($df);
				// Undo breakage introduced in WP 4.8.3 core
				if (is_callable(array($wpdb, 'remove_placeholder_escape'))) $df = $wpdb->remove_placeholder_escape($df);
				$where_sql[] = UpdraftPlus_Manipulation_Functions::backquote($column) . ' = "' . $df . '"';
			}
		}

		if ($upd && !empty($where_sql)) {
			$sql = 'UPDATE '.UpdraftPlus_Manipulation_Functions::backquote($table).' SET '.implode(', ', $update_sql).' WHERE '.implode(' AND ', array_filter($where_sql));
			$result = $updraftplus_restorer->sql_exec($sql, 5, '', false);
			if (false === $result || is_wp_error($result)) {
				$last_error = $this->print_error($sql);
				$report['errors'][] = $last_error;
			} else {
				$report['updates']++;
			}

		} elseif ($upd) {
			$report['errors'][] = sprintf('"%s" has no primary key, manual change needed on row %s.', $table, $this->current_row);
			$updraftplus->log(__('Error:', 'updraftplus').' '.
				/* translators: 1: Table name, 2: Row number requiring manual change. */
				sprintf(__('"%1$s" has no primary key, manual change needed on row %2$s.', 'updraftplus'), $table, $this->current_row),
			'warning-restore');
		}

		return $report;

	}
	
	/**
	 * Take a serialised array and unserialise it replacing elements as needed and
	 * unserialising any subordinate arrays and performing the replace on those too.
	 * N.B. $from and $to can be arrays - they get passed only to str_replace(), which can take an array
	 *
	 * @param string $from            String we're looking to replace.
	 * @param string $to              What we want it to be replaced with
	 * @param array  $data            Used to pass any subordinate arrays back to in.
	 * @param bool   $serialised      Does the array passed via $data need serialising.
	 * @param int    $recursion_level Current recursion depth within the original data.
	 * @param array  $visited_data    Data that has been seen in previous recursion iterations.
	 *
	 * @return array	The original array with all elements replaced as needed.
	 */
	private function recursive_unserialize_replace($from = '', $to = '', $data = '', $serialised = false, $recursion_level = 0, $visited_data = array()) {

		global $updraftplus;

		static $error_count = 0;

		// some unserialised data cannot be re-serialised eg. SimpleXMLElements
		try {
			$case_insensitive = false;

			// If we've reached the maximum recursion level, short circuit
			if (0 !== $this->max_recursion && $recursion_level >= $this->max_recursion) {
				return $data;
			}

			if (is_array($data) || is_object($data)) {
				// If we've seen this exact object or array before, short circuit
				if (in_array($data, $visited_data, true)) {
					return $data; // Avoid infinite recursions when there's a circular reference
				}
				// Add this data to the list of
				$visited_data[] = $data;
			}

			if (is_array($from) && is_array($to)) {
				$case_insensitive = preg_match('#^https?:#i', implode($from)) && preg_match('#^https?:#i', implode($to)) ? true : false;
			} else {
				$case_insensitive = preg_match('#^https?:#i', $from) && preg_match('#^https?:#i', $to) ? true : false;
			}

			// O:8:"DateTime":0:{} : see https://bugs.php.net/bug.php?id=62852
			if (is_serialized($data) && false === strpos($data, 'O:8:"DateTime":0:{}') && false !== ($unserialized = UpdraftPlus::unserialize($data))) {
				$data = $this->recursive_unserialize_replace($from, $to, $unserialized, true, $recursion_level + 1);
			} elseif (is_array($data)) {
				$_tmp = array();
				foreach ($data as $key => $value) {
					$_tmp[$key] = $this->recursive_unserialize_replace($from, $to, $value, false, $recursion_level + 1, $visited_data);
				}

				$data = $_tmp;
				unset($_tmp);
			} elseif (is_object($data)) {
				$_tmp = clone $data;
				$props = get_object_vars($data);

				foreach ($props as $key => $value) {
					// Skip any representation of a protected property or integer property
					if (!preg_match('/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*$/', $key)) continue;

					$_tmp->$key = $this->recursive_unserialize_replace($from, $to, $value, false, $recursion_level + 1, $visited_data);
				}

				$data = $_tmp;
				unset($_tmp);
			} elseif (is_string($data) && (null !== ($_tmp = json_decode($data, true)))) {

				if (is_array($_tmp)) {
					foreach ($_tmp as $key => $value) {
						$_tmp[$key] = $this->recursive_unserialize_replace($from, $to, $value, false, $recursion_level + 1, $visited_data);
					}

					$data = json_encode($_tmp);
					unset($_tmp);
				}

			} else {
				if (is_string($data)) {
					if ($case_insensitive) {
						$data = str_ireplace($from, $to, $data);
					} else {
						$data = str_replace($from, $to, $data);
					}
// Below is the wrong approach. In fact, in the problematic case, the resolution is an extra search/replace to undo unnecessary ones
// if (is_string($from)) {
// $data = str_replace($from, $to, $data);
// } else {
// # Array. We only want a maximum of one replacement to take place. This is only an issue in non-default setups, but in those situations, carrying out all the search/replaces can be wrong. This is also why the most specific URL should be done first.
// foreach ($from as $i => $f) {
// $ndata = str_replace($f, $to[$i], $data);
// if ($ndata != $data) {
// $data = $ndata;
// break;
// }
// }
// }
				}
			}

			if ($serialised)
				return serialize($data);

		} catch (Exception $error) {
			if (3 > $error_count) {
				$log_message = 'PHP Fatal Exception error ('.get_class($error).') has occurred during recursive_unserialize_replace. Error Message: '.$error->getMessage().' (Code: '.$error->getCode().', line '.$error->getLine().' in '.$error->getFile().')';
				$updraftplus->log($log_message, 'warning-restore');
				$error_count++;
			}
		}

		return $data;
	}

	/**
	 * This function will get the last database error and log it
	 *
	 * @param string $sql_line - the sql line that caused the error
	 *
	 * @return void
	 */
	public function print_error($sql_line) {
		global $wpdb, $updraftplus;
		if ($this->use_wpdb) {
			$last_error = $wpdb->last_error;
		} else {
			// phpcs:ignore PHPCompatibility.Extensions.RemovedExtensions.mysql_DeprecatedRemoved, WordPress.DB.RestrictedFunctions.mysql_mysql_error, WordPress.DB.RestrictedFunctions.mysql_mysqli_error -- Ignore removed extension compatibility, direct mysql/mysqli function used for low-level database operations outside of $wpdb.
			$last_error = ($this->use_mysqli) ? mysqli_error($this->mysql_dbh) : mysql_error($this->mysql_dbh);
		}
		$updraftplus->log(__('Error:', 'updraftplus')." ".$last_error." - ".__('the database query being run was:', 'updraftplus').' '.$sql_line, 'warning-restore');
		return $last_error;
	}
}
