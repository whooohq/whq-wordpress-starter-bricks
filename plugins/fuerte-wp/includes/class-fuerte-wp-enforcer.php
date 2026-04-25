<?php

/**
 * Main Enfocer class.
 *
 * @link       https://actitud.xyz
 * @since      1.3.0
 *
 * @author     Esteban Cuevas <esteban@attitude.cl>
 */

// No access outside WP
defined('ABSPATH') || die();

/**
 * Main Fuerte-WP Class.
 */
class Fuerte_Wp_Enforcer
{
    /**
     * Plugin instance.
     *
     * @see get_instance()
     *
     * @type object
     */
    protected static $instance = null;

    public $pagenow;
    public $fuertewp;
    public $current_user;
    public $config;
    /**
     * Auto-update manager instance.
     *
     * @var Fuerte_Wp_Auto_Update_Manager|null
     */
    public $auto_update_manager = null;

    /**
     * Login manager instance.
     *
     * @var Fuerte_Wp_Login_Manager|null
     */
    public $login_manager = null;

    /**
     * Constructor.
     */
    public function __construct()
    {
        // Logger is already initialized in main plugin file
        Fuerte_Wp_Logger::info('Fuerte-WP Enforcer initialized');

        // Load performance optimization classes
        $this->load_optimization_classes();

        //$this->config = $this->config_setup();
    }

    /**
     * Self-healing: Ensure at least one super user exists.
     *
     * Automatically sets the first admin who accesses wp-admin as a super user
     * if no super users are configured yet.
     *
     * @since 1.7.0
     */
    public function ensure_super_user_exists()
    {
        // Only run for admin users (both admin and AJAX contexts)
        if (!current_user_can('manage_options')) {
            return;
        }

        // Check if super users are already configured
        $existing_super_users = Fuerte_Wp_Config::get_field('super_users', [], true);

        if (!empty($existing_super_users)) {
            return; // Super users already configured
        }

        // Check if file configuration exists and has super users
        $file_config = Fuerte_Wp_Config::get_config(true); // Bypass cache to check fresh

        if (!empty($file_config['super_users'])) {
            Fuerte_Wp_Config::set_field('super_users', $file_config['super_users'], true);
            Fuerte_Wp_Logger::info('Super users imported from configuration: ' . implode(', ', $file_config['super_users']));

            return;
        }

        // No super users found - auto-configure current admin user
        $current_user = wp_get_current_user();

        if ($current_user && $current_user->ID > 0 && current_user_can('manage_options')) {
            Fuerte_Wp_Config::set_field('super_users', [$current_user->user_email], true);

            // Also set plugin status if not already set
            $status = Fuerte_Wp_Config::get_field('status');

            if (empty($status)) {
                Fuerte_Wp_Config::set_field('status', 'enabled');
            }

            Fuerte_Wp_Logger::info('Self-healing: Set super user to ' . $current_user->user_email);
        }
    }

    /**
     * Load required classes.
     *
     * @since 1.7.0
     */
    private function load_optimization_classes()
    {
        // Load hook manager
        if (!class_exists('Fuerte_Wp_Hook_Manager')) {
            require_once plugin_dir_path(__FILE__) . 'class-fuerte-wp-hook-manager.php';
        }

        // Load simple config
        if (!class_exists('Fuerte_Wp_Config')) {
            require_once plugin_dir_path(__FILE__) . 'class-fuerte-wp-config.php';
        }
    }

    /**
     * Access this plugin instance.
     */
    public static function get_instance()
    {
        /*
         * To run like:
         * add_action( 'plugins_loaded', [ Fuerte_Wp_Enforcer::get_instance(), 'init' ] );
         */
        null === self::$instance and (self::$instance = new self());

        return self::$instance;
    }

    /**
     * Init the plugin.
     */
    public function run()
    {
        // Initialize auto-update manager
        $this->auto_update_manager = Fuerte_Wp_Auto_Update_Manager::get_instance();

        // Initialize login security manager
        $this->init_login_security();

        $this->enforcer();
    }

    /**
     * Initialize login security features.
     *
     * @since 1.7.0
     */
    private function init_login_security()
    {
        // Register cron cleanup hook
        add_action('fuertewp_cleanup_login_logs', [$this, 'cleanup_login_logs']);

        // Initialize login manager
        $this->login_manager = new Fuerte_Wp_Login_Manager();
        $this->login_manager->run();

        // Load simple configuration (already loaded in constructor)
        // No additional loading needed

        // Initialize login URL hider
        try {
            Fuerte_Wp_Logger::debug('Attempting to initialize Login URL Hider');
            $login_url_hider = Fuerte_Wp_Login_URL_Hider::get_instance();
            Fuerte_Wp_Logger::debug('Login URL Hider initialized successfully');
        } catch (Exception $e) {
            // Error creating Login URL Hider, continue without it
            Fuerte_Wp_Logger::error('Failed to create Login URL Hider: ' . $e->getMessage());
        }

        // AJAX handlers for admin
        if (is_admin() && current_user_can('manage_options')) {
            add_action('wp_ajax_fuertewp_clear_login_logs', [$this, 'ajax_clear_login_logs']);
            add_action('wp_ajax_fuertewp_reset_lockouts', [$this, 'ajax_reset_lockouts']);
            add_action('wp_ajax_fuertewp_export_attempts', [$this, 'ajax_export_attempts']);
            add_action('wp_ajax_fuertewp_export_ips', [$this, 'ajax_export_ips']);
            add_action('wp_ajax_fuertewp_add_ip', [$this, 'ajax_add_ip']);
            add_action('wp_ajax_fuertewp_remove_ip', [$this, 'ajax_remove_ip']);
            add_action('wp_ajax_fuertewp_get_login_logs', [$this, 'ajax_get_login_logs']);
            add_action('wp_ajax_fuertewp_unlock_ip', [$this, 'ajax_unlock_ip']);
            add_action('wp_ajax_fuertewp_unblock_single', [$this, 'ajax_unblock_single']);
        }

        // Self-healing: ensure super users are configured (runs on admin_init)
        add_action('admin_init', [$this, 'ensure_super_user_exists'], 1);
    }

    /**
     * Clean up old login logs (cron job).
     *
     * @since 1.7.0
     */
    public function cleanup_login_logs()
    {
        $logger = new Fuerte_Wp_Login_Logger();
        $logger->cleanup_old_records();
    }

