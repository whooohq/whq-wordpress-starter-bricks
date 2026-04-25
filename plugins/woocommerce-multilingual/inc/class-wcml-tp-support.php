<?php

use WCML\TranslationJob\Hooks;
use function WCML\functions\flushProductCachePrefixById;

class WCML_TP_Support {

	const CUSTOM_FIELD_NAME = 'wc_variation_field:';

	const DOWNLOADABLE_FILES_TRANSLATABLE_FIELDS = [ 'name', 'file' ];

	const PRIORITY_SAVE_VARIATION_CUSTOM_FIELDS_TRANSLATION = 20;

	const PACKAGE_IMAGE_KEY_PREFIX = 'image-id-';

	/** @var woocommerce_wpml */
	private $woocommerce_wpml;
	/** @var  wpdb */
	private $wpdb;
	/** @var WPML_Element_Translation_Package */
	private $tp;
	/** @var array */
	private $tm_settings;

	/**
	 * WCML_Attributes constructor.
	 *
	 * @param woocommerce_wpml $woocommerce_wpml
	 * @param wpdb $wpdb
	 * @param WPML_Element_Translation_Package $tp
	 * @param array $tm_settings
	 */
	public function __construct( woocommerce_wpml $woocommerce_wpml, wpdb $wpdb, WPML_Element_Translation_Package $tp, array $tm_settings ) {
		$this->woocommerce_wpml = $woocommerce_wpml;
		$this->wpdb             = $wpdb;
		$this->tp               = $tp;
		$this->tm_settings      = $tm_settings;
	}

	public function add_hooks() {
		add_filter( 'wpml_tm_translation_job_data', [
			$this,
			'append_custom_attributes_to_translation_package'
		], 10, 2 );
		add_action( 'wpml_translation_job_saved', [ $this, 'save_custom_attribute_translations' ], 10, 3 );

		add_filter( 'wpml_tm_translation_job_data', [
			$this,
			'append_variation_custom_fields_to_translation_package'
		], 10, 2 );

		add_filter( 'wpml_tm_translation_job_data', [
			$this,
			'append_variation_downloadable_fields_to_translation_package'
		], 10, 2 );

		add_filter( 'wpml_tm_translation_job_data', [
			$this,
			'append_simple_downloadable_fields_to_translation_package'
		], 10, 2 );

		add_action( 'wpml_pro_translation_completed', [
			$this,
			'save_variation_custom_fields_translations'
		], self::PRIORITY_SAVE_VARIATION_CUSTOM_FIELDS_TRANSLATION, 3 ); //after WCML_Products

		add_action(
			'wpml_pro_translation_completed',
			[
				$this,
				'flush_variable_product_cache_prefix',
			],
			self::PRIORITY_SAVE_VARIATION_CUSTOM_FIELDS_TRANSLATION + 10,
			3
		);

		add_filter( 'wpml_tm_translation_job_data', [ $this, 'append_images_to_translation_package' ], 10, 2 );
		add_action( 'wpml_translation_job_saved', [ $this, 'save_images_translations' ], 10, 3 );

		add_filter( 'wpml_custom_field_settings_override_lock_render', [ $this, 'set_wpml_term_custom_field_thumbnail_id_as_read_only' ], 10, 2 );
	}

	/**
	 * @param bool $override
	 * @param WPML_Custom_Field_Setting $setting
	 * @return bool
	 */
	public function set_wpml_term_custom_field_thumbnail_id_as_read_only( $override, $setting ) {
		if ( 'thumbnail_id' === $setting->get_index() && ( $setting instanceof WPML_Term_Custom_Field_Setting ) ) {
			if( WPML_COPY_CUSTOM_FIELD === $setting->status() ) {
				$setting->make_read_only();
			}
		}

		return $override;
	}

	/**
	 * @param array $package
	 * @param \WP_Post $post
	 *
	 * @return array
	 */
	public function append_custom_attributes_to_translation_package( $package, $post ) {
		if ( $this->isWpPostAWcProduct( $post ) ) {

			$product      = wc_get_product( $post->ID );

			if ( ! empty( $product ) ) {

				$attributes = $product->get_attributes();

				foreach ( $attributes as $attribute_key => $attribute ) {

					if ( $this->woocommerce_wpml->attributes->is_a_taxonomy( $attribute ) ) {
						continue;
					}

					$package['contents'][ 'wc_attribute_name:' . $attribute_key ] = [
						'translate' => 1,
						'data'      => $this->tp->encode_field_data( $attribute['name'] ),
						'format'    => 'base64'
					];
					$values                                                       = explode( '|', $attribute['value'] );
					$values                                                       = array_map( 'trim', $values );

					foreach ( $values as $value_key => $value ) {
						$package['contents'][ 'wc_attribute_value:' . $value_key . ':' . $attribute_key ] = [
							'translate' => 1,
							'data'      => $this->tp->encode_field_data( $value ),
							'format'    => 'base64'
						];
					}
				}
			}
		}

		return $package;
	}

