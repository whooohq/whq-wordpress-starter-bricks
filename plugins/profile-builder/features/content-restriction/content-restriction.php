<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$wppb_generalSettings = get_option( 'wppb_general_settings', 'not_found' );
$wppb_content_restriction_settings = get_option( 'wppb_content_restriction_settings', 'not_found' );
if( $wppb_generalSettings != 'not_found' || $wppb_content_restriction_settings != 'not_found' ) {
    global $content_restriction_activated;
    $content_restriction_activated = 'no';
    if( !empty( $wppb_content_restriction_settings['contentRestriction'] ) ){
        $content_restriction_activated = $wppb_content_restriction_settings['contentRestriction'];
    }
    elseif( !empty( $wppb_generalSettings['contentRestriction'] ) ){
        $content_restriction_activated = $wppb_generalSettings['contentRestriction'];
    }
    if( $content_restriction_activated == 'yes' ) {
        include_once 'content-restriction-meta-box.php';
        include_once 'content-restriction-filtering.php';
    }
    include_once 'content-restriction-functions.php';
}

add_action( 'admin_menu', 'wppb_content_restriction_submenu', 10 );
add_action( 'admin_enqueue_scripts', 'wppb_content_restriction_scripts_styles' );

function wppb_content_restriction_submenu() {

    add_submenu_page( 'profile-builder', __( 'Content Restriction', 'profile-builder' ), __( 'Content Restriction', 'profile-builder' ), 'manage_options', 'profile-builder-content_restriction', 'wppb_content_restriction_content' );

}

/* hide the menu item for Content restriction if it is disabled...in v 2.8.9 or 2.9.0 we should remove all the unnecessary tab menus */
add_action( 'admin_head', 'wppb_hide_content_restriction_menu' );
function wppb_hide_content_restriction_menu(){
    global $content_restriction_activated;
    if( $content_restriction_activated == 'no' ){
        echo '<style type="text/css">a[href="admin.php?page=profile-builder-content_restriction"]{display:none !important;}</style>';
    }
}

function wppb_content_restriction_settings_defaults() {

    add_option( 'wppb_content_restriction_settings',
        array(
            'restrict_type'         => 'message',
            'redirect_url'          => '',
            'message_logged_out'    => '',
            'message_logged_in'     => '',
            'purchasing_restricted' => '',
            'post_preview'          => 'none',
            'post_preview_length'   => '20',
        )
    );

}

