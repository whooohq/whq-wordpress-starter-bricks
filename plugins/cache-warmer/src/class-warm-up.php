<?php
/**
 * Warm_Up handlers.
 *
 * @package Cache-Warmer
 */

namespace Cache_Warmer;

use ActionScheduler_DBStore;
use ActionScheduler_Store;
use Automattic\WooCommerce\Blocks\Utils\MiniCartUtils;
use Exception;
use WP_Error;

/**
 * Handles Warm Up.
 */
final class Warm_Up {

    /**
     * Batch size: number of pages per one script execution.
     */
    const BATCH_SIZE = 1;

    /**
     * Failed links option key.
     *
     * @var string
     */
    private static $failed_links_option_key;

    /**
     * Retrieved links option key.
     *
     * @var string
     */
    private static $retrieved_links_option_key;

    /**
     * Leftover links option key.
     *
     * @var string
     */
    private static $leftovers_links_option_key;

    /**
     * Returns page HTML.
     *
     * @param string $url        URL.
     * @param int    $timeout    Timeout (seconds).
     * @param string $user_agent User-Agent.
     * @param array  $cookies    Cookies.
     * @param array  $request_headers Request headers.
     *
     * @return array|WP_Error The response or WP_Error on failure.
     */
    public static function request_a_page( string $url, int $timeout, string $user_agent, array $cookies, array $request_headers ) {
        return wp_remote_get(
            $url,
            [
                'timeout'    => $timeout,
                'user-agent' => $user_agent,
                'cookies'    => $cookies,
                'headers'    => array_column( $request_headers, 'value', 'name' ),
            ]
        );
    }

    /**
     * Updates failed to retrieve links.
     *
     * @param string $link  Link.
     * @param int    $depth The depth of the link.
     * @param array  $link_meta Link meta.
     *
     * @throws Exception Exception.
     */
    public static function update_failed_to_retrieve_links( $link, $depth, array $link_meta ) {
        $links = Cache_Warmer::$options->get( self::$failed_links_option_key );

        $link_value_with_meta = Url_Formatting::untrailingslash_a_url( $link ) . '|' . maybe_serialize( $link_meta );

        if (
            ! array_key_exists( $link_value_with_meta, $links ) ||
            $depth < $links[ $link_value_with_meta ] // New depth is less.
        ) {
            $links[ $link_value_with_meta ] = $depth;
            Cache_Warmer::$options->set( self::$failed_links_option_key, $links );
        }
    }

    /**
     * Updates failed to retrieve links.
     *
     * @param string $link  Link.
     * @param int    $depth The depth of the link.
     * @param array  $link_meta Link meta.
     *
     * @throws Exception Exception.
     */
    public static function update_retrieved_links( $link, $depth, array $link_meta ) {
        $links = Cache_Warmer::$options->get( self::$retrieved_links_option_key );

        $link_value_with_meta = Url_Formatting::untrailingslash_a_url( $link ) . '|' . maybe_serialize( $link_meta );

        if (
            ! array_key_exists( $link_value_with_meta, $links ) ||
            $depth < $links[ $link_value_with_meta ] // New depth is less.
        ) {
            $links[ $link_value_with_meta ] = $depth;
            Cache_Warmer::$options->set( self::$retrieved_links_option_key, $links );
        }
    }

    /**
     * Returns the phase (for logging).
     *
     * @param array $meta Meta.
     *
     * @return int The Phase. 0 - in warm-up tree. 1 - in failed to retrieve tree.
     */
    public static function get_phase( $meta ) {
        if ( ! array_key_exists( 'failed_to_retrieve_tree', $meta ) ) {
            return 0;
        } else {
            return 1;
        }
    }

