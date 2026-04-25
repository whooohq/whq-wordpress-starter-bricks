<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

add_shortcode( 'wppb-list-roles', 'wppb_toolbox_list_roles_handler' );
function wppb_toolbox_list_roles_handler($atts){

	$args = shortcode_atts(
		array(
			'user_id'    => array(),
		),
		$atts
	);

	$content = '';

	if ( ! empty( $args['user_id'] ) )
	{
		$users_id = array_map( 'trim', explode( ',', $args['user_id'] ) );
		$all_users = get_users();

		foreach ( $all_users as $user )
		{
			if ( in_array( $user->ID, $users_id ) )
			{
				$roles_list = implode( ', ', $user->roles );
				$content = $content . $roles_list . '<br>';
			}
		}
	}
	else {
		$current_user = wp_get_current_user();

		if ( ! empty( $current_user->roles ) ) {
			$roles_list = implode( ', ', $current_user->roles );
			$content = $content . $roles_list;
		}

	}
	return do_shortcode( $content );
}