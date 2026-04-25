<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<?php $settings = get_option( 'wppb_toolbox_shortcodes_settings' ); ?>

<form method="post" action="options.php">

    <?php settings_fields( 'wppb_toolbox_shortcodes_settings' ); ?>

    <div class="cozmoslabs-settings-container">

        <div class="cozmoslabs-form-subsection-wrapper cozmoslabs-settings cozmoslabs-no-title-section" id="advanced-settings-shortcodes">
            <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                <label class="cozmoslabs-form-field-label" for="toolbox-compare"><?php esc_html_e('Compare Shortcode', 'profile-builder'); ?></label>

                <div class="cozmoslabs-toggle-container">
                    <input type="checkbox" id="toolbox-compare" name="wppb_toolbox_shortcodes_settings[compare]"<?php echo ( ( isset( $settings['compare'] ) && ( $settings['compare'] == 'yes' ) ) ? ' checked' : '' ); ?> value="yes">
                    <label class="cozmoslabs-toggle-track" for="toolbox-compare"></label>
                </div>

                <div class="cozmoslabs-toggle-description">
                    <label for="toolbox-compare" class="cozmoslabs-description"><?php esc_html_e( 'Enable Compare shortcode.', 'profile-builder' ); ?></label>
                </div>

                <p class="cozmoslabs-description cozmoslabs-description-space-left">
                    <?php echo wp_kses_post( __( 'You can read more info about this shortcode by following <a href="https://www.cozmoslabs.com/docs/profile-builder/developers-knowledge-base/shortcodes/compare-shortcode/?utm_source=pb-advanced-settings&utm_medium=client-site&utm_campaign=pb-compare-shortcode" target="_blank">this url</a>.', 'profile-builder' ) ); ?>
                </p>
            </div>

            <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                <label class="cozmoslabs-form-field-label" for="toolbox-usermeta"><?php esc_html_e('Usermeta Shortcode', 'profile-builder'); ?></label>

                <div class="cozmoslabs-toggle-container">
                    <input type="checkbox" id="toolbox-usermeta" name="wppb_toolbox_shortcodes_settings[usermeta]"<?php echo ( ( isset( $settings['usermeta'] ) && ( $settings['usermeta'] == 'yes' ) ) ? ' checked' : '' ); ?> value="yes">
                    <label class="cozmoslabs-toggle-track" for="toolbox-usermeta"></label>
                </div>

                <div class="cozmoslabs-toggle-description">
                    <label for="toolbox-usermeta" class="cozmoslabs-description"><?php esc_html_e( 'Enable Usermeta shortcode.', 'profile-builder' ); ?></label>
                </div>

                <p class="cozmoslabs-description cozmoslabs-description-space-left">
                    <?php echo wp_kses_post( __( 'You can read more info about this shortcode by following <a href="https://www.cozmoslabs.com/docs/profile-builder/developers-knowledge-base/shortcodes/display-user-meta/?utm_source=pb-advanced-settings&utm_medium=client-site&utm_campaign=pb-usermeta-shortcode " target="_blank">this url</a>.', 'profile-builder' ) ); ?>
                </p>
            </div>

            <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                <label class="cozmoslabs-form-field-label" for="toolbox-resend-activation"><?php esc_html_e('Resend Activation Email Shortcode', 'profile-builder'); ?></label>

                <div class="cozmoslabs-toggle-container">
                    <input type="checkbox" id="toolbox-resend-activation" name="wppb_toolbox_shortcodes_settings[resend-activation]"<?php echo ( ( isset( $settings['resend-activation'] ) && ( $settings['resend-activation'] == 'yes' ) ) ? ' checked' : '' ); ?> value="yes">
                    <label class="cozmoslabs-toggle-track" for="toolbox-resend-activation"></label>
                </div>

                <div class="cozmoslabs-toggle-description">
                    <label for="toolbox-resend-activation" class="cozmoslabs-description"><?php esc_html_e( 'Enable Resend Activation Email shortcode.', 'profile-builder' ); ?></label>
                </div>

                <p class="cozmoslabs-description cozmoslabs-description-space-left">
                    <?php echo wp_kses_post( __( 'You can read more info about this shortcode by following <a href="https://www.cozmoslabs.com/docs/profile-builder/developers-knowledge-base/shortcodes/resend-confirmation-email/?utm_source=pb-advanced-settings&utm_medium=client-site&utm_campaign=pb-resend-activation-shortcode" target="_blank">this url</a>.', 'profile-builder' ) ); ?>
                </p>
            </div>

            <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                <label class="cozmoslabs-form-field-label" for="toolbox-format-date"><?php esc_html_e('Format Date Shortcode', 'profile-builder'); ?></label>

                <div class="cozmoslabs-toggle-container">
                    <input type="checkbox" id="toolbox-format-date" name="wppb_toolbox_shortcodes_settings[format-date]"<?php echo ( ( isset( $settings['format-date'] ) && ( $settings['format-date'] == 'yes' ) ) ? ' checked' : '' ); ?> value="yes">
                    <label class="cozmoslabs-toggle-track" for="toolbox-format-date"></label>
                </div>

                <div class="cozmoslabs-toggle-description">
                    <label for="toolbox-format-date" class="cozmoslabs-description"><?php esc_html_e( 'Enable Format Date shortcode.', 'profile-builder' ); ?></label>
                </div>

                <p class="cozmoslabs-description cozmoslabs-description-space-left">
                    <?php echo wp_kses_post( __( 'You can read more info about this shortcode by following <a href="https://www.cozmoslabs.com/docs/profile-builder/developers-knowledge-base/shortcodes/format-date-shortcode/?utm_source=pb-advanced-settings&utm_medium=client-site&utm_campaign=pb-format-date-shortcode" target="_blank">this url</a>.', 'profile-builder' ) ); ?>
                </p>
            </div>

            <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                <label class="cozmoslabs-form-field-label" for="toolbox-list-roles"><?php esc_html_e('List Roles shortcode', 'profile-builder'); ?></label>

                <div class="cozmoslabs-toggle-container">
                    <input type="checkbox" id="toolbox-list-roles" name="wppb_toolbox_shortcodes_settings[list-roles]"<?php echo ( ( isset( $settings['list-roles'] ) && ( $settings['list-roles'] == 'yes' ) ) ? ' checked' : '' ); ?> value="yes">
                    <label class="cozmoslabs-toggle-track" for="toolbox-list-roles"></label>
                </div>

                <div class="cozmoslabs-toggle-description">
                    <label for="toolbox-list-roles" class="cozmoslabs-description"><?php esc_html_e( 'Enable List Roles shortcode.', 'profile-builder' ); ?></label>
                </div>

                <p class="cozmoslabs-description cozmoslabs-description-space-left">
                    <?php echo wp_kses_post( __( 'You can read more info about this shortcode by following <a href="https://www.cozmoslabs.com/docs/profile-builder/general-settings/advanced-settings/?utm_source=pb-advanced-settings&utm_medium=client-site&utm_campaign=pb-list-roles-shortcode/#Shortcodes" target="_blank">this url</a>.', 'profile-builder' ) ); ?>
                </p>
            </div>
        </div>

        <div class="submit cozmoslabs-submit">
            <h3 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Update Settings', 'profile-builder' ) ?></h3>
            <div class="cozmoslabs-publish-button-group">
                <?php submit_button( __( 'Save Changes', 'profile-builder' ) ); ?>
            </div>
        </div>

    </div>

</form>