    /**
     * Handles the page retrieval by link and modifies the tree.
     *
     * @param string $link  Link.
     * @param int    $depth The depth of the link.
     * @param array  $tree  The current tree.
     * @param array  $meta  Current warm up meta data (settings).
     * @param array  $link_meta Link metadata.
     *
     * @throws Exception Exception.
     */
    public static function handle_a_link(
        string $link,
        int $depth,
        array &$tree,
        array &$meta,
        array $link_meta
    ) {
        $max_depth                            = (int) $meta['depth'];
        $warm_up_start_date                   = $meta['start_date'];
        $url_params                           = $meta['url_params'];
        $timeout                              = (int) $meta['timeout'];
        $cookies                              = $meta['cookies'];
        $visit_second_time_without_url_params = $meta['visit_second_time_without_custom_url_params'];
        $visit_second_time_without_cookies    = isset( $meta['visit_second_time_without_cookies'] ) &&
                                                $meta['visit_second_time_without_cookies'];
        $rewrite_to_https                     = $meta['rewrite_to_https'];
        $assets_preloading                    = $meta['assets_preloading'];
        $speed_limit                          = &$meta['speed_limit'];
        $excluded_pages                       = $meta['excluded_pages'] ?? [];
        $excluded_pages_use_regex_match       = isset( $meta['excluded_pages_use_regex_match'] ) &&
                                                $meta['excluded_pages_use_regex_match'];
        $exclude_pages_with_warmed_canonical  = isset( $meta['exclude_pages_with_warmed_canonical'] ) &&
                                                $meta['exclude_pages_with_warmed_canonical'];
        $user_agents                          = $meta['user_agents'];
        $use_external_warmer                  = $meta['use_external_warmer'];

        $user_agent = $link_meta['user_agent'];

        $visit_without_cookies =
            isset( $link_meta['visit_without_cookies'] ) &&
            $link_meta['visit_without_cookies'];

        if ( $visit_without_cookies ) {
            $cookies = [];
        }

        if ( ! isset( $meta['add_entry_points_sites_sitemaps'] ) ) {
            $meta['add_entry_points_sites_sitemaps'] = '';
        }
        $add_entry_points_sites_sitemaps = $meta['add_entry_points_sites_sitemaps'];

        if ( ! isset( $meta['request_headers'] ) ) {
            $meta['request_headers'] = '';
        }
        $request_headers = $meta['request_headers'];

        // Start.

        Cache_Warmer::$options->set( 'last-processing-link', $link );

        /*
         * When this link was already retrieved on a lower depth, delete it.
         */

        $retrieved_links      = Cache_Warmer::$options->get( self::$retrieved_links_option_key );
        $link_value_with_meta = Url_Formatting::untrailingslash_a_url( $link ) . '|' . maybe_serialize( $link_meta );

        if (
            array_key_exists( $link_value_with_meta, $retrieved_links ) &&
            $depth >= $retrieved_links[ $link_value_with_meta ]
        ) {
            Tree::delete_the_first_leaf( $tree );
            return;
        }

        $excluded_match = false;
        foreach ( $excluded_pages as $potential_excluded_match ) {
            /*
             * Check if $potential_excluded_match starts with a quantifier, and if it does, remove the first character.
             * The pattern ^[\*\+\?\{\}] matches any string that starts with a *, +, ?, {, or }
             *
             * To avoid regex issues.
             */
            if ( $excluded_pages_use_regex_match && preg_match( '/^[\*\+\?\{\}]/', $potential_excluded_match ) ) {
                $potential_excluded_match = substr( $potential_excluded_match, 1 );
            }

            if (
                $excluded_pages_use_regex_match && preg_match( '@' . str_replace( '@', '\@', $potential_excluded_match ) . '@', $link ) ||
                ! $excluded_pages_use_regex_match && str_contains( $link, $potential_excluded_match )
            ) {
                $excluded_match = $potential_excluded_match;
                break;
            }
        }

        if ( $excluded_match ) { // When the URL matches the excluded list.
            Logging::log_failure(
                $link,
                $warm_up_start_date,
                $depth,
                null,
                sprintf(
                    /* translators: %1$s is URL, second %2$s is the exclusion match (just a string). */
                    __( 'The URL matches the <a href="%1$s" target="_blank">excluded page setting</a> <b>"%2$s"</b>. Skipping.' ),
                    admin_url( 'admin.php?page=cache-warmer-settings&plugin-selected-tab=excluded-pages' ),
                    $excluded_match
                ),
                2,
                $user_agent
            );

            Tree::delete_the_first_leaf( $tree );
            return;
        }

        $time_before_fetch = microtime( true );
        $response          = self::request_a_page( $link, $timeout, $user_agent, $cookies, $request_headers );
        $fetch_time        = round( microtime( true ) - $time_before_fetch, 2 );

        $response_code = wp_remote_retrieve_response_code( $response );

        if (
            is_wp_error( $response ) ||
            $response_code >= 400
        ) {
            Cache_Warmer::$options->set( 'last-failed-to-retrieve-link', $link );

            if ( $response_code >= 400 ) {
                /* translators: %d is server response code. */
                $custom_error = sprintf( __( 'Server response code is %d.' ), $response_code ) . '<br><br>';

                if ( in_array( $response_code, [ 403, 502, 504 ], true ) ) {
                    $custom_error .=
                        'cloudflare' === ( is_array( $header_val = wp_remote_retrieve_header( $response, 'server' ) ) ? reset( $header_val ) : $header_val ) ? // @codingStandardsIgnoreLine
                            sprintf(
                                /* translators: %1$s is IP, second %2$s is URL. */
                                __( 'The cache warmer was blocked by Cloudflare. Please whitelist your server IP (%1$s) to your Cloudflare IP Access rules with Allow setting: <a href="%2$s">IP Access rules Cloudflare Web Application Firewall (WAF) docs</a>', 'cache-warmer' ),
                                Server_IP_Detection::get_current_server_ip(),
                                'https://developers.cloudflare.com/waf/tools/ip-access-rules/'
                            ) :
                            sprintf(
                                /* translators: %s is IP. */
                                __( 'The cache warmer was blocked, please check your firewall settings that IP (%s) is not blocked', 'cache-warmer' ),
                                Server_IP_Detection::get_current_server_ip()
                            );
                } elseif (

                    /*
                     * Slow-down a bit.
                     *
                     * For the first time, x2; for the second time, x8. For the third time, pause warming for an hour.
                     */
                    in_array( $response_code, [ 429, 500 ], true )
                ) {
                    /*
                     * Base crawl speed used in the future as the reference for all speed limits for this warming.
                     *
                     * Because if I re-calculate it the second time after crawl speed was limited at least once,
                     * Then it will be lower than the real crawl speed. So only the first 'base_crawl_speed' is true (unaffected by any limits).
                     *
                     * And therefore shall be used as a reference.
                     */
                    if ( ! isset( $meta['base_crawl_speed'] ) ) {
                        $meta['base_crawl_speed'] = Summary::get_warm_up_speed( $warm_up_start_date );
                    }

                    if ( ! isset( $meta['initial_speed_limit'] ) ) {
                        $meta['initial_speed_limit'] = $speed_limit;
                    }

                    if ( ! isset( $meta['speed_limit_divider'] ) ) {
                        $meta['speed_limit_divider'] = 1;
                    }

                    $speed_limit_divider = &$meta['speed_limit_divider'];

                    if ( $speed_limit_divider < 8 ) {
                        $speed_limit_divider             = 1 === $speed_limit_divider ? 2 : 8; // First time - 2, second time - 8.
                        $meta['limit_crawl_speed_up_to'] = time() + MINUTE_IN_SECONDS * 15;

                        $speed_limit = max(
                            round( $meta['base_crawl_speed'] / $speed_limit_divider ),
                            1
                        );

                        $custom_error .=
                            sprintf(
                                /* translators: %d is the crawl rate limit. */
                                __( 'Crawl rate reduced to %d pages per minute for 15 minutes.', 'cache-warmer' ),
                                $speed_limit
                            );
                    } else {
                        unset( $meta['speed_limit_divider'] );
                        unset( $meta['limit_crawl_speed_up_to'] );

                        $speed_limit = $meta['initial_speed_limit']; // Unset the speed limit to the default (with which warm-up was created).

                        $custom_error .=
                            sprintf(
                                __( 'Warming paused for 60 minutes.', 'cache-warmer' ),
                                $speed_limit
                            );

                        $meta['pause_warming_up_to'] = time() + HOUR_IN_SECONDS;
                    }
                }
            }

            self::update_failed_to_retrieve_links( $link, $depth, $link_meta );
            Logging::log_failure(
                $link,
                $warm_up_start_date,
                $depth,
                $fetch_time ? $fetch_time : null,
                $custom_error ?? wp_json_encode( $response ),
                self::get_phase( $meta ),
                $user_agent
            );
            Tree::delete_the_first_leaf( $tree );
        } else {
            /**
             * External warmer logic.
             */

            $external_warmer_results = [];

            $domain = wp_parse_url( $link, PHP_URL_HOST );

            if ( $domain ) {
                $external_warmer_request_args = [
                    [
                        $link,
                    ],
                    External_Warmer::get_request_headers(
                        $user_agent,
                        $cookies,
                        $request_headers
                    ),
                ];

                // Add to "external_warmup_request_args" - for interval-based external warmer warmings.

                if ( ! isset( $meta['external_warmup_request_args'][ $domain ] ) ) {
                    $meta['external_warmup_request_args'][ $domain ] = [];
                }
                $meta['external_warmup_request_args'][ $domain ][] = $external_warmer_request_args;

                // Warm the link from the external warmer servers.

                if ( $use_external_warmer ) {
                    $external_warmer_license_key = get_option(
                        'cache-warmer-setting-external-warmer-license-key' . $domain
                    );

                    $external_warmer_last_response_code = (int) get_option(
                        'cache-warmer-setting-external-warmer-key-validation-endpoint-last-response-code' . $domain
                    );

                    $servers_to_use = get_option(
                        'cache-warmer-setting-external-warmer-servers-to-use' . $domain
                    );

                    if (
                        $external_warmer_license_key &&
                        200 === $external_warmer_last_response_code &&
                        $servers_to_use
                    ) {
                        // Visit with the external warmer, and prepare the log array.

                        $external_warmer_response = External_Warmer::warm_the_link( ... $external_warmer_request_args );

                        foreach ( $external_warmer_response as $value ) {
                            $time   = 0;
                            $status = '';

                            if ( isset( $value['body'] ) ) {
                                $body = json_decode( $value['body'], true );

                                $time   = round( $body[0]['time'], 2 );
                                $status = $body[0]['status'];
                            }

                            $external_warmer_results[] = [
                                'server' => $value['server_code'],
                                'code'   => $value['code'],
                                'time'   => $time,
                                'at'     => $value['time'],
                                'status' => $status,
                            ];
                        }
                    }
                }
            }

            // The standard logic.

            Cache_Warmer::$options->set( 'last-retrieved-link', $link );

            $content_type_header = explode( ';', is_array( $header_val = wp_remote_retrieve_header( $response, 'content-type' ) ) ? reset( $header_val ) : $header_val )[0]; // @codingStandardsIgnoreLine

            /*
             * When CF cache is present, we record to the:
             *
             * Before:
             *
             * MISS, EXPIRED, BYPASS, DYNAMIC
             *
             * After:
             *
             * HIT, STALE, UPDATING
             *
             * If CF cache is not preset, we make a second req to figure out the afterwards time.
             */

            $headers_with_types_classification = [
                'cf-cache-status' => [
                    'before' => [
                        'MISS',
                        'EXPIRED',
                        'BYPASS',
                        'DYNAMIC',
                    ],
                    'after'  => [
                        'HIT',
                        'STALE',
                        'UPDATING',
                    ],
                ],
                'x-cache'         => [
                    'before' => [
                        'MISS',
                        'PASS',
                        'ERROR',
                    ],
                    'after'  => [
                        'HIT',
                        'GRACED',
                        'RefreshHit',
                    ],
                ],
            ];

            foreach ( $headers_with_types_classification as $header => $classifications ) {
                $header_val = is_array( $header_val = wp_remote_retrieve_header( $response, $header ) ) ? reset( $header_val ) : $header_val; // @codingStandardsIgnoreLine

                if ( $header_val ) {
                    foreach ( $classifications['before'] as $before_val ) {
                        if ( 0 === stripos( $header_val, $before_val ) ) {
                            $visit_type = 'before';
                            break 2;
                        }
                    }
                    foreach ( $classifications['after'] as $after_val ) {
                        if ( 0 === stripos( $header_val, $after_val ) ) {
                            $visit_type = 'after';
                            break 2;
                        }
                    }
                }
            }

            if ( ! isset( $visit_type ) ) { // Get afterwards fetch type when no typing headers are present.
                if ( // When $visit_second_time_without_url_params is enabled, we don't check afterwards fetch time for the first query.
                    $url_params && $visit_second_time_without_url_params &&
                    isset( wp_parse_url( $link )['query'] )
                ) {
                    $afterwards_fetch_time = null;
                } else {
                    $time_before_afterwards_fetch = microtime( true );
                    self::request_a_page( $link, $timeout, $user_agent, $cookies, $request_headers );
                    $afterwards_fetch_time = round( microtime( true ) - $time_before_afterwards_fetch, 2 );
                }
            }

            self::update_retrieved_links(
                $link,
                $depth,
                $link_meta
            );

            $response_body = wp_remote_retrieve_body( $response );

            /*
             * Add content-types data (pages, styles, scripts, sitemaps, etc.).
             */

            $page_content_types = [
                '', // No header.
                'text/html',
            ];

            $style_content_types = [
                'text/css',
            ];

            $sitemap_content_types = [
                'text/xml',
                'application/xml',
            ];

            /**
             * Detect if the page has a canonical.
             */

            $has_a_canonical = false;
            $canonical       = null;

            if ( $response_body && in_array( $content_type_header, $page_content_types, true ) ) {
                $canonical = Content_Parsing::get_canonical_from_html( $response_body );

                if ( $canonical ) {
                    $canonical = Url_Formatting::convert_a_url_to_absolute( $canonical, $link );
                    if ( $canonical ) {
                        // Add URL params, to have a parity with the later stage filtering.
                        $canonical = Url_Formatting::add_url_params( $canonical, $url_params );

                        if ( $rewrite_to_https ) {
                            $canonical = Url_Formatting::rewrite_url_to_https( $canonical );
                        }

                        if (
                            Url_Formatting::untrailingslash_a_url( $canonical ) !== Url_Formatting::untrailingslash_a_url( $link ) &&
                            Url_Validation::is_url_host_allowed( $canonical, $link ) // Do not add external hosts.
                        ) {
                            $has_a_canonical = true;
                        }
                    }
                }
            }

            /**
             * Log success.
             */

            Logging::log_success(
                $link,
                $warm_up_start_date,
                $depth,
                $fetch_time ? $fetch_time : null,
                $afterwards_fetch_time ?? null,
                $response_code,
                self::get_phase( $meta ),
                $user_agent,
                $external_warmer_results,
                $content_type_header,
                is_array( $header_val = wp_remote_retrieve_header( $response, 'content-length' ) ) ? reset( $header_val ) : $header_val, // @codingStandardsIgnoreLine
                is_array( $header_val = wp_remote_retrieve_header( $response, 'cf-cache-status' ) ) ? reset( $header_val ) : $header_val, // @codingStandardsIgnoreLine
                is_array( $header_val = wp_remote_retrieve_header( $response, 'X-WP-Super-Cache' ) ) ? reset( $header_val ) : $header_val, // @codingStandardsIgnoreLine
                is_array( $header_val = wp_remote_retrieve_header( $response, 'x-cache' ) ) ? reset( $header_val ) : $header_val, // @codingStandardsIgnoreLine
                $visit_type ?? '',
                $has_a_canonical ? $canonical : '',
            );

            $content_types_to_parse_content_for = array_merge( $page_content_types, $sitemap_content_types );

            if ( $assets_preloading['styles'] ) {
                $content_types_to_parse_content_for = array_merge( $content_types_to_parse_content_for, $style_content_types );
            }

            if ( $response_body && in_array( $content_type_header, $style_content_types, true ) ) {
                Tree::add_the_first_leaf_siblings(
                    $tree,
                    Leaf_Only_Subtree::get_fonts_and_images_from_stylesheet_content(
                        $response_body,
                        $link,
                        $rewrite_to_https,
                        $assets_preloading,
                        $user_agents
                    )
                );
            }

            if (
                $response_body &&
                in_array( $content_type_header, $content_types_to_parse_content_for, true ) &&
                $depth < $max_depth
            ) {
                if ( in_array( $content_type_header, $page_content_types, true ) ) {

                    // Add the leaf children from the page content.

                    Tree::add_the_first_leaf_children(
                        $tree,
                        array_merge(
                            Leaf_Only_Subtree::get_pages_links_from_html(
                                $response_body,
                                $link,
                                $url_params,
                                $visit_second_time_without_url_params,
                                $visit_second_time_without_cookies,
                                $rewrite_to_https,
                                $user_agents
                            ),
                            Leaf_Only_Subtree::get_assets_links_from_html(
                                $response_body,
                                $link,
                                $rewrite_to_https,
                                $assets_preloading,
                                $user_agents
                            ),
                            $add_entry_points_sites_sitemaps ?
                                Leaf_Only_Subtree::get_sitemap_links_from_html(
                                    $response_body,
                                    $link,
                                    $rewrite_to_https,
                                    $user_agents
                                ) :
                                []
                        )
                    );
                } elseif ( in_array( $content_type_header, $style_content_types, true ) ) {
                    Tree::add_the_first_leaf_children(
                        $tree,
                        Leaf_Only_Subtree::get_stylesheets_links_from_stylesheet_content(
                            $response_body,
                            $link,
                            $rewrite_to_https,
                            $assets_preloading,
                            $user_agents
                        )
                    );
                } elseif ( in_array( $content_type_header, $sitemap_content_types, true ) ) {
                    Tree::add_the_first_leaf_children(
                        $tree,
                        Leaf_Only_Subtree::get_children_from_sitemap(
                            $response_body,
                            $link,
                            $user_agent,
                            $url_params,
                            $visit_second_time_without_url_params,
                            $visit_second_time_without_cookies,
                            $rewrite_to_https,
                            $user_agents
                        )
                    );
                }
            } else { // Max depth reached.
                Tree::delete_the_first_leaf( $tree );
            }

            /*
             * Add page canonical to the list of warmed pages (if "exclude pages with warmed canonical" setting is on).
             *
             * Otherwise, add the canonical page, to warm it after the current one.
             */

            if ( $has_a_canonical ) {
                if ( $exclude_pages_with_warmed_canonical ) {
                    self::update_retrieved_links(
                        Url_Formatting::add_url_params( $canonical, $url_params ),
                        $depth,
                        $link_meta
                    );
                } else {
                    /*
                     * Add the canonical page, to warm it also, after the current one, on the same depth.
                     */
                    Tree::add_the_first_leaf_siblings(
                        $tree,
                        Leaf_Only_Subtree::get(
                            [
                                $canonical,
                            ],
                            $url_params,
                            $visit_second_time_without_url_params,
                            $visit_second_time_without_cookies,
                            $rewrite_to_https,
                            $user_agents
                        ),
                        false,
                        $depth
                    );
                }
            }
        }
    }

