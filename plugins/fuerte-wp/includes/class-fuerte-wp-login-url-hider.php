<?php

/**
 * Login URL Hider for Fuerte-WP Security Plugin.
 *
 * Handles hiding the default wp-login.php URL and replacing it with a custom slug.
 * Uses WordPress URL filtering and direct access blocking for enhanced security.
 *
 * @link       https://actitud.xyz
 * @since      1.7.0
 *
 * @author     Esteban Cuevas <esteban@attitude.cl>
 */

// No access outside WP
defined('ABSPATH') || die();

/**
 * Login URL Hider class for hiding wp-login.php access.
 *
 * @since 1.7.0
 */
class Fuerte_Wp_Login_URL_Hider
{
    /**
     * Singleton instance.
     *
     * @since 1.7.0
     *
     * @var Fuerte_Wp_Login_URL_Hider
     */
    private static $instance = null;

    /**
     * WordPress database instance.
     *
     * @since 1.7.0
     *
     * @var wpdb
     */
    private $wpdb;

    /**
     * Configuration cache.
     *
     * @since 1.7.0
     *
     * @var array
     */
    private $config_cache = [];

    /**
     * WordPress request path.
     *
     * @since 1.7.0
     *
     * @var string
     */
    private $request_path = '';

    /**
     * Whether this is a valid login request.
     *
     * @since 1.7.0
     *
     * @var bool
     */
    private $is_valid_login_request = false;

    /**
     * Get singleton instance.
     *
     * @since 1.7.0
     *
     * @return Fuerte_Wp_Login_URL_Hider
     */
    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Initialize Login URL Hider.
     *
     * @since 1.7.0
     */
    private function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;

