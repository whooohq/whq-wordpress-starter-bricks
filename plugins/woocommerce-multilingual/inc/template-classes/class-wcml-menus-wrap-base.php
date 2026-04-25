<?php

abstract class WCML_Menu_Wrap_Base extends WCML_Templates_Factory {

	/**
	 * @var \woocommerce_wpml $woocommerce_wpml
	 */
	protected $woocommerce_wpml;

	public function __construct( woocommerce_wpml $woocommerce_wpml ) {
		parent::__construct();

		$this->woocommerce_wpml = $woocommerce_wpml;
	}

	/**
	 * @return array
	 */
	public function get_model() {
		return array_merge(
			[
				'can_operate_options' => current_user_can( 'wpml_operate_woocommerce_multilingual' ),
			],
			$this->get_child_model()
		);
	}

	/**
	 * @return array
	 */
	abstract protected function get_child_model();

	protected function init_template_base_dir() {
		$this->template_paths = [
			WCML_PLUGIN_PATH . '/templates/',
		];
	}

	public function get_template() {
		return 'menus-wrap.twig';
	}

}
