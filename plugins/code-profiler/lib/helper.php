<?php
/**
 +=====================================================================+
 |    ____          _        ____             __ _ _                   |
 |   / ___|___   __| | ___  |  _ \ _ __ ___  / _(_) | ___ _ __         |
 |  | |   / _ \ / _` |/ _ \ | |_) | '__/ _ \| |_| | |/ _ \ '__|        |
 |  | |__| (_) | (_| |  __/ |  __/| | | (_) |  _| | |  __/ |           |
 |   \____\___/ \__,_|\___| |_|   |_|  \___/|_| |_|_|\___|_|           |
 |                                                                     |
 |  (c) Jerome Bruandet ~ https://nintechnet.com/codeprofiler/         |
 +=====================================================================+
*/

if (! defined('ABSPATH') ) {
	die('Forbidden');
}

// =====================================================================
// Various constants

// The profiles directory can be defined in the wp-config.php script
if (! defined('CODE_PROFILER_UPLOAD_DIR') ) {
	// When running Code Profiler via WP CLI on a child site of a multisite installation,
	// we need to get the main site upload folder otherwise wp_upload_dir will return
	// the child site's
	if ( is_multisite() && ! ( is_main_network() && is_main_site() && defined('MULTISITE') ) ) {
		$upload_dir = code_profiler_upload_dir();
	} else {
		$upload_dir = wp_upload_dir();
	}
	define('CODE_PROFILER_UPLOAD_DIR', $upload_dir['basedir'] .'/code-profiler');
}
define('CODE_PROFILER_LOG', CODE_PROFILER_UPLOAD_DIR .'/log.php');
define('CODE_PROFILER_TMP_IOSTATS_LOG', 'iostats.tmp');
define('CODE_PROFILER_TMP_SUMMARY_LOG', 'summary.tmp');
define('CODE_PROFILER_TMP_RERUN_LOG', 'rerun.tmp');
define('CODE_PROFILER_TMP_CALLS_LOG', 'calls.tmp');
define('CODE_PROFILER_TMP_DISKIO_LOG', 'diskio.tmp');
define('CODE_PROFILER_TMP_CONNECTIONS_LOG', 'connections.tmp');
define('CODE_PROFILER_UPDATE_NOTICE', '<div class="updated notice is-dismissible"><p>%s</p></div>');
define('CODE_PROFILER_ERROR_NOTICE', '<div class="error notice is-dismissible"><p>%s</p></div>');
define('CODE_PROFILER_WARNING_NOTICE', '<div class="notice notice-warning"><p>%s</p></div>');
if (! defined('CODE_PROFILER_MUPLUGIN') ) {
	// MU plugin's name can be defined in the wp-config.php
	define('CODE_PROFILER_MUPLUGIN', '0----code-profiler.php');
}

/**
 * Since WP 6.7, translation loading must not be triggered too early.
 */
add_action('init', 'code_profiler_i18n_constants');
function code_profiler_i18n_constants() {

	global $wp_version;
	if (! defined('CODE_PROFILER_UA') ) { // UA signatures can be user-defined in the wp-config.php
		define ('CODE_PROFILER_UA', [
			esc_html__('Desktop', 'code-profiler') => [
				'Firefox'			=> 'Mozilla/5.0 (Linux x86_64; rv:149.0) Gecko/20100101 Firefox/149.0',
				'Chrome'				=> 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML,'.
											' like Gecko) Chrome/147.0.0.0 Safari/537.36',
				'Edge'				=> 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML,'.
											' like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/Chrome/147.0.7727.50'
			],
			esc_html__('Mobile', 'code-profiler') => [
				'Android Phone'	=> 'Mozilla/5.0 (Android 16; Mobile; rv:68.0) Gecko/68.0 Firefox/149.0',
				'Android Tablet'	=> 'Mozilla/5.0 (Linux; Android 16.0; SAMSUNG-SM-T377A Build/NMF26X)'.
										' AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.7727.50 Mobile Safari/537.36',
				'iPhone'				=> 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7_7 like Mac OS X) AppleWebKit/605.1.15'.
											' (KHTML, like Gecko) Version/26.0 Mobile/15E148 Safari/604.1',
				'iPad'				=> 'Mozilla/5.0 (iPad; CPU OS 18_7_7 like Mac OS X) AppleWebKit/605.1.15'.
										' (KHTML, like Gecko) GSA/213.0.449417121 Mobile/15E148 Safari/605.1.15'
			],
			esc_html__('Bot', 'code-profiler')    => [
				'Google Bot'		=> 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
				'WordPress'			=> 'Mozilla/5.0 (compatible; CodeProfiler for WordPress/'. $wp_version .
											'; https://nintechnet.com/codeprofiler/)'
			]

		] );
	}
	define('CODE_PROFILER_ACCURACY', [
		1		=> __('Highest', 'code-profiler'),
		5		=> __('High', 'code-profiler'),
		10		=> __('Moderate', 'code-profiler'),
		15		=> __('Low', 'code-profiler'),
		20		=> __('Lowest', 'code-profiler')
	]);
}

