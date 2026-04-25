<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Woocommerce_Account_Downloads extends Woo_Element {
	public $name            = 'woocommerce-account-downloads';
	public $icon            = 'ti-download';
	public $panel_condition = [ 'templateType', '=', 'wc_account_downloads' ];
	private $downloads;
	private $has_downloads;

	public function get_label() {
		return esc_html__( 'Account', 'bricks' ) . ' - ' . esc_html__( 'Downloads', 'bricks' );
	}

	public function set_controls() {
		// TABLE
		$this->controls['taleSep'] = [
			'type'  => 'separator',
			'label' => esc_html__( 'Table', 'bricks' ),
		];

		$table_controls = $this->generate_standard_controls( 'table', '.woocommerce-table--order-downloads' );
		unset( $table_controls['tableMargin'] );
		unset( $table_controls['tablePadding'] );

		$this->controls = array_merge( $this->controls, $table_controls );

		// HEAD
		$this->controls['theadSep'] = [
			'type'  => 'separator',
			'label' => esc_html__( 'Head', 'bricks' ),
		];

		$thead_controls = $this->generate_standard_controls( 'thead', 'thead th, tbody td::before' );
		unset( $thead_controls['theadMargin'] );
		unset( $thead_controls['theadBoxShadow'] );

		$this->controls = array_merge( $this->controls, $thead_controls );

		// BODY
		$this->controls['tbodySep'] = [
			'type'  => 'separator',
			'label' => esc_html__( 'Body', 'bricks' ),
		];

		$tbody_controls = $this->generate_standard_controls( 'tbody', 'tbody td' );
		unset( $tbody_controls['tbodyMargin'] );
		unset( $tbody_controls['tbodyBoxShadow'] );

		$this->controls = array_merge( $this->controls, $tbody_controls );

		// LINKS
		$this->controls['tbodyLinksSep'] = [
			'type'  => 'separator',
			'label' => esc_html__( 'Links', 'bricks' ),
		];

		$tbody_links_controls = $this->generate_standard_controls( 'tbodyLinks', 'tbody td a:not(.woocommerce-MyAccount-downloads-file.button)' );
		$this->controls       = array_merge( $this->controls, $tbody_links_controls );

		// BUTTON
		$this->controls['buttonSep'] = [
			'type'  => 'separator',
			'label' => esc_html__( 'Button', 'bricks' ),
		];

		$button_controls = $this->generate_standard_controls( 'button', '.woocommerce-MyAccount-downloads-file.button' );
		unset( $button_controls['buttonMargin'] );

		$this->controls = array_merge( $this->controls, $button_controls );
	}

	public function render() {
		// Get woo template
		ob_start();

		wc_get_template( 'myaccount/downloads.php' );

		$woo_template = ob_get_clean();

		// Render Woo template
		echo "<div {$this->render_attributes( '_root' )}>{$woo_template}</div>";
	}
}
