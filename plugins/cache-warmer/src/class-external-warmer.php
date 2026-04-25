<?php
/**
 * A class for external warmer management.
 *
 * @package Cache-Warmer
 */

namespace Cache_Warmer;

/**
 * Class External_Warmer.
 */
final class External_Warmer {

    /**
     * External warmer warm-up ID (for the logs).
     */
    const WARMUP_ID = '2000-01-01';

    /**
     * Hook name for external warmer handling interval.
     */
    const INTERVAL_HOOK_NAME = 'cache_warmer_process_external_warmer';

    /**
     * Hook name for warming of URLs chunk.
     */
    const WARM_POSTS_CHUNK_ACTION_NAME = 'cache_warmer_warm_urls_chunk';

    /**
     * Constructor.
     */
    public function __construct() {

        // Unschedule interval action on plugin deactivation.
        register_deactivation_hook( CACHE_WARMER_FILE, [ __CLASS__, 'unschedule' ] );

        // Creates external warmer post chunks.
        add_action( self::INTERVAL_HOOK_NAME, [ $this, 'create_external_warmer_post_chunks' ] );

        // Warm post chunks.
        add_action( self::WARM_POSTS_CHUNK_ACTION_NAME, [ $this, 'warm_posts_chunk' ] );
    }

    /**
     * Creates external warmer post chunks.
     *
     * @param string $domain A domain.
     */
    public function create_external_warmer_post_chunks( string $domain ) {
        $last_success_warmup_request_args = Cache_Warmer::$options->get( 'last-success-warmup-external-warmup-request-args' );

        if ( ! $last_success_warmup_request_args || ! isset( $last_success_warmup_request_args[ $domain ] ) ) {
            return;
        }

        $arranged_by_headers = []; // Arrange by headers.

        foreach ( $last_success_warmup_request_args[ $domain ] as $request_args ) {
            $urls    = $request_args[0];
            $headers = $request_args[1];

            $headers_str = wp_json_encode( $headers );

            if ( ! isset( $arranged_by_headers[ $headers_str ] ) ) {
                $arranged_by_headers[ $headers_str ] = [];
            }

            $arranged_by_headers[ $headers_str ][] = $urls;
        }

        foreach ( $arranged_by_headers as $headers_str => $urls ) {
            $urls = array_column( $urls, 0 );

            $headers = json_decode( $headers_str, true );
            $chunks  = array_chunk( $urls, 10 );

            foreach ( $chunks as $chunk_urls ) {
                as_enqueue_async_action(
                    self::WARM_POSTS_CHUNK_ACTION_NAME,
                    [
                        [
                            'headers' => $headers,
                            'urls'    => $chunk_urls,
                        ],
                    ]
                );
            }
        }
    }

    /**
     * Warm posts chunk action name.
     *
     * @param array $args Arguments.
     */
    public function warm_posts_chunk( $args ) {
        $urls        = $args['urls'];
        $req_headers = $args['headers'];

        $external_warmer_response = self::warm_the_link( $urls, $req_headers );

        $req_headers_assoc = [];
        foreach ( $req_headers as $header ) {
            $parts = explode( ': ', $header, 2 );
            if ( count( $parts ) === 2 ) {
                $req_headers_assoc[ strtolower( $parts[0] ) ] = $parts[1];
            }
        }

        foreach ( $external_warmer_response as $value ) {
            $code        = $value['code'];
            $server_code = $value['server_code'];

            if ( 200 === $code ) {
                $body = json_decode( $value['body'], true );

                foreach ( $body as $i => $url_visit ) {
                    $url = $urls[ $i ];

                    $status = $url_visit['status'];
                    $time   = $url_visit['time'] ?? 0;

                    $headers       = [];
                    $response_code = '';

                    foreach ( $url_visit['headers'] as $header ) {
                        if ( strpos( $header, 'HTTP/' ) === 0 ) {
                            $parts = explode( ' ', $header );
                            if ( isset( $parts[1] ) ) {
                                $response_code = $parts[1];
                            }
                        } else {
                            $parts = explode( ': ', $header, 2 );
                            if ( count( $parts ) === 2 ) {
                                $headers[ strtolower( $parts[0] ) ] = $parts[1];
                            }
                        }
                    }

                    if ( 'processed' === $status ) {
                        $content_type_header = explode( ';', is_array( $header_val = $headers['content-type'] ?? '' ) ? reset( $header_val ) : $header_val )[0]; // @codingStandardsIgnoreLine

                        Logging::log_success(
                            $server_code . ' ' . $url,
                            self::WARMUP_ID,
                            0,
                            round( $time, 2 ),
                            null,
                            $response_code,
                            0,
                            $req_headers_assoc['user-agent'] ?? '',
                            [],
                            $content_type_header,
                            is_array( $header_val = $headers['content-length'] ?? '' ) ? reset( $header_val ) : $header_val, // @codingStandardsIgnoreLine
                            is_array( $header_val = $headers['cf-cache-status'] ?? '' ) ? reset( $header_val ) : $header_val, // @codingStandardsIgnoreLine
                            is_array( $header_val = $headers['x-wp-super-cache'] ?? '' ) ? reset( $header_val ) : $header_val, // @codingStandardsIgnoreLine
                            is_array( $header_val = $headers['x-cache'] ?? '' ) ? reset( $header_val ) : $header_val, // @codingStandardsIgnoreLine
                        );
                    } else {
                        Logging::log_failure(
                            $server_code . ' ' . $url,
                            self::WARMUP_ID,
                            0,
                            round( $time, 2 ),
                            sprintf(
                                /* translators: %s is external server status. */
                                __( 'External server status: %s', 'cache-warmer' ),
                                $status
                            ),
                            0,
                            $req_headers['User-Agent'] ?? ''
                        );
                    }
                }
            } else {
                foreach ( $urls as  $url ) {
                    Logging::log_failure(
                        $server_code . ' ' . $url,
                        self::WARMUP_ID,
                        0,
                        null,
                        sprintf(
                            /* translators: %s is server response code. */
                            __( 'Server response code: %d', 'cache-warmer' ),
                            $code
                        ),
                        0,
                        $req_headers_assoc['user-agent'] ?? '',
                    );
                }
            }
        }
    }

