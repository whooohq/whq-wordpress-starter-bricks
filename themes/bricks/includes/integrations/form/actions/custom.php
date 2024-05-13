<?php
namespace Bricks\Integrations\Form\Actions;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Custom extends Base {
	/**
	 * Custom action
	 *
	 * @since 1.0
	 */
	public function run( $form ) {
		$form_settings = $form->get_settings();
		$form_fields   = $form->get_fields();

		// Supress output to prevent custom actions to break the actions flow
		ob_start();

		// Perform custom action with submitted form data
		// https://academy.bricksbuilder.io/article/form-element/#custom-action
		do_action( 'bricks/form/custom_action', $form );

		ob_end_clean();
	}
}
