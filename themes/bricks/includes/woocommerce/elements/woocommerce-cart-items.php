<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Woocommerce_Cart_Items extends Element {
	public $category        = 'woocommerce';
	public $name            = 'woocommerce-cart-items';
	public $icon            = 'ti-shopping-cart';
	public $panel_condition = [ 'templateType', '=', 'wc_cart' ];

	public function get_label() {
		return esc_html__( 'Cart items', 'bricks' );
	}

	public function set_control_groups() {
		$this->control_groups['products'] = [
			'title' => esc_html__( 'Products', 'bricks' ),
			'tab'   => 'content',
		];

		$this->control_groups['buttons'] = [
			'title' => esc_html__( 'Buttons', 'bricks' ),
			'tab'   => 'content',
		];

		$this->control_groups['coupon'] = [
			'title' => esc_html__( 'Coupon', 'bricks' ),
			'tab'   => 'content',
		];
	}

	public function set_controls() {
		// PRODUCTS

		$this->controls['itemLinkDisable'] = [
			'tab'   => 'content',
			'group' => 'products',
			'label' => esc_html__( 'Disable link', 'bricks' ),
			'type'  => 'checkbox'
		];

		$columns = [
			'remove'    => esc_html__( 'Remove', 'bricks' ),
			'thumbnail' => esc_html__( 'Thumbnail', 'bricks' ),
			'name'      => esc_html__( 'Name', 'bricks' ),
			'price'     => esc_html__( 'Price', 'bricks' ),
			'quantity'  => esc_html__( 'Quantity', 'bricks' ),
			'subtotal'  => esc_html__( 'Subtotal', 'bricks' ),
		];

		foreach ( $columns as $key => $label ) {
			$this->controls[ "{$key}Hide" ] = [
				'tab'   => 'content',
				'group' => 'products',
				// translators: %s: Label name
				'label' => sprintf( esc_html__( 'Hide %s', 'bricks' ), $label ),
				'type'  => 'checkbox',
				'css'   => [
					[
						'selector' => ".product-$key",
						'property' => 'display',
						'value'    => 'none',
					]
				],
			];
		}

		foreach ( $columns as $key => $label ) {
			if ( in_array( $key, [ 'remove', 'thumbnail' ] ) ) {
				continue;
			}

			$this->controls[ "{$key}Typography" ] = [
				'tab'      => 'content',
				'group'    => 'products',
				// translators: %s: Label name
				'label'    => sprintf( esc_html__( '%s typography', 'bricks' ), $label ),
				'type'     => 'typography',
				'css'      => [
					[
						'property' => 'font',
						'selector' => "tbody .product-$key",
					],
				],
				'required' => [ $key . 'Hide', '=', '' ]
			];
		}

		// Table head

		$this->controls['headSeparator'] = [
			'type'  => 'separator',
			'label' => esc_html__( 'Header', 'bricks' ),
			'tab'   => 'content',
			'group' => 'products',
		];

		$this->controls['headHide'] = [
			'tab'   => 'content',
			'group' => 'products',
			'label' => esc_html__( 'Hide', 'bricks' ),
			'type'  => 'checkbox',
			'css'   => [
				[
					'property' => 'display',
					'selector' => 'thead',
					'value'    => 'none',
				],
			],
		];

		$this->controls['headBackground'] = [
			'tab'      => 'content',
			'group'    => 'products',
			'label'    => esc_html__( 'Background', 'bricks' ),
			'type'     => 'color',
			'css'      => [
				[
					'property' => 'background-color',
					'selector' => 'thead',
				],
			],
			'required' => [ 'headHide', '=', '' ],
		];

		$this->controls['headBorder'] = [
			'tab'      => 'content',
			'group'    => 'products',
			'label'    => esc_html__( 'Border', 'bricks' ),
			'type'     => 'border',
			'css'      => [
				[
					'property' => 'border',
					'selector' => 'thead',
				],
			],
			'inline'   => true,
			'small'    => true,
			'required' => [ 'headHide', '=', '' ],
		];

		$this->controls['headTypography'] = [
			'tab'      => 'content',
			'group'    => 'products',
			'label'    => esc_html__( 'Typography', 'bricks' ),
			'type'     => 'typography',
			'css'      => [
				[
					'property' => 'font',
					'selector' => 'thead',
				],
			],
			'required' => [ 'headHide', '=', '' ],
		];

		// Table body

		$this->controls['bodySeparator'] = [
			'type'  => 'separator',
			'label' => esc_html__( 'Body', 'bricks' ),
			'tab'   => 'content',
			'group' => 'products',
		];

		$this->controls['bodyBackground'] = [
			'tab'    => 'content',
			'group'  => 'products',
			'label'  => esc_html__( 'Background', 'bricks' ),
			'type'   => 'color',
			'css'    => [
				[
					'property' => 'background-color',
					'selector' => 'tbody',
				],
			],
			'inline' => true,
			'small'  => true,
		];

		$this->controls['bodyBorder'] = [
			'tab'    => 'content',
			'group'  => 'products',
			'label'  => esc_html__( 'Border', 'bricks' ),
			'type'   => 'border',
			'css'    => [
				[
					'property' => 'border',
					'selector' => 'tbody tr',
				],
			],
			'inline' => true,
			'small'  => true,
		];

		// Product image

		$this->controls['imageSeparator'] = [
			'type'  => 'separator',
			'label' => esc_html__( 'Image', 'bricks' ),
			'tab'   => 'content',
			'group' => 'products',
		];

		$this->controls['imageDisable'] = [
			'tab'   => 'content',
			'group' => 'products',
			'label' => esc_html__( 'Disable', 'bricks' ),
			'type'  => 'checkbox',
		];

		$this->controls['width'] = [
			'tab'         => 'content',
			'group'       => 'products',
			'label'       => esc_html__( 'Width', 'bricks' ),
			'type'        => 'number',
			'units'       => true,
			'css'         => [
				[
					'property' => 'width',
					'selector' => '.product-thumbnail img',
				]
			],
			'placeholder' => 100,
			'required'    => [ 'imageDisable', '=', '' ],
		];

		$this->controls['imageHeight'] = [
			'tab'      => 'content',
			'group'    => 'products',
			'label'    => esc_html__( 'Height', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'height',
					'selector' => '.product-thumbnail img',
				]
			],
			// 'placeholder' => 'auto',
			'required' => [ 'imageDisable', '=', '' ],
		];

		$this->controls['imageSize'] = [
			'tab'      => 'content',
			'group'    => 'products',
			'label'    => esc_html__( 'Size', 'bricks' ),
			'type'     => 'select',
			'options'  => $this->control_options['imageSizes'],
			'required' => [ 'imageDisable', '=', '' ],
		];

		// Remove

		$this->controls['removeSeparator'] = [
			'type'  => 'separator',
			'label' => esc_html__( 'Remove', 'bricks' ),
			'tab'   => 'content',
			'group' => 'products',
		];

		$this->controls['removeColor'] = [
			'tab'   => 'content',
			'group' => 'products',
			'label' => esc_html__( 'Color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'color',
					'selector' => '.product-remove a',
				],
			],
		];

		$this->controls['removeSize'] = [
			'tab'   => 'content',
			'group' => 'products',
			'label' => esc_html__( 'Size', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'css'   => [
				[
					'property' => 'font-size',
					'selector' => '.product-remove a',
				],
			],
		];

		$this->controls['removePosition'] = [
			'tab'   => 'content',
			'group' => 'products',
			'type'  => 'dimensions',
			'label' => esc_html__( 'Position', 'bricks' ),
			'css'   => [
				[
					'property' => '',
					'selector' => '.product-remove',
				],
				[
					'property' => 'position',
					'selector' => '.product-remove',
					'value'    => 'absolute',
				],
			],
		];

		// BUTTONS

		$this->controls['buttonsTypography'] = [
			'tab'   => 'content',
			'group' => 'buttons',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.button',
				],
			],
		];

		$this->controls['buttonsBackground'] = [
			'tab'   => 'content',
			'group' => 'buttons',
			'label' => esc_html__( 'Background color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.button',
				],
			],
		];

		$this->controls['buttonsBorder'] = [
			'tab'   => 'content',
			'group' => 'buttons',
			'type'  => 'border',
			'label' => esc_html__( 'Border', 'bricks' ),
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.button',
				],
			]
		];

		// COUPON

		$this->controls['hideCoupon'] = [
			'tab'   => 'content',
			'group' => 'coupon',
			'label' => esc_html__( 'Hide', 'bricks' ),
			'type'  => 'checkbox',
		];

		$this->controls['couponMargin'] = [
			'tab'      => 'content',
			'group'    => 'coupon',
			'label'    => esc_html__( 'Margin', 'bricks' ),
			'type'     => 'spacing',
			'css'      => [
				[
					'property' => 'margin',
					'selector' => '.coupon',
				],
			],
			'required' => [ 'hideCoupon', '=', '' ],
		];
	}

	public function render() {
		$settings = $this->settings;

		Woocommerce_Helpers::maybe_init_cart_context();

		// In the builder: add products to cart if cart is empty for a better user experience
		Woocommerce_Helpers::maybe_populate_cart_contents();

		// Add hooks
		add_filter( 'woocommerce_cart_item_permalink', [ $this, 'woocommerce_cart_item_permalink' ], 10, 3 );
		add_filter( 'woocommerce_cart_item_thumbnail', [ $this, 'woocommerce_cart_item_thumbnail' ], 10, 3 );

		// Render
		$this->set_attribute( '_root', 'class', 'woocommerce-cart-form' );
		$this->set_attribute( '_root', 'action', esc_url( wc_get_cart_url() ) );
		$this->set_attribute( '_root', 'method', 'post' );
		?>

		<form <?php echo $this->render_attributes( '_root' ); ?>>
			<?php do_action( 'woocommerce_before_cart_table' ); ?>

			<table class="shop_table shop_table_responsive cart woocommerce-cart-form__contents" cellspacing="0">
				<thead>
					<tr>
						<th class="product-remove">&nbsp;</th>
						<th class="product-thumbnail">&nbsp;</th>
						<th class="product-name"><?php esc_html_e( 'Product', 'woocommerce' ); ?></th>
						<th class="product-price"><?php esc_html_e( 'Price', 'woocommerce' ); ?></th>
						<th class="product-quantity"><?php esc_html_e( 'Quantity', 'woocommerce' ); ?></th>
						<th class="product-subtotal"><?php esc_html_e( 'Subtotal', 'woocommerce' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php do_action( 'woocommerce_before_cart_contents' ); ?>

					<?php
					foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
						$_product     = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
						$product_id   = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );
						$product_name = apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key );

						if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
							$product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
							?>
							<tr class="woocommerce-cart-form__cart-item <?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?>">

								<td class="product-remove">
									<?php
										echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
											'woocommerce_cart_item_remove_link',
											// translators: %s: Product remove URL, %s: Product remove text, %s: Product ID, %s: Product SKU
											sprintf(
												'<a href="%s" class="remove" aria-label="%s" data-product_id="%s" data-product_sku="%s">&times;</a>',
												esc_url( wc_get_cart_remove_url( $cart_item_key ) ),
												// translators: %s: Product name
												esc_attr( sprintf( __( 'Remove %s from cart', 'woocommerce' ), wp_strip_all_tags( $product_name ) ) ),
												esc_attr( $product_id ),
												esc_attr( $_product->get_sku() )
											),
											$cart_item_key
										);
									?>
								</td>

								<td class="product-thumbnail">
								<?php
								$thumbnail = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key );

								if ( ! $product_permalink ) {
									echo $thumbnail; // PHPCS: XSS ok.
								} else {
									printf( '<a href="%s">%s</a>', esc_url( $product_permalink ), $thumbnail ); // PHPCS: XSS ok.
								}
								?>
								</td>

								<td class="product-name" data-title="<?php esc_attr_e( 'Product', 'woocommerce' ); ?>">
								<?php
								if ( ! $product_permalink ) {
									echo wp_kses_post( $product_name . '&nbsp;' );
								} else {
									echo wp_kses_post( apply_filters( 'woocommerce_cart_item_name', sprintf( '<a href="%s">%s</a>', esc_url( $product_permalink ), $_product->get_name() ), $cart_item, $cart_item_key ) );
								}

								do_action( 'woocommerce_after_cart_item_name', $cart_item, $cart_item_key );

								// Meta data.
								echo wc_get_formatted_cart_item_data( $cart_item ); // PHPCS: XSS ok.

								// Backorder notification.
								if ( $_product->backorders_require_notification() && $_product->is_on_backorder( $cart_item['quantity'] ) ) {
									echo wp_kses_post( apply_filters( 'woocommerce_cart_item_backorder_notification', '<p class="backorder_notification">' . esc_html__( 'Available on backorder', 'woocommerce' ) . '</p>', $product_id ) );
								}
								?>
								</td>

								<td class="product-price" data-title="<?php esc_attr_e( 'Price', 'woocommerce' ); ?>">
									<?php
										echo apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key ); // PHPCS: XSS ok.
									?>
								</td>

								<td class="product-quantity" data-title="<?php esc_attr_e( 'Quantity', 'woocommerce' ); ?>">
								<?php
								if ( $_product->is_sold_individually() ) {
									$min_quantity = 1;
									$max_quantity = 1;
								} else {
									$min_quantity = 0; // Follow cart.php in WC 7.4.0.
									$max_quantity = $_product->get_max_purchase_quantity();
								}

								$product_quantity = woocommerce_quantity_input(
									[
										'input_name'   => "cart[{$cart_item_key}][qty]",
										'input_value'  => $cart_item['quantity'],
										'max_value'    => $max_quantity,
										'min_value'    => $min_quantity,
										'product_name' => $product_name,
									],
									$_product,
									false
								);

								echo apply_filters( 'woocommerce_cart_item_quantity', $product_quantity, $cart_item_key, $cart_item ); // PHPCS: XSS ok.
								?>
								</td>

								<td class="product-subtotal" data-title="<?php esc_attr_e( 'Subtotal', 'woocommerce' ); ?>">
									<?php
										echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ); // PHPCS: XSS ok.
									?>
								</td>
							</tr>
							<?php
						}
					}
					?>

					<?php do_action( 'woocommerce_cart_contents' ); ?>

					<tr>
						<td colspan="6" class="actions">
							<?php if ( wc_coupons_enabled() && ! isset( $settings['hideCoupon'] ) ) { ?>
								<div class="coupon">
									<label for="coupon_code"><?php esc_html_e( 'Coupon:', 'woocommerce' ); ?></label> <input type="text" name="coupon_code" class="input-text" id="coupon_code" value="" placeholder="<?php esc_attr_e( 'Coupon code', 'woocommerce' ); ?>" /> <button type="submit" class="button" name="apply_coupon" value="<?php esc_attr_e( 'Apply coupon', 'woocommerce' ); ?>"><?php esc_html_e( 'Apply coupon', 'woocommerce' ); ?></button>
									<?php do_action( 'woocommerce_cart_coupon' ); ?>
								</div>
							<?php } ?>

							<button type="submit" class="button" name="update_cart" value="<?php esc_attr_e( 'Update cart', 'woocommerce' ); ?>"><?php esc_html_e( 'Update cart', 'woocommerce' ); ?></button>

							<?php do_action( 'woocommerce_cart_actions' ); ?>

							<?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>
						</td>
					</tr>

					<?php do_action( 'woocommerce_after_cart_contents' ); ?>
				</tbody>
			</table>
			<?php do_action( 'woocommerce_after_cart_table' ); ?>
		</form>

		<?php
		// Remove hooks
		remove_filter( 'woocommerce_cart_item_permalink', [ $this, 'woocommerce_cart_item_permalink' ] );
		remove_filter( 'woocommerce_cart_item_thumbnail', [ $this, 'woocommerce_cart_item_thumbnail' ] );
	}

	public function woocommerce_cart_item_thumbnail( $thumbnail, $cart_item, $cart_item_key ) {
		if ( isset( $this->settings['imageDisable'] ) ) {
			return '';
		}

		if ( ! empty( $this->settings['imageSize'] ) ) {
			$_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );

			return $_product->get_image( $this->settings['imageSize'] );
		}

		return $thumbnail;
	}

	public function woocommerce_cart_item_permalink( $permalink, $cart_item, $cart_item_key ) {
		if ( isset( $this->settings['itemLinkDisable'] ) ) {
			return '';
		}

		return $permalink;
	}
}
