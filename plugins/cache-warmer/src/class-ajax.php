<?php
/**
 * AJAX handlers.
 *
 * @package Cache-Warmer
 */

namespace Cache_Warmer;

use Exception;
use phpUri;
use vipnytt\SitemapParser;
use vipnytt\SitemapParser\Exceptions\SitemapParserException;
use WP_Plugins_Core\Sanitize;
use WP_Query;

/**
 * Manages AJAX.
 */
final class AJAX {

    /**
     * Adds the menu and inits assets loading for it.
     */
    public function __construct() {
        $this->add_ajax_events();
    }

    /**
     * Loads AJAX handlers.
     */
    private function add_ajax_events() {
        $admin_ajax_events = [
            'save',
            'start_warm_up',
            'stop_warm_up',
            'get_log_content',
            'get_latest_warmup_data',
            'get_warmup_log_content',
            'delete_all_logs',
            'delete_unscheduled_logs',
            'delete_external_warmer_logs',
            'import_settings',
            'reset_settings',
            'get_debug_data',
            'insert_my_cookies',
            'reschedule_intervals',
        ];

        foreach ( $admin_ajax_events as $ajax_event ) {
            add_action( 'wp_ajax_cache_warmer_' . $ajax_event, [ $this, $ajax_event ], 10, 0 );
        }
    }

