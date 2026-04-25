<?php
/*
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2026 Jean-Sebastien Morisset (https://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'JSMSTM_PLUGINDIR' ) ) {

	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'JsmStmTerm' ) ) {

	class JsmStmTerm {

		public function __construct() {

			/*
			 * Make sure we have a taxonomy slug to hook the metabox action.
			 */
			$tax_slug = SucomUtil::get_request_value( 'taxonomy' );	// Uses sanitize_text_field.

			if ( ! empty( $tax_slug ) ) {

				add_action( $tax_slug . '_edit_form', array( $this, 'add_meta_boxes' ), 1000, 1 );
			}

			add_action( 'wp_ajax_delete_jsmstm_meta', array( $this, 'ajax_delete_meta' ) );
		}

		public function add_meta_boxes( $obj ) {

			if ( empty( $obj->term_id ) ) {

				return;
			}

			$term_id  = $obj->term_id;
			$show_cap = apply_filters( 'jsmstm_show_metabox_capability', 'manage_options', $obj );
			$can_show = current_user_can( $show_cap, $term_id, $obj );

			if ( ! $can_show ) {

				return;

			} elseif ( ! apply_filters( 'jsmstm_show_metabox_taxonomy', true, $obj->taxonomy ) ) {

				return;
			}

			$metabox_id      = 'jsmstm';
			$metabox_title   = __( 'Term Metadata', 'jsm-show-term-meta' );
			$metabox_screen  = 'jsm-show-term-meta';
			$metabox_context = 'normal';
			$metabox_prio    = 'low';
			$callback_args   = array(	// Second argument passed to the callback function / method.
				'__block_editor_compatible_meta_box' => true,
			);

			add_meta_box( $metabox_id, $metabox_title, array( $this, 'show_metabox' ),
				$metabox_screen, $metabox_context, $metabox_prio, $callback_args );

			/*
			 * Keep the original 800px width for the form table and allow metaboxes to take the screen width.
			 */
			echo '<style type="text/css">';
			echo 'div.wrap form#edittag { max-width:none; }';
			echo 'div.wrap form#edittag table.form-table { max-width:800px; }';
			echo '</style>' . "\n";
			echo '<div class="metabox-holder">' . "\n";

			do_meta_boxes( $metabox_screen, 'normal', $obj );

			echo "\n" . '</div><!-- .metabox-holder -->' . "\n";
		}

		public function show_metabox( WP_Term $obj ) {

			echo $this->get_metabox( $obj );
		}

		public function get_metabox( WP_Term $obj ) {

			if ( empty( $obj->term_id ) ) {

				return;
			}

			$term_id      = $obj->term_id;
			$cf           = JsmStmConfig::get_config();
			$metadata     = get_metadata( 'term', $term_id );
			$exclude_keys = array();
			$metabox_id   = 'jsmstm';
			$admin_l10n   = $cf[ 'plugin' ][ 'jsmstm' ][ 'admin_l10n' ];

			$titles = array(
				'key'   => __( 'Key', 'jsm-show-term-meta' ),
				'value' => __( 'Value', 'jsm-show-term-meta' ),
			);

			return SucomUtilMetabox::get_table_metadata( $metadata, $exclude_keys, $obj, $term_id, $metabox_id, $admin_l10n, $titles );
		}

		public function ajax_delete_meta() {

			$doing_ajax = SucomUtilWP::doing_ajax();

			if ( ! $doing_ajax ) return;

			check_ajax_referer( JSMSTM_NONCE_NAME, '_ajax_nonce', $die = true );

			if ( empty( $_POST[ 'obj_id' ] ) || empty( $_POST[ 'meta_key' ] ) ) die( -1 );

			/*
			 * Note that the $table_row_id value must match the value used in SucomUtilMetabox::get_table_metadata(),
			 * so that jQuery can hide the table row after a successful delete.
			 */
			$metabox_id   = 'jsmstm';
			$obj_id       = SucomUtil::sanitize_int( $_POST[ 'obj_id' ] );	// Returns integer or null.
			$meta_key     = SucomUtil::sanitize_meta_key( $_POST[ 'meta_key' ] );
			$table_row_id = SucomUtil::sanitize_key( $metabox_id . '_' . $obj_id . '_' . $meta_key );
			$term_obj     = get_term( $obj_id );
			$delete_cap   = apply_filters( 'jsmstm_delete_meta_capability', 'manage_options', $term_obj );
			$can_delete   = current_user_can( $delete_cap, $obj_id, $term_obj );

			if ( ! $can_delete ) die( -1 );

			if ( delete_metadata( 'term', $obj_id, $meta_key ) ) die( $table_row_id );

			die( false );	// Show delete failed message.
		}
	}
}
