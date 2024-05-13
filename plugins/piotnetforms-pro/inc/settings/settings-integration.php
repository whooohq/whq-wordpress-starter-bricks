<div class="piotnetforms-dashboard__title"><?php echo __( 'Integration', 'piotnetforms' ); ?></div>
<div class="piotnetforms-dashboard__item-content">
    <h3><?php esc_html_e( 'Google Sheets Integration', 'piotnetforms' ); ?></h3>
    <div class="piotnetforms-license">
        <form method="post" action="options.php">
            <?php settings_fields( 'piotnetforms-google-sheets-group' ); ?>
            <?php do_settings_sections( 'piotnetforms-google-sheets-group' ); ?>
            <?php
			$redirect      =  get_admin_url( null, 'admin.php?page=piotnetforms&connect_type=google_sheet' );
$client_id     = esc_attr( get_option( 'piotnetforms-google-sheets-client-id' ) );
$client_secret = esc_attr( get_option( 'piotnetforms-google-sheets-client-secret' ) );

// $redirect =  get_admin_url(null,'admin.php?page=piotnetforms'); For PAFE
// if ( empty( $_GET['connect_type']) && ! empty( $_GET['code'] ) ) {  For PAFE
if ( ! empty( $_GET['connect_type'] ) && $_GET['connect_type'] == 'google_sheet' && ! empty( $_GET['code'] ) ) {
	// Authorization
	$code = $_GET['code'];
	// Token
	$url  = 'https://accounts.google.com/o/oauth2/token';
	$curl = curl_init();
	$data = "code=$code&client_id=$client_id&client_secret=$client_secret&redirect_uri=" . urlencode( $redirect ) . '&grant_type=authorization_code';

	curl_setopt_array( $curl, [
		CURLOPT_URL => 'https://accounts.google.com/o/oauth2/token',
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_TIMEOUT => 30,
		CURLOPT_CUSTOMREQUEST => 'POST',
		CURLOPT_POSTFIELDS => $data,
		CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_HTTPHEADER => [
			'Content-Type: application/x-www-form-urlencoded'
		],
	] );

	$response = curl_exec( $curl );
	curl_close( $curl );
	//echo $response;
	$array = json_decode( $response );

	if ( ! empty( $array->access_token ) && ! empty( $array->refresh_token ) && ! empty( $array->expires_in ) ) {
		$piotnetforms_ggsheets_expired_at = time() + $array->expires_in;
		update_option( 'piotnetforms-google-sheets-expires', $array->expires_in );
		update_option( 'piotnetforms-google-sheets-expired-token', $piotnetforms_ggsheets_expired_at );
		update_option( 'piotnetforms-google-sheets-access-token', $array->access_token );
		update_option( 'piotnetforms-google-sheets-refresh-token', $array->refresh_token );
	}
}
?>
            <div style="padding-top: 30px;">
                <b><a href="https://console.developers.google.com/flows/enableapi?apiid=sheets.googleapis.com" target="_blank"><?php esc_html_e( 'Click here to Sign into your Gmail account and access Google Sheets’s application registration', 'piotnetforms' ); ?></a></b>
            </div>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Client ID', 'piotnetforms' ); ?></th>
                    <td><input type="text" name="piotnetforms-google-sheets-client-id" value="<?php echo $client_id; ?>" class="regular-text"/></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Client Secret', 'piotnetforms' ); ?></th>
                    <td class="piotnetforms-settings-page-td">
                        <input type="password" name="piotnetforms-google-sheets-client-secret" value="<?php echo $client_secret; ?>" class="regular-text"/>
                        <label class="piotnetforms-settings-page-show-password-icon" data-settings-page-show-password-icon><i class="fa fa-eye"></i></label>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Authorized redirect URI', 'piotnetforms' ); ?></th>
                    <td><input type="text" readonly="readonly" value="<?php echo $redirect; ?>" class="regular-text"/></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Authorization', 'piotnetforms' ); ?></th>
                    <td>
                        <?php if ( ! empty( $client_id ) && ! empty( $client_secret ) ) : ?>
                            <a class="piotnetforms-toggle-features__button" href="https://accounts.google.com/o/oauth2/auth?redirect_uri=<?php echo urlencode( $redirect ); ?>&client_id=<?php echo $client_id; ?>&response_type=code&scope=https://www.googleapis.com/auth/spreadsheets&approval_prompt=force&access_type=offline">Authorization</a>
                        <?php else : ?>
                            <?php esc_html_e( 'To setup Gmail integration properly you should save Client ID and Client Secret.', 'piotnetforms' ); ?>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
            <?php submit_button( __( 'Save Settings', 'piotnetforms' ) ); ?>
        </form>
    </div>

    <!--Google Calendar-->
    <hr>
    <h3><?php esc_html_e( 'Google Calendar Integration', 'piotnetforms' ); ?></h3>
    <div class="piotnetforms-license">
        <form method="post" action="options.php">
            <?php settings_fields( 'piotnetforms-google-calendar-group' ); ?>
            <?php do_settings_sections( 'piotnetforms-google-calendar-group' ); ?>
            <?php
