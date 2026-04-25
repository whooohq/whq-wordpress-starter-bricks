<?php
/*
 +=====================================================================+
 |    ____          _        ____             __ _ _                   |
 |   / ___|___   __| | ___  |  _ \ _ __ ___  / _(_) | ___ _ __         |
 |  | |   / _ \ / _` |/ _ \ | |_) | '__/ _ \| |_| | |/ _ \ '__|        |
 |  | |__| (_) | (_| |  __/ |  __/| | | (_) |  _| | |  __/ |           |
 |   \____\___/ \__,_|\___| |_|   |_|  \___/|_| |_|_|\___|_|           |
 |                                                                     |
 |  (c) Jerome Bruandet ~ https://nintechnet.com/codeprofiler/         |
 +=====================================================================+
*/

if (! defined('ABSPATH') ) {
	die('Forbidden');
}

// ===================================================================== 2023-11-17
// Start the profiler.

add_action('wp_ajax_codeprofiler_start_profiler', 'codeprofiler_start_profiler');

function codeprofiler_start_profiler() {

	$response = ['status' => 'error'];

	code_profiler_hide_errors();

	$cp_options = get_option('code-profiler');
	if (! empty( $cp_options['mem'] ) ) {
		$mem = $cp_options['mem'];
	} else {
		$mem = [];
	}

	code_profiler_log_debug(
		esc_html__('Entering AJAX endpoint (profiler initialization)', 'code-profiler')
	);

	// If this is an AJAX call, make sure it comes from an admin/superadmin.
	if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'codeprofiler_start_profiler') {
		// Admin/Superadmin only
		if (! is_super_admin() ) {
			$msg = esc_html__('You are not allowed to performed this action', 'code-profiler');
			$response['message'] = $msg;
			code_profiler_log_error( $msg );
			code_profiler_wp_send_json( $response );
		}
	}

	code_profiler_log_debug(
		esc_html__('Verifying security nonce', 'code-profiler')
	);

	// Verify the security nonce
	if ( empty( $_POST['cp_nonce'] ) ||
		! wp_verify_nonce( $_POST['cp_nonce'], 'start_profiler_nonce') ) {

		$msg = esc_html__(
			'Missing or wrong security nonce. Reload the page and try again',
			'code-profiler'
		);
		$response['message'] = $msg;
		code_profiler_log_error( $msg );
		code_profiler_wp_send_json( $response );
	}

	code_profiler_log_debug(
		esc_html__('Checking MU plugin availability', 'code-profiler')
	);

	// Verify the MU plugin is loaded
	if (! defined('CODE_PROFILER_MU_ON') ) {
		$msg = esc_html__('The MU plugin is not loaded, please check the log', 'code-profiler');
		$response['message'] = $msg;
		code_profiler_log_error( $msg );
		code_profiler_wp_send_json( $response );
	}

	code_profiler_log_debug(
		esc_html__('Retrieving parameters #1', 'code-profiler')
	);

	// Frontend or backend
	if ( empty( $_POST['x_end'] ) ||
		! in_array( $_POST['x_end'], ['frontend', 'backend', 'custom', 'wpcron'] ) ) {

		$msg = sprintf(
			esc_html__('Missing or incorrect parameter (%s)', 'code-profiler'), 'x_end'
		);
		$response['message'] = $msg;
		code_profiler_log_error( $msg );
		code_profiler_wp_send_json( $response );
	}
	$mem['x_end'] = $_POST['x_end'];

	code_profiler_log_debug(
		esc_html__('Retrieving parameters #2', 'code-profiler')
	);

	if ( empty( $_POST['post'] ) ) {
		$msg = sprintf( esc_html__('Missing or incorrect parameter (%s)', 'code-profiler'), 'post');
		$response['message'] = $msg;
		code_profiler_log_error( $msg );
		code_profiler_wp_send_json( $response );
	}
	$mem['post'] = $_POST['post'];

	// Make sure we have no more that 4 decimals, because when returning
	// it via AJAX, it will display more decimals than that
	$microtime = number_format( microtime( true ), 4, '.', '');

	code_profiler_log_debug(
		esc_html__('Retrieving parameters #3', 'code-profiler')
	);

	// Authentication
	if ( empty( $_POST['x_auth'] ) || $_POST['x_auth'] != 'authenticated' ) {
		$_POST['x_auth'] = 'unauthenticated';
	}
	$mem['x_auth'] = $_POST['x_auth'];

	code_profiler_log_debug(
		esc_html__('Retrieving parameters #4', 'code-profiler')
	);

	if ( empty( $_POST['profile'] ) || strlen( $_POST['profile'] ) > 100 ) {
		$profile = code_profiler_profile_name();
	} else {
		$profile = sanitize_file_name( $_POST['profile'] );
	}

	/**
	 * WP cron events use POST with an empty payload.
	 */
	if ( $_POST['x_end'] == 'wpcron') {

		$doing_wp_cron = sprintf( '%.22F', microtime( true ) );
		set_transient('doing_cron', $doing_wp_cron );

		code_profiler_log_debug(
			esc_html__('Cron event detected: setting method to POST with an empty payload.', 'code-profiler')
		);
		$_POST['method']			= 'post';
		$_POST['content_type']	= 1;
		$_POST['payload']			= '';
		$_POST['post']				= esc_url(
			plugins_url('wp-cron.php?doing_wp_cron=' .
			$doing_wp_cron,
			dirname( __FILE__ ) )
		) ."&wpcron={$_POST['post']}" ;
	}

	// URI to profile
	$url		= esc_url_raw( $_POST['post'] );
	$siteurl	= esc_html( site_url() );
	code_profiler_log_info( sprintf(
		/* Translators: version, site url, profile name, profile url */
		esc_html__('Initializing Code Profiler v%s on %s. Profile:  %s - %s', 'code-profiler'),
		CODE_PROFILER_VERSION,
		$siteurl,
		$profile,
		$url
	) );

	code_profiler_log_debug(
		esc_html__('Retrieving parameters #5', 'code-profiler')
	);

	// User-agent
	if ( empty( $_POST['user_agent'] ) ) {
		$user_agent = 'Firefox';
	} else {
		$user_agent = sanitize_text_field( $_POST['user_agent'] );
	}
	foreach( CODE_PROFILER_UA as $types => $types_array ) {
		foreach( $types_array as $name => $value ) {
			if ( $user_agent == $name ) {
				$ua_signature = $value;
				break;
			}
		}
	}
	if ( empty( $ua_signature ) ) {
		$ua_signature = CODE_PROFILER_UA['Desktop']['Firefox'];
	}
	$mem['user_agent'] = $user_agent;

	// Theme
	$themes = code_profiler_get_themes();
	if ( empty( $_POST['theme'] ) || empty( $themes[ $_POST['theme'] ] ) ) {
		$theme = '';
		unset( $mem['theme'] );
	} else {
		code_profiler_log_debug(
			esc_html__('Retrieving parameters #6', 'code-profiler')
		);
		$theme = $_POST['theme'];
		$mem['theme'] = $theme;
		// Append the template to the stylesheet
		if (! empty( $themes[ $theme ]['t'] ) ) {
			$theme .= "::{$themes[ $theme ]['t']}";
		} else {
			$theme .= "::$theme";
		}
	}

	code_profiler_log_debug(
		esc_html__('Creating security key', 'code-profiler')
	);

	// Create security key
	$profiler_key			= bin2hex( random_bytes( 16 ) );
	touch( CODE_PROFILER_UPLOAD_DIR .'/key_'. sha1( $profiler_key ) .'.tmp');

	code_profiler_log_debug(
		esc_html__('Building HTTP query', 'code-profiler')
	);

	/**
	 * Build the query.
	 */
	$raw_url = $url;
	$url = add_query_arg( [
		'CODE_PROFILER_ON'	=> $microtime,
		'profiler_key'			=> $profiler_key
	], $url );

	global $wp_version;
	$headers = [
		'Cache-Control' 	=> 'no-cache, no-store, must-revalidate',
		'Pragma' 			=> 'no-cache',
		'Expires' 			=> '0',
		'httpversion'   	=> '1.1',
		// Devs must be allowed to use it on localhost over TLS too
		'sslverify'     	=> apply_filters('https_local_ssl_verify', false ),
		'timeout'       	=> 300,	// 300-second timeout instead of the default 5s
		'redirection'		=> 0,		// We don't want to be redirected
		'headers'       	=> [
			// Lowercase header name
			'code-profiler-key'	=> $profiler_key,
			'accept-language'		=> 'en-US,en;q=0.5',
			'user-agent'			=> $ua_signature,
			'theme'					=> $theme
		 ]
	];

	// Custom HTTP headers
	if (! empty( $_POST['custom_headers'] ) ) {
		$custom_headers = explode( PHP_EOL, trim( stripslashes( $_POST['custom_headers'] ) ) );
		if (! empty( $custom_headers[0] ) ) {
			code_profiler_log_debug(
				esc_html__('Building custom HTTP headers', 'code-profiler')
			);
			$is_custom_headers = '';
			foreach( $custom_headers as $custom_header ) {
				if ( strpos( $custom_header, ':') === false ) {
					continue;
				}
				list( $key, $value ) = explode(':', $custom_header, 2 );
				// Lowercase header name
				$key		= trim( strtolower( $key ) );
				$value	= trim( $value );
				// We want printable ASCII characters only
				$value	= code_profiler_ASCII_filter( $value );
				if (! empty( $key ) && ! empty( $value ) ) {
					$headers['headers'][ $key ] = $value;
					$is_custom_headers .= "$key: $value\n";
				}
			}
		}
	}
	if (! empty( $is_custom_headers ) ) {
		$mem['custom_headers']	= json_encode( $is_custom_headers );
	} else {
		unset( $mem['custom_headers'] );
	}

	code_profiler_log_debug(
		esc_html__('Checking HTTP options', 'code-profiler')
	);

	// Forward basic authentication if any (not available from WP CLI)
	if ( function_exists('apache_request_headers') ) {
		$apache_headers = apache_request_headers();
		if ( isset( $apache_headers['Authorization'] ) ) {
			$headers['headers']['Authorization'] = $apache_headers['Authorization'];
		}
	// WP-CLI ($ wp code-profiler run --u=FOO --p=BAR)
	} elseif ( defined('WP_CLI') && ! empty( $_POST['Authorization'] ) ) {
		$headers['headers']['Authorization'] = $_POST['Authorization'];
	}

	if ( $_POST['x_auth'] == 'authenticated') {

		code_profiler_log_debug(
			esc_html__('Creating authentication cookies', 'code-profiler')
		);

		// Used for authentication
		if ( is_ssl() ) {
			$cookie_auth = SECURE_AUTH_COOKIE;
			$scheme = 'secure_auth';
		} else {
			$cookie_auth = AUTH_COOKIE;
			$scheme = 'auth';
		}

		// Retrieve the user name (since 1.4.3)
		if (! defined('WP_CLI') ) {
			if ( empty( $_POST['username'] ) ) {
				$msg = esc_html__('Missing authenticated username', 'code-profiler');
				$response['message'] = $msg;
				code_profiler_log_error( $msg );
				code_profiler_wp_send_json( $response );
			}
			$username		= sanitize_user( $_POST['username'] );
			$user_object	= get_user_by('login', $username );
			if ( $user_object === false ) {
				$msg = sprintf( esc_html__('User [%s] does not exist.', 'code-profiler'), $username);
				$response['message'] = $msg;
				code_profiler_log_error( $msg );
				code_profiler_wp_send_json( $response );
			}
			$mem['username'] = strtolower( $username );
			$headers['cookies'][ $cookie_auth ] = wp_generate_auth_cookie(
				$user_object->ID,
				time() + 180,
				$scheme
			);
			if ( empty( $headers['cookies'][ $cookie_auth ] ) ) {
				$msg = esc_html__('Unable to create the authentication cookie', 'code-profiler');
				code_profiler_log_error( $msg );
				$response['message'] = $msg;
				code_profiler_wp_send_json( $response );
			}
			$headers['cookies'][ LOGGED_IN_COOKIE ] = wp_generate_auth_cookie(
				$user_object->ID,
				time() + 180,
				'logged_in'
			);
			if ( empty( $headers['cookies'][ LOGGED_IN_COOKIE ] ) ) {
				$msg = esc_html__('Unable to create the "logged_in" cookie', 'code-profiler');
				code_profiler_log_error( $msg );
				$response['message'] = $msg;
				code_profiler_wp_send_json( $response );
			}
		// WP CLI
		} else {
			$id = get_current_user_id();
			$headers['cookies'][ $cookie_auth ] = wp_generate_auth_cookie(
				$id,
				time() + 180,
				$scheme
			);
			if ( empty( $headers['cookies'][ $cookie_auth ] ) ) {
				$msg = esc_html__('Unable to create the authentication cookie', 'code-profiler');
				code_profiler_log_error( $msg );
				$response['message'] = $msg;
				code_profiler_wp_send_json( $response );
			}
			$headers['cookies'][ LOGGED_IN_COOKIE ] = wp_generate_auth_cookie(
				$id,
				time() + 180,
				'logged_in'
			);
			if ( empty( $headers['cookies'][ LOGGED_IN_COOKIE ] ) ) {
				$msg = esc_html__('Unable to create the "logged_in" cookie', 'code-profiler');
				code_profiler_log_error( $msg );
				$response['message'] = $msg;
				code_profiler_wp_send_json( $response );
			}
		}
		$session_id = session_id();
		if ( $session_id !== false ) {
			$session_name = session_name();
			$headers['cookies'][ $session_name ] = $session_id;
		}
	}

	if ( function_exists('opcache_reset')  ) {
		code_profiler_log_debug(
			esc_html__('Clearing opcode cache', 'code-profiler')
		);
		opcache_reset();
	}

	// GET or POST method
	if (! empty( $_POST['method'] ) && $_POST['method'] == 'post') {
		$safe_method = 'wp_safe_remote_post';
		$mem['method'] = 'post';

		// Content-type
		$content_type = [
			1 => 'application/x-www-form-urlencoded', // Formatted
			3 => 'application/x-www-form-urlencoded', // Raw
			2 => 'application/json'
		];
		if ( empty( $_POST['content_type'] ) ||
			! in_array( $_POST['content_type'], [ 1, 2, 3 ] ) ) {

			$mem['content_type'] = 1;
		} else {
			$mem['content_type'] = (int) $_POST['content_type'];
		}
		$headers['headers']['content-type'] = $content_type[ $mem['content_type'] ];

		// Optional POST payload
		if (! empty( $_POST['payload'] ) ) {
			$_payload = trim( stripslashes( $_POST['payload'] ) );

			code_profiler_log_debug(
				esc_html__('Building POST payload', 'code-profiler')
			);
			/**
			 * application/x-www-form-urlencoded (formatted)
			 */
			if ( $mem['content_type'] == 1 ) {
				$payload_array = explode( PHP_EOL, $_payload );
				foreach( $payload_array as $item ) {
					$payload = explode('=', trim( $item ), 2 );
					if ( isset( $payload[1] ) ) {
						$payload[0] = trim( $payload[0] );
						$payload[1] = trim( $payload[1] );
						$headers['body'][ $payload[0] ] = $payload[1];
					}
				}
			/**
			 * application/x-www-form-urlencoded (raw)
			 */
			} elseif ( $mem['content_type'] == 3 ) {
				parse_str( $_payload , $payload_array );
				foreach( $payload_array as $key => $item ) {
					$headers['body'][ $key ] = $item;
				}
			/**
			 * application/json
			 */
			} else {
				$headers['body'] = $_payload;
			}
			$mem['payload'] = json_encode( $_payload );

		} else {
			// POST request without a payload
			unset( $mem['payload'] );
		}

	} else {
		$safe_method = 'wp_safe_remote_get';
		$mem['method'] = 'get';
	}

	// Optional user-defined cookies
	if (! empty( $_POST['cookies'] ) ) {

		code_profiler_log_debug(
			esc_html__('Building HTTP Cookies', 'code-profiler')
		);

		$cookies_array = explode( PHP_EOL, trim( stripslashes( $_POST['cookies'] ) ) );
		foreach( $cookies_array as $item ) {
			$cookie = explode('=', trim( $item ), 2 );
			if ( isset( $cookie[1] ) ) {
				$cookie[0] = trim( $cookie[0] );
				$cookie[1] = trim( $cookie[1] );
				$headers['cookies'][ $cookie[0] ] = $cookie[1];
			}
		}
		$mem['cookies'] = json_encode( $_POST['cookies'] );
	} else {
		unset( $mem['cookies'] );
	}

	/**
	 * Optional file and folder exclusions.
	 */
	$tmp_exclusions = [];
	if (! empty( $_POST['exclusions'] ) ) {
		$tmp_array = explode( PHP_EOL, trim( stripslashes( $_POST['exclusions'] ) ) );
		foreach( $tmp_array as $item ) {
			$item = trim( code_profiler_ASCII_filter( $item ) );
			if ( $item ) {
				$tmp_exclusions[] = $item;
			}
		}
	}
	/**
	 * Remove duplicates.
	 */
	$exclusions = array_unique( $tmp_exclusions );

	if ( $exclusions) {
		$mem['exclusions'] = json_encode( $exclusions );
	} else {
		unset( $mem['exclusions'] );
	}

	$cp_options['mem'] = $mem;
	update_option('code-profiler', $cp_options );

	/**
	 * Save the profile configuration into a temporary file (used by the re-run feature).
	 */
	file_put_contents(
		CODE_PROFILER_UPLOAD_DIR ."/$microtime.". CODE_PROFILER_TMP_RERUN_LOG,
		json_encode( $mem )
	);

	code_profiler_log_debug(
		esc_html__('Sending HTTP request', 'code-profiler')
	);

	// We must allow developers to run the profiler
	// on a local IP (e.g, http://127.0.0.1/)
	add_filter('http_request_host_is_external', '__return_true');
	$res = $safe_method( $url, $headers );

	// Connection error
	if ( is_wp_error( $res ) ) {
		$msg = esc_html__('Cannot connect to the requested page: %s', 'code-profiler');
		$response['message'] = sprintf(
			$msg,
			esc_html( $res->get_error_message() )
		);
		code_profiler_log_error( sprintf( $msg, $res->get_error_message() ) );
		code_profiler_wp_send_json( $response );
	}

	code_profiler_log_debug(
		esc_html__('Fetching HTTP response', 'code-profiler')
	);

	/**
	 * Always log last HTTP response headers and body,
	 * except sensitive data (cookies & PHP session ID).
	 */
	if ( isset( $res['headers'] ) && isset( $res['body'] ) ) {

		require __DIR__.'/class-logs.php';
		/**
		 * Parse headers.
		 */
		$response_headers = "HTTP {$res['response']['code']} {$res['response']['message']}\n";

		foreach( $res['headers'] as $key => $value ) {
			/**
			 * Remove cookies.
			 */
			if ( $key == 'set-cookie') {
				$response_headers .= ucfirst( $key ) .': *** '. __('Removed', 'code-profiler') ." ***\n";
			} else {
				/**
				 * HTTP headers can contain arrays.
				 */
				if ( is_array( $value ) ) {
					foreach( $value as $k => $v ) {
						$response_headers .= ucfirst( $key ) .": $v\n";
					}
				} else {
					$response_headers .= ucfirst( $key ) .": $value\n";
				}
			}
		}
		/**
		 * Save to the log.
		 */
		if ( empty( $headers['body'] ) ) {
			$headers['body'] = 'x';
		}
		if ( empty( $res['body'] ) ) {
			$res['body'] = '<'. __('empty', 'code-profiler') . '>';
		}
		CodeProfiler_Logs::save_HTTP_log(
			$raw_url,
			$headers['body'],
			$response_headers,
			$res['body']
		);
	}

	// HTTP status code
	if (! empty( $cp_options['http_response'] ) ) {
		if ( preg_match( "/{$cp_options['http_response']}/", $res['response']['code'] ) ) {

			$msg = '';

			$log = esc_html__(
				/* Translators: HTTP response code and message */
				'The website returned the following HTTP status code: %s %s.', 'code-profiler'
			);

			if ( $res['response']['code'] < 500 ) {
				$log .= ' '. esc_html__('By default, the profiler will always abort and throw an error if the server did not return a 200 HTTP status code. You can change that behaviour in the "Settings" section if the page you are profiling needs to return a different code (3xx, 4xx or 5xx).', 'code-profiler');
			}

			$msg .= $log .' '. esc_html__('You may find more details about this error in your PHP error log and/or in the "Logs" section.', 'code-profiler');

			$response['message'] = sprintf(
				$msg,
				(int) $res['response']['code'],
				$res['response']['message']
			);
			code_profiler_log_error(
				sprintf( $log, $res['response']['code'], $res['response']['message'] )
			);

			// If it is a 301/302 redirection, we write the new URL to the log
			if ( in_array( $res['response']['code'], [301, 302] ) &&
				isset( $res['headers']['location'] ) ) {

				code_profiler_log_error(
					sprintf(
						/* Translators: URL */
						esc_html__('The URL redirects to: %s', 'code-profiler'),
						$res['headers']['location']
					)
				);
			}
			code_profiler_wp_send_json( $response );
		}
	}

	code_profiler_log_debug(
		esc_html__('Decoding body', 'code-profiler')
	);

	// Check response
	$message = json_decode( $res['body'], true );
	if ( isset( $message['status'] ) && isset( $message['message'] ) ) {
		$response['status']	= $message['status'];
		$response['message']	= $message['message'];
		code_profiler_wp_send_json( $response );
	}
	code_profiler_log_info(
		esc_html__('Collecting data to analyze', 'code-profiler')
	);
	// Return success
	$response					= ['status' => 'success'];
	$response['message']		= 'success';
	$response['microtime']	= $microtime;

	code_profiler_log_debug(
		esc_html__('Leaving AJAX endpoint', 'code-profiler')
	);

	// AJAX action?
	if ( defined('DOING_AJAX') && DOING_AJAX ) {
		code_profiler_wp_send_json( $response );
	}

	return json_encode( $response );

}

