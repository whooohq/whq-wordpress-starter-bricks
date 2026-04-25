<?php
/**
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

if (! defined( 'ABSPATH' ) ) { die( 'Forbidden' ); }

// =====================================================================

class CodeProfiler_WPCron {


	private static $options	= [];
	private static $url		= 'https://api.nintechnet.com/coupons';
	private static $file 	= 'coupon.png';


	/**
	 * Hook (main site only).
	 */
	public static function init() {
		/**
		 * We run on the main site only.
		 */
		if (! is_main_site() ) {
			return ['error' => 'child site'];
		}
		add_action('codeprofiler_wpcron', [ __CLASS__, 'run'] );
	}


	/**
	 * Install scheduled tasks (daily).
	 */
	public static function install() {

		if (! is_main_site() ) {
			return;
		}

		if ( wp_next_scheduled('codeprofiler_wpcron') ) {
			wp_clear_scheduled_hook('codeprofiler_wpcron');
		}
		/**
		 * Start in 30mn.
		 */
		wp_schedule_event( time() + 1800, 'daily', 'codeprofiler_wpcron');
	}


	/**
	 * Unnstall scheduled tasks (daily).
	 */
	public static function uninstall() {

		if ( wp_next_scheduled('codeprofiler_wpcron') ) {
			wp_clear_scheduled_hook('codeprofiler_wpcron');
		}
	}


	/**
	 * Run scheduled tasks.
	 */
	public static function run() {

		self::$options = self::get_option('code-profiler');

		self::get_coupon();
	}


	/**
	 * Display any available coupon.
	 */
	public static function display_coupon() {
		/**
		 * Child site must retrieve the options from the main site.
		 */
		self::$options = self::get_option('code-profiler');

		if ( empty( self::$options['coupon']['date'] ) ) {
			return ['error' => 'no coupon'];
		}
		/**
		 * Make sure it didn't expire yet.
		 */
		$today = date('Y-m-d');
		if ( $today > self::$options['coupon']['date'] ) {
			return ['error' => 'expired coupon'];
		}

		if (! is_file( CODE_PROFILER_UPLOAD_DIR .'/'. self::$file ) ) {
			return ['error' => 'missing file'];
		}
		$data = file_get_contents( CODE_PROFILER_UPLOAD_DIR .'/'. self::$file );

		$until = 'This offer is valid until '.
					date('F d', strtotime( self::$options['coupon']['date'] ) );

		if (! empty( self::$options['coupon']['url'] ) ) {
			$url = self::$options['coupon']['url'];
		} else {
			$url = 'https://nintechnet.com/';
		}

		echo '<p><a href="'. esc_url( $url ) .'" alt="Go Pro! Limited time offer" '.
			'title="Go Pro! Limited time offer" target="_blank" rel="noreferrer noopener">'.
			'<img style="max-width:250px" src="data:image/png;base64, '. esc_attr( $data ) .'" />'.
			'<br />'. esc_html( $until ) .'</a></p>';
	}


	/**
	 * Remote connection.
	 */
	private static function get_coupon() {
		/**
		 * It should not run more than once daily (86400s).
		 */
		if (! empty( self::$options['cronjobs']['coupon']['last'] ) &&
			self::$options['cronjobs']['coupon']['last'] + 86400 > time() ) {

			return ['error' => 'frequency'];
		}
		/**
		 * Update last checked time.
		 */
		self::$options['cronjobs']['coupon']['last'] = time();
		self::update_option('code-profiler', self::$options );

		/**
		 * Connect.
		 */
		global $wp_version;
		$res = wp_remote_get(
			self::$url,
			[
				'timeout'		=> 5,
				'httpversion'	=> '1.1' ,
				'user-agent'	=> 'Mozilla/5.0 (compatible; Code-Profiler/'.
										CODE_PROFILER_VERSION ."; WordPress/$wp_version)",
				'sslverify'		=> true,
				'headers' => [
					'ntn-plugin'	=> 'cp',
					'ntn-cache'		=>	md5( network_site_url() )
				]
			]
		);
		if (! is_wp_error( $res ) && $res['response']['code'] == 200 ) {
			$coupon = json_decode( $res['body'], true );

			if ( empty( $coupon['cp']['img'] ) ) {
				/**
				 * Clear the old coupon.
				 */
				if (! empty( self::$options['coupon'] ) ) {
					unset( self::$options['coupon'] );
					self::update_option('code-profiler', self::$options );
				}
				return ['error' => 'no coupon'];
			}
			/**
			 * Save the image.
			 */
			@ file_put_contents( CODE_PROFILER_UPLOAD_DIR .'/'. self::$file, $coupon['cp']['img'] );
			$coupon['cp']['img'] = self::$file;

			if ( empty( self::$options['coupon'] ) || self::$options['coupon'] != $coupon['cp'] ) {
				/**
				 * Save/update the coupon.
				 */
				self::$options['coupon'] = $coupon['cp'];
				self::update_option('code-profiler', self::$options );
			}
			return $coupon['cp'];
		}
		return ['error' => 'HTTP error'];

	}


	/**
	 * Update options.
	 */
	private static function update_option( $option, $new_value ) {

		if ( is_multisite() ) {
			$res = update_site_option( $option, $new_value );
		} else {
			$res = update_option( $option, $new_value );
		}
		if ( $res == false ) {
			return [];
		}
		return $res;
	}


	/**
	 * Get options.
	 */
	private static function get_option( $option ) {

		if ( is_multisite() ) {
			$res = get_site_option( $option );
		} else {
			$res = get_option( $option );
		}
		if ( $res == false ) {
			return [];
		}
		return $res;
	}
}

CodeProfiler_WPCron::init();

// =====================================================================
// EOF
