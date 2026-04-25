<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Woocommerce_Account_Orders extends Woo_Element {
	public $name            = 'woocommerce-account-orders';
	public $icon            = 'ti-layout-list-thumb-alt';
	public $panel_condition = [ 'templateType', '=', 'wc_account_orders' ];

	public function get_label() {
		return esc_html__( 'Account', 'bricks' ) . ' - ' . esc_html__( 'Orders', 'bricks' );
	}

	public function set_control_groups() {
		$this->control_groups['table'] = [
			'title' => esc_html__( 'Table', 'bricks' ),
		];

		$this->control_groups['pagination'] = [
			'title' => esc_html__( 'Pagination', 'bricks' ),
		];
	}

	public function set_controls() {
		// TABLE
		$table_controls = $this->generate_standard_controls( 'table', '.woocommerce-orders-table' );
		unset( $table_controls['tableMargin'] );
		unset( $table_controls['tablePadding'] );

		$table_controls = $this->controls_grouping( $table_controls, 'table' );
		$this->controls = array_merge( $this->controls, $table_controls );

		// HEAD
		$this->controls['theadSep'] = [
			'group' => 'table',
			'type'  => 'separator',
			'label' => esc_html__( 'Head', 'bricks' ),
		];

		$thead_controls = $this->generate_standard_controls( 'thead', '.woocommerce-orders-table thead th, .woocommerce-orders-table tbody td::before' );
		unset( $thead_controls['theadMargin'] );
		unset( $thead_controls['theadBoxShadow'] );

		$thead_controls = $this->controls_grouping( $thead_controls, 'table' );
		$this->controls = array_merge( $this->controls, $thead_controls );

		// BODY
		$this->controls['tbodySep'] = [
			'group' => 'table',
			'type'  => 'separator',
			'label' => esc_html__( 'Body', 'bricks' ),
		];

		$tbody_controls = $this->generate_standard_controls( 'tbody', '.woocommerce-orders-table tbody td' );
		unset( $tbody_controls['tbodyMargin'] );
		unset( $tbody_controls['tbodyBoxShadow'] );

		$tbody_controls = $this->controls_grouping( $tbody_controls, 'table' );
		$this->controls = array_merge( $this->controls, $tbody_controls );

		// LINKS
		$this->controls['tbodyLinksSep'] = [
			'group' => 'table',
			'type'  => 'separator',
			'label' => esc_html__( 'Links', 'bricks' ),
		];

		$tbody_links_controls = $this->generate_standard_controls( 'tbodyLinks', '.woocommerce-orders-table tbody td a:not(.woocommerce-button)', [ 'typography' ] );
		$tbody_links_controls = $this->controls_grouping( $tbody_links_controls, 'table' );
		$this->controls       = array_merge( $this->controls, $tbody_links_controls );

		// BUTTON
		$this->controls['buttonSeparator'] = [
			'group' => 'table',
			'type'  => 'separator',
			'label' => esc_html__( 'Button', 'bricks' ),
		];

		$button_controls = $this->generate_standard_controls( 'button', '.woocommerce-orders-table a.woocommerce-button' );
		$button_controls = $this->controls_grouping( $button_controls, 'table' );
		unset( $button_controls['buttonMargin'] );

		$this->controls = array_merge( $this->controls, $button_controls );

		// PAGINATION

		$this->controls['paginationInfo'] = [
			'group'   => 'pagination',
			'type'    => 'info',
			'content' => esc_html__( 'Always visible in builder for styling purpose.', 'bricks' ),
		];

		$pagination_controls = $this->generate_standard_controls( 'pagination', '.woocommerce-pagination' );
		$pagination_controls = $this->controls_grouping( $pagination_controls, 'pagination' );

		$this->controls = array_merge( $this->controls, $pagination_controls );

		// BUTTON
		$this->controls['paginationButtonSeparator'] = [
			'group' => 'pagination',
			'type'  => 'separator',
			'label' => esc_html__( 'Button', 'bricks' ),
		];

		$pagination_button_controls = $this->generate_standard_controls( 'paginationButton', '.woocommerce-pagination a.woocommerce-button' );
		$pagination_button_controls = $this->controls_grouping( $pagination_button_controls, 'pagination' );
		unset( $pagination_button_controls['paginationButtonMargin'] );

		$this->controls = array_merge( $this->controls, $pagination_button_controls );
	}

	public function render() {
		global $wp;

		// Get woo template
		ob_start();

		$current_page = isset( $wp->query_vars['orders'] ) && ! empty( $wp->query_vars['orders'] ) ? absint( $wp->query_vars['orders'] ) : 1;
		$is_preview   = bricks_is_builder() || bricks_is_builder_call() || Helpers::is_bricks_template( $this->post_id );

		// NOTE: Not in use to avoid showing no order in case user doesn't have any orders beyond page 1
		// Downside is we can't show "Prev" pagination button in builder
		// Fake current page to show pagination in builder and template preview
		// if ( $is_preview ) {
		// $current_page = 2;
		// }

		$customer_orders = wc_get_orders(
			apply_filters(
				'woocommerce_my_account_my_orders_query',
				[
					'customer' => get_current_user_id(),
					'page'     => $current_page,
					'paginate' => true,
				]
			)
		);

		// To always show pagination in builder to style it
		if ( $is_preview ) {
			$customer_orders->max_num_pages = 3;
		}

		$has_orders      = $customer_orders ? $customer_orders->total > 0 : false;
		$wp_button_class = wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '';

		wc_get_template(
			'myaccount/orders.php',
			[
				'current_page'    => $current_page,
				'customer_orders' => $customer_orders,
				'has_orders'      => $has_orders,
				'wp_button_class' => $wp_button_class,
			]
		);

		$woo_template = ob_get_clean();

		// Render Woo template
		echo "<div {$this->render_attributes( '_root' )}>{$woo_template}</div>";
	}
}
