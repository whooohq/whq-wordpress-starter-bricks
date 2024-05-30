<?php
/**
 * Manages difference versions of the same plugin.
 *
 * @package WP-Plugins-Core
 */

namespace WP_Plugins_Core;

/**
 * Class Versions.
 *
 * Inspired by @see \ActionScheduler_Versions
 */
class Versions {

    /**
     * Instance of this class.
     *
     * @var Versions
     */
    private static $instance = null;

    /**
     * The list of registered versions.
     *
     * @var array
     */
    private $versions = [];

    /**
     * Registers a version.
     *
     * @param string   $version_string          Version.
     * @param callable $initialization_callback Callback.
     *
     * @return bool False if version is already registered, true otherwise.
     */
    public function register( $version_string, $initialization_callback ) {
        if ( isset( $this->versions[ $version_string ] ) ) {
            return false;
        }
        $this->versions[ $version_string ] = $initialization_callback;
        return true;
    }

    /**
     * Returns the list versions.
     *
     * @return array
     */
    public function get_versions() {
        return $this->versions;
    }

    /**
     * Returns latest version, or false if no versions.
     *
     * @return false|string
     */
    public function latest_version() {
        $keys = array_keys( $this->versions );
        if ( empty( $keys ) ) {
            return false;
        }
        uasort( $keys, 'version_compare' );
        return end( $keys );
    }

    /**
     * A callback for the version.
     *
     * @return callable|string Callback.
     */
    public function latest_version_callback() {
        $latest = $this->latest_version();
        if ( empty( $latest ) || ! isset( $this->versions[ $latest ] ) ) {
            return '__return_null';
        }
        return $this->versions[ $latest ];
    }

    /**
     * Returns an instance of this class.
     *
     * @return Versions
     */
    public static function instance() {
        if ( empty( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initializes the latest version.
     */
    public static function initialize_latest_version() {
        $self = self::instance();
        call_user_func( $self->latest_version_callback() );
    }
}
