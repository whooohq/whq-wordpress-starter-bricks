<?php
/**
 * A class for links subtree creation.
 *
 * Also processes tree nodes.
 *
 * The name emphasizes that the subtree part will have only leafs.
 *
 * Like:
 *
 * a -> b1 -> c1
 *         -> c2
 *   -> b2
 *
 * So here "b1" is leaf-only subtree.
 *
 * @package Cache-Warmer
 */

namespace Cache_Warmer;

use Exception;

/**
 * Leaf_Only_Subtree class.
 */
final class Leaf_Only_Subtree {

    /**
     * Returns pages links from HTML.
     *
     * @param string $html                                 HTML.
     * @param string $current_url                          Current page, which should be added before the relative link.
     * @param array  $url_params                           URL params.
     * @param bool   $visit_second_time_without_url_params Visit second time without URL params (if they are set).
     * @param bool   $visit_second_time_without_cookies    Visit second time without cookies (if they are set).
     * @param bool   $rewrite_to_https                     Rewrites HTTP links to HTTPS.
     * @param array  $user_agents                          The list of user agents, to visit each page with.
     *
     * @return array Correctly formatted links for the tree considering current settings (passed params).
     *
     * @throws Exception Exception.
     */
    public static function get_pages_links_from_html(
        $html,
        $current_url,
        $url_params,
        $visit_second_time_without_url_params,
        $visit_second_time_without_cookies,
        $rewrite_to_https,
        array $user_agents
    ) {
        return self::get(
            Content_Parsing::get_pages_links_from_html( $html, $current_url ),
            $url_params,
            $visit_second_time_without_url_params,
            $visit_second_time_without_cookies,
            $rewrite_to_https,
            $user_agents
        );
    }

    /**
     * Returns assets links from HTML.
     *
     * @param string $html              HTML.
     * @param string $current_url       Current page, which should be added before the relative link.
     * @param bool   $rewrite_to_https  Rewrites HTTP links to HTTPS.
     * @param array  $assets_preloading Assets preloading.
     * @param array  $user_agents       The list of user agents, to visit each page with.
     *
     * @return array Correctly formatted links for the tree considering current settings (passed params).
     *
     * @throws Exception Exception.
     */
    public static function get_assets_links_from_html(
        $html,
        $current_url,
        $rewrite_to_https,
        $assets_preloading,
        array $user_agents
    ) {
        return self::get(
            Content_Parsing::get_assets_links_from_html( $html, $current_url, $assets_preloading ),
            [],
            false,
            false,
            $rewrite_to_https,
            $user_agents
        );
    }

    /**
     * Returns fonts links from stylesheet content.
     *
     * @param string $stylesheet_content Stylesheet content.
     * @param string $current_url        Current page, which should be added before the relative link.
     * @param bool   $rewrite_to_https   Rewrites HTTP links to HTTPS.
     * @param array  $assets_preloading  Assets preloading.
     * @param array  $user_agents        The list of user agents, to visit each page with.
     *
     * @return array Correctly formatted links for the tree considering current settings (passed params).
     *
     * @throws Exception Exception.
     */
    public static function get_fonts_and_images_from_stylesheet_content(
        $stylesheet_content,
        $current_url,
        $rewrite_to_https,
        $assets_preloading,
        array $user_agents
    ) {
        return self::get(
            Content_Parsing::get_fonts_and_images_from_stylesheet_content( $stylesheet_content, $current_url, $assets_preloading ),
            [],
            false,
            false,
            $rewrite_to_https,
            $user_agents
        );
    }

    /**
     * Returns fonts links from stylesheet content.
     *
     * @param string $stylesheet_content Stylesheet content.
     * @param string $current_url        Current page, which should be added before the relative link.
     * @param bool   $rewrite_to_https   Rewrites HTTP links to HTTPS.
     * @param array  $assets_preloading  Assets preloading.
     * @param array  $user_agents        The list of user agents, to visit each page with.
     *
     * @return array Correctly formatted links for the tree considering current settings (passed params).
     *
     * @throws Exception Exception.
     */
    public static function get_stylesheets_links_from_stylesheet_content(
        $stylesheet_content,
        $current_url,
        $rewrite_to_https,
        $assets_preloading,
        array $user_agents
    ) {
        return self::get(
            Content_Parsing::get_stylesheets_links_from_stylesheet_content( $stylesheet_content, $current_url, $assets_preloading ),
            [],
            false,
            false,
            $rewrite_to_https,
            $user_agents
        );
    }

    /**
     * Returns sitemap links from HTML (sitemap meta tag).
     *
     * @param string $html             HTML.
     * @param string $current_url      Current page, which should be added before the relative link.
     * @param bool   $rewrite_to_https Rewrites HTTP links to HTTPS.
     * @param array  $user_agents      The list of user agents, to visit each page with.
     *
     * @return array Correctly formatted links for the tree considering current settings (passed params).
     *
     * @throws Exception Exception.
     */
    public static function get_sitemap_links_from_html( $html, $current_url, $rewrite_to_https, array $user_agents ) {
        return self::get(
            Content_Parsing::get_sitemap_links_from_html( $html, $current_url ),
            [],
            false,
            false,
            $rewrite_to_https,
            $user_agents
        );
    }

