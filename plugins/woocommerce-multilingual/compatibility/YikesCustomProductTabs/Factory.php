<?php

namespace WCML\Compatibility\YikesCustomProductTabs;

use WCML\Compatibility\ComponentFactory;
use function WCML\functions\getSitePress;
use function WCML\functions\getWooCommerceWpml;

/**
 * IMPORTANT NOTICE !!!
 * This target plugin is not maintained anymore.
 * We are stopping our compatibility maintenance too.
 *
 * @deprecated
 */
class Factory extends ComponentFactory {

	/**
	 * @inheritDoc
	 */
	public function create() {
		return [
			new \WCML_YIKES_Custom_Product_Tabs( getSitePress(), self::getElementTranslationPackage() ),
			new JobHooks(),
		];
	}
}