// ===================================================================== 2023-11-17

add_action('wp_ajax_codeprofiler_prepare_report', 'codeprofiler_prepare_report');

function codeprofiler_prepare_report() {

	$response = ['status' => 'error'];

	// If this is an AJAX call, make sure it comes from an admin/superadmin.
	if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'codeprofiler_prepare_report') {
		// Admin/Superadmin only
		if (! is_super_admin() ) {
			$msg = esc_html__('You are not allowed to performed this action', 'code-profiler');
			$response['message'] = $msg;
			code_profiler_log_error( $msg );
			code_profiler_wp_send_json( $response );
		}
	}

	code_profiler_log_debug(
		esc_html__('Entering AJAX endpoint (report preparation)', 'code-profiler')
	);

	code_profiler_hide_errors();

	code_profiler_log_debug(
		esc_html__('Verifying security nonce', 'code-profiler')
	);

	// Verify the security nonce
	if ( empty( $_POST['cp_nonce'] ) ||
		! wp_verify_nonce( $_POST['cp_nonce'], 'start_profiler_nonce') ) {

		$msg = esc_html__('Missing or wrong security nonce. Reload the page and try again',
			'code-profiler');
		$response['message'] = $msg;
		code_profiler_log_error( $msg );
		code_profiler_wp_send_json( $response );
	}

	code_profiler_log_debug(
		esc_html__('Retrieving profile ID', 'code-profiler')
	);

	if ( empty( $_POST['microtime'] ) || ! preg_match('/^\d{10}\.\d+$/', $_POST['microtime'] ) ) {
		$msg = esc_html__('Missing parameter (microtime).', 'code-profiler');
		$response['message'] = $msg;
		code_profiler_log_error( $msg );
		code_profiler_wp_send_json( $response );
	}
	$microtime = sanitize_text_field( $_POST['microtime'] );

	code_profiler_log_debug(
		esc_html__('Retrieving profile name', 'code-profiler')
	);

	$profile = sanitize_file_name( $_POST['profile'] );
	if ( empty( $profile ) ) {
		$msg = esc_html__('Missing profile name.', 'code-profiler');
		$response['message'] = $msg;
		code_profiler_log_error( $msg );
		code_profiler_wp_send_json( $response );
	}

	code_profiler_log_info(
		esc_html__('Preparing the report', 'code-profiler')
	);
	require 'class-report.php';
	$report = new CodeProfiler_Report( $profile, $microtime );
	$report->prepare_report();

	// Take a 1s break so that we can spot any potential error
	// in the backend before AJAX refresh the page
	usleep( 1000000 );

	code_profiler_log_info(
		esc_html__('All done, exiting profiler', 'code-profiler')
	);
	$response['cp_profile']  = $microtime;
	$response['status']  = 'success';
	$response['message'] = 'success';

	code_profiler_log_debug(
		esc_html__('Leaving AJAX endpoint', 'code-profiler')
	);

	// AJAX action?
	if ( defined('DOING_AJAX') && DOING_AJAX ) {
		code_profiler_wp_send_json( $response );
	}

	return json_encode( $response );

}

