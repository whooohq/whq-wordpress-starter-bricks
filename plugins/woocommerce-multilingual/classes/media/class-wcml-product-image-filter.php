<?php

class WCML_Product_Image_Filter implements IWPML_Action {

	/**
	 * @var WPML_Translation_Element_Factory
	 */
	private $translation_element_factory;
	/** @var WPML_WP_Cache */
	private $wpml_cache;

	public function __construct( WPML_Translation_Element_Factory $translation_element_factory, $wpml_cache = null ) {
		$this->translation_element_factory = $translation_element_factory;

		$cache_group      = 'WCML_Product_Image_Filter';
		$this->wpml_cache = $wpml_cache;
		if ( null === $wpml_cache ) {
			$this->wpml_cache = new WPML_WP_Cache( $cache_group );
		}
	}

	public function add_hooks() {
		add_filter( 'get_post_metadata', [ $this, 'localize_image_id' ], 11, 3 );
	}

	public function localize_image_id( $value, $object_id, $meta_key ) {

		$image_id = false;
		if ( ! $value && '_thumbnail_id' === $meta_key &&
			 in_array( get_post_type( $object_id ), [ 'product', 'product_variation' ] )
		) {

			$cache_key = $object_id . '_thumbnail_id';
			$found     = false;
			$image_id  = $this->wpml_cache->get( $cache_key, $found );

			if ( ! $image_id ) {
				remove_filter( 'get_post_metadata', [ $this, 'localize_image_id' ], 11 );

				$meta_value = get_post_meta( $object_id, '_thumbnail_id', true );
				if ( empty( $meta_value ) ) {
					$post_element   = $this->translation_element_factory->create( $object_id, 'post' );
					$source_element = $post_element->get_source_element();
					if ( null !== $source_element ) {
						$image_id = get_post_meta( $source_element->get_id(), '_thumbnail_id', true );
					}
				}
				add_filter( 'get_post_metadata', [ $this, 'localize_image_id' ], 11, 3 );

				$this->wpml_cache->set( $cache_key, $image_id );
			}
		}

		return $image_id ? [ $image_id ] : $value;
	}

}
