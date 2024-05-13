<?php

class WCML_WC_Admin_Duplicate_Product {

	/**
	 * @var woocommerce_wpml
	 */
	private $woocommerce_wpml;
	/**
	 * @var SitePress
	 */
	private $sitepress;

	/** @var wpdb $wpdb */
	private $wpdb;

	/**
	 * WCML_WC_Admin_Duplicate_Product constructor.
	 *
	 * @param woocommerce_wpml $woocommerce_wpml
	 * @param SitePress        $sitepress
	 * @param wpdb             $wpdb
	 */
	public function __construct( $woocommerce_wpml, $sitepress, $wpdb ) {
		$this->woocommerce_wpml = $woocommerce_wpml;
		$this->sitepress        = $sitepress;
		$this->wpdb             = $wpdb;

		add_action( 'woocommerce_product_duplicate', [ $this, 'woocommerce_duplicate_product' ], 10, 2 );
	}

	/**
	 * @param int|\WC_Product $new_id
	 * @param \WC_Product     $post
	 *
	 * @return array|null
	 */
	public function woocommerce_duplicate_product( $new_id, $post ) {
		$duplicated_products = [];

		$product_id = $post->get_id();
		if ( ! is_numeric( $new_id ) ) {
			$new_id = $new_id->get_id();
		}
		$element_type = apply_filters( 'wpml_element_type', get_post_type( $product_id ) );

		// duplicate original first.
		$trid      = $this->sitepress->get_element_trid( $product_id, $element_type );
		$orig_id   = $this->sitepress->get_original_element_id_by_trid( $trid );
		$orig_lang = $this->woocommerce_wpml->products->get_original_product_language( $product_id );

		if ( $orig_id == $product_id ) {
			$this->sitepress->set_element_language_details( $new_id, $element_type, false, $orig_lang );
			$new_trid    = $this->sitepress->get_element_trid( $new_id, $element_type );
			$new_orig_id = $new_id;
		} else {
			$post_to_duplicate = $this->wpdb->get_row( $this->wpdb->prepare( "SELECT * FROM {$this->wpdb->posts} WHERE ID=%d", $orig_id ) );
			if ( ! empty( $post_to_duplicate ) ) {

				$new_orig_id = $this->wc_duplicate_product( $post_to_duplicate );

				do_action( 'wcml_after_duplicate_product', $new_id, $post_to_duplicate );
				$this->sitepress->set_element_language_details( $new_orig_id, $element_type, false, $orig_lang );
				$new_trid = $this->sitepress->get_element_trid( $new_orig_id, $element_type );
				if ( get_post_meta( $orig_id, '_icl_lang_duplicate_of' ) ) {
					update_post_meta( $new_id, '_icl_lang_duplicate_of', $new_orig_id );
				}
				$this->sitepress->set_element_language_details( $new_id, $element_type, $new_trid, $this->sitepress->get_current_language() );
			}
		}

		if ( ! isset( $new_orig_id, $new_trid ) ) {
			return null;
		}

		// Set language info for variations.
		if ( $children_products = get_children( 'post_parent=' . $new_orig_id . '&post_type=product_variation' ) ) {
			foreach ( $children_products as $child ) {
				$this->sitepress->set_element_language_details( $child->ID, 'post_product_variation', false, $orig_lang );
			}
		}

		$translations                        = $this->sitepress->get_element_translations( $trid, $element_type );
		$duplicated_products['translations'] = [];
		if ( $translations ) {

			foreach ( $translations as $translation ) {
				if ( ! $translation->original && $translation->element_id != $product_id ) {
					$post_to_duplicate = $this->wpdb->get_row( $this->wpdb->prepare( "SELECT * FROM {$this->wpdb->posts} WHERE ID=%d", $translation->element_id ) );

					if ( ! empty( $post_to_duplicate ) ) {
						$new_id     = $this->wc_duplicate_product( $post_to_duplicate );
						$new_id_obj = get_post( $new_id );
						$new_slug   = wp_unique_post_slug(
							sanitize_title( $new_id_obj->post_title ),
							$new_id,
							get_post_status( $post_to_duplicate ),
							get_post_type( $post_to_duplicate ),
							$new_id_obj->post_parent
						);

						$this->wpdb->update(
							$this->wpdb->posts,
							[
								'post_name'   => $new_slug,
								'post_status' => 'draft',
							],
							[ 'ID' => $new_id ]
						);

						do_action( 'wcml_after_duplicate_product', $new_id, $post_to_duplicate );
						$this->sitepress->set_element_language_details( $new_id, $element_type, $new_trid, $translation->language_code );
						if ( get_post_meta( $translation->element_id, '_icl_lang_duplicate_of' ) ) {
							update_post_meta( $new_id, '_icl_lang_duplicate_of', $new_orig_id );
						}
						$duplicated_products['translations'][] = $new_id;
					}
				}
			}
		}

		$duplicated_products['original'] = $new_orig_id;

		return $duplicated_products;
	}

	public function wc_duplicate_product( $post_to_duplicate ) {

		$product                       = wc_get_product( $post_to_duplicate->ID );
		$wc_duplicate_product_instance = new WC_Admin_Duplicate_Product();

		$duplicate   = $wc_duplicate_product_instance->product_duplicate( $product );
		$new_orig_id = $duplicate->get_id();

		return $new_orig_id;
	}
}
