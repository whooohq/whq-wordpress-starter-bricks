<?php

namespace WCML\OrderItems\Shipping;

trait StoreInDefaultLanguage {

	/**
	 * @param \WC_Order_Item $item
	 * @param string         $titleInDefaultLanguage
	 * @param  string        $targetLanguage
	 */
	public function maybeSaveItem( $item, $titleInDefaultLanguage, $targetLanguage ) {
		if ( ! $item instanceof \WC_Order_Item_Shipping ) {
			return;
		}

		$forceSaveInDefaultLanguage = true;
		/**
		 * Decide whether the stored label for the order item shipping should be forced to be in the default language.
		 *
		 * Having the label for the order item shipping stored in the database as in the default language saves at least one unconfortable query:
		 * when translating from any secondary language, we first need to find the translation in the original language, and then translate to the target language.
		 *
		 * We keep this filter for backward compatibility: it was requested in wcml-3334.
		 * In theory, the underlying reason is no longer valid/relevant, but still.
		 *
		 * @since 4.11.0
		 *
		 * @param  bool          $forceSaveInDefaultLanguage
		 * @param \WC_Order_Item $item
		 * @param  string        $targetLanguage
		 */
		$forceSaveInDefaultLanguage = apply_filters( 'wcml_should_save_adjusted_order_item_in_language', $forceSaveInDefaultLanguage, $item, $targetLanguage );

		if ( $forceSaveInDefaultLanguage ) {
			$originalMethodTitle = $item->get_method_title();
			$item->set_method_title( $titleInDefaultLanguage );
			$item->save();
			$item->set_method_title( $originalMethodTitle );
		}
	}

}
