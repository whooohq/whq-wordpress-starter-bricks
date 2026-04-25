<?php

/**
 * Login Manager for Fuerte-WP Login Security.
 *
 * Main class that handles login attempt validation, lockouts,
 * and integrates with WordPress authentication system.
 *
 * @link       https://actitud.xyz
 * @since      1.7.0
 *
 * @author     Esteban Cuevas <esteban@attitude.cl>
 */

// No access outside WP
defined('ABSPATH') || die();

/**
 * Login Manager class for authentication handling.
 *
 * @since 1.7.0
 */
class Fuerte_Wp_Login_Manager
{
    /**
     * IP Manager instance.
     *
     * @since 1.7.0
     *
     * @var Fuerte_Wp_IP_Manager
     */
    private $ip_manager;

    /**
     * Login Logger instance.
     *
     * @since 1.7.0
     *
     * @var Fuerte_Wp_Login_Logger
     */
    private $logger;

    /**
     * Cached client IP address for current request.
     *
     * @since 1.7.0
     *
     * @var string
     */
    private $cached_ip = null;

    /**
     * Cached settings for current request.
     *
     * @since 1.7.0
     *
     * @var array
     */
    private $cached_settings = null;

    /**
     * Initialize Login Manager.
     *
     * @since 1.7.0
     */
    public function __construct()
    {
        $this->ip_manager = new Fuerte_Wp_IP_Manager();
        $this->logger = new Fuerte_Wp_Login_Logger();
    }

    /**
     * Run Login Manager.
     *
     * Sets up all hooks and filters.
     *
     * @since 1.7.0
     */
    public function run()
    {
        // Early exit #1: Plugin disabled
        if (defined('FUERTEWP_DISABLE') && FUERTEWP_DISABLE) {
            return;
        }

        // Early exit #2: CLI requests (login security not needed)
        if (defined('WP_CLI') && WP_CLI) {
            return;
        }

        // Early exit #3: Cron jobs (except our cleanup cron)
        if (wp_doing_cron() && (!isset($_REQUEST['action']) || $_REQUEST['action'] !== 'fuertewp_cleanup_login_logs')) {
            return;
        }

        // Early exit #4: AJAX requests not related to login
        if (wp_doing_ajax() && !in_array($_REQUEST['action'] ?? '', ['fuertewp_get_remaining_attempts'])) {
            return;
        }

        // Early exit #5: Check if login security is enabled
        if (!$this->is_enabled()) {
            return;
        }

        // Only register hooks if we get past all early exits
        $this->register_login_hooks();
    }

    /**
     * Register login-related hooks.
     *
     * @since 1.7.0
     */
    private function register_login_hooks()
    {
        // Hook into authenticate filter with proper priorities
        add_filter('authenticate', [$this, 'track_credentials'], 30, 3);
        add_filter('authenticate', [$this, 'authenticate'], 20, 3);
        add_filter('wp_authenticate_user', [$this, 'wp_authenticate_user'], 10, 2);

        // Hook into login failed
        add_action('wp_login_failed', [$this, 'handle_login_failed'], 10, 1);

        // Hook into successful login
        add_action('wp_login', [$this, 'handle_login_success'], 10, 2);

        // Display messages on login form
        add_action('login_form', [$this, 'display_login_messages']);
        add_action('register_form', [$this, 'display_login_messages']);
        add_action('login_footer', [$this, 'display_gdpr_message'], 5);

        // Hook into registration
        add_filter('registration_errors', [$this, 'protect_registration'], 10, 3);

        // Cleanup hook (cron)
        add_action('fuertewp_cleanup_login_logs', [$this, 'cleanup_logs']);

        // AJAX handler for login attempts
        add_action('wp_ajax_nopriv_fuertewp_get_remaining_attempts', [$this, 'ajax_get_remaining_attempts']);
        add_action('wp_ajax_fuertewp_get_remaining_attempts', [$this, 'ajax_get_remaining_attempts']);
    }

