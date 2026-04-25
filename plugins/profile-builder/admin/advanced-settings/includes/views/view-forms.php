<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<?php
    $settings = get_option( 'wppb_toolbox_forms_settings' );

    if( function_exists( 'wppb_get_active_form_design' ) ){
        $active_design = wppb_get_active_form_design();
    } else {
        $active_design = 'free';
    }

    $wppb_notifications_background_color_success_default = '#DCEDC8';
    $wppb_notifications_background_color_error_default = '#FFCDD2';
    $wppb_notifications_background_color_warning_default = '#FFF9C4';
    $wppb_notifications_background_color_note_default = '#D6F5FF';
    $wppb_notifications_background_color_default = '#F9F9F9';

    if( $active_design === 'form-style-1') {
	    $wppb_primary_color_default = '#1079F3';
	    $wppb_secondary_color_default = '#2D8BF9';
	    $wppb_button_text_color_default = '#FFFFFF';
	    $wppb_label_size_default = '16';
	    $wppb_label_color_default = '#6E7A86';
	    $wppb_notifications_text_color_default = '#090A0B';
	    $wppb_notifications_border_color_success_default = '#689F38';
	    $wppb_notifications_border_color_error_default = '#C62828';
	    $wppb_notifications_border_color_warning_default = '#F9A825';
	    $wppb_notifications_border_color_note_default = '#00A0D2';
        $wppb_progress_bar_fill_color_default = '#1079F3';
        $wppb_progress_bar_background_color_default = '#D6F5FF';
        $wppb_progress_bar_text_color_default = '#333333';
    }
    elseif( $active_design === 'form-style-2') {
	    $wppb_primary_color_default = '#558B2F';
	    $wppb_secondary_color_default = '#4A8124';
	    $wppb_button_text_color_default = '#FFFFFF';
	    $wppb_label_size_default = '16';
	    $wppb_label_color_default = '#6E7A86';
	    $wppb_notifications_text_color_default = '#6E7A86';
	    $wppb_notifications_border_color_success_default = '#558B2F';
	    $wppb_notifications_border_color_error_default = '#C62828';
	    $wppb_notifications_border_color_warning_default = '#F9A825';
	    $wppb_notifications_border_color_note_default = '#00A0D2';
        $wppb_progress_bar_fill_color_default = '#558B2F';
        $wppb_progress_bar_background_color_default = '#DCEDC8';
        $wppb_progress_bar_text_color_default = '#333333';
    }
    elseif( $active_design === 'form-style-3') {
	    $wppb_primary_color_default = '#554FE6';
	    $wppb_secondary_color_default = '#443ECF';
	    $wppb_button_text_color_default = '#FFFFFF';
	    $wppb_label_size_default = '16';
	    $wppb_label_color_default = '#6E7A86';
	    $wppb_notifications_text_color_default = '#6E7A86';
	    $wppb_notifications_border_color_success_default = '#689F38';
	    $wppb_notifications_border_color_error_default = '#C62828';
	    $wppb_notifications_border_color_warning_default = '#F9A825';
	    $wppb_notifications_border_color_note_default = '#00A0D2';
        $wppb_progress_bar_fill_color_default = '#554FE6'; // Primary color
        $wppb_progress_bar_background_color_default = '#F0F2F5';
        $wppb_progress_bar_text_color_default = '#333333';
    }
    else {
	    $wppb_primary_color_default = '';
	    $wppb_secondary_color_default = '';
	    $wppb_button_text_color_default = '';
	    $wppb_label_size_default = '';
	    $wppb_label_color_default = '';
	    $wppb_notifications_text_color_default = '';
	    $wppb_notifications_border_color_success_default = '';
	    $wppb_notifications_border_color_error_default = '';
	    $wppb_notifications_border_color_warning_default = '';
	    $wppb_notifications_border_color_note_default = '';
        $wppb_progress_bar_fill_color_default = '#4cd964';
        $wppb_progress_bar_background_color_default = '#f0f0f0';
        $wppb_progress_bar_text_color_default = '#333333';
    }


?>