    /**
     * AJAX handler to clear all login logs.
     *
     * @since 1.7.0
     */
    public function ajax_clear_login_logs()
    {
        check_ajax_referer('fuertewp_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'fuerte-wp'));
        }

        $logger = new Fuerte_Wp_Login_Logger();
        $result = $logger->clear_all_attempts();

        wp_send_json_success([
            'message' => __('Login logs cleared successfully', 'fuerte-wp'),
        ]);
    }

    /**
     * AJAX handler to reset all lockouts.
     *
     * @since 1.7.0
     */
    public function ajax_reset_lockouts()
    {
        check_ajax_referer('fuertewp_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'fuerte-wp'));
        }

        $logger = new Fuerte_Wp_Login_Logger();
        $result = $logger->reset_all_lockouts();

        wp_send_json_success([
            'message' => __('Lockouts reset successfully', 'fuerte-wp'),
        ]);
    }

    /**
     * AJAX handler to export login attempts.
     *
     * @since 1.7.0
     */
    public function ajax_export_attempts()
    {
        check_ajax_referer('fuertewp_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'fuerte-wp'));
        }

        $exporter = new Fuerte_Wp_CSV_Exporter();

        // Get filters from request
        $args = [
            'status' => sanitize_text_field($_POST['status'] ?? ''),
            'ip' => sanitize_text_field($_POST['ip'] ?? ''),
            'username' => sanitize_text_field($_POST['username'] ?? ''),
            'date_from' => sanitize_text_field($_POST['date_from'] ?? ''),
            'date_to' => sanitize_text_field($_POST['date_to'] ?? ''),
        ];

        // Export directly (will exit)
        $exporter->export_attempts($args);
    }

    /**
     * AJAX handler to export IP list.
     *
     * @since 1.7.0
     */
    public function ajax_export_ips()
    {
        check_ajax_referer('fuertewp_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'fuerte-wp'));
        }

        $type = sanitize_text_field($_POST['type'] ?? 'whitelist');

        if (!in_array($type, ['whitelist', 'blacklist'])) {
            wp_send_json_error(__('Invalid list type', 'fuerte-wp'));
        }

        $exporter = new Fuerte_Wp_CSV_Exporter();

        // Export directly (will exit)
        $exporter->export_ip_list($type);
    }

    /**
     * AJAX handler to add IP to list.
     *
     * @since 1.7.0
     */
    public function ajax_add_ip()
    {
        check_ajax_referer('fuertewp_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'fuerte-wp'));
        }

        $ip_or_range = sanitize_text_field($_POST['ip_or_range'] ?? '');
        $type = sanitize_text_field($_POST['type'] ?? 'whitelist');
        $note = sanitize_text_field($_POST['note'] ?? '');

        if (empty($ip_or_range)) {
            wp_send_json_error(__('IP or range is required', 'fuerte-wp'));
        }

        if (!in_array($type, ['whitelist', 'blacklist'])) {
            wp_send_json_error(__('Invalid list type', 'fuerte-wp'));
        }

        $ip_manager = new Fuerte_Wp_IP_Manager();
        $result = $ip_manager->add_ip_to_list($ip_or_range, $type, $note);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success([
            'message' => __('IP added successfully', 'fuerte-wp'),
        ]);
    }

    /**
     * AJAX handler to remove IP from list.
     *
     * @since 1.7.0
     */
    public function ajax_remove_ip()
    {
        check_ajax_referer('fuertewp_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'fuerte-wp'));
        }

        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0) {
            wp_send_json_error(__('Invalid ID', 'fuerte-wp'));
        }

        $ip_manager = new Fuerte_Wp_IP_Manager();
        $result = $ip_manager->remove_ip_from_list($id);

        if (!$result) {
            wp_send_json_error(__('Failed to remove IP', 'fuerte-wp'));
        }

