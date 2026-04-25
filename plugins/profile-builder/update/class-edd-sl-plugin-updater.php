<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Allows plugins to use their own update API.
 *
 * @author Easy Digital Downloads
 * @version 1.9.4
 */
if( !class_exists('WPPB_EDD_SL_Plugin_Updater') ) {
    class WPPB_EDD_SL_Plugin_Updater {

        private $api_url = '';
        private $api_data = array();
        private $plugin_file = '';
        private $name = '';
        private $slug = '';
        private $version = '';
        private $wp_override = false;
        private $beta = false;
        private $failed_request_cache_key;

        /**
         * Class constructor.
         *
         * @uses plugin_basename()
         * @uses hook()
         *
         * @param string $_api_url The URL pointing to the custom API endpoint.
         * @param string $_plugin_file Path to the plugin file.
         * @param array $_api_data Optional data to send with API calls.
         */
        public function __construct($_api_url, $_plugin_file, $_api_data = null)
        {

            global $edd_plugin_data;

            $this->api_url                  = trailingslashit($_api_url);
            $this->api_data                 = $_api_data;
            $this->plugin_file              = $_plugin_file;
            $this->name                     = plugin_basename($_plugin_file);
            $this->slug                     = basename(dirname($_plugin_file));

            /**
             * Necessary in order for the View Details button to work properly when multiple products using 
             * this class are active
             * 
             * The original takes the base file name as the slug, but our file names are just `index.php` so we
             * use the folder name instead
             */
            if ( $this->slug === 'index') {
                $this->slug = dirname( plugin_basename( $_plugin_file ) );
            }
            // end modification

            $this->version                  = $_api_data['version'];
            $this->wp_override              = isset($_api_data['wp_override']) ? (bool)$_api_data['wp_override'] : false;
            $this->beta                     = !empty($this->api_data['beta']) ? true : false;
            $this->failed_request_cache_key = 'edd_sl_failed_http_' . md5($this->api_url);

            $edd_plugin_data[$this->slug] = $this->api_data;

            /**
             * Fires after the $edd_plugin_data is setup.
             *
             * @since x.x.x
             *
             * @param array $edd_plugin_data Array of EDD SL plugin data.
             */
            do_action( 'post_edd_sl_plugin_updater_setup', $edd_plugin_data );

            // Set up hooks.
            $this->init();

        }

        /**
         * Set up WordPress filters to hook into WP's update process.
         *
         * @uses add_filter()
         *
         * @return void
         */
        public function init()
        {

            add_filter('pre_set_site_transient_update_plugins', array($this, 'check_update'));
            add_filter('plugins_api', array($this, 'plugins_api_filter'), 10, 3);
            add_action('after_plugin_row', array($this, 'show_update_notification'), 10, 2);
            add_action('admin_init', array($this, 'show_changelog'));

        }

        /**
         * Check for Updates at the defined API endpoint and modify the update array.
         *
         * This function dives into the update API just when WordPress creates its update array,
         * then adds a custom API call and injects the custom plugin data retrieved from the API.
         * It is reassembled from parts of the native WordPress plugin update code.
         * See wp-includes/update.php line 121 for the original wp_update_plugins() function.
         *
         * @uses api_request()
         *
         * @param array $_transient_data Update array build by WordPress.
         * @return array Modified update array with custom plugin data.
         */
        public function check_update($_transient_data)
        {

            global $pagenow;

            if (!is_object($_transient_data)) {
                $_transient_data = new stdClass;
            }

            if ('plugins.php' == $pagenow && is_multisite()) {
                return $_transient_data;
            }

            if (!empty($_transient_data->response) && !empty($_transient_data->response[$this->name]) && false === $this->wp_override) {
                return $_transient_data;
            }

            $current = $this->get_update_transient_data();
            if (false !== $current && is_object($current) && isset($current->new_version)) {
                if (version_compare($this->version, $current->new_version, '<')) {
                    $_transient_data->response[$this->name] = $current;
                } else {
                    // Populating the no_update information is required to support auto-updates in WordPress 5.5.
                    $_transient_data->no_update[$this->name] = $current;
                }
            }
            $_transient_data->last_checked           = current_time('timestamp');
            $_transient_data->checked[$this->name] = $this->version;

            return $_transient_data;
        }

        /**
         * Get repo API data from store.
         * Save to cache.
         *
         * @return \stdClass
         */
        public function get_repo_api_data() {
            $version_info = $this->get_cached_version_info();

            if ( false === $version_info ) {
                $version_info = $this->api_request(
                    'plugin_latest_version',
                    array(
                        'slug' => $this->slug,
                        'beta' => $this->beta,
                    )
                );
                if ( ! $version_info ) {
                    return false;
                }

                // This is required for your plugin to support auto-updates in WordPress 5.5.
                $version_info->plugin = $this->name;
                $version_info->id     = $this->name;
                $version_info->tested = $this->get_tested_version( $version_info );
                if ( ! isset( $version_info->requires ) ) {
                    $version_info->requires = '';
                }
                if ( ! isset( $version_info->requires_php ) ) {
                    $version_info->requires_php = '';
                }

                $this->set_version_info_cache( $version_info );
            }

            return $version_info;
        }

        /**
         * Gets a limited set of data from the API response.
         * This is used for the update_plugins transient.
         *
         * @since 3.8.12
         * @return \stdClass|false
         */
        private function get_update_transient_data() {
            $version_info = $this->get_repo_api_data();

            if ( ! $version_info ) {
                return false;
            }

            $limited_data               = new \stdClass();
            $limited_data->slug         = $this->slug;
            $limited_data->plugin       = $this->name;
            $limited_data->url          = $version_info->url;
            $limited_data->package      = $version_info->package;
            $limited_data->icons        = $this->convert_object_to_array( $version_info->icons );
            $limited_data->banners      = $this->convert_object_to_array( $version_info->banners );
            $limited_data->new_version  = $version_info->new_version;
            $limited_data->tested       = $version_info->tested;
            $limited_data->requires     = $version_info->requires;
            $limited_data->requires_php = $version_info->requires_php;

            return $limited_data;
        }

        /**
         * Gets the plugin's tested version.
         *
         * @since 1.9.2
         * @param object $version_info
         * @return null|string
         */
        private function get_tested_version( $version_info ) {

            // There is no tested version.
            if ( empty( $version_info->tested ) ) {
                return null;
            }

            // Strip off extra version data so the result is x.y or x.y.z.
            list( $current_wp_version ) = explode( '-', get_bloginfo( 'version' ) );

            // The tested version is greater than or equal to the current WP version, no need to do anything.
            if ( version_compare( $version_info->tested, $current_wp_version, '>=' ) ) {
                return $version_info->tested;
            }
            $current_version_parts = explode( '.', $current_wp_version );
            $tested_parts          = explode( '.', $version_info->tested );

            // The current WordPress version is x.y.z, so update the tested version to match it.
            if ( isset( $current_version_parts[2] ) && $current_version_parts[0] === $tested_parts[0] && $current_version_parts[1] === $tested_parts[1] ) {
                $tested_parts[2] = $current_version_parts[2];
            }

            return implode( '.', $tested_parts );
        }

        /**
         * Show the update notification on multisite subsites.
         *
         * @param string  $file
         * @param array   $plugin
         */
        public function show_update_notification( $file, $plugin ) {

            // Return early if in the network admin, or if this is not a multisite install.
            if ( is_network_admin() || ! is_multisite() ) {
                return;
            }

            // Allow single site admins to see that an update is available.
            if ( ! current_user_can( 'activate_plugins' ) ) {
                return;
            }

            if ( $this->name !== $file ) {
                return;
            }

            // Do not print any message if update does not exist.
            $update_cache = get_site_transient( 'update_plugins' );

            if ( ! isset( $update_cache->response[ $this->name ] ) ) {
                if ( ! is_object( $update_cache ) ) {
                    $update_cache = new stdClass();
                }
                $update_cache->response[ $this->name ] = $this->get_repo_api_data();
            }

            // Return early if this plugin isn't in the transient->response or if the site is running the current or newer version of the plugin.
            if ( empty( $update_cache->response[ $this->name ] ) || version_compare( $this->version, $update_cache->response[ $this->name ]->new_version, '>=' ) ) {
                return;
            }

            printf(
                '<tr class="plugin-update-tr %3$s" id="%1$s-update" data-slug="%1$s" data-plugin="%2$s">',
                esc_html( $this->slug ),
                esc_html( $file ),
                in_array( $this->name, $this->get_active_plugins(), true ) ? 'active' : 'inactive'
            );

            echo '<td colspan="3" class="plugin-update colspanchange">';
            echo '<div class="update-message notice inline notice-warning notice-alt"><p>';

            $changelog_link = '';
            if ( ! empty( $update_cache->response[ $this->name ]->sections->changelog ) ) {
                $changelog_link = add_query_arg(
                    array(
                        'edd_sl_action' => 'view_plugin_changelog',
                        'plugin'        => urlencode( $this->name ),
                        'slug'          => urlencode( $this->slug ),
                        'TB_iframe'     => 'true',
                        'width'         => 77,
                        'height'        => 911,
                    ),
                    self_admin_url( 'index.php' )
                );
            }
            $update_link = add_query_arg(
                array(
                    'action' => 'upgrade-plugin',
                    'plugin' => urlencode( $this->name ),
                ),
                self_admin_url( 'update.php' )
            );

            printf(
                /* translators: the plugin name. */
                esc_html__( 'There is a new version of %1$s available.', 'profile-builder' ),
                esc_html( $plugin['Name'] )
            );

            if ( ! current_user_can( 'update_plugins' ) ) {
                echo ' ';
                esc_html_e( 'Contact your network administrator to install the update.', 'profile-builder' );
            } elseif ( empty( $update_cache->response[ $this->name ]->package ) && ! empty( $changelog_link ) ) {
                echo ' ';
                printf(
                    /* translators: 1. opening anchor tag, do not translate 2. the new plugin version 3. closing anchor tag, do not translate. */
                    esc_html__( '%1$sView version %2$s details%3$s.', 'profile-builder' ),
                    '<a target="_blank" class="thickbox open-plugin-details-modal" href="' . esc_url( $changelog_link ) . '">',
                    esc_html( $update_cache->response[ $this->name ]->new_version ),
                    '</a>'
                );
            } elseif ( ! empty( $changelog_link ) ) {
                echo ' ';
                printf(
                    esc_html__( '%1$sView version %2$s details%3$s or %4$supdate now%5$s.', 'profile-builder' ),
                    '<a target="_blank" class="thickbox open-plugin-details-modal" href="' . esc_url( $changelog_link ) . '">',
                    esc_html( $update_cache->response[ $this->name ]->new_version ),
                    '</a>',
                    '<a target="_blank" class="update-link" href="' . esc_url( wp_nonce_url( $update_link, 'upgrade-plugin_' . $file ) ) . '">',
                    '</a>'
                );
            } else {
                printf(
                    ' %1$s%2$s%3$s',
                    '<a target="_blank" class="update-link" href="' . esc_url( wp_nonce_url( $update_link, 'upgrade-plugin_' . $file ) ) . '">',
                    esc_html__( 'Update now.', 'profile-builder' ),
                    '</a>'
                );
            }

            do_action( "in_plugin_update_message-{$file}", $plugin, $plugin );

            echo '</p></div></td></tr>';
        }

        /**
         * Gets the plugins active in a multisite network.
         *
         * @return array
         */
        private function get_active_plugins() {
            $active_plugins         = (array) get_option( 'active_plugins' );
            $active_network_plugins = (array) get_site_option( 'active_sitewide_plugins' );

            return array_merge( $active_plugins, array_keys( $active_network_plugins ) );
        }

        /**
         * Updates information on the "View version x.x details" page with custom data.
         *
         * @uses api_request()
         *
         * @param mixed   $_data
         * @param string  $_action
         * @param object  $_args
         * @return object $_data
         */
        public function plugins_api_filter( $_data, $_action = '', $_args = null ) {

            if ( 'plugin_information' !== $_action ) {

                return $_data;

            }

            if ( ! isset( $_args->slug ) || ( $_args->slug !== $this->slug ) ) {

                return $_data;

            }

            $to_send = array(
                'slug'   => $this->slug,
                'is_ssl' => is_ssl(),
                'fields' => array(
                    'banners' => array(),
                    'reviews' => false,
                    'icons'   => array(),
                ),
            );

            // Get the transient where we store the api request for this plugin for 24 hours
            $edd_api_request_transient = $this->get_cached_version_info();

            //If we have no transient-saved value, run the API, set a fresh transient with the API value, and return that value too right now.
            if ( empty( $edd_api_request_transient ) ) {

                $api_response = $this->api_request( 'plugin_information', $to_send );

                // Expires in 3 hours
                $this->set_version_info_cache( $api_response );

                if ( false !== $api_response ) {
                    $_data = $api_response;
                }
            } else {
                $_data = $edd_api_request_transient;
            }

            // Convert sections into an associative array, since we're getting an object, but Core expects an array.
            if ( isset( $_data->sections ) && ! is_array( $_data->sections ) ) {
                $_data->sections = $this->convert_object_to_array( $_data->sections );
            }

            // Convert banners into an associative array, since we're getting an object, but Core expects an array.
            if ( isset( $_data->banners ) && ! is_array( $_data->banners ) ) {
                $_data->banners = $this->convert_object_to_array( $_data->banners );
            }

            // Convert icons into an associative array, since we're getting an object, but Core expects an array.
            if ( isset( $_data->icons ) && ! is_array( $_data->icons ) ) {
                $_data->icons = $this->convert_object_to_array( $_data->icons );
            }

            // Convert contributors into an associative array, since we're getting an object, but Core expects an array.
            if ( isset( $_data->contributors ) && ! is_array( $_data->contributors ) ) {
                $_data->contributors = $this->convert_object_to_array( $_data->contributors );
            }

            if ( ! isset( $_data->plugin ) ) {
                $_data->plugin = $this->name;
            }

            if ( ! isset( $_data->version ) && ! empty( $_data->new_version ) ) {
                $_data->version = $_data->new_version;
            }

            return $_data;
        }

        /**
         * Convert some objects to arrays when injecting data into the update API
         *
         * Some data like sections, banners, and icons are expected to be an associative array, however due to the JSON
         * decoding, they are objects. This method allows us to pass in the object and return an associative array.
         *
         * @since 3.6.5
         *
         * @param stdClass $data
         *
         * @return array
         */
        private function convert_object_to_array( $data ) {
            if ( ! is_array( $data ) && ! is_object( $data ) ) {
                return array();
            }
            $new_data = array();
            foreach ( $data as $key => $value ) {
                $new_data[ $key ] = is_object( $value ) ? $this->convert_object_to_array( $value ) : $value;
            }

            return $new_data;
        }

        /**
         * Disable SSL verification in order to prevent download update failures
         *
         * @param array   $args
         * @param string  $url
         * @return object $array
         */
        public function http_request_args( $args, $url ) {

            if ( strpos( $url, 'https://' ) !== false && strpos( $url, 'edd_action=package_download' ) ) {
                $args['sslverify'] = $this->verify_ssl();
            }
            return $args;
        }

        /**
         * Calls the API and, if successfull, returns the object delivered by the API.
         *
         * @uses get_bloginfo()
         * @uses wp_remote_post()
         * @uses is_wp_error()
         *
         * @param string  $_action The requested action.
         * @param array   $_data   Parameters for the API action.
         * @return false|object|void
         */
        private function api_request( $_action, $_data ) {
            $data = array_merge( $this->api_data, $_data );

            if ( $data['slug'] !== $this->slug ) {
                return;
            }

            // Don't allow a plugin to ping itself
            if ( trailingslashit( home_url() ) === $this->api_url ) {
                return false;
            }

            if ( $this->request_recently_failed() ) {
                return false;
            }

            return $this->get_version_from_remote();
        }

        /**
         * Determines if a request has recently failed.
         *
         * @since 1.9.1
         *
         * @return bool
         */
        private function request_recently_failed() {
            $failed_request_details = get_option( $this->failed_request_cache_key );

            // Request has never failed.
            if ( empty( $failed_request_details ) || ! is_numeric( $failed_request_details ) ) {
                return false;
            }

            /*
             * Request previously failed, but the timeout has expired.
             * This means we're allowed to try again.
             */
            if ( current_time( 'timestamp' ) > $failed_request_details ) {
                delete_option( $this->failed_request_cache_key );

                return false;
            }

            return true;
        }

        /**
         * Logs a failed HTTP request for this API URL.
         * We set a timestamp for 1 hour from now. This prevents future API requests from being
         * made to this domain for 1 hour. Once the timestamp is in the past, API requests
         * will be allowed again. This way if the site is down for some reason we don't bombard
         * it with failed API requests.
         *
         * @see EDD_SL_Plugin_Updater::request_recently_failed
         *
         * @since 1.9.1
         */
        private function log_failed_request() {
            update_option( $this->failed_request_cache_key, strtotime( '+1 hour' ) );
        }

        /**
         * Gets the current version information from the remote site.
         *
         * @return array|false
         */
        private function get_version_from_remote() {
            $api_params = array(
                'edd_action'  => 'get_version',
                'license'     => ! empty( $this->api_data['license'] ) ? $this->api_data['license'] : '',
                'item_name'   => isset( $this->api_data['item_name'] ) ? $this->api_data['item_name'] : false,
                'item_id'     => isset( $this->api_data['item_id'] ) ? $this->api_data['item_id'] : false,
                'version'     => isset( $this->api_data['version'] ) ? $this->api_data['version'] : false,
                'slug'        => $this->slug,
                'author'      => $this->api_data['author'],
                'url'         => home_url(),
                'beta'        => $this->beta,
                'php_version' => phpversion(),
                'wp_version'  => get_bloginfo( 'version' ),
            );

            /**
             * Filters the parameters sent in the API request.
             *
             * @param array  $api_params        The array of data sent in the request.
             * @param array  $this->api_data    The array of data set up in the class constructor.
             * @param string $this->plugin_file The full path and filename of the file.
             */
            $api_params = apply_filters( 'edd_sl_plugin_updater_api_params', $api_params, $this->api_data, $this->plugin_file );

            $request = wp_remote_post(
                $this->api_url,
                array(
                    'timeout'   => 15,
                    'sslverify' => $this->verify_ssl(),
                    'body'      => $api_params,
                )
            );

            if ( is_wp_error( $request ) || ( 200 !== wp_remote_retrieve_response_code( $request ) ) ) {
                $this->log_failed_request();

                return false;
            }

            $request = json_decode( wp_remote_retrieve_body( $request ) );

            if ( $request && isset( $request->sections ) ) {
                $request->sections = maybe_unserialize( $request->sections );
            } else {
                $request = false;
            }

            if ( $request && isset( $request->banners ) ) {
                $request->banners = maybe_unserialize( $request->banners );
            }

            if ( $request && isset( $request->icons ) ) {
                $request->icons = maybe_unserialize( $request->icons );
            }

            if ( ! empty( $request->sections ) ) {
                foreach ( $request->sections as $key => $section ) {
                    $request->$key = (array) $section;
                }
            }

            return $request;
        }

        /**
         * If available, show the changelog for sites in a multisite install.
         */
        public function show_changelog() {

            if ( empty( $_REQUEST['edd_sl_action'] ) || 'view_plugin_changelog' !== $_REQUEST['edd_sl_action'] ) {
                return;
            }

            if ( empty( $_REQUEST['plugin'] ) ) {
                return;
            }

            if ( empty( $_REQUEST['slug'] ) || $this->slug !== $_REQUEST['slug'] ) {
                return;
            }

            if ( ! current_user_can( 'update_plugins' ) ) {
                wp_die( esc_html__( 'You do not have permission to install plugin updates', 'profile-builder' ), esc_html__( 'Error', 'profile-builder' ), array( 'response' => 403 ) );
            }

            $version_info = $this->get_repo_api_data();
            if ( isset( $version_info->sections ) ) {
                $sections = $this->convert_object_to_array( $version_info->sections );
                if ( ! empty( $sections['changelog'] ) ) {
                    echo '<div style="background:#fff;padding:10px;">' . wp_kses_post( $sections['changelog'] ) . '</div>';
                }
            }

            exit;
        }

        /**
         * Get the version info from the cache, if it exists.
         *
         * @param string $cache_key
         * @return object
         */
        public function get_cached_version_info( $cache_key = '' ) {

            if ( empty( $cache_key ) ) {
                $cache_key = $this->get_cache_key();
            }

            $cache = get_option( $cache_key );

            // Cache is expired
            if ( empty( $cache['timeout'] ) || current_time('timestamp') > $cache['timeout'] ) {
                return false;
            }

            // We need to turn the icons into an array, thanks to WP Core forcing these into an object at some point.
            $cache['value'] = json_decode( $cache['value'] );
            if ( ! empty( $cache['value']->icons ) ) {
                $cache['value']->icons = (array) $cache['value']->icons;
            }

            return $cache['value'];
        }

        /**
         * Adds the plugin version information to the database.
         *
         * @param string $value
         * @param string $cache_key
         */
        public function set_version_info_cache( $value = '', $cache_key = '' ) {

            if ( empty( $cache_key ) ) {
                $cache_key = $this->get_cache_key();
            }

            $data = array(
                'timeout' => strtotime( '+3 hours', current_time('timestamp') ),
                'value'   => wp_json_encode( $value ),
            );

            update_option( $cache_key, $data, 'no' );

            // Delete the duplicate option
            delete_option( 'edd_api_request_' . md5( serialize( $this->slug . $this->api_data['license'] . $this->beta ) ) );
        }

        /**
         * Returns if the SSL of the store should be verified.
         *
         * @since  1.6.13
         * @return bool
         */
        private function verify_ssl() {
            return (bool) apply_filters( 'edd_sl_api_request_verify_ssl', true, $this );
        }

        /**
         * Gets the unique key (option name) for a plugin.
         *
         * @since 1.9.0
         * @return string
         */
        private function get_cache_key() {
            $string = $this->slug . $this->api_data['license'] . $this->beta;

            return 'edd_sl_' . md5( serialize( $string ) );
        }

    }
}