    /**
     * Creates failed to retrieve links fake tree.
     *
     * @param array $failed_to_retrieve_links Failed to retrieve links.
     *
     * @return array Fake tree for failed to retrieve links where depth's parents are already retrieved links (to skip them).
     *
     * @throws Exception Exception.
     */
    public static function create_failed_to_retrieve_links_fake_tree( $failed_to_retrieve_links ) {
        $retrieved_links  = Cache_Warmer::$options->get( self::$retrieved_links_option_key );
        $placeholder_link = array_key_first( $retrieved_links );

        $tree = [];
        Tree::make_a_tree_of_arbitrary_depth( $tree, $placeholder_link, max( $failed_to_retrieve_links ), $failed_to_retrieve_links );

        return $tree;
    }

    /**
     * Returns the count of how many fetches were done for the warmup, for the last minute.
     *
     * @param array $visited_links The array of visited links.
     *
     * @return int Count.
     */
    public static function how_many_fetches_were_done_for_the_warmup( array &$visited_links ) {
        $current_time = time();

        // Remove timestamps older than 60 seconds.
        $visited_links = array_filter(
            $visited_links,
            function ( $timestamp ) use ( $current_time ) {
                return ( $current_time - $timestamp ) <= 60;
            }
        );

        // Add the current timestamp.
        $visited_links[] = $current_time;

        // Return the count of the visited links.
        return count( $visited_links );
    }

