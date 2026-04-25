<?php

namespace WCML\Compatibility\FacebookForWc;

use WCML\Compatibility\ComponentFactory;

/**
 * @see https://woocommerce.com/products/facebook/
 */
class Factory extends ComponentFactory {

	/**
	 * @inheritDoc
	 */
	public function create() {
		return new MultilingualHooks();
	}
}
