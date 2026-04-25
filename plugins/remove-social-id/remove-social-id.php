<?php

/**
 *
 * @link              https://nitin247.com/plugin/remove-social-id/
 * @since             1.0
 * @package           Remove_Social_ID
 *
 * @wordpress-plugin
 * Plugin Name:       Remove Social ID for WP
 * Plugin URI:        https://wordpress.org/plugins/remove-social-id/
 * Description:       Remove Social ID for WordPress removes querystring fbclid, gclid and redirects the URL for your WordPress site.
 * Version:           1.3
 * Author:            Nitin Prakash
 * Author URI:        https://nitin247.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       remove_social_id
 * Domain Path:       /languages
 * Requires PHP:      5.6
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || die( 'WordPress Error! Opening plugin file directly' );

define( 'REMOVE_CLID_FOR_WORDPRESS_VERSION', '1.3' );

function remove_social_id_redirect_page() {

	if ( isset( $_SERVER['HTTPS'] ) &&
		( $_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1 ) || // phpcs:ignore
		isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) &&
		$_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' ) {
		$protocol = 'https://';
	} else {
		$protocol = 'http://';
	}

	$host = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( $_SERVER['HTTP_HOST'] ) : '';
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( $_SERVER['REQUEST_URI'] ) : '';

	$currenturl = '';

	if ( ! empty( $host ) && ! empty( $uri ) ) {
		$currenturl = $protocol . $host . $uri;
	}

	if ( ! empty( $currenturl ) && ( strpos( $currenturl, 'fbclid' ) || strpos( $currenturl, 'gclid' ) || strpos( $currenturl, 'msclkid' ) ) ) {
		$stripped_url = remove_social_id_strip_clid( $currenturl );
		wp_redirect( $stripped_url );
		exit;
	}
}

add_action( 'template_redirect', 'remove_social_id_redirect_page', 5 );

function remove_social_id_strip_clid( $url ) {
	$patterns = array(
		'/(\?|&)fbclid=[^&]*$/' => '',
		'/\?fbclid=[^&]*&/'     => '?',
		'/&fbclid=[^&]*&/'      => '&',
		'/(\?|&)gclid=[^&]*$/'  => '',
		'/\?gclid=[^&]*&/'      => '?',
		'/&gclid=[^&]*&/'       => '&',
	);

	$search  = array_keys( $patterns );
	$replace = array_values( $patterns );

	return preg_replace( $search, $replace, $url );
}
