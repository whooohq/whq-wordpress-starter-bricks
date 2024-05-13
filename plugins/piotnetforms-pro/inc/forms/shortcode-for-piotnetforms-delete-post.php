<?php

function piotnet_forms_delete_post_shortcode( $args, $content ) {
	ob_start();
	if ( is_user_logged_in() ) {
		if ( current_user_can( 'edit_others_posts' ) || get_current_user_id() == get_post( get_the_ID() )->post_author ) {
			$delete_text = __( 'Delete Post', 'piotnetforms' );
            $confirm_delete = empty($args['confirm_delete']) ? 'Delete post1 {post_id}?' : $args['confirm_delete'];
			$redirect = get_home_url();
			$force_delete = 0;

			if ( !empty( $args['delete_text'] ) ) {
				$delete_text = $args['delete_text'];
			}

			if ( !empty( $args['redirect'] ) ) {
				$redirect = $args['redirect'];
			}

			if ( !empty( $args['force_delete'] ) ) {
				$force_delete = $args['force_delete'];
			}

			wp_enqueue_script( 'piotnetforms-script' );
			wp_enqueue_style( 'piotnetforms-style' );

			echo '<a data-piotnetforms-delete-post="' . get_the_ID() . '" data-confirm-delete="'.$confirm_delete.'" data-piotnetforms-delete-post-redirect="' . $redirect . '" data-piotnetforms-delete-post-force="' . $force_delete . '" class="piotnetforms-delete-post">' . $delete_text . '</a>';

			echo '<div data-piotnetforms-ajax-url="' . admin_url( 'admin-ajax.php' ) . '"></div>';
			wp_enqueue_script( 'piotnetforms-advanced2-script' );
		}
	}
	return ob_get_clean();
}
add_shortcode( 'piotnetforms_delete_post', 'piotnet_forms_delete_post_shortcode' );
