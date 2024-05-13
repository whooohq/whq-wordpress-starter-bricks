<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Woocommerce_Products extends Element {
	public $category     = 'woocommerce';
	public $name         = 'woocommerce-products';
	public $icon         = 'ti-archive';
	public $css_selector = '.product';

	public function get_label() {
		return esc_html__( 'Products', 'bricks' );
	}

	public function set_control_groups() {
		$this->control_groups['query'] = [
			'title' => esc_html__( 'Query', 'bricks' ),
			'tab'   => 'content',
		];

		$this->control_groups['fields'] = [
			'title' => esc_html__( 'Fields', 'bricks' ),
			'tab'   => 'content',
		];

		$this->control_groups['widgets'] = [
			'title' => esc_html__( 'Widgets', 'bricks' ),
			'tab'   => 'content',
		];
	}

	public function set_controls() {
		// LAYOUT

		$this->controls['columns'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Columns', 'bricks' ),
			'type'        => 'number',
			'min'         => 1,
			'max'         => 6,
			'breakpoints' => true,
			'css'         => [
				[
					'selector' => '.products',
					'property' => 'grid-template-columns',
					'value'    => 'repeat(%s, 1fr)', // NOTE: Undocumented (@since 1.3)
				],
			],
			'placeholder' => 4,
			'rerender'    => true,
		];

		$this->controls['gap'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Gap', 'bricks' ),
			'type'        => 'number',
			'units'       => true,
			'css'         => [
				[
					'selector' => '.products',
					'property' => 'gap',
				],
			],
			'placeholder' => 30,
		];

		// QUERY

		$query_controls = Woocommerce_Helpers::get_product_query_controls( [ 'group' => 'query' ] );

		$this->controls = array_replace_recursive( $this->controls, $query_controls );

		// FIELDS

		$fields = $this->get_post_fields();

		// Remove field settings (background, border can't be set for .dynamic {woo_add_to_cart}, etc.)
		unset( $fields['fields']['fields']['overlay'] );
		unset( $fields['fields']['fields']['dynamicPadding'] );
		unset( $fields['fields']['fields']['dynamicBackground'] );
		unset( $fields['fields']['fields']['dynamicBorder'] );

		// Set fields defaults (including default {do_action} hooks for standard WooCommerce actions @since 1.7)
		$fields['fields']['default'] = [
			[
				'dynamicData' => '{do_action:woocommerce_before_shop_loop_item}',
				'id'          => Helpers::generate_random_id( false ),
			],
			[
				'dynamicData' => '{do_action:woocommerce_before_shop_loop_item_title}',
				'id'          => Helpers::generate_random_id( false ),
			],
			[
				'dynamicData' => '{featured_image:large:link}',
				'id'          => Helpers::generate_random_id( false ),
			],
			[
				'dynamicData' => '{do_action:woocommerce_shop_loop_item_title}',
				'id'          => Helpers::generate_random_id( false ),
			],
			[
				'dynamicData'   => '{post_title:link}',
				'tag'           => 'h5',
				'dynamicMargin' => [
					'top'    => 15,
					'bottom' => 5,
				],
				'id'            => Helpers::generate_random_id( false ),
			],
			[
				'dynamicData' => '{do_action:woocommerce_after_shop_loop_item_title}',
				'id'          => Helpers::generate_random_id( false ),
			],
			[
				'dynamicData' => '{woo_product_price}',
				'id'          => Helpers::generate_random_id( false ),
			],
			[
				'dynamicData' => '{woo_add_to_cart}',
				'id'          => Helpers::generate_random_id( false ),
			],
			[
				'dynamicData' => '{do_action:woocommerce_after_shop_loop_item}',
				'id'          => Helpers::generate_random_id( false ),
			],
		];

		$this->controls = array_replace_recursive( $this->controls, $fields );

		$this->controls['linkProduct'] = [
			'tab'         => 'content',
			'group'       => 'fields',
			'label'       => esc_html__( 'Link entire product', 'bricks' ),
			'type'        => 'checkbox',
			'inline'      => true,
			'description' => esc_html__( 'Only added if none of your product fields contains any links.', 'bricks' ),
		];

		// Link to woocommerce-template-hooks dynamic data article
		$this->controls['infoWooCommerceHooks'] = [
			'tab'     => 'content',
			'group'   => 'fields',
			'type'    => 'info',
			'content' => sprintf(
				// translators: %1$s: article link, %2$s: dynamic data tag
				esc_html__( 'Learn which %1$s you should to add to the fields above via the %2$s dynamic data tag.', 'bricks' ),
				Helpers::article_link( 'woocommerce-template-hooks', esc_html__( 'WooCommerce template hooks', 'bricks' ) ),
				Helpers::article_link( 'dynamic-data/#advanced', 'do_action' )
			),
		];

		// WIDGETS (before and after grid)

		$widgets = [
			'pagination'   => esc_html__( 'Pagination', 'bricks' ),
			'result_count' => esc_html__( 'Result Count', 'bricks' ),
			'orderby'      => esc_html__( 'Order by', 'bricks' ),
		];

		$this->controls['beforeGrid'] = [
			'tab'      => 'content',
			'group'    => 'widgets',
			'label'    => esc_html__( 'Show Before Grid', 'bricks' ),
			'type'     => 'select',
			'options'  => $widgets,
			'multiple' => true
		];

		$this->controls['afterGrid'] = [
			'tab'      => 'content',
			'group'    => 'widgets',
			'label'    => esc_html__( 'Show After Grid', 'bricks' ),
			'type'     => 'select',
			'options'  => $widgets,
			'multiple' => true
		];

		$this->controls['sortbyOptions'] = [
			'tab'         => 'content',
			'group'       => 'widgets',
			'label'       => esc_html__( 'Sort by options', 'bricks' ),
			'type'        => 'select',
			'options'     => Woocommerce_Helpers::get_default_orderby_control_options(),
			'multiple'    => true,
			'placeholder' => esc_html__( 'Default sorting', 'bricks' ),
		];
	}

	public function render_grid_widgets( $zone ) {
		$widgets = isset( $this->settings[ "{$zone}Grid" ] ) ? $this->settings[ "{$zone}Grid" ] : false;

		if ( ! $widgets ) {
			return;
		}

		add_filter( 'woocommerce_catalog_orderby', [ $this, 'woocommerce_catalog_orderby' ] );

		echo '<div class="bricks-products-widgets ' . $zone . '">';

		foreach ( $widgets as $widget ) {
			if ( $widget === 'pagination' ) {
				woocommerce_pagination();
			} elseif ( $widget === 'result_count' ) {
				woocommerce_result_count();
			} elseif ( $widget === 'orderby' ) {
				woocommerce_catalog_ordering();
			}
		}

		echo '</div>';

		remove_filter( 'woocommerce_catalog_orderby', [ $this, 'woocommerce_catalog_orderby' ] );
	}

	public function woocommerce_catalog_orderby( $orderby ) {
		if ( ! empty( $this->settings['sortbyOptions'] ) ) {
			$orderby = array_intersect_key( Woocommerce_Helpers::get_default_orderby_control_options(), array_fill_keys( $this->settings['sortbyOptions'], '' ) );
		}

		return $orderby;
	}

	public function render() {
		$settings = $this->settings;

		// Auto-merge on Woo product archive pages
		$merge_with_global_query = Woocommerce_Helpers::is_archive_product();

		// Add 'woocommerce' class to add '.button' style on non-woo pages
		$this->set_attribute( 'wrapper', 'class', [ 'products', 'woocommerce' ] );

		// Query: Force the post type and feed the Bricks Query class
		$settings['query']['post_type']           = [ 'product' ];
		$settings['query']['ignore_sticky_posts'] = true;

		// @since 1.9.1 - Set is_archive_main_query inside query key so the Query class understands
		if ( isset( $settings['is_archive_main_query'] ) ) {
			$settings['query']['is_archive_main_query'] = true;
		}

		$query_object = new Query(
			[
				'id'       => $this->id,
				'settings' => $settings,
			]
		);

		$query = $query_object->query_result;

		// Remove ordering query arguments which may have been added by get_catalog_ordering_args
		WC()->query->remove_ordering_args();

		// No products: Show placeholder or default template
		if ( ! $query->have_posts() ) {
			$query_object->destroy();

			do_action( 'woocommerce_no_products_found' );

			return;
		}

		// Set up loop (needed to trigger results, orderby and pagination elements)
		wc_setup_loop(
			[
				'columns'      => ! empty( $settings['columns'] ) ? $settings['columns'] : 4,
				'name'         => 'bricks-products',
				'is_shortcode' => true,
				'is_search'    => false,
				'is_paginated' => true,
				'total'        => (int) $query->found_posts,
				'total_pages'  => (int) $query->max_num_pages,
				'per_page'     => (int) $query->get( 'posts_per_page' ),
				'current_page' => (int) max( 1, $query->get( 'paged', 1 ) ),
			]
		);

		echo "<div {$this->render_attributes( '_root' )}>";

		// HTML starts here
		$this->render_grid_widgets( 'before' );

		// @see: woocommerce_product_loop_start()
		echo "<ul {$this->render_attributes( 'wrapper' )}>";

		// Default WooCommerce loop template
		if ( empty( $settings['fields'] ) || ! is_array( $settings['fields'] ) ) {
			while ( $query->have_posts() ) {
				$query->the_post();

				wc_get_template_part( 'content', 'product' );
			}

			wp_reset_postdata();
		}

		// Render custom fields
		else {
			$post_index = 0;

			// Image classes
			$image_classes = [ 'image', 'css-filter' ];

			// Lazy load image on frontend
			if ( $this->lazy_load() ) {
				$image_classes[] = 'bricks-lazy-hidden';
			}

			while ( $query->have_posts() ) {
				$query->the_post();

				$post = get_post();

				$item_classes = [ 'repeater-item' ];

				// Populate standard WooCommerce product loop item classes
				$item_classes = array_merge( $item_classes, wc_get_product_class( '', $post ) );

				$this->set_attribute( "item-$post_index", 'class', $item_classes );

				$this->render_fields( $image_classes, $post, $post_index );

				// Reset classes before next loop
				$item_classes = [];

				$post_index++;
			}

			wp_reset_postdata();
		}

		if ( $query_object ) {
			$query_object->destroy();
		}

		// @see: woocommerce_product_loop_end();
		echo '</ul>';

		$this->render_grid_widgets( 'after' );

		if ( ! $merge_with_global_query || bricks_is_builder_call() || bricks_is_builder() ) {
			// Not using global query: Reset loop
			wc_reset_loop();
		}

		echo '</div>';
	}

	public function render_fields( $image_classes, $post, $post_index ) {
		$content = Frontend::get_content_wrapper( $this->settings, $this->settings['fields'], $post );

		echo "<li {$this->render_attributes( "item-$post_index" )}>";

		if ( isset( $this->settings['linkProduct'] ) && strpos( $content, '<a ' ) === false ) {
			echo '<a href="' . get_the_permalink( $post ) . '">';
		}

		echo $content;

		// Badge: "New"
		echo Woocommerce::badge_new();

		// Badge: "Sale"
		wc_get_template( 'loop/sale-flash.php' );

		if ( isset( $this->settings['linkProduct'] ) && strpos( $content, '<a ' ) === false ) {
			echo '</a>';
		}

		echo '</li>';
	}

}
