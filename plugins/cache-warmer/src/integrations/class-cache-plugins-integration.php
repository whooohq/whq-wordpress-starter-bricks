<?php
/**
 * Class to handle integrations for cache plugins.
 *
 * @package Cache-Warmer
 */

namespace Cache_Warmer\Integrations;

/**
 * Cache Plugins Integration.
 */
final class Cache_Plugins_Integration {

    /**
     * Prints admin notices.
     */
    public static function admin_notices() {
        WP_Super_Cache::admin_notices();
    }
}
