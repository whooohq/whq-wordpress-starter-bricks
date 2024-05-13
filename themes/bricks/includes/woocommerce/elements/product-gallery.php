<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Product_Gallery extends Element {
	public $category = 'woocommerce_product';
	public $name     = 'product-gallery';
	public $icon     = 'ti-gallery';
	public $scripts  = [ 'bricksWooProductGallery' ];
	public $product  = false;

	public function enqueue_scripts() {
		wp_enqueue_script( 'wc-single-product' );
		wp_enqueue_script( 'flexslider' );

		if ( bricks_is_builder_iframe() ) {
			wp_enqueue_script( 'zoom' );
		} elseif ( ! Database::get_setting( 'woocommerceDisableProductGalleryZoom', false ) ) {
			wp_enqueue_script( 'zoom' );
		}
	}

	public function get_label() {
		return esc_html__( 'Product gallery', 'bricks' );
	}

	public function set_controls() {
		$this->controls['_width']['rerender']    = true;
		$this->controls['_widthMax']['rerender'] = true;

		$this->controls['productImageSize'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Product', 'bricks' ) . ': ' . esc_html__( 'Image size', 'bricks' ),
			'type'        => 'select',
			'options'     => Setup::get_image_sizes_options(),
			'placeholder' => 'woocommerce_single',
		];

		$this->controls['thumbnailImageSize'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Thumbnail', 'bricks' ) . ': ' . esc_html__( 'Image size', 'bricks' ),
			'type'        => 'select',
			'options'     => Setup::get_image_sizes_options(),
			'placeholder' => 'woocommerce_gallery_thumbnail',
		];

		$this->controls['lightboxImageSize'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Lightbox', 'bricks' ) . ': ' . esc_html__( 'Image size', 'bricks' ),
			'type'        => 'select',
			'options'     => Setup::get_image_sizes_options(),
			'placeholder' => 'full',
		];

		// THUMBNAILS

		$this->controls['thumbnailSep'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Thumbnails', 'bricks' ),
			'type'  => 'separator',
		];

		$this->controls['thumbnailPosition'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Position', 'bricks' ),
			'type'        => 'select',
			'inline'      => true,
			'options'     => [
				'top'    => esc_html__( 'Top', 'bricks' ),
				'right'  => esc_html__( 'Right', 'bricks' ),
				'bottom' => esc_html__( 'Bottom', 'bricks' ),
				'left'   => esc_html__( 'Left', 'bricks' ),
			],
			'placeholder' => esc_html__( 'Bottom', 'bricks' ),
		];

		$this->controls['itemWidth'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Item width', 'bricks' ) . ' (px)',
			'type'        => 'number',
			'units'       => true,
			'css'         => [
				[
					'selector' => '&[data-pos="right"] .woocommerce-product-gallery .flex-control-nav',
					'property' => 'width',
				],
				[
					'selector' => '&[data-pos="left"] .woocommerce-product-gallery .flex-control-nav',
					'property' => 'width',
				],
				[
					'selector' => '&[data-pos="right"] .brx-product-gallery-thumbnail-slider',
					'property' => 'width',
				],
				[
					'selector' => '&[data-pos="left"] .brx-product-gallery-thumbnail-slider',
					'property' => 'width',
				],
			],
			'placeholder' => '100px',
			'rerender'    => true,
			'required'    => [ 'thumbnailPosition', '=', [ 'left', 'right' ] ],
		];

		$this->controls['columns'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Columns', 'bricks' ),
			'type'        => 'number',
			'css'         => [
				[
					'selector' => '.flex-control-thumbs',
					'property' => 'grid-template-columns',
					'value'    => 'repeat(%s, 1fr)', // NOTE: Undocumented (@since 1.3)
				],
			],
			'placeholder' => 4,
			'required'    => [
				[ 'thumbnailSlider', '!=', true ],
				[ 'thumbnailPosition', '!=', [ 'left', 'right' ] ]
			],
		];

		$this->controls['gap'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Gap', 'bricks' ),
			'type'        => 'number',
			'units'       => true,
			'css'         => [
				[

					'selector' => '.flex-control-thumbs',
					'property' => 'gap',
				],
				[
					'selector' => '.woocommerce-product-gallery',
					'property' => 'gap',
				],
				[
					'selector' => '&.thumbnail-slider',
					'property' => 'gap',
				],
			],
			'placeholder' => '30px',
		];

		$this->controls['thumbnailOpacity'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Opacity', 'bricks' ),
			'type'        => 'number',
			'step'        => 0.1,
			'css'         => [
				[
					'selector' => '.woocommerce-product-gallery .flex-control-thumbs img:not(.flex-active)',
					'property' => 'opacity',
				],
				[
					'selector' => '&.thumbnail-slider .brx-product-gallery-thumbnail-slider .woocommerce-product-gallery__image:not(.flex-active-slide) img',
					'property' => 'opacity',
				],
			],
			'placeholder' => '0.3',
		];

		$this->controls['thumbnailActiveOpacity'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Opacity', 'bricks' ) . ' (' . esc_html__( 'Active', 'bricks' ) . ')',
			'type'  => 'number',
			'step'  => 0.1,
			'css'   => [
				[
					'selector' => '.woocommerce-product-gallery .flex-control-thumbs img.flex-active',
					'property' => 'opacity',
				],
				[
					'selector' => '&.thumbnail-slider .brx-product-gallery-thumbnail-slider .woocommerce-product-gallery__image.flex-active-slide img',
					'property' => 'opacity',
				],
			],
		];

		$this->controls['thumbnailBorder'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'selector' => '.woocommerce-product-gallery .flex-control-thumbs img',
					'property' => 'border',
				],
				[
					'selector' => '&.thumbnail-slider .brx-product-gallery-thumbnail-slider .woocommerce-product-gallery__image',
					'property' => 'border',
				],
			],
		];

		$this->controls['thumbnailActiveBorder'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Border', 'bricks' ) . ' (' . esc_html__( 'Active', 'bricks' ) . ')',
			'type'  => 'border',
			'css'   => [
				[
					'selector' => '.woocommerce-product-gallery .flex-control-thumbs img.flex-active',
					'property' => 'border',
				],
				[
					'selector' => '&.thumbnail-slider .brx-product-gallery-thumbnail-slider .woocommerce-product-gallery__image.flex-active-slide',
					'property' => 'border',
				],
			],
		];

		// Thumbnail slider (@since 1.9)
		$this->controls['thumbnailSlider'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Slider', 'bricks' ),
			'type'     => 'checkbox',
			'rerender' => true,
		];

		$this->controls['thumbnailWrapperMaxHeight'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Slider', 'bricks' ) . ': ' . esc_html__( 'Height', 'bricks' ) . ' (px)',
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'selector' => '&.thumbnail-slider .brx-product-gallery-thumbnail-slider',
					'property' => 'max-height',
				],
			],
			'rerender' => true,
			'required' => [
				[ 'thumbnailSlider', '=', true ],
				[ 'thumbnailPosition', '=', [ 'left', 'right' ] ],
			],
		];

		// NOTE: 'itemMargin' doesn't support gap in 'vertical' direction, so we have to use 'magin-bottom' instead
		$this->controls['itemMargin'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Slider', 'bricks' ) . ': ' . esc_html__( 'Gap', 'bricks' ) . ' (px)',
			'type'        => 'number',
			'units'       => true,
			'css'         => [
				[
					'selector' => '&.thumbnail-slider[data-pos="right"] .brx-product-gallery-thumbnail-slider .woocommerce-product-gallery__image',
					'property' => 'margin-bottom',
				],
				[
					'selector' => '&.thumbnail-slider[data-pos="left"] .brx-product-gallery-thumbnail-slider .woocommerce-product-gallery__image',
					'property' => 'margin-bottom',
				],
			],
			'placeholder' => '30',
			'rerender'    => true,
			'required'    => [ 'thumbnailSlider', '=', true ],
		];

		// $this->controls['minItems'] = [
		// 'tab'         => 'content',
		// 'label'       => esc_html__( 'Min. items', 'bricks' ),
		// 'type'        => 'number',
		// 'units'       => false,
		// 'placeholder' => '1',
		// 'rerender'    => true,
		// 'required'    => [ 'thumbnailSlider', '=', true ],
		// ];

		// Horizontal direction only (vertical slider doesn't support it)
		$this->controls['maxItems'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Max. items', 'bricks' ),
			'type'        => 'number',
			'units'       => false,
			'placeholder' => '4',
			'rerender'    => true,
			'required'    => [
				[ 'thumbnailSlider', '=', true ],
				[ 'thumbnailPosition', '!=', [ 'left', 'right' ] ],
			],
		];

		// THUMBNAIL NAV (ARROWS)

		$this->controls['arrowsSep'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Arrows', 'bricks' ),
			'type'     => 'separator',
			'required' => [ 'thumbnailSlider', '=', true ],
		];

		$this->controls['prevArrow'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Prev arrow', 'bricks' ),
			'type'        => 'icon',
			'placeholder' => 'ti-angle-left',
			'render'      => false,
			'required'    => [ 'thumbnailSlider', '=', true ],
		];

		$this->controls['nextArrow'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Next arrow', 'bricks' ),
			'type'        => 'icon',
			'placeholder' => 'ti-angle-right',
			'render'      => false,
			'required'    => [ 'thumbnailSlider', '=', true ],
		];

		$this->controls['arrowBackground'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Background', 'bricks' ),
			'type'     => 'color',
			'css'      => [
				[
					'property' => 'background-color',
					'selector' => '.flex-direction-nav a',
				],
			],
			'required' => [ 'thumbnailSlider', '=', true ],
		];

		$this->controls['arrowBorder'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Border', 'bricks' ),
			'type'     => 'border',
			'css'      => [
				[
					'property' => 'border',
					'selector' => '.flex-direction-nav a',
				],
			],
			'required' => [ 'thumbnailSlider', '=', true ],
		];

		$this->controls['arrowColor'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Color', 'bricks' ),
			'type'     => 'color',
			'css'      => [
				[
					'property' => 'color',
					'selector' => '.flex-direction-nav a',
				],
			],
			'required' => [ 'thumbnailSlider', '=', true ],
		];

		$this->controls['arrowSize'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Size', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'font-size',
					'selector' => '.flex-direction-nav a > *',
				],
				[
					'property' => 'height',
					'selector' => '.flex-direction-nav a > svg',
				],
			],
			'required' => [ 'thumbnailSlider', '=', true ],
		];

		$this->controls['arrowHeight'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Height', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'height',
					'selector' => '.flex-direction-nav a',
				],
			],
			'required' => [ 'thumbnailSlider', '=', true ],
		];

		$this->controls['arrowWidth'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Width', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'width',
					'selector' => '.flex-direction-nav a',
				],
			],
			'required' => [ 'thumbnailSlider', '=', true ],
		];
	}

	public function render() {
		global $product;
		$product = wc_get_product( $this->post_id );

		if ( empty( $product ) ) {
			return $this->render_element_placeholder(
				[
					'title'       => esc_html__( 'For better preview select content to show.', 'bricks' ),
					'description' => esc_html__( 'Go to: Settings > Template Settings > Populate Content', 'bricks' ),
				]
			);
		}

		$this->product = $product;
		$settings      = $this->settings;

		if ( ! empty( $settings['thumbnailPosition'] ) ) {
			$this->set_attribute( '_root', 'data-pos', esc_attr( $settings['thumbnailPosition'] ) );
		}

		// STEP: Thumbnail slider enabled
		$thumbnail_slider = isset( $settings['thumbnailSlider'] ) ? $settings['thumbnailSlider'] : false;

		if ( $thumbnail_slider ) {
			$this->set_attribute( '_root', 'class', 'thumbnail-slider' );
		}

		// STEP: Render
		echo "<div {$this->render_attributes( '_root' )}>";

		echo $this->product_gallery_html();

		if ( $thumbnail_slider ) {
			echo $this->bricks_product_gallery_thumbnails();
		}

		echo '</div>';
	}

	/**
	 * Get product gallery HTML
	 *
	 * @since 1.9
	 * @return string
	 */
	public function product_gallery_html() {
		add_filter( 'woocommerce_gallery_thumbnail_size', [ $this, 'set_gallery_thumbnail_size' ] );
		add_filter( 'woocommerce_gallery_image_size', [ $this, 'set_gallery_image_size' ] );
		add_filter( 'woocommerce_gallery_full_size', [ $this, 'set_gallery_full_size' ] );
		add_filter( 'woocommerce_gallery_image_html_attachment_image_params', [ $this, 'add_image_class_prevent_lazy_loading' ], 10, 4 );

		ob_start();
		wc_get_template( 'single-product/product-image.php' );
		$gallery_html = ob_get_clean();

		remove_filter( 'woocommerce_gallery_thumbnail_size', [ $this, 'set_gallery_thumbnail_size' ] );
		remove_filter( 'woocommerce_gallery_image_size', [ $this, 'set_gallery_image_size' ] );
		remove_filter( 'woocommerce_gallery_full_size', [ $this, 'set_gallery_full_size' ] );
		remove_filter( 'woocommerce_gallery_image_html_attachment_image_params', [ $this, 'add_image_class_prevent_lazy_loading' ], 10, 4 );

		return $gallery_html;
	}

	/**
	 * Render Bricks product gallery thumbnails
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	public function bricks_product_gallery_thumbnails() {
		$product = $this->product;

		if ( ! is_a( $product, 'WC_Product' ) ) {
			return '';
		}

		$attachment_ids = $product->get_gallery_image_ids();

		// Exit if no gallery images
		if ( ! $attachment_ids ) {
			return '';
		}

		// STEP: Populate flexslider settings
		$settings    = $this->settings;
		$position    = ! empty( $settings['thumbnailPosition'] ) ? $settings['thumbnailPosition'] : 'bottom';
		$prev_arrow  = ! empty( $settings['prevArrow'] ) ? self::render_icon( $settings['prevArrow'] ) : '';
		$next_arrow  = ! empty( $settings['nextArrow'] ) ? self::render_icon( $settings['nextArrow'] ) : '';
		$direction   = in_array( $position, [ 'bottom', 'top' ] ) ? 'horizontal' : 'vertical';
		$item_margin = isset( $settings['itemMargin'] ) ? absint( $settings['itemMargin'] ) : 30;

		// flexslider settings https://gist.github.com/warrendholmes/9481310
		$thumbnail_settings = [
			'animation'      => 'slide',
			'direction'      => $direction,
			'itemWidth'      => isset( $settings['itemWidth'] ) ? absint( $settings['itemWidth'] ) : 100,
			'itemMargin'     => $item_margin, // Vertical direction doesn't support 'itemMargin' (need to set via margin-bottom)
			'minItems'       => isset( $settings['minItems'] ) ? absint( $settings['minItems'] ) : 1,
			'maxItems'       => isset( $settings['maxItems'] ) ? absint( $settings['maxItems'] ) : 4,
			'animationSpeed' => 500,
			'animationLoop'  => false,
			'smoothHeight'   => false,
			'controlNav'     => false,
			'slideshow'      => false,
			'prevText'       => $prev_arrow,
			'nextText'       => $next_arrow,
			'rtl'            => is_rtl(),
			'asNavFor'       => '.woocommerce-product-gallery',
			'selector'       => '.brx-thumbail-slider-wrapper > .woocommerce-product-gallery__image',
		];

		$this->set_attribute( 'product_thumbnails', 'class', 'brx-product-gallery-thumbnail-slider' );
		$this->set_attribute( 'product_thumbnails', 'data-thumbnail-settings', wp_json_encode( $thumbnail_settings ) );

		// STEP: single-product/product-image.php
		$post_thumbnail_id = $product->get_image_id();

		// NOTE: use woocommerce_gallery_image_size instead of woocommerce_gallery_thumbnail_size we are building a fake thumbnail
		add_filter( 'woocommerce_gallery_image_size', [ $this, 'set_gallery_thumbnail_size' ] );

		if ( $post_thumbnail_id ) {
			$html = wc_get_gallery_image_html( $post_thumbnail_id, true );
		} else {
			$html  = '<div class="woocommerce-product-gallery__image--placeholder">';
			$html .= sprintf( '<img src="%s" alt="%s" class="wp-post-image" />', esc_url( wc_placeholder_img_src( 'woocommerce_single' ) ), esc_html__( 'Awaiting product image', 'woocommerce' ) );
			$html .= '</div>';
		}

		$html = apply_filters( 'woocommerce_single_product_image_thumbnail_html', $html, $post_thumbnail_id );

		// STEP: single-product/product-thumbnails.php
		if ( $attachment_ids && $product->get_image_id() ) {
			foreach ( $attachment_ids as $attachment_id ) {
				$html .= apply_filters( 'woocommerce_single_product_image_thumbnail_html', wc_get_gallery_image_html( $attachment_id ), $attachment_id ); // phpcs:disable WordPress.XSS.EscapeOutput.OutputNotEscaped
			}
		}

		remove_filter( 'woocommerce_gallery_image_size', [ $this, 'set_gallery_thumbnail_size' ] );

		// Return thumbnail slider HTML
		return "<div {$this->render_attributes( 'product_thumbnails' )}><div class=\"brx-thumbail-slider-wrapper\">" . $html . '</div></div>';
	}

	/**
	 * Set gallery image size for the current product gallery
	 *
	 * hook: woocommerce_gallery_image_size
	 *
	 * @see woocommerce/includes/wc-template-functions.php
	 *
	 * @since 1.8
	 */
	public function set_gallery_image_size( $size ) {
		if ( ! empty( $this->settings['productImageSize'] ) ) {
			$size = $this->settings['productImageSize'];
		}

		return $size;
	}

	/**
	 * Set gallery thumbnail size for the current product gallery
	 *
	 * hook: woocommerce_gallery_thumbnail_size
	 *
	 * @see woocommerce/includes/wc-template-functions.php
	 *
	 * @since 1.8
	 */
	public function set_gallery_thumbnail_size( $size ) {
		if ( ! empty( $this->settings['thumbnailImageSize'] ) ) {
			$size = $this->settings['thumbnailImageSize'];
		}

		return $size;
	}

	/**
	 * Set gallery full size for the current product gallery (Lightbox)
	 *
	 * hook: woocommerce_gallery_full_size
	 *
	 * @see woocommerce/includes/wc-template-functions.php
	 *
	 * @since 1.8
	 */
	public function set_gallery_full_size( $size ) {
		if ( ! empty( $this->settings['lightboxImageSize'] ) ) {
			$size = $this->settings['lightboxImageSize'];
		}

		return $size;
	}

	public function add_image_class_prevent_lazy_loading( $attr, $attachment_id, $image_size, $main_image ) {
		// NOTE: Undocumented (used only internally in the Frontend::set_image_attributes)
		if ( $this->lazy_load() ) {
			$attr['_brx_disable_lazy_loading'] = 1;
		}

		// Photoswipe 5 (@since 1.7.2)
		// NOTE: Not in use as Photoswipe 5 is not supported by all major Woo product gallery plugins
		// $attachment               = wp_get_attachment_image_src( $attachment_id, $image_size );
		// $attr['data-pswp-src']    = ! empty( $attachment[0] ) ? $attachment[0] : '';
		// $attr['data-pswp-width']  = ! empty( $attachment[1] ) ? $attachment[1] : '';
		// $attr['data-pswp-height'] = ! empty( $attachment[2] ) ? $attachment[2] : '';
		// $attr['data-pswp-id']     = $this->id;

		return $attr;
	}
}
