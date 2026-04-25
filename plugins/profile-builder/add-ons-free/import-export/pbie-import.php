<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/* include import class */
require_once 'inc/class-pbie-import.php';

/* Import tab content function */
function wppb_pbie_import() {

	if( isset( $_POST['cozmos-import'] ) && isset( $_POST['wppb_nonce'] ) && wp_verify_nonce( sanitize_text_field( $_POST['wppb_nonce'] ), 'wppb_import_setttings' ) ) {
		if( isset( $_FILES['cozmos-upload'] ) ) {
			$pbie_cpts = array(
				'wppb-ul-cpt',
				'wppb-rf-cpt',
				'wppb-epf-cpt'
			);

			$pbie_json_upload = new WPPB_ImpEx_Import( $pbie_cpts );
			$pbie_json_upload->upload_json_file();
			/* show error/success messages */
			$pbie_messages = $pbie_json_upload->get_messages();
			foreach ( $pbie_messages as $pbie_message ) {
				echo '<div id="message" class=';
					echo esc_attr( $pbie_message['type'] );
				echo '>';
				echo '<p>';
					echo esc_html( $pbie_message['message'] );
				echo '</p>';
				echo '</div>';
			}
		}
	}
	?>
    <div class="cozmoslabs-form-subsection-wrapper">
        <h4 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Import Profile Builder Options', 'profile-builder' ); ?></h4>
        <p class="cozmoslabs-description"><?php esc_html_e( 'This allows you to easily import the configuration from another site.', 'profile-builder' ); ?></p>

        <form name="cozmos-upload" method="post" action="" enctype= "multipart/form-data">
            <input type="hidden" name="wppb_nonce" value="<?php echo esc_attr( wp_create_nonce( 'wppb_import_setttings' ) ); ?>" />

            <div class="cozmoslabs-form-field-wrapper">
                <label class="cozmoslabs-form-field-label"><?php esc_html_e('JSON File', 'profile-builder'); ?></label>
                <input type="file" name="cozmos-upload" value="cozmos-upload" id="cozmos-upload" />
            </div>

            <div class="cozmoslabs-form-field-wrapper">
                <label class="cozmoslabs-form-field-label"><?php esc_html_e('Import Options', 'profile-builder'); ?></label>
                <input class="button-secondary" type="submit" name="cozmos-import" value=<?php esc_html_e( 'Import', 'profile-builder' ); ?> id="cozmos-import" onclick="return confirm( '<?php esc_html_e( 'This will overwrite your old PB settings! Are you sure you want to continue?', 'profile-builder' ); ?>' )" />
                <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'Import Profile Builder options from the JSON file selected above.', 'profile-builder' ); ?></p>
            </div>
        </form>
    </div>
<?php
}