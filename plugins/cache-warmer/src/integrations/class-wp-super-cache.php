<?php
/**
 * Class to handle integrations for WP Super Cache plugin.
 *
 * @package Cache-Warmer
 */

namespace Cache_Warmer\Integrations;

use Cache_Warmer\Cache_Warmer;

/**
 * Cache Plugins Integration.
 */
final class WP_Super_Cache {

    /**
     * Shows admin notices, if necessary.
     */
    public static function admin_notices() {
        global $wpsc_served_header;

        if ( is_plugin_active( 'wp-super-cache/wp-cache.php' ) &&
            ! $wpsc_served_header
        ) {
            ?>
                <div class="notice notice-warning">
                    <p>
                        <b><?php echo esc_html( Cache_Warmer::$name ); ?>: </b>
                        <?php
                            esc_html_e(
                                'To get the cache status for WP Super Cache plugin in logs, set $wpsc_served_header variable to "true" in wp-cache-config.php.',
                                'cache-warmer'
                            );
                            ?>
                    </p>
                </div>
            <?php
        }
    }
}
