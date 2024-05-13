<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_ajax_piotnetforms_export_form_submission', 'piotnetforms_export_form_submission' );
add_action( 'wp_ajax_nopriv_piotnetforms_export_form_submission', 'piotnetforms_export_form_submission' );

function piotnetforms_export_form_submission_prepare_http_header() {
	$now = gmdate( 'D, d M Y H:i:s' );
	header( 'Expires: Tue, 03 Jul 2001 06:00:00 GMT' );
	header( 'Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate' );
	header( "Last-Modified: {$now} GMT" );
	header( 'Content-Type: application/force-download' );
	header( 'Content-Type: application/octet-stream' );
	header( 'Content-Type: application/download' );
	header( 'Content-Disposition: attachment;filename=piotnetforms-database.csv' );
	header( 'Content-Transfer-Encoding: binary' );
}

function piotnetforms_export_form_submission() {
	piotnetforms_export_form_submission_prepare_http_header();

	$args = [
		'post_type' => 'piotnetforms-data',
		'posts_per_page' => -1,
	];

	if ( !empty( $_GET['post_status'] ) ) {
		if ( $_GET['post_status'] != 'all' ) {
			$args['post_status'] = $_GET['post_status'];
		}
	}

	if ( !empty( $_GET['post_type'] ) ) {
		$args['post_type'] = $_GET['post_type'];
	}

	if ( !empty( $_GET['m'] ) ) {
		$args['m'] = $_GET['m'];
	}

	if ( !empty( $_GET['form_id'] ) ) {
		$args['meta_key'] = 'form_id';
		$args['meta_value'] = str_replace( '+', ' ', $_GET['form_id'] );
	}

	$query = new WP_Query( $args );
	$field_id = [];
	$fields = [];
	$th = [];

	$index = 0;
	if ( $query->have_posts() ) : while ( $query->have_posts() ) : $query->the_post();
		$index++;
		if ( $index == 1 ) {
			$fields_database = get_post_meta( get_the_ID(), '_piotnetforms_fields_database', true );
			if ( $fields_database ) {
				$fields_database = json_decode( $fields_database, true );
			}
		}
		$metas = get_post_meta( get_the_ID() );

		foreach ( $metas as $key=>$value ) {
			if ( !in_array( $key, $field_id ) ) {
				if ( is_array( $fields_database ) ) {
					if ( isset( $fields_database[$key] ) ) {
						$field_id[] = $key;
					}
				} else {
					$field_id[] = $key;
				}
			}
		}

	endwhile;
	endif;

	foreach ( $field_id as $id ) {
		if ( $id != '_elementor_controls_usage' && $id != '_edit_lock' && $id != 'form_id_piotnetforms' && $id != 'post_id' && $id != '_piotnetforms_fields_database' ) {
			if ( is_array( $fields_database ) ) {
				if ( isset( $fields_database[$id] ) ) {
					$th[] = !empty( $fields_database[$id]['label'] ) ? $fields_database[$id]['label'] : $id;
				}
			} else {
				$th[] = $id;
			}
		}
	}

	$fields[] = $th;

	if ( $query->have_posts() ) : while ( $query->have_posts() ) : $query->the_post();
		$tr = [];
		foreach ( $field_id as $id ) {
			if ( $id != '_elementor_controls_usage' && $id != '_edit_lock' && $id != 'form_id_piotnetforms' && $id != 'post_id' && $id != '_piotnetforms_fields_database' ) {
				$meta_value = get_post_meta( get_the_ID(), $id, true );
				$tr[] = $meta_value;
			}
		}
		$fields[] = $tr;
	endwhile;
	endif;

	$output = fopen( 'php://output', 'w' );

	fwrite( $output, "\xEF\xBB\xBF" );

	foreach ( $fields as $line ) {
		fputcsv( $output, $line );
	}

	wp_die();
}