// =====================================================================
// Find the main site upload dir on a multisite installation.
// Used when running the profiler via WP CLI.
// This code is based on the WP private _wp_upload_dir function.

function code_profiler_upload_dir() {

	$upload_path = trim( get_option('upload_path') );
	if ( empty( $upload_path ) || 'wp-content/uploads' === $upload_path ) {
		$basedir = WP_CONTENT_DIR . '/uploads';
	} elseif ( strpos( $upload_path, ABSPATH ) !== 0 ) {
		// $basedir is absolute, $upload_path is (maybe) relative to ABSPATH.
		$basedir = path_join( ABSPATH, $upload_path );
	} else {
		$basedir = $upload_path;
	}

	if ( defined('UPLOADS') && ! ( is_multisite() && get_site_option('ms_files_rewriting') ) ) {
		$basedir = ABSPATH . UPLOADS;
	}

	return ['basedir' => $basedir];
}

// =====================================================================
// Prevent cURL timeout if a plugin changes its timeout options.

function code_profiler_curl_timeout( $handle, $parsed_args, $url ) {

	if ( isset( $_REQUEST['action'] ) &&
		$_REQUEST['action'] == 'codeprofiler_start_profiler') {

		curl_setopt( $handle, CURLOPT_CONNECTTIMEOUT, 300 );
		curl_setopt( $handle, CURLOPT_TIMEOUT, 300 );
	}
}
add_action('http_api_curl', 'code_profiler_curl_timeout', 1000, 3 );

// =====================================================================
// Update version in the DB and check if options must be updated.

