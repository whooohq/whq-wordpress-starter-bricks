<?php
/**
 * A class for parsing of URLs.
 *
 * @package Cache-Warmer
 */

namespace Cache_Warmer;

/**
 * Url_Parsing class.
 */
final class Url_Parsing {

    /**
     * Get extension from the link.
     *
     * @param string $link Link.
     *
     * @return bool|string Extension or false if no extension found.
     */
    public static function get_extension( $link ) {
        $extension = false;

        $path = wp_parse_url( $link, PHP_URL_PATH );
        if ( $path ) {
            if ( str_contains( $path, '.' ) ) {
                $extension = array_slice( explode( '.', untrailingslashit( $path ) ), -1 )[0];
            }
        }

        return $extension;
    }
}
