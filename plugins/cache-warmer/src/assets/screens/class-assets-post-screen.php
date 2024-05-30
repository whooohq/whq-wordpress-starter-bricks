<?php
/**
 * Assets for posts screen
 *
 * Loads assets (JS, CSS), adds data for them.
 *
 * @package Cache-Warmer
 */

namespace Cache_Warmer\Assets\Menu\Screens;

use Cache_Warmer\Cache_Warmer;
use Cache_Warmer\Url_Formatting;
use Cache_Warmer\Utils;

/**
 * Assets class.
 */
final class Post {

    /**
     * Constructor.
     */
    public function __construct() {
        if ( isset( $_GET['post'] ) ) { // @codingStandardsIgnoreLine
            $current_post_id = (int) $_GET['post']; // @codingStandardsIgnoreLine

            $post_type = get_post_type( $current_post_id );
            if ( $post_type ) {
                $post_type_obj = get_post_type_object( $post_type );
                if ( $post_type_obj->publicly_queryable || $post_type_obj->public ) {
                    $this->styles();
                    $this->scripts();
                }
            }
        }
    }

    /**
     * Loads styles.
     */
    private function styles() {
        wp_enqueue_style(
            'cache-warmer-admin-post-screen-style',
            CACHE_WARMER_URL . 'assets-build/admin/screens/post.css',
            [ 'tmm-wp-plugins-core-admin-style' ],
            CACHE_WARMER_VERSION
        );
    }

    /**
     * Loads scripts.
     */
    private function scripts() {
        $current_post_id = (int) $_GET['post']; // @codingStandardsIgnoreLine

        $dependencies = ( require CACHE_WARMER_DIR . 'assets-build/admin/screens/post.asset.php' )['dependencies'];
        wp_enqueue_script(
            'cache-warmer-admin-post-screen-script',
            CACHE_WARMER_URL . 'assets-build/admin/screens/post.js',
            array_merge(
                $dependencies,
                [
                    'tmm-wp-plugins-core-admin-script',
                ]
            ),
            CACHE_WARMER_VERSION,
            true
        );

        wp_set_script_translations(
            'cache-warmer-admin-post-screen-script',
            'cache-warmer',
            CACHE_WARMER_DIR . 'languages/js'
        );

        $post_data = Utils::get_post_latest_data( $current_post_id );

        $post_url = get_permalink( $current_post_id );

        if ( Cache_Warmer::$options->get( 'setting-rewrite-to-https' ) ) { // Rewrite to HTTPS, if the setting is enabled.
            $post_url = Url_Formatting::rewrite_url_to_https( $post_url );
        }

        $excluded_pages_use_regex_match = '1' === Cache_Warmer::$options->get( 'setting-excluded-pages-use-regex-match' );

        $url_params           = Cache_Warmer::$options->get( 'setting-url-params' );
        $post_url_with_params = false;
        if ( $url_params ) {
            $post_url_with_params = Url_Formatting::add_url_params( $post_url, $url_params );
        }

        $url_to_check_the_exclusion = $post_url_with_params ? $post_url_with_params : $post_url;

        $excluded_match = false;
        foreach ( array_filter( Cache_Warmer::$options->get( 'cache-warmer-setting-excluded-pages' ) ) as $potential_excluded_match ) {
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
                $excluded_pages_use_regex_match && preg_match( '@' . str_replace( '@', '\@', $potential_excluded_match ) . '@', $url_to_check_the_exclusion ) ||
                ! $excluded_pages_use_regex_match && str_contains( $url_to_check_the_exclusion, $potential_excluded_match )
            ) {
                $excluded_match = $potential_excluded_match;
                break;
            }
        }

        $post_data->post_url             = $post_url;
        $post_data->post_url_with_params = $post_url_with_params;

        $post_data = json_decode( wp_json_encode( $post_data ), true ); // Convert to array.

        $post_data['would_be_excluded_str'] = $excluded_match ?
            /* translators: %s is the match. */
            sprintf( __( 'Yes, (because of "%s")', 'cache-warmer' ), $excluded_match ) :
            __( 'No', 'cache-warmer' );

        wp_localize_script(
            'cache-warmer-admin-post-screen-script',
            'cacheWarmer',
            [
                'postData' => $post_data,
            ]
        );
    }
}