    /**
     * Keep track of if user or password are empty, to filter errors correctly.
     *
     * @since 1.7.0
     *
     * @param WP_User|WP_Error|null $user WP_User or WP_Error object
     * @param string $username Username
     * @param string $password Password
     *
     * @return WP_User|WP_Error|null User object
     */
    public function track_credentials($user, $username, $password)
    {

        // Start session if not already started
        if (!session_id()) {
            session_start();
        }

        global $fuertewp_nonempty_credentials;
        $fuertewp_nonempty_credentials = (!empty($username) && !empty($password));

        // Track login attempts left in session
        $_SESSION['fuertewp_login_attempts_left'] = 0;

        return $user;
    }

    /**
     * Authenticate user with login limit checks.
     *
     * This is called early in the authentication process.
     *
     * @since 1.7.0
     *
     * @param WP_User|WP_Error|null $user WP_User or WP_Error object
     * @param string $username Username
     * @param string $password Password
     *
     * @return WP_User|WP_Error Authenticated user or error
     */
    public function authenticate($user, $username, $password)
    {

        // Return if feature is disabled
        if (!$this->is_enabled()) {
            return $user;
        }

        // Return if empty credentials (let WordPress handle it)
        if (empty($username) || empty($password)) {
            return $user;
        }

        $ip = $this->get_cached_ip();

        // Check if IP is whitelisted
        if ($this->ip_manager->is_whitelisted($ip)) {
            return $user;
        }

        // Check if IP is blacklisted
        if ($this->ip_manager->is_blacklisted($ip)) {
            $this->logger->log_attempt($username, $ip, 'blocked', __('IP address blocked', 'fuerte-wp'), $_SERVER['HTTP_USER_AGENT'] ?? '');

            return new WP_Error(
                'fuertewp_ip_blocked',
                __('Your IP address has been blocked from accessing this site.', 'fuerte-wp')
            );
        }

        // Check if username is blacklisted
        if ($this->is_username_blacklisted($username)) {
            $this->logger->log_attempt($username, $ip, 'blocked', __('Username blocked', 'fuerte-wp'), $_SERVER['HTTP_USER_AGENT'] ?? '');

            return new WP_Error(
                'fuertewp_username_blocked',
                __('This username is not allowed.', 'fuerte-wp')
            );
        }

        // Check for active lockout
        $lockout = $this->logger->get_active_lockout($ip, $username);

        if ($lockout) {
            $seconds_until_unlock = $this->logger->get_seconds_until_unlock($ip, $username);

            if ($seconds_until_unlock > 0) {
                $this->logger->log_attempt($username, $ip, 'blocked', sprintf(__('Locked out for %d seconds', 'fuerte-wp'), $seconds_until_unlock), $_SERVER['HTTP_USER_AGENT'] ?? '');

                return new WP_Error(
                    'fuertewp_locked_out',
                    sprintf(
                        /* translators: %d: minutes */
                        __('You are locked out. Try again in %d minutes.', 'fuerte-wp'),
                        ceil($seconds_until_unlock / 60)
                    )
                );
            }
        }

        return $user;
    }

    /**
     * Additional authentication check after user validation.
     *
     * @since 1.7.0
     *
     * @param WP_User|WP_Error $user User object
     * @param string $password Password
     *
     * @return WP_User|WP_Error User object or error
     */
    public function wp_authenticate_user($user, $password)
    {
        if (is_wp_error($user)) {
            return $user;
        }

        if (!$this->is_enabled()) {
            return $user;
        }

        $ip = $this->get_cached_ip();

        // Check if IP is currently locked out
        $lockout = $this->logger->get_active_lockout($ip);

        if ($lockout) {
            $time_remaining = $this->get_time_remaining($lockout->unlock_time);

            return new WP_Error(
                'fuertewp_locked_out',
                sprintf(__('Too many failed login attempts. Please try again in %s.', 'fuerte-wp'), $time_remaining)
            );
        }

        return $user;
    }