        wp_send_json_success([
            'message' => __('IP removed successfully', 'fuerte-wp'),
        ]);
    }

    /**
     * AJAX handler to get login logs table.
     *
     * @since 1.7.0
     */
    public function ajax_get_login_logs()
    {
        check_ajax_referer('fuertewp_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'fuerte-wp'));
        }

        $page = (int) ($_POST['page'] ?? 1);
        $per_page = 50;
        $offset = ($page - 1) * $per_page;

        $logger = new Fuerte_Wp_Login_Logger();
        $attempts = $logger->get_attempts([
            'limit' => $per_page,
            'offset' => $offset,
            'orderby' => 'attempt_time',
            'order' => 'DESC',
        ]);

        $total = $logger->get_attempts_count();
        $total_pages = ceil($total / $per_page);

        // Generate HTML table
        $html = '<table class="wp-list-table widefat fixed striped" id="fuertewp-login-logs">';
        $html .= '<thead><tr>';
        $html .= '<th>' . esc_html__('Date/Time', 'fuerte-wp') . '</th>';
        $html .= '<th class="column-ip">' . esc_html__('IP Address', 'fuerte-wp') . '</th>';
        $html .= '<th>' . esc_html__('Username', 'fuerte-wp') . '</th>';
        $html .= '<th class="column-status">' . esc_html__('Status', 'fuerte-wp') . '</th>';
        $html .= '<th>' . esc_html__('User Agent', 'fuerte-wp') . '</th>';
        $html .= '<th class="column-actions">' . esc_html__('Actions', 'fuerte-wp') . '</th>';
        $html .= '</tr></thead>';

        $html .= '<tbody>';

        if (empty($attempts)) {
            $html .= '<tr><td colspan="6">' . esc_html__('No failed login attempts found.', 'fuerte-wp') . '</td></tr>';
        } else {
            foreach ($attempts as $attempt) {
                $status_class = 'status-' . $attempt->status;
                $status_display = ucfirst($attempt->status);

                $html .= '<tr>';
                $html .= '<td>' . esc_html($attempt->attempt_time) . '</td>';
                $html .= '<td>' . esc_html($attempt->ip_address) . '</td>';
                $html .= '<td>' . esc_html($attempt->username) . '</td>';
                $html .= '<td class="column-status"><span class="' . $status_class . '">' . esc_html($status_display) . '</span></td>';
                $html .= '<td><div class="user-agent-cell">' . esc_html($attempt->user_agent) . '</div></td>';

                // Actions column
                $html .= '<td>';

                if ($attempt->status === 'blocked') {
                    // Check if there's an active lockout for this IP/username combination
                    $active_lockout = $logger->get_active_lockout($attempt->ip_address, $attempt->username);

                    if ($active_lockout) {
                        $html .= '<button type="button" class="button button-small button-secondary fuertewp-unblock-single" ';
                        $html .= 'data-ip="' . esc_attr($attempt->ip_address) . '" ';
                        $html .= 'data-username="' . esc_attr($attempt->username) . '" ';
                        $html .= 'data-id="' . (int) $attempt->id . '">';
                        $html .= esc_html__('Unblock', 'fuerte-wp');
                        $html .= '</button>';
                    }
                }
                $html .= '</td>';

                $html .= '</tr>';
            }
        }

        $html .= '</tbody></table>';

        // Add pagination
        if ($total_pages > 1) {
            $html .= '<div class="fuertewp-pagination" style="margin-top: 20px;">';

            $pagination_links = paginate_links([
                'base' => '#page-%#%',
                'format' => '',
                'current' => $page,
                'total' => $total_pages,
                'prev_text' => '&laquo;',
                'next_text' => '&raquo;',
                'type' => 'array',
            ]);

            if (!empty($pagination_links)) {
                foreach ($pagination_links as $link) {
                    // Extract page number from href attribute and add data-page attribute
                    if (preg_match('/href="#page-(\d+)"/', $link, $matches)) {
                        $page_num = $matches[1];
                        $link = str_replace('href="#page-' . $page_num . '"', 'href="#page-' . $page_num . '" data-page="' . $page_num . '"', $link);
                    } else {
                        // Handle current page (which might not have href)
                        if (preg_match('/class="current"/', $link)) {
                            $link = str_replace('class="current"', 'class="current" data-page="' . $page . '"', $link);
                        }

                        // Handle disabled links (prev/next when no more pages)
                        if (preg_match('/class="[^"]*disabled[^"]*"/', $link)) {
                            continue; // Skip disabled links
                        }
                    }
                    $html .= $link . ' ';
                }
            }

            $html .= '</div>';
        }

        wp_send_json_success([
            'html' => $html,
            'total' => $total,
            'page' => $page,
            'total_pages' => $total_pages,
        ]);
    }

    /**
     * Get cached configuration section with granular caching.
     */
    private function get_cached_config_section(
        $section,
        $callback,
        $expire = DAY_IN_SECONDS,
    ) {
        $cache_key = 'fuertewp_' . $section . '_' . FUERTEWP_VERSION;
        $value = wp_cache_get($cache_key, 'fuertewp');

        if (false === $value) {
            $value = call_user_func($callback);
            wp_cache_set($cache_key, $value, 'fuertewp', $expire);
        }

        return $value;
    }

    /**
     * Get processed list from cached data.
     */
    private function get_processed_list($raw_data)
    {
        if (empty($raw_data)) {
            return [];
        }

        return array_map('trim', explode(PHP_EOL, $raw_data));
    }

    /**
     * Get configuration options using standardized Config class methods.
     * Replaces all direct get_option/carbon_get_theme_option calls with a single standardized method.
     *
     * @since 1.7.2
     *
     * @return array Configuration options
     */
    private function get_config_options_batch()
    {
        return Fuerte_Wp_Config::get_enforcer_config();
    }

    private function config_setup()
    {
        // Get options from simple configuration
        // Fuerte_Wp_Config handles file/database priority and fallback logic
        return Fuerte_Wp_Config::get_config();
    }

    /**
     * Register hooks conditionally based on configuration.
     */
    private function register_conditional_hooks($fuertewp)
    {
        // Only register email-related hooks if email features are enabled
        $this->register_email_hooks($fuertewp);

        // Only register security-related hooks if security features are enabled
        $this->register_security_hooks($fuertewp);

        // Only register UI-related hooks if UI features are enabled
        $this->register_ui_hooks($fuertewp);

        // Only register restriction-related hooks if restrictions are enabled
        $this->register_restriction_hooks($fuertewp);
    }

    /**
     * Register email-related hooks.
     */
    private function register_email_hooks($fuertewp)
    {
        // Always register recovery email hook (core functionality)
        add_filter(
            'recovery_mode_email',
            [__CLASS__, 'recovery_email_address'],
            FUERTEWP_LATE_PRIORITY,
        );

        // Only register sender email hooks if enabled
        if (
            isset($fuertewp['general']['sender_email_enable'])
            && true === $fuertewp['general']['sender_email_enable']
        ) {
            add_filter(
                'wp_mail_from',
                [__CLASS__, 'sender_email_address'],
                FUERTEWP_LATE_PRIORITY,
            );
            add_filter(
                'wp_mail_from_name',
                [__CLASS__, 'sender_email_name'],
                FUERTEWP_LATE_PRIORITY,
            );
        }

        // Only register email notification hooks if any email features are disabled
        $email_hooks_needed = false;

        foreach ($fuertewp['emails'] as $key => $value) {
            if (false === $value) {
                $email_hooks_needed = true;
                break;
            }
        }

        if ($email_hooks_needed) {
            $this->register_email_notification_hooks($fuertewp);
        }
    }

    /**
     * Register email notification hooks.
     */
    private function register_email_notification_hooks($fuertewp)
    {
        // Comment notifications
        if (
            isset($fuertewp['emails']['comment_awaiting_moderation'])
            && false === $fuertewp['emails']['comment_awaiting_moderation']
        ) {
            add_filter(
                'notify_moderator',
                '__return_false',
                FUERTEWP_LATE_PRIORITY,
            );
        }

        if (
            isset($fuertewp['emails']['comment_has_been_published'])
            && false === $fuertewp['emails']['comment_has_been_published']
        ) {
            add_filter(
                'notify_post_author',
                '__return_false',
                FUERTEWP_LATE_PRIORITY,
            );
        }

        // User management notifications
        if (
            isset($fuertewp['emails']['user_reset_their_password'])
            && false === $fuertewp['emails']['user_reset_their_password']
        ) {
            remove_action(
                'after_password_reset',
                'wp_password_change_notification',
                FUERTEWP_LATE_PRIORITY,
            );
        }

        if (
            isset(
                $fuertewp['emails'][
                    'user_confirm_personal_data_export_request'
                ],
            )
            && false
                === $fuertewp['emails']['user_confirm_personal_data_export_request']
        ) {
            remove_action(
                'user_request_action_confirmed',
                '_wp_privacy_send_request_confirmation_notification',
                FUERTEWP_LATE_PRIORITY,
            );
        }

        if (
            isset($fuertewp['emails']['new_user_created'])
            && false === $fuertewp['emails']['new_user_created']
        ) {
            remove_action(
                'register_new_user',
                'wp_send_new_user_notifications',
                FUERTEWP_LATE_PRIORITY,
            );
            remove_action(
                'edit_user_created_user',
                'wp_send_new_user_notifications',
                FUERTEWP_LATE_PRIORITY,
            );
            remove_action(
                'network_site_new_created_user',
                'wp_send_new_user_notifications',
                FUERTEWP_LATE_PRIORITY,
            );
            remove_action(
                'network_site_users_created_user',
                'wp_send_new_user_notifications',
                FUERTEWP_LATE_PRIORITY,
            );
            remove_action(
                'network_user_new_created_user',
                'wp_send_new_user_notifications',
                FUERTEWP_LATE_PRIORITY,
            );
        }

        // Update notifications
        if (
            isset($fuertewp['emails']['automatic_updates'])
            && false === $fuertewp['emails']['automatic_updates']
        ) {
            add_filter(
                'auto_core_update_send_email',
                '__return_false',
                FUERTEWP_LATE_PRIORITY,
            );
            add_filter(
                'send_core_update_notification_email',
                '__return_false',
                FUERTEWP_LATE_PRIORITY,
            );
            add_filter('auto_plugin_update_send_email', '__return_false');
            add_filter('auto_theme_update_send_email', '__return_false');
        }

        // Network notifications
        if (
            isset($fuertewp['emails']['network_new_site_created'])
            && false === $fuertewp['emails']['network_new_site_created']
        ) {
            add_filter(
                'send_new_site_email',
                '__return_false',
                FUERTEWP_LATE_PRIORITY,
            );
        }

        if (
            isset($fuertewp['emails']['network_new_user_site_registered'])
            && false === $fuertewp['emails']['network_new_user_site_registered']
        ) {
            add_filter(
                'wpmu_signup_blog_notification',
                '__return_false',
                FUERTEWP_LATE_PRIORITY,
            );
        }

        if (
            isset($fuertewp['emails']['network_new_site_activated'])
            && false === $fuertewp['emails']['network_new_site_activated']
        ) {
            remove_action(
                'wp_initialize_site',
                'newblog_notify_siteadmin',
                FUERTEWP_LATE_PRIORITY,
            );
        }

        // Error handler
        if (
            isset($fuertewp['emails']['fatal_error'])
            && false === $fuertewp['emails']['fatal_error']
        ) {
            define('WP_DISABLE_FATAL_ERROR_HANDLER', true);
        }
    }

    /**
     * Register security-related hooks.
     */
    private function register_security_hooks($fuertewp)
    {
        // XML-RPC restrictions
        if (
            isset($fuertewp['restrictions']['disable_xmlrpc'])
            && true === $fuertewp['restrictions']['disable_xmlrpc']
        ) {
            add_filter(
                'xmlrpc_enabled',
                '__return_false',
                FUERTEWP_LATE_PRIORITY,
            );
            add_filter(
                'xmlrpc_methods',
                function () {
                    return [];
                },
                FUERTEWP_LATE_PRIORITY,
            );

            add_action(
                'init',
                function () {
                    if (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) {
                        header('HTTP/1.1 403 Forbidden');
                        die(
                            '403 Forbidden - XML-RPC functionality is disabled on this site.'
                        );
                    }
                },
                FUERTEWP_LATE_PRIORITY,
            );
        }

        // REST API restrictions
        if (
            isset($fuertewp['rest_api']['loggedin_only'])
            && true === $fuertewp['rest_api']['loggedin_only']
        ) {
            add_filter(
                'rest_authentication_errors',
                'fuertewp_restapi_loggedin_only',
            );
        }
    }

    /**
     * Register UI-related hooks.
     */
    private function register_ui_hooks($fuertewp)
    {
        // Custom UI tweaks (CSS, JS, login logo)
        add_filter(
            'admin_footer',
            [__CLASS__, 'custom_javascript'],
            FUERTEWP_LATE_PRIORITY,
        );
        add_filter(
            'login_head',
            [__CLASS__, 'custom_javascript'],
            FUERTEWP_LATE_PRIORITY,
        );
        add_filter(
            'admin_head',
            [__CLASS__, 'custom_css'],
            FUERTEWP_LATE_PRIORITY,
        );
        add_filter(
            'login_head',
            [__CLASS__, 'custom_css'],
            FUERTEWP_LATE_PRIORITY,
        );
        add_action(
            'login_enqueue_scripts',
            [__CLASS__, 'custom_login_logo'],
            FUERTEWP_LATE_PRIORITY,
        );
        add_action(
            'login_headerurl',
            [__CLASS__, 'custom_login_url'],
            FUERTEWP_LATE_PRIORITY,
        );
        add_action(
            'login_headertext',
            [__CLASS__, 'custom_login_title'],
            FUERTEWP_LATE_PRIORITY,
        );
    }

    /**
     * Register restriction-related hooks.
     */
    private function register_restriction_hooks($fuertewp)
    {
        // Admin-specific restrictions
        if (is_admin()) {
            // Menu and admin bar restrictions
            add_filter(
                'admin_menu',
                [__CLASS__, 'remove_menus'],
                FUERTEWP_LATE_PRIORITY,
            );
            add_filter(
                'admin_bar_menu',
                [__CLASS__, 'remove_adminbar_menus'],
                FUERTEWP_LATE_PRIORITY,
            );

            // User role restrictions
            if (
                isset($fuertewp['restrictions']['disable_admin_create_edit'])
                && true === $fuertewp['restrictions']['disable_admin_create_edit']
            ) {
                add_filter(
                    'editable_roles',
                    [__CLASS__, 'create_edit_role_check'],
                    FUERTEWP_LATE_PRIORITY,
                );
            }

            // Application passwords
            if (
                isset($fuertewp['rest_api']['disable_app_passwords'])
                && true === $fuertewp['rest_api']['disable_app_passwords']
            ) {
                add_filter(
                    'wp_is_application_passwords_available',
                    '__return_false',
                    FUERTEWP_LATE_PRIORITY,
                );
            }

            // ACF restrictions
            if (
                isset($fuertewp['restrictions']['restrict_acf'])
                && true === $fuertewp['restrictions']['restrict_acf']
            ) {
                add_filter(
                    'acf/settings/show_admin',
                    '__return_false',
                    FUERTEWP_LATE_PRIORITY,
                );
            }
        }

        // Front-end admin bar restrictions
        if (
            !is_admin()
            && isset($fuertewp['restrictions']['disable_admin_bar_roles'])
            && !empty($fuertewp['restrictions']['disable_admin_bar_roles'])
        ) {
            if (
                is_array($fuertewp['restrictions']['disable_admin_bar_roles'])
            ) {
                foreach (
                    $fuertewp['restrictions']['disable_admin_bar_roles'] as $role
                ) {
                    if (true === $this->has_role($role)) {
                        add_filter(
                            'show_admin_bar',
                            '__return_false',
                            FUERTEWP_LATE_PRIORITY,
                        );
                        break; // Only need to add this once
                    }
                }
            }
        }
    }

    /**
     * Enforcer method.
     */
    protected function enforcer()
    {
        global $pagenow, $current_user;
        global $fuertewp;

        // Early exit optimization #1: Quick status check before any processing
        if (defined('FUERTEWP_DISABLE') && FUERTEWP_DISABLE) {
            return;
        }

        // Initialize hook manager for intelligent conditional registration
        if (!class_exists('Fuerte_Wp_Hook_Manager')) {
            require_once plugin_dir_path(__FILE__) . 'class-fuerte-wp-hook-manager.php';
        }
        Fuerte_Wp_Hook_Manager::init();

        $fuertewp = $this->config_setup();

        // Ensure current user is properly loaded
        $current_user = wp_get_current_user();

        // Early exit optimization #2: Plugin disabled
        if (!isset($fuertewp['status']) || $fuertewp['status'] != 'enabled') {
            return;
        }

        // Early exit optimization #3: CLI requests
        if (defined('WP_CLI') && WP_CLI) {
            return;
        }

        // Early exit optimization #4: Cron jobs (except our own)
        if (wp_doing_cron() && (!isset($_REQUEST['action']) || $_REQUEST['action'] !== 'fuertewp_trigger_updates')) {
            return;
        }

        // Early exit optimization #5: AJAX requests for non-admin users
        if (wp_doing_ajax() && !current_user_can('manage_options')) {
            return;
        }

        // Early exit optimization #6: REST API for logged-out users (if restricted)
        if (self::is_rest_request() &&
            isset($fuertewp['restrictions']['restapi_loggedin_only']) &&
            $fuertewp['restrictions']['restapi_loggedin_only'] &&
            !is_user_logged_in()) {
            return;
        }

        // Check if current user should be affected by Fuerte-WP using optimized string operations
        $is_super_user = in_array(
            strtolower($current_user->user_email),
            $fuertewp['super_users'] ?? [],
        );
        $is_forced = defined('FUERTEWP_FORCE') && true === FUERTEWP_FORCE;

        // Early exit optimization #7: Super users (unless forced)
        if ($is_super_user && !$is_forced) {
            return;
        }

        // Only proceed if user is affected by restrictions
        if ($is_forced || !$is_super_user) {
            // Apply core restrictions that don't need hook registration
            $this->apply_core_restrictions($fuertewp);

            // Auto-updates (managed via cronjob)
            $this->auto_update_manager->manage_updates($fuertewp);

            // Apply immediate restrictions (no hooks needed)
            $this->apply_immediate_restrictions($fuertewp);
        }
    }

    /**
     * Apply core restrictions that don't need hook registration.
     *
     * @since 1.7.0
     *
     * @param array $fuertewp Configuration array
     */
    private function apply_core_restrictions($fuertewp)
    {
        // htaccess security rules (immediate, no hooks needed)
        if (
            isset($fuertewp['restrictions']['htaccess_security_rules'])
            && true === $fuertewp['restrictions']['htaccess_security_rules']
        ) {
            $this->apply_htaccess_rules();
        }

        // Core WordPress constants (immediate)
        if (
            isset($fuertewp['restrictions']['disable_theme_editor']) &&
            true === $fuertewp['restrictions']['disable_theme_editor'] ||
            isset($fuertewp['restrictions']['disable_plugin_editor']) &&
            true === $fuertewp['restrictions']['disable_plugin_editor']
        ) {
            if (!defined('DISALLOW_FILE_EDIT')) {
                define('DISALLOW_FILE_EDIT', true);
            }
        }
    }

    /**
     * Apply immediate restrictions (no hooks needed).
     *
     * @since 1.7.0
     *
     * @param array $fuertewp Configuration array
     */
    private function apply_immediate_restrictions($fuertewp)
    {
        global $pagenow;

        // Admin-only restrictions
        if (is_admin()) {
            // Fuerte-WP self-protect
            $this->self_protect();

            // Direct page access restrictions
            $this->apply_page_restrictions($fuertewp, $pagenow);

            // User protection restrictions
            $this->apply_user_protection($fuertewp, $pagenow);
        }
    }

    /**
     * Apply page-based restrictions.
     *
     * @since 1.7.0
     *
     * @param array $fuertewp Configuration array
     * @param string $pagenow Current page
     */
    private function apply_page_restrictions($fuertewp, $pagenow)
    {
        // Theme Editor
        if (
            isset($fuertewp['restrictions']['disable_theme_editor']) &&
            true === $fuertewp['restrictions']['disable_theme_editor'] &&
            $pagenow == 'theme-editor.php'
        ) {
            $this->access_denied();
        }

        // Plugin Editor
        if (
            isset($fuertewp['restrictions']['disable_plugin_editor']) &&
            true === $fuertewp['restrictions']['disable_plugin_editor'] &&
            $pagenow == 'plugin-editor.php'
        ) {
            $this->access_denied();
        }

        // Theme Install
        if (
            isset($fuertewp['restrictions']['disable_theme_install']) &&
            true === $fuertewp['restrictions']['disable_theme_install'] &&
            $pagenow == 'theme-install.php'
        ) {
            $this->access_denied();
        }

        // Plugin Install
        if (
            isset($fuertewp['restrictions']['disable_plugin_install']) &&
            true === $fuertewp['restrictions']['disable_plugin_install'] &&
            $pagenow == 'plugin-install.php'
        ) {
            $this->access_denied();
        }

        // Permalinks
        if (
            isset($fuertewp['restrictions']['restrict_permalinks']) &&
            true === $fuertewp['restrictions']['restrict_permalinks'] &&
            $pagenow == 'options-permalink.php'
        ) {
            $this->access_denied();
        }

        // Restricted scripts
        if (
            isset($fuertewp['restricted_scripts']) &&
            in_array($pagenow, $fuertewp['restricted_scripts']) &&
            !wp_doing_ajax()
        ) {
            $this->access_denied();
        }

        // Restricted pages
        if (
            isset($fuertewp['restricted_pages']) &&
            isset($_REQUEST['page']) &&
            in_array($_REQUEST['page'], $fuertewp['restricted_pages']) &&
            !wp_doing_ajax()
        ) {
            $this->access_denied();
        }
    }

    /**
     * Use helper to restrict REST API access.
     *
     * @since 1.7.0
     *
     * @param mixed $result REST audit result
     *
     * @return mixed Original result or WP_Error
     */
    public static function restrict_rest_api($result)
    {
        return fuertewp_restapi_loggedin_only($result);
    }

    /**
     * Restrict plugin installation.
     *
     * @since 1.7.0
     */
    public static function restrict_plugin_installation()
    {
        wp_die(esc_html__('Plugin installation is restricted on this site.', 'fuerte-wp'));
    }

    /**
     * Restrict theme installation.
     *
     * @since 1.7.0
     */
    public static function restrict_theme_installation()
    {
        wp_die(esc_html__('Theme installation is restricted on this site.', 'fuerte-wp'));
    }

    /**
     * Filter email notifications.
     *
     * @since 1.7.0
     *
     * @param mixed $value Original value
     *
     * @return bool False to disable notification
     */
    public static function filter_email_notifications($value)
    {
        return false;
    }

    /**
     * User protection restrictions.
     *
     * @since 1.7.0
     *
     * @param array $fuertewp Configuration array
     * @param string $pagenow Current page
     */
    private function apply_user_protection($fuertewp, $pagenow)
    {
        $super_users = $fuertewp['super_users'] ?? [];

        // User switching
        if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'switch_to_user') {
            $this->access_denied();
        }

        // ACF restrictions
        if (
            isset($fuertewp['restrictions']['restrict_acf']) &&
            true === $fuertewp['restrictions']['restrict_acf']
        ) {
            if (
                (in_array($pagenow, ['post.php']) && isset($_GET['post']) &&
                    'acf-field-group' === get_post_type($_GET['post'])) ||
                (in_array($pagenow, ['edit.php', 'post-new.php']) && isset($_GET['post_type']) &&
                    'acf-field-group' === $_GET['post_type'])
            ) {
                $this->access_denied();
            }
        }

        // Customizer CSS
        if (
            isset($fuertewp['restrictions']['disable_customizer_css']) &&
            true === $fuertewp['restrictions']['disable_customizer_css'] &&
            $pagenow == 'customize.php'
        ) {
            add_action('customize_register', 'fuertewp_customizer_remove_css_editor');
        }

        // No protected users editing
        if ($pagenow == 'user-edit.php' && isset($_REQUEST['user_id']) && !empty($_REQUEST['user_id'])) {
            $user_info = get_userdata($_REQUEST['user_id']);

            if ($user_info && in_array(strtolower($user_info->user_email), $super_users)) {
                $this->access_denied();
            }
        }

        // No protected users deletion
        if ($pagenow == 'users.php' && isset($_REQUEST['action']) && $_REQUEST['action'] == 'delete') {
            $users_to_check = [];

            if (isset($_REQUEST['users']) && is_array($_REQUEST['users'])) {
                $users_to_check = $_REQUEST['users'];
            } elseif (isset($_REQUEST['user'])) {
                $users_to_check = [$_REQUEST['user']];
            }

            foreach ($users_to_check as $user_id) {
                $user_info = get_userdata($user_id);

                if ($user_info && in_array(strtolower($user_info->user_email), $super_users)) {
                    $this->access_denied();
                }
            }
        }
    }

    /**
     * Apply htaccess security rules.
     *
     * @since 1.7.0
     */
    private function apply_htaccess_rules()
    {
        // Ensure we are running Apache
        if (
            isset($_SERVER['SERVER_SOFTWARE']) &&
            stripos($_SERVER['SERVER_SOFTWARE'], 'Apache') !== false
        ) {
            $htaccessFile = ABSPATH . '.htaccess';

            // Check if we can write to .htaccess
            if (file_exists($htaccessFile) && is_writable($htaccessFile)) {
                $currentContent = file_get_contents($htaccessFile);

                // If .htaccess doesn't contain our rules, add them
                if (false === stripos($currentContent, '# BEGIN Fuerte-WP')) {
                    global $fuertewp_htaccess;

                    // Write .htaccess file, add our rules at the very end
                    file_put_contents(
                        $htaccessFile,
                        $currentContent . PHP_EOL . $fuertewp_htaccess,
                    );
                }
            }
        }
    }

    /**
     * Check if current request is REST API request.
     *
     * @since 1.7.0
     *
     * @return bool
     */
    private static function is_rest_request()
    {
        return defined('REST_REQUEST') && REST_REQUEST ||
               (isset($_SERVER['REQUEST_URI']) && str_contains($_SERVER['REQUEST_URI'], rest_get_url_prefix()));
    }

    /**
     * Fuerte-WP self-protection.
     */
    private function self_protect()
    {
        global $pagenow;

        // Prevent direct deactivation for non-super users only
        if (
            isset($_REQUEST['action'])
            && $_REQUEST['action'] == 'deactivate'
            && $pagenow == 'plugins.php'
            && isset($_REQUEST['plugin'])
            && stripos($_REQUEST['plugin'], 'fuerte-wp') !== false
        ) {
            // Check if current user is a super user
            global $fuertewp, $current_user;
            $is_super_user = false;

            if (isset($current_user) && isset($fuertewp['super_users'])) {
                $is_super_user = in_array(
                    strtolower($current_user->user_email),
                    $fuertewp['super_users'],
                );
            }

            // Only block deactivation if not a super user
            if (!$is_super_user) {
                $this->access_denied();
            }
        }

        // Check if a non super-user is accessing our plugin options
        if (
            $pagenow == 'options-general.php'
            && isset($_REQUEST['page'])
            && $_REQUEST['page'] == 'fuerte-wp-options'
        ) {
            // Check if current user is a super user
            global $fuertewp, $current_user;
            $is_super_user = false;

            if (isset($current_user) && isset($fuertewp['super_users'])) {
                $is_super_user = in_array(
                    strtolower($current_user->user_email),
                    $fuertewp['super_users'],
                );
            }

            // Only block access if not a super user
            if (!$is_super_user) {
                $this->access_denied();
            }
        }

        // Hide deactivation link for non-super users only
        add_filter(
            'plugin_action_links',
            function ($actions, $plugin_file) {
                // Only hide deactivate for non-super users
                global $fuertewp, $current_user;

                if (plugin_basename(FUERTEWP_PLUGIN_BASE) === $plugin_file) {
                    // Check if current user is a super user
                    $is_super_user = false;

                    if (isset($current_user) && isset($fuertewp['super_users'])) {
                        $is_super_user = in_array(
                            strtolower($current_user->user_email),
                            $fuertewp['super_users'],
                        );
                    }

                    // Only hide deactivate if not a super user
                    if (!$is_super_user) {
                        unset($actions['deactivate']);
                    }
                }

                return $actions;
            },
            FUERTEWP_LATE_PRIORITY,
            2,
        );
    }

    /**
     * Prints and ends WP execution with "Access denied" message.
     */
    protected function access_denied()
    {
        global $fuertewp;

        if (
            !isset($fuertewp['general']['access_denied_message'])
            || empty($fuertewp['general']['access_denied_message'])
        ) {
            $fuertewp['general']['access_denied_message'] = 'Access denied.';
        }

        wp_die($fuertewp['general']['access_denied_message']);

        return false;
    }

    /**
     * Set WP sender email address.
     *
     * @return string Email address
     */
    public static function sender_email_address(): string
    {
        global $fuertewp;

        $sender_email_address
            = $fuertewp['general']['sender_email']
            ?? 'no-reply@' . parse_url(home_url())['host'];

        // Remove www from hostname
        return str_replace('www.', '', $sender_email_address);
    }

    /**
     * Set WP sender email name.
     *
     * @return string Email name
     */
    public static function sender_email_name(): string
    {
        $site_name = get_bloginfo('name');

        return $site_name ?: 'WordPress';
    }

    /**
     * Change WP recovery email adresss.
     *
     * @return string Email address
     */
    public static function recovery_email_address(): array
    {
        global $fuertewp, $pagenow, $current_user;

        $recovery_email
            = $fuertewp['general']['recovery_email']
            ?? 'dev@' . parse_url(home_url())['host'];
        $email_data['to'] = $recovery_email;

        return $email_data;
    }

    /**
     * Remove wp-admin menus.
     */
    public static function remove_menus()
    {
        global $fuertewp;

        if (
            isset($fuertewp['restricted_scripts'])
            && !empty($fuertewp['restricted_scripts'])
        ) {
            foreach ($fuertewp['restricted_scripts'] as $item) {
                if (substr($item, 0, 2) === '//') {
                    // Commented item, skip it
                    continue;
                }

                remove_menu_page($item);
            }
        }

        if (
            isset($fuertewp['removed_menus'])
            && !empty($fuertewp['removed_menus'])
        ) {
            foreach ($fuertewp['removed_menus'] as $slug) {
                remove_menu_page($slug);
            }
        }

        if (
            isset($fuertewp['removed_submenus'])
            && !empty($fuertewp['removed_submenus'])
        ) {
            $submenu_parts = [];

            foreach ($fuertewp['removed_submenus'] as $item) {
                $submenu_parts = explode('|', $item);
                $submenu_parts = array_map('trim', $submenu_parts);

                remove_submenu_page($submenu_parts[0], $submenu_parts[1]);
            }
        }
    }

    /**
     * Remove adminbar menus (nodes).
     */
    public static function remove_adminbar_menus($wp_admin_bar)
    {
        global $fuertewp;

        if (
            isset($fuertewp['removed_adminbar_menus'])
            && !empty($fuertewp['removed_adminbar_menus'])
        ) {
            foreach ($fuertewp['removed_adminbar_menus'] as $item) {
                $wp_admin_bar->remove_node($item);
            }

            define('UPDRAFTPLUS_ADMINBAR_DISABLE', true);
        }
    }

    /**
     * Check if a role can be created/edited.
     *
     * @return array Roles array, without administrator role
     */
    public static function create_edit_role_check($roles): array
    {
        unset($roles['administrator']);

        return $roles;
    }

    /**
     * Check current user role
     * https://wordpress.org/support/article/roles-and-capabilities/.
     *
     * @return bool True if it has the role
     */
    public static function has_role($role = 'subscriber'): bool
    {
        $user = wp_get_current_user();

        return in_array($role, (array) $user->roles);
    }

    /**
     * Custom Javascript at footer.
     */
    public static function custom_javascript()
    {
        global $fuertewp; ?>
		<script type="text/javascript">
			document.addEventListener("DOMContentLoaded", function() {
				<?php // Disable typing a custom password (new user, profile edit, lost password).

        // Needed outside wp-admin, because reset password screen
    if (
        isset($fuertewp['restrictions']['force_strong_passwords'])
        && true === $fuertewp['restrictions']['force_strong_passwords']
    ): ?>
					if (document.body.classList.contains('user-new-php') ||
						document.body.classList.contains('user-edit-php') ||
						document.body.classList.contains('login') ||
						document.body.classList.contains('profile-php')) {
						document.getElementById('pass1').setAttribute('readonly', 'readonly');
					}
				<?php endif; ?>
			});
		</script>
	<?php
    }

    /**
     * Custom CSS at header.
     */
    public static function custom_css()
    {
        global $fuertewp; ?>
		<style type="text/css">
			<?php
   // Hides "Confirm use of weak password" checkbox on weak password, forcing a medium one at the very minimum.
   // Needed outside wp-admin, because reset password screen
   if (
       isset($fuertewp['restrictions']['disable_weak_passwords'])
       && true === $fuertewp['restrictions']['disable_weak_passwords']
   ): ?>.pw-weak {
				display: none !important;
			}

			<?php endif;

        // Hides ACF cog that allow users access ACF editable meta boxes UI
        if (
            isset($fuertewp['restrictions']['restrict_acf'])
            && true === $fuertewp['restrictions']['restrict_acf']
        ): ?>.wp-admin h3.hndle.ui-sortable-handle a.acf-hndle-cog {
				display: none !important;
				visibility: hidden !important;
			}

			<?php endif; ?>
		</style>
		<?php
    }

    /**
     * WP Login custom logo.
     */
    public static function custom_login_logo()
    {
        global $fuertewp;

        if (
            isset($fuertewp['tweaks']['use_site_logo_login'])
            && true === $fuertewp['tweaks']['use_site_logo_login']
        ) {
            if (!has_custom_logo()) {
                return;
            } ?>
			<style type="text/css">
				#login h1 a,
				.login h1 a {
					background-image: url(<?php echo esc_url(
					    wp_get_attachment_url(get_theme_mod('custom_logo')),
					); ?>);
					background-repeat: no-repeat;
					padding-bottom: 20px;
					filter: drop-shadow(0px 0px 4px #3c434a);
				}
			</style>
<?php
        }
    }

    /**
     * WP Login custom logo URL.
     *
     * @return string Blog URL
     */
    public static function custom_login_url()
    {
        global $fuertewp;

        if (
            isset($fuertewp['tweaks']['use_site_logo_login'])
            && true === $fuertewp['tweaks']['use_site_logo_login']
        ) {
            return home_url();
        }
    }

    /**
     * WP Login custom logo title.
     *
     * @return string Blog name
     */
    public static function custom_login_title()
    {
        global $fuertewp;

        if (
            isset($fuertewp['tweaks']['use_site_logo_login'])
            && true === $fuertewp['tweaks']['use_site_logo_login']
        ) {
            return get_bloginfo('name');
        }
    }

    // Work in Progress...
    public static function recommended_plugins()
    {
        global $fuertewp, $pagenow;

        $show_notice = false;
        $plugin_recommendations = [];

        if (
            !isset($fuertewp['recommended_plugins'])
            || empty($fuertewp['recommended_plugins'])
        ) {
            return;
        }

        if (current_user_can('activate_plugins') && !wp_doing_ajax()) {
            if (is_array($fuertewp['recommended_plugins'])) {
                foreach ($fuertewp['recommended_plugins'] as $plugin) {
                    if (
                        !is_plugin_active($plugin)
                        && !is_plugin_active_for_network($plugin)
                    ) {
                        $show_notice = true;
                        $plugin_recommendations[] = $plugin;
                    }
                }
            }
        }

        if (
            true === $show_notice
            && ($pagenow == 'plugins.php'
                || (isset($_REQUEST['page'])
                    && $_REQUEST['page'] == 'wc-settings')
                || $pagenow == 'options-general.php')
        ) {
            //add_action( 'admin_notices', 'fuertewp_recommended_plugins_notice' );
        }
    }

    /**
     * AJAX handler to get remaining login attempts.
     *
     * @since 1.7.0
     */
    public function ajax_get_remaining_attempts()
    {
        check_ajax_referer('fuertewp-get-attempts', 'security');

        if (!session_id()) {
            session_start();
        }

        $remaining = isset($_SESSION['fuertewp_login_attempts_left']) ? (int) $_SESSION['fuertewp_login_attempts_left'] : 0;

        if ($remaining > 0) {
            $message = sprintf(
                _n('<strong>%d</strong> attempt remaining.', '<strong>%d</strong> attempts remaining.', $remaining, 'fuerte-wp'),
                $remaining
            );
            wp_send_json_success($message);
        } else {
            wp_send_json_error();
        }
    }

    /**
     * AJAX handler to unlock an IP address.
     *
     * @since 1.7.0
     */
    public function ajax_unlock_ip()
    {
        check_ajax_referer('fuertewp-unlock-ip', 'security');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('You do not have sufficient permissions to perform this action.', 'fuerte-wp'));
        }

        $ip = isset($_POST['ip']) ? sanitize_text_field($_POST['ip']) : '';

        if (empty($ip)) {
            wp_send_json_error(__('IP address is required.', 'fuertewp'));
        }

        // Remove lockout from database
        global $wpdb;
        $table_name = $wpdb->prefix . 'fuertewp_login_lockouts';
        $wpdb->delete(
            $table_name,
            ['ip_address' => $ip],
            ['%s']
        );

        // Log the unlock action
        // Admin unlock logging removed for production

        wp_send_json_success(__('IP address unlocked successfully.', 'fuerte-wp'));
    }

    /**
     * AJAX handler for unblocking individual login attempts.
     *
     * @since 1.7.0
     */
    public function ajax_unblock_single()
    {
        check_ajax_referer('fuertewp_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'fuerte-wp'));
        }

        $ip = sanitize_text_field($_POST['ip'] ?? '');
        $username = sanitize_text_field($_POST['username'] ?? '');
        $attempt_id = (int) ($_POST['id'] ?? 0);

        if (empty($ip) || empty($attempt_id)) {
            wp_send_json_error(__('Invalid parameters', 'fuerte-wp'));
        }

        // Remove lockout for this specific IP/username combination
        $logger = new Fuerte_Wp_Login_Logger();
        $lockouts = $logger->get_active_lockouts($ip, $username);

        if ($lockouts) {
            // Find and remove the relevant lockout(s)
            global $wpdb;
            $table_name = $wpdb->prefix . 'fuertewp_login_lockouts';

            $wpdb->delete(
                $table_name,
                [
                    'ip_address' => $ip,
                    'username' => $username,
                ],
                ['%s', '%s']
            );

            // Log the unlock action
            // Admin unblock logging removed for production

            wp_send_json_success([
                'message' => sprintf(
                    __('Unblocked IP %s for user %s', 'fuerte-wp'),
                    esc_html($ip),
                    esc_html($username)
                ),
            ]);
        } else {
            wp_send_json_error(__('No active lockout found for this entry', 'fuerte-wp'));
        }
    }
} // Class Fuerte_Wp_Enforcer
