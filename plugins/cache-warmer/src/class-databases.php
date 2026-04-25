<?php
/**
 * Custom database tables.
 *
 * @package Cache-Warmer
 */

namespace Cache_Warmer;

use DateTime;
use DateTimeZone;

/**
 * Manages custom DB tables.
 */
final class DB {

    /**
     * Custom tables prefix.
     */
    const TABLES_PREFIX = 'cache_warmer_';

    /**
     * Creates/modifies database tables for the plugin by applying DB migrations.
     *
     * Also logs them.
     *
     * @see WC_Install::create_tables()
     */
    public static function do_migrations() {
        global $wpdb;
        $wpdb->hide_errors();

        // DB Migrations.

        $migrations                    = self::get_migrations();
        $last_success_migration_number = (int) Cache_Warmer::$options->get( 'last-db-success-migration-number' );

        $migrations_to_run = array_slice( $migrations, $last_success_migration_number );

        if ( $migrations_to_run ) {
            $db_migrations_log = Cache_Warmer::$options->get( 'db-migration-log' );

            $migration_number = $last_success_migration_number + 1;

            $max_retries = 5;
            $failed      = false;

            foreach ( $migrations_to_run as $migration_to_run ) {
                $retries = 0;

                $queries = explode( ';', $migration_to_run );

                while ( $retries < $max_retries ) {
                    $wpdb->query( 'START TRANSACTION' ); // @codingStandardsIgnoreLine

                    $failed = false;
                    foreach ( $queries as $query ) {
                        if ( trim( $query ) !== '' ) {
                            // Check if this query is for adding a key. Do not error out in this case, and proceed to the next migration.
                            if (
                                preg_match(
                                    '/alter\s+table\s+(.*?)\s+add\s+(index|constraint|key)\s+(.*?)\s+/i',
                                    $query,
                                    $matches
                                )
                            ) {
                                $table_name = trim( $matches[1], '`"\'' );
                                $type       = trim( $matches[2], '`"\'' );
                                $key_name   = trim( $matches[3], '`"\'' );

                                // Check if foreign key already exists.
                                $key_exists = $wpdb->get_var(
                                    $wpdb->prepare(
                                        '
                                        SELECT COUNT(*)
                                        FROM information_schema.STATISTICS
                                        WHERE
                                            TABLE_SCHEMA = DATABASE() AND
                                            TABLE_NAME = %s AND
                                            INDEX_NAME = %s
                                        ',
                                        $table_name,
                                        $key_name
                                    )
                                );

                                // If foreign key already exists, skip this query.
                                if ( $key_exists > 0 ) {
                                    $time_before = DateTime::createFromFormat( 'U.u', microtime( true ) );
                                    $time_before->setTimezone( new DateTimeZone( 'GMT' ) );
                                    $time_before = $time_before->format( 'Y-m-d H:i:s.u' );

                                    $db_migrations_log[] = [
                                        'migration_number' => $migration_number,
                                        'query'            => $query,
                                        'time_before'      => $time_before,
                                        'time_after'       => '',
                                        'error'            => "$type '$key_name' already exists. Skipping.",
                                    ];

                                    continue;
                                }
                            } elseif ( // Check if this query is for adding a column.
                                preg_match(
                                    '/alter\s+table\s+(.*?)\s+add(?:\s+column)?\s+(.*?)\s*(\s|$)/i',
                                    $query,
                                    $matches
                                )
                            ) {
                                $table_name  = trim( $matches[1], '`"\'' );
                                $column_name = trim( $matches[2], '`"\'' );

                                // Check if column already exists.
                                $column_exists = $wpdb->get_var(
                                    $wpdb->prepare(
                                        '
                                        SELECT COLUMN_NAME 
                                        FROM INFORMATION_SCHEMA.COLUMNS 
                                        WHERE TABLE_SCHEMA = DATABASE() 
                                          AND TABLE_NAME = %s 
                                          AND COLUMN_NAME = %s
                                        ',
                                        $table_name,
                                        $column_name
                                    )
                                );

                                // If column already exists, skip this query.
                                if ( $column_exists ) {
                                    $time_before = DateTime::createFromFormat( 'U.u', microtime( true ) );
                                    $time_before->setTimezone( new DateTimeZone( 'GMT' ) );
                                    $time_before = $time_before->format( 'Y-m-d H:i:s.u' );

                                    $db_migrations_log[] = [
                                        'migration_number' => $migration_number,
                                        'query'            => $query,
                                        'time_before'      => $time_before,
                                        'time_after'       => '',
                                        'error'            => "COLUMN '$column_name' already exists. Skipping.",
                                    ];

                                    continue;
                                }
                            }

                            $time_before = microtime( true );
                            $result      = $wpdb->query( $query ); // @codingStandardsIgnoreLine
                            $time_after  = microtime( true );

                            // Log.

                            $time_before = DateTime::createFromFormat( 'U.u', $time_before );
                            $time_before->setTimezone( new DateTimeZone( 'GMT' ) );
                            $time_before = $time_before->format( 'Y-m-d H:i:s.u' );

                            $time_after = DateTime::createFromFormat( 'U.u', $time_after );
                            $time_after->setTimezone( new DateTimeZone( 'GMT' ) );
                            $time_after = $time_after->format( 'Y-m-d H:i:s.u' );

                            $db_migrations_log_elem = [
                                'migration_number' => $migration_number,
                                'query'            => $query,
                                'time_before'      => $time_before,
                                'time_after'       => $time_after,
                                'error'            => '',
                            ];

                            if ( false === $result ) {
                                $db_migrations_log_elem['error'] = $wpdb->last_error;
                            }

                            $db_migrations_log[] = $db_migrations_log_elem;

                            if ( false === $result ) {
                                $failed = true;
                                break;
                            }
                        }
                    }

                    if ( $failed ) {
                        $wpdb->query( 'ROLLBACK' ); // @codingStandardsIgnoreLine
                        $retries ++;
                        sleep( 1 ); // Avoid rapid-fire retries.
                    } else {
                        $wpdb->query( 'COMMIT' ); // @codingStandardsIgnoreLine
                        break;
                    }
                }

                if ( ! $failed ) {
                    $last_success_migration_number = $migration_number;
                } else {
                    break;
                }

                $migration_number ++;
            }

            Cache_Warmer::$options->set( 'last-db-success-migration-number', $last_success_migration_number );
            Cache_Warmer::$options->set( 'last-db-migration-success', (string) ( count( $migrations ) === $last_success_migration_number ) );
            Cache_Warmer::$options->set( 'db-migration-log', $db_migrations_log );
        }
    }

