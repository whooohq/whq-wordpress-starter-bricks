<?php
/**
 * Handles remote POST requests when unregistering the sitekey.
 */
class OTGS_Installer_Site_Key_Remove_Request {
	/**
	 * Perform a remote POST request to the repository API.
	 *
	 * @param string $url   The API URL.
	 * @param array  $params The request parameters.
	 *
	 */
	public function run( $url, $params ) {
		return wp_remote_post( $url, $params );
	}

	public function build_params( $repository, $site_key ) {
		$site_url = get_site_url();
		$repository_id = $repository->get_id();
		$timestamp = time();

		$message = $site_key . '|' . $site_url . '|' . $timestamp;
		$signature = hash_hmac( 'sha256', $message, $site_key );

		$params = [
			'body' => [
				'site_key'          => $site_key,
				'site_url'          => $site_url,
				'installer_version' => WP_INSTALLER_VERSION,
				'repository_id'     => $repository_id,
				'timestamp'         => $timestamp,
				'signature'         => $signature,
			],
		];
		$url = $repository->get_api_url() . '?action=unregister_site_key';

		return [
			$url,
			$params
		];
	}
}