    /**
     * Save plugin settings.
     *
     * @throws Exception Exception.
     */
    public function save() {
        /*
         * Nonce check.
         */
        check_ajax_referer( 'cache-warmer-menu', 'nonceToken' );

        if ( array_key_exists( 'depth', $_REQUEST ) ) {
            $depth = max( (int) sanitize_text_field( wp_unslash( $_REQUEST['depth'] ) ), 0 );
            Cache_Warmer::$options->set( 'setting-depth', $depth );
        }

        if ( array_key_exists( 'speedLimit', $_REQUEST ) ) {
            $speed_limit = (int) sanitize_text_field( wp_unslash( $_REQUEST['speedLimit'] ) );
            if ( $speed_limit ) {
                Cache_Warmer::$options->set( 'setting-speed-limit', max( $speed_limit, 1 ) );
            } else {
                Cache_Warmer::$options->delete( 'setting-speed-limit' );
            }
        }

        if ( array_key_exists( 'cookies', $_REQUEST ) ) {
            $cookies = Sanitize::sanitize_array( (array) json_decode( wp_unslash( $_REQUEST['cookies'] ), true ) ); // @codingStandardsIgnoreLine
            Cache_Warmer::$options->set( 'setting-cookies', $cookies );
        }

        if ( array_key_exists( 'urlParams', $_REQUEST ) ) {
            $url_params = Sanitize::sanitize_array( (array) json_decode( wp_unslash( $_REQUEST['urlParams'] ), true ) ); // @codingStandardsIgnoreLine
            Cache_Warmer::$options->set( 'setting-url-params', $url_params );
        }

        if ( array_key_exists( 'requestHeaders', $_REQUEST ) ) {
            $request_headers = Sanitize::sanitize_array( (array) json_decode( wp_unslash( $_REQUEST['requestHeaders'] ), true ) ); // @codingStandardsIgnoreLine
            Cache_Warmer::$options->set( 'setting-request-headers', $request_headers );
        }

        if ( array_key_exists( 'entryPoints', $_REQUEST ) ) {
            $entry_points = Sanitize::sanitize_array( (array) json_decode( wp_unslash( $_REQUEST['entryPoints'] ), true ) ); // @codingStandardsIgnoreLine
            if ( $entry_points ) {
                Cache_Warmer::$options->set( 'setting-entry-points', $entry_points );
            } else {
                Cache_Warmer::$options->delete( 'setting-entry-points' );
            }
        }

        if ( array_key_exists( 'userAgents', $_REQUEST ) ) {
            $user_agents = Sanitize::sanitize_array( (array) json_decode( wp_unslash( $_REQUEST['userAgents'] ), true ) ); // @codingStandardsIgnoreLine
            if ( $user_agents ) {
                Cache_Warmer::$options->set( 'setting-user-agents', $user_agents );
            } else {
                Cache_Warmer::$options->delete( 'setting-user-agents' );
            }
        }

        if ( array_key_exists( 'timeout', $_REQUEST ) ) {
            $timeout = min( max( (int) sanitize_text_field( wp_unslash( $_REQUEST['timeout'] ) ), 1 ), CACHE_WARMER_MAX_REQUEST_TIMEOUT );
            Cache_Warmer::$options->set( 'setting-timeout', $timeout );
        }

        if ( array_key_exists( 'interval', $_REQUEST ) ) {
            $interval = max( (int) sanitize_text_field( wp_unslash( $_REQUEST['interval'] ) ), 0 );
            Cache_Warmer::$options->set( 'setting-interval', $interval );
        }

        if ( array_key_exists( 'visitSecondTimeWithoutUrlParams', $_REQUEST ) ) {
            $visit_second_time_without_url_params = sanitize_text_field( wp_unslash( $_REQUEST['visitSecondTimeWithoutUrlParams'] ) );
            Cache_Warmer::$options->set( 'setting-visit-second-time-without-url-params', $visit_second_time_without_url_params );
        }

        if ( array_key_exists( 'visitSecondTimeWithoutCookies', $_REQUEST ) ) {
            $visit_second_time_without_cookies = sanitize_text_field( wp_unslash( $_REQUEST['visitSecondTimeWithoutCookies'] ) );
            Cache_Warmer::$options->set( 'setting-visit-second-time-without-cookies', $visit_second_time_without_cookies );
        }

        if ( array_key_exists( 'useObjectCache', $_REQUEST ) ) {
            $use_object_cache = sanitize_text_field( wp_unslash( $_REQUEST['useObjectCache'] ) );
            Cache_Warmer::$options->set( 'setting-use-object-cache', $use_object_cache );
        }

        if ( array_key_exists( 'addThisSiteAllPublicPosts', $_REQUEST ) ) {
            $add_this_site_all_public_posts = sanitize_text_field( wp_unslash( $_REQUEST['addThisSiteAllPublicPosts'] ) );
            Cache_Warmer::$options->set( 'setting-add-this-site-all-public-posts', $add_this_site_all_public_posts );
        }

        if ( array_key_exists( 'warmUpPosts', $_REQUEST ) ) {
            $warm_up_posts = sanitize_text_field( wp_unslash( $_REQUEST['warmUpPosts'] ) );
            Cache_Warmer::$options->set( 'setting-warm-up-posts', $warm_up_posts );
        }

        if ( array_key_exists( 'rewriteToHTTPS', $_REQUEST ) ) {
            $rewrite_to_https = sanitize_text_field( wp_unslash( $_REQUEST['rewriteToHTTPS'] ) );
            Cache_Warmer::$options->set( 'setting-rewrite-to-https', $rewrite_to_https );
        }

        if ( array_key_exists( 'assetsPreloadingScripts', $_REQUEST ) ) {
            $assets_preloading_scripts = sanitize_text_field( wp_unslash( $_REQUEST['assetsPreloadingScripts'] ) );
            Cache_Warmer::$options->set( 'setting-assets-preloading-scripts', $assets_preloading_scripts );
        }

        if ( array_key_exists( 'assetsPreloadingStyles', $_REQUEST ) ) {
            $assets_preloading_styles = sanitize_text_field( wp_unslash( $_REQUEST['assetsPreloadingStyles'] ) );
            Cache_Warmer::$options->set( 'setting-assets-preloading-styles', $assets_preloading_styles );
        }

        if ( array_key_exists( 'assetsPreloadingFonts', $_REQUEST ) ) {
            $assets_preloading_fonts = sanitize_text_field( wp_unslash( $_REQUEST['assetsPreloadingFonts'] ) );
            Cache_Warmer::$options->set( 'setting-assets-preloading-fonts', $assets_preloading_fonts );
        }

        if ( array_key_exists( 'assetsPreloadingImages', $_REQUEST ) ) {
            $assets_preloading_images = sanitize_text_field( wp_unslash( $_REQUEST['assetsPreloadingImages'] ) );
            Cache_Warmer::$options->set( 'setting-assets-preloading-images', $assets_preloading_images );
        }

        if ( array_key_exists( 'excludedPagesUseRegexMatch', $_REQUEST ) ) {
            $excluded_pages_use_regex_match = sanitize_text_field( wp_unslash( $_REQUEST['excludedPagesUseRegexMatch'] ) );
            Cache_Warmer::$options->set( 'setting-excluded-pages-use-regex-match', $excluded_pages_use_regex_match );
        }

        if ( array_key_exists( 'postsWarmingEnqueueInterval', $_REQUEST ) ) {
            $posts_warming_enqueue_delay = max( (int) sanitize_text_field( wp_unslash( $_REQUEST['postsWarmingEnqueueInterval'] ) ), 1 );
            Cache_Warmer::$options->set( 'setting-posts-warming-enqueue-interval', $posts_warming_enqueue_delay );
        }

        if ( array_key_exists( 'externalWarmerSettings', $_REQUEST ) ) {
            $external_warmer_settings = json_decode( wp_unslash( $_REQUEST['externalWarmerSettings'] ), true );

            if ( $external_warmer_settings ) {
                as_unschedule_all_actions( External_Warmer::INTERVAL_HOOK_NAME );

                foreach ( $external_warmer_settings as $domain => $settings ) {

                    // Update license key.

                    $external_warmer_license_key_changed = false;

                    $external_warmer_license_key_initial = get_option( 'cache-warmer-setting-external-warmer-license-key' . $domain );
                    $external_warmer_license_key         = $settings['licenseKey'];

                    if ( $external_warmer_license_key !== $external_warmer_license_key_initial ) {
                        $external_warmer_license_key_changed = true;

                        update_option( 'cache-warmer-setting-external-warmer-license-key' . $domain, $external_warmer_license_key );
                    }

                    // Make a server request to get the data.

                    if ( $external_warmer_license_key_changed ) {
                        if ( $external_warmer_license_key ) {
                            $url = 'https://validate.cachewarmer.xyz';

                            $query_params = [
                                'domain' => $domain,
                                'key'    => $external_warmer_license_key,
                            ];

                            $body = wp_json_encode( $query_params, true );

                            $response = wp_remote_post(
                                $url,
                                [
                                    'headers' => [
                                        'Content-Type' => 'application/json',
                                    ],
                                    'body'    => $body,
                                    'timeout' => 15,
                                ]
                            );

                            update_option(
                                'cache-warmer-setting-external-warmer-key-validation-endpoint-last-response-code' . $domain,
                                wp_remote_retrieve_response_code( $response )
                            );

                            update_option(
                                'cache-warmer-setting-external-warmer-key-validation-endpoint-last-response-body' . $domain,
                                wp_remote_retrieve_body( $response )
                            );

                            // Reset the list of servers to use.
                            delete_option(
                                'cache-warmer-setting-external-warmer-servers-to-use' . $domain
                            );
                        } else {
                            delete_option(
                                'cache-warmer-setting-external-warmer-key-validation-endpoint-last-response-code' . $domain,
                            );

                            delete_option(
                                'cache-warmer-setting-external-warmer-key-validation-endpoint-last-response-body' . $domain,
                            );
                        }
                    }

                    // Update interval.

                    update_option(
                        'cache-warmer-setting-external-warmer-interval' . $domain,
                        max( (int) $settings['interval'], 0 )
                    );

                    // Update servers to use.

                    if ( isset( $settings['serversToUse'] ) ) {
                        update_option(
                            'cache-warmer-setting-external-warmer-servers-to-use' . $domain,
                            $settings['serversToUse']
                        );
                    } else {
                        delete_option( 'cache-warmer-setting-external-warmer-servers-to-use' . $domain );
                    }
                }

                // Reschedules external warmer intervals.
                External_Warmer::reschedule_intervals();
            }
        }

        if ( array_key_exists( 'addEntryPointSitesSitemaps', $_REQUEST ) ) {
            $add_entry_points_sitemaps = sanitize_text_field( wp_unslash( $_REQUEST['addEntryPointSitesSitemaps'] ) );
            Cache_Warmer::$options->set( 'setting-add-entry-point-sites-sitemaps', $add_entry_points_sitemaps );
        }

        if ( array_key_exists( 'forHowManyDaysToKeepTheLogs', $_REQUEST ) ) {
            $for_how_many_days_to_keep_the_logs = sanitize_text_field( wp_unslash( $_REQUEST['forHowManyDaysToKeepTheLogs'] ) );
            Cache_Warmer::$options->set( 'setting-for-how-many-days-to-keep-the-logs', $for_how_many_days_to_keep_the_logs );
        }

        if ( array_key_exists( 'useExternalWarmerServersDuringTheWarming', $_REQUEST ) ) {
            $use_external_warmer_servers_during_the_warming = sanitize_text_field( wp_unslash( $_REQUEST['useExternalWarmerServersDuringTheWarming'] ) );
            Cache_Warmer::$options->set( 'setting-use-external-warmer-servers-during-the-warming', $use_external_warmer_servers_during_the_warming );
        }

        if ( array_key_exists( 'excludedPages', $_REQUEST ) ) {
            $excluded_pages = Sanitize::sanitize_array( (array) json_decode( wp_unslash( $_REQUEST['excludedPages'] ), true ) ); // @codingStandardsIgnoreLine
            Cache_Warmer::$options->set( 'setting-excluded-pages', $excluded_pages );
        }

        if ( array_key_exists( 'excludePagesWithWarmedCanonical', $_REQUEST ) ) {
            $exclude_pages_with_warmed_canonical = sanitize_text_field( wp_unslash( $_REQUEST['excludePagesWithWarmedCanonical'] ) );
            Cache_Warmer::$options->set( 'setting-exclude-pages-with-warmed-canonical', $exclude_pages_with_warmed_canonical );
        }

        wp_send_json_success();
    }