<form method="post" action="options.php">

    <?php settings_fields( 'wppb_toolbox_forms_settings' ); ?>
    <div class="cozmoslabs-settings-container">

        <div class="cozmoslabs-form-subsection-wrapper cozmoslabs-settings cozmoslabs-no-title-section">
            <?php if( $active_design !== 'form-style-default' && $active_design != 'free' ): ?>
                <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch color-switcher" data-active-design="<?php echo esc_attr( $active_design ); ?>">
                    <label class="cozmoslabs-form-field-label" for="wppb-color-switcher"><?php esc_html_e('Color Switcher', 'profile-builder'); ?></label>

                    <div class="cozmoslabs-toggle-container">
                        <input type="checkbox" id="wppb-color-switcher" name="wppb_toolbox_forms_settings[color-switcher]"<?php echo ( ( isset( $settings['color-switcher'] ) &&  $settings['color-switcher'] == 'yes' && $active_design !== 'form-style-default' ) ? ' checked' : '' ); ?> value="yes">
                        <label class="cozmoslabs-toggle-track" for="wppb-color-switcher"></label>
                    </div>

                    <div class="cozmoslabs-toggle-description">
                        <label for="wppb-color-switcher" class="cozmoslabs-description"><?php esc_html_e( 'Enable this option to change the colors of the forms.', 'profile-builder' ); ?></label>
                    </div>
                </div>

                <div class="cozmoslabs-form-field-wrapper wppb-color-switcher-section">
                    <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Primary Color', 'profile-builder' ); ?></label>
                    <input type="color" id ="wppb-primary-color" name="wppb_toolbox_forms_settings[primary-color]" class="custom_field_colorpicker" value="<?php echo ( ( isset( $settings['primary-color'] ) &&  !empty( $settings['primary-color'] ) ) ? esc_attr( $settings['primary-color'] ) : esc_attr( $wppb_primary_color_default ) ); ?>"/>
                </div>

                <div class="cozmoslabs-form-field-wrapper wppb-color-switcher-section">
                    <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Secondary Color', 'profile-builder' ); ?></label>
                    <input type="color" id ="wppb-secondary-color" name="wppb_toolbox_forms_settings[secondary-color]" class="custom_field_colorpicker" value="<?php echo ( ( isset( $settings['secondary-color'] ) &&  !empty( $settings['secondary-color'] ) ) ? esc_attr( $settings['secondary-color'] ) : esc_attr( $wppb_secondary_color_default ) ); ?>"/>
                </div>

                <div class="cozmoslabs-form-field-wrapper wppb-color-switcher-section">
                    <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Button Text Color', 'profile-builder' ); ?></label>
                    <input type="color" id ="wppb-button-text-color" name="wppb_toolbox_forms_settings[button-text-color]" class="custom_field_colorpicker" value="<?php echo ( ( isset( $settings['button-text-color'] ) &&  !empty( $settings['button-text-color'] ) ) ? esc_attr( $settings['button-text-color'] ) : esc_attr( $wppb_button_text_color_default ) ); ?>"/>
                </div>

                <div class="cozmoslabs-form-field-wrapper wppb-color-switcher-section">
                    <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Label Size', 'profile-builder' ); ?></label>
                    <input type="text" id ="wppb-label-size" name="wppb_toolbox_forms_settings[label-size]" class="custom_field_colorpicker" value="<?php echo ( ( isset( $settings['label-size'] ) &&  !empty( $settings['label-size'] ) ) ? esc_attr( $settings['label-size'] ) : esc_attr( $wppb_label_size_default ) ); ?>"/>
                </div>

                <div class="cozmoslabs-form-field-wrapper wppb-color-switcher-section">
                    <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Label Color', 'profile-builder' ); ?></label>
                    <input type="color" id ="wppb-label-color" name="wppb_toolbox_forms_settings[label-color]" class="custom_field_colorpicker" value="<?php echo ( ( isset( $settings['label-color'] ) &&  !empty( $settings['label-color'] ) ) ? esc_attr( $settings['label-color'] ) : esc_attr( $wppb_label_color_default ) ); ?>"/>
                </div>

                <div class="cozmoslabs-form-field-wrapper wppb-color-switcher-section">
                    <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Notification Text Color', 'profile-builder' ); ?></label>
                    <input type="color" id ="wppb-notifications-text-color" name="wppb_toolbox_forms_settings[notifications-text-color]" class="custom_field_colorpicker" value="<?php echo ( ( isset( $settings['notifications-text-color'] ) &&  !empty( $settings['notifications-text-color'] ) ) ? esc_attr( $settings['notifications-text-color'] ) : esc_attr( $wppb_notifications_text_color_default ) ); ?>"/>
                </div>

                <div class="cozmoslabs-form-field-wrapper wppb-color-switcher-section" id="wppb-notifications-background-section">
                    <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Notification Background Color', 'profile-builder' ); ?></label><br>
                    <input type="color" id ="wppb-notifications-background-color" name="wppb_toolbox_forms_settings[notifications-background-color]" class="custom_field_colorpicker wppb-other-style-fields" value="<?php echo ( ( isset( $settings['notifications-background-color'] ) &&  !empty( $settings['notifications-background-color'] ) ) ? esc_attr( $settings['notifications-background-color'] ) : esc_attr( $wppb_notifications_background_color_default ) ); ?>"/>
                    <div class="cozmoslabs-form-field-wrapper wppb-form-style-1-fields">
                            <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Success', 'profile-builder' ); ?></label>
                            <input type="color" id ="wppb-notifications-background-color-success" name="wppb_toolbox_forms_settings[notifications-background-color-success]" class="custom_field_colorpicker" value="<?php echo ( ( isset( $settings['notifications-background-color-success'] ) &&  !empty( $settings['notifications-background-color-success'] ) ) ? esc_attr( $settings['notifications-background-color-success'] ) : esc_attr( $wppb_notifications_background_color_success_default ) ); ?>"/>

                            <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Error', 'profile-builder' ); ?></label>
                            <input type="color" id ="wppb-notifications-background-color-error" name="wppb_toolbox_forms_settings[notifications-background-color-error]" class="custom_field_colorpicker" value="<?php echo ( ( isset( $settings['notifications-background-color-error'] ) &&  !empty( $settings['notifications-background-color-error'] ) ) ? esc_attr( $settings['notifications-background-color-error'] ) : esc_attr( $wppb_notifications_background_color_error_default ) ); ?>"/>

                            <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Warning', 'profile-builder' ); ?></label>
                            <input type="color" id ="wppb-notifications-background-color-warning" name="wppb_toolbox_forms_settings[notifications-background-color-warning]" class="custom_field_colorpicker" value="<?php echo ( ( isset( $settings['notifications-background-color-warning'] ) &&  !empty( $settings['notifications-background-color-warning'] ) ) ? esc_attr( $settings['notifications-background-color-warning'] ) : esc_attr( $wppb_notifications_background_color_warning_default ) ); ?>"/>

                            <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Note', 'profile-builder' ); ?></label>
                            <input type="color" id ="wppb-notifications-background-color-note" name="wppb_toolbox_forms_settings[notifications-background-color-note]" class="custom_field_colorpicker" value="<?php echo ( ( isset( $settings['notifications-background-color-note'] ) &&  !empty( $settings['notifications-background-color-note'] ) ) ? esc_attr( $settings['notifications-background-color-note'] ) : esc_attr( $wppb_notifications_background_color_note_default ) ); ?>"/>
                    </div>
                </div>

                <div class="cozmoslabs-form-field-wrapper wppb-color-switcher-section" id="wppb-notifications-border-section">
                    <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Notification Border Color', 'profile-builder' ); ?></label><br>
                    <div class="cozmoslabs-form-field-wrapper">
                        <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Success', 'profile-builder' ); ?></label>
                        <input type="color" id ="wppb-notifications-border-color-success" name="wppb_toolbox_forms_settings[notifications-border-color-success]" class="custom_field_colorpicker" value="<?php echo ( ( isset( $settings['notifications-border-color-success'] ) &&  !empty( $settings['notifications-border-color-success'] ) ) ? esc_attr( $settings['notifications-border-color-success'] ) : esc_attr( $wppb_notifications_border_color_success_default ) ); ?>"/>

                        <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Error', 'profile-builder' ); ?></label>
                        <input type="color" id ="wppb-notifications-border-color-error" name="wppb_toolbox_forms_settings[notifications-border-color-error]" class="custom_field_colorpicker" value="<?php echo ( ( isset( $settings['notifications-border-color-error'] ) &&  !empty( $settings['notifications-border-color-error'] ) ) ? esc_attr( $settings['notifications-border-color-error'] ) : esc_attr( $wppb_notifications_border_color_error_default ) ); ?>"/>

                        <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Warning', 'profile-builder' ); ?></label>
                        <input type="color" id ="wppb-notifications-border-color-warning" name="wppb_toolbox_forms_settings[notifications-border-color-warning]" class="custom_field_colorpicker" value="<?php echo ( ( isset( $settings['notifications-border-color-warning'] ) &&  !empty( $settings['notifications-border-color-warning'] ) ) ? esc_attr( $settings['notifications-border-color-warning'] ) : esc_attr( $wppb_notifications_border_color_warning_default ) ); ?>"/>

                        <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Note', 'profile-builder' ); ?></label>
                        <input type="color" id ="wppb-notifications-border-color-note" name="wppb_toolbox_forms_settings[notifications-border-color-note]" class="custom_field_colorpicker" value="<?php echo ( ( isset( $settings['notifications-border-color-note'] ) &&  !empty( $settings['notifications-border-color-note'] ) ) ? esc_attr( $settings['notifications-border-color-note'] ) : esc_attr( $wppb_notifications_border_color_note_default ) ); ?>"/>
                    </div>
                </div>

                <div class="cozmoslabs-form-field-wrapper wppb-color-switcher-section" id="wppb-progress-bar-section">
                    <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Progress Bar', 'profile-builder' ); ?></label><br>
                    <div class="cozmoslabs-form-field-wrapper">
                        <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Fill Color', 'profile-builder' ); ?></label>
                        <input type="color" id ="wppb-progress-bar-fill-color" name="wppb_toolbox_forms_settings[progress-bar-fill-color]" class="custom_field_colorpicker" value="<?php echo ( ( isset( $settings['progress-bar-fill-color'] ) &&  !empty( $settings['progress-bar-fill-color'] ) ) ? esc_attr( $settings['progress-bar-fill-color'] ) : esc_attr( $wppb_progress_bar_fill_color_default ) ); ?>"/>

                        <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Background Color', 'profile-builder' ); ?></label>
                        <input type="color" id ="wppb-progress-bar-background-color" name="wppb_toolbox_forms_settings[progress-bar-background-color]" class="custom_field_colorpicker" value="<?php echo ( ( isset( $settings['progress-bar-background-color'] ) &&  !empty( $settings['progress-bar-background-color'] ) ) ? esc_attr( $settings['progress-bar-background-color'] ) : esc_attr( $wppb_progress_bar_background_color_default ) ); ?>"/>

                        <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Text Color', 'profile-builder' ); ?></label>
                        <input type="color" id ="wppb-progress-bar-text-color" name="wppb_toolbox_forms_settings[progress-bar-text-color]" class="custom_field_colorpicker" value="<?php echo ( ( isset( $settings['progress-bar-text-color'] ) &&  !empty( $settings['progress-bar-text-color'] ) ) ? esc_attr( $settings['progress-bar-text-color'] ) : esc_attr( $wppb_progress_bar_text_color_default ) ); ?>"/>
                    </div>
                </div>

                <div class="cozmoslabs-form-field-wrapper wppb-color-switcher-section">
                    <button type="button" class="button reset button-secondary" id="reset-">
                        <?php esc_html_e( 'Reset Data', 'profile-builder' ); ?>
                    </button>
                </div>
            <?php endif; ?>

            <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                <input type="hidden" name="wppb_toolbox_forms_settings[placeholder-labels]" value="">

                <label class="cozmoslabs-form-field-label" for="placeholder-labels-enable"><?php esc_html_e('Placeholder Labels', 'profile-builder'); ?></label>

                <div class="cozmoslabs-toggle-container">
                    <input type="checkbox" id="placeholder-labels-enable" name="wppb_toolbox_forms_settings[placeholder-labels]"<?php echo ( ( isset( $settings['placeholder-labels'] ) && ( $settings['placeholder-labels'] == 'yes' ) ) ? ' checked' : '' ); ?> value="yes">
                    <label class="cozmoslabs-toggle-track" for="placeholder-labels-enable"></label>
                </div>

                <div class="cozmoslabs-toggle-description">
                    <label for="placeholder-labels-enable" class="cozmoslabs-description"><?php esc_html_e( 'Replace Labels with Placeholders in Profile Builder forms.', 'profile-builder' ); ?></label>
                </div>
            </div>

            <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                <label class="cozmoslabs-form-field-label" for="restricted-email-domains-enable"><?php esc_html_e('Email Domains Registering', 'profile-builder'); ?></label>

                <div class="cozmoslabs-toggle-container">
                    <input type="checkbox" id="restricted-email-domains-enable" name="wppb_toolbox_forms_settings[restricted-email-domains]"<?php echo ( ( isset( $settings['restricted-email-domains'] ) && ( $settings['restricted-email-domains'] == 'on' ) ) ? ' checked' : '' ); ?> value="on" class="wppb-toolbox-switch">
                    <label class="cozmoslabs-toggle-track" for="restricted-email-domains-enable"></label>
                </div>

                <div class="cozmoslabs-toggle-description">
                    <label for="restricted-email-domains-enable" class="cozmoslabs-description"><?php esc_html_e( 'By enabling this option you can allow or deny email domains from registering.', 'profile-builder' ); ?></label>
                </div>
            </div>


            <div class="cozmoslabs-form-field-wrapper wppb-toolbox-accordion">
                <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Registering Type', 'profile-builder' ); ?></label>

                <div class="cozmoslabs-radio-inputs-row">
                    <label>
                        <input type="radio" name="wppb_toolbox_forms_settings[restricted-email-domains-type]"<?php echo ( ( isset( $settings['restricted-email-domains-type'] ) && ( $settings['restricted-email-domains-type'] == 'allow' ) ) ? ' checked' : '' ); ?> value="allow">
                        <?php esc_html_e( 'Allow', 'profile-builder' ); ?>
                    </label>

                    <label>
                        <input type="radio" name="wppb_toolbox_forms_settings[restricted-email-domains-type]"<?php echo ( ( isset( $settings['restricted-email-domains-type'] ) && ( $settings['restricted-email-domains-type'] == 'deny' ) ) ? ' checked' : '' ); ?> value="deny">
                        <?php esc_html_e( 'Deny', 'profile-builder' ); ?>
                    </label>
                </div>

                <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'Choose rather to allow or deny Email Domains from registering.', 'profile-builder' ); ?></p>
            </div>

            <div class="cozmoslabs-form-field-wrapper wppb-toolbox-accordion">
                <label class="cozmoslabs-form-field-label" for="toolbox-restricted-emails"><?php esc_html_e('Restricted Domains', 'profile-builder'); ?></label>

                <select id="toolbox-restricted-emails" class="wppb-select" name="wppb_toolbox_forms_settings[restricted-email-domains-data][]" multiple="multiple">

                    <?php
                    if ( !empty( $settings['restricted-email-domains-data'] ) ) {
                        foreach( $settings['restricted-email-domains-data'] as $domain ) {
                            echo '<option value="'.esc_attr( $domain ).'" selected>'.esc_html( $domain ).'</option>';
                        }
                    }
                    ?>

                </select>

                <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'You should add only the domain in the list from above. eg.: gmail.com.', 'profile-builder' ); ?></p>
            </div>

            <div class="cozmoslabs-form-field-wrapper wppb-toolbox-accordion">
                <label class="cozmoslabs-form-field-label" for="toolbox-restricted-email-domains-message"><?php esc_html_e('Error Message', 'profile-builder'); ?></label>
                <input type="text" id="toolbox-restricted-email-domains-message" name="wppb_toolbox_forms_settings[restricted-email-domains-message]" value="<?php echo ( !empty( $settings['restricted-email-domains-message']) ? esc_attr( $settings['restricted-email-domains-message'] ) : '' ); ?>">
                <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'Add the Email Domain Registering restriction message.', 'profile-builder' ); ?></p>
            </div>





            <?php
            $wppb_module_settings = get_option( 'wppb_module_settings' );

            if ( $wppb_module_settings != false && isset( $wppb_module_settings['wppb_multipleRegistrationForms']) && $wppb_module_settings['wppb_multipleRegistrationForms'] == 'show' ) :
                ?>
                <div class="cozmoslabs-form-field-wrapper">
                    <label class="cozmoslabs-form-field-label" for="toolbox-bypass-ec"><?php esc_html_e('Email Confirmation Bypass Forms', 'profile-builder'); ?></label>

                    <select id="toolbox-bypass-ec" class="wppb-select" name="wppb_toolbox_forms_settings[ec-bypass][]" multiple="multiple">

                        <?php
                        $registration_forms = get_posts( array( 'post_type' => 'wppb-rf-cpt', 'numberposts' => -1 ) );

                        if ( !empty( $registration_forms ) ) {
                            foreach ( $registration_forms as $form ) {
                                $form_slug = trim( Wordpress_Creation_Kit_PB::wck_generate_slug( $form->post_title ) );

                                ?>
                                <option value="<?php echo esc_attr( $form_slug ); ?>" <?php echo ( ( isset( $settings['ec-bypass'] ) && in_array( $form_slug, $settings['ec-bypass'] ) ) ? 'selected' : '' ); ?>>
                                    <?php echo esc_html( $form->post_title ); ?>
                                </option>
                                <?php
                            }
                        }
                        ?>

                    </select>

                    <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'Select the Forms that should bypass Email Confirmation.', 'profile-builder' ); ?></p>
                    <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'Users registering through any of the selected forms will not need to confirm their email address.', 'profile-builder' ); ?></p>
                </div>
            <?php endif; ?>

            <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                <label class="cozmoslabs-form-field-label" for="toolbox-confirm-user-email-change"><?php esc_html_e('Email confirmation', 'profile-builder'); ?></label>

                <div class="cozmoslabs-toggle-container">
                    <input type="checkbox" id="toolbox-confirm-user-email-change" name="wppb_toolbox_forms_settings[confirm-user-email-change]"<?php echo ( ( isset( $settings['confirm-user-email-change'] ) && ( $settings['confirm-user-email-change'] == 'yes' ) ) ? ' checked' : '' ); ?> value="yes">
                    <label class="cozmoslabs-toggle-track" for="toolbox-confirm-user-email-change"></label>
                </div>

                <div class="cozmoslabs-toggle-description">
                    <label for="toolbox-confirm-user-email-change" class="cozmoslabs-description"><?php esc_html_e( 'Enable “Email confirmation” for changing user email address.', 'profile-builder' ); ?></label>
                </div>

                <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'If enabled, an activation email is sent for the new email address.', 'profile-builder' ); ?></p>
            </div>

            <?php if ( defined( 'WPPB_PAID_PLUGIN_DIR' ) && file_exists( WPPB_PAID_PLUGIN_DIR.'/add-ons-advanced/social-connect/index.php' ) ) : ?>
                <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                    <label class="cozmoslabs-form-field-label" for="toolbox-social-connect-bypass-ec"><?php esc_html_e('Bypass Email Confirmation', 'profile-builder'); ?></label>

                    <div class="cozmoslabs-toggle-container">
                        <input type="checkbox" id="toolbox-social-connect-bypass-ec" name="wppb_toolbox_forms_settings[social-connect-bypass-ec]"<?php echo ( ( isset( $settings['social-connect-bypass-ec'] ) && ( $settings['social-connect-bypass-ec'] == 'yes' ) ) ? ' checked' : '' ); ?> value="yes">
                        <label class="cozmoslabs-toggle-track" for="toolbox-social-connect-bypass-ec"></label>
                    </div>

                    <div class="cozmoslabs-toggle-description">
                        <label for="toolbox-social-connect-bypass-ec" class="cozmoslabs-description"><?php esc_html_e( 'Disable Email Confirmation for Social Connect registrations.', 'profile-builder' ); ?></label>
                    </div>

                    <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'Allow users that register through the Social Connect add-on to bypass the Email Confirmation feature.', 'profile-builder' ); ?></p>
                </div>
            <?php endif; ?>

            <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                <label class="cozmoslabs-form-field-label" for="toolbox-remember-me"><?php esc_html_e('Checked Remember Me', 'profile-builder'); ?></label>

                <div class="cozmoslabs-toggle-container">
                    <input type="checkbox" id="toolbox-remember-me" name="wppb_toolbox_forms_settings[remember-me]"<?php echo ( ( isset( $settings['remember-me'] ) && ( $settings['remember-me'] == 'yes' ) ) ? ' checked' : '' ); ?> value="yes">
                    <label class="cozmoslabs-toggle-track" for="toolbox-remember-me"></label>
                </div>

                <div class="cozmoslabs-toggle-description">
                    <label for="toolbox-remember-me" class="cozmoslabs-description"><?php esc_html_e( 'Check the "Remember Me" checkbox on Login forms, by default.', 'profile-builder' ); ?></label>
                </div>
            </div>

            <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                <label class="cozmoslabs-form-field-label" for="toolbox-recover-password-autologin"><?php esc_html_e('Password Reset Auto-Login', 'profile-builder'); ?></label>

                <div class="cozmoslabs-toggle-container">
                    <input type="checkbox" id="toolbox-recover-password-autologin" name="wppb_toolbox_forms_settings[recover-password-autologin]"<?php echo ( ( isset( $settings['recover-password-autologin'] ) && ( $settings['recover-password-autologin'] == 'yes' ) ) ? ' checked' : '' ); ?> value="yes">
                    <label class="cozmoslabs-toggle-track" for="toolbox-recover-password-autologin"></label>
                </div>

                <div class="cozmoslabs-toggle-description">
                    <label for="toolbox-recover-password-autologin" class="cozmoslabs-description"><?php esc_html_e( 'Automatically log in users after they reset their password using the Recover Password form.', 'profile-builder' ); ?></label>
                </div>
            </div>

            <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                <label class="cozmoslabs-form-field-label" for="toolbox-back-end-validation"><?php esc_html_e('Remove Profile Page Validation', 'profile-builder'); ?></label>

                <div class="cozmoslabs-toggle-container">
                    <input type="checkbox" id="toolbox-back-end-validation" name="wppb_toolbox_forms_settings[back-end-validation]"<?php echo ( ( isset( $settings['back-end-validation'] ) && ( $settings['back-end-validation'] == 'yes' ) ) ? ' checked' : '' ); ?> value="yes">
                    <label class="cozmoslabs-toggle-track" for="toolbox-back-end-validation"></label>
                </div>

                <div class="cozmoslabs-toggle-description">
                    <label for="toolbox-back-end-validation" class="cozmoslabs-description"><?php esc_html_e( 'Remove validation from back-end Profile page.', 'profile-builder' ); ?></label>
                </div>
            </div>

            <?php
            $users = count_users();

            if ( $users['total_users'] >= 5000 ) : ?>
                <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                    <label class="cozmoslabs-form-field-label" for="toolbox-edit-other-users-limit"><?php esc_html_e('Edit Users Selector', 'profile-builder'); ?></label>

                    <div class="cozmoslabs-toggle-container">
                        <input type="checkbox" id="toolbox-edit-other-users-limit" name="wppb_toolbox_forms_settings[edit-other-users-limit]"<?php echo ( ( isset( $settings['edit-other-users-limit'] ) && ( $settings['edit-other-users-limit'] == 'yes' ) ) ? ' checked' : '' ); ?> value="yes">
                        <label class="cozmoslabs-toggle-track" for="toolbox-edit-other-users-limit"></label>
                    </div>

                    <div class="cozmoslabs-toggle-description">
                        <p class="cozmoslabs-description"><?php esc_html_e( 'Always show edit other users dropdown.', 'profile-builder' ); ?></p>
                    </div>

                    <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'For perfomance reasons, we disable the Select if you have more than 5000 users on your website.', 'profile-builder' ); ?></p>
                </div>
            <?php endif; ?>

            <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                <label class="cozmoslabs-form-field-label" for="toolbox-users-can-register"><?php esc_html_e('Anyone can Register', 'profile-builder'); ?></label>

                <div class="cozmoslabs-toggle-container">
                    <input type="checkbox" id="toolbox-users-can-register" name="wppb_toolbox_forms_settings[users-can-register]"<?php echo ( ( isset( $settings['users-can-register'] ) && ( $settings['users-can-register'] == 'yes' ) ) ? ' checked' : '' ); ?> value="yes">
                    <label class="cozmoslabs-toggle-track" for="toolbox-users-can-register"></label>
                </div>

                <div class="cozmoslabs-toggle-description">
                    <label for="toolbox-users-can-register" class="cozmoslabs-description"><?php esc_html_e( 'Consider "Anyone can Register" WordPress option.', 'profile-builder' ); ?></label>
                </div>

                <p class="cozmoslabs-description cozmoslabs-description-space-left">
                    <?php
                    echo wp_kses_post( sprintf( __( 'By default, Profile Builder ignores this %1$s. By enabling this option, our Registration Form will consider it.', 'profile-builder' ), '<a href="'. esc_url( admin_url( 'options-general.php' ) ) .'" target="_blank">' . esc_html__( 'setting', 'profile-builder' ) . '</a>' ) );
                    ?>
                </p>
            </div>

            <div class="cozmoslabs-form-field-wrapper">
                <label class="cozmoslabs-form-field-label" for="toolbox-redirect-delay-timer"><?php esc_html_e('Redirect Delay Timer', 'profile-builder'); ?></label>
                <input type="text" id="toolbox-redirect-delay-timer" name="wppb_toolbox_forms_settings[redirect-delay-timer]" value="<?php echo ( ( !empty( $settings['redirect-delay-timer'] ) || ( isset( $settings['redirect-delay-timer'] ) && $settings['redirect-delay-timer'] == 0 ) ) ? esc_attr( $settings['redirect-delay-timer'] ) : '' ); ?>">
                <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'This allows you to change the amount of seconds it takes for the "After Registration" redirect to happen.', 'profile-builder' ); ?></p>
                <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'The default is 3 seconds. Leave empty if you do not want to change it.', 'profile-builder' ); ?></p>
            </div>

            <?php if ( defined( 'WPPB_PAID_PLUGIN_DIR' ) && file_exists( WPPB_PAID_PLUGIN_DIR.'/features/admin-approval/admin-approval.php' ) ) : ?>
                <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                    <label class="cozmoslabs-form-field-label" for="toolbox-save-admin-approval-status"><?php esc_html_e('Admin Approval Status Usermeta', 'profile-builder'); ?></label>

                    <div class="cozmoslabs-toggle-container">
                        <input type="checkbox" id="toolbox-save-admin-approval-status" name="wppb_toolbox_forms_settings[save-admin-approval-status]"<?php echo ( ( isset( $settings['save-admin-approval-status'] ) && ( $settings['save-admin-approval-status'] == 'yes' ) ) ? ' checked' : '' ); ?> value="yes">
                        <label class="cozmoslabs-toggle-track" for="toolbox-save-admin-approval-status"></label>
                    </div>

                    <div class="cozmoslabs-toggle-description">
                        <label for="toolbox-save-admin-approval-status" class="cozmoslabs-description"><?php esc_html_e( 'Save Admin Approval status in usermeta.', 'profile-builder' ); ?></label>
                    </div>

                    <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'By default, the Admin Approval status is saved as a custom taxonomy that is attached to the user.', 'profile-builder' ); ?></p>
                    <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'By enabling this option, the status will also be saved in the "*_usermeta" table under the "wppb_approval_status" meta name.', 'profile-builder' ); ?></p>
                </div>
            <?php endif; ?>

            <?php if ( defined( 'WPPB_PAID_PLUGIN_DIR' ) && file_exists( WPPB_PAID_PLUGIN_DIR.'/features/admin-approval/admin-approval.php' ) ) : ?>
                <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                    <label class="cozmoslabs-form-field-label" for="toolbox-redirect-author-page"><?php esc_html_e('Redirect Unapproved Users', 'profile-builder'); ?></label>

                    <div class="cozmoslabs-toggle-container">
                        <input type="checkbox" id="toolbox-redirect-author-page" name="wppb_toolbox_forms_settings[redirect-author-page]"<?php echo ( ( isset( $settings['redirect-author-page'] ) && ( $settings['redirect-author-page'] == 'yes' ) ) ? ' checked' : '' ); ?> value="yes">
                        <label class="cozmoslabs-toggle-track" for="toolbox-redirect-author-page"></label>
                    </div>

                    <div class="cozmoslabs-toggle-description">
                        <label for="toolbox-redirect-author-page" class="cozmoslabs-description"><?php esc_html_e( 'Redirect "/author" page if user is not approved.', 'profile-builder' ); ?></label>
                    </div>

                    <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'By default, users placed in Admin Approval will not be able to login, but the Author pages will be accessible.', 'profile-builder' ); ?></p>
                    <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'Using this option you can redirect these pages, sending users who try to access them to your home page.', 'profile-builder' ); ?></p>
                </div>
            <?php endif; ?>

            <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                <label class="cozmoslabs-form-field-label" for="toolbox-save-last-login"><?php esc_html_e('Last Login Usermeta', 'profile-builder'); ?></label>

                <div class="cozmoslabs-toggle-container">
                    <input type="checkbox" id="toolbox-save-last-login" name="wppb_toolbox_forms_settings[save-last-login]"<?php echo ( ( isset( $settings['save-last-login'] ) && ( $settings['save-last-login'] == 'yes' ) ) ? ' checked' : '' ); ?> value="yes">
                    <label class="cozmoslabs-toggle-track" for="toolbox-save-last-login"></label>
                </div>

                <div class="cozmoslabs-toggle-description">
                    <label for="toolbox-save-last-login" class="cozmoslabs-description"><?php esc_html_e( 'Save "Last Login" date in usermeta.', 'profile-builder' ); ?></label>
                </div>

                <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'By enabling this option, each time a user logins, the date and time will be saved in the database under the "last_login_date" meta name.', 'profile-builder' ); ?></p>
                <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php echo wp_kses_post( __( 'You can <a href="https://www.cozmoslabs.com/docs/profile-builder/manage-user-fields/?utm_source=pb-advanced-settings&utm_medium=client-site&utm_campaign=pb-last-login#Manage_existing_custom_fields_with_Profile_Builder" target="_blank">create a field with this meta name</a> to display it in the Userlisting or Edit Profile forms.', 'profile-builder' ) ); ?></p>
            </div>

            <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                <label class="cozmoslabs-form-field-label" for="toolbox-save-last-profile-update"><?php esc_html_e('Last Profile Update Usermeta', 'profile-builder'); ?></label>

                <div class="cozmoslabs-toggle-container">
                    <input type="checkbox" id="toolbox-save-last-profile-update" name="wppb_toolbox_forms_settings[save-last-profile-update]"<?php echo ( ( isset( $settings['save-last-profile-update'] ) && ( $settings['save-last-profile-update'] == 'yes' ) ) ? ' checked' : '' ); ?> value="yes">
                    <label class="cozmoslabs-toggle-track" for="toolbox-save-last-profile-update"></label>
                </div>

                <div class="cozmoslabs-toggle-description">
                    <label for="toolbox-save-last-profile-update" class="cozmoslabs-description"><?php esc_html_e( 'Save "Last Profile Update" date in usermeta.', 'profile-builder' ); ?></label>
                </div>

                <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'By enabling this option, each time a user modifies his profile the date and time will be saved in the database under the "last_profile_update_date" meta name.', 'profile-builder' ); ?></p>
                <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php echo wp_kses_post( __( 'You can <a href="https://www.cozmoslabs.com/docs/profile-builder/manage-user-fields/?utm_source=pb-advanced-settings&utm_medium=client-site&utm_campaign=pb-last-profile-update#Manage_existing_custom_fields_with_Profile_Builder" target="_blank">create a field with this meta name</a> to display it in the Userlisting or Edit Profile forms.', 'profile-builder' ) ); ?></p>
            </div>

            <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                <label class="cozmoslabs-form-field-label" for="toolbox-disable-automatic-scrolling"><?php esc_html_e('Disable Automatic Scrolling', 'profile-builder'); ?></label>

                <div class="cozmoslabs-toggle-container">
                    <input type="checkbox" id="toolbox-disable-automatic-scrolling" name="wppb_toolbox_forms_settings[disable-automatic-scrolling]"<?php echo ( ( isset( $settings['disable-automatic-scrolling'] ) && ( $settings['disable-automatic-scrolling'] == 'yes' ) ) ? ' checked' : '' );?> value="yes">
                    <label class="cozmoslabs-toggle-track" for="toolbox-disable-automatic-scrolling"></label>
                </div>

                <div class="cozmoslabs-toggle-description">
                    <label for="toolbox-disable-automatic-scrolling" class="cozmoslabs-description"><?php esc_html_e( 'Disable automatic scrolling after submit.', 'profile-builder' ); ?></label>
                </div>

                <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'By default, after each form submission the page will automatically scroll to the form message. By enabling this option, automatic scrolling will be disabled.', 'profile-builder' ); ?></p>
            </div>

            <?php if( wppb_conditional_fields_exists() ): ?>
                <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                    <label class="cozmoslabs-form-field-label" for="toolbox-ajax-conditional-logic"><?php esc_html_e('Conditional Fields Ajax', 'profile-builder'); ?></label>

                    <div class="cozmoslabs-toggle-container">
                        <input type="checkbox" id="toolbox-ajax-conditional-logic" name="wppb_toolbox_forms_settings[ajax-conditional-logic]"<?php echo ( ( isset( $settings['ajax-conditional-logic'] ) && ( $settings['ajax-conditional-logic'] == 'yes' ) ) ? ' checked' : '' );?> value="yes">
                        <label class="cozmoslabs-toggle-track" for="toolbox-ajax-conditional-logic"></label>
                    </div>

                    <div class="cozmoslabs-toggle-description">
                        <label for="toolbox-ajax-conditional-logic" class="cozmoslabs-description"><?php esc_html_e( 'Use Ajax on conditional fields.', 'profile-builder' ); ?></label>
                    </div>

                    <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'For large conditional forms. Enable option for improved page performance.', 'profile-builder' ); ?></p>
                </div>
            <?php endif; ?>

            <input type="hidden" name="wppb_toolbox_current_tab" value="forms" />

        </div>

        <div class="submit cozmoslabs-submit">
            <h3 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Update Settings', 'profile-builder' ) ?></h3>
            <div class="cozmoslabs-publish-button-group">
                <?php submit_button( __( 'Save Changes', 'profile-builder' ) ); ?>
            </div>
        </div>

    </div>

