<?php

namespace OTGS\Installer\CDTClient\Api;

class Request {


	/**
	 * @param ValidatorInterface $validator
	 * @param string $url
	 * @param array $body
	 *
	 * @return Response
	 */
	public function post( ValidatorInterface $validator, $url, $body ) {

		$validationResult = $validator->validate();

		if ( ! $validationResult ) {
			return new Response( false, 'Invalid request data' );
		}

		$response = wp_remote_post( $url, [
			'headers'     => [
				'Content-Type' => 'application/json',
			],
			'body'        => wp_json_encode( $body ),
			'data_format' => 'body'
		] );

		if ( is_wp_error( $response ) ) {
			return new Response( false, 'fail' );
		}

		$responseBody = json_decode( wp_remote_retrieve_body( $response ), true ) ?: false;

		$isSuccessful = $responseBody &&
		                isset( $responseBody['uuid'] ) &&
		                wp_remote_retrieve_response_code( $response ) === 201;

		return new Response(
			$isSuccessful,
			$responseBody ? $responseBody['message'] : ''
		);
	}
}
