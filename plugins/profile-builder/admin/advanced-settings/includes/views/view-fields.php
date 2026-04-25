<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<?php
    $settings = get_option( 'wppb_toolbox_fields_settings' );
?>

<form method="post" action="options.php">

    <?php settings_fields( 'wppb_toolbox_fields_settings' ); ?>

    <div class="cozmoslabs-settings-container">

        <div class="cozmoslabs-form-subsection-wrapper cozmoslabs-settings cozmoslabs-no-title-section">
            <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                <label class="cozmoslabs-form-field-label" for="toolbox-automatically-generate-password"><?php esc_html_e('Auto Generate Password', 'profile-builder'); ?></label>

                <div class="cozmoslabs-toggle-container">
                    <input type="checkbox" id="toolbox-automatically-generate-password" name="wppb_toolbox_fields_settings[automatically-generate-password]"<?php echo ( ( isset( $settings['automatically-generate-password'] ) && ( $settings['automatically-generate-password'] == 'yes' ) ) ? ' checked' : '' ); ?> value="yes">
                    <label class="cozmoslabs-toggle-track" for="toolbox-automatically-generate-password"></label>
                </div>

                <div class="cozmoslabs-toggle-description">
                    <label for="toolbox-automatically-generate-password" class="cozmoslabs-description"><?php esc_html_e( 'Automatically generate password for users.', 'profile-builder' ); ?></label>
                </div>

                <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'By enabling this option, the password will be automatically generated and emailed to the user.', 'profile-builder' ); ?></p>
                <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'In order for this functionality to work you need to add the {{password}} merge tag to the default user registration email.', 'profile-builder' ); ?></p>
            </div>

            <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                <label class="cozmoslabs-form-field-label" for="toolbox-send-credentials-hide"><?php esc_html_e('Send Credentials Checkbox', 'profile-builder'); ?></label>

                <div class="cozmoslabs-toggle-container">
                    <input type="checkbox" id="toolbox-send-credentials-hide" name="wppb_toolbox_fields_settings[send-credentials-hide]"<?php echo ( ( isset( $settings['send-credentials-hide'] ) && ( $settings['send-credentials-hide'] == 'yes' ) ) ? ' checked' : '' ); ?> value="yes">
                    <label class="cozmoslabs-toggle-track" for="toolbox-send-credentials-hide"></label>
                </div>

                <div class="cozmoslabs-toggle-description">
                    <label for="toolbox-send-credentials-hide" class="cozmoslabs-description"><?php esc_html_e( 'Hidden and checked "Send Credentials" checkbox.', 'profile-builder' ); ?></label>
                </div>

                <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'By default, the user needs to choose if he wants to receive a registration email.', 'profile-builder' ); ?></p>
                <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'By enabling this option, the field won\'t be shown and the message will always be sent to the user.', 'profile-builder' ); ?></p>
            </div>

            <div class="cozmoslabs-form-field-wrapper" id="wppb-toolbox-send-credentials-text">
                <label class="cozmoslabs-form-field-label" for="toolbox-send-credentials-text"><?php esc_html_e('Send Credentials Text', 'profile-builder'); ?></label>
                <input type="text" id="toolbox-send-credentials-text" name="wppb_toolbox_fields_settings[send-credentials-text]" value="<?php echo ( !empty( $settings['send-credentials-text']) ? esc_attr( $settings['send-credentials-text'] ) : '' ); ?>">

                <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'You can modify the default "Send Credentials" field text by entering something in the field above.', 'profile-builder' ); ?></p>
            </div>

            <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                <label class="cozmoslabs-form-field-label" for="toolbox-redirect-if-empty-required"><?php esc_html_e('Empty Required Fields Redirect', 'profile-builder'); ?></label>

                <div class="cozmoslabs-toggle-container">
                    <input type="checkbox" id="toolbox-redirect-if-empty-required" name="wppb_toolbox_fields_settings[redirect-if-empty-required]"<?php echo ( ( isset( $settings['redirect-if-empty-required'] ) && ( $settings['redirect-if-empty-required'] == 'yes' ) ) ? ' checked' : '' ); ?> value="yes">
                    <label class="cozmoslabs-toggle-track" for="toolbox-redirect-if-empty-required"></label>
                </div>

                <div class="cozmoslabs-toggle-description">
                    <label for="toolbox-redirect-if-empty-required" class="cozmoslabs-description"><?php esc_html_e( 'Redirect users to a page if they have empty required fields.', 'profile-builder' ); ?></label>
                </div>

                <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'By enabling this option, logged in users which have empty required fields on their profile will be redirected to a selected page.', 'profile-builder' ); ?></p>
            </div>

            <div class="cozmoslabs-form-field-wrapper" id="wppb-toolbox-redirect-if-empty-required-url">
                <label class="cozmoslabs-form-field-label" for="toolbox-redirect-if-empty-required-url"><?php esc_html_e('Redirect Page', 'profile-builder'); ?></label>

                <select id="toolbox-redirect-if-empty-required-url" name="wppb_toolbox_fields_settings[redirect-if-empty-required-url]">
                    <option value="-1"><?php esc_html_e( 'Choose...', 'profile-builder' ) ?></option>

                    <?php
                    foreach( get_pages() as $page )
                        echo '<option value="' . esc_attr( $page->ID ) . '"' . ( isset( $settings['redirect-if-empty-required-url'] ) ? selected( $settings['redirect-if-empty-required-url'], $page->ID, false ) : '') . '>' . esc_html( $page->post_title ) . ' ( ID: ' . esc_attr( $page->ID ) . ')' . '</option>';
                    ?>
                </select>

                <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'Select the page where you want to redirect the users which have empty required fields on their profile.', 'profile-builder' ); ?></p>
                <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'For example, you can redirect these users to the Edit Profile form so they can add the missing info.', 'profile-builder' ); ?></p>
                <p class="cozmoslabs-description cozmoslabs-description-space-left"><strong><?php esc_html_e( 'NOTE:', 'profile-builder' ); ?></strong> <?php esc_html_e( 'This option will not work if you have conditional logic implemented in the form.', 'profile-builder' ); ?></p>
            </div>

            <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                <label class="cozmoslabs-form-field-label" for="toolbox-restricted-words"><?php esc_html_e('Restrict Words', 'profile-builder'); ?></label>

                <div class="cozmoslabs-toggle-container">
                    <input type="checkbox" id="toolbox-restricted-words" name="wppb_toolbox_fields_settings[restricted-words]"<?php echo ( ( isset( $settings['restricted-words'] ) && ( $settings['restricted-words'] == 'on' ) ) ? ' checked' : '' ); ?> value="on" class="wppb-toolbox-switch">
                    <label class="cozmoslabs-toggle-track" for="toolbox-restricted-words"></label>
                </div>

                <div class="cozmoslabs-toggle-description">
                    <label for="toolbox-restricted-words" class="cozmoslabs-description"><?php esc_html_e( 'Restrict certain words from being used in fields.', 'profile-builder' ); ?></label>
                </div>

                <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'Allows you to define some words which users cannot use in their Username, First Name or Last Name when registering.', 'profile-builder' ); ?></p>
            </div>

            <div class="cozmoslabs-form-field-wrapper cozmoslabs-checkbox-list-wrapper wppb-toolbox-accordion">
                <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Affected fields', 'profile-builder' ); ?></label>

                <div class="cozmoslabs-checkbox-list cozmoslabs-checkbox-4-col-list">
                    <div class="cozmoslabs-chckbox-container">
                        <input type="checkbox" id="toolbox-ban-username-words" name="wppb_toolbox_fields_settings[restricted-words-fields][]"<?php echo ( ( isset( $settings['restricted-words-fields'] ) && in_array( 'username', $settings['restricted-words-fields'] ) ) ? ' checked' : '' ); ?> value="username">
                        <label class="pms-meta-box-checkbox-label" for="toolbox-ban-username-words"><?php esc_html_e( 'Username', 'profile-builder' ); ?></label>
                    </div>

                    <div class="cozmoslabs-chckbox-container">
                        <input type="checkbox" id="toolbox-ban-first-name-words" name="wppb_toolbox_fields_settings[restricted-words-fields][]"<?php echo ( ( isset( $settings['restricted-words-fields'] ) && in_array( 'first-name', $settings['restricted-words-fields'] ) ) ? ' checked' : '' ); ?> value="first-name">
                        <label class="pms-meta-box-checkbox-label" for="toolbox-ban-first-name-words"><?php esc_html_e( 'First Name', 'profile-builder' ); ?></label>
                    </div>

                    <div class="cozmoslabs-chckbox-container">
                        <input type="checkbox" id="toolbox-ban-last-name-words" name="wppb_toolbox_fields_settings[restricted-words-fields][]"<?php echo ( ( isset( $settings['restricted-words-fields'] ) && in_array( 'last-name', $settings['restricted-words-fields'] ) ) ? ' checked' : '' ); ?> value="last-name">
                        <label class="pms-meta-box-checkbox-label" for="toolbox-ban-last-name-words"><?php esc_html_e( 'Last Name', 'profile-builder' ); ?></label>
                    </div>
                </div>

                <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'Chose which fields will be affected by the "Restrict Words" option.', 'profile-builder' ); ?></p>
            </div>

            <div class="cozmoslabs-form-field-wrapper wppb-toolbox-accordion">
                <label class="cozmoslabs-form-field-label" for="toolbox-restricted-words-data"><?php esc_html_e( 'Restricted Words', 'profile-builder' ); ?></label>

                <select id="toolbox-restricted-words-data" class="wppb-select wppb-select2" name="wppb_toolbox_fields_settings[restricted-words-data][]" multiple="multiple">
                    <?php
                    if ( !empty( $settings['restricted-words-data'] ) ) {
                        foreach( $settings['restricted-words-data'] as $domain ) {
                            echo '<option value="'.esc_attr( $domain ).'" selected>'.esc_html( $domain ).'</option>';

                        }
                    }
                    ?>
                </select>

                <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'Select the words which you want to ban with the "Restrict Words" option.', 'profile-builder' ); ?></p>
            </div>

            <div class="cozmoslabs-form-field-wrapper wppb-toolbox-accordion">
                <label class="cozmoslabs-form-field-label" for="toolbox-restricted-words-message"><?php esc_html_e( 'Word Restriction Message', 'profile-builder' ); ?></label>
                <input type="text" id="toolbox-restricted-words-message" name="wppb_toolbox_fields_settings[restricted-words-message]" value="<?php echo ( !empty( $settings['restricted-words-message']) ? esc_attr( $settings['restricted-words-message'] ) : '' ); ?>">
                <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'Add a message to notify the user when a restricted word is used.', 'profile-builder' ); ?></p>
            </div>

            <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                <label class="cozmoslabs-form-field-label" for="toolbox-unique-display-name"><?php esc_html_e('Unique Display Name', 'profile-builder'); ?></label>

                <div class="cozmoslabs-toggle-container">
                    <input type="checkbox" id="toolbox-unique-display-name" name="wppb_toolbox_fields_settings[unique-display-name]"<?php echo ( ( isset( $settings['unique-display-name'] ) && ( $settings['unique-display-name'] == 'yes' ) ) ? ' checked' : '' ); ?> value="yes">
                    <label class="cozmoslabs-toggle-track" for="toolbox-unique-display-name"></label>
                </div>

                <div class="cozmoslabs-toggle-description">
                    <label for="toolbox-unique-display-name" class="cozmoslabs-description"><?php esc_html_e( 'Unique "Display Name" for users.', 'profile-builder' ); ?></label>
                </div>

                <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'By enabling this option, users will not be able to choose a "Display Name" that is already used by another account.', 'profile-builder' ); ?></p>
            </div>

            <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                <label class="cozmoslabs-form-field-label" for="toolbox-capitalize-first-last"><?php esc_html_e('Capitalize Name Fields', 'profile-builder'); ?></label>

                <div class="cozmoslabs-toggle-container">
                    <input type="checkbox" id="toolbox-capitalize-first-last" name="wppb_toolbox_fields_settings[capitalize-first-last]"<?php echo ( ( isset( $settings['capitalize-first-last'] ) && ( $settings['capitalize-first-last'] == 'yes' ) ) ? ' checked' : '' ); ?> value="yes">
                    <label class="cozmoslabs-toggle-track" for="toolbox-capitalize-first-last"></label>
                </div>

                <div class="cozmoslabs-toggle-description">
                    <label for="toolbox-capitalize-first-last" class="cozmoslabs-description"><?php esc_html_e( 'Always capitalize "First Name" and "Last Name" default fields.', 'profile-builder' ); ?></label>
                </div>

                <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'If you have these fields in your forms, they will be always saved with the first letter as uppercase.', 'profile-builder' ); ?></p>
                <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'eg.: "John Doe" instead of "john doe"', 'profile-builder' ); ?></p>
            </div>

            <?php if ( defined( 'WPPB_PAID_PLUGIN_DIR' ) && file_exists( WPPB_PAID_PLUGIN_DIR . '/front-end/extra-fields/extra-fields.php' ) ) : ?>
                <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                    <label class="cozmoslabs-form-field-label" for="toolbox-datepicker-starts-monday"><?php esc_html_e('Datepicker Starts Monday', 'profile-builder'); ?></label>

                    <div class="cozmoslabs-toggle-container">
                        <input type="checkbox" id="toolbox-datepicker-starts-monday" name="wppb_toolbox_fields_settings[datepicker-starts-monday]"<?php echo ( ( isset( $settings['datepicker-starts-monday'] ) && ( $settings['datepicker-starts-monday'] == 'yes' ) ) ? ' checked' : '' ); ?> value="yes">
                        <label class="cozmoslabs-toggle-track" for="toolbox-datepicker-starts-monday"></label>
                    </div>

                    <div class="cozmoslabs-toggle-description">
                        <label for="toolbox-datepicker-starts-monday" class="cozmoslabs-description"><?php esc_html_e( 'Make all Datepickers use Monday as the first day of the week.', 'profile-builder' ); ?></label>
                    </div>
                </div>
            <?php endif; ?>

            <?php
            $wppb_module_settings = get_option( 'wppb_module_settings' );

            if ( $wppb_module_settings != false && isset( $wppb_module_settings['wppb_repeaterFields']) && $wppb_module_settings['wppb_repeaterFields'] == 'show' ) :
                ?>
                <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                    <label class="cozmoslabs-form-field-label" for="toolbox-remove-repeater-fields"><?php esc_html_e('Hide Repeater Fields', 'profile-builder'); ?></label>

                    <div class="cozmoslabs-toggle-container">
                        <input type="checkbox" id="toolbox-remove-repeater-fields" name="wppb_toolbox_fields_settings[remove-repeater-fields]"<?php echo ( ( isset( $settings['remove-repeater-fields'] ) && ( $settings['remove-repeater-fields'] == 'yes' ) ) ? ' checked' : '' ); ?> value="yes">
                        <label class="cozmoslabs-toggle-track" for="toolbox-remove-repeater-fields"></label>
                    </div>

                    <div class="cozmoslabs-toggle-description">
                        <label for="toolbox-remove-repeater-fields" class="cozmoslabs-description"><?php esc_html_e( 'Hide Repeater Fields from the back-end Profile page.', 'profile-builder' ); ?></label>
                    </div>

                    <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'Repeater Fields from Profile Builder do not work on the back-end User Profile page, they are just displayed. If you want to remove them completely, you can use this option.', 'profile-builder' ); ?></p>
                    <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'You will still be able to use them from a front-end Edit Profile Form.', 'profile-builder' ); ?></p>
                </div>
            <?php endif; ?>

            <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                <label class="cozmoslabs-form-field-label" for="toolbox-password-visibility-hide"><?php esc_html_e('Password Visibility Toggle', 'profile-builder'); ?></label>

                <div class="cozmoslabs-toggle-container">
                    <input type="checkbox" id="toolbox-password-visibility-hide" name="wppb_toolbox_fields_settings[password-visibility-hide]"<?php echo ( ( isset( $settings['password-visibility-hide'] ) && ( $settings['password-visibility-hide'] == 'yes' ) ) ? ' checked' : '' ); ?> value="yes">
                    <label class="cozmoslabs-toggle-track" for="toolbox-password-visibility-hide"></label>
                </div>

                <div class="cozmoslabs-toggle-description">
                    <label for="toolbox-password-visibility-hide" class="cozmoslabs-description"><?php esc_html_e( 'Show the Password field visibility toggle.', 'profile-builder' ); ?></label>
                </div>

                <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'Enabling this option will show a visibility toggle button for all Password fields..', 'profile-builder' ); ?></p>
            </div>

            <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                <label class="cozmoslabs-form-field-label" for="toolbox-remove-all-fields-from-backend"><?php esc_html_e('Remove Extra Fields', 'profile-builder'); ?></label>

                <div class="cozmoslabs-toggle-container">
                    <input type="checkbox" id="toolbox-remove-all-fields-from-backend" name="wppb_toolbox_fields_settings[remove-all-fields-from-backend]"<?php echo ( ( isset( $settings['remove-all-fields-from-backend'] ) && ( $settings['remove-all-fields-from-backend'] == 'yes' ) ) ? ' checked' : '' ); ?> value="yes">
                    <label class="cozmoslabs-toggle-track" for="toolbox-remove-all-fields-from-backend"></label>
                </div>

                <div class="cozmoslabs-toggle-description">
                    <label for="toolbox-remove-all-fields-from-backend" class="cozmoslabs-description"><?php esc_html_e( 'Remove all Extra Fields from back-end Edit Profile page.', 'profile-builder' ); ?></label>
                </div>

                <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'Enabling this option will remove all Custom Fields from the backend Profile page created with Profile Builder.', 'profile-builder' ); ?></p>
            </div>

            <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                <label class="cozmoslabs-form-field-label" for="toolbox-update-db-meta-keys"><?php esc_html_e('Update Database Entries', 'profile-builder'); ?></label>

                <div class="cozmoslabs-toggle-container">
                    <input type="checkbox" id="toolbox-update-db-meta-keys" name="wppb_toolbox_fields_settings[update-db-meta-keys]"<?php echo ( ( isset( $settings['update-db-meta-keys'] ) && ( $settings['update-db-meta-keys'] == 'yes' ) ) ? ' checked' : '' ); ?> value="yes">
                    <label class="cozmoslabs-toggle-track" for="toolbox-update-db-meta-keys"></label>
                </div>

                <div class="cozmoslabs-toggle-description">
                    <label for="toolbox-update-db-meta-keys" class="cozmoslabs-description"><?php esc_html_e( 'Update database entries when changing meta key.', 'profile-builder' ); ?></label>
                </div>

                <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'Enabling this option, when changing the meta key of a field, existing entries from the database will be updated as well.', 'profile-builder' ); ?></p>
            </div>

            <?php do_action( 'wppb_extra_toolbox_fields_settings', $settings ); ?>

        </div>

        <div class="submit cozmoslabs-submit">
            <h3 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Update Settings', 'profile-builder' ) ?></h3>
            <div class="cozmoslabs-publish-button-group">
                <?php submit_button( __( 'Save Changes', 'profile-builder' ) ); ?>
            </div>
        </div>

    </div>

</form>
