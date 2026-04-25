<?php

namespace OTGS\Installer\CDTClient\Api;

class Api {

	const URL = 'https://cdt.wpml.org';

	public static function getUrl() {
		return defined( 'CDT_QA_API_URL' ) ?
			constant( 'CDT_QA_API_URL' ) :
			self::URL;
	}

}