    /**
     * Handle failed login attempt.
     *
     * @since 1.7.0
     *
     * @param string $username Username that failed
     */
    public function handle_login_failed($username)
    {
        if (!session_id()) {
            session_start();
        }

        if (!$this->is_enabled()) {
            return;
        }

        $ip = $this->get_cached_ip();
        $settings = $this->get_cached_settings();

        $max_attempts = $settings['max_attempts'];
        $lockout_duration = $settings['lockout_duration'];
        $increasing_lockout = $settings['increasing_lockout'];

        // Log the failed attempt
        $this->logger->log_attempt($username, $ip, 'failed', __('Login failed', 'fuerte-wp'), $_SERVER['HTTP_USER_AGENT'] ?? '');

        // Get failed attempts count
        $failed_count = $this->logger->get_failed_attempts($ip, $username);

        // Update session with attempts remaining
        $attempts_left = max(0, $max_attempts - $failed_count);
        $_SESSION['fuertewp_login_attempts_left'] = $attempts_left;

        // Check if we should lock out
        if ($failed_count >= $max_attempts) {
            // Calculate lockout duration
            $lockout_minutes = $lockout_duration;

            // Apply increasing lockout if enabled
            if ($increasing_lockout) {
                // Exponential backoff: 1x, 2x, 4x, 8x, etc.
                $multiplier = pow(2, floor($failed_count / $max_attempts));
                $lockout_minutes = $lockout_duration * $multiplier;

                // Cap at 24 hours max
                $lockout_minutes = min($lockout_minutes, 1440);
            }

            // Create lockout
            $this->logger->create_lockout(
                $ip,
                $username,
                $lockout_minutes,
                sprintf(__('Exceeded %d failed login attempts', 'fuerte-wp'), $failed_count),
                $failed_count
            );

            // Log the lockout
            // Lockout logging removed for production
        }
    }

    /**
     * Handle successful login.
     *
     * @since 1.7.0
     *
     * @param string $user_login User login
     * @param WP_User $user WP_User object
     */
    public function handle_login_success($user_login, $user)
    {
        if (!$this->is_enabled()) {
            return;
        }

        $ip = $this->get_cached_ip();

        // Log successful login
        $this->logger->log_attempt($user_login, $ip, 'success', __('Login successful', 'fuerte-wp'), $_SERVER['HTTP_USER_AGENT'] ?? '');

        // Clear any active lockouts for this IP/username
        $this->clear_lockouts($ip, $user_login);
    }

    /**
     * Display login security messages on login form.
     *
     * @since 1.7.0
     */
    public function display_login_messages()
    {
        if (!$this->is_enabled()) {
            return;
        }

        $ip = $this->get_cached_ip();
        $username = $_POST['log'] ?? '';

        // Check for active lockout
        $lockout = $this->logger->get_active_lockout($ip, $username);

        if ($lockout) {
            $seconds_until_unlock = $this->logger->get_seconds_until_unlock($ip, $username);
            $minutes = ceil($seconds_until_unlock / 60);

            echo '<div id="fuertewp-lockout-message" class="message error" style="padding: 10px; margin: 10px 0; background: #ffe6e6; border-left: 4px solid #dc3232;">';
            echo '<p><strong>' . esc_html__('Locked Out', 'fuerte-wp') . '</strong></p>';
            echo '<p>' . sprintf(
                esc_html__('You are locked out due to too many failed login attempts. Try again in %d minutes.', 'fuerte-wp'),
                $minutes
            ) . '</p>';
            echo '</div>';

            return;
        }

        // Show remaining attempts if any failed
        $remaining = $this->logger->get_remaining_attempts($ip, $username);
        $max_attempts = (int) Fuerte_Wp_Config::get_field('login_max_attempts', 5);

        if ($remaining < $max_attempts) {
            echo '<div id="fuertewp-remaining-message" class="message warning" style="padding: 10px; margin: 10px 0; background: #fff3cd; border-left: 4px solid #ffb900;">';
            echo '<p><strong>' . esc_html__('Warning', 'fuerte-wp') . '</strong></p>';
            echo '<p>' . sprintf(
                esc_html(_n(
                    'You have %d login attempt remaining.',
                    'You have %d login attempts remaining.',
                    $remaining,
                    'fuerte-wp'
                )),
                $remaining
            ) . '</p>';
            echo '</div>';
        }

        // GDPR compliance message is already displayed via login_footer hook
        // to avoid duplication when custom login URLs are used
    }