        // Initialize request path for efficient checking
        $this->request_path = parse_url(rawurldecode($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?? '';

        Fuerte_Wp_Logger::debug('Login URL Hider constructor called');

        $this->init_hooks();
    }

    /**
     * Initialize WordPress hooks.
     *
     * @since 1.7.0
     */
    private function init_hooks()
    {
        // Only proceed if feature is enabled
        $is_enabled = $this->is_login_url_hiding_enabled();

        Fuerte_Wp_Logger::debug('Login URL Hider init_hooks - feature enabled: ' . ($is_enabled ? 'YES' : 'NO'));

        if (!$is_enabled) {
            return;
        }

        // URL filtering hooks
        add_filter('site_url', [$this, 'filter_site_url'], 10, 4);
        add_filter('network_site_url', [$this, 'filter_network_site_url'], 10, 3);
        add_filter('wp_redirect', [$this, 'filter_wp_redirect'], 10, 2);
        add_filter('login_url', [$this, 'filter_login_url'], 10, 3);
        add_filter('logout_url', [$this, 'filter_logout_url'], 10, 2);
        add_filter('lostpassword_url', [$this, 'filter_lostpassword_url'], 10, 2);
        add_filter('register_url', [$this, 'filter_register_url'], 10, 1);

        // Early WP-Admin access check - before WordPress core redirects
        add_action('wp_loaded', [$this, 'early_wp_admin_check'], 1);

        // Login form modifications
        add_action('login_form', [$this, 'add_hidden_field_to_login_form']);

        // Hidden field validation
        add_action('authenticate', [$this, 'validate_hidden_login_field'], 999, 3);

        // Direct wp-login.php access blocking
        add_action('login_init', [$this, 'handle_login_init'], 1);

        // Custom login URL request handling
        add_action('parse_request', [$this, 'handle_parse_request'], 1);

        // Debug: Log hook registration
        Fuerte_Wp_Logger::debug('Login URL Hider hooks registered - parse_request priority 1');

        // Admin access protection
        add_action('admin_init', [$this, 'protect_wp_admin_access']);
    }

    /**
     * Check if login URL hiding is enabled.
     *
     * @since 1.7.0
     *
     * @return bool True if enabled, false otherwise
     */
    public function is_login_url_hiding_enabled()
    {
        if (isset($this->config_cache['enabled'])) {
            return $this->config_cache['enabled'];
        }

        // Ensure simple config class is loaded
        if (!class_exists('Fuerte_Wp_Config')) {
            require_once plugin_dir_path(__FILE__) . 'class-fuerte-wp-config.php';
        }

        // Use simple configuration with default value of false
        $enabled = Fuerte_Wp_Config::get('login_security.login_url_hiding_enabled', false);

        Fuerte_Wp_Logger::debug('Login URL Hider - raw config value: ' . var_export($enabled, true));

        // Handle different value formats
        if (is_array($enabled) && isset($enabled[0])) {
            $enabled = $enabled[0];
        }

        $this->config_cache['enabled'] = ($enabled === 'enabled' || $enabled === true || $enabled === 1 || $enabled === '1');

        Fuerte_Wp_Logger::debug('Login URL Hider - final enabled state: ' . ($this->config_cache['enabled'] ? 'YES' : 'NO'));

        return $this->config_cache['enabled'];
    }

    /**
     * Get invalid login redirect configuration.
     *
     * @since 1.7.0
     *
     * @return array Redirect configuration with 'type' and 'url' keys
     */
    public function get_invalid_login_redirect_config()
    {
        if (isset($this->config_cache['redirect_config'])) {
            return $this->config_cache['redirect_config'];
        }

        // Use centralized configuration cache
        $redirect_type = Fuerte_Wp_Config::get('login_security.redirect_invalid_logins', 'home_404');
        $redirect_url = Fuerte_Wp_Config::get('login_security.redirect_invalid_logins_url', '');

        // Handle array format for redirect type
        if (is_array($redirect_type) && isset($redirect_type[0])) {
            $redirect_type = $redirect_type[0];
        }

        // Validate redirect type
        $redirect_type = in_array($redirect_type, ['home_404', 'custom_page']) ? $redirect_type : 'home_404';

        // Handle array format for redirect URL
        if (is_array($redirect_url) && isset($redirect_url[0])) {
            $redirect_url = $redirect_url[0];
        }

        // Sanitize redirect URL
        $redirect_url = esc_url_raw($redirect_url);

        $config = [
            'type' => $redirect_type,
            'url' => $redirect_url,
        ];

        $this->config_cache['redirect_config'] = $config;

        return $config;
    }

    /**
     * Get custom login slug.
     *
     * @since 1.7.0
     *
     * @return string Custom login slug
     */
    public function get_custom_login_slug()
    {
        if (isset($this->config_cache['slug'])) {
            return $this->config_cache['slug'];
        }

        // Use centralized configuration cache
        $slug = Fuerte_Wp_Config::get('login_security.custom_login_slug', 'secure-login');

        // Handle array format
        if (is_array($slug) && isset($slug[0])) {
            $slug = $slug[0];
        }

        // Sanitize the slug
        $slug = sanitize_title_with_dashes($slug);

        $this->config_cache['slug'] = $slug;

        return $slug;
    }

    /**
     * Get login URL type.
     *
     * @since 1.7.0
     *
     * @return string 'query_param' or 'pretty_url'
     */
    public function get_login_url_type()
    {
        if (isset($this->config_cache['url_type'])) {
            return $this->config_cache['url_type'];
        }

        // Use centralized configuration cache
        $url_type = Fuerte_Wp_Config::get('login_security.login_url_type', 'query_param');

        // Handle array format
        if (is_array($url_type) && isset($url_type[0])) {
            $url_type = $url_type[0];
        }

        // Validate URL type
        $url_type = in_array($url_type, ['query_param', 'pretty_url']) ? $url_type : 'query_param';

        $this->config_cache['url_type'] = $url_type;

        return $url_type;
    }

    /**
     * Check if WP-Admin protection is enabled.
     *
     * @since 1.7.0
     *
     * @return bool True if protection is enabled
     */
    public function is_wp_admin_protection_enabled()
    {
        if (isset($this->config_cache['protect_admin'])) {
            return $this->config_cache['protect_admin'];
        }

        // WP-Admin protection is automatically enabled when Login URL Hiding is enabled
        $protect = $this->is_login_url_hiding_enabled();

        $this->config_cache['protect_admin'] = $protect;

        return $protect;
    }

    /**
     * Check if current user should bypass restrictions.
     *
     * @since 1.7.0
     *
     * @return bool True if user should bypass, false otherwise
     */
    public function should_bypass_restrictions()
    {
        // Allow super users to bypass
        if (class_exists('Fuerte_Wp_Enforcer')) {
            $enforcer = Fuerte_Wp_Enforcer::get_instance();

            if (method_exists($enforcer, 'should_bypass_restrictions')) {
                return $enforcer->should_bypass_restrictions();
            }
        }

        return false;
    }

    /**
     * Generate custom login URL.
     *
     * @since 1.7.0
     *
     * @param string $scheme URL scheme
     *
     * @return string Custom login URL
     */
    public function generate_custom_login_url($scheme = null)
    {
        $slug = $this->get_custom_login_slug();
        $url_type = $this->get_login_url_type();

        if ($url_type === 'pretty_url') {
            // Pretty URL mode: /custom-slug/
            $url = home_url($slug . '/', $scheme);
        } else {
            // Query parameter mode: ?custom-slug
            $url = home_url('/', $scheme);
            $url = add_query_arg($slug, '', $url);
        }

        return $url;
    }

    /**
     * Filter site URLs to replace login URLs.
     *
     * @since 1.7.0
     *
     * @param string $url The complete site URL
     * @param string $path Path relative to the site URL
     * @param string|null $scheme Scheme to give the site URL context
     * @param int|null $blog_id Site ID
     *
     * @return string Filtered URL
     */
    public function filter_site_url($url, $path, $scheme, $blog_id)
    {
        if ($this->should_bypass_restrictions()) {
            return $url;
        }

        // Check if this URL should be filtered
        if ($this->is_login_related_url($url)) {
            return $this->replace_login_url($url, $scheme);
        }

        return $url;
    }

    /**
     * Filter network site URLs.
     *
     * @since 1.7.0
     *
     * @param string $url The complete network site URL
     * @param string $path Path relative to the network site URL
     * @param string|null $scheme Scheme to give the network site URL context
     *
     * @return string Filtered URL
     */
    public function filter_network_site_url($url, $path, $scheme)
    {
        if ($this->should_bypass_restrictions()) {
            return $url;
        }

        if ($this->is_login_related_url($url)) {
            return $this->replace_login_url($url, $scheme);
        }

        return $url;
    }

    /**
     * Filter wp redirects.
     *
     * @since 1.7.0
     *
     * @param string $location The redirect URL
     * @param int $status HTTP status code
     *
     * @return string Filtered location
     */
    public function filter_wp_redirect($location, $status)
    {
        if ($this->should_bypass_restrictions()) {
            return $location;
        }

        if ($this->is_login_related_url($location)) {
            return $this->replace_login_url($location);
        }

        return $location;
    }

    /**
     * Filter login URL.
     *
     * @since 1.7.0
     *
     * @param string $login_url Login URL
     * @param string $redirect URL to redirect to after login
     * @param bool $force_reauth Whether to force reauthentication
     *
     * @return string Filtered login URL
     */
    public function filter_login_url($login_url, $redirect, $force_reauth)
    {
        if ($this->should_bypass_restrictions()) {
            return $login_url;
        }

        return $this->replace_login_url($login_url);
    }

    /**
     * Filter logout URL.
     *
     * @since 1.7.0
     *
     * @param string $logout_url Logout URL
     * @param string $redirect URL to redirect to after logout
     *
     * @return string Filtered logout URL
     */
    public function filter_logout_url($logout_url, $redirect)
    {
        if ($this->should_bypass_restrictions()) {
            return $logout_url;
        }

        return $this->replace_login_url($logout_url);
    }

    /**
     * Filter lost password URL.
     *
     * @since 1.7.0
     *
     * @param string $lostpassword_url Lost password URL
     * @param string $redirect URL to redirect to after password reset
     *
     * @return string Filtered lost password URL
     */
    public function filter_lostpassword_url($lostpassword_url, $redirect)
    {
        if ($this->should_bypass_restrictions()) {
            return $lostpassword_url;
        }

        return $this->replace_login_url($lostpassword_url);
    }

    /**
     * Filter registration URL.
     *
     * @since 1.7.0
     *
     * @param string $register_url Registration URL
     *
     * @return string Filtered registration URL
     */
    public function filter_register_url($register_url)
    {
        if ($this->should_bypass_restrictions()) {
            return $register_url;
        }

        return $this->replace_login_url($register_url);
    }

    /**
     * Add hidden field to login form.
     *
     * @since 1.7.0
     */
    public function add_hidden_field_to_login_form()
    {
        $slug = $this->get_custom_login_slug();
        echo '<input type="hidden" name="fuertewp_login_slug" value="' . esc_attr($slug) . '" />';
    }

    /**
     * Validate hidden field during authentication.
     *
     * @since 1.7.0
     *
     * @param WP_User|WP_Error|null $user User object or error
     * @param string $username Username
     * @param string $password Password
     *
     * @return WP_User|WP_Error|null Filtered user result
     */
    public function validate_hidden_login_field($user, $username, $password)
    {
        // Skip validation if restrictions should be bypassed
        if ($this->should_bypass_restrictions()) {
            return $user;
        }

        // Check if this is a POST request to wp-login.php
        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' &&
            isset($_POST['log']) && isset($_POST['pwd'])) {

            $expected_slug = $this->get_custom_login_slug();
            $submitted_slug = $_POST['fuertewp_login_slug'] ?? '';

            // Log failed validation attempt
            if ($submitted_slug !== $expected_slug) {
                if (class_exists('Fuerte_Wp_Login_Logger')) {
                    $logger = new Fuerte_Wp_Login_Logger();
                    $ip = $this->get_client_ip();
                    $logger->log_attempt('blocked', $ip, 'blocked', 'Invalid login form submission - missing or incorrect hidden field', $_SERVER['HTTP_USER_AGENT'] ?? '');
                }

                return new WP_Error(
                    'invalid_login_form',
                    '<strong>Error:</strong> Invalid login form submission. Please use the proper login page.'
                );
            }
        }

        return $user;
    }

