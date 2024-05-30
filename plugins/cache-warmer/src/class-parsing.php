<?php
/**
 * Class for parsing of HTML and CSS to get the list of URLs for further retrieval.
 *
 * @package Cache-Warmer
 */

namespace Cache_Warmer;

use phpUri;
use vipnytt\SitemapParser;
use vipnytt\SitemapParser\Exceptions\SitemapParserException;

/**
 * Parsing class.
 */
final class Parsing {

    /**
     * Get the extension from URL parts.
     *
     * @param array $url_parts URL Parts.
     *
     * @return bool|string Extension or false if not extension found.
     */
    private static function get_extension_from_url_parts( $url_parts ) {
        $extension = false;
        if ( array_key_exists( 'path', $url_parts ) ) {
            $path = untrailingslashit( $url_parts['path'] );
            if ( str_contains( $path, '.' ) ) {
                $parts     = explode( '.', $path );
                $extension = end( $parts );
            }
        }
        return $extension;
    }

    /**
     * Formats links array into well-formatted URLs. Used for to post-process regEx founded links from the page.
     *
     * @param array  $links             Links that should be formatted.
     * @param string $current_url       Current page, which should be added before the relative link.
     * @param array  $current_url_parts Current URL parts.
     * @param array  $urls              The current list of URLs.
     *
     * @return array The current list of URLs plus well-formatted links from the array.
     */
    private static function format_links_and_append_to_the_list( array $links, $current_url, $current_url_parts = null, array $urls = [] ) {
        if ( null === $current_url_parts ) {
            $current_url_parts = wp_parse_url( $current_url );
        }

        foreach ( $links as $link ) {
            $absolute_url = phpUri::parse( $current_url )->join( $link ); // Convert relative URL to absolute.
            $url_parts    = wp_parse_url( $absolute_url );

            if (
                ! in_array( $absolute_url, $urls, true ) && // Unique.
                self::is_host_allowed( $current_url_parts, $url_parts ) // Do not add external hosts.
            ) {
                $urls[] = $absolute_url;
            }
        }

        return $urls;
    }