</form>

<?php if( $active_design !== 'form-style-default' && $active_design != 'free' ): ?>
    <div id="modal-reset" class="wppb-ul-color-switcher-reset-modal" title="Reset Colors">
        <form method="post" id="reset-content-data-colors">
            <input type="hidden" value="color_switcher_reset_data" name="action">
            <div class="wppb-reset-modal-content">
                <p class="wppb-options-message"><?php esc_html_e('Select which settings you want to reset:', 'profile-builder'); ?></p>
                <div class="wppb-reset-options">
                    <div class="wppb-options-wrapper cozmoslabs-checkbox-list" id="color-options">
                        <label for="wppb-settings-primary-color"><input type="checkbox" name="reset_primary_color" id="wppb-settings-primary-color" value="<?php echo esc_attr( $wppb_primary_color_default ) ?>"><?php esc_html_e('Primary Color', 'profile-builder'); ?></label>
                        <label for="wppb-settings-secondary-color"><input type="checkbox" name="reset_secondary_color" id="wppb-settings-secondary-color" value="<?php echo esc_attr( $wppb_secondary_color_default ) ?>"><?php esc_html_e('Secondary Color', 'profile-builder'); ?></label>
                        <label for="wppb-settings-button-text-color"><input type="checkbox" name="reset_button_text_color" id="wppb-settings-button-text-color" value="<?php echo esc_attr( $wppb_button_text_color_default ) ?>"><?php esc_html_e('Button Text Color', 'profile-builder'); ?></label>
                        <label for="wppb-settings-label-size"><input type="checkbox" name="reset_label_size" id="wppb-settings-label-size" value="<?php echo esc_attr( $wppb_label_size_default ) ?>"><?php esc_html_e('Label Size', 'profile-builder'); ?></label>
                        <label for="wppb-settings-label-color"><input type="checkbox" name="reset_label_color" id="wppb-settings-label-color" value="<?php echo esc_attr( $wppb_label_color_default ) ?>"><?php esc_html_e('Label Color', 'profile-builder'); ?></label>
                        <label for="wppb-settings-notifications-text-color"><input type="checkbox" name="reset_notifications_text_color" id="wppb-settings-notifications-text-color" value="<?php echo esc_attr( $wppb_notifications_text_color_default ) ?>"><?php esc_html_e('Notification Text Color', 'profile-builder'); ?></label>
                        <label class="wppb-form-style-1-fields" for="wppb-settings-notifications-background-color-success"><input type="checkbox" name="reset_notifications_background_color_success" id="wppb-settings-notifications-background-color-success" value="<?php echo esc_attr( $wppb_notifications_background_color_success_default ) ?>"><?php esc_html_e('Notification Background Color - Success', 'profile-builder'); ?></label>
                        <label class="wppb-form-style-1-fields" for="wppb-settings-notifications-background-color-error"><input type="checkbox" name="reset_notifications_background_color_error" id="wppb-settings-notifications-background-color-error" value="<?php echo esc_attr( $wppb_notifications_background_color_error_default ) ?>"><?php esc_html_e('Notification Background Color - Error', 'profile-builder'); ?></label>
                        <label class="wppb-form-style-1-fields" for="wppb-settings-notifications-background-color-warning"><input type="checkbox" name="reset_notifications_background_color_warning" id="wppb-settings-notifications-background-color-warning" value="<?php echo esc_attr( $wppb_notifications_background_color_warning_default ) ?>"><?php esc_html_e('Notification Background Color - Warning', 'profile-builder'); ?></label>
                        <label class="wppb-form-style-1-fields" for="wppb-settings-notifications-background-color-note"><input type="checkbox" name="reset_notifications_background_color_note" id="wppb-settings-notifications-background-color-note" value="<?php echo esc_attr( $wppb_notifications_background_color_note_default ) ?>"><?php esc_html_e('Notification Background Color - Note', 'profile-builder'); ?></label>
                        <label class="wppb-other-style-fields" for="wppb-settings-notifications-background-color"><input type="checkbox" name="reset_notifications_background_color" id="wppb-settings-notifications-background-color" value="<?php echo esc_attr( $wppb_notifications_background_color_default ) ?>"><?php esc_html_e('Notification Background Color', 'profile-builder'); ?></label>
                        <label for="wppb-settings-notifications-border-color-success"><input type="checkbox" name="reset_notifications_border_color_success" id="wppb-settings-notifications-border-color-success" value="<?php echo esc_attr( $wppb_notifications_border_color_success_default ) ?>"><?php esc_html_e('Notification Border Color - Success', 'profile-builder'); ?></label>
                        <label for="wppb-settings-notifications-border-color-error"><input type="checkbox" name="reset_notifications_border_color_error" id="wppb-settings-notifications-border-color-error" value="<?php echo esc_attr( $wppb_notifications_border_color_error_default ) ?>"><?php esc_html_e('Notification Border Color - Error', 'profile-builder'); ?></label>
                        <label for="wppb-settings-notifications-border-color-warning"><input type="checkbox" name="reset_notifications_border_color_warning" id="wppb-settings-notifications-border-color-warning" value="<?php echo esc_attr( $wppb_notifications_border_color_warning_default ) ?>"><?php esc_html_e('Notification Border Color - Warning', 'profile-builder'); ?></label>
                        <label for="wppb-settings-notifications-border-color-note"><input type="checkbox" name="reset_notifications_border_color_note" id="wppb-settings-notifications-border-color-note" value="<?php echo esc_attr( $wppb_notifications_border_color_note_default ) ?>"><?php esc_html_e('Notification Border Color - Note', 'profile-builder'); ?></label>
                        <label for="wppb-settings-progress-bar-fill-color"><input type="checkbox" name="reset_progress_bar_fill_color" id="wppb-settings-progress_bar_fill_color" value="<?php echo esc_attr( $wppb_progress_bar_fill_color_default ) ?>"><?php esc_html_e('Progress Bar Fill Color', 'profile-builder'); ?></label>
                        <label for="wppb-settings-progress-bar-background-color"><input type="checkbox" name="reset_progress_bar_background_color" id="wppb-settings-progress_bar_background_color" value="<?php echo esc_attr( $wppb_progress_bar_background_color_default ) ?>"><?php esc_html_e('Progress Bar Background Color', 'profile-builder'); ?></label>
                        <label for="wppb-settings-progress-bar-text-color"><input type="checkbox" name="reset_progress_bar_text_color" id="wppb-settings-progress_bar_text_color" value="<?php echo esc_attr( $wppb_progress_bar_text_color_default ) ?>"><?php esc_html_e('Progress Bar Text Color', 'profile-builder'); ?></label>

                        <label for="wppb-settings-all-colors"><input type="checkbox" name="reset_all_colors" id="wppb-settings-all-colors" value="all"><?php esc_html_e('Reset All Color Settings', 'profile-builder'); ?></label>
                    </div>
                </div>
                <div class="wppb-reset-buttons">
                    <button type="button" class="button cancel-reset" value="modal-reset" ><?php esc_html_e('Cancel', 'profile-builder'); ?></button>
                    <button type="submit" class="button button-primary confirm-reset">
                        <?php esc_html_e('Confirm', 'profile-builder') ?>
                    </button>
                </div>
            </div>
        </form>
    </div>
<?php endif; ?>