<?php
/**
 * A class for the plugins changelog display.
 *
 * @package WP-Plugins-Core
 */

namespace WP_Plugins_Core;

/**
 * Changelog class.
 */
final class Changelog {

    /**
     * Main plugin.
     *
     * @var WP_Plugins_Core
     */
    public $core;

    /**
     * Changelog.
     *
     * @var array
     */
    public $changelog;

    /**
     * Constructor.
     *
     * @param WP_Plugins_Core $core Plugin core.
     */
    public function __construct( WP_Plugins_Core $core ) {
        $this->core = $core;

        $this->changelog = $this->get_changelog();

        if ( $this->changelog ) {
            // Assets.
            new Assets\Dashboard;

            // Adds dashboard widget.
            add_action( 'wp_dashboard_setup', [ $this, 'setup_dashboard_widget' ] );
        }
    }

    /**
     * Get content of README file.
     *
     * @return array Changelog. Where array key is version and value is changelog description.
     */
    private function get_changelog() {
        $readme_file = $this->core->plugin_dir . '/readme.txt';

        $changelog = [];
        if ( is_file( $readme_file ) ) {
            $file      = fopen( $readme_file, 'r' ); // @codingStandardsIgnoreLine

            $changelog_started = false;

            $version = null;

            // @codingStandardsIgnoreLine
            while ( ! feof( $file ) ) {
                $line         = fgets( $file );
                $trimmed_line = trim( $line );

                if ( $changelog_started && str_starts_with( $trimmed_line, '== ' ) ) { // Changelog ended.
                    break;
                }

                if ( '== Changelog ==' === $trimmed_line ) {
                    $changelog_started = true;
                }

                if ( $changelog_started ) {
                    if ( preg_match( '/^= (.*) =/', $trimmed_line, $matches ) ) {
                        $version               = $matches[1];
                        $changelog[ $version ] = '';
                    } elseif ( $version ) {
                        $changelog[ $version ] .= $line;
                    }
                }
            }
            fclose( $file ); // @codingStandardsIgnoreLine

            $changelog = array_map( 'trim', $changelog );
        }
        return $changelog;
    }

    /**
     * Adds the dashboard metrics widget.
     */
    public function setup_dashboard_widget() {
        wp_add_dashboard_widget(
            "dashboard_tmm_wp_plugins_core_changelog_{$this->core->plugin_slug}",
            /* translators: %s is the plugin name. */
            sprintf( __( 'Changelog (%s)', 'tmm-wp-plugins-core' ), $this->core->plugin_name ),
            [ $this, 'show_dashboard_widget' ],
            null,
            null,
            'side',
            'default'
        );
    }

    /**
     * Displays the changelog dashboard widget.
     */
    public function show_dashboard_widget() {
        require TMM_WP_PLUGINS_CORE_DIR . 'src/templates/changelog-widget.php';
    }
}
