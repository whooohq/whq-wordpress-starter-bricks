<?php

namespace WCML\OrderItems;

interface Translator {

	/**
	 * @param \WC_Order_Item $item
	 * @param string         $targetLanguage
	 */
	public function translateItem( $item, $targetLanguage );

}