    /**
     * Display GDPR compliance message.
     *
     * @since 1.7.0
     */
    public function display_gdpr_message()
    {
        // Get GDPR message using configuration
        $gdpr_message = Fuerte_Wp_Config::get('login_security.login_gdpr_message');

        // Use default message if no custom message is set
        if (empty($gdpr_message)) {
            $gdpr_message = __('By proceeding you understand and give your consent that your IP address and browser information might be processed by the security plugins installed on this site.', 'fuerte-wp');
        }

        // Display with same styling as other WordPress login footer text
        echo '<div id="fuertewp-gdpr-message" class="privacy-policy-page-link" style="margin: 1em 0; text-align: center; max-width: 400px; margin-left: auto; margin-right: auto;">';
        echo '<p style="margin: 0; font-size: 13px; line-height: 1.4;">' . esc_html($gdpr_message) . '</p>';
        echo '</div>';
    }

    /**
     * Protect registration by blocking blacklisted usernames.
     *
     * @since 1.7.0
     *
     * @param WP_Error $errors Registration errors
     * @param string $sanitized_user_login Sanitized username
     * @param string $user_email User email
     *
     * @return WP_Error Registration errors
     */
    public function protect_registration($errors, $sanitized_user_login, $user_email)
    {
        if (!$this->is_registration_enabled()) {
            return $errors;
        }

        $ip_address = $this->get_cached_ip();

        // Log registration attempt
        $this->logger->log_attempt($ip_address, $sanitized_user_login, 'registration', '', $_SERVER['HTTP_USER_AGENT'] ?? '');

        // Check if IP is currently locked out
        $lockout = $this->logger->get_active_lockout($ip_address);

        if ($lockout) {
            $time_remaining = $this->get_time_remaining($lockout->unlock_time);
            $errors->add(
                'fuertewp_registration_locked_out',
                sprintf(
                    __('Too many registration attempts. Please try again in %s.', 'fuerte-wp'),
                    $time_remaining
                )
            );

            return $errors;
        }

        // Check registration attempt limits
        if ($this->logger->has_exceeded_attempts($ip_address, 'registration')) {
            // Lock out the IP
            $this->logger->lock_ip($ip_address, $sanitized_user_login, 'Registration limit exceeded');

            $errors->add(
                'fuertewp_registration_limit_exceeded',
                __('Too many registration attempts from this IP address. Registration temporarily blocked.', 'fuerte-wp')
            );

            return $errors;
        }

        // Check if username is blacklisted
        if ($this->is_username_blacklisted($sanitized_user_login)) {
            $errors->add(
                'fuertewp_registration_blocked',
                __('Registration of this username is not allowed.', 'fuerte-wp')
            );
        }

        // Block common admin usernames
        $blocked_usernames = ['admin', 'administrator', 'root', 'test', 'testing'];
        $login_lower = strtolower($sanitized_user_login);

        if (in_array($login_lower, $blocked_usernames)) {
            $errors->add(
                'fuertewp_admin_username_blocked',
                __('This username is not allowed.', 'fuerte-wp')
            );
        }

        return $errors;
    }

    /**
     * Check if feature is enabled.
     *
     * @since 1.7.0
     *
     * @return bool True if enabled, false otherwise
     */
    private function is_enabled()
    {
        // Get login setting using simple configuration
        $enabled = Fuerte_Wp_Config::get_field('login_enable', 'enabled');

        return $enabled === 'enabled' || $enabled === true || $enabled === 1 || $enabled === '1';
    }

    private function is_registration_enabled()
    {
        $enabled = Fuerte_Wp_Config::get_field('registration_enable', 'enabled');

        return $enabled === 'enabled' || $enabled === true || $enabled === 1 || $enabled === '1';
    }