$redirect      =  get_admin_url( null, 'admin.php?page=piotnetforms&connect_type=google_calendar' );
$gg_cld_client_id     = esc_attr( get_option( 'piotnetforms-google-calendar-client-id' ) );
$gg_cld_client_secret = esc_attr( get_option( 'piotnetforms-google-calendar-client-secret' ) );
$client_api_key = esc_attr( get_option( 'piotnetforms-google-calendar-client-api-key' ) );

if ( ! empty( $_GET['connect_type'] ) && $_GET['connect_type'] == 'google_calendar' && ! empty( $_GET['code'] ) ) {

	// Authorization
	$code = $_GET['code'];

	// Token
	$curl = curl_init();
	$data = "code=$code&client_id=$gg_cld_client_id&client_secret=$gg_cld_client_secret&redirect_uri=" . urlencode( $redirect ) . '&grant_type=authorization_code';

	curl_setopt_array( $curl, [
		CURLOPT_URL => 'https://accounts.google.com/o/oauth2/token',
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_TIMEOUT => 30,
		CURLOPT_CUSTOMREQUEST => 'POST',
		CURLOPT_POSTFIELDS => $data,
		CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_HTTPHEADER => [
			'Content-Type: application/x-www-form-urlencoded'
		],
	] );

	$response = curl_exec( $curl );
	curl_close( $curl );
	//echo $response;
	$array = json_decode( $response );

	if ( ! empty( $array->access_token ) && ! empty( $array->refresh_token ) && ! empty( $array->expires_in ) ) {
		$piotnetforms_ggcalendar_expired_at = time() + $array->expires_in;
		update_option( 'piotnetforms-google-calendar-exprires', $array->expires_in );
		update_option( 'piotnetforms-google-calendar-expired-token', $piotnetforms_ggcalendar_expired_at );
		update_option( 'piotnetforms-google-calendar-access-token', $array->access_token );
		update_option( 'piotnetforms-google-calendar-refresh-token', $array->refresh_token );

		function piotnetforms_google_calendar_get_calendar_id( $access_token, $client_api_key ) {
			$curl = curl_init();

			curl_setopt_array( $curl, [
				CURLOPT_URL            => "https://www.googleapis.com/calendar/v3/users/me/calendarList?minAccessRole=writer&key=$client_api_key",
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_TIMEOUT        => 30,
				CURLOPT_CUSTOMREQUEST  => 'GET',
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_HTTPHEADER     => [
					"Authorization: Bearer $access_token",
					'Accept: application/json'
				],
			] );

			$response = curl_exec( $curl );
			curl_close( $curl );

			$response = json_decode( $response );
			//print_r($response);
			$gg_calendar_items = $response->items;
			$gg_calendar_id = null;
			foreach ( $gg_calendar_items as $gg_calendar_item ) {
				$gg_calendar_item_id = $gg_calendar_item->id;
				if ( empty( $gg_calendar_id ) ) {
					$gg_calendar_id = $gg_calendar_item_id;
				}
				if ( !empty( $gg_calendar_item->primary ) && $gg_calendar_item->primary == 1 ) {
					$gg_calendar_id = $gg_calendar_item_id;
					break;
				}
			}
			return $gg_calendar_id;
		}

		$gg_calendar_id = piotnetforms_google_calendar_get_calendar_id( $array->access_token, $client_api_key );
		update_option( 'piotnetforms-google-calendar-id', $gg_calendar_id );
	}
}
?>
            <div style="padding-top: 30px;">
                <b><a href="https://console.developers.google.com/" target="_blank"><?php esc_html_e( 'Click here to Sign into your Gmail account and access Google Calendar’s application registration', 'piotnetforms' ); ?></a></b>
            </div>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Client ID', 'piotnetforms' ); ?></th>
                    <td><input type="text" name="piotnetforms-google-calendar-client-id" value="<?php echo $gg_cld_client_id; ?>" class="regular-text"/></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Client Secret', 'piotnetforms' ); ?></th>
                    <td class="piotnetforms-settings-page-td">
                        <input type="password" name="piotnetforms-google-calendar-client-secret" value="<?php echo $gg_cld_client_secret; ?>" class="regular-text"/>
                        <label class="piotnetforms-settings-page-show-password-icon" data-settings-page-show-password-icon><i class="fa fa-eye"></i></label>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'API Key', 'piotnetforms' ); ?></th>
                    <td class="piotnetforms-settings-page-td">
                        <input type="password" name="piotnetforms-google-calendar-client-api-key" value="<?php echo $client_api_key; ?>" class="regular-text"/>
                        <label class="piotnetforms-settings-page-show-password-icon" data-settings-page-show-password-icon><i class="fa fa-eye"></i></label>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Authorized redirect URI', 'piotnetforms' ); ?></th>
                    <td><input type="text" readonly="readonly" value="<?php echo $redirect; ?>" class="regular-text"/></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Authorization', 'piotnetforms' ); ?></th>
                    <td>
                        <?php if ( ! empty( $gg_cld_client_id ) && ! empty( $gg_cld_client_secret ) ) : ?>
                            <a class="piotnetforms-toggle-features__button" href="https://accounts.google.com/o/oauth2/auth?redirect_uri=<?php echo urlencode( $redirect ); ?>&client_id=<?php echo $gg_cld_client_id; ?>&response_type=code&scope=https://www.googleapis.com/auth/calendar.readonly https://www.googleapis.com/auth/calendar.events&approval_prompt=force&access_type=offline">Authorization</a>
                        <?php else : ?>
                            <?php esc_html_e( 'To setup Gmail integration properly you should save Client ID and Client Secret.', 'piotnetforms' ); ?>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
            <?php submit_button( __( 'Save Settings', 'piotnetforms' ) ); ?>
        </form>
    </div>

    <hr>
    <h3><?php esc_html_e( 'Google Maps Integration', 'piotnetforms' ); ?></h3>
    <div class="piotnetforms-license">
        <form method="post" action="options.php">
            <?php settings_fields( 'piotnetforms-google-maps-group' ); ?>
            <?php do_settings_sections( 'piotnetforms-google-maps-group' ); ?>
            <?php
