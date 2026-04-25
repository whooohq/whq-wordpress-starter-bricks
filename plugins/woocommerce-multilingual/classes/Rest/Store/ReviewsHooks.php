<?php

namespace WCML\Rest\Store;

use WPML\FP\Fns;
use function WCML\functions\getSetting;

class ReviewsHooks implements \IWPML_Action {

	public function add_hooks() {
		if ( getSetting( 'reviews_in_all_languages', false ) ) {
			add_action( 'wpml_is_comment_query_filtered', Fns::always( false ) );
		}
	}
}