    private function is_username_blacklisted($username)
    {
        $blacklisted_raw = Fuerte_Wp_Config::get_field('username_blacklist', '');

        if (empty($blacklisted_raw)) {
            return false;
        }

        // Parse textarea (one per line)
        $blacklisted = array_filter(array_map('trim', explode("\n", $blacklisted_raw)));

        if (empty($blacklisted)) {
            return false;
        }

        $username_lower = strtolower($username);

        foreach ($blacklisted as $blocked) {
            $blocked_lower = strtolower($blocked);

            if ($username_lower === $blocked_lower) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get cached client IP address.
     *
     * @since 1.7.0
     *
     * @return string IP address
     */
    private function get_cached_ip()
    {
        if ($this->cached_ip === null) {
            $this->cached_ip = $this->ip_manager->get_client_ip();
        }

        return $this->cached_ip;
    }

    /**
     * Get cached settings array.
     *
     * @since 1.7.0
     *
     * @return array Settings with max_attempts, lockout_duration, increasing_lockout
     */
    private function get_cached_settings()
    {
        if ($this->cached_settings === null) {
            // Get settings using centralized cache
            $this->cached_settings = [
                'max_attempts' => (int) Fuerte_Wp_Config::get('login_security.login_max_attempts', 5),
                'lockout_duration' => (int) Fuerte_Wp_Config::get('login_security.login_lockout_duration', 60),
                'increasing_lockout' => Fuerte_Wp_Config::get('login_security.login_increasing_lockout', '') === 'yes',
            ];
        }

        return $this->cached_settings;
    }

    /**
     * Clear lockouts for IP and/or username.
     *
     * @since 1.7.0
     *
     * @param string $ip IP address
     * @param string $username Username (optional)
     */
    private function clear_lockouts($ip, $username = '')
    {
        global $wpdb;

        $table = $wpdb->prefix . 'fuertewp_login_lockouts';

        $where = ['unlock_time <= %s'];
        $values = [current_time('mysql')];

        if (!empty($ip)) {
            $where[] = 'ip_address = %s';
            $values[] = $ip;
        }

        if (!empty($username)) {
            $where[] = '(username = %s OR username IS NULL)';
            $values[] = $username;
        }

        $where_clause = implode(' AND ', $where);

        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $table WHERE $where_clause",
                ...$values
            )
        );
    }

    /**
     * Get display name for lockout reason.
     *
     * @since 1.7.0
     *
     * @param string $reason Internal reason
     *
     * @return string User-friendly reason
     */
    public function get_lockout_reason_display($reason)
    {
        return $reason;
    }

    /**
     * Get all usernames with failed attempts in time window.
     *
     * @since 1.7.0
     *
     * @param int $minutes Time window in minutes
     *
     * @return array Array of usernames
     */
    public function get_usernames_with_failures($minutes = 60)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'fuertewp_login_attempts';
        $cutoff_time = date('Y-m-d H:i:s', strtotime("-$minutes minutes"));

        return $wpdb->get_col(
            $wpdb->prepare(
                "SELECT DISTINCT username FROM $table
                WHERE status = 'failed'
                AND attempt_time >= %s
                AND username != ''",
                $cutoff_time
            )
        );
    }

    /**
     * Get all IPs with failed attempts in time window.
     *
     * @since 1.7.0
     *
     * @param int $minutes Time window in minutes
     *
     * @return array Array of IP addresses
     */
    public function get_ips_with_failures($minutes = 60)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'fuertewp_login_attempts';
        $cutoff_time = date('Y-m-d H:i:s', strtotime("-$minutes minutes"));

        return $wpdb->get_col(
            $wpdb->prepare(
                "SELECT DISTINCT ip_address FROM $table
                WHERE status = 'failed'
                AND attempt_time >= %s
                AND ip_address != ''",
                $cutoff_time
            )
        );
    }

    /**
     * Get formatted time remaining until unlock.
     *
     * @since 1.7.0
     *
     * @param string $unlock_time Unlock time in MySQL datetime format
     *
     * @return string Formatted time remaining (e.g., "1 hour, 30 minutes")
     */
    private function get_time_remaining($unlock_time)
    {
        $unlock_timestamp = strtotime($unlock_time);
        $current_timestamp = time();
        $seconds_remaining = max(0, $unlock_timestamp - $current_timestamp);

        if ($seconds_remaining === 0) {
            return '0 minutes';
        }

        $hours = floor($seconds_remaining / 3600);
        $minutes = floor(($seconds_remaining % 3600) / 60);

        $parts = [];

        if ($hours > 0) {
            $parts[] = sprintf(_n('%d hour', '%d hours', $hours, 'fuerte-wp'), $hours);
        }

        if ($minutes > 0 || empty($parts)) {
            $parts[] = sprintf(_n('%d minute', '%d minutes', $minutes, 'fuerte-wp'), $minutes);
        }

        return implode(', ', $parts);
    }
}