// ===================================================================== 2023-11-17
// Rename a profile.

add_action('wp_ajax_codeprofiler_rename', 'codeprofiler_rename');

function codeprofiler_rename() {

	$response = ['status' => 'error'];

	code_profiler_hide_errors();

	// Admin/Superadmin only
	if (! is_super_admin() ) {
		$response['message'] = esc_html__(
			'You are not allowed to performed this action.', 'code-profiler'
		);
		wp_send_json( $response );
	}

	// Verify the security nonce
	if ( empty( $_POST['cp_nonce'] ) || ! wp_verify_nonce( $_POST['cp_nonce'], 'rename-profile') ) {
		$response['message'] = esc_html__(
			'Missing or wrong security nonce. Reload the page and try again.', 'code-profiler'
		);
		wp_send_json( $response );
	}

	if ( empty( $_POST['new_name'] ) ) {
		$response['message'] = esc_html__('Please enter a name for this profile.', 'code-profiler');
		wp_send_json( $response );
	}
	$new_name = sanitize_file_name( $_POST['new_name'] );
	if ( strlen( $new_name ) > 100 ) {
		$new_name = substr( $new_name, 0, 100 );
	}
	if ( empty( $new_name ) ) {
		$response['message'] = esc_html__('Please enter a name for this profile.', 'code-profiler');
		wp_send_json( $response );
	}

	if ( empty( $_POST['profile'] ) || ! preg_match('/^\d{10}\.\d{4}$/', $_POST['profile'] ) ) {
		$response['message'] = esc_html__('Missing profile identifier.', 'code-profiler');
		wp_send_json( $response );
	}
	$profile = $_POST['profile'];

	$glob = code_profiler_glob( CODE_PROFILER_UPLOAD_DIR, "^$profile", true );

	$res = false;

	if ( is_array( $glob ) ) {
		foreach( $glob as $path ) {
			// preg_quote is needed for Windows servers because ABSPATH will contain backslashes
			if ( preg_match('`^'. preg_quote( CODE_PROFILER_UPLOAD_DIR . DIRECTORY_SEPARATOR ) .
				'(\d{10}\.\d{4})\..+?\.([a-z]+?\.profile)$`', $path, $match ) ) {

				$res = rename( $path, CODE_PROFILER_UPLOAD_DIR . "/{$match[1]}.$new_name.{$match[2]}" );
				if ( $res === false ) {
					$response['message'] = esc_html__(
						'The operation failed.', 'code-profiler'
					);
					wp_send_json( $response );
				}
			}
		}
	}

	if ( $res === false ) {
		$response['message'] = esc_html__(
			'The operation failed.', 'code-profiler'
		);
		wp_send_json( $response );
	}

	/**
	 * Rename the profile in the summary file,
	 * so that it can be used by the re-run feature.
	 */
	if (! empty( $match[1] ) &&
		is_file( CODE_PROFILER_UPLOAD_DIR ."/{$match[1]}.$new_name.summary.profile" ) ) {

		$data = json_decode(
			file_get_contents( CODE_PROFILER_UPLOAD_DIR ."/{$match[1]}.$new_name.summary.profile" ),
			true
		);
		if (! empty( $data['rerun']['profile'] ) ) {
			$data['rerun']['profile'] = $new_name;
			file_put_contents(
				CODE_PROFILER_UPLOAD_DIR ."/{$match[1]}.$new_name.summary.profile", json_encode( $data )
			);
		}
	}

	$response['status']  = 'success';
	$response['newname']	= $new_name;
	wp_send_json( $response );

}

// =====================================================================
// EOF
