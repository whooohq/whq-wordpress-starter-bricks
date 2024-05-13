<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Woocommerce_Account_Form_Edit_Address extends Woo_Element {
	public $name            = 'woocommerce-account-form-edit-address';
	public $icon            = 'ti ti-pencil-alt';
	public $panel_condition = [ 'templateType', '=', 'wc_account_form_edit_address' ];

	public function get_label() {
		return esc_html__( 'Account', 'bricks' ) . ' - ' . esc_html__( 'Edit address', 'bricks' );
	}

	public function set_control_groups() {
		$this->control_groups['title'] = [
			'title' => esc_html__( 'Title', 'bricks' ),
		];

		$this->control_groups['fields'] = [
			'title' => esc_html__( 'Fields', 'bricks' ),
		];

		$this->control_groups['submitButton'] = [
			'title' => esc_html__( 'Submit button', 'bricks' ),
		];
	}

	public function set_controls() {
		// TITLE
		$this->controls['titleHide'] = [
			'group' => 'title',
			'type'  => 'checkbox',
			'label' => esc_html__( 'Hide', 'bricks' ),
			'css'   => [
				[
					'selector' => '> form > h3',
					'property' => 'display',
					'value'    => 'none',
				]
			],
		];

		$title_controls = $this->generate_standard_controls( 'title', '> form > h3' );
		$title_controls = $this->controls_grouping( $title_controls, 'title' );
		$this->controls = array_merge( $this->controls, $title_controls );

		// FIELDS
		$fields_controls = $this->get_woo_form_fields_controls();
		$fields_controls = $this->controls_grouping( $fields_controls, 'fields' );

		unset( $fields_controls['fieldsAlignItems'] );
		unset( $fields_controls['fieldsWidth'] );
		unset( $fields_controls['fieldsGap'] );
		unset( $fields_controls['hideLabels'] );
		unset( $fields_controls['hidePlaceholders'] );

		$this->controls = array_merge( $this->controls, $fields_controls );

		// SUBMIT BUTTON
		$submit_controls = $this->get_woo_form_submit_controls();
		$submit_controls = $this->controls_grouping( $submit_controls, 'submitButton' );
		$this->controls  = array_merge( $this->controls, $submit_controls );
	}

	public function render() {
		/**
		 * STEP: Get the edit address form Woo shortcode
		 *
		 * Much easier than getting the Woo template for this one.
		 */

		global $wp;

		/**
		 * Get the edit address form type (billing or shipping)
		 *
		 * @since 1.9.2: use wc_edit_address_i18n() to get the correct address type
		 */
		$load_address = isset( $wp->query_vars['edit-address'] ) ? wc_edit_address_i18n( sanitize_title( $wp->query_vars['edit-address'] ), true ) : 'billing';

		echo "<div {$this->render_attributes( '_root' )}>";
		echo \WC_Shortcode_My_Account::edit_address( $load_address );
		echo '</div>';
	}
}