    /**
     * Processes the links.
     *
     * @throws Exception Exception.
     */
    public static function process() {
        $warm_up_for_unscheduled = false;
        $data                    = Cache_Warmer::$options->get( 'cache-warmer-links-tree-leftovers' );

        if ( ! $data ) {
            $warm_up_for_unscheduled = true;
            $data                    = Cache_Warmer::$options->get( 'cache-warmer-unscheduled-links-tree-leftovers' );
        }

        if ( // When no data, then do not warm.
            ! isset( $data['meta'] ) ||
            ! isset( $data['tree'] )
        ) {
            return;
        }

        $tree = $data['tree'];
        $meta = $data['meta'];

        if ( ! array_key_exists( 'speed_limit', $meta ) ) {
            $meta['speed_limit'] = 1000;
        }

        $speed_limit = &$meta['speed_limit'];

        if ( ! array_key_exists( 'visited_links', $meta ) ) {
            $meta['visited_links'] = [];
        }

        $visited_links = &$meta['visited_links'];

        self::$failed_links_option_key = ! $warm_up_for_unscheduled ?
            'cache-warmer-failed-to-retrieve-links' :
            'cache-warmer-unscheduled-failed-to-retrieve-links';

        self::$retrieved_links_option_key = ! $warm_up_for_unscheduled ?
            'cache-warmer-retrieved-links' :
            'cache-warmer-unscheduled-retrieved-links';

        self::$leftovers_links_option_key = ! $warm_up_for_unscheduled ?
            'cache-warmer-links-tree-leftovers' :
            'cache-warmer-unscheduled-links-tree-leftovers';

        if ( isset( $meta['limit_crawl_speed_up_to'] ) && time() > $meta['limit_crawl_speed_up_to'] ) { // When crawl speed limit is lifted off.
            unset( $meta['speed_limit_divider'] );
            unset( $meta['limit_crawl_speed_up_to'] );

            $speed_limit = $meta['initial_speed_limit'];
        }

        if ( ! isset( $meta['external_warmup_request_args'] ) ) {
            $meta['external_warmup_request_args'] = [];
        }

        $i = 0;
        while (
            $tree && // Iterates through the tree until all links are retrieved.
            ++ $i <= self::BATCH_SIZE && // Or until batch size is hit.
            // Or until the current speed is too fast and over the limit.
            ( $how_many_fetches_were_done = self::how_many_fetches_were_done_for_the_warmup( $visited_links ) ) < $speed_limit // @codingStandardsIgnoreLine
        ) {
            $first_link_data = Tree::get_the_first_leaf_data( $tree );

            self::handle_a_link(
                $first_link_data['link'],
                $first_link_data['depth'],
                $tree,
                $meta,
                $first_link_data['meta']
            );

            if ( isset( $meta['pause_warming_up_to'] ) ) {
                break;
            }

            // Don't fetch more pages when warm-up was stopped by hand.
            if ( ! $warm_up_for_unscheduled && Cache_Warmer::$options->get( 'cache-warmer-last-warmup-was-stopped-by-hand' ) ) {
                Cache_Warmer::$options->set( 'last-stopped-by-hand-warmup-id', $meta['start_date'] );

                // Update last success warmup meta (for external interval warming).

                if (
                    isset( $meta['external_warmup_request_args'] ) &&
                    $meta['external_warmup_request_args'] &&
                    '0000-00-00 00:00:00' !== $meta['start_date'] // Not for unscheduled.
                ) {
                    Cache_Warmer::$options->set( 'last-external-warmup-request-args-id', $meta['start_date'] );
                    Cache_Warmer::$options->set( 'last-success-warmup-external-warmup-request-args', $meta['external_warmup_request_args'] );
                }

                die();
            }
        }

        if ( isset( $meta['pause_warming_up_to'] ) ) {
            $pause_warming_until = $meta['pause_warming_up_to'];
            unset( $meta['pause_warming_up_to'] );
        }

        if (
            $tree || // If anything in the tree is left, but the current run is over the batch limit or speed.
            isset( $pause_warming_until ) // Or to just pause the warming for some period of time.
        ) {
            Cache_Warmer::$options->set(
                self::$leftovers_links_option_key,
                [
                    'tree' => $tree,
                    'meta' => $meta,
                ]
            );

            if ( isset( $pause_warming_until ) ) {
                self::schedule_async_warmup( $pause_warming_until );
            } elseif (
                // If the fetch is over the speed limit, then delay the next warm-up action to run by 10 seconds (+ pending time so will be about 60s).
                $how_many_fetches_were_done >= $speed_limit
            ) {
                self::schedule_async_warmup( time() + 10 );
            } else { // Otherwise, the batch limit is hit. Then start the next run immediately.
                self::schedule_async_warmup();
            }
        } else { // All links in the tree are visited.
            $failed_to_retrieve_links = Cache_Warmer::$options->get( self::$failed_links_option_key );

            // If failed to retrieve links were not handled yet, then do it using the standard tree's iteration.
            if (
                $failed_to_retrieve_links &&
                ! array_key_exists( 'failed_to_retrieve_tree', $meta )
            ) {
                Cache_Warmer::$options->set(
                    self::$leftovers_links_option_key,
                    [
                        'tree' => self::create_failed_to_retrieve_links_fake_tree( $failed_to_retrieve_links ),
                        'meta' => array_merge( $meta, [ 'failed_to_retrieve_tree' => true ] ),
                    ]
                );

                self::schedule_async_warmup();
            } else {
                Cache_Warmer::$options->delete( self::$leftovers_links_option_key );

                if (
                    ( $warm_up_for_unscheduled && Cache_Warmer::$options->get( 'cache-warmer-links-tree-leftovers' ) ) || // Scheduled tree.
                    ( ! $warm_up_for_unscheduled && Cache_Warmer::$options->get( 'cache-warmer-unscheduled-links-tree-leftovers' ) ) // Unscheduled tree.
                ) {
                    self::schedule_async_warmup(); // Schedule another warm-up if anything is left in the other tree.
                }

                // Update last success warmup meta (for external interval warming).

                Cache_Warmer::$options->set( 'last-success-warmup-id', $meta['start_date'] );

                // Success warmup links tree.
                if (
                    isset( $meta['external_warmup_request_args'] ) &&
                    $meta['external_warmup_request_args'] &&
                    '0000-00-00 00:00:00' !== $meta['start_date'] // Not for unscheduled.
                ) {
                    Cache_Warmer::$options->set( 'last-external-warmup-request-args-id', $meta['start_date'] );
                    Cache_Warmer::$options->set( 'last-success-warmup-external-warmup-request-args', $meta['external_warmup_request_args'] );
                }
            }
        }
    }

