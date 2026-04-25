<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2026 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'JSMSUM_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'JsmSumUser' ) ) {

	class JsmSumUser {

		public function __construct() {

			add_action( 'edit_user_profile', array( $this, 'add_meta_boxes' ), 1000, 1 );
			add_action( 'show_user_profile', array( $this, 'add_meta_boxes' ), 1000, 1 );
			add_action( 'wp_ajax_delete_jsmsum_meta', array( $this, 'ajax_delete_meta' ) );
		}

		public function add_meta_boxes( $obj ) {

			if ( empty( $obj->ID ) ) {

				return;
			}

			$user_id  = $obj->ID;
			$screen   = get_current_screen();
			$show_cap = apply_filters( 'jsmsum_show_metabox_capability', 'manage_options', $obj );
			$can_show = current_user_can( $show_cap, $user_id, $obj );

			if ( ! $can_show ) {

				return;

			} elseif ( ! apply_filters( 'jsmsum_show_metabox_screen_base', true, $screen->base ) ) {

				return;
			}

			$metabox_id      = 'jsmsum';
			$metabox_title   = __( 'User Metadata', 'jsm-show-user-meta' );
			$metabox_screen  = 'jsm-show-user-meta';
			$metabox_context = 'normal';
			$metabox_prio    = 'low';
			$callback_args   = array(	// Second argument passed to the callback function / method.
				'__block_editor_compatible_meta_box' => true,
			);

			add_meta_box( $metabox_id, $metabox_title, array( $this, 'show_metabox' ),
				$metabox_screen, $metabox_context, $metabox_prio, $callback_args );

			echo '<div class="metabox-holder">' . "\n";

			do_meta_boxes( $metabox_screen, 'normal', $obj );

			echo "\n" . '</div><!-- .metabox-holder -->' . "\n";
		}

		public function show_metabox( WP_User $obj ) {

			echo $this->get_metabox( $obj );
		}

		public function get_metabox( WP_User $obj ) {

			if ( empty( $obj->ID ) ) return;

			$user_id      = $obj->ID;
			$cf           = JsmSumConfig::get_config();
			$metadata     = get_metadata( 'user', $user_id );
			$exclude_keys = array();
			$metabox_id   = 'jsmsum';
			$admin_l10n   = $cf[ 'plugin' ][ 'jsmsum' ][ 'admin_l10n' ];

			$titles = array(
				'key'   => __( 'Key', 'jsm-show-user-meta' ),
				'value' => __( 'Value', 'jsm-show-user-meta' ),
			);

			return SucomUtilMetabox::get_table_metadata( $metadata, $exclude_keys, $obj, $user_id, $metabox_id, $admin_l10n, $titles );
		}

		public function ajax_delete_meta() {

			$doing_ajax = SucomUtilWP::doing_ajax();

			if ( ! $doing_ajax ) return;

			check_ajax_referer( JSMSUM_NONCE_NAME, '_ajax_nonce', $die = true );

			if ( empty( $_POST[ 'obj_id' ] ) || empty( $_POST[ 'meta_key' ] ) ) die( -1 );

			/*
			 * Note that the $table_row_id value must match the value used in SucomUtilMetabox::get_table_metadata(),
			 * so that jQuery can hide the table row after a successful delete.
			 */
			$metabox_id   = 'jsmsum';
			$obj_id       = SucomUtil::sanitize_int( $_POST[ 'obj_id' ] );	// Returns integer or null.
			$meta_key     = SucomUtil::sanitize_meta_key( $_POST[ 'meta_key' ] );
			$table_row_id = SucomUtil::sanitize_key( $metabox_id . '_' . $obj_id . '_' . $meta_key );
			$user_obj     = get_userdata( $obj_id );
			$delete_cap   = apply_filters( 'jsmsum_delete_meta_capability', 'manage_options', $user_obj );
			$can_delete   = current_user_can( $delete_cap, $obj_id, $user_obj );

			if ( ! $can_delete ) die( -1 );

			if ( delete_metadata( 'user', $obj_id, $meta_key ) ) die( $table_row_id );

			die( false );	// Show delete failed message.
		}
	}
}
