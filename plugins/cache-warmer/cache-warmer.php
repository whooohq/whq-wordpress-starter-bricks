<?php
/**
 * Plugin Name: Cache Warmer
 * Description: Visits website pages to warm (create) the cache if you have any caching solutions configured.
 * Version:     1.3.8
 * Text Domain: cache-warmer
 * Author:      TMM Technology
 * Author URI:  https://tmm.ventures/
 * Requires PHP: 7.4
 * License:     GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package Cache-Warmer
 */

namespace Cache_Warmer;

/**
 * Main Cache_Warmer class.
 */
final class Cache_Warmer {

    /**
     * Cache warmer process hook name.
     */
    const HOOK_NAME = 'cache_warmer_process';

    /**
     * Cache warmer process hook name for interval.
     */
    const INTERVAL_HOOK_NAME = 'cache_warmer_process_interval';

    /**
     * AJAX class.
     *
     * @var AJAX
     */
    public static $ajax;

    /**
     * Options.
     *
     * @var Options
     */
    public static $options;

    /**
     * Plugin name.
     *
     * @var string
     */
    public static $name;

    /**
     * Plugin slug.
     *
     * @var string
     */
    public static $slug;

    /**
     * Plugin version.
     *
     * @var string
     */
    public static $version;

    /**
     * The list of extensions that are polyfilled and therefore not required to run the plugin.
     *
     * Some of them can be fakely polyfilled (just to allow to start the plugin).
     *
     * @var string[]
     */
    public static $polyfilled_extensions = [
        // Faked.
        'simplexml',
    ];

    /**
     * Constructor.
     */
    public function __construct() {
        $this->define_constants();
        $this->import_plugin_files();

        // Schedule intervals.
        new Intervals_Scheduler();

        add_action(
            'plugins_loaded',
            function() {
                new \WP_Plugins_Core\WP_Plugins_Core( $this, false );

                // Load translations.
                $this->load_plugin_textdomain();

                // Options.
                self::$options = new Options();

                // Handle version update.
                $this->handle_version_update();

                // The plugin is being updated notice.
                if ( self::$version !== self::$options->get( 'last-handled-version-update' ) ) {
                    add_action(
                        'admin_notices',
                        function() {
                            ?>
                            <div class="notice notice-info">
                                <p>
                                    <b><?php echo esc_html( self::$name ); ?>: </b>
                                    <?php
                                    esc_html_e(
                                        'The plugin is being updated in the background.',
                                        'cache-warmer'
                                    );
                                    ?>
                                </p>
                            </div>
                            <?php
                        }
                    );
                }

                // Interval.
                new Interval();

                // Posts Warming Interval.
                new Posts_Warming_Interval();

                // Clear old actions.
                new Clear_Old_Actions();

                // Assets.
                new Assets\Assets();

                // Post publish box.
                new Representation\Publish_Box();

                // Server IP detection logic.
                new Server_IP_Detection();

                // Adds dashboard widget.
                add_action( 'wp_dashboard_setup', [ $this, 'setup_dashboard_widget' ] );

                // Adds plugin settings links to plugins admin screen.
                add_filter( 'plugin_action_links_' . CACHE_WARMER_BASENAME, [ $this, 'plugin_action_links' ] );

                add_action( self::HOOK_NAME, [ 'Cache_Warmer\Warm_Up', 'process' ] );
                add_action( self::INTERVAL_HOOK_NAME, [ 'Cache_Warmer\AJAX', 'start_warm_up' ] );

                add_action(
                    'init',
                    function () {

                        // Posts enqueue.
                        new Posts_Enqueue();

                        // External warmer.
                        new External_Warmer();

                        // Menu.
                        new Admin_Menu();

                        // AJAX Handler.
                        self::$ajax = new AJAX();

                        // Extend WP CLI.
                        new Extend_WP_CLI();
                    }
                );
            }
        );
    }

