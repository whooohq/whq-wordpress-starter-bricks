<?php

/**
 * Login Logger for Fuerte-WP Login Security.
 *
 * Handles logging of login attempts, managing lockouts, and database queries.
 * Uses WordPress wpdb for safe database operations.
 *
 * @link       https://actitud.xyz
 * @since      1.7.0
 *
 * @author     Esteban Cuevas <esteban@attitude.cl>
 */

// No access outside WP
defined('ABSPATH') || die();

/**
 * Login Logger class for database operations.
 *
 * @since 1.7.0
 */
class Fuerte_Wp_Login_Logger
{
    /**
     * WordPress database instance.
     *
     * @since 1.7.0
     *
     * @var wpdb
     */
    private $wpdb;

    /**
     * Table names with prefix.
     *
     * @since 1.7.0
     *
     * @var array
     */
    private $tables = [];

    /**
     * Cached settings.
     *
     * @since 1.7.0
     *
     * @var array
     */
    private static $cached_settings = null;

    /**
     * Initialize Login Logger.
     *
     * @since 1.7.0
     */
    public function __construct()
    {
        global $wpdb;

        $this->wpdb = $wpdb;
        $this->tables = [
            'attempts' => $wpdb->prefix . 'fuertewp_login_attempts',
            'lockouts' => $wpdb->prefix . 'fuertewp_login_lockouts',
            'ips' => $wpdb->prefix . 'fuertewp_login_ips',
        ];
    }

    /**
     * Get cached settings (static method for efficiency).
     *
     * @since 1.7.0
     *
     * @return array Settings with max_attempts and lockout_duration
     */
    private static function get_cached_settings()
    {
        if (self::$cached_settings === null) {
            // Get settings using centralized cache
            self::$cached_settings = [
                'max_attempts' => (int) Fuerte_Wp_Config::get_field('login_max_attempts', 5),
                'lockout_duration' => (int) Fuerte_Wp_Config::get_field('login_lockout_duration', 60),
            ];
        }

        return self::$cached_settings;
    }

    /**
     * Log a login attempt.
     *
     * @since 1.7.0
     *
     * @param string $username Username attempted
     * @param string $ip IP address
     * @param string $status Status (success, failed, blocked)
     * @param string $message Result message
     * @param string $user_agent User agent (optional)
     *
     * @return int|false Insert ID on success, false on failure
     */
    public function log_attempt($username, $ip, $status, $message = '', $user_agent = '')
    {
        // Use direct database insertion without memory manager
        $result = $this->wpdb->insert(
            $this->tables['attempts'],
            [
                'ip_address' => sanitize_text_field($ip),
                'username' => sanitize_text_field($username),
                'attempt_time' => current_time('mysql'),
                'status' => $status,
                'user_agent' => sanitize_text_field(substr($user_agent, 0, 255)), // Limit user agent length
                'result_message' => sanitize_text_field(substr($message, 0, 255)), // Limit message length
            ],
            [
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
            ]
        );

        if ($result === false) {
            return false;
        }

        // Memory optimization removed - use default WordPress memory management

        return $this->wpdb->insert_id;
    }