    /**
     * Handle login initialization.
     *
     * @since 1.7.0
     */
    public function handle_login_init()
    {
        global $pagenow;

        // Check if this is a direct wp-login.php access
        if ($pagenow === 'wp-login.php' && !$this->is_valid_login_request()) {
            if ($this->should_bypass_restrictions()) {
                return;
            }

            $this->redirect_invalid_login();
            exit;
        }
    }

    /**
     * Protect WP-Admin access.
     *
     * @since 1.7.0
     */
    public function protect_wp_admin_access()
    {
        $protection_enabled = $this->is_wp_admin_protection_enabled();

        if (!$protection_enabled) {
            return;
        }

        if ($this->should_bypass_restrictions()) {
            return;
        }

        $is_logged_in = is_user_logged_in();
        $is_wp_cli = defined('WP_CLI');
        $is_doing_ajax = defined('DOING_AJAX');
        $is_doing_cron = defined('DOING_CRON');
        $has_action = isset($_GET['action']);
        $is_admin_request = $this->is_wp_admin_request();

        // Check if this is an admin-ajax.php request (these should be allowed for functionality)
        $is_admin_ajax = strpos($_SERVER['REQUEST_URI'] ?? '', 'admin-ajax.php') !== false;

        if (!$is_logged_in &&
            !$is_wp_cli &&
            !$is_doing_cron &&
            !$is_admin_ajax &&
            !$has_action &&
            $is_admin_request) {

            // Get redirect configuration and redirect
            $redirect_config = $this->get_invalid_login_redirect_config();

            if ($redirect_config['type'] === 'custom_page' && !empty($redirect_config['url'])) {
                // Redirect to custom URL
                wp_safe_redirect($redirect_config['url'], 302);
            } else {
                // Default: redirect to home page with 404-like behavior
                wp_safe_redirect(home_url('404'), 302);
            }
            exit;
        }
    }

