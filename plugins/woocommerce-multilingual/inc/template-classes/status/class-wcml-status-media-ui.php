<?php

class WCML_Status_Media_UI extends WCML_Templates_Factory {

	/**
	 * @var SitePress
	 */
	private $sitepress;

	/**
	 * WCML_Status_Media_UI constructor.
	 *
	 * @param SitePress $sitepress
	 */
	public function __construct( \WPML\Core\ISitePress $sitepress ) {
		parent::__construct();

		$this->sitepress = $sitepress;
	}

	public function get_model() {

		$media_plugin_name = 'WPML Media';
		$model             = [
			'strings'                  => [
				'heading'                     => __( 'Media', 'woocommerce-multilingual' ),
				'media_tip'                   => $media_plugin_name,
				/* translators: %s is a plugin name */
				'not_using_media_translation' => sprintf( __( '%s is not active.', 'woocommerce-multilingual' ), '<strong>' . $media_plugin_name . '</strong>' ),
				/* translators: %s is a plugin name */
				'why_use_media_translation'   => sprintf( __( '%s is not required in order to run WooCommerce Multilingual & Multicurrency but it’s recommended if you want to use separate product images and galleries for different languages.', 'woocommerce-multilingual' ), $media_plugin_name ),
				/* translators: %s is a plugin name */
				'using_media_translation'     => sprintf( __( '%s is installed and active.', 'woocommerce-multilingual' ), '<strong>' . $media_plugin_name . '</strong>' ),
			],
			'media_translation_active' => null !== $this->sitepress->get_wp_api()->constant( 'WPML_MEDIA_VERSION' ),
		];

		return $model;

	}

	public function init_template_base_dir() {
		$this->template_paths = [
			WCML_PLUGIN_PATH . '/templates/status/',
		];
	}

	public function get_template() {
		return 'media.twig';
	}

}