	public function save_custom_attribute_translations( $post_id, $data, $job ) {

		$translated_attributes = [];
		$translated_labels     = $this->woocommerce_wpml->attributes->get_attr_label_translations( $post_id );

		foreach ( $data as $value ) {

			if ( $value['finished'] && isset( $value['field_type'] ) && strpos( $value['field_type'], 'wc_attribute_' ) === 0 ) {

				if ( strpos( $value['field_type'], 'wc_attribute_name:' ) === 0 ) {

					$exp           = explode( ':', $value['field_type'], 2 );
					$attribute_key = $exp[1];

					$translated_attributes[ $attribute_key ]['name'] = $value['data'];

				} else if ( strpos( $value['field_type'], 'wc_attribute_value:' ) === 0 ) {

					$exp           = explode( ':', $value['field_type'], 3 );
					$value_key     = $exp[1];
					$attribute_key = $exp[2];

					$translated_attributes[ $attribute_key ]['values'][ $value_key ] = $value['data'];

				}

			}

		}

		if ( $translated_attributes ) {

			$product_attributes = get_post_meta( $post_id, '_product_attributes', true ) ?: [];

			if( isset( $job->original_doc_id ) ){
				$original_post_id = $job->original_doc_id;
			}else{
				$original_post_id = $this->woocommerce_wpml->products->get_original_product_id( $post_id );
			}

			$original_attributes = get_post_meta( $original_post_id, '_product_attributes', true ) ?: [];

			foreach ( $translated_attributes as $attribute_key => $attribute ) {
				if( isset( $original_attributes[ $attribute_key ] ) ){
					$product_attributes[ $attribute_key ]          = $original_attributes[ $attribute_key ];
					$product_attributes[ $attribute_key ]['name']  = $attribute['name'];
					$product_attributes[ $attribute_key ]['value'] = isset( $attribute['values'] )
						? join( ' | ', $attribute['values'] )
						: $product_attributes[ $attribute_key ]['value'];

					$translated_labels[ $job->language_code ][ $attribute_key ] = $attribute['name'];
				}
			}

			update_post_meta( $post_id, '_product_attributes', $product_attributes );
			update_post_meta( $post_id, 'attr_label_translations', $translated_labels );
		}

	}

	/**
	 * @param int $variation_id
	 *
	 * @return array
	 */
	private function get_variation_custom_fields_to_translate( $variation_id ) {
		$is_field_translatable = function ( $meta_key ) {
			return isset( $this->tm_settings['custom_fields_translation'][ $meta_key ] )
			       && (int) $this->tm_settings['custom_fields_translation'][ $meta_key ] === WPML_TRANSLATE_CUSTOM_FIELD;
		};

		return wpml_collect( (array) get_post_custom_keys( $variation_id ) )
			->filter( $is_field_translatable )
			->toArray();
	}

	/**
	 * @param array $package
	 * @param \WP_Post $post
	 *
	 * @return array
	 */
	public function append_variation_custom_fields_to_translation_package( $package, $post ) {
		if ( $this->isWpPostAWcProduct( $post ) ) {

			/** @var WC_Product_Variable $product */
			$product = wc_get_product( $post->ID );

			$allowed_variations_types = apply_filters( 'wcml_xliff_allowed_variations_types', [ 'variable' ] );

			if ( $product instanceof WC_Product && in_array( $product->get_type(), $allowed_variations_types, true ) ) {

				$variations = $this->woocommerce_wpml->sync_variations_data->get_product_variations( $post->ID );

				foreach ( $variations as $variation ) {

					$meta_keys_to_translate = $this->get_variation_custom_fields_to_translate( $variation->ID );

					foreach ( $meta_keys_to_translate as $meta_key ){
						$meta_value = get_post_meta( $variation->ID, $meta_key, true );

						if ( $meta_value && !is_array( $meta_value ) ) {
							$package['contents'][ self::CUSTOM_FIELD_NAME.$meta_key.':' . $variation->ID ] = [
								'translate' => 1,
								'data'      => $this->tp->encode_field_data( $meta_value ),
								'format'    => 'base64'
							];
						}
					}
				}

			}

		}

		return $package;

	}

