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

if (! defined('ABSPATH') ) { die('Forbidden'); }

// =====================================================================

class CodeProfiler_CLI extends WP_CLI_Command {

	private $file;
	private $out		= 'view';
	private $cmd_view = 'wp code-profiler view';
	private $cmd_run  = 'wp code-profiler run';

	/**
	 * Run the profiler.
	 *
	 */
	public function run( $args, $assoc_args ) {

		$this->is_enabled();

		$_POST['cp_nonce'] = wp_create_nonce('start_profiler_nonce');

		$_POST['post'] = home_url( '/' );
		// Mark the connection as 'HTTPS' if needed.
		if ( strtolower( parse_url( $_POST['post'], PHP_URL_SCHEME ) ) == 'https') {
			$_SERVER['HTTPS'] = 'on';
		}

		if (! empty( $assoc_args['wpcron'] ) ) {
			$_POST['x_end'] = 'wpcron';
			$_POST['post']  = $assoc_args['wpcron'];

		} else {
			$_POST['x_end'] = 'frontend';
			// Detect if we're authenticated or not
			if ( is_user_logged_in() === true ) {
				$_POST['x_auth']		= 'authenticated';
			} else {
				$_POST['x_auth']		= 'unauthenticated';
			}
		}

		$_POST['profile']		= 'WP-CLI_' . time();
		$_POST['user_agent']	= 'Firefox';

		if (! empty( $assoc_args['dest'] ) ) {
			$message = sprintf(
				__('The "%s" option is only available in the pro version of Code Profiler.', 'code-profiler'),
				'--dest'
			);
			WP_CLI::error( $message );
			exit;
		}

		if (! empty( $assoc_args['out'] ) && in_array( $assoc_args['out'], ['json', 'csv'] ) ) {
			$this->out = $assoc_args['out'];
		}

		// HTTP basic authentication
		if (! empty( $assoc_args['u'] ) ) {
			_e('Enter your HTTP basic authentication password:', 'code-profiler');
			echo ' ';
			echo "\033[30;40m";
			$password = trim( stream_get_line( STDIN, 255, PHP_EOL) );
			echo "\033[0m";
			$_POST['Authorization'] = 'Basic '. base64_encode( "{$assoc_args['u']}:{$password}" );
		}

		if ( $this->out == 'view') {
			WP_CLI::log( sprintf(
				__('Starting Code Profiler v%s (profile: %s)', 'code-profiler'),
				CODE_PROFILER_VERSION, $_POST['profile'] ) . "\n"
			);

			$progress = \WP_CLI\Utils\make_progress_bar( '', 3 );
			$progress->tick();
		}

		// Run the profiler
		$response = json_decode( codeprofiler_start_profiler(), true );
		if ( $response === false ) {
			$message = __('Unknown error returned by AJAX', 'code-profiler');
			WP_CLI::error( $message );
			exit;
		}
		if ( $response['status'] == 'error' ) {
			WP_CLI::error( $response['message'] );
			exit;
		}

		if ( $this->out == 'view') {
			$progress->tick();
		}

		// All good, run the parser
		$_POST['microtime'] = $response['microtime'];
		$response = json_decode( codeprofiler_prepare_report(), true );
		if ( $response === false ) {
			$message = __('Unknown error returned by AJAX', 'code-profiler');
			WP_CLI::error( $message );
			exit;
		}
		if ( $response['status'] == 'error' ) {
			WP_CLI::error( $response['message'] );
			exit;
		}

		if ( $this->out == 'view') {
			$progress->tick();
			$progress->finish();
		}

		// Run the parser and show the results
		$this->view( $args, $assoc_args, $response['cp_profile'] );

		exit;
	}


