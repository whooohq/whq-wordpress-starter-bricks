<?php

namespace WCML\Compatibility\Sensei;

use WCML\Compatibility\ComponentFactory;
use WCML_Sensei;
use WPML_Custom_Columns;
use function WCML\functions\getSitePress;

class Factory extends ComponentFactory {

	/**
	 * @inheritDoc
	 */
	public function create() {
		$sitepress = getSitePress();

		return new WCML_Sensei( $sitepress, new WPML_Custom_Columns( $sitepress ) );
	}
}