    /**
     * Start warm up.
     *
     * @param bool       $check_for_nonce         Whether to do nonce check.
     * @param bool       $start_for_interval      Whether to start the warm-up for the interval (without additional checks).
     * @param bool|array $warm_up_for_unscheduled Whether to start the unscheduled warm-up (e.g. on posts modification) -
     *                                            if so, then the value is array of URLs, and false otherwise.
     *
     * @throws Exception Exception.
     */
    public static function start_warm_up( $check_for_nonce = true, $start_for_interval = false, $warm_up_for_unscheduled = false ) {
        if ( $check_for_nonce ) {
            /*
             * Nonce check.
             */
            check_ajax_referer( 'cache-warmer-menu', 'nonceToken' );
        }

        if (
            ( ! $warm_up_for_unscheduled && 'in-progress' !== Warm_Up::get_last_warm_up_state() ) ||
            $warm_up_for_unscheduled
        ) {
            $warm_up_start_date                   = ! $warm_up_for_unscheduled ? wp_date( 'Y-m-d H:i:s' ) : '0000-00-00 00:00:00';
            $depth                                = ! $warm_up_for_unscheduled ? (int) Cache_Warmer::$options->get( 'setting-depth' ) : 0;
            $speed_limit                          = (int) Cache_Warmer::$options->get( 'setting-speed-limit' );
            $url_params                           = Cache_Warmer::$options->get( 'setting-url-params' );
            $request_headers                      = Cache_Warmer::$options->get( 'setting-request-headers' );
            $user_agents                          = array_column( Cache_Warmer::$options->get( 'setting-user-agents' ), 'value' );
            $cookies_option                       = Cache_Warmer::$options->get( 'setting-cookies' );
            $cookies                              = array_combine( array_column( $cookies_option, 'name' ), array_column( $cookies_option, 'value' ) );
            $timeout                              = Cache_Warmer::$options->get( 'setting-timeout' );
            $visit_second_time_without_url_params = '1' === Cache_Warmer::$options->get( 'setting-visit-second-time-without-url-params' );
            $visit_second_time_without_cookies    = '1' === Cache_Warmer::$options->get( 'setting-visit-second-time-without-cookies' );
            $rewrite_to_https                     = '1' === Cache_Warmer::$options->get( 'setting-rewrite-to-https' );
            $assets_preloading_scripts            = '1' === Cache_Warmer::$options->get( 'setting-assets-preloading-scripts' );
            $assets_preloading_styles             = '1' === Cache_Warmer::$options->get( 'setting-assets-preloading-styles' );
            $assets_preloading_fonts              = '1' === Cache_Warmer::$options->get( 'setting-assets-preloading-fonts' );
            $assets_preloading_images             = '1' === Cache_Warmer::$options->get( 'setting-assets-preloading-images' );
            $add_entry_points_sites_sitemaps      = '1' === Cache_Warmer::$options->get( 'setting-add-entry-point-sites-sitemaps' );
            $add_this_site_all_public_posts       = '1' === Cache_Warmer::$options->get( 'setting-add-this-site-all-public-posts' );
            $excluded_pages                       = array_filter( Cache_Warmer::$options->get( 'cache-warmer-setting-excluded-pages' ) );
            $excluded_pages_use_regex_match       = '1' === Cache_Warmer::$options->get( 'setting-excluded-pages-use-regex-match' );
            $exclude_pages_with_warmed_canonical  = '1' === Cache_Warmer::$options->get( 'setting-exclude-pages-with-warmed-canonical' );
            $use_external_warmer                  = '1' === Cache_Warmer::$options->get( 'setting-use-external-warmer-servers-during-the-warming' );

            $failed_links_option_key = ! $warm_up_for_unscheduled ?
                'cache-warmer-failed-to-retrieve-links' :
                'cache-warmer-unscheduled-failed-to-retrieve-links';

            $retrieved_links_option_key = ! $warm_up_for_unscheduled ?
                'cache-warmer-retrieved-links' :
                'cache-warmer-unscheduled-retrieved-links';

            $leftovers_links_option_key = ! $warm_up_for_unscheduled ?
                'cache-warmer-links-tree-leftovers' :
                'cache-warmer-unscheduled-links-tree-leftovers';

            Cache_Warmer::$options->delete( $failed_links_option_key );
            Cache_Warmer::$options->delete( $retrieved_links_option_key );

            if ( ! $warm_up_for_unscheduled ) {
                Cache_Warmer::$options->delete( 'cache-warmer-last-warmup-was-stopped-by-hand' );
            }

            Logging::create_a_warm_up( $warm_up_start_date );

            if ( ! $warm_up_for_unscheduled ) {

                $home_url = home_url();

                $links              = [];
                $entry_points_links = [];

                // Add entry points.

                $entry_points = Cache_Warmer::$options->get( 'setting-entry-points' );
                foreach ( $entry_points as $entry_point ) {
                    $entry_points_links[] = phpUri::parse( $home_url )->join( $entry_point['url'] ); // Convert relative URL to absolute.
                }

                // Add sitemaps for entry points.

                if ( extension_loaded( 'simplexml' ) ) {
                    if ( $add_entry_points_sites_sitemaps ) {
                        $sitemap_sites =
                            array_unique(
                                array_filter(
                                    array_map(
                                        function( $link ) {
                                            $url_parts = wp_parse_url( $link );

                                            if ( ! $url_parts ) {
                                                return false;
                                            }

                                            return trailingslashit(
                                                $url_parts['scheme'] . '://' . $url_parts['host'] .
                                                ( array_key_exists( 'port', $url_parts ) ? ':' . $url_parts['port'] : '' )
                                            );
                                        },
                                        $entry_points_links
                                    )
                                )
                            );

                        foreach ( $sitemap_sites as $sitemap_site ) {
                            try {
                                $parser = new SitemapParser( $user_agents[0] );
                                $parser->parse( $sitemap_site . 'robots.txt' );

                                $sitemaps = array_unique( array_keys( $parser->getSitemaps() ) );

                                if ( $sitemaps ) {
                                    foreach ( $sitemaps as $sitemap ) {
                                        $links[] = $sitemap;
                                    }
                                } else { // If no sitemap in robots.txt found, then just assume that it's located at sitemap.xml.
                                    $links[] = $sitemap_site . 'sitemap.xml';
                                }
                            } catch ( SitemapParserException $e ) {
                                $links[] = $sitemap_site . 'sitemap.xml';
                            }
                        }
                    }
                }

                // Add entry point links to the links list (at the end, as sitemaps and posts are higher prio).

                if ( $add_this_site_all_public_posts ) {

                    // Add posts.

                    $args  = [
                        'post_type'      => 'any',
                        'post_status'    => 'publish',
                        'posts_per_page' => -1,
                        'perm'           => 'readable',
                    ];
                    $posts = get_posts( $args );
                    foreach ( $posts as $post ) {
                        $links[] = get_permalink( $post );
                    }

                    // Add pagination for blog posts.

                    $args        = [
                        'post_type'   => 'post',
                        'post_status' => 'publish',
                    ];
                    $posts_query = new WP_Query( $args );

                    $pagination_format = get_option( 'permalink_structure' ) ? '/page/%d/' : '&paged=%d';

                    $blog_page = get_option( 'page_for_posts' );
                    if ( $blog_page ) {
                        // Get the blog page link.
                        $blog_link = get_permalink( $blog_page );

                        if ( $blog_link ) {
                            // Add Pagination for blog page.
                            $total_pages = $posts_query->max_num_pages;

                            for ( $i = 2; $i <= $total_pages; $i ++ ) {
                                $links[] = untrailingslashit( $blog_link ) . sprintf( $pagination_format, $i );
                            }
                        }
                    }

                    // Post types archives.

                    foreach ( get_post_types() as $post_type ) {
                        if ( is_post_type_viewable( $post_type ) ) {
                            $link = get_post_type_archive_link( $post_type );
                            if ( $link ) {
                                $links[] = $link;
                            }
                        }
                    }

                    // Add term links.

                    if ( function_exists( 'wc_get_attribute_taxonomies' ) ) {
                        $wc_public_attributes = array_map(
                            function( $x ) {
                                return 'pa_' . $x;
                            },
                            array_keys( array_filter( wp_list_pluck( wc_get_attribute_taxonomies(), 'attribute_public', 'attribute_name' ) ) )
                        );
                    }

                    foreach ( get_taxonomies() as $taxonomy ) {
                        if (
                            is_taxonomy_viewable( $taxonomy ) &&
                            (
                                ! function_exists( 'wc_get_attribute_taxonomies' ) ||
                                ! str_starts_with( $taxonomy, 'pa_' ) ||
                                (
                                    isset( $wc_public_attributes ) &&
                                    in_array( $taxonomy, $wc_public_attributes, true )
                                )
                            )
                        ) {
                            $terms = get_terms(
                                $taxonomy,
                                [
                                    'hide_empty' => false,
                                ]
                            );
                            foreach ( $terms as $term ) {
                                $term_link = get_term_link( $term );
                                if ( ! is_wp_error( $term_link ) ) {
                                    $links[] = $term_link;

                                    // Determine posts per page based on taxonomy type.
                                    if (
                                        (
                                            function_exists( 'wc_get_attribute_taxonomies' ) && str_starts_with( $taxonomy, 'pa_' ) ||
                                            str_starts_with( $taxonomy, 'product_' )
                                        ) &&
                                        function_exists( 'wc_get_default_products_per_row' )
                                    ) {
                                        $posts_per_page = (int) apply_filters(
                                            'loop_shop_per_page',
                                            wc_get_default_products_per_row() * wc_get_default_product_rows_per_page()
                                        );
                                    } else {
                                        $posts_per_page = (int) get_option( 'posts_per_page' );
                                    }

                                    // Get the total number of posts in the term.
                                    $total_posts = $term->count;

                                    // Calculate total pages.
                                    $total_pages = ceil( $total_posts / $posts_per_page );

                                    // Get links for each page.
                                    for ( $i = 2; $i <= $total_pages; $i ++ ) {
                                        $links[] = untrailingslashit( $term_link ) . sprintf( $pagination_format, $i );
                                    }
                                }
                            }
                        }
                    }
                }

                foreach ( $entry_points_links as $entry_points_link ) {
                    $links[] = $entry_points_link;
                }
            } else {
                $links = $warm_up_for_unscheduled;
            }

            // Filter out the links without absolute path.
            $links = array_unique(
                array_filter(
                    array_map(
                        [
                            'Cache_Warmer\Url_Formatting',
                            'convert_a_url_to_absolute',
                        ],
                        $links
                    )
                )
            );

            // Filter out the links based on "Excluded pages" setting on the early stage.
            $links = array_filter(
                $links,
                function( $link ) use ( $excluded_pages, $excluded_pages_use_regex_match, $url_params, $rewrite_to_https ) {
                    $link = Url_Formatting::add_url_params( $link, $url_params ); // Add URL params, to have a parity with the later stage filtering.

                    if ( $rewrite_to_https ) {
                        $link = Url_Formatting::rewrite_url_to_https( $link );
                    }

                    foreach ( $excluded_pages as $potential_excluded_match ) {
                        if (
                            $excluded_pages_use_regex_match && preg_match( '@' . str_replace( '@', '\@', $potential_excluded_match ) . '@', $link ) ||
                            ! $excluded_pages_use_regex_match && str_contains( $link, $potential_excluded_match )
                        ) {
                            return false;
                        }
                    }
                    return true;
                }
            );

            $tree = Leaf_Only_Subtree::get(
                $links,
                $url_params,
                $visit_second_time_without_url_params,
                $visit_second_time_without_cookies,
                $rewrite_to_https,
                $user_agents
            );

            /*
             * Merge the new tree with the previous one for the unscheduled warm-up.
             */
            if ( $warm_up_for_unscheduled ) {
                $previous_tree_data = Cache_Warmer::$options->get( $leftovers_links_option_key );
                if ( ! isset( $previous_tree_data['tree'] ) ) {
                    $previous_tree_data['tree'] = [];
                }
                $tree = Utils::array_merge_recursive_ex( $previous_tree_data['tree'], $tree );
            }

            Cache_Warmer::$options->set(
                $leftovers_links_option_key,
                [
                    'tree' => $tree,
                    'meta' => [ // So the settings changes will not affect the currently running warm-up.
                        'depth'                           => $depth,
                        'assets_preloading'               => [
                            'scripts' => $assets_preloading_scripts,
                            'styles'  => $assets_preloading_styles,
                            'fonts'   => $assets_preloading_fonts,
                            'images'  => $assets_preloading_images,
                        ],
                        'speed_limit'                     => $speed_limit,
                        'url_params'                      => $url_params,
                        'request_headers'                 => $request_headers,
                        'cookies'                         => $cookies,
                        'timeout'                         => $timeout,
                        'visit_second_time_without_custom_url_params' => $visit_second_time_without_url_params,
                        'visit_second_time_without_cookies' => $visit_second_time_without_cookies,
                        'rewrite_to_https'                => $rewrite_to_https,
                        'start_date'                      => $warm_up_start_date,
                        'failed_warm_ups_counter'         => Warm_Up::action_scheduler_get_failed_warm_ups_counter(),
                        'add_entry_points_sites_sitemaps' => $add_entry_points_sites_sitemaps,
                        'add_this_site_all_public_posts'  => $add_this_site_all_public_posts,
                        'excluded_pages'                  => $excluded_pages,
                        'excluded_pages_use_regex_match'  => $excluded_pages_use_regex_match,
                        'exclude_pages_with_warmed_canonical' => $exclude_pages_with_warmed_canonical,
                        'user_agents'                     => $user_agents,
                        'visited_links'                   => [],
                        'external_warmup_request_args'    => [],
                        'use_external_warmer'             => $use_external_warmer,
                    ],
                ]
            );

            Warm_Up::schedule_async_warmup();

            if ( ! $start_for_interval ) {
                $output = [];

                $output = array_merge(
                    $output,
                    Debug::maybe_get_debug_array()
                );

                if ( $check_for_nonce ) {
                    wp_send_json_success( $output ); // Debug data.
                }
            }
        } elseif ( ! $start_for_interval ) {
            if ( $check_for_nonce ) {
                wp_send_json_error( __( 'There is already an active warm-up... Please wait until it finishes.', 'cache-warmer' ) );
            }
        }
    }

