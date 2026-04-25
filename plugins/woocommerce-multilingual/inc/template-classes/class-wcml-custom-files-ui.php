<?php

class WCML_Custom_Files_UI extends WCML_Templates_Factory {

	private $product_id;
	private $is_variation;

	/**
	 * @param int  $product_id
	 * @param bool $is_variation
	 */
	public function __construct( $product_id, $is_variation = false ) {
		// @todo Cover by tests, required for wcml-3037.
		parent::__construct();

		$this->product_id   = $product_id;
		$this->is_variation = $is_variation;
	}

	public function get_model() {

		$model = [
			'product_id'   => $this->product_id,
			'is_variation' => $this->is_variation,
			'nonce'        => wp_nonce_field( 'wcml_save_files_option', 'wcml_save_files_option_nonce', true, false ),
			'sync_custom'  => get_post_meta( $this->product_id, 'wcml_sync_files', true ),
			'strings'      => [
				'use_custom' => __( 'Use custom settings for translations download files', 'woocommerce-multilingual' ),
				'use_same'   => __( 'Use the same files for translations', 'woocommerce-multilingual' ),
				'separate'   => __( 'Add separate download files for translations when you translate this product', 'woocommerce-multilingual' ),
			],
		];

		return $model;
	}

	public function init_template_base_dir() {
		$this->template_paths = [
			WCML_PLUGIN_PATH . '/templates/',
		];
	}

	public function get_template() {
		return 'custom-files.twig';
	}
}
