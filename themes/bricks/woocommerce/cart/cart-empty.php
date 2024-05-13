<?php
/**
 * Empty cart page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/cart-empty.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.0.1
 */

defined( 'ABSPATH' ) || exit;

$templates   = Bricks\Templates::get_templates_by_type( 'wc_cart_empty' );
$template_id = ! empty( $templates[0] ) ? $templates[0] : false;

// NOTE TODO: The woocommerce_cart_is_empty hook should be outside of the conditional or placed in a way that WooCommerce has access to it to render the page after cart emptied.
// This will come with additional messages and might need to be filtered if we're planning on excluding everything and just letting the empty cart template run (@see #30zbcqm).
// @since 1.7 - Place {do_action:woocommerce_cart_is_empty} in the user custom Empty Cart template will solve the issue.

// Render Bricks template
if ( $template_id ) {
	$elements = get_post_meta( $template_id, BRICKS_DB_PAGE_CONTENT, true );

	/**
	 * Add CSS class 'cart-empty' to the first Bricks element via 'bricks/element/settings'
	 *
	 * So empty cart shows when remove last item from cart.
	 *
	 * TODO: The sequence of the elements is not guaranteed and same as the order in the builder. Maybe a new section added last and dragged to the top.
	 * TODO: Maybe #862jued8a rearrange element function can be used here.
	 *
	 * @since 1.8
	 */
	if ( is_array( $elements ) && isset( $elements[0] ) ) {
		$element_id = $elements[0]['id'];

		add_filter(
			'bricks/element/render_attributes',
			function( $attributes, $key, $element ) use ( $element_id ) {
				if ( $element->id !== $element_id ) {
					return $attributes;
				}

				if ( isset( $attributes['_root']['class'] ) ) {
					$attributes['_root']['class'][] = 'cart-empty';
					/**
					 * As we removed wc_empty_cart_message on wooocommerce_cart_is_empty hook, we need to add the class here. (WooCommerce 8.0)
					 * Remove the wc_empty_cart_message is for the flexibility of the user to use the template as they want.
					 *
					 * @see Woocommerce_Helpers::repeated_wc_template_hooks()
					 * @since 1.8.6
					 */
					$attributes['_root']['class'][] = 'wc-empty-cart-message';
				} else {
					$attributes['_root']['class'] = [ 'cart-empty', 'wc-empty-cart-message' ];
				}

				return $attributes;
			},
			10,
			3
		);
	}

	$template_data = Bricks\Woocommerce::get_template_data_by_type( 'wc_cart_empty' );

	// Render template
	echo $template_data; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	/**
	 *
	 * $_GET['removed_item'] or $_SERVER['HTTP_REFERER'] is set equal to wc_get_cart_url()
	 *
	 * HTTP_REFERER a workaround as update cart quantity to zero is not detectable this data via AJAX + redirect (#862k6erqj)
	 *
	 * Add inline CSS to the page (WooCommerce fetches this data via AJAX)
	 *
	 * @since 1.8
	 */
	if (
		isset( $_GET['removed_item'] ) ||
		( isset( $_SERVER['HTTP_REFERER'] ) && $_SERVER['HTTP_REFERER'] === wc_get_cart_url() )
	) {
		// $inline_css = Bricks\Assets::generate_inline_css();
		$inline_css  = Bricks\Assets::$inline_css['global_classes'];
		$inline_css .= Bricks\Assets::$inline_css[ "template_$template_id" ];

		echo "<style id=\"bricks-cart-empty-inline-css\">$inline_css</style>"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

// Render WooCommerce template
else {
	/*
	* @hooked wc_empty_cart_message - 10
	*/
	do_action( 'woocommerce_cart_is_empty' );

	if ( wc_get_page_id( 'shop' ) > 0 ) { ?>
		<p class="return-to-shop">
			<a class="button wc-backward<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ); ?>">
				<?php
					/**
					 * Filter "Return To Shop" text.
					 *
					 * @since 4.6.0
					 * @param string $default_text Default text.
					 */
					echo esc_html( apply_filters( 'woocommerce_return_to_shop_text', __( 'Return to shop', 'woocommerce' ) ) );
				?>
			</a>
		</p>
		<?php
	}
}