    /**
     * Returns children (be it the children sitemaps or the pages) from the sitemap content.
     *
     * @param string $sitemap_content   Sitemap content.
     * @param string $current_url       Current page, which should be added before the relative link.
     * @param array  $user_agent        User-agent.
     * @param array  $url_params                           URL params.
     * @param bool   $visit_second_time_without_url_params Visit second time without URL params (if they are set).
     * @param bool   $visit_second_time_without_cookies    Visit second time without cookies (if they are set).
     * @param bool   $rewrite_to_https  Rewrites HTTP links to HTTPS.
     * @param array  $user_agents       The list of user agents, to visit each page with.
     *
     * @return array Correctly formatted links for the tree considering current settings (passed params).
     *
     * @throws Exception Exception.
     */
    public static function get_children_from_sitemap(
        $sitemap_content,
        $current_url,
        $user_agent,
        $url_params,
        $visit_second_time_without_url_params,
        $visit_second_time_without_cookies,
        $rewrite_to_https,
        array $user_agents
    ) {
        return self::get(
            Content_Parsing::get_children_from_sitemap( $sitemap_content, $current_url, $user_agent ),
            $url_params,
            $visit_second_time_without_url_params,
            $visit_second_time_without_cookies,
            $rewrite_to_https,
            $user_agents
        );
    }

    /**
     * Returns the subtree with the post-formatted nodes.
     *
     * @param array $links                                Links.
     * @param array $url_params                           URL params.
     * @param bool  $visit_second_time_without_url_params Visit second time without URL params (if they are set).
     * @param bool  $visit_second_time_without_cookies    Visit second time without cookies (if they are set).
     * @param bool  $rewrite_to_https                     Rewrite HTTP links to HTTPS.
     * @param array $user_agents                          The list of user agents, to visit each page with.
     *
     * @return array Correctly formatted links for the tree considering current settings (passed params).
     *
     * @throws Exception Exception.
     */
    public static function get(
        $links,
        $url_params,
        $visit_second_time_without_url_params,
        bool $visit_second_time_without_cookies,
        bool $rewrite_to_https,
        array $user_agents
    ) {
        return self::delete_retrieved_links_from_subtree(
            self::add_links_meta(
                self::update_url_params_and_second_time_visit(
                    self::rewrite_to_https( self::create_a_subtree_array_from_links( $links ), $rewrite_to_https ),
                    $url_params,
                    $visit_second_time_without_url_params
                ),
                $user_agents,
                $visit_second_time_without_cookies
            )
        );
    }

    /**
     * Creates an array for links where value is an array and key is the link.
     *
     * @param array $links Links.
     *
     * @return array
     */
    private static function create_a_subtree_array_from_links( array $links ) {
        $array = [];
        foreach ( $links as $link ) {
            $array[ $link ] = [];
        }
        return $array;
    }

    /**
     * Deletes retrieved links from the array of the links.
     *
     * @param array $array Links.
     *
     * @return array Array of links without the retrieved ones.
     *
     * @throws Exception Exception.
     */
    private static function delete_retrieved_links_from_subtree( array $array ) {
        $retrieved_links = Cache_Warmer::$options->get( 'cache-warmer-retrieved-links' );
        foreach ( array_keys( $array ) as $link ) {
            if ( array_key_exists( $link, $retrieved_links ) ||
                array_key_exists( $link . '/', $retrieved_links )
            ) {
                unset( $array[ $link ] );
            }
        }

        return $array;
    }

    /**
     * Optionally Rewrites HTTP urls to HTTPS, depending on the parameter passed.
     *
     * @param array $urls             URLs.
     * @param bool  $rewrite_to_https Whether to rewrite HTTP links to HTTPS.
     *
     * @return array URLs with protocol optionally overwritten to HTTPS.
     */
    private static function rewrite_to_https( $urls, $rewrite_to_https ) {
        if ( $rewrite_to_https ) {
            foreach ( $urls as $link => $children ) {
                unset( $urls[ $link ] );
                $urls[ Url_Formatting::rewrite_url_to_https( $link ) ] = $children;
            }
        }
        return $urls;
    }

    /**
     * Post-process subtree, by adding URL params and second time visit to it, if needed.
     *
     * @param array $urls                                 URLs.
     * @param array $url_params                           URL params.
     * @param bool  $visit_second_time_without_url_params Visit pages second time without URL params.
     *
     * @return array URLs with added url params.
     */
    private static function update_url_params_and_second_time_visit( $urls, $url_params, $visit_second_time_without_url_params ) {
        foreach ( $urls as $link => $children ) {
            $extension = Url_Parsing::get_extension( $link );

            // Add URL params and visit second time only pages, and not to assets, sitemaps etc.
            if ( ! $extension || in_array( $extension, Content_Parsing::PAGE_EXTENSIONS, true ) ) {
                unset( $urls[ $link ] );

                $urls[ Url_Formatting::add_url_params( $link, $url_params ) ] = $children;

                if ( $url_params && $visit_second_time_without_url_params ) {
                    $urls[ $link ] = [];
                }
            }
        }

        return $urls;
    }

    /**
     * Adds meta to the links.
     *
     * Currently, does the following:
     *
     * - For each user agents, create a separate link visit with that user-agent in meta.
     *
     * @param array $urls                              URLs.
     * @param array $user_agents                       The list of user agents, to visit each page with.
     * @param bool  $visit_second_time_without_cookies Visit pages second time without cookies.
     *
     * @return array URLs with added url params.
     */
    private static function add_links_meta( array $urls, array $user_agents, bool $visit_second_time_without_cookies ) : array {
        foreach ( $urls as $link => $children ) {
            unset( $urls[ $link ] );

            foreach ( $user_agents as $user_agent ) {
                $meta = [
                    'user_agent' => $user_agent,
                ];

                $urls[ $link . '|' . maybe_serialize( $meta ) ] = $children;

                if ( $visit_second_time_without_cookies ) {
                    $meta['visit_without_cookies'] = true;

                    $urls[ $link . '|' . maybe_serialize( $meta ) ] = $children;
                }
            }
        }

        return $urls;
    }
}