	/**
	 * Display last created profile.
	 *
	 */
	public function view( $args = [], $assoc_args = [], $profile = '') {

		$this->is_enabled();

		if (! empty( $assoc_args['out'] ) && in_array( $assoc_args['out'], ['json', 'csv'] ) ) {
			$this->out = $assoc_args['out'];
		}

		// The profile was just created (`wp code-profiler run`)...
		if (! empty( $profile ) ) {
			$this->file = code_profiler_get_profile_path( $profile );
			if ( $this->file === false ) {
				$message = __('Cannot find the profile file', 'code-profiler');
				WP_CLI::error( $message );
				exit;
			}
			$this->file .= '.slugs.profile';

		// ... or we show last created one  (`wp code-profiler view`)
		} else {
			$this->file = $this->find_last_profile();
			if ( $this->file === false ) {
				$message =	sprintf(
					__('No profile found. Run the profiler at least once to create a profile: %s', 'code-profiler'),
					$this->cmd_run
				);
				WP_CLI::error( $message );
				exit;
			}
		}

		// Fetch and display the profile's name only
		preg_match(
			'`'. CODE_PROFILER_UPLOAD_DIR .'/\d{10}\.\d+\.(.+?)\.slugs\.profile$`',
			$this->file,
			$match
		);

		$cp_slug = __('Slug', 'code-profiler');
		$cp_time = __('Execution time', 'code-profiler');
		$cp_name = __('Name', 'code-profiler');
		$cp_type = __('Type', 'code-profiler');

		if ( $this->out == 'view' ) {
			$buffer = '';
			$message = sprintf( __('Viewing: %s', 'code-profiler'), $match[1] );
			$date = date('Y/m/d \@ H:i:s', filemtime( $this->file ) );
			echo WP_CLI::colorize("\n%Y$message ~ $date%n\n\n");
			// Display stats
			$summary_file = str_replace('.slugs.profile', '', $this->file );
			echo code_profiler_getsummarystats( $summary_file, 'text');
		/**
		 * CSV output.
		 */
		} elseif ( $this->out == 'csv' ) {
			$buffer = "$cp_slug,\"$cp_time\",$cp_name,$cp_type\n";
		/**
		 * JSON-encoded output.
		 */
		} else {
			$buffer = [];
		}

		$slugs = $this->read_profile();

		$cp_options = get_option('code-profiler');

		// Get the total time and the slowest item
		$total_time = 0;
		foreach( $slugs as $k => $v ) {
			$total_time += $v[1];
		}

		$coeff = number_format( $slugs[0][1] / $total_time * 100 );

		foreach( $slugs as $k => $v ) {

			if ( $this->out == 'csv') {
				$buffer .= "{$v[0]},{$v[1]},{$v[2]},{$v[3]}\n";

			} elseif( $this->out == 'json') {
				$buffer[] = [
 					$cp_slug = $v[0],
					$cp_time = $v[1],
					$cp_name = $v[2],
					$cp_type = $v[3]
				];

			} else {
				// Display name, time and %
				if ( isset( $cp_options['display_name'] ) && $cp_options['display_name'] == 'slug' ) {
					$name = $v[0];
				} else {
					$name = $v[2];
				}
				// Inform if it's the theme or a mu-plugin
				if ( $v[3] == 'theme') {
					$name .= ' (theme)';
				} elseif ( $v[3] == 'mu-plugin') {
					$name .= ' (mu-plugin)';
				}

				$time    = number_format( $v[1], 3                  );
				$percent = number_format( $v[1] / $total_time * 100 );
				$chars   = number_format( $percent * 80 / $coeff    );
				// We use `echo` instead of `WP_CLI::log` because the layout could
				// be all messed-up when some caching plugins such as LiteSpeed Cache
				// are activated.
				echo " $name | {$time}s | {$percent}%\n";
				if (! $percent ) {
					echo " \u{258C}\n\n";
				} else {
					$bar = '';
					for ( $i = 0; $i < $chars; $i++ ) {
						$bar .= ' ';
					}
					echo WP_CLI::colorize(" %8$bar%n\n\n");
				}
			}
		}
		if ( $this->out == 'csv') {
			echo $buffer;

		} elseif ( $this->out == 'json' ) {
			echo json_encode( $buffer );
		}

		exit;
	}


	/**
	 * Retrieve the full path/name to the last created profile
	 *
	 */
	 private function find_last_profile() {

		$profiles = code_profiler_glob( CODE_PROFILER_UPLOAD_DIR, '\.slugs\.profile$', true );

		array_multisort(
			array_map('filectime', $profiles ),
			SORT_NUMERIC,
			SORT_DESC,
			$profiles
		);

		if (! empty( $profiles[0] ) ) {
			return $profiles[0];
		}

		return false;
	 }


	/**
	 * Parse and return the profile's data
	 *
	 */
	private function read_profile() {

		$profile = str_replace('.slugs.profile', '', $this->file );

		$res = code_profiler_get_profile_data( $profile );
		if ( isset( $res['error'] ) ) {
			WP_CLI::error( $res['error'] );
			exit;
		}

		// Sort data (slowest plugin/theme first)
		usort( $res, function( $a, $b ) {
			return $b[1] <=> $a[1];
		} );

		return $res;
	}


	/**
	 * Verify wether WP CLI integration is enabled or not
	 *
	 */
	private function is_enabled() {

		$cp_options = get_option('code-profiler');
		if ( empty( $cp_options['enable_wpcli'] ) ) {
			$message = __('WP-CLI integration is disabled. To enable it, log in to your admin dashboard and go to the Code Profiler settings page.', 'code-profiler');
			WP_CLI::error( $message );
			exit;
		}
	}


	/**
	 * Display help screen and quit.
	 *
	 */
	public function help() {

		$this->is_enabled();

		WP_CLI::log("\nCode Profiler v". CODE_PROFILER_VERSION .
			" (c)". date('Y') ." Jerome Bruandet & NinTechNet Limited ~ https://nintechnet.com/codeprofiler/\n\n".
			"  {$this->cmd_view}     ". __('View last created profile', 'code-profiler') ."\n".
			"  {$this->cmd_run}      ". __('Run the profiler in the frontend', 'code-profiler') ."\n\n".
			__('GLOBAL PARAMETERS', 'code-profiler') ."\n\n".
			"  --dest=<URL to profile>  **". __('Pro version only', 'code-profiler') ."**\n".
			"      ". __('Path to the WordPress page or post to profile. If missing, profile the frontend.', 'code-profiler') ."\n\n".
			"  --wpcron=<cron event> (optional)\n".
			"      ". __('WordPress cron event to profile.', 'code-profiler') ."\n\n".
			"  --user=<id|login|email> (optional)\n".
			"      ". __('Run the profiler as the corresponding WordPress user. If missing, run as an unauthenticated user.', 'code-profiler') ."\n\n".
			"  --u=<username> (optional)\n".
			"      ". __('HTTP Basic authentication username. You will be prompted to enter your password.', 'code-profiler') ."\n\n".
			"  --out=<json|csv> (optional)\n".
			"      ". __('Return the results in JSON-encoded or CSV format.', 'code-profiler') ."\n\n" );
		exit;
	}

}

WP_CLI::add_command(
	'code-profiler',
	'CodeProfiler_CLI',
	['shortdesc' => 'Profile your blog with Code Profiler.']
);

// =====================================================================
// EOF