    /**
     * Returns the list of servers to use for external warmer.
     *
     * @param string $domain A domain.
     *
     * @return array
     */
    private static function get_servers_to_use( string $domain ) {
        $license_key        = get_option( 'cache-warmer-setting-external-warmer-license-key' . $domain );
        $last_response_code = (int) get_option( 'cache-warmer-setting-external-warmer-key-validation-endpoint-last-response-code' . $domain );

        if ( $license_key && 200 === $last_response_code ) {
            return get_option( 'cache-warmer-setting-external-warmer-servers-to-use' . $domain, [] );
        } else {
            return [];
        }
    }

    /**
     * Returns request headers.
     *
     * @param string $user_agent      User-Agent.
     * @param array  $cookies         Cookies.
     * @param array  $request_headers Request headers.
     *
     * @return array Request headers.
     */
    public static function get_request_headers( string $user_agent, array $cookies, array $request_headers ): array {
        $prepared_headers = [
            'User-Agent' => $user_agent,
        ];

        // Cookies.

        if ( ! empty( $cookies ) ) {
            $cookie_header = [];
            foreach ( $cookies as $name => $value ) {
                $cookie_header[] = $name . '=' . $value;
            }
            $prepared_headers['Cookie'] = implode( '; ', $cookie_header );
        }

        // Request headers.

        $request_headers = array_column( $request_headers, 'value', 'name' );
        foreach ( $request_headers as $header_name => $header_value ) {
            $prepared_headers[ $header_name ] = $header_value;
        }

        // Convert headers to array of strings.
        return array_map(
            function( $name, $value ) {
                return sprintf( '%s: %s', $name, str_replace( ':', '\:', $value ) ); // Escape.
            },
            array_keys( $prepared_headers ),
            $prepared_headers
        );
    }

    /**
     * Warms the URL(s), using the external warmer.
     *
     * @param string[] $urls_of_the_same_domain URLs to warm (all of them should belong to the same domain).
     * @param string[] $request_headers         Request headers.
     *
     * @return array Response data by server.
     */
    public static function warm_the_link( array $urls_of_the_same_domain, array $request_headers ): array {
        if ( ! $urls_of_the_same_domain ) {
            return [];
        }

        $domain = wp_parse_url( $urls_of_the_same_domain[0], PHP_URL_HOST );

        if ( ! $domain ) {
            return [];
        }

        // An assumption is that all of them belong to the same domain.

        $external_warmer_license_key = get_option( 'cache-warmer-setting-external-warmer-license-key' . $domain );

        if ( ! $external_warmer_license_key ) {
            return [];
        }

        $response_by_server = [];

        foreach ( self::get_servers_to_use( $domain ) as $server ) {

            // Make a request.
            $response = wp_remote_post(
                $server,
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                    'body'    => wp_json_encode(
                        [
                            'domain'  => $domain,
                            'key'     => $external_warmer_license_key,
                            'urls'    => $urls_of_the_same_domain,
                            'headers' => $request_headers,
                        ]
                    ),
                    'timeout' => 30,
                ]
            );

            $response_by_server[ $server ] = [
                'server_code' => self::get_server_part_by_server_full_url( $server ),
                'code'        => wp_remote_retrieve_response_code( $response ),
                'body'        => wp_remote_retrieve_body( $response ),
                'time'        => time(),
            ];
        }

        return $response_by_server;
    }

    /**
     * Returns server part by server full URL.
     *
     * E.g. for input 'https://us2.cachewarmer.xyz/' will return 'us2'.
     *
     * @param string $server_url Server full URLs.
     *
     * @return string Server part.
     */
    private static function get_server_part_by_server_full_url( string $server_url ) {
        $parsed_url = wp_parse_url( $server_url );
        $host       = $parsed_url['host'];

        $host_parts = explode( '.', $host );

        return $host_parts[0];
    }

    /**
     * Schedules the interval action if it isn't scheduled.
     */
    public static function schedule_for_all_domains() {
        foreach ( Utils::get_unique_domains_from_entry_points() as $unique_domain ) {
            $license_key    = get_option( 'cache-warmer-setting-external-warmer-license-key' . $unique_domain );
            $response_code  = (int) get_option( 'cache-warmer-setting-external-warmer-key-validation-endpoint-last-response-code' . $unique_domain );
            $servers_to_use = get_option( 'cache-warmer-setting-external-warmer-servers-to-use' . $unique_domain );
            $interval       = get_option( 'cache-warmer-setting-external-warmer-interval' . $unique_domain ) * 60 * 60;

            if (
                $license_key &&
                200 === $response_code &&
                $servers_to_use &&
                $interval
            ) {
                Utils::schedule_the_undrifting_interval(
                    $interval,
                    self::INTERVAL_HOOK_NAME,
                    [
                        $unique_domain,
                    ]
                );
            }
        }
    }

    /**
     * Plugin deactivation handler.
     */
    public static function unschedule() {
        as_unschedule_all_actions( self::INTERVAL_HOOK_NAME );
        as_unschedule_all_actions( self::WARM_POSTS_CHUNK_ACTION_NAME );
    }

    /**
     * Reschedules intervals.
     */
    public static function reschedule_intervals() {
        as_unschedule_all_actions( self::INTERVAL_HOOK_NAME );
        self::schedule_for_all_domains();
    }
}
