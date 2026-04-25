<?php

/**
 * Fuerte_Wp_TwoFactor Class.
 *
 * @link       https://actitud.xyz
 *
 * @author     Esteban Cuevas <esteban@attitude.cl>
 */

// No access outside WP
defined('ABSPATH') || die();

/**
 * Main Class.
 */
class Fuerte_Wp_TwoFactor extends Fuerte_Wp
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
     * Constructor.
     */
    public function __construct()
    {
        //$this->config = $this->config_setup();
    }

    /**
     * Access this plugin instance.
     */
    public static function get_instance()
    {
        /*
         * To run like:
         * add_action( 'plugins_loaded', array( Fuerte_Wp_Enforcer::get_instance(), 'init' ) );
         */
        null === self::$instance and self::$instance = new self();

        return self::$instance;
    }

    /**
     * Init the plugin.
     */
    public function run()
    {
        $this->twofactor_enforcer();
    }

    /**
     * Enforcer_twofactor method.
     */
    protected function twofactor_enforcer()
    {
        // Check if WP's official two-factor plugin is active
        if (!is_plugin_active('two-factor/two-factor.php')) {
            // Bail out
            return;
        }

        // Don't allow disabling two-factor plugin
        add_filter('plugin_action_links_two-factor/two-factor.php', [$this, 'disable_two_factor']);

    }

    /**
     * Avoid disable two-factor plugin.
     */
    public function disable_two_factor($actions)
    {
        unset($actions['deactivate']);

        return $actions;
    }
} // Class end
