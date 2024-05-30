<?php
/**
 * A class for misc Utils.
 *
 * @package WP-Plugins-Core
 */

namespace WP_Plugins_Core;

/**
 * Class Utils
 */
final class Utils {

    /**
     * Map array recursively.
     *
     * @param callable $callback Callback.
     * @param array    $array    Array to map.
     *
     * @return array
     */
    public static function array_map_recursive( $callback, $array ) {
        $func = function ( $x ) use ( &$func, &$callback ) {
            return is_array( $x ) ? array_map( $func, $x ) : call_user_func( $callback, $x );
        };

        return array_map( $func, $array );
    }
}