    /**
     * Defines constants.
     */
    private function define_constants() {
        require_once __DIR__ . '/data/constants.php';

        /**
         * Plugin name.
         */
        self::$name = get_file_data( CACHE_WARMER_FILE, [ 'Plugin Name' ] )[0];

        /**
         * Plugin slug.
         */
        $dir_parts  = explode( DIRECTORY_SEPARATOR, CACHE_WARMER_DIR );
        self::$slug = $dir_parts[ array_search( 'plugins', $dir_parts, true ) + 1 ];

        /**
         * Plugin version.
         */
        self::$version = CACHE_WARMER_VERSION;
    }

    /**
     * Imports plugin files.
     */
    private function import_plugin_files() {
        $src_files = [
            'assets/class-assets',
            'assets/screens/class-assets-logs-screen',
            'assets/screens/class-assets-main-screen',
            'assets/screens/class-assets-settings-screen',
            'assets/screens/class-assets-post-screen',
            'assets/screens/class-assets-dashboard',
            'class-admin-menu',
            'class-warm-up',
            'class-ajax',
            'class-summary',
            'class-logging',
            'class-databases',
            'class-options',
            'class-settings-export',
            'class-interval',
            'class-content-parsing',
            'class-tree',
            'class-leaf-only-subtree',
            'class-url-formatting',
            'class-url-parsing',
            'class-url-validation',
            'class-debug',
            'class-utils',
            'class-object-cache',
            'class-server-ip-detection',
            'class-migrations',
            'class-clear-old-actions',
            'class-external-warmer',
            'class-intervals-scheduler',
            'class-extend-wp-cli',
            'posts-warming/class-posts-enqueue',
            'posts-warming/class-posts-warming-interval',
            'integrations/class-cache-plugins-integration',
            'integrations/class-wp-super-cache',
            'representation/class-publish-box',
        ];
        foreach ( $src_files as $file ) {
            require_once CACHE_WARMER_DIR . 'src/' . $file . '.php';
        }

        $files = [
            'libs/phpuri/phpuri',
            'vendor/autoload_packages',
            'vendor/tmmtech/wp-plugins-core/wp-plugins-core',
            'vendor/woocommerce/action-scheduler/action-scheduler',
        ];
        foreach ( $files as $file ) {
            require_once CACHE_WARMER_DIR . $file . '.php';
        }
    }

    /**
     * Loads textdomain.
     */
    private function load_plugin_textdomain() {
        load_plugin_textdomain(
            'cache-warmer',
            false,
            dirname( CACHE_WARMER_BASENAME ) . '/languages'
        );
    }

    /**
     * Show settings link on the plugin screen.
     *
     * @param mixed $links Plugin Action links.
     *
     * @return array
     */
    public function plugin_action_links( $links ) {
        $action_links = array(
            'settings' => '<a href="' . admin_url( 'admin.php?page=cache-warmer-settings' ) . '" aria-label="' .
                esc_attr__( 'View Cache Warmer settings', 'cache-warmer' ) . '">' . esc_html__( 'Settings', 'cache-warmer' ) . '</a>',
        );

        return array_merge( $action_links, $links );
    }

    /**
     * Handles version update.
     */
    private function handle_version_update() {
        if ( time() - MINUTE_IN_SECONDS > (int) get_option( 'cache-warmer-updating' ) ) {
            $last_handled_version_update = self::$options->get( 'last-handled-version-update' );
            if ( self::$version !== $last_handled_version_update ) {
                update_option( 'cache-warmer-updating', time() );

                DB::do_migrations();
                new Migrations( $last_handled_version_update );

                self::$options->set( 'last-handled-version-update', self::$version );
                delete_option( 'cache-warmer-updating' );
            }
        }
    }

    /**
     * Adds the dashboard metrics widget.
     */
    public function setup_dashboard_widget() {
        wp_add_dashboard_widget(
            'dashboard_cache_warmer',
            __( 'Cache Warmer', 'cache-warmer' ),
            [ $this, 'show_dashboard_widget' ],
            null,
            null,
            'normal',
            'high'
        );
    }

    /**
     * Displays the dashboard widget
     *
     * @return void
     */
    public function show_dashboard_widget() {
        require_once CACHE_WARMER_DIR . 'src/templates/admin/widget.php';
    }
}
new Cache_Warmer();