function code_profiler_init_update() {

	if ( ( $cp_options = get_option('code-profiler') ) == false ) {
		/**
		 * "Automatic conversion of false to array is deprecated" since PHP 8.1
		 */
		$cp_options = [];
	}

	if ( empty( $cp_options['version'] ) ) {
		$cp_options['version'] = CODE_PROFILER_VERSION;
		update_option('code-profiler', $cp_options );

	} elseif ( version_compare( $cp_options['version'], CODE_PROFILER_VERSION, '<') ) {

		// Version 1.1
		if ( version_compare( $cp_options['version'], '1.1', '<' ) ) {
			if (! isset( $cp_options['enable_wpcli'] ) ) {
				$cp_options['enable_wpcli'] = 1;
			}
		}

		// Version 1.2
		if ( version_compare( $cp_options['version'], '1.2', '<' ) ) {
			if (! isset( $cp_options['disable_wpcron'] ) ) {
				$cp_options['disable_wpcron'] = 1;
			}
		}

		// Version 1.3.1
		if ( version_compare( $cp_options['version'], '1.3.1', '<' ) ) {
			if (! isset( $cp_options['http_response'] ) ) {
				$cp_options['http_response'] = '^(?:3|4|5)\d{2}$';
			}
		}

		// Version 1.5
		if ( version_compare( $cp_options['version'], '1.5', '<' ) ) {
			if (! isset( $cp_options['accuracy'] ) ) {
				$cp_options['accuracy'] = 1;
			}
		}

		// Version 1.7.5
		if ( version_compare( $cp_options['version'], '1.7.5', '<' ) ) {
			if (! isset( $cp_options['php_error'] ) ) {
				$cp_options['php_error'] = 1;
			}
		}

		// Version 1.8
		if ( version_compare( $cp_options['version'], '1.8', '<' ) ) {
			if ( isset( $cp_options['mem_where'] ) ) {
				$cp_options['mem']['x_end'] = $cp_options['mem_where'];
				unset( $cp_options['mem_where'] );
			}
			if ( isset( $cp_options['mem_post'] ) ) {
				$cp_options['mem']['post'] = $cp_options['mem_post'];
				unset( $cp_options['mem_post'] );
			}
			if ( isset( $cp_options['mem_user'] ) ) {
				$cp_options['mem']['x_auth'] = $cp_options['mem_user'];
				unset( $cp_options['mem_user'] );
			}
			if ( isset( $cp_options['mem_username'] ) ) {
				$cp_options['mem']['username'] = $cp_options['mem_username'];
				unset( $cp_options['mem_username'] );
			}
			if ( isset( $cp_options['mem_method'] ) ) {
				$cp_options['mem']['method'] = $cp_options['mem_method'];
				unset( $cp_options['mem_method'] );
			}
			if ( isset( $cp_options['mem_theme'] ) ) {
				$cp_options['mem']['theme'] = $cp_options['mem_theme'];
				unset( $cp_options['mem_theme'] );
			}
			if ( isset( $cp_options['ua'] ) ) {
				$cp_options['mem']['user_agent'] = $cp_options['ua'];
				unset( $cp_options['ua'] );
			}
			if ( isset( $cp_options['cookies'] ) ) {
				$cp_options['mem']['cookies'] = $cp_options['cookies'];
				unset( $cp_options['cookies'] );
			}
			if ( isset( $cp_options['mem_content_type'] ) ) {
				$cp_options['mem']['content_type'] = $cp_options['mem_content_type'];
				unset( $cp_options['mem_content_type'] );
			}
			if ( isset( $cp_options['payload'] ) ) {
				$cp_options['mem']['payload'] = $cp_options['payload'];
				unset( $cp_options['payload'] );
			}
			if ( isset( $cp_options['custom_headers'] ) ) {
				$cp_options['mem']['custom_headers'] = $cp_options['custom_headers'];
				unset( $cp_options['custom_headers'] );
			}
			if ( isset( $cp_options['exclusions'] ) ) {
				$cp_options['mem']['exclusions'] = $cp_options['exclusions'];
				unset( $cp_options['exclusions'] );
			}
		}

		// Version 1.8.1
		if ( version_compare( $cp_options['version'], '1.8.1', '<' ) ) {
			CodeProfiler_WPCron::install();
		}

		/**
		 * Version 1.9.2
		 */
		if ( version_compare( $cp_options['version'], '1.9.2', '<' ) ) {
			unset( $cp_options['warn_composer'] );
		}

		// Adjust current version
		$cp_options['version'] = CODE_PROFILER_VERSION;

		// Update version in the DB
		update_option('code-profiler', $cp_options );
	}

}
add_action('admin_init', 'code_profiler_init_update');

// =====================================================================
// Verify or create our storage folder in the uploads directory.
// Call during activation and access to the plugin.

