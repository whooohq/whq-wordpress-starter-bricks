<?php
/**
 * Auto registration
 *
 * @since      	1.0
 */
defined( 'WPINC' ) || exit;

$php_files = array(
	// core file priority
	'src/instance.cls.php',

	// main src files
	'src/admin.cls.php',
	'src/auth.cls.php',
	'src/captcha.cls.php',
	'src/cli.cls.php',
	'src/conf.cls.php',
	'src/core.cls.php',
	'src/data.cls.php',
	'src/f.cls.php',
	'src/gui.cls.php',
	'src/installer.cls.php',
	'src/ip.cls.php',
	'src/lang.cls.php',
	'src/pswdless.cls.php',
	'src/rest.cls.php',
	'src/router.cls.php',
	'src/s.cls.php',
	'src/site.cls.php',
	'src/sms.cls.php',
	'src/twofa.cls.php',
	'src/util.cls.php',
);
foreach ($php_files as $class) {
	$file = DOLOGIN_DIR . $class;
	require_once $file;
}

if ( ! function_exists( 'dologin_autoload' ) ) {
	function dologin_autoload( $cls ) {
		if ( strpos( $cls, 'dologin' ) !== 0 ) {
			return;
		}

		$file = explode( '\\', $cls );
		array_shift( $file );
		$file = implode( '/', $file );
		$file = str_replace( '_', '-', strtolower( $file ) );

		if ( strpos( $file, 'lib/' ) === 0 || strpos( $file, 'thirdparty/' ) === 0 ) {
			$file = DOLOGIN_DIR . $file . '.cls.php';
		}
		else {
			$file = DOLOGIN_DIR . 'src/' . $file . '.cls.php';
		}

		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
}

spl_autoload_register( 'dologin_autoload' );

