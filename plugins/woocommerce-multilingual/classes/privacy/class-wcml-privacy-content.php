<?php

/**
 * @author OnTheGo Systems
 */
class WCML_Privacy_Content extends WPML_Privacy_Content {

	/**
	 * @return string
	 */
	protected function get_plugin_name() {
		return 'WPML Multilingual & Multicurrency for WooCommerce';
	}

	/**
	 * @return string|array
	 */
	protected function get_privacy_policy() {
		return [
			__( 'WPML Multilingual & Multicurrency for WooCommerce will use cookies to understand the basket info when using languages in domains and to transfer data between the domains.', 'woocommerce-multilingual' ),
			__( 'WPML Multilingual & Multicurrency for WooCommerce will also use cookies to identify the language and currency of each customer’s order as well as the currency of the reports created by WooCommerce. WPML Multilingual & Multicurrency for WooCommerce extends these reports by adding the currency’s information.', 'woocommerce-multilingual' ),
		];
	}

}
