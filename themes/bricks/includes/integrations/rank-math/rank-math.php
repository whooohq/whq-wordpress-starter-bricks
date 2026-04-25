<?php
namespace Bricks\Integrations\Rank_Math;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Rank_Math {
	public function __construct() {}

	public static function register() {
		if ( ! class_exists( 'RankMath' ) ) {
			return;
		}

		$instance = new self();

		add_action( 'wp_enqueue_scripts', [ $instance, 'wp_enqueue_scripts' ], 10 );
		add_action( 'admin_enqueue_scripts', [ $instance, 'wp_enqueue_scripts' ], 10 );

		add_filter( 'rank_math/sitemap/content_before_parse_html_images', [ $instance, 'add_bricks_content_for_parse_html_images' ], 10, 2 );

		/**
		 * Add Bricks data to the Rank Math description
		 *
		 * NOTE: Not yet in use due to performance issues.
		 *
		 * @since 1.9.6
		 */
		// add_filter( 'rank_math/frontend/description', [ $instance, 'modify_rank_math_description' ], 10, 1 );
	}

	/**
	 * Feed Rank Math with the rendered Bricks data to build the images sitemap
	 *
	 * @since 1.5.5
	 */
	public function add_bricks_content_for_parse_html_images( $content, $post_id ) {
		// Set the post_id in 'Database' to avoid errors
		\Bricks\Database::$page_data['preview_or_post_id'] = $post_id;

		if ( ! \Bricks\Helpers::render_with_bricks( $post_id ) ) {
			return $content;
		}

		$bricks_data = get_post_meta( $post_id, BRICKS_DB_PAGE_CONTENT, true );

		// Page has Bricks data: Render it & feed Rank Math logic
		if ( $bricks_data ) {
			return \Bricks\Frontend::render_data( $bricks_data );
		}

		return $content;
	}

	/**
	 * Add Bricks integration with Rank Math to the builder
	 *
	 * @since 1.3.2
	 */
	public function wp_enqueue_scripts( $hook_suffix ) {
		if ( bricks_is_builder() || ( is_admin() && $hook_suffix == 'post.php' ) ) {
			// NOTE: rank-math-analyzer is not enqueued by default in the builder.
			wp_enqueue_script( 'bricks-rank-math', BRICKS_URL_ASSETS . 'js/integrations/rank-math.min.js', [ 'wp-hooks', 'rank-math-analyzer' ], filemtime( BRICKS_PATH_ASSETS . 'js/integrations/rank-math.min.js' ), true );

			if ( bricks_is_builder() ) {
				$nonce = wp_create_nonce( 'bricks-nonce-builder' );
			} elseif ( is_admin() ) {
				$nonce = wp_create_nonce( 'bricks-nonce-admin' );
			} else {
				$nonce = wp_create_nonce( 'bricks-nonce' );
			}

			wp_localize_script(
				'bricks-rank-math',
				'bricksRankMath',
				[
					'postId'           => get_the_ID(),
					'nonce'            => $nonce,
					'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
					'renderWithBricks' => \Bricks\Helpers::is_post_type_supported() && \Bricks\Helpers::render_with_bricks()
				]
			);
		}
	}

	/**
	 * Modify the Rank Math description to use Bricks content if needed
	 *
	 * @since 1.9.6
	 */
	public function modify_rank_math_description( $description ) {
		if ( ! is_singular() || $description ) {
			return $description;
		}

		global $post;

		$desc = \RankMath\Helper::get_settings( "titles.pt_{$post->post_type}_description" );

		if ( $desc !== '%excerpt%' ) {
			return $description;
		}

		$bricks_data = get_post_meta( $post->ID, BRICKS_DB_PAGE_CONTENT, true );

		if ( ! $bricks_data ) {
			return $description;
		}

		// NOTE: Not yet in use as this causes performance issues on large sites.
		$content = \Bricks\Frontend::render_data( $bricks_data );

		// Extract the first paragraph content
		$first_paragraph = $this->extract_first_paragraph( $content );

		return $first_paragraph ? \RankMath\Helpers\Str::truncate( $first_paragraph, 200 ) : '';
	}

	/**
	 * Extracts the first paragraph from the given HTML content
	 *
	 * @param string $html The HTML content.
	 * @return string The text content of the first paragraph or an empty string.
	 */
	private function extract_first_paragraph( $html ) {
		if ( preg_match( '/<p[^>]*>(.*?)<\/p>/', $html, $matches ) ) {
			return wp_strip_all_tags( $matches[1] );
		}
		return '';
	}
}