$google_maps_api_key = esc_attr( get_option( 'piotnetforms-google-maps-api-key' ) );
?>
            <div style="padding-top: 30px;">
                <b><a href="https://cloud.google.com/maps-platform/?apis=maps,places" target="_blank"><?php esc_html_e( 'Click here to get Google Maps API Key', 'piotnetforms' ); ?></a></b>
            </div>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Google Maps API Key', 'piotnetforms' ); ?></th>
                    <td class="piotnetforms-settings-page-td">
                        <input type="password" name="piotnetforms-google-maps-api-key" value="<?php echo $google_maps_api_key; ?>" class="regular-text"/>
                        <label class="piotnetforms-settings-page-show-password-icon" data-settings-page-show-password-icon><i class="fa fa-eye"></i></label>
                    </td>
                </tr>
            </table>
            <?php submit_button( __( 'Save Settings', 'piotnetforms' ) ); ?>
        </form>
    </div>

    <br>

    <hr>
    <h3><?php esc_html_e( 'Stripe Integration', 'piotnetforms' ); ?></h3>
    <div class="piotnetforms-license">
        <form method="post" action="options.php">
            <?php settings_fields( 'piotnetforms-stripe-group' ); ?>
            <?php do_settings_sections( 'piotnetforms-stripe-group' ); ?>
            <?php
$publishable_key = esc_attr( get_option( 'piotnetforms-stripe-publishable-key' ) );
$secret_key      = esc_attr( get_option( 'piotnetforms-stripe-secret-key' ) );
?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Publishable Key', 'piotnetforms' ); ?></th>
                    <td><input type="text" name="piotnetforms-stripe-publishable-key" value="<?php echo $publishable_key; ?>" class="regular-text"/></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Secret Key', 'piotnetforms' ); ?></th>
                    <td class="piotnetforms-settings-page-td">
                        <input type="password" name="piotnetforms-stripe-secret-key" value="<?php echo $secret_key; ?>" class="regular-text"/>
                        <label class="piotnetforms-settings-page-show-password-icon" data-settings-page-show-password-icon><i class="fa fa-eye"></i></label>
                    </td>
                </tr>
            </table>
            <?php submit_button( __( 'Save Settings', 'piotnetforms' ) ); ?>
        </form>
    </div>

    <hr>
    <h3><?php _e( 'Paypal Integration', 'piotnetforms' ); ?></h3>
    <div class="piotnetforms-license">
        <form method="post" action="options.php">
            <?php settings_fields( 'piotnetforms-paypal-group' ); ?>
            <?php do_settings_sections( 'piotnetforms-paypal-group' ); ?>
            <?php