function wppb_content_restriction_content() {

    wppb_content_restriction_settings_defaults();

    $wppb_content_restriction_settings = get_option( 'wppb_content_restriction_settings', 'not_found' );

    ?>
    <div class="wrap wppb-content-restriction-wrap cozmoslabs-wrap">

        <h1></h1>
        <!-- WordPress Notices are added after the h1 tag -->

        <div class="cozmoslabs-page-header">
            <div class="cozmoslabs-section-title">

                <h2 class="cozmoslabs-page-title">
                    <?php esc_html_e( 'Content Restriction Settings', 'profile-builder' ); ?>
                    <a href="https://www.cozmoslabs.com/docs/profile-builder/general-settings/content-restriction/?utm_source=wpbackend&utm_medium=pb-documentation&utm_campaign=PBDocs" target="_blank" data-code="f223" class="wppb-docs-link dashicons dashicons-editor-help"></a>
                </h2>

            </div>
        </div>

        <?php settings_errors(); ?>

        <?php wppb_generate_settings_tabs() ?>

        <form method="post" action="options.php">
            <?php settings_fields( 'wppb_content_restriction_settings' ); ?>

            <div class="cozmoslabs-settings-container">

                <div class="cozmoslabs-settings">

                    <div class="cozmoslabs-form-subsection-wrapper cozmoslabs-no-title-section" id="wppb-content-restriction-settings">

                        <?php
                        $wppb_generalSettings = get_option( 'wppb_general_settings' );
                        $content_restriction_activated = 'no';
                        if( !empty( $wppb_content_restriction_settings['contentRestriction'] ) ){
                            $content_restriction_activated = $wppb_content_restriction_settings['contentRestriction'];
                        }
                        elseif( !empty( $wppb_generalSettings['contentRestriction'] ) ){
                            $content_restriction_activated = $wppb_generalSettings['contentRestriction'];
                        }
                        ?>

                        <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                            <label class="cozmoslabs-form-field-label" for="contentRestrictionSelect"><?php esc_html_e( 'Content Restriction', 'profile-builder' ); ?></label>

                            <div class="cozmoslabs-toggle-container">
                                <input type="checkbox" name="wppb_content_restriction_settings[contentRestriction]" id="contentRestrictionSelect" value="yes" <?php echo ( $content_restriction_activated == 'yes' ) ? 'checked' : ''; ?> >
                                <label class="cozmoslabs-toggle-track" for="contentRestrictionSelect"></label>
                            </div>

                            <div class="cozmoslabs-toggle-description">
                                <label for="contentRestrictionSelect" class="cozmoslabs-description"><?php esc_html_e( 'Activate Content Restriction.', 'profile-builder' ); ?></label>
                            </div>
                        </div>

                        <div class="cozmoslabs-form-field-wrapper">
                            <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Type of Restriction', 'profile-builder' ); ?></label>

                            <div class="cozmoslabs-radio-inputs-row">
                                <label for="wppb-content-restrict-type-message">
                                    <input type="radio" id="wppb-content-restrict-type-message" value="message" <?php echo ( ( $wppb_content_restriction_settings != 'not_found' && $wppb_content_restriction_settings['restrict_type'] == 'message' ) ? 'checked="checked"' : '' ); ?> name="wppb_content_restriction_settings[restrict_type]">
                                    <?php esc_html_e( 'Message', 'profile-builder' ); ?>
                                </label>

                                <label for="wppb-content-restrict-type-redirect">
                                    <input type="radio" id="wppb-content-restrict-type-redirect" value="redirect" <?php echo ( ( $wppb_content_restriction_settings != 'not_found' && $wppb_content_restriction_settings['restrict_type'] == 'redirect' ) ? 'checked="checked"' : '' ); ?> name="wppb_content_restriction_settings[restrict_type]">
                                    <?php esc_html_e( 'Redirect', 'profile-builder' ); ?>
                                </label>
                            </div>

                            <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php echo esc_html__( 'If you select "Message", the post\'s content will be protected by being replaced with a custom message.', 'profile-builder' ); ?></p>
                            <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php echo esc_html__( 'If you select "Redirect", the post\'s content will be protected by redirecting the user to the URL you specify. The redirect happens only when accessing a single post. On archive pages the restriction message will be displayed, instead of the content.', 'profile-builder' ); ?></p>
                        </div>

                        <div class="cozmoslabs-form-field-wrapper">
                            <label class="cozmoslabs-form-field-label" for="wppb-content-restrict-redirect-url"><?php esc_html_e( 'Redirect URL', 'profile-builder' ); ?></label>
                            <input type="text" id="wppb-content-restrict-redirect-url" class="widefat" name="wppb_content_restriction_settings[redirect_url]" value="<?php echo ( ( $wppb_content_restriction_settings != 'not_found' && ! empty( $wppb_content_restriction_settings['redirect_url'] ) ) ? esc_url( $wppb_content_restriction_settings['redirect_url'] ) : '' ); ?>" />

                            <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php echo esc_html__( 'The URL where the user will be redirected if a redirect restriction is selected above.', 'profile-builder' ); ?></p>
                        </div>

                        <div class="cozmoslabs-form-field-wrapper cozmoslabs-column-radios-wrapper">
                            <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Restricted Posts Preview', 'profile-builder' ); ?></label>

                            <div class="cozmoslabs-radio-inputs-column">
                                <label>
                                    <input type="radio" name="wppb_content_restriction_settings[post_preview]" value="none" <?php echo ( ( $wppb_content_restriction_settings != 'not_found' ) && $wppb_content_restriction_settings['post_preview'] == 'none' ? 'checked' : '' ); ?> />
                                    <?php esc_html_e( 'None', 'profile-builder' ); ?>
                                </label>

                                <label>
                                    <input type="radio" name="wppb_content_restriction_settings[post_preview]" value="trim-content" <?php echo ( ( $wppb_content_restriction_settings != 'not_found' ) && $wppb_content_restriction_settings['post_preview'] == 'trim-content' ? 'checked' : '' ); ?> />
                                    <?php echo sprintf( __( 'Show the first %s words of the post\'s content', 'profile-builder' ), '<input name="wppb_content_restriction_settings[post_preview_length]" type="text" value="'. ( $wppb_content_restriction_settings != 'not_found' && ! empty( $wppb_content_restriction_settings['post_preview_length'] ) ? esc_attr( $wppb_content_restriction_settings['post_preview_length'] ) : 20 ) .'" style="width: 50px; height: 25px; min-height: 25px;" />' ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                </label>

                                <label>
                                    <input type="radio" name="wppb_content_restriction_settings[post_preview]" value="more-tag" <?php echo ( ( $wppb_content_restriction_settings != 'not_found' ) && $wppb_content_restriction_settings['post_preview'] == 'more-tag' ? 'checked' : '' ); ?> />
                                    <?php echo esc_html__( 'Show the content before the "more" tag', 'profile-builder' ); ?>
                                </label>
                            </div>

                            <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php echo esc_html__( 'Show a portion of the restricted post to logged-out users or users that are not allowed to see it.', 'profile-builder' ); ?></p>
                        </div>

                        <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                            <label class="cozmoslabs-form-field-label" for="excludeRestrictedPosts"><?php esc_html_e( 'Exclude Restricted Posts from Queries', 'profile-builder' ); ?></label>

                            <div class="cozmoslabs-toggle-container">
                                <input type="checkbox" name="wppb_content_restriction_settings[excludePosts]" id="excludeRestrictedPosts" value="yes" <?php echo ( isset( $wppb_content_restriction_settings['excludePosts'] ) && $wppb_content_restriction_settings['excludePosts'] == 'yes' ) ? 'checked' : ''; ?> >
                                <label class="cozmoslabs-toggle-track" for="excludeRestrictedPosts"></label>
                            </div>

                            <div class="cozmoslabs-toggle-description">
                                <label for="excludeRestrictedPosts" class="cozmoslabs-description"><?php esc_html_e( 'Activate this option to exclude the restricted posts from default WordPress and WooCommerce queries.', 'profile-builder' ); ?></label>
                            </div>
                        </div>

                        <?php do_action( 'wppb_extra_content_restriction_settings', $wppb_content_restriction_settings ); ?>

                    </div>

                    <div class="cozmoslabs-form-subsection-wrapper cozmoslabs-wysiwyg-container">
                        <h4 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Message for logged-out users', 'profile-builder' ); ?></h4>

                        <div class="cozmoslabs-form-field-wrapper cozmoslabs-wysiwyg-wrapper">
                            <?php wp_editor( wppb_get_restriction_content_message( 'logged_out' ), 'message_logged_out', array( 'textarea_name' => 'wppb_content_restriction_settings[message_logged_out]', 'editor_height' => 180 ) ); ?>
                        </div>
                    </div>

                    <div class="cozmoslabs-form-subsection-wrapper cozmoslabs-wysiwyg-container">
                        <h4 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Message for logged-in users', 'profile-builder' ); ?></h4>

                        <div class="cozmoslabs-form-field-wrapper cozmoslabs-wysiwyg-wrapper">
                            <?php wp_editor( wppb_get_restriction_content_message( 'logged_in' ), 'message_logged_in', array( 'textarea_name' => 'wppb_content_restriction_settings[message_logged_in]', 'editor_height' => 180 ) ); ?>
                        </div>
                    </div>

                    <?php if ( ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) || ( is_plugin_active_for_network('woocommerce/woocommerce.php') ) ) : ?>
                        <div class="cozmoslabs-form-subsection-wrapper cozmoslabs-wysiwyg-container">
                            <h4 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Message for WooCommerce Restricted Product Purchase', 'profile-builder' ); ?></h4>

                            <div class="cozmoslabs-form-field-wrapper cozmoslabs-wysiwyg-wrapper">
                                <?php wp_editor( wppb_get_restriction_content_message( 'purchasing_restricted' ), 'messages_purchasing_restricted', array( 'textarea_name' => 'wppb_content_restriction_settings[purchasing_restricted]', 'editor_height' => 180 ) ); ?>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>

                <div class="submit cozmoslabs-submit">
                    <h3 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Update Settings', 'profile-builder' ) ?></h3>
                    <div class="cozmoslabs-publish-button-group">
                        <?php submit_button( __( 'Save Changes', 'profile-builder' ) ); ?>
                    </div>
                </div>

            </div>

        </form>
    </div>
    <?php

}

