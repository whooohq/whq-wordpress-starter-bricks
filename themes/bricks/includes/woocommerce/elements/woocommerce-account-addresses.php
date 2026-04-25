<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Woocommerce_Account_Addresses extends Woo_Element {
	public $name            = 'woocommerce-account-addresses';
	public $icon            = 'fa fa-address-book';
	public $panel_condition = [ 'templateType', '=', 'wc_account_addresses' ];

	public function get_label() {
		return esc_html__( 'Account', 'bricks' ) . ' - ' . esc_html__( 'Addresses', 'bricks' );
	}

	public function set_control_groups() {
		$this->control_groups['wrapper'] = [
			'title' => esc_html__( 'Wrapper', 'bricks' ),
		];

		$this->control_groups['title'] = [
			'title' => esc_html__( 'Title', 'bricks' ),
		];

		$this->control_groups['editLink'] = [
			'title' => esc_html__( 'Edit link', 'bricks' ),
		];

		$this->control_groups['address'] = [
			'title' => esc_html__( 'Address', 'bricks' ),
		];
	}

	public function set_controls() {
		$this->controls['addressesDirection'] = [
			'label'  => esc_html__( 'Direction', 'bricks' ),
			'type'   => 'direction',
			'inline' => true,
			'css'    => [
				[
					'selector' => '.woocommerce-Addresses',
					'property' => 'flex-direction',
				],
			],
		];

		$this->controls['addressesGap'] = [
			'type'  => 'number',
			'units' => true,
			'label' => esc_html__( 'Gap', 'bricks' ),
			'css'   => [
				[
					'property' => 'gap',
					'selector' => '.woocommerce-Addresses',
				]
			],
		];

		// WRAPPER
		$wrapper_controls = $this->generate_standard_controls( 'wrapper', '.woocommerce-Address' );
		$wrapper_controls = $this->controls_grouping( $wrapper_controls, 'wrapper' );
		$this->controls   = array_merge( $this->controls, $wrapper_controls );

		// TITLE
		$this->controls['titleSep'] = [
			'group' => 'title',
			'type'  => 'separator',
			'label' => esc_html__( 'Title', 'bricks' ),
		];

		$title_controls = $this->generate_standard_controls( 'title', '.woocommerce-Address-title h3' );
		$title_controls = $this->controls_grouping( $title_controls, 'title' );
		$this->controls = array_merge( $this->controls, $title_controls );

		// EDIT LINK
		$edit_link_controls = $this->generate_standard_controls( 'editLink', '.edit' );
		$edit_link_controls = $this->controls_grouping( $edit_link_controls, 'editLink' );
		$this->controls     = array_merge( $this->controls, $edit_link_controls );

		// ADDRESS
		$this->controls['addressSep'] = [
			'group' => 'address',
			'type'  => 'separator',
			'label' => esc_html__( 'Address', 'bricks' ),
		];

		$address_controls = $this->generate_standard_controls( 'address', 'address' );
		$address_controls = $this->controls_grouping( $address_controls, 'address' );
		$this->controls   = array_merge( $this->controls, $address_controls );
	}

	public function render() {
		// Get woo template
		ob_start();

		wc_get_template( 'myaccount/my-address.php' );

		$woo_template = ob_get_clean();

		// Render Woo template
		echo "<div {$this->render_attributes( '_root' )}>{$woo_template}</div>";
	}
}
