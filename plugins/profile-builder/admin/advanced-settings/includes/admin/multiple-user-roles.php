<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

add_filter( 'wppb_backend_allow_multiple_user_roles_selection', '__return_false' );