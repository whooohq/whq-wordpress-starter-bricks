<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/*
Description: Adds a PB subpage where you can Import and Export settings of Profile Builder.
*/

/* include content of Import and Export tabs */
require_once 'pbie-import.php';
require_once 'pbie-export.php';

/* add submenu page */
add_action( 'admin_menu', 'wppb_pbie_register_submenu_page', 18 );

function wppb_pbie_register_submenu_page() {
	add_submenu_page( 'profile-builder', __( 'Import and Export', 'profile-builder' ), __( 'Import and Export', 'profile-builder' ), 'manage_options', 'pbie-import-export', 'wppb_pbie_submenu_page_callback' );
}

function wppb_pbie_submenu_page_callback() {
	wppb_pbie_page();
}

/**
 * adds Import and Export tab
 *
 * @param string  $current  tab to display. default 'import'.
 */
function wppb_pbie_tabs( $current = 'import' ) {
	$tabs = array(
		'import' => __( 'Import', 'profile-builder' ),
		'export' => __( 'Export', 'profile-builder' )
	);

	echo '<nav class="nav-tab-wrapper cozmoslabs-nav-tab-wrapper">';
	foreach( $tabs as $tab => $name ) {
		$class = ( $tab == $current ) ? ' nav-tab-active' : '';
		echo "<a class='nav-tab". esc_attr( $class )."' href='?page=pbie-import-export&tab=". esc_attr( $tab ) ."'>". esc_html( $name ) ."</a>";
	}
	echo '</nav>';
}

/* PB Import and Export subpage content function */
function wppb_pbie_page() {
	global $pagenow;

	?>
	<div class="wrap cozmoslabs-wrap">

        <h1></h1>
        <!-- WordPress Notices are added after the h1 tag -->

        <div class="cozmoslabs-page-header">
            <div class="cozmoslabs-section-title">

                <h2 class="cozmoslabs-page-title">
                    <?php esc_html_e( 'Import and Export', 'profile-builder' ); ?>
                    <a href="https://www.cozmoslabs.com/docs/profile-builder/add-ons/import-export-pb-settings/?utm_source=wpbackend&utm_medium=pb-documentation&utm_campaign=PBDocs" target="_blank" data-code="f223" class="wppb-docs-link dashicons dashicons-editor-help" style="margin-left: 5px;"></a>
                </h2>

            </div>
        </div>

		<?php
		if( isset ( $_GET['tab'] ) ) wppb_pbie_tabs( sanitize_text_field( $_GET['tab'] ) );
		else wppb_pbie_tabs( 'import' );
		?>

		<form method="post" action="<?php admin_url( 'admin.php?page=pbie-import-export' ); ?>" enctype= "multipart/form-data">
			<?php
			if( $pagenow == 'admin.php' && isset( $_GET['page'] ) && $_GET['page'] === 'pbie-import-export' ) {
				if( isset ( $_GET['tab'] ) ) $tab = sanitize_text_field( $_GET['tab'] );
				else $tab = 'import';

				switch ( $tab ) {
					case 'export' :
						wppb_pbie_export();
						break;
					case 'import' :
						wppb_pbie_import();
						break;
				}
			}
			?>
		</form>
	</div>
<?php
}
