<?php
/**
 * A class to add post info to the post publish box.
 *
 * @package Cache-Warmer
 */

namespace Cache_Warmer\Representation;

use Cache_Warmer\Cache_Warmer;
use Cache_Warmer\Url_Formatting;
use Cache_Warmer\Utils;
use WP_Post;

/**
 * Class Post_Publish_Box.
 */
final class Publish_Box {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'add_meta_boxes', [ $this, 'add_custom_box' ] );
    }

    /**
     * Adds custom box.
     */
    public function add_custom_box() {
        add_meta_box(
            'cache-warmer-standard-editor',
            __( 'Cache Warmer', 'cache-warmer' ),
            [ $this, 'print_meta_box_content' ],
            [ 'post', 'page', 'product' ],
            'side',
            'default',
            [ '__back_compat_meta_box' => true ]
        );
    }

    /**
     * Adds publish-box text.
     *
     * @param WP_Post $post The current post object.
     */
    public function print_meta_box_content( WP_Post $post ) {
        $post_data = Utils::get_post_latest_data( $post->ID );

        $post_url = get_permalink( $post->ID );

        if ( Cache_Warmer::$options->get( 'setting-rewrite-to-https' ) ) { // Rewrite to HTTPS, if the setting is enabled.
            $post_url = Url_Formatting::rewrite_url_to_https( $post_url );
        }

        $url_params           = Cache_Warmer::$options->get( 'setting-url-params' );
        $post_url_with_params = false;
        if ( $url_params ) {
            $post_url_with_params = Url_Formatting::add_url_params( $post_url, $url_params );
        }

        ?>
        <div class="misc-pub-section cache-warmer-publish-box" id="cache-warmer-url-to-warm">
            <div class="cache-warmer-publish-box-image"></div>
            <div class="cache-warmer-publish-box-content">
                <?php esc_attr_e( 'URL:', 'cache-warmer' ); ?>
                <b><?php echo esc_attr( $post_url ); ?></b>
            </div>
        </div>
        <?php
        if ( $post_url_with_params ) :
            ?>
        <div class="misc-pub-section cache-warmer-publish-box" id="cache-warmer-url-to-warm-with-request-params">
            <div class="cache-warmer-publish-box-image"></div>
            <div class="cache-warmer-publish-box-content">
                <?php esc_attr_e( 'URL (with added params):', 'cache-warmer' ); ?>
                <b><?php echo esc_attr( $post_url_with_params ); ?></b>
            </div>
        </div>
            <?php
        endif;
        ?>
        <div class="misc-pub-section cache-warmer-publish-box" id="cache-warmer-last-warmed">
            <div class="cache-warmer-publish-box-image"></div>
            <div class="cache-warmer-publish-box-content">
                <?php esc_attr_e( 'Last warmed (GMT):', 'cache-warmer' ); ?>
                <b><?php echo esc_attr( $post_data->log_date ); ?></b>
            </div>
        </div>
        <div class="misc-pub-section cache-warmer-publish-box" id="cache-warmer-last-result">
            <div class="cache-warmer-publish-box-image"></div>
            <div class="cache-warmer-publish-box-content">
                <?php esc_attr_e( 'Last result:', 'cache-warmer' ); ?>
                <b><?php echo $post_data->log_extra; ?></b>
            </div>
        </div>
        <div class="misc-pub-section cache-warmer-publish-box" id="cache-warmer-depth">
            <div class="cache-warmer-publish-box-image"></div>
            <div class="cache-warmer-publish-box-content">
                <?php esc_attr_e( 'Depth:', 'cache-warmer' ); ?>
                <b><?php echo esc_attr( $post_data->log_depth ); ?></b>
            </div>
        </div>
        <div class="misc-pub-section cache-warmer-publish-box" id="cache-warmer-time-cold">
            <div class="cache-warmer-publish-box-image"></div>
            <div class="cache-warmer-publish-box-content">
                <?php esc_attr_e( 'Loading time cold (last one):', 'cache-warmer' ); ?>
                <b><?php echo esc_attr( $post_data->log_time_spent ); ?></b>
            </div>
        </div>
        <div class="misc-pub-section cache-warmer-publish-box" id="cache-warmer-time-warm">
            <div class="cache-warmer-publish-box-image"></div>
            <div class="cache-warmer-publish-box-content">
                <?php esc_attr_e( 'Loading time warm:', 'cache-warmer' ); ?>
                <b><?php echo esc_attr( $post_data->log_time_afterwards ); ?></b>
            </div>
        </div>
        <div class="misc-pub-section cache-warmer-publish-box" id="cache-warmer-would-be-skipped">
            <div class="cache-warmer-publish-box-image"></div>
            <div class="cache-warmer-publish-box-content">
                <?php esc_attr_e( 'Would be excluded:', 'cache-warmer' ); ?>
                <b>
                    <?php
                    $excluded_pages_use_regex_match = '1' === Cache_Warmer::$options->get( 'setting-excluded-pages-use-regex-match' );

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

                    if ( $excluded_match ) {
                        /* translators: %s is the match. */
                        echo esc_html( sprintf( __( 'Yes, (because of "%s")', 'cache-warmer' ), $excluded_match ) );
                    } else {
                        esc_html_e( 'No', 'cache-warmer' );
                    }
                    ?>
                </b>
            </div>
        </div>
        <?php
    }
}
