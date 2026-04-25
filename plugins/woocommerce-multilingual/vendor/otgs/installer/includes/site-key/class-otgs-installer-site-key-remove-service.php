<?php

class  OTGS_Installer_Site_Key_Remove_Service {

	const RETRY_CRON_HOOK     = 'otgs_installer_site_key_remove_retry';
	const RETRY_CRON_INTERVAL = 10 * MINUTE_IN_SECONDS;

	/** @var OTGS_Installer_Repositories */
	private $repositories;

	/** @var OTGS_Installer_Site_Key_Remove_Request */
	private $removeApi;

	public function __construct(
		OTGS_Installer_Repositories $repositories,
		OTGS_Installer_Site_Key_Remove_Request $removeApi
	) {
		$this->repositories = $repositories;
		$this->removeApi    = $removeApi;

		add_action( self::RETRY_CRON_HOOK, [ $this, 'cron_retry_handler' ], 10, 2 );
	}


	public function remove( string $repository = 'wpml', bool $notifyExternalApi = true ) {
		$repository = $this->repositories->get( $repository );

		if ( $notifyExternalApi ) {
			$site_key = $repository->get_subscription()->get_site_key();
			list( $url, $params ) = $this->removeApi->build_params( $repository, $site_key );
		}

		$repository->set_subscription( null );
		$this->repositories->save_subscription( $repository );

		if ( $notifyExternalApi ) {
			$response = $this->removeApi->run( $url, $params );

			$body = maybe_unserialize( wp_remote_retrieve_body( $response ) );
			if ( isset( $body->success ) && ! $body->success ) {
				$this->schedule_retry( $repository, $site_key );
			}
		}

		do_action( 'otgs_installer_clean_plugins_update_cache' );
		do_action( 'otgs_installer_site_key_update', $repository->get_id() );

		$this->repositories->refresh();
	}

	private function schedule_retry( $repository, $site_key ) {
		if ( ! wp_next_scheduled( self::RETRY_CRON_HOOK, [ $repository, $site_key ] ) ) {
			wp_schedule_single_event( time() + self::RETRY_CRON_INTERVAL, self::RETRY_CRON_HOOK, [ $repository, $site_key ] );
		}
	}

	private function cron_retry_handler( $repository, $site_key ) {
		// Build params again with a new timestamp value.
		// Because the request to API will be expired in 60 seconds.
		list( $url, $params ) = $this->removeApi->build_params( $repository, $site_key );
		$this->removeApi->run( $url, $params );
	}

}
