<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

add_filter( 'wppb_ul_show_filter_count', '__return_false' );
