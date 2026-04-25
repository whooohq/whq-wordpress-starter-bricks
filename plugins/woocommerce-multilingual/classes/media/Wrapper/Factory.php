<?php

namespace WCML\Media\Wrapper;

use WCML\StandAlone\NullSitePress;

class Factory {

	/**
	 * @return IMedia
	 */
	public static function create() {
		/**
		 * @var \SitePress $sitepress
		 * @var \wpdb      $wpdb
		 */
		global $sitepress, $wpdb;

		$settingsFactory = new \WPML_Element_Sync_Settings_Factory();

		if ( $settingsFactory->create( 'post' )->is_sync( 'attachment' ) ) {
			return new Translatable( $sitepress, $wpdb );
		}

		return new NonTranslatable();
	}
}
