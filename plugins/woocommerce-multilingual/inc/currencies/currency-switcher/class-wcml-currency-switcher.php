<?php

use WCML\MultiCurrency\Settings;
use WCML\MultiCurrency\Geolocation;
use WPML\Core\ISitePress;
use WCML\StandAlone\NullSitePress;

/**
 * Class WCML_Currency_Switcher
 *
 * Main class
 */
class WCML_Currency_Switcher {

	/** @var woocommerce_wpml $woocommerce_wpml */
	private $woocommerce_wpml;
	/** @var SitePress|NullSitePress $sitepress */
	private $sitepress;

	/**
	 * @param woocommerce_wpml        $woocommerce_wpml
	 * @param SitePress|NullSitePress $sitepress
	 */
	public function __construct( woocommerce_wpml $woocommerce_wpml, ISitePress $sitepress ) {
		$this->woocommerce_wpml = $woocommerce_wpml;
		$this->sitepress        = $sitepress;
	}

	public function add_hooks() {
		add_action( 'init', [ $this, 'on_init' ], 5 );
	}

	public function on_init() {
		add_action( 'wcml_currency_switcher', [ $this, 'wcml_currency_switcher' ] );
		// @deprecated 3.9
		add_action( 'currency_switcher', [ $this, 'currency_switcher' ] );
		add_shortcode( 'currency_switcher', [ $this, 'currency_switcher_shortcode' ] );
		// Built in currency switcher
		add_action( 'woocommerce_product_meta_start', [ $this, 'show_currency_switcher' ] );
		add_action( 'pre_update_option_sidebars_widgets', [ $this, 'update_option_sidebars_widgets' ], 10, 2 );
	}

	public static function get_settings( $switcher_id ) {
		global $woocommerce_wpml;

		$wcml_settings = $woocommerce_wpml->get_settings();

		return isset( $wcml_settings['currency_switchers'][ $switcher_id ] ) ? $wcml_settings['currency_switchers'][ $switcher_id ] : [];
	}

	public function currency_switcher_shortcode( $atts ) {

		$atts = (array) $atts;

		ob_start();
		$this->wcml_currency_switcher( $atts );
		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}

	public function wcml_currency_switcher( $args = [] ) {

		if ( ! is_array( $args ) ) {
			$args = [];
		}

		if ( ! isset( $args['switcher_id'] ) ) {
			$args['switcher_id'] = 'product';
		}

		$wcml_settings              = $this->woocommerce_wpml->get_settings();
		$currency_switcher_settings = [];

		if ( isset( $wcml_settings['currency_switchers'][ $args['switcher_id'] ] ) ) {
			$currency_switcher_settings = $wcml_settings['currency_switchers'][ $args['switcher_id'] ];
		}

		$args = $this->check_and_convert_switcher_style( $args );

		if (
			! isset( $args['preview'] ) &&
			! isset( $args['switcher_style'] )
		) {
			$args['switcher_style'] = isset( $currency_switcher_settings['switcher_style'] ) ? $currency_switcher_settings['switcher_style'] : $this->woocommerce_wpml->cs_templates->get_first_active();
		}

		if ( ! isset( $args['format'] ) ) {

			$args['format'] = '%name% (%symbol%) - %code%';
			if ( isset( $currency_switcher_settings['template'] ) && '' !== $currency_switcher_settings['template'] ) {
				$args['format'] = apply_filters( 'wpml_translate_single_string', $currency_switcher_settings['template'], 'woocommerce-multilingual', $args['switcher_id'] . '_switcher_format' );
			}
		}

		if ( ! isset( $args['color_scheme'] ) ) {
			$args['color_scheme'] = isset( $currency_switcher_settings['color_scheme'] ) ? $currency_switcher_settings['color_scheme'] : [];
		}

		$preview                = '';
		$show_currency_switcher = true;

		$display_custom_prices = isset( $wcml_settings['display_custom_prices'] ) && $wcml_settings['display_custom_prices'];
		$is_cart_or_checkout   = is_page( wc_get_page_id( 'cart' ) ) || is_page( wc_get_page_id( 'checkout' ) );

		if ( $display_custom_prices ) {
			if ( $is_cart_or_checkout ) {
				$show_currency_switcher = false;
			} elseif ( is_product() ) {
				$current_product_id  = get_post()->ID;
				$original_product_id = $this->woocommerce_wpml->products->get_original_product_id( $current_product_id );
				$use_custom_prices   = get_post_meta(
					$original_product_id,
					'_wcml_custom_prices_status',
					true
				);

				if ( ! $use_custom_prices ) {
					$show_currency_switcher = false;
				}
			}
		}

		if ( $show_currency_switcher ) {

			$currencies = Settings::getOrderedCurrencyCodes();

			if ( ! is_admin() ) {
				$currencies = $this->filter_allowed_currencies_on_frontend( $currencies );
			}

			if ( count( $currencies ) > 1 ) {
				$template = $this->woocommerce_wpml->cs_templates->get_template( $args['switcher_style'] );

				if ( $template ) {
					$this->woocommerce_wpml->cs_templates->maybe_late_enqueue_template( $args['switcher_style'], $template );
					$template->set_model( $this->get_model_data( $args, $currencies ) );
					$preview = $template->get_view();
				}
			} elseif ( is_admin() ) {
				$preview = '<i>' . esc_html__( "You haven't added any secondary currencies.", 'woocommerce-multilingual' ) . '</i>';
			}
		}

		if ( ! isset( $args['echo'] ) || $args['echo'] ) {
			echo $preview;
		} else {
			return $preview;
		}
	}

