<?php

namespace OTGS\Installer\CDTClient\Api\Endpoints\v1\Requests\Actions\AddStats;

use OTGS\Installer\CDTClient\Api\ValidatorInterface;

class Validator implements ValidatorInterface {

	/** @var array */
	private $body;


	/**
	 * @param array{
	 *     request_uuid: string,
	 *     request_type: string,
	 *     data: array{
	 *      siteKey: string,
	 *      currentTranslationEditor: string,
	 *      siteUUID: string|null,
	 *      siteUrl: string|null,
	 *      siteSharedKey: string,
	 *      defaultLanguage: array{
	 *      code: string,
	 *      defaultLocale: string,
	 *      nativeName: string,
	 *      englishName: string,
	 *      displayName: string,
	 *   },
	 *      translationLanguages: array{
	 *      code: string,
	 *      defaultLocale: string,
	 *      nativeName: string,
	 *      englishName: string,
	 *      displayName: string,
	 *   }[],
	 *      contentStats: array<string, array{
	 *      postsCount: int,
	 *      charactersCount: int,
	 *      translationCoverage: array<string, float|int>
	 *   }>
	 *     }
	 * } $body
	 */
	public function __construct( array $body ) {
		$this->body = $body;
	}


	public function validate() {
		$result = $this->validateRequestUuid() &&
		          $this->validateRequestType() &&
		          $this->validateCreatedAt();

		if ( ! $result || ! array_key_exists( 'data', $this->body ) ) {
			return false;
		}

		return $this->validateSiteKey() &&
		       $this->validateSiteUrl() &&
		       $this->validateTranslationEditor() &&
		       $this->validateSiteUuid() &&
		       $this->validateSiteSharedKey() &&
		       $this->validateDefaultLanguage() &&
		       $this->validateTranslationLanguages() &&
		       $this->validateContentStats();
	}


	/**
	 * @return bool
	 */
	private function validateRequestUuid() {
		return array_key_exists( 'request_uuid', $this->body ) &&
		       is_string( $this->body['request_uuid'] ) &&
		       ! empty( $this->body['request_uuid'] );
	}


	/**
	 * @return bool
	 */
	private function validateRequestType() {
		return array_key_exists( 'request_type', $this->body ) &&
		       is_string( $this->body['request_type'] ) &&
		       ! empty( $this->body['request_type'] );
	}


	private function validateCreatedAt() {
		return array_key_exists( 'created_time', $this->body ) &&
		       is_int( $this->body['created_time'] );
	}


	/**
	 * @return bool
	 */
	private function validateSiteKey() {
		return array_key_exists( 'siteKey', $this->body['data'] ) &&
		       is_string( $this->body['data']['siteKey'] ) &&
		       ! empty( $this->body['data']['siteKey'] );
	}


	/**
	 * @return bool
	 */
	private function validateSiteUrl() {
		return array_key_exists( 'siteUrl', $this->body['data'] ) &&
		       is_string( $this->body['data']['siteUrl'] ) &&
		       ! empty( $this->body['data']['siteUrl'] );
	}


	/**
	 * @return bool
	 */
	private function validateTranslationEditor() {
		return array_key_exists( 'currentTranslationEditor', $this->body['data'] ) &&
		       is_string( $this->body['data']['currentTranslationEditor'] ) &&
		       ! empty( $this->body['data']['currentTranslationEditor'] );
	}


	/**
	 * @return bool
	 */
	private function validateSiteUuid() {
		return array_key_exists( 'siteUUID', $this->body['data'] ) &&
		       (
			       is_string( $this->body['data']['siteUUID'] ) ||
			       is_null( $this->body['data']['siteUUID'] )
		       );
	}


	/**
	 * @return bool
	 */
	private function validateSiteSharedKey() {
		return array_key_exists( 'siteSharedKey', $this->body['data'] ) &&
		       (
			       is_string( $this->body['data']['siteSharedKey'] ) ||
			       is_null( $this->body['data']['siteSharedKey'] )
		       );
	}


	/**
	 * @return bool
	 */
	private function validateDefaultLanguage() {
		return array_key_exists( 'defaultLanguage', $this->body['data'] ) &&
		       is_array( $this->body['data']['defaultLanguage'] ) &&
		       array_key_exists( 'code', $this->body['data']['defaultLanguage'] ) &&
		       is_string( $this->body['data']['defaultLanguage']['code'] ) &&
		       ! empty( $this->body['data']['defaultLanguage']['code'] );
	}


	/**
	 * @return bool
	 */
	private function validateTranslationLanguages() {
		$result = array_key_exists( 'translationLanguages', $this->body['data'] ) &&
		          is_array( $this->body['data']['translationLanguages'] );

		if ( ! $result ) {
			return false;
		}

		foreach ( $this->body['data']['translationLanguages'] as $translationLanguage ) {
			if ( ! array_key_exists( 'code', $translationLanguage ) ||
			     ! is_string( $translationLanguage['code'] ) ||
			     empty( $translationLanguage['code'] ) ) {
				$result = false;
				break;
			}
		}

		return $result;
	}


	/**
	 * @return bool
	 */
	private function validateContentStats() {
		$result = true;

		foreach ( $this->body['data']['contentStats'] as $contentStat ) {
			if ( ! array_key_exists( 'postsCount', $contentStat ) ||
			     ! is_int( $contentStat['postsCount'] ) ||
			     ! array_key_exists( 'charactersCount', $contentStat ) ||
			     ! is_int( $contentStat['charactersCount'] ) ||
			     ! array_key_exists( 'translationCoverage', $contentStat ) ||
			     ! is_array( $contentStat['translationCoverage'] ) ) {
				$result = false;
				break;
			}

			foreach ( $contentStat['translationCoverage'] as $translationCoverage ) {
				if ( ! is_float( $translationCoverage ) && ! is_int( $translationCoverage ) ) {
					$result = false;
					break;
				}
			}
		}

		return $result;
	}
}
