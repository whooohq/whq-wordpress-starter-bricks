<?php
/**
 * A class to sanitize data.
 *
 * @package WP-Plugins-Core
 */

namespace WP_Plugins_Core;

/**
 * Class Sanitize
 */
final class Sanitize {

    /**
     * Sanitizes each item of array item.
     *
     * @param array $array Array to sanitize.
     *
     * @return array Sanitized array.
     */
    public static function sanitize_array( array $array ) {
        return Utils::array_map_recursive(
            function( $x ) {
                return is_string( $x ) ? sanitize_text_field( $x ) : $x;
            },
            $array
        );
    }
}