    /**
     * Stop warm up.
     *
     * @param bool $check_for_nonce Whether to do nonce check.
     *
     * @throws Exception Exception.
     */
    public static function stop_warm_up( $check_for_nonce = true ) {
        if ( $check_for_nonce ) {
            /*
             * Nonce check.
             */
            check_ajax_referer( 'cache-warmer-menu', 'nonceToken' );
        }

        $data = Cache_Warmer::$options->get( 'cache-warmer-links-tree-leftovers' );

        if ( ! isset( $data['meta'] ) ) {
            return;
        }

        $meta = $data['meta'];

        Cache_Warmer::$options->set( 'last-stopped-by-hand-warmup-id', $meta['start_date'] );

        if (
            isset( $meta['external_warmup_request_args'] ) &&
            $meta['external_warmup_request_args'] &&
            '0000-00-00 00:00:00' !== $meta['start_date'] // Not for unscheduled.
        ) {
            Cache_Warmer::$options->set( 'last-external-warmup-request-args-id', $meta['start_date'] );
            Cache_Warmer::$options->set( 'last-success-warmup-external-warmup-request-args', $meta['external_warmup_request_args'] );
        }

        Warm_Up::stop_current_warmup();

        // Whether the last warmup was stopped by hand.
        Cache_Warmer::$options->set( 'cache-warmer-last-warmup-was-stopped-by-hand', true );

        if ( $check_for_nonce ) {
            wp_send_json_success();
        }
    }