function code_profiler_check_uploadsdir() {

	if (! file_exists( CODE_PROFILER_UPLOAD_DIR ) ) {
		mkdir( CODE_PROFILER_UPLOAD_DIR, 0755 );
	}
	if (! is_writable( CODE_PROFILER_UPLOAD_DIR ) ) {
		// PHP running as an Apache module?
		chmod( CODE_PROFILER_UPLOAD_DIR, 0777 );
	}
	if (! file_exists( CODE_PROFILER_UPLOAD_DIR .'/index.html') ) {
		touch( CODE_PROFILER_UPLOAD_DIR .'/index.html');
	}
	// For Apache & Litespeed
	if (! file_exists( CODE_PROFILER_UPLOAD_DIR .'/.htaccess') ) {
		file_put_contents(
			CODE_PROFILER_UPLOAD_DIR .'/.htaccess',
			'Require all denied'
		);
	}
	// Make sure there's a MU plugin directory
	if (! is_dir( WPMU_PLUGIN_DIR ) ) {
		wp_mkdir_p( WPMU_PLUGIN_DIR );
	}


	if (! file_exists( WPMU_PLUGIN_DIR .'/'. CODE_PROFILER_MUPLUGIN ) ) {

		if (! is_writable( WPMU_PLUGIN_DIR ) ) {
			wp_die(
				sprintf(
					__('Error: The MU folder is read only, please make it writable: %s', 'code-profiler'),
					esc_html( WPMU_PLUGIN_DIR ) .'/'
				)
			);
		}

		if (! file_exists( __DIR__ .'/mu.plugin') ) {
			code_profiler_log_error( sprintf(
				esc_html__('Cannot find the MU plugin: %s', 'code-profiler'),
				__DIR__ .'/mu.plugin'
			));
		} else {
			$res = copy( __DIR__ .'/mu.plugin', WPMU_PLUGIN_DIR .'/'. CODE_PROFILER_MUPLUGIN );
			if ( $res === false ) {
				code_profiler_log_error( sprintf(
					esc_html__('Cannot copy the MU plugin to %s', 'code-profiler'),
					WPMU_PLUGIN_DIR
				));
			}
		}
	} else {
		// Update MU plugin it if needed
		if ( md5_file( WPMU_PLUGIN_DIR .'/'. CODE_PROFILER_MUPLUGIN ) !== md5_file( __DIR__ .'/mu.plugin') ) {
			copy( __DIR__ .'/mu.plugin', WPMU_PLUGIN_DIR .'/'. CODE_PROFILER_MUPLUGIN );
		}
	}

	// Log file
	if (! file_exists( CODE_PROFILER_LOG ) ) {
		file_put_contents( CODE_PROFILER_LOG, "<?php exit; ?>\n"	);
	}

}

// =====================================================================
// Check the PHP memory limit and return a suggested size
// for the profiler's buffer.

function code_profiler_suggested_memory() {

	$memory_limit = ini_get('memory_limit');

	if ('-1' == $memory_limit ) {
		// Return max size
		return 10;
	}

	if ( preg_match('/^(\d+)([PTGMK])$/i', $memory_limit, $match ) ) {
		$bytes = (int) $match[1];
		switch ( strtoupper( $match[2] ) ) {
			case 'P':
				$bytes *= 1024;
			case 'T':
				$bytes *= 1024;
			case 'G':
				$bytes *= 1024;
			case 'M':
				$bytes *= 1024;
			case 'K':
				$bytes *= 1024;
		}
		// 256 MB
		if ( $bytes >= 268435456 ) {
			return 10;
		// 128 MB
		} elseif ( $bytes >= 134217728 ) {
			return 7;
		// 64 MB
		} elseif ( $bytes >= 67108864 ) {
			return 4;
		// <64 MB
		} else {
			return 1;
		}
	}
	// Don't know :/
	return 5;
}

// =====================================================================
// Create the default options.

function code_profiler_default_options() {

	$buffer = code_profiler_suggested_memory();

	$cp_options = [
		'show_paths' 			=> 'relative',
		'display_name'			=> 'full',
		'truncate_name'		=> 30,
		'chart_type'			=> 'x',
		'chart_max_plugins'	=> 25,
		'hide_empty_value'	=> 1,
		'table_max_rows'		=> 30,
		'enable_wpcli'			=> 1,
		'disable_wpcron'		=> 1,
		'http_response'		=> '^(?:3|4|5)\d{2}$',
		'accuracy'				=> 1,
		'buffer'					=> (int) $buffer
	];

	update_option('code-profiler', $cp_options );

}

// ===================================================================== 2023-06-14
// Create a profile name.

function code_profiler_profile_name() {

	return date('Y-m-d_') . substr( time(), 4 );
}

// ===================================================================== 2023-06-14
// Disable PHP display_errors so that notice, warning and error messages
// don't show up in the AJAX response.

function code_profiler_hide_errors() {

	ini_set('display_errors', 0 );
}

// =====================================================================
// Disable opcode cache.

