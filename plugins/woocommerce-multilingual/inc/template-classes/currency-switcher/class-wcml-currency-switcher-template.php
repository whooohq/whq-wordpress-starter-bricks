<?php

use WCML\Multicurrency\CurrencySwitcher\CurrencySwitcherComponent;
use WCML\Multicurrency\CurrencySwitcher\CurrencySwitcherTemplateInterface;
use WPML\Core\Twig_SimpleFunction;

class WCML_Currency_Switcher_Template extends WCML_Templates_Factory implements CurrencySwitcherTemplateInterface {

	const TEMPLATE_FILENAME_LEGACY_TWIG = 'template.twig';

	/**
	 * backward compatibility
	 * @deprcated 5.5.0
	 */
	const FILENAME = self::TEMPLATE_FILENAME_LEGACY_TWIG;

	/* @var array $template */
	private $template;

	/* @var string $prefix */
	private $prefix = 'wcml-cs-';

	/** @var array|null $model */
	private $model;

	/**
	 * @param array $template_data
	 */
	public function __construct( $template_data ) {
		$this->template = $this->format_data( $template_data );

		if ( array_key_exists( 'template_string', $this->template ) ) {
			$this->template_string = $this->template['template_string'];
		}

		$functions = [
			new Twig_SimpleFunction( 'get_formatted_price', [ $this, 'get_formatted_price' ] ),
		];

		parent::__construct( $functions );
	}

	/**
	 * @param array|mixed $model
	 */
	public function set_model( $model ) {
		$this->model = is_array( $model ) ? $model : [ $model ];
	}

	/**
	 * @return array
	 */
	public function get_model() {
		return $this->model;
	}

	public function render() {
		echo $this->get_view();
	}

	protected function before_render() {
		$templateSetup = $this->get_template_data();

		/**
		 * Hook fired when a currency switcher is using legacy TWIG template
		 *
		 * @param string $templateSlug Template slug
		 */
		do_action( 'wpml_currency_switcher_uses_twig_templates', $templateSetup['slug'] ?? '' );
	}

	static public function get_formatted_price( $currency, $format ) {
		$wc_currencies = get_woocommerce_currencies();

		$currency_format = preg_replace(
			[ '#%name%#', '#%symbol%#', '#%code%#' ],
			[
				$wc_currencies[ $currency ],
				get_woocommerce_currency_symbol( $currency ),
				$currency,

			],
			$format
		);

		return $currency_format;
	}

	/**
	 * Make sure some elements are of array type
	 *
	 * @param array $template_data
	 *
	 * @return array
	 */
	private function format_data( $template_data ) {
		foreach ( [ 'path', 'js', 'css' ] as $k ) {
			$template_data[ $k ] = $template_data[ $k ] ?? [];
			$template_data[ $k ] = is_array( $template_data[ $k ] ) ? $template_data[ $k ] : [ $template_data[ $k ] ];
		}

		return $template_data;
	}

	public function get_styles( bool $with_version = false ): array {
		return $with_version
			? array_map( [ CurrencySwitcherComponent::class, 'addResourceVersion' ], $this->template['css'] )
			: $this->template['css'];
	}

	public function has_styles(): bool {
		return ! empty( $this->template['css'] );
	}

	public function get_scripts( bool $with_version = false ): array {
		return $with_version
			? array_map( [ CurrencySwitcherComponent::class, 'addResourceVersion' ], $this->template['js'] )
			: $this->template['js'];
	}

	public function get_resource_handler( string $index ): string {
		$slug = $this->template['slug'] ?? '';
		$prefix = $this->is_core() ? '' : $this->prefix;

		return $prefix . $slug . '-' . $index;
	}

	public function get_inline_style_handler() {
		$count = count( $this->template['css'] );

		return $count > 0 ? $this->get_resource_handler( $count - 1 ) : null;
	}

	protected function init_template_base_dir() {
		$this->template_paths = $this->template['path'];
	}

	/**
	 * @return string Template filename
	 */
	public function get_template() {
		$template = self::FILENAME;

		if ( isset( $this->template_string ) ) {
			$template = $this->template_string;
		} elseif ( array_key_exists( 'filename', $this->template ) ) {
			$template = $this->template['filename'];
		}

		return $template;
	}

	public function get_template_data(): array {
		return $this->template;
	}

	/**
	 * return bool
	 */
	public function is_core() {
		return isset( $this->template['is_core'] ) ? (bool) $this->template['is_core'] : false;
	}

	public function is_path_valid(): bool {
		foreach ( $this->template_paths as $path ) {
			if ( ! file_exists( $path ) ) {
				return false;
			}
		}

		return true;
	}
}
