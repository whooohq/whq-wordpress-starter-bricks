<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

add_filter( 'wppb_show_password_visibility_toggle', '__return_true' );