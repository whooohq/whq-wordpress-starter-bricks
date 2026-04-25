<?php
/**
 * Class to handle posts warming enqueue.
 *
 * @package Cache-Warmer
 */

namespace Cache_Warmer;

use Exception;
use WP_Post;

/**
 * Manages Posts Enqueue.
 */
final class Posts_Enqueue {

    /**
     * Cache warmer hook name for posts enqueue handling interval.
     */
    const INTERVAL_HOOK_NAME = 'cache_warmer_process_posts_enqueue';

    /**
     * Constructor.
     *
     * @throws Exception Exception.
     */
    public function __construct() {
        if ( Cache_Warmer::$options->get( 'cache-warmer-setting-warm-up-posts' ) ) {
            add_action( 'transition_post_status', [ $this, 'populate_enqueue' ], 10, 3 );
        }
        add_action( $this::INTERVAL_HOOK_NAME, [ $this, 'handle_enqueue' ] );
    }

    /**
     * Populates posts enqueue.
     *
     * @param string  $new_status New post status.
     * @param string  $old_status Old post status.
     * @param WP_Post $post       Post object.
     *
     * @throws Exception Exception.
     */
    public function populate_enqueue( $new_status, $old_status, $post ) {
        if ( 'publish' === $new_status ) {
            $posts_enqueue = Cache_Warmer::$options->get( 'cache-warmer-posts-enqueue' );
            if ( ! in_array( $post->ID, $posts_enqueue, true ) ) {
                $posts_enqueue[] = $post->ID;
                Cache_Warmer::$options->set( 'cache-warmer-posts-enqueue', $posts_enqueue );
            }
        }
    }

    /**
     * Handles the posts enqueue.
     *
     * @throws Exception Exception.
     */
    public function handle_enqueue() {
        $posts_enqueue = Cache_Warmer::$options->get( 'cache-warmer-posts-enqueue' );
        if ( $posts_enqueue ) {
            $posts_urls = array_unique(
                array_filter(
                    array_map(
                        'get_permalink',
                        $posts_enqueue
                    )
                )
            );
            Cache_Warmer::$options->delete( 'cache-warmer-posts-enqueue' );
            AJAX::start_warm_up( false, true, $posts_urls );
        }
    }
}