function code_profiler_disable_opcode() {

	try {
		if ( extension_loaded('Zend OPcache') ) {
			ini_set('opcache.enable', 0);
		} elseif ( extension_loaded('wincache') ) {
			ini_set('wincache.fcenabled', 0);
		}
		set_time_limit(0);
		ini_set('memory_limit', -1);

	} catch ( Exception $e ) { }

	$cp_options = get_option('code-profiler');
	/**
	 * Disable WP-CRON.
	 */
	if (! empty( $cp_options['disable_wpcron'] ) ) {
		if (! defined('DISABLE_WP_CRON') ) {
			define('DISABLE_WP_CRON', true );
		}
	}
	/**
	 * Enable PHP error logging.
	 */
	if (! empty( $cp_options['php_error'] ) ) {
		ini_set('log_errors', 1 );
		$phplog = ini_get('error_log');
		if ( empty( $phplog ) || $phplog === false ) {
			ini_set('error_log', WP_CONTENT_DIR .'/debug.log');
		} else {
			ini_set('error_log', $phplog );
		}
	}
}

// =====================================================================
// Verify the security key when running the profiler.

function code_profiler_verify_key() {

	$response = [
		'status'		=> 'error',
		// We cannot load translation here.
		'message'	=> 'Security keys do not match. Reload the page and try again (%s)'
	];

	if ( empty( $_REQUEST['profiler_key'] ) ) {
		$response['message'] = sprintf( $response['message'], '001');

	} else {
		$file = CODE_PROFILER_UPLOAD_DIR .'/key_'. sha1( $_REQUEST['profiler_key'] ) .'.tmp';
		if ( file_exists( $file ) ) {
			// Delete it and accept the request
			unlink( $file );
			return;
		}
		$response['message'] = sprintf( $response['message'], '002');
	}
	wp_send_json( $response );

}

// =====================================================================
// Write message to the log.
// Log level can be a combination of INFO (1), WARN (2), ERROR (4)
// and DEBUG (8).

function code_profiler_write2log( $message, $level, $create ) {

	if ( empty( $create ) ) {
		file_put_contents(
			CODE_PROFILER_LOG,
			time() ."~~$level~~$message\n",
			FILE_APPEND
		);
	} else {
		file_put_contents(
			CODE_PROFILER_LOG,
			time() ."~~$level~~$message\n"
		);
	}
}

function code_profiler_log_info(  $string, $create = 0 ) {
	code_profiler_write2log( $string, 1, $create );
}
function code_profiler_log_warn(  $string, $create = 0 ) {
	code_profiler_write2log( $string, 2, $create );
}
function code_profiler_log_error( $string, $create = 0 ) {
	code_profiler_write2log( $string, 4, $create );
}
function code_profiler_log_debug( $string, $create = 0 ) {
	code_profiler_write2log( $string, 8, $create );
}

// =====================================================================
// Retrieve and return matching files from a directory.

function code_profiler_glob( $directory, $regex, $pathname = false ) {

	$list = [];

	foreach ( new DirectoryIterator( $directory ) as $finfo ) {
		if (! $finfo->isDot() && preg_match("`$regex`", $finfo->getFilename() ) ) {
			if ( $pathname ) {
				$list[] = $finfo->getPathname();
			} else {
				$list[] = $finfo->getFilename();
			}
		}
	}
	return $list;
}

// =====================================================================
// Verify if a profile exists and return its full path.

function code_profiler_get_profile_path( $id, $type = 'slugs') {

	if (! empty( $id ) && preg_match('/^\d{10}\.\d+$/', $id ) ) {

		$glob = code_profiler_glob(CODE_PROFILER_UPLOAD_DIR, "$id\..+\.$type\.profile$", true);

		if ( is_array( $glob ) && ! empty( $glob[0] ) ) {
			if ( preg_match( "`$id\.(.+?).$type.profile$`", $glob[0], $match ) ) {

				return CODE_PROFILER_UPLOAD_DIR. "/$id.{$match[1]}";
			}
		}
	}
	return false;
}

// =====================================================================
// Open, parse and return the content of a profile file.

