<?php

use function WCML\functions\isStandAlone;

class WCML_Pointers{

	public function add_hooks() {
		add_action( 'admin_head', [ $this, 'setup' ] );
	}

	public function setup() {
		$current_screen = get_current_screen();

		if ( empty( $current_screen ) ) {
			return;
		}

		if ( ! WCML_Capabilities::canManageWcml() ) {
			return;
		}

		$tab        = $_GET['tab'] ?? '';
		$section    = $_GET['section'] ?? '';
		$isFullMode = ! isStandAlone();
		wp_register_style( 'wcml-pointers', WCML_PLUGIN_URL . '/res/css/wcml-pointers.css' );

		if ( $isFullMode && 'edit-product' === $current_screen->id ) {
			add_action( 'admin_footer', [ $this, 'add_products_translation_link' ], 100 );
		} elseif ( 'woocommerce_page_wc-settings' === $current_screen->id ) {
			if ( $isFullMode && 'shipping' === $tab && 'classes' === $section ) {
				add_action( 'admin_footer', [ $this, 'add_shipping_classes_translation_link' ] );
			} elseif ( ! $tab || 'general' === $tab ) {
				add_filter( 'woocommerce_general_settings', [ $this, 'add_multi_currency_link' ] );
			} elseif ( $isFullMode && 'advanced' === $tab ) {
				add_filter( 'woocommerce_settings_pages', [ $this, 'add_endpoints_translation_link' ] );
			}
		}
	}

	public function add_products_translation_link() {
		$link   = \WCML\Utilities\AdminUrl::getWPMLTMDashboardProducts();
		$name   = __( 'Translate WooCommerce products', 'woocommerce-multilingual' );
		$anchor = '<a class="button button-small button-wpml wcml-pointer-products_translation" href="{{ url }}">{{ text }}</a>';

		$this->add_link_with_jquery( $link, $name, $anchor, '.subsubsub' );
	}

	public function add_shipping_classes_translation_link() {
		if ( ! WCML_Terms::wpml_is_product_shipping_class_set_as_translated() ) {
			return;
		}

		$link   = \WCML\Utilities\AdminUrl::getWPMLTaxonomyTranslation( 'product_shipping_class' );
		$name   = __( 'Translate shipping classes', 'woocommerce-multilingual' );
		$anchor = '<a class="button button-small button-wpml wcml-pointer-shipping_classes_translation" href="{{ url }}">{{ text }}</a>';

		$this->add_link_with_jquery( $link, $name, $anchor, '.wc-shipping-classes', true );
	}

	/**
	 * @param array $settings
	 *
	 * @return array
	 */
	public function add_multi_currency_link( array $settings ) {
		$link = \WCML\Utilities\AdminUrl::getMultiCurrencyTab();
		$name = __( 'Configure multicurrency for multilingual sites', 'woocommerce-multilingual' );

		$anchor = '<a class="button button-small button-wpml wcml-pointer-multi_currency" href="{{ url }}">{{ text }}</a>';

		return $this->add_link_with_settings( $link, $name, 'pricing_options', $settings, $anchor );
	}

	/**
	 * @param array $settings
	 *
	 * @return array
	 */
	public function add_endpoints_translation_link( array $settings ) {
		$link = \WCML\Utilities\AdminUrl::getStoreURLTab();
		$name = __( 'Translate endpoints', 'woocommerce-multilingual' );

		$anchor = '<a class="button button-small button-wpml wcml-pointer-endpoints_translation" href="{{ url }}">{{ text }}</a>';

		return $this->add_link_with_settings( $link, $name, 'checkout_endpoint_options', $settings, $anchor );
	}

	/**
	 * @param string $link
	 * @param string $name
	 * @param string $anchor_template
	 * @param string $jquery_selector
	 * @param bool   $before
	 */
	private function add_link_with_jquery( $link, $name, $anchor_template, $jquery_selector, $before = false ) {
		wp_enqueue_style( 'wcml-pointers' );

		$method = $before ? 'before' : 'append';
		// @todo move to an enqueued script?.
		?>
			<script type="text/javascript">
				jQuery('<?php echo esc_js( $jquery_selector ); ?>').<?php echo $method; ?>('<?php echo $this->get_anchor( $link, $name, $anchor_template ); ?>');
			</script>
		<?php
	}

	/**
	 * @param string $link
	 * @param string $name
	 * @param string $setting_key
	 * @param array  $settings
	 * @param string $anchor_template
	 *
	 * @return array
	 */
	private function add_link_with_settings( $link, $name, $setting_key, array $settings, $anchor_template ) {
		wp_enqueue_style( 'wcml-pointers' );
		foreach ( $settings as $key => $value ) {
			if ( is_array( $value ) && isset( $value['id'], $value['desc'] ) && $setting_key === $value['id'] ) {

				$settings[ $key ]['desc'] = $this->get_anchor( $link, $name, $anchor_template ) . '<br />' . $value['desc'];
			}
		}

		return $settings;
	}

	/**
	 * @param string $link
	 * @param string $name
	 * @param string $anchor_template
	 *
	 * @return string
	 */
	private function get_anchor( $link, $name, $anchor_template ) {
		return str_replace( [ '{{ url }}', '{{ text }}' ], [ $link, $name ], $anchor_template );
	}
}