    /**
     * Gets the latest warm-up log data.
     *
     * @throws Exception Exception.
     */
    public static function get_latest_warmup_data() {
        /*
         * Nonce check.
         */
        check_ajax_referer( 'cache-warmer-menu', 'nonceToken' );

        if ( array_key_exists( 'page', $_REQUEST ) ) {
            $page = (int) sanitize_text_field( wp_unslash( $_REQUEST['page'] ) );
        } else {
            $page = 1;
        }

        $output = [
            'logContent'      => Logging::format_log_content_array_into_string(
                Logging::get_latest_warmed_at(),
                Logging::get_latest_log_content( $page ),
                $page
            ),
            'lastWarmUpState' => Warm_Up::get_last_warm_up_state(),

        ];

        $output = array_merge(
            $output,
            Debug::maybe_get_debug_array()
        );

        wp_send_json_success( $output );
    }

    /**
     * Gets the latest warm up log content.
     */
    public static function delete_all_logs() {
        /*
         * Nonce check.
         */
        check_ajax_referer( 'cache-warmer-menu', 'nonceToken' );

        DB::truncate_tables();

        wp_send_json_success();
    }

    /**
     * Deletes unscheduled logs.
     */
    public static function delete_unscheduled_logs() {
        /*
         * Nonce check.
         */
        check_ajax_referer( 'cache-warmer-menu', 'nonceToken' );

        global $wpdb;

        $table_name = DB::get_tables_prefix() . 'warm_ups_list';
        $query      = "DELETE FROM $table_name WHERE warmed_at=0";
        $wpdb->query( $query ); // @codingStandardsIgnoreLine

        wp_send_json_success();
    }