function code_profiler_get_profile_data( $file, $type = 'slugs') {

	$buffer = [];

	$fh = fopen( "$file.$type.profile", 'rb');

	if ( $fh === false ) {
		$err = sprintf(
			CODE_PROFILER_ERROR_NOTICE,
			sprintf(
				esc_html__('Unable to open profile file: %s.', 'code-profiler'),
				esc_html( "$file.$type.profile" )
			)
		);
		$buffer['error'] = $err;
		return $buffer;
	}

	/**
	 * We don't use fgetcsv() as it requires the $escape parameter since PHP 8.4
	 */
	while (! feof( $fh ) ) {
		$tmp = trim( fgets( $fh, 1000 ) );
		if (! $tmp ) {
			continue;
		}
		$buffer[] = explode( "\t", $tmp );
	}

	fclose( $fh );

	if ( empty( $buffer ) ) {
		$err = sprintf(
			CODE_PROFILER_ERROR_NOTICE,
			sprintf(
				esc_html__('Profile file is empty or corrupted: %s.', 'code-profiler'),
				esc_html( "$file.$type.profile" )
			)
		);
		$buffer['error'] = $err;
		return $buffer;
	}

	// Get rid of the empty field created by fgetcsv
	return array_filter( $buffer );
}

// =====================================================================
// Exit with a json-encoded error message except if we're running
// from WP CLI

function code_profiler_wp_send_json( $response ) {

	if ( defined('WP_CLI') && WP_CLI ) {
		WP_CLI::error( $response['message'] );
	}
	wp_send_json( $response );
}

// =====================================================================
// Retrieve the summary stats for a given profile.

function code_profiler_getsummarystats( $profile_path, $type = 'html') {

	if ( $type == 'html') {
		$open		= '<ul><li>&#10148; ';
		$div		= '</li><li>&#10148; ';
		$close	= '</li></ul>';
	} else {
		// WP-CLI
		$open		= " \u{27A4} ";
		$div		= "\n \u{27A4} ";
		$close	= "\n\n";
	}

	if (! file_exists( "$profile_path.summary.profile" ) ) {
		return false;
	}
	$decode	= json_decode( file_get_contents( "$profile_path.summary.profile" ) );

	$string	= $open;

	if ( empty( $decode->items ) ) {
		$tmp = __('N/A', 'code-profiler');
	} else {
		$tmp = (int) --$decode->items;
	}
	$string .= sprintf(
		__('%s plugins and 1 theme', 'code-profiler'),
		$tmp
	);

	$string .= $div;

	if ( empty( $decode->time ) ) {
		$tmp = __('N/A', 'code-profiler');
	} else {
		$tmp = number_format( $decode->time, 4 );
	}
	$string .= sprintf(
		__('Execution time: %ss', 'code-profiler'),
		$tmp
	);

	$string .= $div;

	if ( empty( $decode->memory ) ) {
		$tmp = __('N/A', 'code-profiler');
	} else {
		$tmp = number_format( $decode->memory, 2);
	}
	$string .= sprintf(
		__('Peak memory: %s MB', 'code-profiler'),
		$tmp
	);

	$string .= $div;

	if ( empty( $decode->io ) ) {
		$tmp = __('N/A', 'code-profiler');
	} else {
		$tmp = number_format( (int) $decode->io );
	}
	$string .= sprintf(
		__('File I/O operations: %s', 'code-profiler'),
		$tmp
	);

	$string .= $div;

	if ( empty( $decode->queries ) ) {
		$tmp = __('N/A', 'code-profiler');
	} else {
		$tmp = number_format( (int) $decode->queries );
	}
	$string .= sprintf(
		__('SQL queries: %s', 'code-profiler'),
		$tmp
	);

	$string .= $div;

	if ( empty( CODE_PROFILER_ACCURACY[ $decode->precision ] ) ) {
		$tmp = __('N/A', 'code-profiler');
	} else {
		$tmp = CODE_PROFILER_ACCURACY[ $decode->precision ];
	}
	$string .= sprintf(
		__('Accuracy: %s', 'code-profiler'),
		$tmp
	);

	$string .= $close;

	return $string;
}

