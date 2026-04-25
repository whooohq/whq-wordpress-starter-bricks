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

if (! defined( 'ABSPATH' ) ) {
	die( 'Forbidden' );
}

// =====================================================================

class CodeProfiler_Logs {


	private static $HTTP_log_reg = '^last_request\.\d+?\.\d+?\.php$';

	/**
	 * Retrieve the last HTTP response log, if any.
	 */
	public static function get_HTTP_log() {
		/**
		 * E.g., "last_request.1727412303.5681.php".
		 */
		$file = code_profiler_glob( CODE_PROFILER_UPLOAD_DIR, self::$HTTP_log_reg, true );

		if (! empty( $file[0] ) ) {

			$log = file( $file[0] );
			if (! empty( $log[0] ) && ! empty( $log[1] ) &&
				! empty( $log[2] ) && ! empty( $log[3] )
			) {

				$page    = json_decode( $log[0], true );
				$payload = json_decode( $log[1], true );
				$headers = json_decode( $log[2], true );
				$body    = json_decode( $log[3], true );

				$data = "==================================================\n".
							__('Requested page:', 'code-profiler') ."\n\n".
							"$page\n";

				if ( $payload != 'x') {
					$data .= "==================================================\n".
								__('Post payload:', 'code-profiler') ."\n\n".
								print_r( $payload, true ) ."\n";
				}

				$data .= "==================================================\n".
							__('Response headers:', 'code-profiler') ."\n\n".
							"$headers\n".
							"==================================================\n".
							__('Response body:', 'code-profiler') ."\n\n".
							"$body\n";

				return $data;
			}
		}
		return false;
	}


	/**
	 * Save the last HTTP response log to disk.
	 */
	public static function save_HTTP_log( $page, $payload, $response_headers, $body ) {

		$file = code_profiler_glob( CODE_PROFILER_UPLOAD_DIR, self::$HTTP_log_reg, true );

		if (! empty( $file[0] ) ) {
			$fh = fopen( $file[0], 'w');

		} else {
			$fh = fopen(
				CODE_PROFILER_UPLOAD_DIR .'/last_request.'. microtime( true ) .'.php', 'w'
			);
		}

		fwrite( $fh,
			json_encode( $page ) ."\n". json_encode( $payload ) ."\n".
			json_encode( $response_headers ) ."\n". json_encode( $body )
		);

		fclose( $fh );
	}

}
// =====================================================================
// EOF