class WPPB_Plugin_Updater {

    private $store_url = "https://www.cozmoslabs.com";

    public function __construct(){

        if( defined( 'WPPB_PAID_PLUGIN_DIR' ) ){
            add_action('admin_init', array( $this, 'activate_license' ) );
            add_action('admin_init', array( $this, 'deactivate_license' ) );
            add_action('admin_notices', array( $this, 'admin_activation_notices' ) );

            add_filter('pre_set_site_transient_update_plugins', array( $this, 'check_license' ) );

            // Activate current site if license is correct
            add_action('admin_init', array( $this, 'initial_site_activation' ) );
        }

    }

    protected function get_option( $license_key_option ){

        if( is_multisite() ){

            $license = get_site_option( $license_key_option );

            // fall back to old settings option in case this is empty
            if( empty( $license ) )
                $license = get_option( $license_key_option );

            return $license;

        } else
            return get_option( $license_key_option );

    }

    protected function delete_option( $license_key_option ){
        if( is_multisite() )
            delete_site_option( $license_key_option );
        else
            delete_option( $license_key_option );
    }

    protected function update_option( $license_key_option, $value ){
        if( is_multisite() )
            update_site_option( $license_key_option, $value );
        else
            update_option( $license_key_option, $value );
    }

    protected function license_page_url( ){

        if( !is_multisite() )
            return admin_url( 'admin.php?page=profile-builder-general-settings' );
        else
            return network_admin_url( 'admin.php?page=profile-builder-register' );

    }

