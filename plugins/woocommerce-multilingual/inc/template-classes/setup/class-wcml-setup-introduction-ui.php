<?php

class WCML_Setup_Introduction_UI extends WCML_Templates_Factory {

	private $woocommerce_wpml;
	private $next_step_url;

	public function __construct( $woocommerce_wpml, $next_step_url ) {
		parent::__construct();

		$this->woocommerce_wpml = $woocommerce_wpml;
		$this->next_step_url    = $next_step_url;

	}

	public function get_model() {

		$model = [
			'strings'      => [
				'step_id'      => 'introduction_step',
				'heading'      => __( "Let's turn your WooCommerce shop multilingual", 'woocommerce-multilingual' ),
				'description1' => __( 'Thank you for choosing WooCommerce Multilingual & Multicurrency. We need to do a few upgrades to your site, so that it has everything needed to run multilingual.', 'woocommerce-multilingual' ),
				'description2' => [

					'title' => __( "We'll help you:", 'woocommerce-multilingual' ),
					'step1' => __( "Translate the 'store' pages", 'woocommerce-multilingual' ),
					'step2' => __( 'Choose which attributes to make translatable', 'woocommerce-multilingual' ),
					'step3' => __( 'Choose if you need multiple currencies', 'woocommerce-multilingual' ),

				],
				/* translators: %1$s and %2$s are opening and closing HTML strong tags */
				'description3' => sprintf( __( 'You can make these updates now, or later from the %1$sWooCommerce &raquo; WooCommerce Multilingual & Multicurrency%2$s menu.', 'woocommerce-multilingual' ), '<strong>', '</strong>' ),
				'continue'     => __( "Let's continue", 'woocommerce-multilingual' ),
				'later'        => __( "I'll do the setup later", 'woocommerce-multilingual' ),
			],
			'later_url'    => admin_url( 'admin.php?page=wpml-wcml&src=setup_later' ),
			'continue_url' => $this->next_step_url,
		];

		return $model;

	}

	protected function init_template_base_dir() {
		$this->template_paths = [
			WCML_PLUGIN_PATH . '/templates/',
		];
	}

	public function get_template() {
		return '/setup/introduction.twig';
	}


}
