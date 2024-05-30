<?php
/**
 * Class for parsing of HTML and CSS to get the list of URLs for further retrieval.
 *
 * @package Cache-Warmer
 */

namespace Cache_Warmer;

use vipnytt\SitemapParser;
use vipnytt\SitemapParser\Exceptions\SitemapParserException;

/**
 * Content_Parsing class.
 */
final class Content_Parsing {

    /**
     * The list of allowed HTML page extensions.
     */
    const PAGE_EXTENSIONS = [
        'html',
        'htm',
        'xhtml',
        'dhtml',
        'shtml',
        'jsp',
        'asp',
        'php',
    ];

    /**
     * Formats links array into well-formatted URLs. Used to post-process regEx founded links from the page.
     *
     * @param array  $links       Links that should be formatted.
     * @param string $current_url Current page, which should be added before the relative link.
     * @param array  $urls        The current list of URLs.
     *
     * @return array The current list of URLs plus well-formatted links from the array.
     */
    private static function links_format_validate_and_append_to_the_link( array $links, $current_url, array &$urls = [] ) {
        foreach ( $links as $link ) {
            $link = Url_Formatting::convert_a_url_to_absolute( $link, $current_url );

            if (
                $link &&
                ! in_array( $link, $urls, true ) && // Unique.
                Url_Validation::is_url_host_allowed( $link, $current_url ) // Do not add external hosts.
            ) {
                $urls[] = $link;
            }
        }

        return $urls;
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
        $urls = [];

        if ( preg_match_all( '/<a[^>]+href=["\']([^"\']+?)["\'][^>]*?>/i', $html, $links ) ) {
            foreach ( $links[1] as $link ) {
                if ( str_starts_with( $link, '#' ) ) {
                    continue; // No anchors.
                }

                $link = Url_Formatting::convert_a_url_to_absolute( $link, $current_url );
                if ( ! $link ) {
                    continue;
                }

                $extension = Url_Parsing::get_extension( $link );

                if (
                    ! in_array( $link, $urls, true ) && // Unique.
                    Url_Validation::is_url_host_allowed( $link, $current_url ) &&
                    ( ! $extension || in_array( $extension, self::PAGE_EXTENSIONS, true ) )
                ) {
                    $urls[] = $link;
                }
            }
        }

        return $urls;
    }

    /**
     * Get canonical URL from HTML.
     *
     * @param string $html HTML.
     *
     * @return string|false
     */
    public static function get_canonical_from_html( string $html ) {
        $doc = new \DOMDocument();
        libxml_use_internal_errors( true ); // Suppress HTML parsing errors.
        $doc->loadHTML( $html );

        $links = $doc->getElementsByTagName( 'link' );

        foreach ( $links as $link ) {
            if ( 'canonical' === $link->getAttribute( 'rel' ) ) {
                return $link->getAttribute( 'href' );
            }
        }

        return false;
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

        if ( ! $current_url_parts ) {
            return $urls;
        }

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
            if ( preg_match_all( '/<script[^>]+src=["\']([^"\']+?)["\'][^>]*?>/i', $html, $links ) ) {
                self::links_format_validate_and_append_to_the_link( $links[1], $current_url, $urls );
            }
        }

        if ( $assets_preloading['styles'] ) {
            if ( preg_match_all( '/<link[^>]+rel=["\']stylesheet["\'][^<]*?>/i', $html, $stylesheets ) ) {
                foreach ( $stylesheets[0] as $stylesheet ) {
                    if ( preg_match( '/href=["\']([^"\']+?)["\']/i', $stylesheet, $href ) ) {
                        self::links_format_validate_and_append_to_the_link( (array) $href[1], $current_url, $urls );
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

            if ( preg_match_all( '/<img[^>]+src=["\']([^"\']+?)["\'][^>]*?>/i', $html, $links ) ) {
                self::links_format_validate_and_append_to_the_link( $links[1], $current_url, $urls );
            }

            // <img>s' srcsets.

            if ( preg_match_all( '/<img[^>]+srcset=["\']([^"\']+?)["\'][^>]*?>/i', $html, $srcsets ) ) {
                foreach ( $srcsets[1] as $srcset ) {
                    self::links_format_validate_and_append_to_the_link( $get_links_from_srcset( $srcset ), $current_url, $urls );
                }
            }

            // <picture>s' <source>s' srcsets.

            if ( preg_match_all( '/<picture>([\S\s]+?)<\/picture>/i', $html, $pictures ) ) {
                foreach ( $pictures[1] as $picture ) {
                    if ( preg_match_all( '/<source[^>]+srcset=["\']([^"\']+?)["\'][^>]*?>/i', $picture, $srcsets ) ) {
                        foreach ( $srcsets[1] as $srcset ) {
                            self::links_format_validate_and_append_to_the_link( $get_links_from_srcset( $srcset ), $current_url, $urls );
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
        return preg_match_all( '/<link[^>]+rel=["\']sitemap["\'][^>]+href=["\']([^"\']+?)["\']/i', $html, $sitemaps ) ?
            self::links_format_validate_and_append_to_the_link( $sitemaps[1], $current_url ) :
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
            $current_url_parts &&
            $assets_preloading['fonts'] ||
            $assets_preloading['images']
        ) {
            if ( preg_match_all( '/@font-face[\S\s]+?}/i', $stylesheet_content, $font_faces ) ) {
                foreach ( $font_faces[0] as $font_face ) {
                    $stylesheet_content = str_replace( $font_face, '', $stylesheet_content ); // Remove all @font-face.

                    if ( $assets_preloading['fonts'] ) {
                        if ( preg_match_all( '/url\(["\']?((?!data:)[^"\']+?)["\']?\)/i', $font_face, $links ) ) {
                            self::links_format_validate_and_append_to_the_link( $links[1], $current_url, $urls );
                        }
                    }
                }
            }

            if ( $assets_preloading['images'] ) {
                $stylesheet_content = preg_replace( '#@import\s+(?:url\()?["\']?([^"\']+?)["\']?(\))?#', '', $stylesheet_content ); // Remove all @import.

                // We suppose that all leftover rules are for images because @font-face and @import rules were removed.
                if ( preg_match_all( '/url\(["\']?((?!data:)[^"\']+?)["\']?\)/i', $stylesheet_content, $links ) ) {
                    self::links_format_validate_and_append_to_the_link( $links[1], $current_url, $urls );
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

        if ( $current_url_parts && $assets_preloading['styles'] ) {
            if ( preg_match_all( '#@import\s+(?:url\()?["\']?([^"\']+?)["\']?(\))?#', $stylesheet_content, $stylesheet_imports ) ) {
                self::links_format_validate_and_append_to_the_link( $stylesheet_imports[1], $current_url, $urls );
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

            return self::links_format_validate_and_append_to_the_link( $urls, $current_url );
        } catch ( SitemapParserException $e ) {
            return [];
        }
    }
}
