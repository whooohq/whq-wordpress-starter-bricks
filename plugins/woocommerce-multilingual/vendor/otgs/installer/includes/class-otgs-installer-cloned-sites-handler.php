<?php

class OTGS_Installer_Cloned_Sites_Handler {

	/** @var OTGS_Installer_Site_Key_Remove_Service */
	private $site_key_remove_service;

	/**
	 * @param OTGS_Installer_Site_Key_Remove_Service $site_key_remove_service
	 */
	public function __construct( OTGS_Installer_Site_Key_Remove_Service $site_key_remove_service ) {
		$this->site_key_remove_service = $site_key_remove_service;
	}

	/**
	 * Adds hooks for handling cloned sites.
	 */
	public function add_hooks() {
		add_action( 'wpml_tm_cloned_site_reported', [ $this, 'handle_cloned_site' ] );
	}

	public function handle_cloned_site() {
		if ( $this->isWPMLSiteKeyDefinedInWPConfig() ) {
			// In this case, the internal Installer workflow will take care of validating and removing site key if needed.
			return;
		}

		$this->site_key_remove_service->remove( 'wpml', false );
	}

	private function isWPMLSiteKeyDefinedInWPConfig(): bool {
		return class_exists( 'WP_Installer' ) && WP_Installer::get_repository_hardcoded_site_key( 'wpml' );
	}
}