    /**
     * Deletes external warmer logs.
     */
    public static function delete_external_warmer_logs() {
        /*
         * Nonce check.
         */
        check_ajax_referer( 'cache-warmer-menu', 'nonceToken' );

        global $wpdb;

        $table_name = DB::get_tables_prefix() . 'warm_ups_list';
        $query      = "DELETE FROM $table_name WHERE warmed_at='2000-01-01 00:00:00'";
        $wpdb->query( $query ); // @codingStandardsIgnoreLine

        update_option( 'cache-warmer-last-delete-external-warmer-logs', time() );

        wp_send_json_success();
    }

    /**
     * Returns log content.
     */
    public function get_log_content() {
        /*
         * Nonce check.
         */
        check_ajax_referer( 'cache-warmer-menu', 'nonceToken' );

        if ( array_key_exists( 'page', $_REQUEST ) ) {
            $page = (int) sanitize_text_field( wp_unslash( $_REQUEST['page'] ) );
        } else {
            $page = 1;
        }

        if ( array_key_exists( 'logName', $_REQUEST ) ) {
            $log_name = sanitize_text_field( wp_unslash( $_REQUEST['logName'] ) );

            if ( 'unscheduled' === $log_name ) {
                $log_name = '0000-00-00 00:00:00';
            } elseif ( 'external-warmer' === $log_name ) {
                $log_name = '2000-01-01 00:00:00';
            } elseif ( 'latest' === $log_name ) {
                $log_name = Logging::get_latest_warmed_at();
            }

            wp_send_json_success( [ 'content' => Logging::format_log_content_array_into_string( $log_name, Logging::get_log_content( $log_name, $page ), $page ) ] );
        }

        wp_send_json_error();
    }

