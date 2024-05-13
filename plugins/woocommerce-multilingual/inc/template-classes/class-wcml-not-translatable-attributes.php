<?php

/**
 * Created by OnTheGo Systems
 */
class WCML_Not_Translatable_Attributes extends WCML_Templates_Factory {

	private $attr_id;
	private $woocommerce_wpml;

	/**
	 * WCML_Not_Translatable_Attributes constructor.
	 *
	 * @param int              $attr_id
	 * @param woocommerce_wpml $woocommerce_wpml
	 */
	public function __construct( $attr_id, $woocommerce_wpml ) {
		// @todo Cover by tests, required for wcml-3037.
		parent::__construct();

		$this->attr_id          = $attr_id;
		$this->woocommerce_wpml = $woocommerce_wpml;
	}

	public function get_model() {

		$model = [
			'checked'   => $this->is_translatable(),
			'edit_mode' => $this->attr_id ? true : false,
			'strings'   => [
				'label'       => __( 'Translatable?', 'woocommerce-multilingual' ),
				'description' => __( 'Enable this if you want to translate attribute values with WooCommerce Multilingual & Multicurrency', 'woocommerce-multilingual' ),
				'notice'      => __( 'Existing translations and variations associated will be deleted.', 'woocommerce-multilingual' ),
			],
		];

		return $model;
	}

	public function is_translatable() {
		global $wpdb;

		$attribute_to_edit = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . 'woocommerce_attribute_taxonomies WHERE attribute_id = %d', $this->attr_id ) );
		if ( $attribute_to_edit ) {
			$att_name = wc_attribute_taxonomy_name( $attribute_to_edit->attribute_name );

			$wcml_settings = $this->woocommerce_wpml->get_settings();

			return isset( $wcml_settings['attributes_settings'][ $att_name ] ) ? $wcml_settings['attributes_settings'][ $att_name ] : true;
		}

		return true;
	}

	public function init_template_base_dir() {
		$this->template_paths = [
			WCML_PLUGIN_PATH . '/templates/',
		];
	}

	public function get_template() {
		return 'trnsl-attributes.twig';
	}
}
