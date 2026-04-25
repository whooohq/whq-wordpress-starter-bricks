<?php

namespace WCML\PostHog;

use WPML\LIB\WP\Hooks as WpHooks;
use function WPML\FP\spreadArgs;

class Hooks implements \IWPML_Backend_Action {

	public function add_hooks() {
		WpHooks::onFilter( 'wpml_posthog_allowed_pages' )
			->then( spreadArgs( function( $allowedPages ) {
				return array_merge(
					$allowedPages,
					[
						\WCML_Admin_Menus::SLUG,
						\WCML_Setup_UI::SLUG,
					]
				);
			} ) );
	}
}