$client_id = esc_attr( get_option( 'piotnetforms-paypal-client-id' ) );
$secret_id = esc_attr( get_option( 'piotnetforms-paypal-secret-id' ) );
?>
            <table class="form-table">
                <div style="padding-top: 30px;">
                    <b><a href="https://developer.paypal.com/developer/applications/" target="_blank"><?php _e( 'Click here to Create app and get the Client ID', 'piotnetforms' ); ?></a></b>
                </div>
                <tr valign="top">
                    <th scope="row"><?php _e( 'Client ID', 'piotnetforms' ); ?></th>
                    <td><input type="text" name="piotnetforms-paypal-client-id" value="<?php echo $client_id; ?>" class="regular-text"/></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e( 'Client Secret', 'piotnetforms' ); ?></th>
                    <td class="piotnetforms-settings-page-td">
                        <input type="password" name="piotnetforms-paypal-secret-id" value="<?php echo $secret_id; ?>" class="regular-text"/>
                        <label class="piotnetforms-settings-page-show-password-icon" data-settings-page-show-password-icon><i class="fa fa-eye"></i></label>
                    </td>
                </tr>
            </table>
            <?php submit_button( __( 'Save Settings', 'piotnetforms' ) ); ?>
        </form>
    </div>

    <hr>
    <h3><?php _e( 'Mollie Payment', 'piotnetforms' ); ?></h3>
    <div class="piotnetforms-license">
        <form method="post" action="options.php">
            <?php settings_fields( 'piotnetforms-mollie-group' ); ?>
            <?php do_settings_sections( 'piotnetforms-mollie-group' ); ?>
            <?php
$api_key = esc_attr( get_option( 'piotnetforms-mollie-api-key' ) );
?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php _e( 'API Key', 'piotnetforms' ); ?></th>
                    <td class="piotnetforms-settings-page-td">
                        <input type="password" name="piotnetforms-mollie-api-key" value="<?php echo $api_key; ?>" class="regular-text"/>
                        <label class="piotnetforms-settings-page-show-password-icon" data-settings-page-show-password-icon><i class="fa fa-eye"></i></label>
                    </td>
                </tr>
            </table>
            <?php submit_button( __( 'Save Settings', 'piotnetforms' ) ); ?>
        </form>
    </div>

    <hr>
    <h3><?php esc_html_e( 'MailChimp Integration', 'piotnetforms' ); ?></h3>
    <div class="piotnetforms-license">
        <form method="post" action="options.php">
            <?php settings_fields( 'piotnetforms-mailchimp-group' ); ?>
            <?php do_settings_sections( 'piotnetforms-mailchimp-group' ); ?>
            <?php
$api_key = esc_attr( get_option( 'piotnetforms-mailchimp-api-key' ) );
?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'API Key', 'piotnetforms' ); ?></th>
                    <td class="piotnetforms-settings-page-td">
                        <input type="password" name="piotnetforms-mailchimp-api-key" value="<?php echo $api_key; ?>" class="regular-text"/>
                        <label class="piotnetforms-settings-page-show-password-icon" data-settings-page-show-password-icon><i class="fa fa-eye"></i></label>
                    </td>
                </tr>
            </table>
            <?php submit_button( __( 'Save Settings', 'piotnetforms' ) ); ?>
        </form>
    </div>

    <hr>
    <h3><?php esc_html_e( 'HubSpot Integration', 'piotnetforms' ); ?></h3>
    <div class="piotnetforms-license">
        <form method="post" action="options.php">
            <?php settings_fields( 'piotnetforms-hubspot-group' ); ?>
            <?php do_settings_sections( 'piotnetforms-hubspot-group' ); ?>
            <?php
                $hubspot_access_token = esc_attr(get_option('piotnetforms-hubspot-access-token'));
            ?>
            <div style="padding-top: 30px;">
                <b><a href="https://app.hubspot.com/login" target="_blank"><?php esc_html_e( 'Click here to Sign into your Hubspot Account and take Access Token', 'piotnetforms' ); ?></a></b>
            </div>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Private App Access Token', 'piotnetforms' ); ?></th>
                    <td class="piotnetforms-settings-page-td">
                        <input type="password" name="piotnetforms-hubspot-access-token" value="<?php echo $hubspot_access_token; ?>" class="regular-text"/>
                        <label class="piotnetforms-settings-page-show-password-icon" data-settings-page-show-password-icon><i class="fa fa-eye"></i></label>
                    </td>
                </tr>
            </table>
            <?php submit_button( __( 'Save Settings', 'piotnetforms' ) ); ?>
        </form>
    </div>

    <hr>
    <h3><?php esc_html_e( 'MailerLite Integration', 'piotnetforms' ); ?></h3>
    <div class="piotnetforms-license">
        <form method="post" action="options.php">
            <?php settings_fields( 'piotnetforms-mailerlite-group' ); ?>
            <?php do_settings_sections( 'piotnetforms-mailerlite-group' ); ?>
            <?php
