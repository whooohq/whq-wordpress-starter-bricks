<?php

namespace WCML\Compatibility\WcTabManager;

use WCML\Compatibility\ComponentFactory;
use WCML_Tab_Manager;
use function WCML\functions\getSitePress;
use function WCML\functions\getWooCommerceWpml;

class Factory extends ComponentFactory {

	/**
	 * @inheritDoc
	 */
	public function create() {
		$hooks = [];

		$hooks[] = new WCML_Tab_Manager( getSitePress(), getWooCommerceWpml(), self::getWpdb(), self::getElementTranslationPackage() );
		$hooks[] = new TranslationEditor\GroupsAndLabels();

		return $hooks;
	}
}
