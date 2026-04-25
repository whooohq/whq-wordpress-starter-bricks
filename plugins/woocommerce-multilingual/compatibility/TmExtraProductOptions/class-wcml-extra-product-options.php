<?php

class WCML_Extra_Product_Options implements \IWPML_Action {

	const TRANSLATION_DOMAIN = 'wc_extra_product_options';

	public function add_hooks() {
		add_action( 'tm_before_extra_product_options', [ $this, 'inf_translate_product_page_strings' ] );
		add_action( 'tm_before_price_rules', [ $this, 'inf_translate_strings' ] );
	}

	public function inf_translate_strings() {
		// phpcs:ignore WordPress.VIP.SuperGlobalInputUsage.AccessDetected
		if ( isset( $_GET['page'] ) && 'tm-global-epo' === sanitize_text_field( $_GET['page'] ) ) {
			$this->inf_message( 'Options Form' );
		}
	}

	public function inf_translate_product_page_strings() {
		$this->inf_message( 'Product' );
	}

	/**
	 * @param string $text Deprecated
	 */
	public function inf_message( $text ) {
		$dashboardUrl  = \WCML\Utilities\AdminUrl::getWPMLTMDashboardStringDomain( self::TRANSLATION_DOMAIN );
		$message       = '<div><p class="icl_cyan_box">';
		$message      .= sprintf(
			/* translators: %1$s and %2$s are opening and closing HTML link tags */
			esc_html__( 'To translate custom product fields, save your changes here first, then go to the %2$sTranslation Dashboard%3$s.', 'woocommerce-multilingual' ),
			'<a href="' . esc_url( $dashboardUrl ) . '">',
			'</a>'
		);
		$message .= '</p></div>';

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $message;
	}
}