$api_key = esc_attr( get_option( 'piotnetforms-mailerlite-api-key' ) );
?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'API Key', 'piotnetforms' ); ?></th>
                    <td class="piotnetforms-settings-page-td">
                        <input type="password" name="piotnetforms-mailerlite-api-key" value="<?php echo $api_key; ?>" class="regular-text"/>
                        <label class="piotnetforms-settings-page-show-password-icon" data-settings-page-show-password-icon><i class="fa fa-eye"></i></label>
                    </td>
                </tr>
            </table>
            <?php submit_button( __( 'Save Settings', 'piotnetforms' ) ); ?>
        </form>
    </div>

    <hr>
    <h3><?php _e( 'Sendinblue Integration', 'piotnetforms' ); ?></h3>
    <div class="piotnetforms-license">
        <form method="post" action="options.php">
            <?php settings_fields( 'piotnetforms-addons-for-elementor-pro-sendinblue-group' ); ?>
            <?php do_settings_sections( 'piotnetforms-addons-for-elementor-pro-sendinblue-group' ); ?>
            <?php
$sendinblue_api_key = esc_attr( get_option( 'piotnetforms-addons-for-elementor-pro-sendinblue-api-key' ) );
?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php _e( 'API Key', 'pafe' ); ?></th>
                    <td class="piotnetforms-settings-page-td">
                        <input type="password" name="piotnetforms-addons-for-elementor-pro-sendinblue-api-key" value="<?php echo $sendinblue_api_key; ?>" class="regular-text"/>
                        <label class="piotnetforms-settings-page-show-password-icon" data-settings-page-show-password-icon><i class="fa fa-eye"></i></label>
                    </td>
                </tr>
            </table>
            <?php submit_button( __( 'Save Settings', 'pafe' ) ); ?>
        </form>
    </div>

    <hr>
    <h3><?php esc_html_e( 'ActiveCampaign Integration', 'piotnetforms' ); ?></h3>
    <div class="piotnetforms-license">
        <form method="post" action="options.php">
            <?php settings_fields( 'piotnetforms-activecampaign-group' ); ?>
            <?php do_settings_sections( 'piotnetforms-activecampaign-group' ); ?>
            <?php
$api_key = esc_attr( get_option( 'piotnetforms-activecampaign-api-key' ) );
$api_url = esc_attr( get_option( 'piotnetforms-activecampaign-api-url' ) );
?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'API Key', 'piotnetforms' ); ?></th>
                    <td class="piotnetforms-settings-page-td">
                        <input type="password" name="piotnetforms-activecampaign-api-key" value="<?php echo $api_key; ?>" class="regular-text"/>
                        <label class="piotnetforms-settings-page-show-password-icon" data-settings-page-show-password-icon><i class="fa fa-eye"></i></label>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'API URL', 'piotnetforms' ); ?></th>
                    <td><input type="text" name="piotnetforms-activecampaign-api-url" value="<?php echo $api_url; ?>" class="regular-text"/></td>
                </tr>
            </table>
            <?php submit_button( __( 'Save Settings', 'piotnetforms' ) ); ?>
        </form>
    </div>

    <hr>
    <h3><?php esc_html_e( 'reCAPTCHA (v3) Integration', 'piotnetforms' ); ?></h3>
    <div class="piotnetforms-license">
        <form method="post" action="options.php">
            <?php settings_fields( 'piotnetforms-recaptcha-group' ); ?>
            <?php do_settings_sections( 'piotnetforms-recaptcha-group' ); ?>
            <?php
