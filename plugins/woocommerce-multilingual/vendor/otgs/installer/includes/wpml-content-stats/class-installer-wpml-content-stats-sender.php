<?php

use OTGS\Installer\CDTClient\Api\Endpoints\v1\Requests\Actions\AddStats\Action as AddStatsAction;

class WPML_Content_Stats_Sender {

	/** @var WP_Installer */
	private $installer;

	/** @var OTGS_Installer_WP_Share_Local_Components_Setting */
	private $settings;

	private $action;

	public function __construct(
		WP_Installer $installer,
		OTGS_Installer_WP_Share_Local_Components_Setting $settings,
		\OTGS\Installer\CDTClient\Api\Endpoints\v1\Requests\Actions\AddStats\Action $action
	) {
		$this->installer = $installer;
		$this->settings  = $settings;
		$this->action    = $action;
	}

	/**
	 * @param array{
	 *     siteKey: string,
	 *     currentTranslationEditor: string,
	 *     siteUUID: string,
	 *     siteUrl: string,
	 *     siteSharedKey: string,
	 *     defaultLanguage: array{
	 *     code: string,
	 *     defaultLocale: string,
	 *     nativeName: string,
	 *     englishName: string,
	 *     displayName: string,
	 *  },
	 *     translationLanguages: array{
	 *     code: string,
	 *     defaultLocale: string,
	 *     nativeName: string,
	 *     englishName: string,
	 *     displayName: string,
	 *  }[],
	 *     contentStats: array<string, array{
	 *     postsCount: int,
	 *     charactersCount: int,
	 *     translationCoverage: array<string, float|int>
	 *  }>
	 * } $data
	 *
	 * @return bool
	 */
	public function send( array $data ) {
		if ( ! $this->installer->get_repositories() ) {
			$this->installer->load_repositories_list();
		}

		if ( ! $this->installer->get_settings() ) {
			$this->installer->save_settings();
		}

		if ( ! $this->settings->is_repo_allowed( 'wpml' ) ) {
			return false;
		}

		$response = $this->action->run( $data );

		return $response->isSuccessful();
	}

}