    /**
     * Schedules an immediate action with Action Scheduler to run in the background.
     *
     * @param int $timestamp Timestamp when the next action to run. If no timestamp, run immediately.
     */
    public static function schedule_async_warmup( $timestamp = false ) {
        if (
            ! as_get_scheduled_actions(
                [
                    'hook'   => Cache_Warmer::HOOK_NAME,
                    'status' => ActionScheduler_Store::STATUS_PENDING,
                ]
            )
        ) {
            if ( $timestamp ) {
                as_schedule_single_action( $timestamp, Cache_Warmer::HOOK_NAME );
            } else {
                as_enqueue_async_action( Cache_Warmer::HOOK_NAME );
            }
        }
    }

    /**
     * Stops the currently running scheduled warm-up.
     *
     * @throws Exception Exception.
     */
    public static function stop_current_warmup() {
        as_unschedule_all_actions( Cache_Warmer::HOOK_NAME );
        Cache_Warmer::$options->delete( 'cache-warmer-links-tree-leftovers' );

        // Deletes running actions.

        $running_actions = as_get_scheduled_actions(
            [
                'hook'     => Cache_Warmer::HOOK_NAME,
                'status'   => ActionScheduler_Store::STATUS_RUNNING,
                'per_page' => -1,
            ],
            'ids'
        );
        if ( $running_actions ) {
            $as = new ActionScheduler_DBStore();
            foreach ( $running_actions as $action_id ) {
                $as->delete_action( $action_id );
            }
        }
    }

