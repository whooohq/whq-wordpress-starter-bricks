<?php
/**
 * Class for debug data.
 *
 * @package Cache-Warmer
 */

namespace Cache_Warmer;

use Exception;

/**
 * Debug class.
 */
final class Debug {

    /**
     * Maybe returns the debug array.
     *
     * @return array Debug array.
     *
     * @throws Exception Exception.
     */
    public static function maybe_get_debug_array() {
        return self::debug_mode_enabled()
            &&
            (
                // Return only 1 / 10th (each 50s on average) due to the potentially big size.
                self::debug_each_request() || 1 === wp_rand( 1, 10 )
            )
            ? self::get_debug_array() : [];
    }

    /**
     * Returns the debug array.
     *
     * @return array Debug array.
     *
     * @throws Exception Exception.
     */
    public static function get_debug_array() {
        $data = [
            'current-server-ip-address'    => Server_IP_Detection::get_current_server_ip(),
            'last-processing-link'         => Cache_Warmer::$options->get( 'last-processing-link' ),
            'last-failed-to-retrieve-link' => Cache_Warmer::$options->get( 'last-failed-to-retrieve-link' ),
            'last-retrieved-link'          => Cache_Warmer::$options->get( 'last-retrieved-link' ),
        ];

        $options_to_inspect = [
            'links-tree-leftovers',
            'retrieved-links',
            'failed-to-retrieve-links',
            'unscheduled-links-tree-leftovers',
            'unscheduled-retrieved-links',
            'unscheduled-failed-to-retrieve-links',
        ];

        foreach ( $options_to_inspect as $option ) {
            $option_name     = "cache-warmer-$option";
            $data[ $option ] = [
                'fromObjectCache' => Cache_Warmer::$options->use_object_cache_for_option( $option_name ),
                'value'           => Cache_Warmer::$options->get( $option_name ),
                'optionValue'     => get_option( $option_name ),
                'cacheValue'      => wp_cache_get( $option_name ),
            ];
        }

        return $data;
    }

    /**
     * Check if the plugin debug mode is enabled.
     *
     * @return bool
     */
    private static function debug_mode_enabled() {
        return isset( $_ENV['CACHE_WARMER_DEBUG'] ) && in_array( $_ENV['CACHE_WARMER_DEBUG'], [ 'true', '1', 'yes' ], true );
    }

    /**
     * Check if needed to debug each request.
     *
     * @return bool
     */
    private static function debug_each_request() {
        return isset( $_ENV['CACHE_WARMER_DEBUG_EACH_REQUEST'] ) && in_array( $_ENV['CACHE_WARMER_DEBUG_EACH_REQUEST'], [ 'true', '1', 'yes' ], true );
    }
}
