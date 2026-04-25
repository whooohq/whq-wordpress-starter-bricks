<?php

namespace WCML\OrderItems;

interface TranslatorFactory {

	/**
	 * @param \WC_Order_Item $item
	 *
	 * @return Translator|null
	 */
	public function getTranslator( $item );

}
