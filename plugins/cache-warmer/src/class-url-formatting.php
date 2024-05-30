<?php
/**
 * Class for formatting of URLs.
 *
 * @package Cache-Warmer
 */

namespace Cache_Warmer;

use phpUri;

/**
 * Url_Formatting class.
 */
final class Url_Formatting {

    /**
     * Adds URL params.
     *
     * @param string $url        URL.
     * @param array  $url_params URL params.
     *
     * @return string URL with added url params.
     */
    public static function add_url_params( $url, $url_params ) : string {
        if ( ! $url_params ) {
            return $url;
        }

        // Add URL params and visit second time only pages, and not to assets, sitemaps etc.
        $extension = Url_Parsing::get_extension( $url );
        if ( $extension && ! in_array( $extension, Content_Parsing::PAGE_EXTENSIONS, true ) ) {
            return $url;
        }

        $url_parts = wp_parse_url( $url );

        if ( isset( $url_parts['query'] ) ) {
            parse_str( $url_parts['query'], $params );
        } else {
            $params = [];
        }

        foreach ( $url_params as $param ) {
            if ( array_key_exists( 'value', $param ) ) {
                $params[ $param['name'] ] = $param['value'];
            } else {
                $params[ $param['name'] ] = 1;
            }
        }

        $url_parts['query'] = http_build_query( $params );

        return $url_parts['scheme'] . '://' . $url_parts['host'] .
            ( array_key_exists( 'port', $url_parts ) ? ':' . $url_parts['port'] : '' ) .
            ( array_key_exists( 'path', $url_parts ) ? $url_parts['path'] : '' ) .
            '?' . $url_parts['query'];
    }

    /**
     * Converts the URL from relative to absolute (and returns false if failed to convert).
     *
     * @param string $url         URL.
     * @param string $current_url Current page, which the page is a child of. Used to convert relative links to absolute.
     *                            If not specified, expects that the $link is immediately the absolute URL.
     *
     * @return string|bool String if successfully converted the URL to absolute path, false on failure
     *                     (if absolute URL scheme is not recognised, or it's not 'http' / 'https').
     */
    public static function convert_a_url_to_absolute( $url, $current_url = null ) {
        $absolute_url = $current_url ?
            phpUri::parse( $current_url )->join( $url ) : // Convert relative URL to absolute.
            $url;

        $url_parts = wp_parse_url( $absolute_url );

        if ( ! isset( $url_parts['scheme'] ) || ! in_array( $url_parts['scheme'], [ 'http', 'https' ], true ) ) { // Only http / https protocol (no mailto: etc).
            return false;
        }

        return $url_parts['scheme'] . '://' .
            ( array_key_exists( 'host', $url_parts ) ? $url_parts['host'] : '' ) .
            ( array_key_exists( 'port', $url_parts ) ? ':' . $url_parts['port'] : '' ) .
            ( array_key_exists( 'path', $url_parts ) ? $url_parts['path'] : '' ) .
            ( array_key_exists( 'query', $url_parts ) ? '?' . $url_parts['query'] : '' );
    }

    /**
     * Untrailingslashes a URL.
     *
     * Usually used to avoid duplicate page visits, let's say "https://example.com/" and "https://example.com".
     *
     * @param string $absolute_url An absolute URL.
     *
     * @return string Untrailingslashes absolute URL, or the same URL if failed to untrailingslash.
     */
    public static function untrailingslash_a_url( string $absolute_url ): string {
        $url_parts = wp_parse_url( $absolute_url );

        if ( ! isset( $url_parts['scheme'] ) || ! in_array( $url_parts['scheme'], [ 'http', 'https' ], true ) ) {
            return $absolute_url;
        }

        return $url_parts['scheme'] . '://' .
            ( array_key_exists( 'host', $url_parts ) ? $url_parts['host'] : '' ) .
            ( array_key_exists( 'port', $url_parts ) ? ':' . $url_parts['port'] : '' ) .
            ( array_key_exists( 'path', $url_parts ) ? untrailingslashit( $url_parts['path'] ) : '' ) .
            ( array_key_exists( 'query', $url_parts ) ? '?' . $url_parts['query'] : '' );
    }

    /**
     * Rewrite URL to HTTPS.
     *
     * @param string $url URL.
     *
     * @return string
     */
    public static function rewrite_url_to_https( string $url ) : string {
        return preg_replace( '/^http:/i', 'https:', $url );
    }
}
