<?php
/**
 * A pseudo-cron daemon for scheduling WordPress tasks.
 *
 * WP-Cron is triggered when the site receives a visit. In the scenario
 * where a site may not receive enough visits to execute scheduled tasks
 * in a timely manner, this file can be called directly or via a server
 * cron daemon for X number of times.
 *
 * Defining DISABLE_WP_CRON as true and calling this file directly are
 * mutually exclusive and the latter does not rely on the former to work.
 *
 * The HTTP request to this file will not slow down the visitor who happens to
 * visit when a scheduled cron event runs.
 *
 * @package WordPress
 */

ignore_user_abort( true );

if ( ! headers_sent() ) {
	header( 'Expires: Wed, 11 Jan 1984 05:00:00 GMT' );
	header( 'Cache-Control: no-cache, must-revalidate, max-age=0' );
}

$response['status'] = 'error';

if (! empty( $_POST ) || defined( 'DOING_AJAX' ) || defined( 'DOING_CRON' ) ) {
	$response['message'] = 'Error: Cannot start wp-cron.';
	// wp_send_json is not loaded yet.
	echo json_encode( $response );
	exit;
}

/**
 * Tell WordPress the cron task is running.
 */
define( 'DOING_CRON', true );

/**
 * Load WordPress bootstrap file and our MU plugin.
 */
if (! is_file(  __DIR__ .'/tmp/profiler.inc.php') ) {
	$response['message'] = 'Error: Cannot find [/tmp/profiler.inc.php].';
	echo json_encode( $response );
	exit;
}
include_once __DIR__ .'/tmp/profiler.inc.php';
require_once ABSPATH . 'wp-load.php';

/**
 * No one else is allowed to run this script.
 * Full verification is done by the MU plugin.
 */
if (! isset( $_REQUEST['CODE_PROFILER_ON'] ) ||
	! preg_match('/^\d{10}\.\d+$/', $_REQUEST['CODE_PROFILER_ON'] ) ) {

	$response['message'] = 'Not allowed.';
	wp_send_json( $response );
}

/**
 * Make sure we have a valid cron task.
 */
if ( empty( $_GET['wpcron'] ) ) {
	$response['message'] = 'Error: No cron event selected.';
	wp_send_json( $response );
}

/**
 * Get the event to run.
 */
$wpcron  = trim( $_GET['wpcron'] );
$crons   = [];
$wpcrons = _get_cron_array();

foreach ( $wpcrons as $timestamp => $cronhooks ) {
	foreach ( $cronhooks as $hook => $keys ) {
		if ( $hook == $wpcron ) {
			$time = (int) microtime( true );
			/**
			 * There could be multiple cron events scheduled at the same time.
			 */
			$crons[ $time - 10 ][ $hook ] = $keys;
			break 2;
		}
	}
}
if ( empty( $crons ) ) {
	$response['message'] = 'Error: Cannot find the requested cron event.';
	wp_send_json( $response );
}

// Attempt to raise the PHP memory limit for cron event processing.
wp_raise_memory_limit( 'cron' );

/**
 * Retrieves the cron lock.
 *
 * Returns the uncached `doing_cron` transient.
 *
 * @ignore
 * @since 3.3.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @return string|int|false Value of the `doing_cron` transient, 0|false otherwise.
 */
function _get_cron_lock() {
	global $wpdb;

	$value = 0;
	if ( wp_using_ext_object_cache() ) {
		/*
		 * Skip local cache and force re-fetch of doing_cron transient
		 * in case another process updated the cache.
		 */
		$value = wp_cache_get( 'doing_cron', 'transient', true );
	} else {
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", '_transient_doing_cron' ) );
		if ( is_object( $row ) ) {
			$value = $row->option_value;
		}
	}

	return $value;
}

$gmt_time = microtime( true );
/**
 * The cron lock: a unix timestamp from when the cron was spawned.
 */
$doing_cron_transient = get_transient( 'doing_cron' );
/**
 * Use global $doing_wp_cron lock, otherwise use the GET lock. If no lock, try to grab a new lock.
 */
if ( empty( $doing_wp_cron ) ) {
	if ( empty( $_GET['doing_wp_cron'] ) ) {
		// Called from external script/job. Try setting a lock.
		if ( $doing_cron_transient && ( $doing_cron_transient + WP_CRON_LOCK_TIMEOUT > $gmt_time ) ) {
			$response['message'] = 'Error: WordPress cannot set a lock.';
			wp_send_json( $response );
		}
		$doing_wp_cron        = sprintf( '%.22F', microtime( true ) );
		$doing_cron_transient = $doing_wp_cron;
		set_transient( 'doing_cron', $doing_wp_cron );
	} else {
		$doing_wp_cron = $_GET['doing_wp_cron'];
	}
}

/*
 * The cron lock (a unix timestamp set when the cron was spawned),
 * must match $doing_wp_cron (the "key").
 */
if ( $doing_cron_transient !== $doing_wp_cron ) {
	$response['message'] = 'Error: Transients do not match.';
	wp_send_json( $response );
}

foreach ( $crons as $timestamp => $cronhooks ) {
	foreach ( $cronhooks as $hook => $keys ) {
		foreach ( $keys as $k => $v ) {
			/**
			 * Fires scheduled events.
			 *
			 * @ignore
			 * @since 2.1.0
			 *
			 * @param string $hook Name of the hook that was scheduled to be fired.
			 * @param array  $args The arguments to be passed to the hook.
			 */
			do_action_ref_array( $hook, $v['args'] );

			// If the hook ran too long and another cron process stole the lock, quit.
			if ( _get_cron_lock() !== $doing_wp_cron ) {
				$response['message'] = 'Error: The hook ran too long.';
				wp_send_json( $response );
			}
		}
	}
}

if ( _get_cron_lock() === $doing_wp_cron ) {
	delete_transient( 'doing_cron' );
}

die();