	/**
	 * @param array $package
	 * @param \WP_Post $post
	 *
	 * @return array
	 */
	public function append_variation_downloadable_fields_to_translation_package( $package, $post ) {
		if ( $this->isWpPostAWcProduct( $post ) ) {

			/** @var WC_Product_Variable $product */
			$product = wc_get_product( $post->ID );

			$allowed_variations_types = apply_filters( 'wcml_xliff_allowed_variations_types', [ 'variable' ] );

			if ( $product instanceof WC_Product && in_array( $product->get_type(), $allowed_variations_types, true ) ) {

				$variations = $this->woocommerce_wpml->sync_variations_data->get_product_variations( $post->ID );

				foreach ( $variations as $variation ) {

					$meta_keys_to_translate = $this->get_variation_custom_fields_to_translate( $variation->ID );

					if ( ! in_array( WCML_Downloadable_Products::DOWNLOADABLE_FILES_META, $meta_keys_to_translate ) ) {
						continue;
					}

					$meta_value = get_post_meta( $variation->ID, WCML_Downloadable_Products::DOWNLOADABLE_FILES_META, true );

					$fileNo = 0;
					foreach ( $meta_value as $downloadableId => $downloadableFile ) {
						foreach ( $downloadableFile as $name => $value ) {
							if ( ! in_array( $name, self::DOWNLOADABLE_FILES_TRANSLATABLE_FIELDS ) ) {
								continue;
							}

							$productKey = WCML_Downloadable_Products::buildDownloadableFileField( $fileNo, $downloadableId, $name );
							$productVariantKey = sprintf( '%s:%d', $productKey, $variation->ID );

							$package['contents'][ $productVariantKey ] = [
								'translate' => 1,
								'data'      => $this->tp->encode_field_data( $value ),
								'format'    => 'base64'
							];
						}
						$fileNo ++;
					}
				}
			}
		}
		return $package;
	}

	/**
	 * @param array $package
	 * @param \WP_Post $post
	 *
	 * @return array
	 */
	public function append_simple_downloadable_fields_to_translation_package( $package, $post ) {
		if ( $this->isWpPostAWcProduct( $post ) ) {
			if ( WCML_Downloadable_Products::isDownloadableFilesSetToUseSame( $post->ID ) ) {
				return $package;
			}

			/** @var WC_Product_Variable $product */
			$product = wc_get_product( $post->ID );

			$allowed_types = [ 'simple' ];

			if ( $product instanceof WC_Product && in_array( $product->get_type(), $allowed_types, true ) ) {

				$meta_value = get_post_meta( $post->ID, WCML_Downloadable_Products::DOWNLOADABLE_FILES_META, true );

				if ( ! is_array( $meta_value ) ) {
					return $package;
				}

				$fileNo = 0;
				foreach ( $meta_value as $downloadableId => $downloadableFile ) {
					foreach ( $downloadableFile as $name => $value ) {
						if ( ! in_array( $name, self::DOWNLOADABLE_FILES_TRANSLATABLE_FIELDS ) ) {
							continue;
						}

						$productKey = WCML_Downloadable_Products::buildDownloadableFileField( $fileNo, $downloadableId, $name );

						$package['contents'][ $productKey ] = [
							'translate' => 1,
							'data'      => $this->tp->encode_field_data( $value ),
							'format'    => 'base64'
						];
					}
					$fileNo ++;
				}
			}
		}

		return $package;
	}

	public function save_variation_custom_fields_translations( $post_id, $data, $job ) {
		if ( ! Hooks::isProduct( $job ) ) {
			return null;
		}

		$language = $job->language_code;

		foreach ( $data as $value ) {

			if ( $value['finished'] && isset( $value['field_type'] ) && strpos( $value['field_type'], self::CUSTOM_FIELD_NAME ) === 0 ) {

				$exp          = explode( ':', $value['field_type'], 3 );
				$meta_key     = $exp[1];
				$variation_id = $exp[2];

				if ( is_post_type_translated( 'product_variation' ) ) {
					$translated_variation_id = apply_filters( 'wpml_object_id', $variation_id, 'product_variation', false, $language );
				} else {
					global $wpml_post_translations;
					$translations            = $wpml_post_translations->get_element_translations( $variation_id );
					$translated_variation_id = $translations[ $language ] ?? false;
				}

				if ( $translated_variation_id ) {
					update_post_meta( $translated_variation_id, $meta_key, $value['data'] );
				}
			}
		}

	}