function wppb_content_restriction_scripts_styles($hook_suffix) {
    //Check if it's an editing or adding new post page
    if( $hook_suffix === 'post-new.php' || $hook_suffix === 'edit.php' || $hook_suffix === 'edit-tags.php' || $hook_suffix === 'term.php' || ( $hook_suffix === 'post.php' && isset( $_GET['action'] ) && $_GET['action'] === 'edit' ) || ( isset( $_GET['page'] ) && $_GET['page'] === 'profile-builder-content_restriction' ) ){
        wp_enqueue_script( 'wppb_content_restriction_js', plugin_dir_url( __FILE__ ) .'assets/js/content-restriction.js', array( 'jquery' ), PROFILE_BUILDER_VERSION );
        wp_enqueue_style( 'wppb_content_restriction_css', plugin_dir_url( __FILE__ ) .'assets/css/content-restriction.css', array(), PROFILE_BUILDER_VERSION );

        wp_enqueue_style( 'wppb-back-end-style', WPPB_PLUGIN_URL . 'assets/css/style-back-end.css', array(), PROFILE_BUILDER_VERSION );
    }
}

// Declare HPOS compatibility
add_action( 'before_woocommerce_init', 'wppb_woo_declare_hpos_compatibility' );
function wppb_woo_declare_hpos_compatibility() {

    $plugin_slug = 'profile-builder/index.php';

    if( defined( 'PROFILE_BUILDER_PAID_VERSION' ) && PROFILE_BUILDER_PAID_VERSION == 'dev' )
        $plugin_slug = 'profile-builder-dev/index.php';

	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) )
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', $plugin_slug, true );

}