    /**
     * Get failed attempts for IP or username within time window.
     *
     * @since 1.7.0
     *
     * @param string $ip IP address
     * @param string $username Username
     * @param int $minutes Time window in minutes
     *
     * @return int Number of failed attempts
     */
    public function get_failed_attempts($ip, $username, $minutes = 60)
    {
        $cutoff_time = date('Y-m-d H:i:s', strtotime("-$minutes minutes"));

        $count = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->tables['attempts']}
                WHERE status = 'failed'
                AND attempt_time >= %s
                AND (
                    (ip_address = %s AND ip_address IS NOT NULL)
                    OR (username = %s AND username IS NOT NULL)
                )",
                $cutoff_time,
                $ip,
                $username
            )
        );

        return (int) $count;
    }

    /**
     * Check if IP or username is currently locked out.
     *
     * @since 1.7.0
     *
     * @param string $ip IP address
     * @param string $username Username (optional)
     *
     * @return array|false Lockout data if locked, false otherwise
     */
    public function get_active_lockout($ip, $username = '')
    {
        $now = current_time('mysql');

        $sql = "SELECT * FROM {$this->tables['lockouts']} WHERE unlock_time > %s AND (";

        $conditions = [];
        $values = [$now];

        // Check IP lockout
        if (!empty($ip)) {
            $conditions[] = 'ip_address = %s';
            $values[] = $ip;
        }

        // Check username lockout if provided
        if (!empty($username)) {
            $conditions[] = '(username = %s OR username IS NULL)';
            $values[] = $username;
        }

        $sql .= implode(' OR ', $conditions) . ') LIMIT 1';

        $query = $this->wpdb->prepare($sql, ...$values);
        $result = $this->wpdb->get_row($query);

        if ($result && strtotime($result->unlock_time) > time()) {
            return $result;
        }

        // Clean up expired lockout if exists
        if ($result) {
            $this->remove_lockout($result->id);
        }

        return false;
    }

    /**
     * Create a lockout for IP or username.
     *
     * @since 1.7.0
     *
     * @param string $ip IP address (optional if username provided)
     * @param string $username Username (optional if IP provided)
     * @param int $duration Duration in minutes
     * @param string $reason Lockout reason
     * @param int $attempt_count Number of attempts (default 1)
     *
     * @return int|false Lockout ID on success, false on failure
     */
    public function create_lockout($ip, $username, $duration, $reason, $attempt_count = 1)
    {
        $unlock_time = date('Y-m-d H:i:s', strtotime("+$duration minutes"));

        // Use INSERT ... ON DUPLICATE KEY UPDATE to handle unique constraints
        $sql = $this->wpdb->prepare(
            "INSERT INTO {$this->tables['lockouts']}
            (ip_address, username, lockout_time, unlock_time, attempt_count, reason)
            VALUES (%s, %s, %s, %s, %d, %s)
            ON DUPLICATE KEY UPDATE
            unlock_time = VALUES(unlock_time),
            attempt_count = VALUES(attempt_count),
            reason = VALUES(reason)",
            $ip,
            $username,
            current_time('mysql'),
            $unlock_time,
            $attempt_count,
            $reason
        );

        $result = $this->wpdb->query($sql);

        if ($result === false) {
            return false;
        }

        // Return the ID (may not be insert_id for ON DUPLICATE KEY)
        return $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT id FROM {$this->tables['lockouts']} WHERE ip_address = %s OR username = %s LIMIT 1",
                $ip,
                $username
            )
        );
    }

    /**
     * Remove a lockout.
     *
     * @since 1.7.0
     *
     * @param int $lockout_id Lockout ID
     *
     * @return bool True on success, false on failure
     */
    public function remove_lockout($lockout_id)
    {
        return $this->wpdb->delete(
            $this->tables['lockouts'],
            ['id' => (int) $lockout_id],
            ['%d']
        ) !== false;
    }

    /**
     * Get all active lockouts.
     *
     * @since 1.7.0
     *
     * @return array Array of lockout data
     */
    public function get_active_lockouts()
    {
        $now = current_time('mysql');

        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->tables['lockouts']} WHERE unlock_time > %s ORDER BY unlock_time ASC",
                $now
            )
        );
    }

    /**
     * Get login attempts with filters and pagination.
     *
     * @since 1.7.0
     *
     * @param array $args Query arguments
     *
     * @return array Attempts data
     */
    public function get_attempts($args = [])
    {
        $defaults = [
            'status' => '',
            'ip' => '',
            'username' => '',
            'date_from' => '',
            'date_to' => '',
            'limit' => 50,
            'offset' => 0,
            'orderby' => 'attempt_time',
            'order' => 'DESC',
        ];

        $args = wp_parse_args($args, $defaults);

        $where = ['1=1'];
        $values = [];

        if (!empty($args['status'])) {
            $where[] = 'status = %s';
            $values[] = $args['status'];
        }

        if (!empty($args['ip'])) {
            $where[] = 'ip_address = %s';
            $values[] = $args['ip'];
        }

        if (!empty($args['username'])) {
            $where[] = 'username = %s';
            $values[] = $args['username'];
        }

        if (!empty($args['date_from'])) {
            $where[] = 'attempt_time >= %s';
            $values[] = $args['date_from'];
        }

        if (!empty($args['date_to'])) {
            $where[] = 'attempt_time <= %s';
            $values[] = $args['date_to'];
        }

        $where_clause = implode(' AND ', $where);

        // Validate orderby and order
        $allowed_orderby = ['attempt_time', 'status', 'ip_address', 'username'];

        if (!in_array($args['orderby'], $allowed_orderby)) {
            $args['orderby'] = 'attempt_time';
        }

        $args['order'] = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';

        $sql = "SELECT * FROM {$this->tables['attempts']}
                WHERE $where_clause
                ORDER BY {$args['orderby']} {$args['order']}, id {$args['order']}
                LIMIT %d OFFSET %d";

        $values[] = (int) $args['limit'];
        $values[] = (int) $args['offset'];

        if (!empty($values)) {
            $query = $this->wpdb->prepare($sql, ...$values);
        } else {
            $query = $sql;
        }

        return $this->wpdb->get_results($query);
    }

    /**
     * Get total count of attempts matching filters.
     *
     * @since 1.7.0
     *
     * @param array $args Query arguments
     *
     * @return int Total count
     */
    public function get_attempts_count($args = [])
    {
        $defaults = [
            'status' => '',
            'ip' => '',
            'username' => '',
            'date_from' => '',
            'date_to' => '',
        ];

        $args = wp_parse_args($args, $defaults);

        $where = ['1=1'];
        $values = [];

        if (!empty($args['status'])) {
            $where[] = 'status = %s';
            $values[] = $args['status'];
        }

        if (!empty($args['ip'])) {
            $where[] = 'ip_address = %s';
            $values[] = $args['ip'];
        }

        if (!empty($args['username'])) {
            $where[] = 'username = %s';
            $values[] = $args['username'];
        }

        if (!empty($args['date_from'])) {
            $where[] = 'attempt_time >= %s';
            $values[] = $args['date_from'];
        }

        if (!empty($args['date_to'])) {
            $where[] = 'attempt_time <= %s';
            $values[] = $args['date_to'];
        }

        $where_clause = implode(' AND ', $where);

        $sql = "SELECT COUNT(*) FROM {$this->tables['attempts']} WHERE $where_clause";

        if (!empty($values)) {
            $query = $this->wpdb->prepare($sql, ...$values);
        } else {
            $query = $sql;
        }

        return (int) $this->wpdb->get_var($query);
    }

    /**
     * Get lockout statistics.
     *
     * @since 1.7.0
     *
     * @return array Statistics data
     */
    public function get_lockout_stats()
    {
        $stats = [];

        // Total lockouts
        $stats['total_lockouts'] = (int) $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->tables['lockouts']}"
        );

        // Active lockouts
        $now = current_time('mysql');
        $stats['active_lockouts'] = (int) $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->tables['lockouts']} WHERE unlock_time > %s",
                $now
            )
        );

        // Failed attempts today
        $today = date('Y-m-d 00:00:00');
        $stats['failed_today'] = (int) $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->tables['attempts']} WHERE status = 'failed' AND attempt_time >= %s",
                $today
            )
        );

        // Failed attempts this week
        $week_ago = date('Y-m-d 00:00:00', strtotime('-7 days'));
        $stats['failed_week'] = (int) $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->tables['attempts']} WHERE status = 'failed' AND attempt_time >= %s",
                $week_ago
            )
        );

        return $stats;
    }

    /**
     * Clean up old login attempt records.
     *
     * Runs via cron job. Removes records older than retention period.
     *
     * @since 1.7.0
     *
     * @return int Number of records deleted
     */
    public function cleanup_old_records()
    {
        $retention_days = (int) Fuerte_Wp_Config::get_field('login_data_retention', 30);

        if ($retention_days <= 0) {
            return 0;
        }

        $cutoff_date = date('Y-m-d H:i:s', strtotime("-$retention_days days"));

        // Delete old attempts
        $deleted = $this->wpdb->query(
            $this->wpdb->prepare(
                "DELETE FROM {$this->tables['attempts']} WHERE attempt_time < %s",
                $cutoff_date
            )
        );

        if ($deleted === false) {
            return 0;
        }

        // Delete expired lockouts
        $now = current_time('mysql');
        $this->wpdb->query(
            $this->wpdb->prepare(
                "DELETE FROM {$this->tables['lockouts']} WHERE unlock_time < %s",
                $now
            )
        );

        return (int) $deleted;
    }

    /**
     * Clear all login attempts (admin function).
     *
     * @since 1.7.0
     *
     * @return bool True on success, false on failure
     */
    public function clear_all_attempts()
    {
        $result = $this->wpdb->query("DELETE FROM {$this->tables['attempts']}");

        return $result !== false;
    }

    /**
     * Reset lockouts (admin function).
     *
     * @since 1.7.0
     *
     * @return bool True on success, false on failure
     */
    public function reset_all_lockouts()
    {
        $result = $this->wpdb->query("DELETE FROM {$this->tables['lockouts']}");

        return $result !== false;
    }

    /**
     * Get remaining attempts before lockout.
     *
     * @since 1.7.0
     *
     * @param string $ip IP address
     * @param string $username Username
     *
     * @return int Remaining attempts
     */
    public function get_remaining_attempts($ip, $username)
    {
        $max_attempts = (int) Fuerte_Wp_Config::get_field('login_max_attempts', 5);

        $failed_count = $this->get_failed_attempts($ip, $username);

        return max(0, $max_attempts - $failed_count);
    }

    /**
     * Get time until unlock for current lockout.
     *
     * @since 1.7.0
     *
     * @param string $ip IP address
     * @param string $username Username
     *
     * @return int Seconds until unlock (0 if not locked)
     */
    public function get_seconds_until_unlock($ip, $username)
    {
        $lockout = $this->get_active_lockout($ip, $username);

        if (!$lockout) {
            return 0;
        }

        $unlock_timestamp = strtotime($lockout->unlock_time);
        $current_timestamp = time();

        return max(0, $unlock_timestamp - $current_timestamp);
    }

    /**
     * Check if IP has exceeded maximum failed attempts.
     *
     * @since 1.7.0
     *
     * @param string $ip IP address
     * @param string $username Username (optional, used for context like 'registration')
     *
     * @return bool True if exceeded, false otherwise
     */
    public function has_exceeded_attempts($ip, $username = '')
    {
        $settings = self::get_cached_settings();
        $max_attempts = $settings['max_attempts'];

        $failed_count = $this->get_failed_attempts($ip, $username);

        return $failed_count >= $max_attempts;
    }

    /**
     * Lock an IP address (alias for create_lockout with standardized parameters).
     *
     * @since 1.7.0
     *
     * @param string $ip IP address
     * @param string $username Username (optional)
     * @param string $reason Lockout reason
     *
     * @return int|false Lockout ID on success, false on failure
     */
    public function lock_ip($ip, $username = '', $reason = 'Login limit exceeded')
    {
        $settings = self::get_cached_settings();
        $lockout_duration = $settings['lockout_duration'];

        // Get the current failed attempt count for logging
        $failed_count = $this->get_failed_attempts($ip, $username);

        return $this->create_lockout($ip, $username, $lockout_duration, $reason, $failed_count);
    }
}
