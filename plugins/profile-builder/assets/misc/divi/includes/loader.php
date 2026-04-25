<?php

if ( ! class_exists( 'ET_Builder_Element' ) ) {
	return;
}

$module_files = glob( __DIR__ . '/modules/*/*.php' );

// Load custom Divi Builder modules
foreach ( (array) $module_files as $module_file ) {
	if ( $module_file && preg_match( "/\/modules\/\b([^\/]+)\/\\1\.php$/", $module_file ) ) {
        if ( str_ends_with( $module_file, '/user-listing/user-listing.php' ) ){
            if( defined( 'WPPB_PAID_PLUGIN_URL' ) && wppb_check_if_add_on_is_active( 'wppb_userListing' ) && file_exists( WPPB_PAID_PLUGIN_DIR . '/add-ons/user-listing/assets/misc/gutenberg-blocks/user-listing/user-listing.php' ) )
                require_once $module_file;
        } else {
            require_once $module_file;
        }
    }
}
