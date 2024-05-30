<?php
/**
 * Assets
 *
 * Loads assets (JS, CSS), adds data for them.
 *
 * @package Cache-Warmer
 */

namespace Cache_Warmer\Assets;

/**
 * Assets class.
 */
final class Assets {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action(
            'current_screen',
            function () {
                $screen = get_current_screen();

                // Dashboard assets.

                if ( 'dashboard' === $screen->id ) {
                    Dashboard::init();
                }

                // Admin publish-box assets.

                if ( 'post' === $screen->base ) {
                    new Menu\Screens\Post;
                }
            }
        );
    }
}
