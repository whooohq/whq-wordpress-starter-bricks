<?php
/**
Plugin Name: Multiple Roles
Description: Allow users to have multiple roles on one site.
Version: 1.3.7
Author: Christian Neumann
Author URI: https://utopicode.de
Plugin URI: https://wordpress.org/plugins/multiple-roles/
Github URI: https://github.com/chrneumann/multiple-roles
Text Domain: multiple-roles
 */

define( 'MDMR_PATH', plugin_dir_path( __FILE__ ) );
define( 'MDMR_URL', plugin_dir_url( __FILE__ ) );

/**
 * Load files and add hooks to get things rolling.
 */
require_once MDMR_PATH . 'model.php';
require_once MDMR_PATH . 'controllers/checklist.php';
require_once MDMR_PATH . 'controllers/column.php';

$mdmr_model     = new MDMR_Model();
$mdmr_checklist = new MDMR_Checklist_Controller( $mdmr_model );
$mdmr_column    = new MDMR_Column_Controller( $mdmr_model );

add_action( 'init', 'mdmr_load_translation' );
function mdmr_load_translation() {
	load_plugin_textdomain( 'multiple-roles', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
