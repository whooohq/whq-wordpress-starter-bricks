<?php

add_action( 'wp_ajax_piotnetforms_mailerlite_get_fields', 'piotnetforms_mailerlite_get_fields' );
add_action( 'wp_ajax_nopriv_piotnetforms_mailerlite_get_fields', 'piotnetforms_mailerlite_get_fields' );

function piotnetforms_mailerlite_get_fields() {
	$api_key = $_REQUEST['apiKey'];
	if ( $api_key == 'false' ) {
		$api_key = get_option( 'piotnetforms-mailerLite-api-key' );
	}
	if ( !empty( $api_key ) ) {
		$curl = curl_init();
		curl_setopt_array( $curl, [
			CURLOPT_URL => 'https://api.mailerlite.com/api/v2/fields',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'GET',
			CURLOPT_HTTPHEADER => [
				'X-MailerLite-ApiKey: '.$api_key.'',
				'Cookie: __cfduid=d93fce62abc3f8948b1b41f9517e951fc1600160151; PHPSESSID=cc262a1d6be5d59f2a8817dbc6ad57ad'
			],
		] );
		$response = curl_exec( $curl );
		$response = json_decode( $response );
		curl_close( $curl );
		$html = '<h4 class="piotnetforms-mailerlite-title">Fields</h4>';
		if ( $response ) {
			foreach ( $response as $value ) {
				$html .= '<div class="piotnetforms-mailerlite-fields-result-item"><label>'.$value->title.'</label><div><input type="text" value="'.$value->key.'" readonly></div></div>';
			}
		}
		echo $html;
	} else {
		echo 'Please enter API key';
	}
	wp_die();
}