$site_key   = esc_attr( get_option( 'piotnetforms-recaptcha-site-key' ) );
$secret_key = esc_attr( get_option( 'piotnetforms-recaptcha-secret-key' ) );
?>
            <div style="padding-top: 30px;" data-piotnetforms-dropdown>
                <b><a href="#" data-piotnetforms-dropdown-trigger><?php esc_html_e( 'Click here to view tutorial', 'piotnetforms' ); ?></a></b>
                <div data-piotnetforms-dropdown-content>
                    <p>Very first thing you need to do is register your website on Google reCAPTCHA to do that click <a href="https://www.google.com/recaptcha/admin" target="_blank">here</a>.</p>

                    <p>Login to your Google account and create the app by filling the form. Select the reCAPTCHA v3 and in that select “I am not a robot” checkbox option.</p>
                    <div>
                        <img src="<?php echo plugin_dir_url( __FILE__ ); ?>../forms/google-recaptcha-1.jpg">
                    </div>

                    <p>Once submitted, Google will provide you with the following two information: Site key, Secret key.</p>
                    <div>
                        <img src="<?php echo plugin_dir_url( __FILE__ ); ?>../forms/google-recaptcha-2.jpg">
                    </div>
                </div>
            </div>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Site Key', 'piotnetforms' ); ?></th>
                    <td><input type="text" name="piotnetforms-recaptcha-site-key" value="<?php echo $site_key; ?>" class="regular-text"/></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Secret Key', 'piotnetforms' ); ?></th>
                    <td class="piotnetforms-settings-page-td">
                        <input type="password" name="piotnetforms-recaptcha-secret-key" value="<?php echo $secret_key; ?>" class="regular-text"/>
                        <label class="piotnetforms-settings-page-show-password-icon" data-settings-page-show-password-icon><i class="fa fa-eye"></i></label>
                    </td>
                </tr>
            </table>
            <?php submit_button( __( 'Save Settings', 'piotnetforms' ) ); ?>
        </form>
    </div>

    <hr>
    <h3><?php esc_html_e( 'Getresponse Integration', 'piotnetforms' ); ?></h3>
    <div class="piotnetforms-license">
        <form method="post" action="options.php">
            <?php settings_fields( 'piotnetforms-getresponse-group' ); ?>
            <?php do_settings_sections( 'piotnetforms-getresponse-group' ); ?>
            <?php
$getresponse_api_key   = esc_attr( get_option( 'piotnetforms-getresponse-api-key' ) );
?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'API Key', 'piotnetforms' ); ?></th>
                    <td class="piotnetforms-settings-page-td">
                        <input type="password" name="piotnetforms-getresponse-api-key" value="<?php echo $getresponse_api_key; ?>" class="regular-text"/>
                        <label class="piotnetforms-settings-page-show-password-icon" data-settings-page-show-password-icon><i class="fa fa-eye"></i></label>
                    </td>
                </tr>
            </table>
            <?php submit_button( __( 'Save Settings', 'piotnetforms' ) ); ?>
        </form>
    </div>

    <hr>
    <h3><?php esc_html_e( 'Twilio Integration', 'piotnetforms' ); ?></h3>
    <div class="piotnetforms-license">
        <form method="post" action="options.php">
            <?php settings_fields( 'piotnetforms-twilio-group' ); ?>
            <?php do_settings_sections( 'piotnetforms-twilio-group' ); ?>
            <?php
$account_sid = esc_attr( get_option( 'piotnetforms-twilio-account-sid' ) );
$author_token = esc_attr( get_option( 'piotnetforms-twilio-author-token' ) );
?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Account SID', 'piotnetforms' ); ?></th>
                    <td><input type="text" name="piotnetforms-twilio-account-sid" value="<?php echo $account_sid; ?>" class="regular-text"/></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Author Token', 'piotnetforms' ); ?></th>
                    <td class="piotnetforms-settings-page-td">
                        <input type="password" name="piotnetforms-twilio-author-token" value="<?php echo $author_token; ?>" class="regular-text"/>
                        <label class="piotnetforms-settings-page-show-password-icon" data-settings-page-show-password-icon><i class="fa fa-eye"></i></label>
                    </td>
                </tr>
            </table>
            <?php submit_button( __( 'Save Settings', 'piotnetforms' ) ); ?>
        </form>
    </div>

    <hr>
    <h3><?php esc_html_e( 'SendFox Integration', 'piotnetforms' ); ?></h3>
    <div class="piotnetforms-license">
        <form method="post" action="options.php">
            <?php settings_fields( 'piotnetforms-sendfox-group' ); ?>
            <?php do_settings_sections( 'piotnetforms-sendfox-group' ); ?>
            <?php
