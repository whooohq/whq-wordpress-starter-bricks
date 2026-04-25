<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<?php
    $settings = get_option( 'wppb_toolbox_admin_settings' );
?>

<form method="post" action="options.php">

    <?php settings_fields( 'wppb_toolbox_admin_settings' ); ?>

    <div class="cozmoslabs-settings-container">

        <div class="cozmoslabs-form-subsection-wrapper cozmoslabs-settings cozmoslabs-no-title-section">
            <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                <label class="cozmoslabs-form-field-label" for="toolbox-admin-approval-access"><?php esc_html_e('Admin Approval List', 'profile-builder'); ?></label>

                <div class="cozmoslabs-toggle-container">
                    <input type="checkbox" id="toolbox-admin-approval-access" name="wppb_toolbox_admin_settings[admin-approval-access]"<?php echo ( ( isset( $settings['admin-approval-access'] ) && ( $settings['admin-approval-access'] == 'yes' ) ) ? ' checked' : '' ); ?> value="yes">
                    <label class="cozmoslabs-toggle-track" for="toolbox-admin-approval-access"></label>
                </div>

                <div class="cozmoslabs-toggle-description">
                    <label for="toolbox-admin-approval-access" class="cozmoslabs-description"><?php esc_html_e( 'Allow users with "delete_users" capability to view the Admin Approval list.', 'profile-builder' ); ?></label>
                </div>

                <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'By enabling this option, you will allow users that have the "delete_users" capability to access and use the Admin Approval list.', 'profile-builder' ); ?></p>
            </div>

            <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                <label class="cozmoslabs-form-field-label" for="toolbox-email-confirmation-access"><?php esc_html_e('Unconfirmed Emails List', 'profile-builder'); ?></label>

                <div class="cozmoslabs-toggle-container">
                    <input type="checkbox" id="toolbox-email-confirmation-access" name="wppb_toolbox_admin_settings[email-confirmation-access]"<?php echo ( ( isset( $settings['email-confirmation-access'] ) && ( $settings['email-confirmation-access'] == 'yes' ) ) ? ' checked' : '' ); ?> value="yes">
                    <label class="cozmoslabs-toggle-track" for="toolbox-email-confirmation-access"></label>
                </div>

                <div class="cozmoslabs-toggle-description">
                    <label for="toolbox-email-confirmation-access" class="cozmoslabs-description"><?php esc_html_e( 'Allow users with "delete_users" capability to view the Unconfirmed Emails list.', 'profile-builder' ); ?></label>
                </div>

                <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'By enabling this option, you will allow users that have the "delete_users" capability to see the list of Unconfirmed Email Addresses.', 'profile-builder' ); ?></p>
            </div>

            <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                <label class="cozmoslabs-form-field-label" for="toolbox-admin-approval-confirmation"><?php esc_html_e('Disable Confirmation Dialog', 'profile-builder'); ?></label>

                <div class="cozmoslabs-toggle-container">
                    <input type="checkbox" id="toolbox-admin-approval-confirmation" name="wppb_toolbox_admin_settings[admin-approval-confirmation]"<?php echo ( ( isset( $settings['admin-approval-confirmation'] ) && ( $settings['admin-approval-confirmation'] == 'yes' ) ) ? ' checked' : '' ); ?> value="yes">
                    <label class="cozmoslabs-toggle-track" for="toolbox-admin-approval-confirmation"></label>
                </div>

                <div class="cozmoslabs-toggle-description">
                    <label for="toolbox-admin-approval-confirmation" class="cozmoslabs-description"><?php esc_html_e( 'Disable confirmation dialog for "{{approval_url}}" or "{{approval_link}}" Email Customizer tags.', 'profile-builder' ); ?></label>
                </div>

                <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'By enabling this option, you will disable the confirmation dialog, allowing you to approve new users simply by visiting the "{{approval_url}}" or "{{approval_link}}".', 'profile-builder' ); ?></p>
            </div>

            <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                <!-- don't remove the hidden, we need it so after save there is a value in the database for this, or else it might get set to yes because of the compatibility with Multiple Admin Emails addon -->
                <input type="hidden" name="wppb_toolbox_admin_settings[multiple-admin-emails]" value="">

                <label class="cozmoslabs-form-field-label" for="toolbox-multiple-admin-emails"><?php esc_html_e('Multiple Admin Emails', 'profile-builder'); ?></label>

                <div class="cozmoslabs-toggle-container">
                    <input type="checkbox" id="toolbox-multiple-admin-emails" name="wppb_toolbox_admin_settings[multiple-admin-emails]"<?php echo ( ( isset( $settings['multiple-admin-emails'] ) && ( $settings['multiple-admin-emails'] == 'yes' ) ) ? ' checked' : '' ); ?> value="yes" class="wppb-toolbox-switch">
                    <label class="cozmoslabs-toggle-track" for="toolbox-multiple-admin-emails"></label>
                </div>

                <div class="cozmoslabs-toggle-description">
                    <label for="toolbox-multiple-admin-emails" class="cozmoslabs-description"><?php esc_html_e( 'Enable multiple Admin email addresses.', 'profile-builder' ); ?></label>
                </div>

                <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php  esc_html_e( 'By enabling this option, you can set multiple admin e-mail addresses that will receive e-mail notifications sent by Profile Builder.', 'profile-builder' ); ?></p>
            </div>

            <div class="cozmoslabs-form-field-wrapper wppb-toolbox-accordion">
                <label class="cozmoslabs-form-field-label" for="toolbox-admin-emails"><?php esc_html_e( 'Admin Emails', 'profile-builder' ); ?></label>
                <input class="wppb-text widefat" id="toolbox-admin-emails" type="text" name="wppb_toolbox_admin_settings[admin-emails]" value="<?php echo ( ( isset( $settings['admin-emails'] ) ) ? esc_attr( $settings['admin-emails'] ) : '' ); ?>" />

                <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php  esc_html_e( 'Add email addresses, separated by comma, for people you wish to receive notifications from Profile Builder.', 'profile-builder' ); ?></p>
                <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php echo wp_kses_post( sprintf( __( 'These addresses will overwrite the default Email Address from <a href="%s">Settings -> General</a>.', 'profile-builder' ), esc_url( get_site_url() ) . "/wp-admin/options-general.php" ) ); ?></p>
            </div>

            <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                <label class="cozmoslabs-form-field-label" for="toolbox-multiple-user-roles"><?php esc_html_e('Disable Multiple User Roles', 'profile-builder'); ?></label>

                <div class="cozmoslabs-toggle-container">
                    <input type="checkbox" id="toolbox-multiple-user-roles" name="wppb_toolbox_admin_settings[multiple-user-roles]"<?php echo ( ( isset( $settings['multiple-user-roles'] ) && ( $settings['multiple-user-roles'] == 'yes' ) ) ? ' checked' : '' ); ?> value="yes">
                    <label class="cozmoslabs-toggle-track" for="toolbox-multiple-user-roles"></label>
                </div>

                <div class="cozmoslabs-toggle-description">
                    <label for="toolbox-multiple-user-roles" class="cozmoslabs-description"><?php esc_html_e( 'Disable the ability to select multiple Roles for a user.', 'profile-builder' ); ?></label>
                </div>
            </div>

            <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                <label class="cozmoslabs-form-field-label" for="toolbox-plugin-optin"><?php esc_html_e('Marketing Optin', 'profile-builder'); ?></label>

                <div class="cozmoslabs-toggle-container">
                    <input type="checkbox" id="toolbox-plugin-optin" name="wppb_toolbox_admin_settings[plugin-optin]"<?php echo ( ( isset( $settings['plugin-optin'] ) && ( $settings['plugin-optin'] == 'yes' ) ) ? ' checked' : '' ); ?> value="yes">
                    <label class="cozmoslabs-toggle-track" for="toolbox-plugin-optin"></label>
                </div>

                <div class="cozmoslabs-toggle-description">
                    <label for="toolbox-plugin-optin" class="cozmoslabs-description"><?php esc_html_e( 'Opt in to our security and feature updates notifications, and non-sensitive diagnostic tracking.', 'profile-builder' ); ?></label>
                </div>
            </div>

            <!-- Only show cleanup button if cleanup hasn't been completed -->
            <?php if( !get_option( 'wppb_postmeta_cleanup_completed_v2' ) ) : ?>
                <div class="cozmoslabs-form-field-wrapper">
                    <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Cleanup Postmeta' , 'profile-builder' ) ?></label>

                    <button class="button button-secondary wppb-cleanup-postmeta" data-nonce="<?php echo esc_attr( wp_create_nonce( 'wppb_cleanup_postmeta' ) ); ?>">
                        <?php esc_html_e( 'Cleanup Postmeta', 'profile-builder' ); ?>
                    </button>

                    <p class="cozmoslabs-description cozmoslabs-description-align-right">
                        <?php esc_html_e( 'Use this option to clean up the postmeta table from data that was added unnecessarily. This tool should be used only once so it will disappear after it is used.', 'profile-builder' ); ?>
                    </p>
                </div>
            <?php endif; ?>

            <input type="hidden" name="wppb_toolbox_current_tab" value="admin" />
        </div>

        <div class="submit cozmoslabs-submit">
            <h3 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Update Settings', 'profile-builder' ); ?></h3>
            <div class="cozmoslabs-publish-button-group">
                <?php submit_button( __( 'Save Changes', 'profile-builder' ) ); ?>
            </div>
        </div>

    </div>

</form>
