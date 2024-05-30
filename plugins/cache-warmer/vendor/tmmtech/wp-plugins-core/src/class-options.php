<?php
/**
 * Class to handle plugin options.
 *
 * @package WP-Plugins-Core
 */

namespace WP_Plugins_Core;

use Exception;

/**
 * Manages Options using WordPress options API.
 */
final class Options {

    /**
     * All options.
     *
     * @var array
     */
    public $all_options = [];

    /**
     * Settings options.
     *
     * @var array
     */
    public $settings_options = [];

    /**
     * Image ID options.
     *
     * @var array
     */
    public $image_id_options = [];

    /**
     * Main plugin.
     *
     * @var WP_Plugins_Core
     */
    public $core;

    /**
     * Constructor.
     *
     * @param WP_Plugins_Core $core Plugin core.
     */
    public function __construct( WP_Plugins_Core $core ) {
        $this->core = $core;

        $this->all_options = require TMM_WP_PLUGINS_CORE_DIR . 'data/options.php';

        $this->define_options();
    }

    /**
     * Defines different types of options.
     */
    private function define_options() {

        /**
         * Defines settings options.
         */

        foreach ( $this->all_options as $option => $option_data ) {
            if ( preg_match( '@^wp-plugins-core-setting-@', $option ) ) {
                $this->settings_options[] = $option;
            }
        }

        /**
         * Defines image ID options.
         */

        foreach ( $this->all_options as $option => $option_data ) {
            if (
                array_key_exists( 'type', $option_data ) &&
                'image_id' === $option_data['type']
            ) {
                $this->image_id_options[] = $option;
            }
        }
    }

    /**
     * Validates option name, and if it does not exist, throws an exception.
     *
     * @param string $option_name Name of the option to validate.
     *
     * @throws Exception Exception.
     */
    private function validation_option( $option_name ) {
        if ( ! array_key_exists( $option_name, $this->all_options ) ) {
            throw new Exception( WP_Plugins_Core::$name . ': ' . esc_html__( 'Unknown option name:', 'tmm-wp-plugins-core' ) . ' ' . esc_html( $option_name ) );
        }
    }

    /**
     * Returns real option name, adding the plugin slug after it.
     *
     * @param string $option_name Name of the option.
     *
     * @return string Real option name.
     */
    private function get_real_option_name( $option_name ) {
        return $option_name . '-' . $this->core->plugin_slug;
    }

    /**
     * Gets the option value. Returns the default value if the value does not exist.
     *
     * @param string $option_name Name of the option to get.
     *
     * @return mixed Option value.
     *
     * @throws Exception Exception.
     */
    public function get( $option_name ) {
        try {
            $this->validation_option( $option_name );
        } catch ( Exception $e ) {
            $option_name = WP_Plugins_Core::$slug . '-' . $option_name;
            $this->validation_option( $option_name );
        }

        $option_data = $this->all_options[ $option_name ];
        return get_option( $this->get_real_option_name( $option_name ), array_key_exists( 'default', $option_data ) ? $option_data['default'] : false );
    }

    /**
     * Sets the option. Update the value if the option for the given name already exists.
     *
     * @param string $option_name Name of the option to set.
     * @param mixed  $value       Value to set for the option.
     *
     * @throws Exception Exception.
     */
    public function set( $option_name, $value ) {
        try {
            $this->validation_option( $option_name );
        } catch ( Exception $e ) {
            $option_name = WP_Plugins_Core::$slug . '-' . $option_name;
            $this->validation_option( $option_name );
        }

        $option_data = $this->all_options[ $option_name ];
        update_option( $this->get_real_option_name( $option_name ), $value, array_key_exists( 'autoload', $option_data ) ? $option_data['autoload'] : null );
    }

    /**
     * Deletes the option value.
     *
     * @param string $option_name Name of the option to delete.
     *
     * @throws Exception Exception.
     */
    public function delete( $option_name ) {
        try {
            $this->validation_option( $option_name );
        } catch ( Exception $e ) {
            $option_name = WP_Plugins_Core::$slug . '-' . $option_name;
            $this->validation_option( $option_name );
        }

        delete_option( $this->get_real_option_name( $option_name ) );
    }
}
