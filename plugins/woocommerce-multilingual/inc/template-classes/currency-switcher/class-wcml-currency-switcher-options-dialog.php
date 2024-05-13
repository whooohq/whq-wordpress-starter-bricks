<?php

class WCML_Currency_Switcher_Options_Dialog extends WCML_Templates_Factory {

	private $woocommerce_wpml;
	private $args;

	/**
	 * WCML_Currency_Switcher_Options_Dialog constructor.
	 *
	 * @param array            $args
	 * @param woocommerce_wpml $woocommerce_wpml
	 */
	public function __construct( $args, $woocommerce_wpml ) {
		// @todo Cover by tests, required for wcml-3037.
		parent::__construct();

		$this->woocommerce_wpml = $woocommerce_wpml;
		$this->args             = $args;

		add_action( 'wcml_before_currency_switcher_options', [ $this, 'render' ] );
	}

	public function get_model() {

		$model = [

			'args'          => $this->args,
			'color_schemes' => [
				'clear_all' => [
					'label' => __( 'Clear all colors', 'woocommerce-multilingual' ),
				],
				'gray'      => [
					'label' => __( 'Gray', 'woocommerce-multilingual' ),
				],
				'white'     => [
					'label' => __( 'White', 'woocommerce-multilingual' ),
				],
				'blue'      => [
					'label' => __( 'Blue', 'woocommerce-multilingual' ),
				],
			],
			'options'       => [
				'border'             => __( 'Border', 'woocommerce-multilingual' ),
				'font_current'       => __( 'Current currency font color', 'woocommerce-multilingual' ),
				'font_other'         => __( 'Other currency font color', 'woocommerce-multilingual' ),
				'background_current' => __( 'Current currency background color', 'woocommerce-multilingual' ),
				'background_other'   => __( 'Other currency background color', 'woocommerce-multilingual' ),
			],
			'form'          => [
				'switcher_style'      => [
					'label'        => __( 'Currency switcher style', 'woocommerce-multilingual' ),
					'core'         => __( 'Core', 'woocommerce-multilingual' ),
					'custom'       => __( 'Custom', 'woocommerce-multilingual' ),
					'allowed_tags' => __( 'Allowed HTML tags: <img> <span> <u> <strong> <em>', 'woocommerce-multilingual' ),
				],
				'template'            => [
					'label'           => __( 'Template for currency switcher', 'woocommerce-multilingual' ),
					'parameters'      => __( 'Available parameters', 'woocommerce-multilingual' ),
					/* translators: unidentified placeholders... */
					'template_tip'    => __( 'Default: %name% (%1$symbol%) - %2$code%', 'woocommerce-multilingual' ),
					'parameters_list' => '%code%, %symbol%, %name%',
				],
				'colors'              => [
					'label'                => __( 'Currency switcher colors', 'woocommerce-multilingual' ),
					'theme'                => __( 'Color theme', 'woocommerce-multilingual' ),
					'normal'               => __( 'Normal', 'woocommerce-multilingual' ),
					'hover'                => __( 'Hover', 'woocommerce-multilingual' ),
					'select_option_choose' => __( 'Select a preset', 'woocommerce-multilingual' ),
				],
				'widgets'             => [
					'widget_area'        => __( 'Widget area', 'woocommerce-multilingual' ),
					'widget_title'       => __( 'Widget title', 'woocommerce-multilingual' ),
					'choose_label'       => __( '-- Choose a widget area --', 'woocommerce-multilingual' ),
					'available_sidebars' => $this->woocommerce_wpml->multi_currency->currency_switcher->get_available_sidebars(),
				],
				'preview'             => __( 'Preview', 'woocommerce-multilingual' ),
				'preview_nonce'       => wp_create_nonce( 'wcml_currencies_switcher_preview' ),
				'save_settings_nonce' => wp_create_nonce( 'wcml_currencies_switcher_save_settings' ),
				'cancel'              => __( 'Cancel', 'woocommerce-multilingual' ),
				'save'                => __( 'Save', 'woocommerce-multilingual' ),
			],
		];

		return $model;
	}

	public static function currency_switcher_pre_selected_colors() {

		$defaults = [];

		$defaults['clear_all'] = [
			'font_current_normal'       => '',
			'font_current_hover'        => '',
			'background_current_normal' => '',
			'background_current_hover'  => '',
			'font_other_normal'         => '',
			'font_other_hover'          => '',
			'background_other_normal'   => '',
			'background_other_hover'    => '',
			'border_normal'             => '',
		];

		$defaults['gray'] = [
			'font_current_normal'       => '#222222',
			'font_current_hover'        => '#000000',
			'background_current_normal' => '#eeeeee',
			'background_current_hover'  => '#eeeeee',
			'font_other_normal'         => '#222222',
			'font_other_hover'          => '#000000',
			'background_other_normal'   => '#e5e5e5',
			'background_other_hover'    => '#eeeeee',
			'border_normal'             => '#cdcdcd',
		];

		$defaults['white'] = [
			'font_current_normal'       => '#444444',
			'font_current_hover'        => '#000000',
			'background_current_normal' => '#ffffff',
			'background_current_hover'  => '#eeeeee',
			'font_other_normal'         => '#444444',
			'font_other_hover'          => '#000000',
			'background_other_normal'   => '#ffffff',
			'background_other_hover'    => '#eeeeee',
			'border_normal'             => '#cdcdcd',
		];

		$defaults['blue'] = [
			'font_current_normal'       => '#ffffff',
			'font_current_hover'        => '#000000',
			'background_current_normal' => '#95bedd',
			'background_current_hover'  => '#95bedd',
			'font_other_normal'         => '#000000',
			'font_other_hover'          => '#ffffff',
			'background_other_normal'   => '#cbddeb',
			'background_other_hover'    => '#95bedd',
			'border_normal'             => '#0099cc',
		];

		return $defaults;
	}

	public function render() {
		echo $this->get_view();
	}

	protected function init_template_base_dir() {
		$this->template_paths = [
			WCML_PLUGIN_PATH . '/templates/multi-currency/',
		];
	}

	public function get_template() {
		return 'currency-switcher-options-dialog.twig';
	}
}
