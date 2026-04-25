<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<?php $settings = get_option( 'wppb_toolbox_userlisting_settings' ); ?>

<form method="post" action="options.php">

    <?php settings_fields( 'wppb_toolbox_userlisting_settings' ); ?>

    <div class="cozmoslabs-settings-container">

        <div class="cozmoslabs-form-subsection-wrapper cozmoslabs-settings cozmoslabs-no-title-section">
            <div class="cozmoslabs-form-field-wrapper">
                <label class="cozmoslabs-form-field-label" for="toolbox-search-placeholder-text"><?php esc_html_e( 'Search Box Placeholder', 'profile-builder' ); ?></label>
                <input type="text" id="toolbox-search-placeholder-text" name="wppb_toolbox_userlisting_settings[search-placeholder-text]" value="<?php echo ( !empty( $settings['search-placeholder-text']) ? esc_attr( $settings['search-placeholder-text'] ) : '' ); ?>">
                <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'This refers to the placeholder text from the "{{{extra_search_all_fields}}}" tag.', 'profile-builder' ); ?></p>
                <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'Default text is "Search Users by All Fields", use this option to change it to something else. Leave empty if you do not want to change it.', 'profile-builder' ); ?></p>
            </div>

            <div class="cozmoslabs-form-field-wrapper">
                <label class="cozmoslabs-form-field-label" for="toolbox-modify-permalinks-single"><?php esc_html_e( 'Single Userlisting Base URL', 'profile-builder' ); ?></label>
                <input type="text" id="toolbox-modify-permalinks-single" name="wppb_toolbox_userlisting_settings[modify-permalinks-single]" value="<?php echo ( !empty( $settings['modify-permalinks-single']) ? esc_attr( $settings['modify-permalinks-single'] ) : '' ); ?>">

                <p class="cozmoslabs-description cozmoslabs-description-space-left">
                    <?php esc_html_e( 'By default Single Userlisting URLs contain the word "user".', 'profile-builder' ); ?>
                    <br>
                    <em><?php esc_html_e( 'eg.: ', 'profile-builder' ); echo esc_url( home_url( 'userlisting/user/123' ) ); ?></em>
                </p>
                <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'You can change the word "user" to something else. Leave empty if you do not want to change it.', 'profile-builder' ); ?></p>
            </div>

            <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                <label class="cozmoslabs-form-field-label" for="toolbox-use-nicename-single"><?php esc_html_e('Single Userlisting URLs Nicename', 'profile-builder'); ?></label>

                <div class="cozmoslabs-toggle-container">
                    <input type="checkbox" id="toolbox-use-nicename-single" name="wppb_toolbox_userlisting_settings[use-nicename-single]"<?php echo ( ( isset( $settings['use-nicename-single'] ) && ( $settings['use-nicename-single'] == 'yes' ) ) ? ' checked' : '' ); ?> value="yes">
                    <label class="cozmoslabs-toggle-track" for="toolbox-use-nicename-single"></label>
                </div>

                <div class="cozmoslabs-toggle-description">
                    <label for="toolbox-use-nicename-single" class="cozmoslabs-description"><?php esc_html_e( 'Make the Single Userlisting URLs work with user nicename.', 'profile-builder' ); ?></label>
                </div>

                <p class="cozmoslabs-description cozmoslabs-description-space-left">
                    <?php esc_html_e( 'By default Single Userlisting URLs are generated using the users ID.', 'profile-builder' ); ?>
                    <br>
                    <em><?php esc_html_e( 'eg.: ', 'profile-builder' ); echo esc_url( home_url( 'userlisting/user/123' ) ); ?></em>
                </p>
                <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'By enabling this option the URLs will be generated using the users "nicename".', 'profile-builder' ); ?></p>
            </div>

            <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                <label class="cozmoslabs-form-field-label" for="toolbox-remove-repetitions"><?php esc_html_e('Remove Faceted Menus Counters', 'profile-builder'); ?></label>

                <div class="cozmoslabs-toggle-container">
                    <input type="checkbox" id="toolbox-remove-repetitions" name="wppb_toolbox_userlisting_settings[remove-repetitions]"<?php echo ( ( isset( $settings['remove-repetitions'] ) && ( $settings['remove-repetitions'] == 'yes' ) ) ? ' checked' : '' ); ?> value="yes">
                    <label class="cozmoslabs-toggle-track" for="toolbox-remove-repetitions"></label>
                </div>

                <div class="cozmoslabs-toggle-description">
                    <label for="toolbox-remove-repetitions" class="cozmoslabs-description"><?php esc_html_e( 'Remove repetition counts from Faceted Menus.', 'profile-builder' ); ?></label>
                </div>

                <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'The number of users that share a particular value is shown for the Select and Checkbox facet types.', 'profile-builder' ); ?></p>
                <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'By enabling this option the counters will be hidden.', 'profile-builder' ); ?></p>
            </div>

            <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                <label class="cozmoslabs-form-field-label" for="toolbox-enable-map-poi-conditional-logic"><?php esc_html_e('Conditional Logic in Map POI Bubbles', 'profile-builder'); ?></label>

                <div class="cozmoslabs-toggle-container">
                    <input type="checkbox" id="toolbox-enable-map-poi-conditional-logic" name="wppb_toolbox_userlisting_settings[enable-map-poi-conditional-logic]"<?php echo ( ( isset( $settings['enable-map-poi-conditional-logic'] ) && ( $settings['enable-map-poi-conditional-logic'] == 'yes' ) ) ? ' checked' : '' ); ?> value="yes">
                    <label class="cozmoslabs-toggle-track" for="toolbox-enable-map-poi-conditional-logic"></label>
                </div>

                <div class="cozmoslabs-toggle-description">
                    <label for="toolbox-enable-map-poi-conditional-logic" class="cozmoslabs-description"><?php esc_html_e( 'Enable the functionality.', 'profile-builder' ); ?></label>
                </div>

                <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'When enabled, the Map POI bubble will only display fields that are visible according to conditional logic.', 'profile-builder' ); ?></p>
                <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'By default this option is disabled.', 'profile-builder' ); ?></p>
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