    /**
     * Returns debug data.
     *
     * @throws Exception Exception.
     */
    public static function get_debug_data() {
        /*
         * Nonce check.
         */
        check_ajax_referer( 'cache-warmer-menu', 'nonceToken' );

        $debug_env              = $_ENV['CACHE_WARMER_DEBUG'] ?? '';
        $debug_each_request_env = $_ENV['CACHE_WARMER_DEBUG_EACH_REQUEST'] ?? '';
        $debug_object           = print_r( Debug::get_debug_array(), true ); // @codingStandardsIgnoreLine

        $html = '
            <div class="cache-warmer-debug-data-container">
                <h3>Main debug object</h3>
                <pre class="text-align-left cache-warmer-debug-block">' . $debug_object . '</pre>
                <h3>ENVs</h3>
                <pre>CACHE_WARMER_DEBUG: ' . $debug_env . '</pre>
                <pre>CACHE_WARMER_DEBUG_EACH_REQUEST: ' . $debug_each_request_env . '</pre>
            </div>
        ';

        wp_send_json( [ 'html' => $html ] );
    }

    /**
     * Imports settings from the file.
     *
     * Accepts: $_REQUEST['file']
     *
     * @throws Exception Exception.
     */
    public function import_settings() {
        /*
         * Nonce check.
         */
        check_ajax_referer( 'cache-warmer-menu', 'nonceToken' );

        if ( array_key_exists( 'file', $_REQUEST ) ) {
            $settings = Sanitize::sanitize_array( (array) json_decode( base64_decode( $_REQUEST['file'] ), true ) ); // @codingStandardsIgnoreLine
            foreach ( $settings as $option_name => $option_value ) {
                // Some simple security check, so only the option that start from "cache-warmer-" could be updated.
                if ( preg_match( '@^cache-warmer-setting-@', $option_name ) ) {
                    Cache_Warmer::$options->set( $option_name, $option_value );
                }
            }

            // Reschedules external warmer intervals.
            External_Warmer::reschedule_intervals();
        }

        wp_send_json_success();
    }

