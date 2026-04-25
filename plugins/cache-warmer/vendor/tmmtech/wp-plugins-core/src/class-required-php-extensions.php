<?php
/**
 * A class for plugin required PHP extensions.
 *
 * When not all required PHP extensions are installed, disabled the plugin and shows an admin notification (error notice).
 *
 * @package WP-Plugins-Core
 */

namespace WP_Plugins_Core;

/**
 * Class Required_PHP_Extensions
 */
final class Required_PHP_Extensions {

    /**
     * Main plugin.
     *
     * @var WP_Plugins_Core
     */
    public $core;

    /**
     * The filepath to the required extensions relative to the plugin.
     */
    const REQUIRED_EXTENSIONS_FILEPATH = 'data/required_extensions.php';

    /**
     * The list of extensions that are polyfilled and therefore not required to run the plugin.
     *
     * @var string[]
     */
    public $polyfilled_extensions = [
        'mbstring',
    ];

    /**
     * Constructor.
     *
     * @param WP_Plugins_Core $core Plugins core.
     */
    public function __construct( WP_Plugins_Core $core ) {

        // Skip the extension check if running in WP-CLI.
        if ( defined( 'WP_CLI' ) && WP_CLI ) {
            return;
        }

        $this->core = $core;

        if ( property_exists( $core->plugin, 'polyfilled_extensions' ) ) {
            $this->polyfilled_extensions = array_merge(
                $this->polyfilled_extensions,
                get_class( $core->plugin )::${'polyfilled_extensions'}
            );
        }

        $required_extensions_path = $this->core->plugin_dir . '/' . self::REQUIRED_EXTENSIONS_FILEPATH;

        if ( is_file( $required_extensions_path ) ) {
            $required_extensions = [];
            require_once $required_extensions_path; // Exports "$required_extensions" variable.

            $missing_extensions = [];

            foreach ( $required_extensions as $required_extension ) {
                if ( ! extension_loaded( $required_extension ) && ! in_array( $required_extension, $this->polyfilled_extensions, true ) ) {
                    $missing_extensions[] = $required_extension;
                }
            }

            if ( $missing_extensions ) {
                if ( ! function_exists( 'deactivate_plugins' ) ) {
                    require_once ABSPATH . 'wp-admin/includes/plugin.php';
                }

                deactivate_plugins( $this->core->plugin_file );

                add_action(
                    'admin_notices',
                    function() use ( $missing_extensions, $core ) {
                        ?>
                        <div class="notice notice-error">
                            <p>
                                <b>
                                    <?php
                                    /* translators: %s is a plugin name. */
                                    echo esc_html( sprintf( __( '%s error:', 'tmm-wp-plugins-core' ), $this->core->plugin_name ) );
                                    ?>
                                </b>
                                <br><br>
                                <?php
                                    $missing_extensions = array_map(
                                        function( $extensions ) {
                                            return '<b>' . $extensions . '</b>';
                                        },
                                        $missing_extensions
                                    );
                                    echo sprintf(
                                        /* translators: %s is missing PHP extensions name(s). */
                                        _n( // @codingStandardsIgnoreLine
                                            'Your server is missing the required PHP extension: %s',
                                            'Your server is missing the required PHP extensions: %s',
                                            count( $missing_extensions )
                                        ),
                                        implode( ', ', $missing_extensions ) // @codingStandardsIgnoreLine
                                    );
                                    echo '<br><br>';
                                    echo sprintf(
                                        esc_html(
                                            _n(
                                                'Please install this PHP extension for using the plugin.',
                                                'Please install these PHP extensions for using the plugin.',
                                                count( $missing_extensions )
                                            )
                                        ),
                                        implode( ', ', $missing_extensions ) // @codingStandardsIgnoreLine
                                    );
                                ?>
                            </p>
                        </div>
                        <?php
                    }
                );
            }
        }
    }
}
