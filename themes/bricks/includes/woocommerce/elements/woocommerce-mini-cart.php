<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Woocommerce_Mini_Cart extends Element {
	public $category = 'woocommerce';
	public $name     = 'woocommerce-mini-cart';
	public $icon     = 'ti-shopping-cart';
	public $scripts  = [ 'bricksWooRefreshCartFragments' ];

	/**
	 * Enqueue wc-cart-fragments script if WooCommerce version is >= 7.8
	 *
	 * @since 1.8.1
	 *
	 * @see https://developer.woocommerce.com/2023/06/13/woocommerce-7-8-released/#mini-cart-performance-improvement
	 */
	public function enqueue_scripts() {
		// Enqueue WooCommerce cart fragments script if WooCommerce version is >= 7.8
		if ( version_compare( WC()->version, '7.8', '>=' ) && ! wp_script_is( 'wc-cart-fragments', 'enqueued' ) ) {
			wp_enqueue_script( 'wc-cart-fragments' );
		}
	}

	public function get_label() {
		return esc_html__( 'Mini cart', 'bricks' );
	}

	public function set_control_groups() {
		$this->control_groups['cartCount'] = [
			'title' => esc_html__( 'Cart count', 'bricks' ),
			'tab'   => 'content',
		];

		$this->control_groups['cartSubtotal'] = [
			'title' => esc_html__( 'Cart subtotal', 'bricks' ),
			'tab'   => 'content',
		];

		$this->control_groups['cartDetails'] = [
			'title' => esc_html__( 'Cart Details', 'bricks' ),
			'tab'   => 'content',
		];
	}

	public function set_controls() {
		// ICON
		$this->controls['icon'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Icon', 'bricks' ),
			'type'     => 'icon',
			'default'  => [
				'library' => 'themify',
				'icon'    => 'ti-shopping-cart',
			],
			'rerender' => true,
		];

		$this->controls['iconTypography'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Icon typography', 'bricks' ),
			'type'     => 'typography',
			'css'      => [
				[
					'property' => 'font',
					'selector' => '.mini-cart-link i',
				],
			],
			'required' => [ 'icon.icon', '!=', '' ],
		];

		// @since 1.6.1 - Open cart on ajax add to cart. Support native WooCommerce AJAX add to cart and Bricks AJAX add to cart.
		if ( Woocommerce::enabled_ajax_add_to_cart() ) {
			$this->controls['openMiniCartOnAddedToCart'] = [
				'tab'   => 'content',
				'label' => esc_html__( 'Open on add to cart (AJAX)', 'bricks' ),
				'type'  => 'checkbox',
			];
		}

		// CART COUNT
		$this->controls['cartCount'] = [
			'tab'         => 'content',
			'group'       => 'cartCount',
			'label'       => esc_html__( 'Visibility', 'bricks' ),
			'type'        => 'select',
			'options'     => [
				'none' => esc_html__( 'Hide', 'bricks' ) . ' (' . esc_html__( 'Always', 'bricks' ) . ')',
				'flex' => esc_html__( 'Show', 'bricks' ) . ' (' . esc_html__( 'Always', 'bricks' ) . ')',
			],
			'placeholder' => esc_html__( 'Hide if empty', 'bricks' ),
			'inline'      => true,
			'css'         => [
				[
					'property'  => 'display',
					'selector'  => '.cart-count',
					'important' => true,
				],
			],
		];

		$this->controls['cartCountBackground'] = [
			'tab'      => 'content',
			'group'    => 'cartCount',
			'label'    => esc_html__( 'Background color', 'bricks' ),
			'type'     => 'color',
			'css'      => [
				[
					'property' => 'background-color',
					'selector' => '.cart-count',
				]
			],
			'required' => [ 'cartCount', '!=', 'none' ],
		];

		$this->controls['cartCountBorder'] = [
			'tab'      => 'content',
			'group'    => 'cartCount',
			'type'     => 'border',
			'label'    => esc_html__( 'Border', 'bricks' ),
			'css'      => [
				[
					'property' => 'border',
					'selector' => '.mini-cart-link .cart-icon .cart-count',
				],
			],
			'required' => [ 'cartCount', '!=', 'none' ],
		];

		$this->controls['cartCountTypography'] = [
			'tab'      => 'content',
			'group'    => 'cartCount',
			'label'    => esc_html__( 'Typography', 'bricks' ),
			'type'     => 'typography',
			'css'      => [
				[
					'property' => 'font',
					'selector' => '.mini-cart-link .cart-icon .cart-count',
				],
			],
			'required' => [ 'cartCount', '!=', 'none' ],
		];

		$this->controls['cartCountTransform'] = [
			'tab'      => 'style',
			'group'    => 'cartCount',
			'type'     => 'transform',
			'label'    => esc_html__( 'Transform', 'bricks' ),
			'css'      => [
				[
					'property' => 'transform',
					'selector' => '.mini-cart-link .cart-icon .cart-count',
				],
			],
			'required' => [
				[ 'cartCount', '!=', 'none' ],
				[ 'hideCartDetails', '=', '' ],
				[ 'cartDetailsOffCanvas', '!=', [ 'top', 'bottom' ] ],
			],
		];

		$this->controls['cartCountHeight'] = [
			'tab'      => 'content',
			'group'    => 'cartCount',
			'label'    => esc_html__( 'Height', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'height',
					'selector' => '.cart-count',
				],
			],
			'required' => [ 'cartCount', '!=', 'none' ],
		];

		$this->controls['cartCountWidth'] = [
			'tab'      => 'content',
			'group'    => 'cartCount',
			'label'    => esc_html__( 'Width', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'width',
					'selector' => '.cart-count',
				],
			],
			'required' => [ 'cartCount', '!=', 'none' ],
		];

		$this->controls['cartCountTop'] = [
			'tab'      => 'style',
			'group'    => 'cartCount',
			'label'    => esc_html__( 'Top', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'top',
					'selector' => '.mini-cart-link .cart-icon .cart-count',
				],
			],
			'required' => [ 'cartCount', '!=', 'none' ],
		];

		$this->controls['cartCountRight'] = [
			'tab'      => 'style',
			'group'    => 'cartCount',
			'label'    => esc_html__( 'Right', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'right',
					'selector' => '.mini-cart-link .cart-icon .cart-count',
				],
			],
			'required' => [ 'cartCount', '!=', 'none' ],
		];

		$this->controls['cartCountBottom'] = [
			'tab'      => 'style',
			'group'    => 'cartCount',
			'label'    => esc_html__( 'Bottom', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'bottom',
					'selector' => '.mini-cart-link .cart-icon .cart-count',
				],
			],
			'required' => [ 'cartCount', '!=', 'none' ],
		];

		$this->controls['cartCountLeft'] = [
			'tab'      => 'style',
			'group'    => 'cartCount',
			'label'    => esc_html__( 'Left', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'left',
					'selector' => '.mini-cart-link .cart-icon .cart-count',
				],
			],
			'required' => [ 'cartCount', '!=', 'none' ],
		];

		// SUBTOTAL

		$this->controls['subtotalPosition'] = [
			'tab'         => 'content',
			'group'       => 'cartSubtotal',
			'label'       => esc_html__( 'Position', 'bricks' ),
			'type'        => 'select',
			'options'     => [
				'row'         => esc_html__( 'Right', 'bricks' ),
				'row-reverse' => esc_html__( 'Left', 'bricks' ),
			],
			'css'         => [
				[
					'property' => 'flex-direction',
					'selector' => '.mini-cart-link',
				],
			],
			'inline'      => true,
			'placeholder' => esc_html__( 'Hide', 'bricks' ),
			'rerender'    => true,
		];

		$this->controls['subtotalGap'] = [
			'tab'      => 'content',
			'group'    => 'cartSubtotal',
			'label'    => esc_html__( 'Gap', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'inline'   => true,
			'css'      => [
				[
					'property' => 'gap',
					'selector' => '.mini-cart-link',
				],
			],
			'required' => [ 'subtotalPosition', '!=', '' ],
		];

		$this->controls['subtotalTypography'] = [
			'tab'      => 'content',
			'group'    => 'cartSubtotal',
			'label'    => esc_html__( 'Typography', 'bricks' ),
			'type'     => 'typography',
			'css'      => [
				[
					'property' => 'font',
					'selector' => '.mini-cart-link .cart-subtotal',
				],
			],
			'required' => [ 'subtotalPosition', '!=', '' ],
		];

		// CART DETAIL (FRAGMENTS)
		$this->controls['hideCartDetails'] = [
			'tab'         => 'content',
			'group'       => 'cartDetails',
			'label'       => esc_html__( 'Hide', 'bricks' ),
			'type'        => 'checkbox',
			'description' => esc_html__( 'Hide cart details to link directly to the cart.', 'bricks' ),
		];

		// @since 1.9.4
		$this->controls['skipClickOutside'] = [
			'tab'      => 'content',
			'group'    => 'cartDetails',
			'label'    => esc_html__( 'Don\'t close on click outside mini cart', 'bricks' ),
			'type'     => 'checkbox',
			'required' => [ 'hideCartDetails', '=', '' ],
		];

		$this->controls['cartDetailsOffCanvas'] = [
			'tab'         => 'content',
			'group'       => 'cartDetails',
			'label'       => esc_html__( 'Off-Canvas', 'bricks' ),
			'type'        => 'select',
			'options'     => [
				'top'    => esc_html__( 'Top', 'bricks' ),
				'right'  => esc_html__( 'Right', 'bricks' ),
				'bottom' => esc_html__( 'Bottom', 'bricks' ),
				'left'   => esc_html__( 'Left', 'bricks' ),
			],
			'inline'      => true,
			'rerender'    => true,
			'placeholder' => esc_html__( 'Disabled', 'bricks' ),
			'required'    => [ 'hideCartDetails', '=', '' ],
		];

		$this->controls['cartDetailsHeight'] = [
			'tab'      => 'content',
			'group'    => 'cartDetails',
			'label'    => esc_html__( 'Height', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'selector' => '.cart-detail',
					'property' => 'height',
				],
			],
			'required' => [
				[ 'hideCartDetails', '=', '' ],
				[ 'cartDetailsOffCanvas', '!=', [ 'top', 'bottom' ] ],
			],
		];

		$this->controls['cartDetailsWidth'] = [
			'tab'         => 'content',
			'group'       => 'cartDetails',
			'label'       => esc_html__( 'Width', 'bricks' ),
			'type'        => 'number',
			'units'       => true,
			'css'         => [
				[
					'selector' => '.cart-detail',
					'property' => 'width',
				],
			],
			'placeholder' => '400px',
			'required'    => [
				[ 'hideCartDetails', '=', '' ],
				[ 'cartDetailsOffCanvas', '!=', [ 'top', 'bottom' ] ],
			],
		];

		$this->controls['cartDetailsImageWidth'] = [
			'tab'         => 'style',
			'group'       => 'cartDetails',
			'type'        => 'number',
			'units'       => true,
			'label'       => esc_html__( 'Image width', 'bricks' ),
			'css'         => [
				[
					'selector' => '.cart-detail img',
					'property' => 'width',
				],
			],
			'placeholder' => '60px',
			'required'    => [ 'hideCartDetails', '=', '' ],
		];

		$this->controls['cartDetailsPadding'] = [
			'tab'         => 'content',
			'group'       => 'cartDetails',
			'label'       => esc_html__( 'Padding', 'bricks' ),
			'type'        => 'spacing',
			'css'         => [
				[
					'selector' => '.widget_shopping_cart_content',
					'property' => 'padding',
				],
			],
			'placeholder' => [
				'top'    => 30,
				'right'  => 30,
				'bottom' => 30,
				'left'   => 30,
			],
			'required'    => [
				[ 'hideCartDetails', '=', '' ],
			],
		];

		$this->controls['cartDetailsPosition'] = [
			'tab'      => 'style',
			'group'    => 'cartDetails',
			'type'     => 'dimensions',
			'label'    => esc_html__( 'Position', 'bricks' ),
			'css'      => [
				[
					'selector' => '.cart-detail',
				],
			],
			'required' => [
				[ 'hideCartDetails', '=', '' ],
				[ 'cartDetailsOffCanvas', '=', '' ],
			],
		];

		$this->controls['cartDetailsTransform'] = [
			'tab'      => 'style',
			'group'    => 'cartDetails',
			'type'     => 'transform',
			'label'    => esc_html__( 'Transform', 'bricks' ),
			'css'      => [
				[
					'selector' => '.cart-detail',
					'property' => 'transform',
				],
			],
			'required' => [
				[ 'hideCartDetails', '=', '' ],
				[ 'cartDetailsOffCanvas', '!=', [ 'top', 'bottom' ] ],
			],
		];

		$this->controls['cartDetailsBackground'] = [
			'tab'      => 'style',
			'group'    => 'cartDetails',
			'type'     => 'color',
			'label'    => esc_html__( 'Background', 'bricks' ),
			'css'      => [
				[
					'selector' => '.cart-detail',
					'property' => 'background-color',
				],
			],
			'required' => [ 'hideCartDetails', '=', '' ],
		];

		$this->controls['cartDetailsBorder'] = [
			'tab'      => 'style',
			'group'    => 'cartDetails',
			'type'     => 'border',
			'label'    => esc_html__( 'Border', 'bricks' ),
			'css'      => [
				[
					'selector' => '.cart-detail',
					'property' => 'border',
				],
			],
			'required' => [ 'hideCartDetails', '=', '' ],
		];

		$this->controls['cartDetailsBoxShadow'] = [
			'tab'      => 'style',
			'group'    => 'cartDetails',
			'type'     => 'box-shadow',
			'label'    => esc_html__( 'Box shadow', 'bricks' ),
			'css'      => [
				[
					'selector' => '.cart-detail',
					'property' => 'box-shadow',
				],
			],
			'required' => [ 'hideCartDetails', '=', '' ],
		];

		$this->controls['cartDetailsTypography'] = [
			'tab'      => 'style',
			'group'    => 'cartDetails',
			'type'     => 'typography',
			'label'    => esc_html__( 'Typography', 'bricks' ) . ' (' . esc_html__( 'Title', 'bricks' ) . ')',
			'css'      => [
				[
					'selector' => '.woocommerce-mini-cart-item a:not(.remove)',
					'property' => 'font',
				],
			],
			'required' => [ 'hideCartDetails', '=', '' ],
		];

		$this->controls['cartDetailsTypographyQuantity'] = [
			'tab'      => 'style',
			'group'    => 'cartDetails',
			'type'     => 'typography',
			'label'    => esc_html__( 'Typography', 'bricks' ) . ' (' . esc_html__( 'Quantity', 'bricks' ) . ')',
			'css'      => [
				[
					'selector' => '.woocommerce-mini-cart-item .quantity',
					'property' => 'font',
				],
			],
			'required' => [ 'hideCartDetails', '=', '' ],
		];

		// BUTTONS

		$this->controls['_buttonSeparator'] = [
			'tab'      => 'content',
			'group'    => 'cartDetails',
			'label'    => esc_html__( 'Buttons', 'bricks' ),
			'type'     => 'separator',
			'required' => [ 'hideCartDetails', '=', '' ],
		];

		$this->controls['buttonBackgroundColor'] = [
			'tab'      => 'content',
			'group'    => 'cartDetails',
			'label'    => esc_html__( 'Background color', 'bricks' ),
			'type'     => 'color',
			'css'      => [
				[
					'selector' => '.cart-detail .woocommerce-mini-cart__buttons .button',
					'property' => 'background-color',
				],
			],
			'required' => [ 'hideCartDetails', '=', '' ],
		];

		$this->controls['buttonBorder'] = [
			'tab'      => 'content',
			'group'    => 'cartDetails',
			'label'    => esc_html__( 'Border', 'bricks' ),
			'type'     => 'border',
			'css'      => [
				[
					'selector' => '.cart-detail .woocommerce-mini-cart__buttons .button',
					'property' => 'border',
				],
			],
			'required' => [ 'hideCartDetails', '=', '' ],
		];

		$this->controls['buttonTypography'] = [
			'tab'      => 'content',
			'group'    => 'cartDetails',
			'label'    => esc_html__( 'Typography', 'bricks' ),
			'type'     => 'typography',
			'css'      => [
				[
					'selector' => '.cart-detail .woocommerce-mini-cart__buttons .button',
					'property' => 'font',
				],
			],
			'required' => [ 'hideCartDetails', '=', '' ],
		];

		// CLOSE (@since 1.7.1)

		$this->controls['closeSeparator'] = [
			'type'     => 'separator',
			'label'    => esc_html__( 'Close', 'bricks' ),
			'tab'      => 'content',
			'group'    => 'cartDetails',
			'required' => [ 'hideCartDetails', '=', '' ],
		];

		$this->controls['cartDetailsCloseIcon'] = [
			'tab'      => 'content',
			'group'    => 'cartDetails',
			'label'    => esc_html__( 'Icon', 'bricks' ),
			'type'     => 'icon',
			'required' => [ 'hideCartDetails', '=', '' ],
		];

		$this->controls['cartDetailsCloseTypography'] = [
			'tab'      => 'content',
			'group'    => 'cartDetails',
			'label'    => esc_html__( 'Typography', 'bricks' ),
			'type'     => 'typography',
			'css'      => [
				[
					'property' => 'font',
					'selector' => '.bricks-mini-cart-close > *',
				],
			],
			'exclude'  => [
				'font-family',
				'font-weight',
				'font-style',
				'text-align',
				'text-decoration',
				'text-transform',
				'line-height',
				'letter-spacing',
			],
			'required' => [
				[ 'hideCartDetails', '=', '' ],
				[ 'cartDetailsCloseIcon', '!=', '' ],
			],
		];

		$this->controls['cartDetailsClosePosition'] = [
			'tab'         => 'content',
			'group'       => 'cartDetails',
			'type'        => 'dimensions',
			'label'       => esc_html__( 'Position', 'bricks' ),
			'css'         => [
				[
					'selector' => '.bricks-mini-cart-close',
				],
			],
			'placeholder' => [
				'top'   => 0,
				'right' => 0,
			],
			'required'    => [
				[ 'hideCartDetails', '=', '' ],
				[ 'cartDetailsCloseIcon', '!=', '' ],
			],
		];

		$this->controls['cartDetailsClosePadding'] = [
			'tab'         => 'content',
			'group'       => 'cartDetails',
			'label'       => esc_html__( 'Padding', 'bricks' ),
			'type'        => 'spacing',
			'css'         => [
				[
					'property' => 'padding',
					'selector' => '.bricks-mini-cart-close',
				],
			],
			'placeholder' => [
				'top'    => 10,
				'right'  => 10,
				'bottom' => 10,
				'left'   => 10,
			],
			'required'    => [
				[ 'hideCartDetails', '=', '' ],
				[ 'cartDetailsCloseIcon', '!=', '' ],
			],
		];

		$this->controls['cartDetailsCloseBackgroundColor'] = [
			'tab'      => 'content',
			'group'    => 'cartDetails',
			'label'    => esc_html__( 'Background color', 'bricks' ),
			'type'     => 'color',
			'css'      => [
				[
					'property' => 'background-color',
					'selector' => '.bricks-mini-cart-close',
				],
			],
			'required' => [
				[ 'hideCartDetails', '=', '' ],
				[ 'cartDetailsCloseIcon', '!=', '' ],
			],
		];

		$this->controls['cartDetailsCloseBorder'] = [
			'tab'      => 'content',
			'group'    => 'cartDetails',
			'label'    => esc_html__( 'Border', 'bricks' ),
			'type'     => 'border',
			'css'      => [
				[
					'property' => 'border',
					'selector' => '.bricks-mini-cart-close',
				],
			],
			'required' => [
				[ 'hideCartDetails', '=', '' ],
				[ 'cartDetailsCloseIcon', '!=', '' ],
			],
		];
	}

	public function render() {
		$settings          = $this->settings;
		$cart_icon         = isset( $settings['icon'] ) ? self::render_icon( $settings['icon'] ) : false;
		$cart_count        = is_object( WC()->cart ) ? WC()->cart->get_cart_contents_count() : 0;
		$subtotal_position = isset( $settings['subtotalPosition'] ) ? $settings['subtotalPosition'] : false;
		$subtotal          = is_object( WC()->cart ) && $subtotal_position ? WC()->cart->get_cart_subtotal() : 0;

		// Hide empty cart (if not explicitly set to always show or hide)
		if ( empty( $settings['cartCount'] ) && $cart_count == 0 ) {
			$this->set_attribute( '_root', 'class', 'hide-empty-count' );
		}

		// Mini cart link
		$this->set_attribute( 'a', 'href', isset( $settings['hideCartDetails'] ) ? wc_get_cart_url() : '#' );

		$link_classes = [ 'mini-cart-link', 'toggle-button' ];

		if ( ! isset( $settings['hideCartDetails'] ) ) {
			$link_classes[] = 'bricks-woo-toggle';
		}

		$this->set_attribute( 'a', 'class', $link_classes );

		// Mini cart link aria label (@since 1.9.5)
		$mini_cart_aria_label = isset( $settings['hideCartDetails'] ) ? esc_attr__( 'View cart', 'bricks' ) : esc_attr__( 'Toggle mini cart', 'bricks' );
		$this->set_attribute( 'a', 'aria-label', $mini_cart_aria_label );

		// Open cart on added to cart (AJAX)
		if ( isset( $settings['openMiniCartOnAddedToCart'] ) && Woocommerce::enabled_ajax_add_to_cart() ) {
			$this->set_attribute( 'a', 'data-open-on-add-to-cart', 'true' );
		}

		$cart_detail_classes = [ 'cart-detail' ];

		if ( ! empty( $settings['cartDetailsOffCanvas'] ) ) {
			$cart_detail_classes[] = "off-canvas {$settings['cartDetailsOffCanvas']}";
		}

		// Target specific mini cart (needed when using multiple mini cart elements on one page)
		$cart_detail_target_class = "cart-detail-{$this->id}";

		$cart_detail_classes[] = $cart_detail_target_class;

		$this->set_attribute( 'cart-detail', 'class', $cart_detail_classes );

		// Skip click outside close mini cart event
		if ( isset( $settings['skipClickOutside'] ) ) {
			$this->set_attribute( 'cart-detail', 'data-skip-click-outside', 'true' );
		}

		echo "<div {$this->render_attributes( '_root' )}>"; ?>

		<a <?php echo $this->render_attributes( 'a' ); ?> data-toggle-target=".<?php echo $cart_detail_target_class; ?>">
			<span class="cart-icon">
				<?php echo $cart_icon; ?>
				<span class="cart-count"><?php echo absint( $cart_count ); ?></span>
			</span>

			<?php if ( $subtotal_position ) { ?>
				<span class="cart-subtotal"><?php echo $subtotal; ?></span>
			<?php } ?>
		</a>

		<?php if ( ! isset( $settings['hideCartDetails'] ) ) { ?>
		<div <?php echo $this->render_attributes( 'cart-detail' ); ?>>
			<div class="widget_shopping_cart_content"></div>

			<?php
			// Close button (@since 1.7.1)
			if ( ! empty( $settings['cartDetailsCloseIcon'] ) ) {
				?>
			<button class="bricks-mini-cart-close" data-toggle-target=".<?php echo $cart_detail_target_class; ?>" aria-label="<?php esc_attr_e( 'Close mini cart', 'bricks' ); ?>">
				<?php echo self::render_icon( $settings['cartDetailsCloseIcon'] ); ?>
			</button>
			<?php } ?>
		</div>
			<?php
		}

		if ( ! empty( $settings['cartDetailsOffCanvas'] ) ) {
			echo '<div class="off-canvas-overlay"></div>';
		}

		echo '</div>';
	}

	/**
	 * NOTE: Not in use in order to show .cart-detail (fragments)
	 */
	public static function not_in_use_render_builder() {
		?>
		<script type="text/x-template" id="tmpl-bricks-element-woocommerce-mini-cart">
			<component :is="tag">
				<a class="mini-cart-link toggle-button">
					<span class="cart-icon">
						<icon-svg v-if="settings?.icon?.icon || settings?.icon?.svg" :iconSettings="settings.icon"/>
						<span class="cart-count">0</span>
					</span>

					<span v-if="settings.subtotalPosition" class="cart-subtotal">$0,00</span>
				</a>
			</component>
		</script>
		<?php
	}
}