	/**
	 * @param array $package
	 * @param \WP_Post $post
	 *
	 * @return array
	 */
	public function append_images_to_translation_package( $package, $post ) {
		if ( $this->isWpPostAWcProduct( $post ) ) {

			$product_images = $this->woocommerce_wpml->media->product_images_ids( $post->ID );
			foreach ( $product_images as $image_id ) {
				/** @var stdClass */
				$attachment_data = $this->wpdb->get_row( $this->wpdb->prepare( "SELECT post_title,post_excerpt,post_content FROM {$this->wpdb->posts} WHERE ID = %d", $image_id ) );
				if ( ! is_object( $attachment_data ) ) {
					continue;
				}
				$alt_text = get_post_meta( $image_id, '_wp_attachment_image_alt', true );
				$alt_text = $alt_text ?: '';
				$this->add_to_package( $package, self::PACKAGE_IMAGE_KEY_PREFIX . $image_id . '-title', $attachment_data->post_title );
				$this->add_to_package( $package, self::PACKAGE_IMAGE_KEY_PREFIX . $image_id . '-caption', $attachment_data->post_excerpt );
				$this->add_to_package( $package, self::PACKAGE_IMAGE_KEY_PREFIX . $image_id . '-description', $attachment_data->post_content );
				$this->add_to_package( $package, self::PACKAGE_IMAGE_KEY_PREFIX . $image_id . '-alt-text', $alt_text );

			}
		}

		return $package;
	}

	public function save_images_translations( $post_id, $data, $job ) {

		$language = $job->language_code;

		$product_images = $this->woocommerce_wpml->media->product_images_ids( $job->original_doc_id );
		foreach ( $product_images as $image_id ) {
			$translated_prod_image = apply_filters( 'wpml_object_id', $image_id, 'attachment', false, $language );
			$image_data            = $this->get_image_data( $image_id, $data );
			if ( ! empty( $image_data ) ) {

				$translation = [];
				if ( isset( $image_data['title'] ) ) {
					$translation['post_title'] = $image_data['title'];
				}
				if ( isset( $image_data['description'] ) ) {
					$translation['post_content'] = $image_data['description'];
				}
				if ( isset( $image_data['caption'] ) ) {
					$translation['post_excerpt'] = $image_data['caption'];
				}

				if ( $translation ) {
					$this->wpdb->update( $this->wpdb->posts, $translation, [ 'id' => $translated_prod_image ] );
				}

				if ( isset( $image_data['alt-text'] ) ) {
					update_post_meta( $translated_prod_image, '_wp_attachment_image_alt', $image_data['alt-text'] );
				}
			}
		}
	}

	/**
	 * @param \WP_Post $post
	 */
	private function isWpPostAWcProduct( $post ): bool {
		return 'product' === $post->post_type;
	}

	private function get_image_data( $image_id, $data ) {
		$image_data = [];

		foreach ( $data as $value ) {
			if ( $value['finished'] && isset( $value['field_type'] ) ) {
				if ( strpos( $value['field_type'], self::PACKAGE_IMAGE_KEY_PREFIX . $image_id ) === 0 ) {
					if ( $value['field_type'] === self::PACKAGE_IMAGE_KEY_PREFIX . $image_id . '-title' ) {
						$image_data['title'] = $value['data'];
					}
					if ( $value['field_type'] === self::PACKAGE_IMAGE_KEY_PREFIX . $image_id . '-caption' ) {
						$image_data['caption'] = $value['data'];
					}
					if ( $value['field_type'] === self::PACKAGE_IMAGE_KEY_PREFIX . $image_id . '-description' ) {
						$image_data['description'] = $value['data'];
					}
					if ( $value['field_type'] === self::PACKAGE_IMAGE_KEY_PREFIX . $image_id . '-alt-text' ) {
						$image_data['alt-text'] = $value['data'];
					}
				}
			}
		}

		return $image_data;
	}

	private function add_to_package( &$package, $key, $data ) {
		$package['contents'][ $key ] = [
			'translate' => 1,
			'data'      => $this->tp->encode_field_data( $data ),
			'format'    => 'base64'
		];

	}

	/**
	 * @param int                                        $post_id
	 * @param array                                      $data
	 * @param bool|stdClass|WPML_Element_Translation_Job $job
	 * @return void|null
	 */
	public function flush_variable_product_cache_prefix( $post_id, $data, $job ) {
		if ( Hooks::isProduct( $job ) ) {
			flushProductCachePrefixById( $post_id );
		}
	}
}