// =====================================================================
// Remove *tmp files left in the profiles folder.

function code_profiler_cleantmpfiles() {

	$glob = code_profiler_glob( CODE_PROFILER_UPLOAD_DIR, '\.tmp$', true );

	if ( is_array( $glob ) ) {
		foreach( $glob as $file ) {
			unlink( $file );
		}
	}
}

// =====================================================================
// Remove non-ASCII characters from a string.

function code_profiler_ASCII_filter( $string ) {

	return preg_replace('/[\x00-\x1f\x7f-\xff]/', '', $string );
}

// =====================================================================
// Clear the log if it's bigger than 100KB.

function code_profiler_clearlog() {

	if ( file_exists( CODE_PROFILER_LOG ) && filesize( CODE_PROFILER_LOG ) > 100000 ) {
		file_put_contents( CODE_PROFILER_LOG, "<?php exit; ?>\n"	);
	}
}

// ===================================================================== 2023-06-15
// We don't want to be bothered by other themes/plugins' admin notices.

add_action('admin_head', 'code_profiler_hide_admin_notices');

function code_profiler_hide_admin_notices() {
	if ( isset( $_GET['page'] ) && $_GET['page'] == 'code-profiler') {
		remove_all_actions('admin_notices');
		remove_all_actions('all_admin_notices');
		add_filter('admin_footer_text', 'code_profiler_admin_footer');
	}
}

function code_profiler_admin_footer () {
    echo '<span id="footer-thankyou">'.
		sprintf(
			/* Translators: %s are the '<a href="">' and '</a>' anchors */
			esc_html(
				'Thank you for using %sCode Profiler%s.',
				'code-profiler'
			),
			'<a href="https://nintechnet.com/codeprofiler/" target="_blank">',
			'</a>'
		).
		'</span>';
}


// =====================================================================
// Hide/display an element depending on some value.

function code_profiler_showhide( $var, $val ) {

	if ( $var == $val ) {
		echo " style='display:block'";
	} else {
		echo " style='display:none'";
	}
}

// =====================================================================
// Retrieve all themes.

function code_profiler_get_themes() {

	$list = [];

	// Make sure the function is loaded
	if (! function_exists('wp_get_themes') ) {
		require_once ABSPATH . 'wp-includes/theme.php';
	}
	$themes = wp_get_themes();
	foreach( $themes as $k => $v ) {
		if ( $v->Name ) {
			$list[ $k ] = [
				'n' => $v->Name,

				't' => $v->Template
			];
		} else {
			$list[ $k ] = [
				'n' => $k,
				't' => $v->Template
			];
		}
	}
	return $list;
}

// =====================================================================
// Retrieve all available cron events.

function code_profiler_get_crons() {

	$list = [];

	$wp_crons = _get_cron_array();

	if ( empty( $wp_crons ) ) {
		return $list;
	}

	foreach ( $wp_crons as $timestamp => $cronhooks ) {
		foreach ( $cronhooks as $hook => $keys ) {
			$list[] = $hook;
		}
	}
	sort( $list );
	return $list;
}

// =====================================================================
// Create a file with the ABSPATH.

function code_profiler_create_tmpfile() {

	$tmp_folder = dirname( __DIR__ ) .'/tmp';
	$tmp_file   = "$tmp_folder/profiler.inc.php";

	if (! is_dir( $tmp_folder ) ) {
		$res = @ mkdir( $tmp_folder );
		if ( false === $res ) {
			return sprintf(
				__('Cannot create temporary folder: [%s]. Any attempt to profile a cron event will likely fail.', 'code-profiler'),
				$tmp_folder
			);
		}
	}

	if (! is_file( "$tmp_folder/index.html" ) ) {
		touch( "$tmp_folder/index.html" );
	}

	$res = @ file_put_contents(
		$tmp_file,
		"<?php\nconst ABSPATH = '". ABSPATH ."';\n"
	);
	if ( false === $res ) {
		return sprintf(
			__('Cannot create temporary file: [%s]. Any attempt to profile a cron event will likely fail.', 'code-profiler'),
			$tmp_file
		);
	}
}

// =====================================================================
// EOF
