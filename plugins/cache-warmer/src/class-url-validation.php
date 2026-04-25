<?php
/**
 * A class for validation of URLs.
 *
 * @package Cache-Warmer
 */

namespace Cache_Warmer;

/**
 * Url_Validation class.
 */
final class Url_Validation {

    /**
     * Checks whether the URL hosts match.
     *
     * Used not to add a link of hostname B from page of host A.
     *
     * @param string $url_to_check URL to check.
     * @param string $current_url  Current URL, that will be checked against.
     *
     * @return bool Whether the URLs belong to the same host.
     */
    public static function is_url_host_allowed( $url_to_check, $current_url ) {
        $add_protocol_if_missing = function ( $url ) {
            if ( ! preg_match( '/^https?:\/\//', $url ) ) {
                $url = 'https://' . $url;
            }
            return $url;
        };

        $url_to_check = $add_protocol_if_missing( $url_to_check );
        $current_url  = $add_protocol_if_missing( $current_url );

        $url_to_check_host = wp_parse_url( $url_to_check, PHP_URL_HOST );

        if ( ! $url_to_check_host ) {
            return false;
        }

        /**
         * Strips "www." from the beginning of the host.
         *
         * @param string $host
         *
         * @return string URL without a "www." postfix.
         */
        $host_strip_www = function ( $host ) {
            return preg_replace( '/^www\./i', '', $host );
        };

        /**
         * Prepares the URL.
         *
         * Retrieves the host and strips "www." from the beginning of it.
         *
         * @param string $url
         *
         * @return string|false URL host without a "www." postfix, or false if failed to get the host.
         */
        $url_get_host_and_strip_www = function ( $url ) use ( $host_strip_www ) {
            $host = wp_parse_url( $url, PHP_URL_HOST );
            return $host ? $host_strip_www( $host ) : false;
        };

        /**
         * A list of hosts to check the current URL against.
         *
         * @var string|false[] $hosts_to_check_against
         */
        $hosts_to_check_against = [
            $url_get_host_and_strip_www( $current_url ),
        ];

        // WP-Rocket CDN support.
        if (
            in_array( 'wp-rocket/wp-rocket.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) &&
            function_exists( 'get_rocket_option' )
        ) {
            if ( get_rocket_option( 'cdn' ) ) {
                $rocket_cnames = get_rocket_option( 'cdn_cnames' );
                foreach ( $rocket_cnames as $cname ) {
                    if ( ! in_array( $cname, $hosts_to_check_against, true ) ) { // Unique.
                        $hosts_to_check_against[] = $host_strip_www( $cname );
                    }
                }
            }
        }

        return in_array( $host_strip_www( $url_to_check_host ), $hosts_to_check_against, true );
    }
}