    /**
     * Handle custom login URL requests via parse_request.
     *
     * @since 1.7.0
     *
     * @param WP $wp WordPress request object
     */
    public function handle_parse_request($wp)
    {
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';

        $request = parse_url(rawurldecode($request_uri));
        $is_custom_login = $this->is_custom_login_url_request($request);

        Fuerte_Wp_Logger::debug('Parse request - Custom login detected: ' . ($is_custom_login ? 'YES' : 'NO'));

        if ($is_custom_login) {
            $this->is_valid_login_request = true;

            // Set up WordPress to treat this as a login request
            global $pagenow;
            $pagenow = 'wp-login.php';

            // Tell WordPress this is a login request
            $wp->query_vars = array_merge($wp->query_vars, [
                'pagename' => 'wp-login',
                'action' => $_GET['action'] ?? 'login',
            ]);

            Fuerte_Wp_Logger::debug('Parse request - Including wp-login.php directly');

            // Include the login file directly
            $login_file = ABSPATH . 'wp-login.php';

            if (file_exists($login_file)) {
                include_once $login_file;
                exit;
            }
        }
    }

    /**
     * Display the login form directly.
     *
     * @since 1.7.0
     */
    private function display_login_form()
    {
        // Define constants that wp-login.php expects
        if (!defined('WP_USE_THEMES')) {
            define('WP_USE_THEMES', false);
        }

        // Set up the login form
        $action = $_GET['action'] ?? 'login';
        $redirect_to = $_GET['redirect_to'] ?? admin_url();

        // Include WordPress login functions
        if (!function_exists('login_header')) {
            require_once ABSPATH . 'wp-login.php';
        }

        // Show the login form
        login_form([
            'redirect' => $redirect_to,
            'form_id' => 'loginform',
            'label_username' => __('Username or Email Address'),
            'label_password' => __('Password'),
            'label_remember' => __('Remember Me'),
            'label_log_in' => __('Log In'),
            'id_username' => 'user_login',
            'id_password' => 'user_pass',
            'id_remember' => 'rememberme',
            'id_submit' => 'wp-submit',
            'remember' => true,
            'value_username' => '',
            'value_remember' => false,
        ]);

        exit;
    }