    public function edd_sanitize_license( $new ) {
        $new = sanitize_text_field($new);
        $old = wppb_get_serial_number();
        if( $old && $old != $new ) {
            $this->delete_option( 'wppb_license_status' ); // new license has been entered, so must reactivate
        }
        return $new;
    }

    /**
     * This function is run when wordpress checks for updates ( twice a day I believe )
     * @param $transient_data
     * @return mixed
     */
    public function check_license( $transient_data ){

        if( empty( $transient_data->response ) )
            return $transient_data;

        if ( false === ( $wppb_check_license = get_transient( 'wppb_checked_licence' ) ) ) {

            $license         = trim( wppb_get_serial_number() );
            $license_details = array();

            // data to send in our API request
            $api_params = array(
                'edd_action' => 'activate_license',                  //as the license is already activated this does not do anything. We could use check_license action but it gives different results  so we can't use it consistently with the result we get from the moment we activate it
                'license'    => $license,
                'item_name'  => urlencode( $this->get_edd_product_name() ), // the name of our product in EDD
                'url'        => home_url()
            );

            // Call the custom API.
            $response = wp_remote_post( $this->store_url, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

            // make sure the response came back okay
            if ( !is_wp_error($response) ) {

                $license_data = json_decode(wp_remote_retrieve_body($response));

                if ( false === $license_data->success )
                    $license_details = $license_data;
                else
                    $license_details = $license_data;

            }

            $this->update_option('wppb_license_details', $license_details);

            if( !$license ){
                //we need to throw a notice if we have a pro addon active and no license entered
                $license_details = (object) array( 'error' => 'missing' );
                $this->update_option('wppb_license_details', $license_details);
            }


            set_transient( 'wppb_checked_licence', 'yes', DAY_IN_SECONDS );

        }

        return $transient_data;
    }

    public function admin_activation_notices() {
        if ( isset( $_GET['wppb_sl_activation'] ) && ! empty( $_GET['message'] ) && isset( $_GET['wppb_license_nonce'] ) && wp_verify_nonce( sanitize_text_field( $_GET['wppb_license_nonce'] ), 'wppb_license_display_message' ) ) {

            switch( $_GET['wppb_sl_activation'] ) {
                case 'false':
                    $class ="error";
                    break;
                case 'true':
                default:
                    $class ="updated";
                    break;
            }

            ?>
            <div class="<?php echo esc_attr( $class ); ?>">
                <p><?php echo wp_kses_post( urldecode( $_GET['message'] ) );//phpcs:ignore ?></p>
            </div>
            <?php
        }
    }

    public function activate_license() {

        // listen for our activate button to be clicked
        if( isset( $_POST['wppb_edd_license_activate'] ) ) {
            // run a quick security check
            if( ! check_admin_referer( 'wppb_license_nonce', 'wppb_license_nonce' ) )
                return; // get out if we didn't click the Activate button

            if( !current_user_can( 'manage_options' ) )
                return;

            if ( isset( $_POST['wppb_license_key'] ) && preg_match('/[*]{3,}/', $_POST['wppb_license_key']) && strlen( $_POST['wppb_license_key'] ) > 5 ) { //phpcs:ignore
                // pressed submit without altering the existing license key (containing only * as outputted by default)
                // useful for Deactivating/Activating valid license back
                $license = wppb_get_serial_number();
            } else {
                // save the license
                $license = $this->edd_sanitize_license( trim( $_POST['wppb_license_key'] ) );//phpcs:ignore
                $this->update_option( 'wppb_license_key', $license );
            }

            $message         = array();
            $license_details = array();

            // data to send in our API request
            $api_params = array(
                'edd_action' => 'activate_license',
                'license'    => $license,
                'item_name'  => urlencode( $this->get_edd_product_name() ), // the name of our product in EDD
                'url'        => home_url()
            );

            // Call the custom API.
            $response = wp_remote_post( $this->store_url, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

            // make sure the response came back okay
            if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {

                $response_error_message = $response->get_error_message();
                $message[] = ( is_wp_error( $response ) && ! empty( $response_error_message ) ) ? $response->get_error_message() : __( 'An error occurred, please try again.', 'profile-builder' );

            } else {

                $license_data = json_decode( wp_remote_retrieve_body( $response ) );

                if ( false === $license_data->success ) {

                    switch( $license_data->error ) {
                        case 'expired' :
                            $message[] = sprintf(
                                __( 'Your license key expired on %s.', 'profile-builder' ),
                                date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires, current_time( 'timestamp' ) ) )
                            );
                            break;
                        case 'revoked' :
                            $message[] = __( 'Your license key has been disabled.', 'profile-builder' );
                            break;
                        case 'missing' :
                            $message[] = __( 'Invalid license.', 'profile-builder' );
                            break;
                        case 'invalid' :
                        case 'site_inactive' :
                            $message[] = __( 'Your license is not active for this URL.', 'profile-builder' );
                            break;
                        case 'item_name_mismatch' :
                            $message[] = sprintf( __( 'This appears to be an invalid license key for %s.', 'profile-builder' ), $this->get_edd_product_name() );
                            break;
                        case 'no_activations_left':
                            $message[] = __( 'Your license key has reached its activation limit.', 'profile-builder' );
                            break;
                        default :
                            $message[] = __( 'An error occurred, please try again.', 'profile-builder' );
                            break;
                    }

                    $license_details = $license_data;

                } else {
                    $license_details = $license_data;
                }

            }

            //store the license reponse for each addon in the database
            $this->update_option( 'wppb_license_details', $license_details );

            // Check if anything passed on a message constituting a failure
            if ( ! empty( $message ) ) {
                $message = implode( "<br/>", array_unique($message) );//if we got the same message for multiple addons show just one, and add a br in case we show multiple messages
                $redirect = add_query_arg( array( 'wppb_sl_activation' => 'false', 'message' => urlencode( $message ), 'wppb_license_nonce' => wp_create_nonce( 'wppb_license_display_message' ) ), $this->license_page_url() );

                $this->update_option( 'wppb_license_status', isset( $license_data->error ) ? $license_data->error : $license_data->license );

                wp_redirect( $redirect );
                exit();
            }

            // $license_data->license will be either "valid" or "invalid"
            $this->update_option( 'wppb_license_status', isset( $license_data->error ) ? $license_data->error : $license_data->license );

            $redirect = add_query_arg( array( 'wppb_sl_activation' => 'true', 'message' => urlencode( __( 'You have successfully activated your license.', 'profile-builder' ) ), 'wppb_license_nonce' => wp_create_nonce( 'wppb_license_display_message' ) ), $this->license_page_url() );
            
            wp_redirect( $redirect );
            exit();
        }
    }