    /**
     * Return a list of Cache-Warmer tables.
     *
     * Parent tables are above children tables.
     *
     * @return array Tables list.
     */
    public static function get_tables() {
        return [
            'warm_ups_logs',
            'warm_ups_list',
            'warm_ups_user_agents',
        ];
    }

    /**
     * Return full tables prefix.
     *
     * @return string Tables prefix.
     */
    public static function get_tables_prefix() {
        global $wpdb;
        return $wpdb->prefix . self::TABLES_PREFIX;
    }

    /**
     * Returns table migrations.
     */
    public static function get_migrations() {
        global $wpdb;

        $collate      = '';
        $table_prefix = self::get_tables_prefix();

        if ( $wpdb->has_cap( 'collation' ) ) {
            $collate = $wpdb->get_charset_collate();
        }

        // Generate a unique constraint name using a hash of the table prefix to ensure it's valid and unique.
        $constraint_name_fk_list_id        = 'fk_' . $table_prefix . 'warm_ups_list_id';
        $constraint_name_fk_user_agents_id = 'fk_' . $table_prefix . 'user_agents_id';

        $migrations = [
            "
                CREATE TABLE IF NOT EXISTS {$table_prefix}warm_ups_list (
                  id BIGINT UNSIGNED AUTO_INCREMENT,
                  warmed_at DATETIME NOT NULL UNIQUE,
                  PRIMARY KEY (id)
                ) $collate ENGINE=InnoDB
            ",
            "
                CREATE TABLE IF NOT EXISTS {$table_prefix}warm_ups_logs (
                  id BIGINT(20) unsigned NOT NULL AUTO_INCREMENT,
                  list_id BIGINT UNSIGNED NOT NULL,
                  log_is_success TINYINT(1) unsigned NOT NULL,
                  log_date DATETIME NOT NULL,
                  log_depth TINYINT unsigned NOT NULL,
                  log_url TEXT NOT NULL,
                  log_post_id BIGINT(20) UNSIGNED NOT NULL,
                  log_time_spent FLOAT DEFAULT NULL,
                  log_time_afterwards FLOAT DEFAULT NULL,
                  log_extra TEXT NOT NULL,
                  log_phase TINYINT unsigned NOT NULL DEFAULT 0,
                  log_content_type TEXT NOT NULL,
                  log_content_length TEXT NOT NULL,
                  log_cf_cache_status TEXT NOT NULL,
                  log_wp_super_cache_status TEXT NOT NULL,
                  log_x_cache_header TEXT NOT NULL,
                  log_visit_type TEXT NOT NULL,
                  PRIMARY KEY (id)
                ) $collate ENGINE=InnoDB
            ",
            "
                INSERT IGNORE INTO {$table_prefix}warm_ups_list (warmed_at) VALUES (0)
            ",
            "
                ALTER TABLE {$table_prefix}warm_ups_logs
                ADD CONSTRAINT {$constraint_name_fk_list_id}
                FOREIGN KEY (list_id)
                REFERENCES {$table_prefix}warm_ups_list(id) ON DELETE CASCADE
            ",
            "
                ALTER TABLE {$table_prefix}warm_ups_logs
                ADD KEY idx_log_post_id (log_post_id)
            ",
            // Add logs user agent field.
            "
                ALTER TABLE {$table_prefix}warm_ups_logs
                ADD `user_agent_id` BIGINT UNSIGNED NULL
            ",
            // Add meta table.
            "
            CREATE TABLE IF NOT EXISTS {$table_prefix}warm_ups_user_agents (
              id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
              `value` VARCHAR(500) NOT NULL,
              PRIMARY KEY (id),              
              UNIQUE KEY uniq_value (`value`)
            ) $collate ENGINE=InnoDB
            ",
            // FK meta field to user agents table.
            "
                ALTER TABLE {$table_prefix}warm_ups_logs
                ADD CONSTRAINT {$constraint_name_fk_user_agents_id}
                FOREIGN KEY (user_agent_id)
                REFERENCES {$table_prefix}warm_ups_user_agents(id) ON DELETE CASCADE
            ",
            /**
             * Change DB structure.
             *
             * 1, 2: So log time spent and log time spent afterwards contain NULL.
             * 3, 4: Update values from 0 to NULL.
             */
            "
                ALTER TABLE `{$table_prefix}warm_ups_logs` CHANGE `log_time_spent` `log_time_spent` FLOAT NULL DEFAULT NULL
            ",
            "
                ALTER TABLE `{$table_prefix}warm_ups_logs` CHANGE `log_time_afterwards` `log_time_afterwards` FLOAT NULL DEFAULT NULL
            ",
            "
                UPDATE `{$table_prefix}warm_ups_logs` SET `log_time_spent` = NULL WHERE `log_time_spent` = 0
            ",
            "
                UPDATE `{$table_prefix}warm_ups_logs` SET `log_time_afterwards` = NULL WHERE `log_time_afterwards` = 0
            ",
            "
                ALTER TABLE {$table_prefix}warm_ups_logs
                ADD `canonical` TEXT DEFAULT ''
            ",
            // Add external_warmer_results field.
            "
                ALTER TABLE `{$table_prefix}warm_ups_logs`
                ADD `external_warmer_results` TEXT DEFAULT NULL
            ",
            // Add warmed at of 1 (for external interval warms).
            "
                INSERT IGNORE INTO {$table_prefix}warm_ups_list (warmed_at) VALUES ('" . External_Warmer::WARMUP_ID . "')
            ",

        ];

        return $migrations;
    }

    /**
     * Truncates all Cache-Warmer tables.
     */
    public static function truncate_tables() {
        global $wpdb;

        $tables_prefix = self::get_tables_prefix();
        $tables        = self::get_tables();

        $wpdb->query( 'SET FOREIGN_KEY_CHECKS = 0;' ); // @codingStandardsIgnoreLine

        foreach ( $tables as $table ) {
            $wpdb->query( "TRUNCATE TABLE {$tables_prefix}{$table};" ); // @codingStandardsIgnoreLine
        }

        $wpdb->query( 'SET FOREIGN_KEY_CHECKS = 1;' ); // @codingStandardsIgnoreLine
    }

    /**
     * Returns warm-up ID from warm-up start date.
     *
     * @param string $warmed_at The date of the warm-up start.
     *
     * @return string|bool Returns warm-up ID.
     */
    public static function get_warm_up_id_from_warm_up_date( $warmed_at ) {
        global $wpdb;

        $table_name = self::get_tables_prefix() . 'warm_ups_list';
        $result     = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id FROM $table_name WHERE warmed_at=%s;",
                $warmed_at
            ),
            ARRAY_N
        ); // @codingStandardsIgnoreLine

        return $result ? (int) $result[0] : false;
    }
}