    /**
     * Check if URL is login related.
     *
     * @since 1.7.0
     *
     * @param string $url URL to check
     *
     * @return bool True if login related
     */
    private function is_login_related_url($url)
    {
        return strpos($url, 'wp-login.php') !== false;
    }

    /**
     * Replace login URL in given URL.
     *
     * @since 1.7.0
     *
     * @param string $url Original URL
     * @param string|null $scheme URL scheme
     *
     * @return string Modified URL
     */
    private function replace_login_url($url, $scheme = null)
    {
        if (strpos($url, 'wp-login.php') === false) {
            return $url;
        }

        // Parse the URL to extract query parameters
        $parsed_url = parse_url($url);

        if (!$parsed_url) {
            return $url;
        }

        // Extract existing query parameters
        $query_params = [];

        if (isset($parsed_url['query'])) {
            parse_str($parsed_url['query'], $query_params);
        }

        // Remove the wp-login.php path
        $path = isset($parsed_url['path']) ? str_replace('wp-login.php', '', $parsed_url['path']) : '';

        // Build the new URL with custom login slug
        $new_url = $this->generate_custom_login_url($scheme);

        // Add query parameters if any
        if (!empty($query_params)) {
            $new_url = add_query_arg($query_params, $new_url);
        }

        return $new_url;
    }

