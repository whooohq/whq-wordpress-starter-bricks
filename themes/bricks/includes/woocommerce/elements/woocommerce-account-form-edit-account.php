<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Woocommerce_Account_Form_Edit_Account extends Woo_Element {
	public $name            = 'woocommerce-account-form-edit-account';
	public $icon            = 'fas fa-user-edit';
	public $panel_condition = [ 'templateType', '=', 'wc_account_form_edit_account' ];

	public function get_label() {
		return esc_html__( 'Account', 'bricks' ) . ' - ' . esc_html__( 'Edit account', 'bricks' );
	}

	public function set_control_groups() {
		$this->control_groups['field'] = [
			'title' => esc_html__( 'Field', 'bricks' ),
		];

		$this->control_groups['fieldset'] = [
			'title' => esc_html__( 'Fieldset', 'bricks' ),
		];

		$this->control_groups['submitButton'] = [
			'title' => esc_html__( 'Submit button', 'bricks' ),
		];
	}

	public function set_controls() {
		// FIELD
		$fields_controls = $this->get_woo_form_fields_controls();
		$fields_controls = $this->controls_grouping( $fields_controls, 'field' );

		unset( $fields_controls['fieldsSep'] );
		unset( $fields_controls['fieldsAlignItems'] );
		unset( $fields_controls['fieldsWidth'] );
		unset( $fields_controls['fieldsGap'] );

		// Remove as password change does not have any placeholder in Woo template
		unset( $fields_controls['hideLabels'] );
		unset( $fields_controls['hidePlaceholders'] );
		unset( $fields_controls['placeholderTypography'] );

		$fields_controls['fieldsInputMargin']['css'][0]['selector'] = '.woocommerce-form-row';

		$this->controls = array_merge( $this->controls, $fields_controls );

		// FIELDSET
		$fieldset_controls = $this->get_woo_form_fieldset_controls();
		$fieldset_controls = $this->controls_grouping( $fieldset_controls, 'fieldset' );
		$this->controls    = array_merge( $this->controls, $fieldset_controls );

		// SUBMIT BUTTON
		$submit_controls = $this->get_woo_form_submit_controls();
		$submit_controls = $this->controls_grouping( $submit_controls, 'submitButton' );
		$this->controls  = array_merge( $this->controls, $submit_controls );
	}

	public function render() {
		/**
			 * STEP: Get woo template
			 *
			 * Pass required $user to Woo template
			 */
		$user = wp_get_current_user();

		ob_start();

		wc_get_template(
			'myaccount/form-edit-account.php',
			[
				'user' => $user,
			]
		);

		$woo_template = ob_get_clean();

		// Render Woo template
		echo "<div {$this->render_attributes( '_root' )}>{$woo_template}</div>";
	}
}
