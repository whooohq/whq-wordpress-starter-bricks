<?php

add_action( 'wp_ajax_piotnetforms_twilio_sendgrid_get_list', 'piotnetforms_twilio_sendgrid_get_list' );
add_action( 'wp_ajax_nopriv_piotnetforms_twilio_sendgrid_get_list', 'piotnetforms_twilio_sendgrid_get_list' );

function piotnetforms_twilio_sendgrid_get_list() {
	$api_key = $_REQUEST['api'];
	if ( !empty( $api_key ) ) {
		$api_key = 'authorization: Bearer ' . $api_key;
		$curl = curl_init();

		curl_setopt_array( $curl, [
			CURLOPT_URL => 'https://api.sendgrid.com/v3/marketing/lists?page_size=100',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'GET',
			CURLOPT_POSTFIELDS =>'{}',
			CURLOPT_SSL_VERIFYPEER =>false,
			CURLOPT_HTTPHEADER => [
				$api_key,
				'content-type: application/json'
			],
		] );

		$response = curl_exec( $curl );

		curl_close( $curl );

		$response = json_decode( $response );
		$result = $response->result;
        if(!empty($result)){
            foreach ( $result as $value ) {
                $name = $value->name;
                $id = $value->id;
                echo '<div class="piotnetforms-twilio-sendgrid-list__item" style="padding-top:5px;"><label> <strong>'.$name.'</strong> ('.$value->contact_count.')</label><div class="piotnetforms-twilio-sendgrid-list__item-value" style="padding-bottom:3px;"><input type="text" value="'.$id.'" readonly></div></div>';
            }
        }else{
            echo '<div style="margin-top: 5px;">List does not exist. The contacts will be saved to the "All Contacts".</div>';
        }
	} else {
		echo 'Please enter the API key.';
	}
    wp_die();
}
