<?php

namespace WCML\TMDashboard;

use WPML\LIB\WP\Hooks as WPHooks;

use function WPML\FP\spreadArgs;

class Hooks implements \IWPML_REST_Action {

	public function add_hooks() {
		WPHooks::onFilter( 'wpml_tm_dashboard_posts', 10, 2 )
		       ->then( spreadArgs( [ $this, 'addProductThumbnail' ] ) );

		WPHooks::onFilter( 'wpml_tm_dashboard_item_sections_note' )
		       ->then( spreadArgs( [ $this, 'addFooterNoteToProductsBlock' ] ) );
	}

	/**
	 * @param array[] $posts Post[] from \WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\GetPosts\GetPostsController
	 * @param array   $searchCriteria SearchCriteriaRaw from \WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\GetPosts\GetPostsController
	 *
	 * @return array[]
	 */
	public function addProductThumbnail( $posts, $searchCriteria ) {
		if ( $searchCriteria['type'] !== 'product' ) {
			return $posts;
		}

		/**
		 * @param array $post Post from \WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\GetPosts\GetPostsController
		 *
		 * @return array
		 */
		$transformAddProductThumbnail = function ( array $post ) {
			$postId = (int) $post['id'];

			$post['image'] = $postId > 0 && has_post_thumbnail( $postId )
				? get_the_post_thumbnail_url( $postId, [ 150, 150 ] )
				: wc_placeholder_img_src();

			return $post;
		};

		return array_map( $transformAddProductThumbnail, $posts );
	}

	/**
	 * @param array[] $itemSections
	 *
	 * @return array[]
	 */
	public function addFooterNoteToProductsBlock( array $itemSections ): array {
		return array_map( function ( $itemSection ) {
			if ( 'post/product' === $itemSection['id'] ) {
				$itemSection['note'] = sprintf(
					/* translators: %1$s and %2$s are opening and closing HTML link tags */
					esc_html__( 'To translate product details, including their taxonomy, use this dashboard. If you need to translate taxonomy independently or adjust store and currency settings, visit: %1$sWPML Multilingual & Multicurrency for WooCommerce documentation%2$s.', 'woocommerce-multilingual' ),
					sprintf( '<a href="%s" target="_blank">', \WCML_Tracking_Link::getWcmlMainDoc() ),
					'</a>'
				);
			}

			return $itemSection;

		}, $itemSections );
	}
}
