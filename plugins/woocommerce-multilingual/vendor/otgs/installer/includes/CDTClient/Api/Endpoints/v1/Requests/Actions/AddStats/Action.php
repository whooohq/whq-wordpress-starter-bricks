<?php

namespace OTGS\Installer\CDTClient\Api\Endpoints\v1\Requests\Actions\AddStats;

use OTGS\Installer\CDTClient\Api\Api;
use OTGS\Installer\CDTClient\Api\Endpoints\v1\Requests\Endpoint;
use OTGS\Installer\CDTClient\Api\Request;
use OTGS\Installer\UUID\UUID_v5;

class Action {

	const NAME = 'add_stats';

	/** @var Request */
	private $request;

	public function __construct( Request $request ) {
		$this->request = $request;
	}

	public function run( array $data ) {
		$createdAt = time();

		$requestBody = [
			'request_uuid' => UUID_v5::generate( self::NAME . ':' . $createdAt, get_site_url() ),
			'request_type' => self::NAME,
			'created_time' => $createdAt,
			'data'         => $data,
		];

		$validator = new Validator( $requestBody );

		return $this->request->post(
			$validator,
			Api::getUrl() . Endpoint::ROUTE,
			$requestBody
		);
	}
}
