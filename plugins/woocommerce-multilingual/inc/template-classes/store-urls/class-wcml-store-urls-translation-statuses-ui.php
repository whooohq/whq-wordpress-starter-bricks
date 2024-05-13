<?php

use WPML\Core\Twig_SimpleFunction;

/**
 * Created by OnTheGo Systems
 */
class WCML_Store_URLs_Translation_Statuses_UI extends WCML_Templates_Factory {

	private $base;
	private $active_languages;
	private $value;
	private $woocommerce_wpml;
	private $sitepress;


	/**
	 * WCML_Store_URLs_Translation_Statuses_UI constructor.
	 *
	 * @param string           $base
	 * @param array            $active_languages
	 * @param bool             $value
	 * @param woocommerce_wpml $woocommerce_wpml
	 * @param SitePress        $sitepress
	 */
	public function __construct( $base, $active_languages, $value, $woocommerce_wpml, $sitepress ) {
		// @todo Cover by tests, required for wcml-3037.
		parent::__construct();

		$this->base             = $base;
		$this->active_languages = $active_languages;
		$this->value            = $value;
		$this->woocommerce_wpml = $woocommerce_wpml;
		$this->sitepress        = $sitepress;
	}

	public function init_twig_functions() {
		$function = new Twig_SimpleFunction( 'wcml_base_edit_dialog', [ $this, 'render_base_edit_dialog' ] );

		/** @var WPML\Core\Twig\Environment */
		$twig = $this->get_twig();
		$twig->addFunction( $function );
	}

	public function get_model() {
		$this->init_twig_functions();

		$source_language = $this->woocommerce_wpml->url_translation->get_source_slug_language( $this->base );

		$model = [
			'base'             => $this->base,
			'active_languages' => $this->get_languages_info(),
			'source_language'  => $source_language,
			'value'            => $this->value,
			'strings'          => [
				'orig_lang' => __( 'Original language', 'woocommerce-multilingual' ),
				'update'    => __( 'Update translation', 'woocommerce-multilingual' ),
				'add'       => __( 'Add translation', 'woocommerce-multilingual' ),
				'edit'      => __( 'Edit translation', 'woocommerce-multilingual' ),
			],
			'nonces'           => [
				'edit_base'   => wp_nonce_field( 'wcml_edit_base', 'wcml_edit_base_nonce', true, false ),
				'update_base' => wp_nonce_field( 'wcml_update_base_translation', 'wcml_update_base_nonce', true, false ),
			],
		];

		return $model;
	}

	public function get_languages_info() {

		$languages = $this->active_languages;

		foreach ( $languages as $key => $language ) {

			if ( $this->base == 'shop' ) {
				$translated_base = apply_filters( 'translate_object_id', wc_get_page_id( 'shop' ), 'page', false, $language['code'] );

			} else {
				$translated_base_info = $this->woocommerce_wpml->url_translation->get_base_translation( $this->base, $language['code'] );

				if ( isset( $translated_base_info['needs_update'] ) ) {
					$languages[ $key ]['status'] = 'upd';
				} else {
					$translated_base = $translated_base_info['translated_base'];
				}
			}

			if ( isset( $translated_base ) && $translated_base ) {
				$languages[ $key ]['status'] = 'edit';
			} else {
				$languages[ $key ]['status'] = 'add';
			}
		}

		return $languages;

	}

	public function init_template_base_dir() {
		$this->template_paths = [
			WCML_PLUGIN_PATH . '/templates/store-urls/',
		];
	}

	public function get_template() {
		return 'translation-statuses.twig';
	}

	public function render_base_edit_dialog( $base, $language ) {

		$edit_base = new WCML_Store_URLs_Edit_Base_UI( $base, $language, $this->woocommerce_wpml, $this->sitepress );
		return $edit_base->get_view();
	}
}