$sendfox_access_token   = esc_attr( get_option( 'piotnetforms-sendfox-access-token' ) );
?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'SendFox Personal Aceess Token', 'piotnetforms' ); ?></th>
                    <td class="piotnetforms-settings-page-td">
                        <input type="password" name="piotnetforms-sendfox-access-token" value="<?php echo $sendfox_access_token; ?>" class="regular-text"/>
                        <label class="piotnetforms-settings-page-show-password-icon" data-settings-page-show-password-icon><i class="fa fa-eye"></i></label>
                    </td>
                </tr>
            </table>
            <?php submit_button( __( 'Save Settings', 'piotnetforms' ) ); ?>
        </form>
    </div>

    <hr>
    <h3><?php _e( 'Constant contact', 'piotnetforms' ); ?></h3>
    <div class="piotnetforms-license">
        <?php
		$c_ID = esc_attr( get_option( 'piotnetforms-constant-contact-api-key' ) );
$app_secret = get_option( 'piotnetforms-constant-contact-app-secret-id' );
$redirectURI = admin_url( 'admin.php?page=piotnetforms' );
$baseURL = 'https://authz.constantcontact.com/oauth2/default/v1/authorize';
$authURL = $baseURL . '?client_id=' . $c_ID . '&scope=contact_data+campaign_data+account_update+account_read+offline_access&response_type=code' . '&redirect_uri=' . urlencode( $redirectURI ).'&state=piotnet';
?>
        <form method="post" action="options.php">
            <?php settings_fields( 'piotnetforms-constant-contact-group' ); ?>
            <?php do_settings_sections( 'piotnetforms-constant-contact-group' ); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php _e( 'API Key', 'piotnetforms' ); ?></th>
                    <td class="piotnetforms-settings-page-td">
                        <input type="password" name="piotnetforms-constant-contact-api-key" value="<?php echo $c_ID; ?>" class="regular-text"/>
                        <label class="piotnetforms-settings-page-show-password-icon" data-settings-page-show-password-icon><i class="fa fa-eye"></i></label>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e( 'App Secret', 'piotnetforms' ); ?></th>
                    <td class="piotnetforms-settings-page-td">
                        <input type="password" name="piotnetforms-constant-contact-app-secret-id" value="<?php echo $app_secret; ?>" class="regular-text"/>
                        <label class="piotnetforms-settings-page-show-password-icon" data-settings-page-show-password-icon><i class="fa fa-eye"></i></label>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e( 'Authorization Redirect URI', 'pafe' ); ?></th>
                    <td><input type="text" value="<?php echo $redirectURI; ?>" class="regular-text" readonly/></td>
                </tr>
            </table>
            <div class="piotnet-addons-zoho-admin-api">
                <?php submit_button( __( 'Save Settings', 'piotnetforms' ) ); ?>
                <p class="submit"><a class="button button-primary" href="<?php echo $authURL; ?>" authenticate-zoho-crm>Authenticate Constant Contact</a></p>
            </div>
        </form>
        <?php
if ( !empty( $_GET['code'] ) && $_GET['state'] == 'piotnet' ) {
	$token_data = piotnetforms_constantcontact_get_token( $_GET['code'], $redirectURI, $c_ID, $app_secret );
	if ( !empty( $token_data->access_token ) ) {
		update_option( 'piotnetforms-constant-contact-access-token', $token_data->access_token );
		update_option( 'piotnetforms-constant-contact-refresh-token', $token_data->refresh_token );
		update_option( 'piotnetforms-constant-contact-time-get-token', time() );
		echo '<p>Constant Contact authentication successful.</p>';
	}
}
?>
    </div>

    <hr>
    <h3><?php esc_html_e( 'Convertkit Integration', 'piotnetforms' ); ?></h3>
    <div class="piotnetforms-license">
        <form method="post" action="options.php">
            <?php settings_fields( 'piotnetforms-convertkit-group' ); ?>
            <?php do_settings_sections( 'piotnetforms-convertkit-group' ); ?>
            <?php
	$convertkit_api_key   = esc_attr( get_option( 'piotnetforms-convertkit-api-key' ) );
?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'API Key', 'piotnetforms' ); ?></th>
                    <td class="piotnetforms-settings-page-td">
                        <input type="password" name="piotnetforms-convertkit-api-key" value="<?php echo $convertkit_api_key; ?>" class="regular-text"/>
                        <label class="piotnetforms-settings-page-show-password-icon" data-settings-page-show-password-icon><i class="fa fa-eye"></i></label>
                    </td>
                </tr>
            </table>
            <?php submit_button( __( 'Save Settings', 'piotnetforms' ) ); ?>
        </form>
    </div>

    <hr>
    <h3><?php _e( 'Zoho Integration', 'piotnetforms' ); ?></h3>
    <div class="piotnetforms-license">
        <form method="post" action="options.php">
            <?php settings_fields( 'piotnetforms-zoho-group' ); ?>
            <?php do_settings_sections( 'piotnetforms-zoho-group' ); ?>
            <?php
