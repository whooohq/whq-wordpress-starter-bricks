<?php

namespace OTGS\Installer\CDTClient\Api;

class Response {

	/** @var bool */
	private $success;

	/** @var string */
	private $message;


	/**
	 * @param bool $success
	 * @param string $message
	 */
	public function __construct(  $success, $message ) {
		$this->success = $success;
		$this->message = $message;
	}

	public function isSuccessful() {
		return $this->success;
	}

	public function message() {
		return $this->message;
	}
}
