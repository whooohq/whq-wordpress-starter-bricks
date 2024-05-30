<?php
/**
 * A class to detect IP address of the server.
 *
 * @package Cache-Warmer
 */

namespace Cache_Warmer;

/**
 * Class Server_IP_Detection.
 */
final class Server_IP_Detection {

    /**
     * Which request parameter to use.
     */
    const REQUEST_PARAM = 'cache_warmer_get_ip_address';

    /**
     * Name of nonce token.
     */
    const NONCE_TOKEN_NAME = self::REQUEST_PARAM;

    /**
     * Constructor.
     */
    public function __construct() {
        if ( // Echo the visitor IP.
            isset( $_REQUEST[ self::REQUEST_PARAM ] )
        ) {
            $this->send_no_cache_headers();
            die( esc_html( $this->get_visitor_ip() ) );
        }
    }

    /**
     * Sends "no-cache" headers.
     */
    private function send_no_cache_headers() {
        header( 'Cache-Control: no-cache, no-store, must-revalidate' ); // HTTP 1.1.
        header( 'Pragma: no-cache' ); // HTTP 1.0.
        header( 'Expires: 0' ); // Proxies.
    }

    /**
     * Returns the visitor IP.
     *
     * Supports CloudFlare.
     *
     * @return string IP or empty string if IP can't be determined.
     *
     * Inspired by @see \WC_Geolocation::get_ip_address
     */
    private function get_visitor_ip() {
        if ( isset( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) { // CloudFlare.
            return sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ) );
        } elseif ( isset( $_SERVER['HTTP_X_REAL_IP'] ) ) {
            return sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_REAL_IP'] ) );
        } elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) { // Forwarded.
            // Proxy servers can send through this header like this: X-Forwarded-For: client1, proxy1, proxy2
            // Make sure we always only send through the first IP in the list which should always be the client IP.
            return (string) rest_is_ip_address( trim( current( preg_split( '/,/', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) ) ) ) );
        } elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
            return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
        } else {
            return '';
        }
    }

    /**
     * Get the IP of the current server.
     *
     * Request itself, and let the above methods to output the IP that connected to it.
     */
    public static function get_current_server_ip() {
        $response = wp_remote_post(
            home_url(),
            [
                'body'    => [
                    self::REQUEST_PARAM => true,
                ],
                'timeout' => 30,
            ]
        );

        $response_body = wp_remote_retrieve_body( $response );

        if (
            ! is_wp_error( $response ) &&
            200 === wp_remote_retrieve_response_code( $response ) &&
            $response_body
        ) {
            return $response_body;
        } else { // A fallback.
            return gethostbyname( gethostname() );
        }
    }
}