$zoho_domain = esc_attr( get_option( 'piotnetforms-zoho-domain' ) );
$client_id = esc_attr( get_option( 'piotnetforms-zoho-client-id' ) );
$redirect_url = admin_url( 'admin.php?page=piotnetforms' );
$client_secret = esc_attr( get_option( 'piotnetforms-zoho-client-secret' ) );
$token = esc_attr( get_option( 'piotnetforms-zoho-token' ) );
$refresh_token = esc_attr( get_option( 'piotnetforms-zoho-refresh-token' ) );
$zoho_domains = ['accounts.zoho.com', 'accounts.zoho.com.au', 'accounts.zoho.eu', 'accounts.zoho.in', 'accounts.zoho.com.cn']
?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php _e( 'Domain', 'piotnetforms' ); ?></th>
                    <td>
                        <select name="piotnetforms-zoho-domain">
                            <?php foreach ( $zoho_domains as $zoho ) {
                            	if ( $zoho_domain == $zoho ) {
                            		echo '<option value="'.$zoho.'" selected>'.$zoho.'</option>';
                            	} else {
                            		echo '<option value="'.$zoho.'">'.$zoho.'</option>';
                            	}
                            }
?>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e( 'Client ID', 'piotnetforms' ); ?></th>
                    <td>
                        <input type="text" name="piotnetforms-zoho-client-id" value="<?php echo $client_id; ?>" class="regular-text"/>
                        <a target="_blank" href="https://accounts.zoho.com/developerconsole">How to create client id and Screct key</a>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e( 'Client Secret', 'piotnetforms' ); ?></th>
                    <td class="piotnetforms-settings-page-td">
                        <input type="password" name="piotnetforms-zoho-client-secret" value="<?php echo $client_secret; ?>" class="regular-text"/>
                        <label class="piotnetforms-settings-page-show-password-icon" data-settings-page-show-password-icon><i class="fa fa-eye"></i></label>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e( 'Authorization Redirect URI', 'piotnetforms' ); ?></th>
                    <td><input type="text" name="piotnetforms-zoho-redirect-url" value="<?php echo $redirect_url; ?>" class="regular-text" readonly/></td>
                </tr>
                <tr valign="top">
                    <th scope="row"></th>
                    <td style="font-style: italic;">Note: Save before authentication</td>
                </tr>
            </table>
            <div class="piotnetforms-zoho-admin-api">
                <?php submit_button( __( 'Save Settings', 'piotnetforms' ) ); ?>
                <?php
				$scope_module = 'ZohoCRM.modules.all,ZohoCRM.settings.all';
$oauth = 'https://'.$zoho_domain.'/oauth/v2/auth?scope='.$scope_module.'&client_id='.$client_id.'&response_type=code&access_type=offline&redirect_uri='.$redirect_url.'';
echo '<p class="piotnetforms-zoho-admin-api-authenticate submit"><a class="button button-primary" href="'.$oauth.'" authenticate-zoho-crm>Authenticate Zoho CRM</a></p>';
?>
                <?php if ( !empty( $_REQUEST['code'] ) && !empty( $_REQUEST['accounts-server'] ) ):
                	$url_get_token = 'https://'.$zoho_domain.'/oauth/v2/token?client_id='.$client_id.'&grant_type=authorization_code&client_secret='.$client_secret.'&redirect_uri='.$redirect_url.'&code='.$_REQUEST['code'].'';
                	$zoho_response = wp_remote_post( $url_get_token, [] );
                	if ( !empty( $zoho_response['body'] ) ) {
                		$zoho_response = json_decode( $zoho_response['body'] );
                		if ( empty( $zoho_response->error ) ) {
                			update_option( 'piotnetforms_zoho_access_token', $zoho_response->access_token );
                			update_option( 'piotnetforms_zoho_refresh_token', $zoho_response->refresh_token );
                			update_option( 'piotnetforms_zoho_api_domain', $zoho_response->api_domain );
                			echo 'Success';
                		} else {
                			echo $zoho_response->error;
                		}
                	} else {
                		echo 'Cannot verify zoho account';
                	}
?>
                    <script type="text/javascript">
                        window.history.pushState({}, '','<?php echo admin_url( 'admin.php?page=piotnetforms' ); ?>' );
                    </script>
                <?php endif; ?>
            </div>

        </form>
    </div>
</div>
