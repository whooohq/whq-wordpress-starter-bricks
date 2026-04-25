<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

add_filter( 'wppb_edit_other_users_count_limit', 'wppb_toolbox_edit_users_dropdown_limit' );
function wppb_toolbox_edit_users_dropdown_limit() {
	return 100000;
}