    /**
     * Is host allowed.
     *
     * Used to not add a link for hostname B from page of host A.
     *
     * @param array $current_url_parts          Current page parts, from which the URL is retrieved.
     * @param array $url_parts_to_check_against URL parts of the URL that should be checked.
     */
    private static function is_host_allowed( $current_url_parts, $url_parts_to_check_against ) {
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
         * Retrieves the host a strips "www." from the beginning of it.
         *sv
         * @param string $url
         *
         * @return string URL without a "www." postfix.
         */
        $url_get_host_and_strip_www = function ( $url ) use ( $host_strip_www ) {
            return $host_strip_www( wp_parse_url( $url )['host'] );
        };

        $hosts_to_check_against = [
            $host_strip_www( $current_url_parts['host'] ),
        ];

        // WP-Rocket CDN support.
        if (
            in_array( 'wp-rocket/wp-rocket.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) &&
            function_exists( 'get_rocket_option' )
        ) {
            if ( get_rocket_option( 'cdn' ) ) {
                $rocket_cnames = get_rocket_option( 'cdn_cnames' );
                foreach ( $rocket_cnames as $url ) {
                    if ( ! in_array( $url, $hosts_to_check_against, true ) ) { // Unique.
                        $hosts_to_check_against[] = $url_get_host_and_strip_www( $url );
                    }
                }
            }
        }

        return
            ! isset( $url_parts_to_check_against['host'] ) ||
            in_array( $host_strip_www( $url_parts_to_check_against['host'] ), $hosts_to_check_against, true );
    }

    /**
     * Returns page links.
     *
     * @param string $html        HTML.
     * @param string $current_url Current page, which should be added before the relative link.
     *
     * @return array
     */
    public static function get_pages_links_from_html( $html, $current_url ) {
        $current_url_parts = wp_parse_url( $current_url );
        $urls              = [];

        if ( preg_match_all( '/<a[^>]+href=["\']([^"\']+)["\'][^>]*?>/i', $html, $links ) ) {
            foreach ( $links[1] as $link ) {
                if ( str_starts_with( $link, '#' ) ) {
                    continue; // No anchors.
                }

                $absolute_url = phpUri::parse( $current_url )->join( $link ); // Convert relative URL to absolute.

                // Untrailingslash the path.

                $url_parts    = wp_parse_url( $absolute_url );

                $absolute_url = $url_parts['scheme'] . '://' .
                                ( array_key_exists( 'host', $url_parts ) ? $url_parts['host'] : '' ) .
                                ( array_key_exists( 'port', $url_parts ) ? ':' . $url_parts['port'] : '' ) .
                                ( array_key_exists( 'path', $url_parts ) ? untrailingslashit( $url_parts['path'] ) : '' ) .
                                ( array_key_exists( 'query', $url_parts ) ? '?' . $url_parts['query'] : '' );

                // Get the extension.

                $extension = self::get_extension_from_url_parts( $url_parts );

                if (
                    ! in_array( $absolute_url, $urls, true ) && // Unique.
                    self::is_host_allowed( $current_url_parts, $url_parts ) &&
                    ( ! $extension || 'php' === $extension ) // No extension or .php.
                ) {
                    $urls[] = $absolute_url;
                }
            }
        }

        return $urls;
    }

    /**
     * Returns page links.
     *
     * @param string $html              HTML.
     * @param string $current_url       Current page, which should be added before the relative link.
     * @param array  $assets_preloading Assets preloading.
     *
     * @return array
     */
    public static function get_assets_links_from_html( $html, $current_url, $assets_preloading ) {
        $current_url_parts = wp_parse_url( $current_url );
        $urls              = [];

        /**
         * Returns array of srcsets links.
         *
         * @param string[] Array of string of srcsets' values.
         *
         * @return array Links.
         */
        $get_links_from_srcset = function ( $srcsets ) {
            return array_map( // Converts srcset entities to the links.
                function ( $x ) {
                    return preg_split( '/\s+/i', $x )[0];
                },
                array_map( 'trim', explode( ',', $srcsets ) )
            );
        };

        if ( $assets_preloading['scripts'] ) {
            if ( preg_match_all( '/<script[^>]+src=["\']([^"\']+)["\'][^>]*?>/i', $html, $links ) ) {
                $urls = self::format_links_and_append_to_the_list( $links[1], $current_url, $current_url_parts, $urls );
            }
        }

        if ( $assets_preloading['styles'] ) {
            if ( preg_match_all( '/<link[^>]+rel=["\']stylesheet["\'][^<]*?>/i', $html, $stylesheets ) ) {
                foreach ( $stylesheets[0] as $stylesheet ) {
                    if ( preg_match( '/href=["\']([^"\']+)["\']/i', $stylesheet, $href ) ) {
                        $urls = self::format_links_and_append_to_the_list( (array) $href[1], $current_url, $current_url_parts, $urls );
                    }
                }
            }

            if ( preg_match_all( '/<style[\S\s]+?<\/style>/i', $html, $styles ) ) {
                foreach ( $styles[0] as $style ) {
                    $urls = array_merge(
                        $urls,
                        self::get_fonts_and_images_from_stylesheet_content( $style, $current_url, $assets_preloading ),
                        self::get_stylesheets_links_from_stylesheet_content( $style, $current_url, $assets_preloading )
                    );
                }
            }
        }

        if ( $assets_preloading['images'] ) {
            // <img>s' src.

            if ( preg_match_all( '/<img[^>]+src=["\']([^"\']+)["\'][^>]*?>/i', $html, $links ) ) {
                $urls = self::format_links_and_append_to_the_list( $links[1], $current_url, $current_url_parts, $urls );
            }

            // <img>s' srcsets.

            if ( preg_match_all( '/<img[^>]+srcset=["\']([^"\']+)["\'][^>]*?>/i', $html, $srcsets ) ) {
                foreach ( $srcsets[1] as $srcset ) {
                    $urls = self::format_links_and_append_to_the_list( $get_links_from_srcset( $srcset ), $current_url, $current_url_parts, $urls );
                }
            }

            // <picture>s' <source>s' srcsets.

            if ( preg_match_all( '/<picture>([\S\s]+?)<\/picture>/i', $html, $pictures ) ) {
                foreach ( $pictures[1] as $picture ) {
                    if ( preg_match_all( '/<source[^>]+srcset=["\']([^"\']+)["\'][^>]*?>/i', $picture, $srcsets ) ) {
                        foreach ( $srcsets[1] as $srcset ) {
                            $urls = self::format_links_and_append_to_the_list( $get_links_from_srcset( $srcset ), $current_url, $current_url_parts, $urls );
                        }
                    }
                }
            }
        }

        return $urls;
    }

    /**
     * Returns sitemap links from HTML (sitemap meta tag).
     *
     * @param string $html        HTML.
     * @param string $current_url Current page, which should be added before the relative link.
     *
     * @return array
     */
    public static function get_sitemap_links_from_html( $html, $current_url ) {
        return preg_match_all( '/<link[^>]+rel=["\']sitemap["\'][^>]+href=["\']([^"\']+)["\']/i', $html, $sitemaps ) ?
            self::format_links_and_append_to_the_list( $sitemaps[1], $current_url ) :
            [];
    }

    /**
     * Returns links from stylesheet.
     *
     * @param string $stylesheet_content Content of the stylesheet.
     * @param string $current_url        Current page, which should be added before the relative link.
     * @param array  $assets_preloading  Assets preloading.
     *
     * @return array
     */
    public static function get_fonts_and_images_from_stylesheet_content( $stylesheet_content, $current_url, $assets_preloading ) {
        $current_url_parts = wp_parse_url( $current_url );
        $urls              = [];

        if (
            $assets_preloading['fonts'] ||
            $assets_preloading['images']
        ) {
            if ( preg_match_all( '/@font-face[\S\s]+?}/i', $stylesheet_content, $font_faces ) ) {
                foreach ( $font_faces[0] as $font_face ) {
                    $stylesheet_content = str_replace( $font_face, '', $stylesheet_content ); // Remove all @font-face.

                    if ( $assets_preloading['fonts'] ) {
                        if ( preg_match_all( '/url\(["\']?((?!data:)[^"\']+)["\']?\)/i', $font_face, $links ) ) {
                            $urls = self::format_links_and_append_to_the_list( $links[1], $current_url, $current_url_parts, $urls );
                        }
                    }
                }
            }

            if ( $assets_preloading['images'] ) {
                $stylesheet_content = preg_replace( '#@import\s+(?:url\()?["\']?([^"\']+)["\']?(\))?#', '', $stylesheet_content ); // Remove all @import.

                // We suppose that all leftover rules are for images because @font-face and @import rules were removed.
                if ( preg_match_all( '/url\(["\']?((?!data:)[^"\']+)["\']?\)/i', $stylesheet_content, $links ) ) {
                    $urls = self::format_links_and_append_to_the_list( $links[1], $current_url, $current_url_parts, $urls );
                }
            }
        }

        return $urls;
    }

    /**
     * Returns links from stylesheet.
     *
     * @param string $stylesheet_content Content of the stylesheet.
     * @param string $current_url        Current page, which should be added before the relative link.
     * @param array  $assets_preloading  Assets preloading.
     *
     * @return array
     */
    public static function get_stylesheets_links_from_stylesheet_content( $stylesheet_content, $current_url, $assets_preloading ) {
        $current_url_parts = wp_parse_url( $current_url );
        $urls              = [];

        if ( $assets_preloading['styles'] ) {
            if ( preg_match_all( '#@import\s+(?:url\()?["\']?([^"\']+)["\']?(\))?#', $stylesheet_content, $stylesheet_imports ) ) {
                $urls = self::format_links_and_append_to_the_list( $stylesheet_imports[1], $current_url, $current_url_parts, $urls );
            }
        }

        return $urls;
    }

    /**
     * Returns children (be it the children sitemaps or the pages) from the sitemap content.
     *
     * @param string $sitemap_content Sitemap content.
     * @param string $current_url     Current page, which should be added before the relative link.
     * @param array  $user_agent      User-agent.
     *
     * @return array Get children from the sitemap.
     */
    public static function get_children_from_sitemap( $sitemap_content, $current_url, $user_agent ) {
        if ( ! extension_loaded( 'simplexml' ) ) {
            return [];
        }

        try {
            $parser = new SitemapParser( $user_agent );
            $parser->parse( $current_url, $sitemap_content );

            $sitemap_pages = $parser->getURLs();

            if ( $sitemap_pages ) {
                usort( // Sort by priority.
                    $sitemap_pages,
                    function ( $a, $b ) {
                        $priority_a = isset( $a['priority'] ) ? (float) $a['priority'] : 0.5;
                        $priority_b = isset( $b['priority'] ) ? (float) $b['priority'] : 0.5;
                        if ( $priority_b === $priority_a ) {
                            return 0;
                        }
                        return ( $priority_b < $priority_a ) ? -1 : 1;
                    }
                );
            }

            $urls = array_merge(
                array_unique( array_column( $sitemap_pages, 'loc' ) ),
                array_unique( array_keys( $parser->getSitemaps() ) )
            );

            return self::format_links_and_append_to_the_list( $urls, $current_url );
        } catch ( SitemapParserException $e ) {
            return [];
        }
    }
}
