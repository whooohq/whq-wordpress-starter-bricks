<?php
namespace Bricks\Integrations\Form\Actions;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Redirect extends Base {
	/**
	 * Redirect action
	 *
	 * @since 1.0
	 */
	public function run( $form ) {
		$redirect_to = false;

		$form_settings = $form->get_settings();

		if ( isset( $form_settings['redirect'] ) ) {
			$redirect_to = $form_settings['redirect'];
		}

		// Redirect to admin area
		if ( isset( $form_settings['redirectAdminUrl'] ) ) {
			$redirect_to = isset( $form_settings['redirect'] ) ? admin_url( $form_settings['redirect'] ) : admin_url();

			if ( is_multisite() ) {
				$user_sites = get_blogs_of_user( $login_response->ID );

				foreach ( $user_sites as $site_id => $site ) {
					// Skip main site
					if ( $site_id !== 1 ) {
						$redirect_to = isset( $form_settings['redirect'] ) ? get_admin_url( $site_id, $form_settings['redirect'] ) : get_admin_url( $site_id );
					}
				}
			}
		}

		if ( $redirect_to ) {
			$form->set_result(
				[
					'action'          => $this->name,
					'type'            => 'redirect',
					'redirectTo'      => $redirect_to,
					'redirectTimeout' => isset( $form_settings['redirectTimeout'] ) ? intval( $form_settings['redirectTimeout'] ) : 0
				]
			);
		}
	}
}
