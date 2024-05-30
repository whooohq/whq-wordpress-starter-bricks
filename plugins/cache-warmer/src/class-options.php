<?php
/**
 * Class to handle plugin options.
 *
 * @package Cache-Warmer
 */

namespace Cache_Warmer;

use Exception;

/**
 * Manages Options using the WordPress options API.
 */
final class Options {

    /**
     * All options.
     *
     * Values:
     *  'default':   Default option value.              If not set, equals false.
     *  'autoload':  Whether to autoload the option.    If not set, equals true.
     *  'shortTerm': Whether the option is short-term.  If not set, equals false.
     *               If true and object cache is enabled,
     *               then uses it to store the option.
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
     * Constructor.
     */
    public function __construct() {
        $this->all_options = require CACHE_WARMER_DIR . 'data/options.php';

        $this->define_settings_options();
        $this->add_listeners();
    }

    /**
     * Defines settings options.
     */
    private function define_settings_options() {
        foreach ( $this->all_options as $option => $option_data ) {
            if ( preg_match( '@^cache-warmer-setting-@', $option ) ) {
                $this->settings_options[] = $option;
            }
        }
    }

    /**
     * Whether to use object cache for the option.
     *
     * @param string $option_name The name of the option.
     *
     * @return bool
     *
     * @throws Exception Exception.
     */
    public function use_object_cache_for_option( $option_name ) {
        $option_data = $this->all_options[ $option_name ];
        return array_key_exists( 'shortTerm', $option_data ) &&
            $option_data['shortTerm'] &&
            Object_Cache::use_object_cache();
    }

    /**
     * Adds listeners for options to execute the logic on their change.
     */
    private function add_listeners() {
        $options_with_changes_handlers = [
            'cache-warmer-setting-interval' => [ $this, 'handle_interval_value_change' ],
            'cache-warmer-setting-posts-warming-enqueue-interval' => [ $this, 'handle_posts_warming_interval_value_change' ],
        ];

        foreach ( $options_with_changes_handlers as $option => $handler ) {
            add_action( "delete_option_$option", $handler, 10, 1 );
            add_action( "add_option_$option", $handler, 10, 2 );
            add_action( "update_option_$option", $handler, 10, 3 );
        }
    }

    /**
     * Handles interval value change.
     *
     * @param mixed  $old_value The old option value.
     * @param mixed  $value     The new option value.
     * @param string $option    Option name.
     *
     * @throws Exception Exception.
     */
    public function handle_interval_value_change( $old_value = null, $value = null, $option = null ) {
        if ( null === $value ) { // delete_option_$option (1 args).
            Interval::unschedule();
        } else {
            $value = (int) $value;

            if ( null !== $option ) { // update_option_$option (3 args).
                $old_value = (int) $old_value;

                if ( $value !== $old_value ) {
                    Interval::handle_interval_change( $value );
                }
            } else { // add_option_$option (2 args).
                Interval::handle_interval_change( $value );
            }
        }
    }

    /**
     * Handles posts warming enqueue interval value change.
     *
     * @param mixed  $old_value The old option value.
     * @param mixed  $value     The new option value.
     * @param string $option    Option name.
     *
     * @throws Exception Exception.
     */
    public function handle_posts_warming_interval_value_change( $old_value = null, $value = null, $option = null ) {
        if ( null === $value ) { // delete_option_$option (1 args).
            Posts_Warming_Interval::unschedule();
        } else {
            $value = (int) $value;

            if ( null !== $option ) { // update_option_$option (3 args).
                $old_value = (int) $old_value;

                if ( $value !== $old_value ) {
                    Posts_Warming_Interval::handle_interval_change( $value );
                }
            } else { // add_option_$option (2 args).
                Posts_Warming_Interval::handle_interval_change( $value );
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
            throw new Exception( Cache_Warmer::$name . ': ' . esc_html__( 'Unknown option name:', 'tmm-wp-plugins-core' ) . ' ' . esc_html( $option_name ) );
        }
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
            $option_name = Cache_Warmer::$slug . '-' . $option_name;
            $this->validation_option( $option_name );
        }

        $option_data = $this->all_options[ $option_name ];

        if ( $this->use_object_cache_for_option( $option_name ) ) {
            $value = wp_cache_get( $option_name );
            return false === $value ? array_key_exists( 'default', $option_data ) ? $option_data['default'] : false : $value;
        } else {
            return get_option( $option_name, array_key_exists( 'default', $option_data ) ? $option_data['default'] : false );
        }
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
            $option_name = Cache_Warmer::$slug . '-' . $option_name;
            $this->validation_option( $option_name );
        }

        $option_data = $this->all_options[ $option_name ];

        if ( $this->use_object_cache_for_option( $option_name ) ) {
            wp_cache_set( $option_name, $value );
        } else {
            update_option( $option_name, $value, array_key_exists( 'autoload', $option_data ) ? $option_data['autoload'] : null );
        }
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
            $option_name = Cache_Warmer::$slug . '-' . $option_name;
            $this->validation_option( $option_name );
        }

        wp_cache_delete( $option_name ); // If the option is shortTerm (but do not check because we do not need another method).
        delete_option( $option_name );
    }
}