    /**
     * Returns failed warm-ups counter.
     *
     * @return int Failed warm-ups counter.
     */
    public static function action_scheduler_get_failed_warm_ups_counter() {
        return count(
            as_get_scheduled_actions(
                [
                    'hook'     => Cache_Warmer::HOOK_NAME,
                    'status'   => ActionScheduler_Store::STATUS_FAILED,
                    'per_page' => -1,
                ]
            )
        );
    }

    /**
     * Returns last warm-up state.
     *
     * @return string Last warm up state: in-progress, failed, complete.
     *
     * @throws Exception Exception.
     */
    public static function get_last_warm_up_state() {
        $last_warmup_data = Cache_Warmer::$options->get( 'cache-warmer-links-tree-leftovers' );

        $is_not_finished = (bool) $last_warmup_data;
        $is_failed       = $last_warmup_data && self::action_scheduler_get_failed_warm_ups_counter() > (int) $last_warmup_data['meta']['failed_warm_ups_counter'];

        if ( $is_failed ) {
            $state = 'failed';
        } elseif ( $is_not_finished ) {
            $state = 'in-progress';
        } else {
            $state = 'complete';
        }

        // There is some undiscovered edge-case bug when the unfinished (with the leftovers tree) warmup stops.
        // This thing covers it.
        if (
            $last_warmup_data &&
            ! as_has_scheduled_action( Cache_Warmer::HOOK_NAME )
        ) {
            self::schedule_async_warmup();
        }

        return $state;
    }
}