    public function initial_site_activation(){

        if( is_multisite() )
            $edd_sl_initial_activation = get_network_option( null, 'wppb_edd_sl_initial_activation', false );
        else
            $edd_sl_initial_activation = get_option( 'wppb_edd_sl_initial_activation', false );

        if( $edd_sl_initial_activation != false )
            return;

        $license = wppb_get_serial_number();

        if( empty( $license ) )
            return;

        // data to send in our API request
        $api_params = array(
            'edd_action' => 'activate_license',
            'license'    => $license,
            'item_name'  => urlencode( $this->get_edd_product_name() ), // the name of our product in EDD
            'url'        => home_url()
        );

        // Call the custom API.
        $response = wp_remote_post( $this->store_url, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

        if ( !is_wp_error($response) ) {

            $license_data = json_decode(wp_remote_retrieve_body($response));

            if ( false === $license_data->success )
                $license_details = $license_data;
            else
                $license_details = $license_data;

        }

        $this->update_option('wppb_license_details', $license_details);

        if( is_multisite() )
            update_network_option( null, 'wppb_edd_sl_initial_activation', 'yes' );
        else
            update_option( 'wppb_edd_sl_initial_activation', 'yes', false );

    }

    function deactivate_license() {

        // listen for our activate button to be clicked
        if( isset( $_POST['wppb_edd_license_deactivate'] ) ) {

            // run a quick security check
            if( ! check_admin_referer( 'wppb_license_nonce', 'wppb_license_nonce' ) )
                return; // get out if we didn't click the Activate button

            if( !current_user_can( 'manage_options' ) )
                return;

            // retrieve the license from the database
            $license = trim( wppb_get_serial_number() );

            // data to send in our API request
            $api_params = array(
                'edd_action' => 'deactivate_license',
                'license'    => $license,
                'item_name'  => urlencode( $this->get_edd_product_name() ), // the name of our product in EDD
                'url'        => home_url()
            );

            // Call the custom API.
            $response = wp_remote_post( $this->store_url, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

            // make sure the response came back okay
            if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {

                if ( is_wp_error( $response ) )
                    $message = $response->get_error_message();
                else
                    $message = __( 'An error occurred, please try again.', 'profile-builder' );

                wp_redirect( add_query_arg( array( 'wppb_sl_activation' => 'false', 'message' => urlencode( $message ), 'wppb_license_nonce' => wp_create_nonce( 'wppb_license_display_message' ) ), $this->license_page_url() ) );
                exit();
            }

            // decode the license data
            $license_data = json_decode( wp_remote_retrieve_body( $response ) );

            // $license_data->license will be either "deactivated" or "failed"
            // regardless, we delete the record in the client website. Otherwise, if he tries to add a new license, he can't.
            if( $license_data->license == 'deactivated' || $license_data->license == 'failed'){
                delete_option( 'wppb_license_status' );
                delete_option( 'wppb_license_details' );
            }

            wp_redirect( $this->license_page_url() );
            exit();

        }

    }

    function get_edd_product_name(){

        return PROFILE_BUILDER;

    }

}

new WPPB_Plugin_Updater();