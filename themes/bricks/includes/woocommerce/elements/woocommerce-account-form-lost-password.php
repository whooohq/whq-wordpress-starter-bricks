<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Woocommerce_Account_Form_Lost_Password extends Woo_Element {
	public $name            = 'woocommerce-account-form-lost-password';
	public $icon            = 'fas fa-passport';
	public $panel_condition = [ 'templateType', '=', 'wc_account_form_lost_password' ];

	public function get_label() {
		return esc_html__( 'Account', 'bricks' ) . ' - ' . esc_html__( 'Lost password', 'bricks' );
	}

	public function set_control_groups() {
		$this->control_groups['message'] = [
			'title' => esc_html__( 'Message', 'bricks' ),
		];

		$this->control_groups['fields'] = [
			'title' => esc_html__( 'Fields', 'bricks' ),
		];

		$this->control_groups['submitButton'] = [
			'title' => esc_html__( 'Submit button', 'bricks' ),
		];
	}

	public function set_controls() {
		// MESSAGE
		$this->controls['messageDisable'] = [
			'group' => 'message',
			'label' => esc_html__( 'Disable', 'bricks' ),
			'type'  => 'checkbox',
			'css'   => [
				[
					'property' => 'display',
					'selector' => 'form > p:first-child',
					'value'    => 'none',
				],
			],
		];

		$this->controls['messageTypography'] = [
			'group'    => 'message',
			'label'    => esc_html__( 'Typography', 'bricks' ),
			'type'     => 'typography',
			'css'      => [
				[
					'property' => 'font',
					'selector' => 'form > p:first-child',
				],
			],
			'required' => [ 'messageDisale', '=', false ],
		];

		// FIELDS
		$fields_controls = $this->get_woo_form_fields_controls( 'form' );
		$fields_controls = $this->controls_grouping( $fields_controls, 'fields' );

		unset( $fields_controls['hideLabels'] );
		unset( $fields_controls['hidePlaceholders'] );
		unset( $fields_controls['placeholderTypography'] );

		$this->controls = array_merge( $this->controls, $fields_controls );

		// SUBMIT BUTTON
		$submit_controls = $this->get_woo_form_submit_controls();
		$submit_controls = $this->controls_grouping( $submit_controls, 'submitButton' );
		$this->controls  = array_merge( $this->controls, $submit_controls );
	}

	public function render() {
		// Get woo template
		ob_start();

		wc_get_template( 'myaccount/form-lost-password.php' );

		$woo_template = ob_get_clean();

		// Render Woo template
		echo "<div {$this->render_attributes( '_root' )}>{$woo_template}</div>";
	}
}
