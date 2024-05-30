<?php
/**
 * Class for formatting of links.
 *
 * @package Cache-Warmer
 */

namespace Cache_Warmer;

use Exception;

/**
 * Formatting class.
 */
final class Formatting {

    /**
     * Creates an array for links where value is an array and key is the link.
     *
     * @param array $links Links.
     *
     * @return array
     */
    public static function create_an_array_from_links( array $links ) {
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
    public static function delete_retrieved_links_from_array( array $array ) {
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
     * Adds URL params.
     *
     * @param string $url        URL.
     * @param array  $url_params URL params.
     *
     * @return string URL with added url params.
     */
    public static function add_url_params( $url, $url_params ) {
        if ( ! $url_params ) {
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
     * Optionally Rewrites HTTP urls to HTTPS, depending on the parameter passed.
     *
     * @param array $urls             URLs.
     * @param bool  $rewrite_to_https Whether to rewrite HTTP links to HTTPS.
     *
     * @return array URLs with protocol optionally overwritten to HTTPS.
     */
    public static function rewrite_to_https( $urls, $rewrite_to_https ) {
        if ( $rewrite_to_https ) {
            foreach ( $urls as $link => $children ) {
                unset( $urls[ $link ] );
                $urls[ preg_replace( '/^http:/i', 'https:', $link ) ] = $children;
            }
        }
        return $urls;
    }

    /**
     * Adds URL params.
     *
     * @param array $urls                                 URLs.
     * @param array $url_params                           URL params.
     * @param bool  $visit_second_time_without_url_params Visit pages second time without URL params.
     *
     * @return array URLs with added url params.
     */
    public static function add_url_params_array( $urls, $url_params, $visit_second_time_without_url_params ) {
        foreach ( $urls as $link => $children ) {
            unset( $urls[ $link ] );
            $url_with_params          = self::add_url_params( $link, $url_params );
            $urls[ $url_with_params ] = $children;

            if ( $url_params && $visit_second_time_without_url_params ) {
                $urls[ $link ] = [];
            }
        }

        return $urls;
    }

    /**
     * Handles links from HTML.
     *
     * @param array $links                                Links.
     * @param array $url_params                           URL params.
     * @param bool  $visit_second_time_without_url_params Visit second time without URL params.
     * @param bool  $rewrite_to_https                     Rewrite HTTP links to HTTPS.
     *
     * @return array Correctly formatted links for the tree considering current settings (passed params).
     *
     * @throws Exception Exception.
     */
    public static function handle_links( $links, $url_params, $visit_second_time_without_url_params, $rewrite_to_https ) {
        return self::delete_retrieved_links_from_array(
            self::add_url_params_array(
                self::rewrite_to_https( self::create_an_array_from_links( $links ), $rewrite_to_https ),
                $url_params,
                $visit_second_time_without_url_params
            )
        );
    }

    /**
     * Returns pages links from HTML.
     *
     * @param string $html                                 HTML.
     * @param string $current_url                          Current page, which should be added before the relative link.
     * @param array  $url_params                           URL params.
     * @param bool   $visit_second_time_without_url_params Visit second time without URL params.
     * @param bool   $rewrite_to_https                     Rewrites HTTP links to HTTPS.
     *
     * @return array Correctly formatted links for the tree considering current settings (passed params).
     *
     * @throws Exception Exception.
     */
    public static function get_pages_links_from_html( $html, $current_url, $url_params, $visit_second_time_without_url_params, $rewrite_to_https ) {
        return self::handle_links(
            Parsing::get_pages_links_from_html( $html, $current_url ),
            $url_params,
            $visit_second_time_without_url_params,
            $rewrite_to_https
        );
    }

    /**
     * Returns assets links from HTML.
     *
     * @param string $html              HTML.
     * @param string $current_url       Current page, which should be added before the relative link.
     * @param bool   $rewrite_to_https  Rewrites HTTP links to HTTPS.
     * @param array  $assets_preloading Assets preloading.
     *
     * @return array Correctly formatted links for the tree considering current settings (passed params).
     *
     * @throws Exception Exception.
     */
    public static function get_assets_links_from_html( $html, $current_url, $rewrite_to_https, $assets_preloading ) {
        return self::handle_links(
            Parsing::get_assets_links_from_html( $html, $current_url, $assets_preloading ),
            [],
            false,
            $rewrite_to_https
        );
    }

    /**
     * Returns sitemap links from HTML (sitemap meta tag).
     *
     * @param string $html             HTML.
     * @param string $current_url      Current page, which should be added before the relative link.
     * @param bool   $rewrite_to_https Rewrites HTTP links to HTTPS.
     *
     * @return array Correctly formatted links for the tree considering current settings (passed params).
     *
     * @throws Exception Exception.
     */
    public static function get_sitemap_links_from_html( $html, $current_url, $rewrite_to_https ) {
        return self::handle_links(
            Parsing::get_sitemap_links_from_html( $html, $current_url ),
            [],
            false,
            $rewrite_to_https
        );
    }

    /**
     * Returns fonts links from stylesheet content.
     *
     * @param string $stylesheet_content Stylesheet content.
     * @param string $current_url        Current page, which should be added before the relative link.
     * @param bool   $rewrite_to_https   Rewrites HTTP links to HTTPS.
     * @param array  $assets_preloading  Assets preloading.
     *
     * @return array Correctly formatted links for the tree considering current settings (passed params).
     *
     * @throws Exception Exception.
     */
    public static function get_fonts_and_images_from_stylesheet_content( $stylesheet_content, $current_url, $rewrite_to_https, $assets_preloading ) {
        return self::handle_links(
            Parsing::get_fonts_and_images_from_stylesheet_content( $stylesheet_content, $current_url, $assets_preloading ),
            [],
            false,
            $rewrite_to_https
        );
    }

    /**
     * Returns fonts links from stylesheet content.
     *
     * @param string $stylesheet_content Stylesheet content.
     * @param string $current_url        Current page, which should be added before the relative link.
     * @param bool   $rewrite_to_https   Rewrites HTTP links to HTTPS.
     * @param array  $assets_preloading  Assets preloading.
     *
     * @return array Correctly formatted links for the tree considering current settings (passed params).
     *
     * @throws Exception Exception.
     */
    public static function get_stylesheets_links_from_stylesheet_content( $stylesheet_content, $current_url, $rewrite_to_https, $assets_preloading ) {
        return self::handle_links(
            Parsing::get_stylesheets_links_from_stylesheet_content( $stylesheet_content, $current_url, $assets_preloading ),
            [],
            false,
            $rewrite_to_https
        );
    }

    /**
     * Returns children (be it the children sitemaps or the pages) from the sitemap content.
     *
     * @param string $sitemap_content   Sitemap content.
     * @param string $current_url       Current page, which should be added before the relative link.
     * @param bool   $rewrite_to_https  Rewrites HTTP links to HTTPS.
     * @param array  $user_agent        User-agent.
     *
     * @return array Correctly formatted links for the tree considering current settings (passed params).
     *
     * @throws Exception Exception.
     */
    public static function get_children_from_sitemap( $sitemap_content, $current_url, $rewrite_to_https, $user_agent ) {
        return self::handle_links(
            Parsing::get_children_from_sitemap( $sitemap_content, $current_url, $user_agent ),
            [],
            false,
            $rewrite_to_https
        );
    }
}
