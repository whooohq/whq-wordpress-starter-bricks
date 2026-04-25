<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2026 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'JSMSPM_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'JsmSpmPost' ) ) {

	class JsmSpmPost {

		public function __construct() {

			add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 1000, 2 );
			add_action( 'wp_ajax_get_metabox_postbox_id_jsmspm_inside', array( $this, 'ajax_get_metabox' ) );
			add_action( 'wp_ajax_delete_jsmspm_meta', array( $this, 'ajax_delete_meta' ) );
		}

		public function add_meta_boxes( $post_type, $obj ) {

			if ( empty( $obj->ID ) ) {

				return;
			}

			$post_id  = $obj->ID;
			$show_cap = apply_filters( 'jsmspm_show_metabox_capability', 'manage_options', $obj );
			$can_show = current_user_can( $show_cap, $post_id, $obj );

			if ( ! $can_show ) {

				return;

			} elseif ( ! apply_filters( 'jsmspm_show_metabox_post_type', true, $post_type ) ) {

				return;
			}

			$metabox_id      = 'jsmspm';
			$metabox_title   = __( 'Post Metadata', 'jsm-show-post-meta' );
			$metabox_screen  = $post_type;
			$metabox_context = 'normal';
			$metabox_prio    = 'low';
			$callback_args   = array(	// Second argument passed to the callback function / method.
				'__block_editor_compatible_meta_box' => true,
			);

			add_meta_box( $metabox_id, $metabox_title, array( $this, 'show_metabox' ),
				$metabox_screen, $metabox_context, $metabox_prio, $callback_args );
		}

		public function show_metabox( WP_Post $obj ) {

			echo $this->get_metabox( $obj );
		}

		public function get_metabox( WP_Post $obj ) {

			if ( ! empty( $obj->ID ) ) {

				$post_id = $obj->ID;

			} else return;

			$cf           = JsmSpmConfig::get_config();
			$metadata     = get_metadata( 'post', $post_id );
			$exclude_keys = array();
			$metabox_id   = 'jsmspm';
			$admin_l10n   = $cf[ 'plugin' ][ 'jsmspm' ][ 'admin_l10n' ];

			$titles = array(
				'key'   => __( 'Key', 'jsm-show-post-meta' ),
				'value' => __( 'Value', 'jsm-show-post-meta' ),
			);

			return SucomUtilMetabox::get_table_metadata( $metadata, $exclude_keys, $obj, $post_id, $metabox_id, $admin_l10n, $titles );
		}

		public function ajax_get_metabox() {

			$doing_ajax = SucomUtilWP::doing_ajax();

			if ( ! $doing_ajax ) {	// Just in case.

				return;

			} elseif ( SucomUtil::get_const( 'DOING_AUTOSAVE' ) ) {

				die( -1 );
			}

			check_ajax_referer( JSMSPM_NONCE_NAME, '_ajax_nonce', $die = true );

			$post_id = isset( $_POST[ 'post_id' ] ) ? SucomUtil::sanitize_int( $_POST[ 'post_id' ] ) : 0;	// Returns integer or null.

			if ( empty( $post_id ) ) {

				die( -1 );
			}

			$obj = SucomUtilWP::get_post_object( $post_id );

			if ( ! is_object( $obj ) ) {

				die( -1 );

			} elseif ( empty( $obj->post_type ) ) {

				die( -1 );

			} elseif ( empty( $obj->post_status ) ) {

				die( -1 );
			}

			$show_cap = apply_filters( 'jsmspm_show_metabox_capability', 'manage_options', $obj );
			$can_show = current_user_can( $show_cap, $post_id, $obj );

			if ( ! $can_show ) {

				die( -1 );
			}

			$metabox_html = $this->get_metabox( $obj );

			die( $metabox_html );
		}

		public function ajax_delete_meta() {

			$doing_ajax = SucomUtilWP::doing_ajax();

			if ( ! $doing_ajax ) return;

			check_ajax_referer( JSMSPM_NONCE_NAME, '_ajax_nonce', $die = true );

			if ( empty( $_POST[ 'obj_id' ] ) || empty( $_POST[ 'meta_key' ] ) ) die( -1 );

			/*
			 * Note that the $table_row_id value must match the value used in SucomUtilMetabox::get_table_metadata(),
			 * so that jQuery can hide the table row after a successful delete.
			 */
			$metabox_id   = 'jsmspm';
			$obj_id       = SucomUtil::sanitize_int( $_POST[ 'obj_id' ] );	// Returns integer or null.
			$meta_key     = SucomUtil::sanitize_meta_key( $_POST[ 'meta_key' ] );
			$table_row_id = SucomUtil::sanitize_key( $metabox_id . '_' . $obj_id . '_' . $meta_key );
			$post_obj     = get_post( $obj_id );
			$delete_cap   = apply_filters( 'jsmspm_delete_meta_capability', 'manage_options', $post_obj );
			$can_delete   = current_user_can( $delete_cap, $obj_id, $post_obj );

			if ( ! $can_delete ) die( -1 );

			if ( delete_metadata( 'post', $obj_id, $meta_key ) ) die( $table_row_id );

			die( false );	// Show delete failed message.
		}
	}
}
