<?php

class WCML_WC_Product_Bundles_Items {

	/**
	 * @param int $product_id
	 *
	 * @return array
	 */
	public function get_items( $product_id ) {

		$items          = [];
		$product_bundle = new WC_Product_Bundle( $product_id );
		if ( $product_bundle ) {
			$items = $product_bundle->get_bundled_items();
		}
		return $items;

	}

	/**
	 * @param WC_Bundled_Item $bundled_item
	 *
	 * @return array
	 */
	public function get_item_data( $bundled_item ) {
		$item_data = $bundled_item->get_data();
		// #wcml-1927 - Insufficient Stock issue
		if ( $item_data['max_stock'] === null ) {
			$item_data['max_stock'] = '';
		}
		return $item_data;
	}

	public function copy_item_data( $item_id_1, $item_id_2 ) {

		$item_1_data = $this->get_item_data_object( $item_id_1 );
		$item_2_data = $this->get_item_data_object( $item_id_2 );

		$meta_data = $item_1_data->get_meta_data();

		foreach ( $meta_data as $key => $value ) {
			$item_2_data->update_meta( $key, $value );
		}

		$item_2_data->save();
	}

	/**
	 * @param int $item_id
	 *
	 * @return WC_Bundled_Item_Data
	 */
	public function get_item_data_object( $item_id ) {
		return new WC_Bundled_Item_Data( $item_id );
	}

	/**
	 * @param WC_Bundled_Item_Data $bundled_item_data
	 * @param string               $key
	 * @param mixed                $value
	 */
	public function update_item_meta( $bundled_item_data, $key, $value ) {
		$bundled_item_data->update_meta( $key, $value );
	}

	/**
	 * @param WC_Bundled_Item_Data $bundled_item_data
	 */
	public function save_item_meta( $bundled_item_data ) {
		$bundled_item_data->save();
	}


}