    /**
     * Resets all plugin settings.
     *
     * @throws Exception Exception.
     */
    public function reset_settings() {
        /*
         * Nonce check.
         */
        check_ajax_referer( 'cache-warmer-menu', 'nonceToken' );

        // Delete all settings options.
        foreach ( Cache_Warmer::$options->settings_options as $option ) {
            Cache_Warmer::$options->delete( $option );
        }

        // Set them to defaults to trigger all event listeners.
        foreach ( Cache_Warmer::$options->settings_options as $option ) {
            $option_data = Cache_Warmer::$options->all_options[ $option ];
            if ( array_key_exists( 'default', $option_data ) ) {
                Cache_Warmer::$options->set( $option, $option_data['default'] );
            }
        }

        // Reschedules external warmer intervals.
        External_Warmer::reschedule_intervals();

        wp_send_json_success();
    }

    /**
     * Inserts user's cookies.
     *
     * @throws Exception Exception.
     */
    public function insert_my_cookies() {
        /*
         * Nonce check.
         */
        check_ajax_referer( 'cache-warmer-menu', 'nonceToken' );

        $cookies = Cache_Warmer::$options->get( 'setting-cookies' );

        $cookies_to_add = [];
        foreach ( $_COOKIE as $cookie_name => $cookie_value ) {
            $cookies_to_add[] = [
                'name'  => $cookie_name,
                'value' => $cookie_value,
            ];
        }

        Cache_Warmer::$options->set( 'setting-cookies', array_merge( $cookies, $cookies_to_add ) );

        wp_send_json_success();
    }

    /**
     * Reschedules intervals.
     */
    public function reschedule_intervals() {
        /*
         * Nonce check.
         */
        check_ajax_referer( 'cache-warmer-menu', 'nonceToken' );

        Intervals_Scheduler::fix_missing_intervals();

        wp_send_json_success();
    }
}
