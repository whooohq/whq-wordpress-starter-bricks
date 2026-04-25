<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * Function that creates the "Private Website" submenu page
 *
 * @since v.2.0
 *
 * @return void
 */
function wppb_private_website_submenu_page() {
    add_submenu_page( 'profile-builder', __( 'Private Website', 'profile-builder' ), __( 'Private Website', 'profile-builder' ), 'manage_options', 'profile-builder-private-website', 'wppb_private_website_content' );
}
add_action( 'admin_menu', 'wppb_private_website_submenu_page' );


/**
 * Function that generates the default settings for private page
 */
function wppb_private_website_settings_defaults() {

    add_option( 'wppb_private_website_settings',
        array(
            'private_website'       =>  'no',
            'redirect_to'           =>  '',
            'allowed_pages'         =>  array(),
            'hide_menus'            =>  'no',
            'disable_rest_api'      =>  'yes',
        )
    );

}

/**
 * Function that generates the content for the settings page
 */
function wppb_private_website_content() {

    wppb_private_website_settings_defaults();

    $wppb_private_website_settings = get_option( 'wppb_private_website_settings', 'not_found' );

    $args = array(
        'post_type'         => 'page',
        'posts_per_page'    => -1
    );

    if( function_exists( 'wc_get_page_id' ) )
        $args['exclude'] = wc_get_page_id( 'shop' );

    $all_pages = get_posts( $args );
    ?>
    <div class="wrap wppb-wrap wppb-private-website cozmoslabs-wrap">

        <h1></h1>
        <!-- WordPress Notices are added after the h1 tag -->

        <div class="cozmoslabs-page-header">
            <div class="cozmoslabs-section-title">

                <h2 class="cozmoslabs-page-title">
                    <?php esc_html_e( 'Private Website Settings', 'profile-builder' );?>
                    <a href="https://www.cozmoslabs.com/docs/profile-builder/general-settings/private-website/?utm_source=wpbackend&utm_medium=pb-documentation&utm_campaign=PBDocs" target="_blank" data-code="f223" class="wppb-docs-link dashicons dashicons-editor-help"></a>
                </h2>

            </div>
        </div>

        <?php settings_errors(); ?>

        <?php wppb_generate_settings_tabs() ?>

        <form method="post" action="options.php">
            <?php settings_fields( 'wppb_private_website_settings' ); ?>

            <div class="cozmoslabs-settings-container">

                <div class="cozmoslabs-settings">

                    <div class="cozmoslabs-form-subsection-wrapper cozmoslabs-no-title-section">

                        <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                            <label class="cozmoslabs-form-field-label" for="private-website-enable"><?php esc_html_e('Private Website', 'profile-builder'); ?></label>

                            <div class="cozmoslabs-toggle-container">
                                <input type="checkbox" name="wppb_private_website_settings[private_website]" id="private-website-enable" value="yes" <?php echo (!empty($wppb_private_website_settings['private_website']) && $wppb_private_website_settings['private_website'] === 'yes') ? 'checked' : ''; ?> >
                                <label class="cozmoslabs-toggle-track" for="private-website-enable"></label>
                            </div>

                            <div class="cozmoslabs-toggle-description">
                                <label for="private-website-enable" class="cozmoslabs-description"><?php esc_html_e( 'Activate Private Website. It will restrict the content, RSS and REST API for your website.', 'profile-builder' ); ?></label>
                            </div>
                        </div>

                        <div class="cozmoslabs-form-field-wrapper">
                            <label class="cozmoslabs-form-field-label" for="private-website-redirect-to-login"><?php esc_html_e('Redirect to', 'profile-builder'); ?></label>

                            <select id="private-website-redirect-to-login" class="wppb-select" name="wppb_private_website_settings[redirect_to]">
                                <option value=""><?php esc_html_e( 'Default WordPress login page', 'profile-builder' ); ?></option>

                                <?php
                                if( !empty( $all_pages ) ){
                                    foreach ($all_pages as $page){
                                        ?>
                                        <option value="<?php echo esc_attr( $page->ID ) ?>" <?php echo ( ( $wppb_private_website_settings != 'not_found' && isset( $wppb_private_website_settings['redirect_to'] ) && $wppb_private_website_settings['redirect_to'] == $page->ID ) ? 'selected' : '' ); ?>><?php echo esc_html( $page->post_title ) ?></option>
                                        <?php
                                    }
                                }
                                ?>
                            </select>

                            <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'Redirects to this page if not logged in. We recommend this page contains the [wppb-login] shortcode.', 'profile-builder' ); ?></p>
                            <p class="cozmoslabs-description cozmoslabs-description-space-left">
                                <?php esc_html_e( 'You can force access to wp-login.php so you don\'t get locked out of the site by accessing the link:', 'profile-builder' ); ?>
                                <a href="<?php echo esc_url( wp_login_url() ).'?wppb_force_wp_login=true' ?>"><?php echo esc_url( wp_login_url() ).'?wppb_force_wp_login=true' ?></a>
                            </p>
                        </div>

                        <div class="cozmoslabs-form-field-wrapper">
                            <label class="cozmoslabs-form-field-label" for="private-website-allowed-pages"><?php esc_html_e('Allowed Pages', 'profile-builder'); ?></label>

                            <select id="private-website-allowed-pages" class="wppb-select" name="wppb_private_website_settings[allowed_pages][]" multiple="multiple">
                                <?php
                                if( !empty( $all_pages ) ){
                                    foreach ($all_pages as $page){
                                        ?>
                                        <option value="<?php echo esc_attr( $page->ID ) ?>" <?php echo ( ( $wppb_private_website_settings != 'not_found' && isset( $wppb_private_website_settings['allowed_pages'] ) && in_array( $page->ID, $wppb_private_website_settings['allowed_pages'] ) ) ? 'selected' : '' ); ?>><?php echo esc_html( $page->post_title ) ?></option>
                                        <?php
                                    }
                                }
                                ?>

                            </select>

                            <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'Allow these pages to be accessed even if you are not logged in', 'profile-builder' ); ?></p>
                        </div>

                        <div class="cozmoslabs-form-field-wrapper">
                            <label class="cozmoslabs-form-field-label" for="private-website-allowed-paths"><?php esc_html_e('Allowed Paths', 'profile-builder'); ?></label>
                            <textarea id="private-website-allowed-paths" class="wppb-textarea" name="wppb_private_website_settings[allowed_paths]"><?php echo ( ( $wppb_private_website_settings != 'not_found' && !empty($wppb_private_website_settings['allowed_paths']) ) ? esc_textarea( $wppb_private_website_settings['allowed_paths'] ) : '' ); ?></textarea>

                            <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'Allow these paths to be accessed even if you are not logged in (supports wildcard at the end of the path). For example to exclude https://example.com/some/path/ you can either use the rule /some/path/ or /some/* Enter each rule on it\'s own line', 'profile-builder' ); ?></p>
                        </div>

                        <div class="cozmoslabs-form-field-wrapper">
                            <label class="cozmoslabs-form-field-label" for="private-website-allowed-query-strings"><?php esc_html_e('Allowed Query Strings', 'profile-builder'); ?></label>
                            <textarea id="private-website-allowed-query-strings" class="wppb-textarea" name="wppb_private_website_settings[allowed_query_strings]"><?php echo ( ( $wppb_private_website_settings != 'not_found' && !empty( $wppb_private_website_settings['allowed_query_strings'] ) ) ? esc_textarea( $wppb_private_website_settings['allowed_query_strings'] ) : '' ); ?></textarea>

                            <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'Allow paths containing these query strings to be accessible to logged out users. For example, you can add s to exclude a search request: https://example.com/?s=search', 'profile-builder' ); ?></p>
                        </div>

                        <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                            <label class="cozmoslabs-form-field-label" for="private-website-menu-hide"><?php esc_html_e('Hide all Menus', 'profile-builder'); ?></label>

                            <div class="cozmoslabs-toggle-container">
                                <input type="checkbox" name="wppb_private_website_settings[hide_menus]" id="private-website-menu-hide" value="yes" <?php echo ( !empty( $wppb_private_website_settings['hide_menus'] ) && $wppb_private_website_settings['hide_menus'] === 'yes' ) ? 'checked' : ''; ?> >
                                <label class="cozmoslabs-toggle-track" for="private-website-menu-hide"></label>
                            </div>

                            <div class="cozmoslabs-toggle-description">
                                <label for="private-website-menu-hide" class="cozmoslabs-description"><?php wp_kses_post( printf( __( 'Hide all menu items if you are not logged in. We recommend "<a href="%s" target="_blank">Custom Profile Menus</a>" addon if you need different menu items for logged in / logged out users.', 'profile-builder' ), 'https://www.cozmoslabs.com/add-ons/custom-profile-menus/?utm_source=pb-private-website-settings&utm_medium=client-site&utm_campaign=pb-hide-all-menus' ) )    //phpcs:ignore; ?></label>
                            </div>
                        </div>

                        <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                            <label class="cozmoslabs-form-field-label" for="private-website-disable-rest-api"><?php esc_html_e('Disable REST-API', 'profile-builder'); ?></label>

                            <div class="cozmoslabs-toggle-container">
                                <input type="checkbox" name="wppb_private_website_settings[disable_rest_api]" id="private-website-disable-rest-api" value="yes" <?php echo ( !empty( $wppb_private_website_settings['disable_rest_api'] ) && $wppb_private_website_settings['disable_rest_api'] === 'yes' ) ? 'checked' : ''; ?> >
                                <label class="cozmoslabs-toggle-track" for="private-website-disable-rest-api"></label>
                            </div>

                            <div class="cozmoslabs-toggle-description">
                                <label for="private-website-disable-rest-api" class="cozmoslabs-description"><?php esc_html_e( 'Disable the WordPress REST-API for non-logged in users when Private Website is enabled', 'profile-builder' ); ?></label>
                            </div>
                        </div>

                    </div>

                </div>

                <div class="submit cozmoslabs-submit">
                    <h3 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Update Settings', 'profile-builder' ) ?></h3>
                    <div class="cozmoslabs-publish-button-group">
                        <?php submit_button( esc_html__( 'Save Changes', 'profile-builder' ) ); ?>
                    </div>
                </div>

            </div>

        </form>

    </div>
    <?php
}