	/**
	 * @param array $currencies
	 *
	 * @return array
	 */
	private function filter_allowed_currencies_on_frontend( $currencies ){
		$ifDisallowedByLanguage = function( $currency ) {
			return ! Settings::isValidCurrencyForLang( $currency, $this->sitepress->get_current_language() );
		};

		$ifDisallowedByLocation = function( $currency ) {
			return ! Settings::isValidCurrencyByCountry( $currency, Geolocation::getUserCountry() );
		};

		return wpml_collect( $currencies )
			->reject( Settings::isModeByLanguage() ? $ifDisallowedByLanguage : $ifDisallowedByLocation )
			->toArray();
	}

	public function get_model_data( $args, $currencies ) {

		$css_classes = $this->get_css_classes( [ $args['switcher_style'], $args['switcher_id'], 'wcml_currency_switcher' ] );

		$format = isset( $args['format'] ) ? $args['format'] : '%name% (%symbol%) - %code%';

		$model = [
			'css_classes'       => $css_classes,
			'format'            => $format,
			'currencies'        => $currencies,
			'selected_currency' => $this->woocommerce_wpml->multi_currency->get_client_currency(),
		];

		return $model;
	}

	public function get_css_classes( $classes = [] ) {

		if ( $this->sitepress->is_rtl( $this->sitepress->get_current_language() ) ) {
			$classes[] = 'wcml-cs-rtl';
		}

		$classes = $this->add_user_agent_touch_device_classes( $classes );
		$classes = apply_filters( 'wcml_cs_template_css_classes', $classes );

		return implode( ' ', $classes );
	}

	public function add_user_agent_touch_device_classes( $classes ) {
		
		if ( wp_is_mobile() ) {
			$classes[] = 'wcml-cs-touch-device';
		}

		return $classes;
	}

	public function show_currency_switcher() {
		$settings = $this->woocommerce_wpml->get_settings();

		if (
			isset( $settings['currency_switcher_product_visibility'] ) &&
			$settings['currency_switcher_product_visibility'] === 1 &&
			is_product()
		) {
			echo( do_shortcode( '[currency_switcher]' ) );
		}
	}

	/**
	 * @deprecated 3.9
	 */
	public function currency_switcher( $args = [] ) {

		$this->wcml_currency_switcher( $args );
	}

	/**
	 * @return array
	 */
	public function get_registered_sidebars() {
		global $wp_registered_sidebars;

		return is_array( $wp_registered_sidebars ) ? $wp_registered_sidebars : [];
	}

	public function get_available_sidebars() {
		$sidebars      = $this->get_registered_sidebars();
		$wcml_settings = $this->woocommerce_wpml->get_settings();

		foreach ( $sidebars as $key => $sidebar ) {
			if ( isset( $wcml_settings['currency_switchers'][ $sidebar['id'] ] ) ) {
				unset( $sidebars[ $key ] );
			}
		}

		return $sidebars;
	}

	public function update_option_sidebars_widgets( $sidebars, $old_sidebars ) {

		foreach ( $sidebars as $sidebar => $widgets ) {
			if ( 'wp_inactive_widgets' === $sidebar ) {
				continue;
			}
			$found = false;
			if ( is_array( $widgets ) ) {
				foreach ( $widgets as $key => $widget_id ) {
					if ( strpos( $widget_id, WCML_Currency_Switcher_Widget::SLUG ) === 0 ) {
						if ( $found ) { // Only one CS widget instance per sidebar
							unset( $sidebars[ $sidebar ][ $key ] );
							continue;
						}
						$found = true;
					}
				}
			}

			$wcml_settings = $this->woocommerce_wpml->get_settings();
			if ( $found && empty( $wcml_settings['currency_switchers'][ $sidebar ] ) ) {
				$wcml_settings['currency_switchers'][ $sidebar ] = $this->get_switcher_default_settings();
			} elseif ( ! $found && isset( $wcml_settings['currency_switchers'][ $sidebar ] ) ) {
				unset( $wcml_settings['currency_switchers'][ $sidebar ] );
			}

			$this->woocommerce_wpml->update_settings( $wcml_settings );
		}

		return $sidebars;
	}

	public function get_switcher_default_settings() {
		return [
			'switcher_style' => 'wcml-dropdown',
			'widget_title'   => '',
			'template'       => '%name% (%symbol%) - %code%',
			'color_scheme'   => [
				'font_current_normal'       => '',
				'font_current_hover'        => '',
				'background_current_normal' => '',
				'background_current_hover'  => '',
				'font_other_normal'         => '',
				'font_other_hover'          => '',
				'background_other_normal'   => '',
				'background_other_hover'    => '',
				'border_normal'             => '',
			],
		];
	}

	// backward compatibility to convert switcher style for users who uses old parameters wcml-1874
	public function check_and_convert_switcher_style( $args ) {

		if ( isset( $args['switcher_style'] ) ) {
			if (
				'list' === $args['switcher_style'] &&
				isset( $args['orientation'] )
			) {
				if ( 'horizontal' === $args['orientation'] ) {
					$args['switcher_style'] = 'wcml-horizontal-list';
				} else {
					$args['switcher_style'] = 'wcml-vertical-list';
				}
				unset( $args['orientation'] );
			} elseif ( 'dropdown' === $args['switcher_style'] ) {
				$args['switcher_style'] = 'wcml-dropdown';
			}
		}

		return $args;
	}

}
