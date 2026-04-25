<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<h3><?php esc_html_e( 'Design & User Experience Settings', 'profile-builder' ); ?></h3>
<p class="cozmoslabs-description"><?php esc_html_e( 'Customize the way your users interact with the website!', 'profile-builder' ); ?></p>

<form class="wppb-setup-form wppb-setup-form-general" method="post">

    <div class="wppb-setup-form-styles">
        <?php
        if ( defined( 'WPPB_PAID_PLUGIN_DIR' ) && file_exists( WPPB_PAID_PLUGIN_DIR.'/features/form-designs/form-designs.php' ) ) {
            echo wppb_render_forms_design_selector(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

            ?>
            <p class="info">
                <?php esc_html_e( 'Choose a style that better suits your website.', 'profile-builder' ); ?>
                <br>
                <?php esc_html_e( 'The default style is there to let you customize the CSS and in general will receive the look and feel from your own themes styling. ', 'profile-builder' ); ?>
                <br>
                <?php esc_html_e( 'The extra styles can be customized to your liking through extra settings. ', 'profile-builder' ); ?>
            </p>
            <?php
        }
        elseif ( PROFILE_BUILDER == 'Profile Builder Free' ) {
            echo wppb_display_form_designs_preview(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

            printf( esc_html__( '%3$sYou can now beautify your forms using pre-made templates. Enable Form Designs by upgrading to %1$sBasic or PRO versions%2$s.%4$s', 'profile-builder' ),'<a href="https://www.cozmoslabs.com/wordpress-profile-builder/?utm_source=pb-setup-wizard&utm_medium=client-site&utm_campaign=pb-form-design-templates#pricing" target="_blank">', '</a>', '<p class="wppb-setup-form-styles__upsell">', '</p>' );
        }
        ?>
    </div>

    <strong class="wppb-setup-general-settings__heading"><?php esc_html_e( 'Optimize the login and registration flow for your members!', 'profile-builder' ); ?></strong>

    <div class="wppb-setup-general-settings">
        <div class="wppb-setup-general-settings__item">
            <div class="cozmoslabs-toggle-switch">
                <div class="cozmoslabs-toggle-container">
                    <input type="checkbox" name="automaticallyLogIn" id="wppb_settings_automatically_log_in" value="Yes" <?php echo ( !empty( $this->general_settings['automaticallyLogIn'] ) && $this->general_settings['automaticallyLogIn'] === 'Yes' ) ? 'checked' : ''; ?> >
                    <label class="cozmoslabs-toggle-track" for="wppb_settings_automatically_log_in"></label>
                </div>
            </div>

            <label for="wppb_settings_automatically_log_in"><?php esc_html_e( 'Automatically log users in after registration', 'profile-builder' ); ?></label>
        </div>

        <div class="wppb-setup-general-settings__item">
            <div class="cozmoslabs-toggle-switch">
                <div class="cozmoslabs-toggle-container">
                    <input type="checkbox" name="hide_admin_bar_for_subscriber" id="hide_admin_bar_for_subscriber" value="Yes" <?php echo ( !empty( $this->general_settings['hide_admin_bar_for']) && in_array( 'Subscriber', $this->general_settings['hide_admin_bar_for']  )) ? 'checked' : ''; ?> >
                    <label class="cozmoslabs-toggle-track" for="hide_admin_bar_for_subscriber"></label>
                </div>
            </div>

            <label for="hide_admin_bar_for_subscriber" title="<?php esc_html_e( 'You can modify each role individually in the settings', 'profile-builder' ); ?>">
                <?php esc_html_e( 'Hide the admin bar for the subscriber role', 'profile-builder' ); ?>
            </label>
        </div>

        <div class="wppb-setup-general-settings__item">
            <div class="cozmoslabs-toggle-switch">
                <div class="cozmoslabs-toggle-container">
                    <input type="checkbox" name="emailConfirmation" id="emailConfirmation" value="yes" <?php echo ( !empty( $this->general_settings['emailConfirmation'] ) && $this->general_settings['emailConfirmation'] === 'yes' ) ? 'checked' : ''; ?> >
                    <label class="cozmoslabs-toggle-track" for="emailConfirmation"></label>
                </div>
            </div>

            <label for="emailConfirmation"><?php esc_html_e( 'Email Confirmation after registration', 'profile-builder' ); ?></label>
        </div>

        <div class="wppb-setup-general-settings__item">

            <?php
            if ( defined( 'WPPB_PAID_PLUGIN_DIR' ) ) {
                ?>
                <div class="cozmoslabs-toggle-switch">
                    <div class="cozmoslabs-toggle-container">
                        <input type="checkbox" name="adminApproval" id="adminApproval" value="yes" <?php echo ( !empty( $this->general_settings['adminApproval'] ) && $this->general_settings['adminApproval'] === 'yes' ) ? 'checked' : ''; ?> >
                        <label class="cozmoslabs-toggle-track" for="adminApproval"></label>
                    </div>
                </div>

                <label for="adminApproval"><?php esc_html_e( 'Admin Approval for new users', 'profile-builder' ); ?></label>
                <?php
            }
            elseif ( PROFILE_BUILDER == 'Profile Builder Free' ) {
                ?>
                <div class="cozmoslabs-toggle-switch" title="<?php esc_html_e( 'Available in the Pro version', 'profile-builder' ); ?>">
                    <div class="cozmoslabs-toggle-container">
                        <input type="checkbox" id="adminApproval" disabled >
                        <label class="cozmoslabs-toggle-track" for="adminApproval"></label>
                    </div>
                </div>

                <span title="<?php esc_html_e( 'Available in the Pro version', 'profile-builder' ); ?>">
                    <?php esc_html_e( 'Admin Approval for new users', 'profile-builder' ); ?>
                </span>
                <?php
            }
            ?>

        </div>

    </div>


    <div class="wppb-setup-form-button">
        <input type="submit" class="button primary button-primary button-hero" value="<?php esc_html_e( 'Continue', 'profile-builder' ); ?>" />
    </div>

    <?php wp_nonce_field( 'wppb-setup-wizard-nonce', 'wppb_setup_wizard_nonce' ); ?>
</form>