    /**
     * Check if this is a custom login URL request.
     *
     * @since 1.7.0
     *
     * @param array $request Parsed request array
     *
     * @return bool True if custom login URL
     */
    private function is_custom_login_url_request($request)
    {
        $slug = $this->get_custom_login_slug();
        $url_type = $this->get_login_url_type();

        Fuerte_Wp_Logger::debug('URL check - Slug: "' . $slug . '", Type: "' . $url_type . '"');
        Fuerte_Wp_Logger::debug('URL check - GET params: ' . json_encode($_GET));

        if ($url_type === 'pretty_url') {
            // Check for pretty URL: /custom-slug/
            $expected_path = '/' . $slug . '/';
            $request_path = $request['path'] ?? '';
            $normalized_request_path = untrailingslashit($request_path);
            $normalized_expected_path = untrailingslashit($expected_path);

            return $normalized_request_path === $normalized_expected_path;
        } else {
            // Check for query parameter: ?custom-slug (empty value indicates login request)
            $result = isset($_GET[$slug]);

            if (class_exists('Fuerte_Wp_Logger')) {
                Fuerte_Wp_Logger::debug('Query param check - isset($_GET["' . $slug . '"]): ' . ($result ? 'YES' : 'NO'));
            }

            return $result;
        }
    }

    /**
     * Check if this is a WP-Admin request.
     *
     * @since 1.7.0
     *
     * @return bool True if WP-Admin request
     */
    private function is_wp_admin_request()
    {
        return $this->request_path === '/wp-admin/' ||
               strpos($this->request_path, '/wp-admin/') === 0;
    }

    /**
     * Redirect invalid login attempts.
     *
     * @since 1.7.0
     */
    private function redirect_invalid_login()
    {
        // Log the blocked attempt if logger is available
        if (class_exists('Fuerte_Wp_Login_Logger')) {
            $logger = new Fuerte_Wp_Login_Logger();
            $ip = $this->get_client_ip();
            $logger->log_attempt('blocked', $ip, 'blocked', 'Direct wp-login.php access blocked', $_SERVER['HTTP_USER_AGENT'] ?? '');
        }

        // Get redirect configuration
        $redirect_config = $this->get_invalid_login_redirect_config();

        if ($redirect_config['type'] === 'custom_page' && !empty($redirect_config['url'])) {
            // Redirect to custom URL
            wp_safe_redirect($redirect_config['url'], 302);
        } else {
            // Default: redirect to home page with 404-like behavior
            wp_safe_redirect(home_url('404'), 302);
        }
        exit;
    }

    /**
     * Get client IP address.
     *
     * @since 1.7.0
     *
     * @return string Client IP address
     */
    private function get_client_ip()
    {
        $ip_keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];

        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);

                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Early WP-Admin access check before WordPress core redirects.
     *
     * @since 1.7.0
     */
    public function early_wp_admin_check()
    {
        // Check if this is a WP-Admin request
        if ($this->is_wp_admin_request()) {
            // Check if user is logged in
            $is_logged_in = is_user_logged_in();

            if (!$is_logged_in) {
                // Clear any redirect parameters that WordPress might set
                unset($_GET['redirect_to']);
                unset($_REQUEST['redirect_to']);

                // Get redirect configuration and redirect
                $redirect_config = $this->get_invalid_login_redirect_config();

                if ($redirect_config['type'] === 'custom_page' && !empty($redirect_config['url'])) {
                    // Redirect to custom URL
                    wp_safe_redirect($redirect_config['url'], 302);
                } else {
                    // Default: redirect to home page with 404-like behavior
                    wp_safe_redirect(home_url('404'), 302);
                }
                exit;
            }
        }
    }

    /**
     * Check if current request is valid.
     *
     * @since 1.7.0
     *
     * @return bool True if valid login request
     */
    public function is_valid_login_request()
    {
        return $this->is_valid_login_request;
    }

    /**
     * Clear configuration cache.
     *
     * @since 1.7.0
     */
    public function clear_config_cache()
    {
        $this->config_cache = [];
    }
}
