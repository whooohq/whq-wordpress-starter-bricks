<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * Function that returns an array with the settings tabs(pages) and secondary tabs( can be sub-pages (we load a registered page as a secondary tab) or actual sub-tabs )
 * @return array with the tabs
 */
function wppb_get_settings_pages(){
    $wppb_module_settings = get_option('wppb_module_settings');

	$settings_pages['pages'] = array(
		'profile-builder-general-settings' => __( 'General Settings', 'profile-builder' ),
		'profile-builder-content_restriction' => __( 'Content Restriction', 'profile-builder' ),
		'profile-builder-private-website' => __( 'Private Website', 'profile-builder' ),
		'profile-builder-toolbox-settings' => __( 'Advanced Settings', 'profile-builder' ),
	);

    //add tabs here for Advanced Settings
    $settings_pages['sub-tabs']['profile-builder-toolbox-settings']['forms'] = __( 'Forms', 'profile-builder' );
    $settings_pages['sub-tabs']['profile-builder-toolbox-settings']['fields'] = __( 'Fields', 'profile-builder' );

    if ( defined( 'WPPB_PAID_PLUGIN_DIR' ) && file_exists( WPPB_PAID_PLUGIN_DIR . '/add-ons/add-ons.php' ) && isset( $wppb_module_settings['wppb_userListing'] ) &&  $wppb_module_settings['wppb_userListing'] === 'show' )
        $settings_pages['sub-tabs']['profile-builder-toolbox-settings']['userlisting'] = __( 'Userlisting', 'profile-builder' );

    $settings_pages['sub-tabs']['profile-builder-toolbox-settings']['shortcodes'] = __( 'Shortcodes', 'profile-builder' );
    $settings_pages['sub-tabs']['profile-builder-toolbox-settings']['admin'] = __( 'Admin', 'profile-builder' );

    //add sub-pages here for email customizer
	if( file_exists( WPPB_PLUGIN_DIR . '/features/email-customizer/email-customizer.php' ) ){

		$settings_pages['pages']['user-email-customizer'] = __( 'Email Customizer', 'profile-builder' );
		$settings_pages['sub-pages']['user-email-customizer']['user-email-customizer'] = __( 'User Emails', 'profile-builder' );
		$settings_pages['sub-pages']['user-email-customizer']['admin-email-customizer'] = __( 'Administrator Emails', 'profile-builder' );

	} else if ( defined( 'WPPB_PAID_PLUGIN_DIR' ) && file_exists( WPPB_PAID_PLUGIN_DIR . '/add-ons/add-ons.php' ) ) {
		if( ( isset($wppb_module_settings['wppb_emailCustomizerAdmin']) && $wppb_module_settings['wppb_emailCustomizerAdmin'] == 'show' ) || ( isset($wppb_module_settings['wppb_emailCustomizer']) && $wppb_module_settings['wppb_emailCustomizer'] == 'show') ){
			$settings_pages['pages']['user-email-customizer'] = __( 'Email Customizer', 'profile-builder' );
			$settings_pages['sub-pages']['user-email-customizer']['user-email-customizer'] = __( 'User Emails', 'profile-builder' );
			$settings_pages['sub-pages']['user-email-customizer']['admin-email-customizer'] = __( 'Administrator Emails', 'profile-builder' );
		}
	}

	return $settings_pages;
}

/**
 * Function that generates the html for the tabs and subtabs on the settings page
 */
function wppb_generate_settings_tabs(){
	?>
	<nav class="nav-tab-wrapper cozmoslabs-nav-tab-wrapper">
	<?php
		$pages = wppb_get_settings_pages();

		$active_tab = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : '';
		//if we are on a subpage we need to change the active tab to the parent
		if( !empty( $pages['sub-pages'] ) ) {
			foreach ($pages['sub-pages'] as $parent_slug => $subpages) {
				if (array_key_exists($active_tab, $subpages)) {
					$active_tab = $parent_slug;
				}
			}
		}

		foreach( $pages['pages'] as $page_slug => $tab_name ){
			echo '<a href="' . esc_url( admin_url( add_query_arg( array( 'page' => $page_slug ), 'admin.php' )  ) ) . '"  class="nav-tab ' . ( $active_tab == $page_slug ? 'nav-tab-active' : '' ) . '">'. esc_html( $tab_name ) .'</a>';
		}
	?>
	</nav>
	<?php

    // this is not always the same as the active tab
    $active_subpage = sanitize_text_field($_GET['page']);

	if( !empty( $pages['sub-pages'] ) ) {
		foreach ($pages['sub-pages'] as $parent_slug => $subpages) {
			if (array_key_exists( sanitize_text_field( $active_subpage ), $subpages)) {
                echo '<ul class="wppb-subtabs subsubsub cozmoslabs-nav-sub-tab-wrapper">';
				foreach ($subpages as $subpage_slug => $subpage_name) {
					echo '<li><a href="' . esc_url( admin_url( add_query_arg(array('page' => $subpage_slug), 'admin.php') ) ) . '"  class="nav-sub-tab ' . ($active_subpage == $subpage_slug ? 'current' : '') . '">' . esc_html( $subpage_name ) . '</a></li>';
				}
                echo '</ul>';
			}
		}
	}

    if( !empty( $pages['sub-tabs'] ) ) {
        foreach ($pages['sub-tabs'] as $parent_slug => $tabs) {
            if ( $active_subpage == $parent_slug) {
                echo '<ul class="wppb-subtabs subsubsub cozmoslabs-nav-sub-tab-wrapper">';
                //determine the active tab, if no tab present then default to the first one
                if( isset($_GET['tab']) )
                    $active_tab = sanitize_text_field( $_GET['tab'] );
                else {
                    $keys = array_keys($tabs);
                    $active_tab = array_shift( $keys );
                }
                foreach ($tabs as $tab_slug => $tab_name) {
                    echo '<li><a href="' . esc_url( add_query_arg( array('tab' => $tab_slug) ) ) . '"  class="nav-sub-tab ' . ( $active_tab == $tab_slug ? 'current' : '') . '">' . esc_html( $tab_name ) . '</a></li>';
                }
                echo '</ul>';
            }
        }
    }
}

/**
 * Function that creates the "General Settings" submenu page
 *
 * @since v.2.0
 *
 * @return void
 */
function wppb_register_general_settings_submenu_page() {
	add_submenu_page( 'profile-builder', __( 'Settings', 'profile-builder' ), __( 'Settings', 'profile-builder' ), 'manage_options', 'profile-builder-general-settings', 'wppb_general_settings_content' );
}
add_action( 'admin_menu', 'wppb_register_general_settings_submenu_page', 3 );


function wppb_generate_default_settings_defaults(){
	add_option( 'wppb_general_settings', array( 'extraFieldsLayout' => 'default', 'automaticallyLogIn' => 'No', 'emailConfirmation' => 'no', 'activationLandingPage' => '', 'adminApproval' => 'no', 'loginWith' => 'usernameemail', 'rolesEditor' => 'no', 'conditional_fields_ajax' => 'no', 'formsDesign' => 'form-style-default', 'hide_admin_bar_for' => '' ) );
}


/**
 * Function that adds content to the "General Settings" submenu page
 *
 * @since v.2.0
 *
 * @return string
 */
function wppb_general_settings_content() {
	wppb_generate_default_settings_defaults();
?>
	<div class="wrap wppb-wrap cozmoslabs-wrap">

        <h1></h1>
        <!-- WordPress Notices are added after the h1 tag -->

        <div class="cozmoslabs-page-header">
            <div class="cozmoslabs-section-title">

                <h2 class="cozmoslabs-page-title">
                    <?php esc_html_e( 'Profile Builder Settings', 'profile-builder' ); ?>
                    <a href="https://www.cozmoslabs.com/docs/profile-builder/general-settings/?utm_source=wpbackend&utm_medium=pb-documentation&utm_campaign=PBDocs" target="_blank" data-code="f223" class="wppb-docs-link dashicons dashicons-editor-help"></a>
                </h2>

            </div>
        </div>

        <?php settings_errors(); ?>

		<?php wppb_generate_settings_tabs(); ?>

        <?php wppb_load_necessary_scripts(); ?>

        <div class="cozmoslabs-settings-container">

            <?php if ( !is_multisite() && defined( 'WPPB_PAID_PLUGIN_DIR' ) && defined( 'PROFILE_BUILDER_PAID_VERSION' ) ) : ?>
                <div class="cozmoslabs-form-subsection-wrapper" id="wppb-register-version">
                    <?php wppb_add_register_version_form(); ?>
                </div>
            <?php endif; ?>

            <form method="post" action="options.php#general-settings">
                <?php $wppb_generalSettings = get_option( 'wppb_general_settings' ); ?>
                <?php settings_fields( 'wppb_general_settings' ); ?>

                <div class="cozmoslabs-settings">

                    <div class="cozmoslabs-form-subsection-wrapper" id="wppb-form_desings">
                        <h4 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Design & User Experience', 'profile-builder' ); ?></h4>
                        <p class="cozmoslabs-description" style="margin-bottom: 5px;"><?php esc_html_e( 'Choose a style that better suits your website.', 'profile-builder' ); ?></p>
                        <p class="cozmoslabs-description"><?php esc_html_e( 'The default style is there to let you customize the CSS and in general will receive the look and feel from your own theme’s styling.', 'profile-builder' ); ?></p>

                        <div class="cozmoslabs-form-field-wrapper">
                            <?php
                            if ( defined( 'WPPB_PAID_PLUGIN_DIR' ) && file_exists( WPPB_PAID_PLUGIN_DIR.'/features/form-designs/form-designs.php' ) ) {
                                 echo wppb_render_forms_design_selector(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            }
                            elseif ( PROFILE_BUILDER == 'Profile Builder Free' ) {
                                echo wppb_display_form_designs_preview(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            }
                            ?>
                        </div>
                    </div>

                    <div class="cozmoslabs-form-subsection-wrapper">
                        <h4 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Optimize The login and Registration flow for your members', 'profile-builder' ); ?></h4>

                        <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                            <label class="cozmoslabs-form-field-label" for="wppb_settings_automatically_log_in"><?php esc_html_e('Automatically Log In', 'profile-builder'); ?></label>

                            <div class="cozmoslabs-toggle-container">
                                <input type="checkbox" name="wppb_general_settings[automaticallyLogIn]" id="wppb_settings_automatically_log_in" value="Yes" <?php echo (!empty($wppb_generalSettings['automaticallyLogIn']) && $wppb_generalSettings['automaticallyLogIn'] === 'Yes') ? 'checked' : ''; ?> >
                                <label class="cozmoslabs-toggle-track" for="wppb_settings_automatically_log_in"></label>
                            </div>

                            <div class="cozmoslabs-toggle-description">
                                <label for="wppb_settings_automatically_log_in" class="cozmoslabs-description"><?php esc_html_e( 'Enable to automatically log in new users after successful registration.', 'profile-builder' ); ?></label>
                            </div>
                        </div>

                        <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                            <label class="cozmoslabs-form-field-label" for="wppb_settings_email_confirmation"><?php esc_html_e('Email Confirmation', 'profile-builder'); ?></label>


                            <div class="cozmoslabs-toggle-container">

                                <input type="checkbox" name="wppb_general_settings[emailConfirmation]" id="wppb_settings_email_confirmation" value="yes" <?php echo (!empty($wppb_generalSettings['emailConfirmation']) && $wppb_generalSettings['emailConfirmation'] === 'yes') ? 'checked' : ''; ?> >
                                <label class="cozmoslabs-toggle-track" for="wppb_settings_email_confirmation"></label>

                            </div>
                            <div class="cozmoslabs-toggle-description">
                                <label for="wppb_settings_email_confirmation" class="cozmoslabs-description"><?php  esc_html_e( 'This works with front-end forms only. Recommended to redirect WP default registration to a Profile Builder one using "Custom Redirects" module.', 'profile-builder' ); ?></label>
                            </div>

                            <p class="cozmoslabs-description cozmoslabs-description-space-left" id="unconfirmed-user-emails"><?php  printf( esc_html__( 'You can find a list of unconfirmed email addresses %1$sUsers > All Users > Email Confirmation%2$s.', 'profile-builder' ), '<a href="'. esc_url( get_bloginfo( 'url' ) ).'/wp-admin/users.php?page=unconfirmed_emails">', '</a>' )?></p>
                        </div>

                        <div class="cozmoslabs-form-field-wrapper" id="wppb-settings-activation-page">
                            <label class="cozmoslabs-form-field-label" for="wppb_settings_email_confirmation_page"><?php esc_html_e('Email Confirmation Page', 'profile-builder'); ?></label>

                            <select name="wppb_general_settings[activationLandingPage]" class="wppb-select" id="wppb_settings_email_confirmation_page">
                                <option value="" <?php if ( empty( $wppb_generalSettings['emailConfirmation'] ) ) echo 'selected'; ?>></option>
                                <optgroup label="<?php esc_html_e( 'Existing Pages', 'profile-builder' ); ?>">
                                    <?php
                                    $pages = get_pages( apply_filters( 'wppb_page_args_filter', array( 'sort_order' => 'ASC', 'sort_column' => 'post_title', 'post_type' => 'page', 'post_status' => array( 'publish' ) ) ) );

                                    foreach ( $pages as $key => $value ){
                                        echo '<option value="'.esc_attr( $value->ID ).'"';
                                        if ( $wppb_generalSettings['activationLandingPage'] == $value->ID )
                                            echo ' selected';

                                        echo '>' . esc_html( $value->post_title ) . '</option>';
                                    }
                                    ?>
                                </optgroup>
                            </select>

                            <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'Specify the page where the users will be directed when confirming the email account. This page can differ from the register page(s) and can be changed at any time.', 'profile-builder' ); ?></p>
                        </div>

                        <?php
                        if ( PROFILE_BUILDER == 'Profile Builder Free' ) {
                            ?>

                            <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                                <label class="cozmoslabs-form-field-label"><?php esc_html_e('Admin Approval', 'profile-builder'); ?></label>

                                <p class="cozmoslabs-description cozmoslabs-description-align-right cozmoslabs-description-upsell">
                                    <?php echo esc_html__( 'You decide who is a user on your website. Get notified via email or approve multiple users at once from the WordPress UI.', 'profile-builder' ); ?><br>
                                    <?php printf( esc_html__( 'Enable Admin Approval by upgrading to %1$sBasic or PRO versions%2$s.', 'profile-builder' ),'<a href="https://www.cozmoslabs.com/wordpress-profile-builder/?utm_source=pb-general-settings&utm_medium=client-site&utm_campaign=pb-admin-approval#pricing">', '</a>' )?>
                                </p>
                            </div>

                        <?php } ?>


                        <?php
                        if ( defined( 'WPPB_PAID_PLUGIN_DIR' ) && file_exists( WPPB_PAID_PLUGIN_DIR.'/features/admin-approval/admin-approval.php' ) ){
                            ?>

                            <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                                <label class="cozmoslabs-form-field-label" for="adminApprovalSelect"><?php esc_html_e('Admin Approval', 'profile-builder'); ?></label>

                                <div class="cozmoslabs-toggle-container">
                                    <input type="checkbox" name="wppb_general_settings[adminApproval]" id="adminApprovalSelect" value="yes" <?php echo (!empty($wppb_generalSettings['adminApproval']) && $wppb_generalSettings['adminApproval'] === 'yes') ? 'checked' : ''; ?> >
                                    <label class="cozmoslabs-toggle-track" for="adminApprovalSelect"></label>
                                </div>

                                <div class="cozmoslabs-toggle-description">
                                    <label for="adminApprovalSelect" class="cozmoslabs-description wppb-aa-details"><?php esc_html_e( 'Each user that registers on the website will be given a Pending status and will need to be approved or unapproved by the admin before he/she can login.', 'profile-builder' ); ?></label>
                                </div>

                                <p class="cozmoslabs-description cozmoslabs-description-space-left wppb-aa-user-list"><?php printf( esc_html__( 'You can find a list of users at %1$sUsers > All Users > Admin Approval%2$s.', 'profile-builder' ), '<a href="'. esc_url( get_bloginfo( 'url' ) ).'/wp-admin/users.php?page=admin_approval&orderby=registered&order=desc">', '</a>' )?></p>
                            </div>

                            <div class="cozmoslabs-form-field-wrapper wppb-aa-user-list">
                                <label class="cozmoslabs-form-field-label" for="adminApprovalOnUserRoleSelect"><?php esc_html_e('Admin Approval User Role', 'profile-builder'); ?></label>

                                <select name="wppb_general_settings[adminApprovalOnUserRole][]" class="wppb-select wppb-select2" multiple>
                                    <?php
                                    $wppb_userRoles = wppb_adminApproval_onUserRole();

                                    if( ! empty( $wppb_userRoles ) ) {
                                        foreach( $wppb_userRoles as $role => $role_name ) {

                                            echo '<option value="' . esc_attr( $role )  . '"' . (( !empty( $wppb_generalSettings['adminApprovalOnUserRole'] ) && in_array( $role, $wppb_generalSettings['adminApprovalOnUserRole'] ) ) || empty( $wppb_generalSettings['adminApprovalOnUserRole']) ? ' selected' : '') . '>' . esc_html( $role_name ) . '</option>';

                                        }
                                    }
                                    ?>
                                </select>
                            </div>

                        <?php } ?>

                        <div class="cozmoslabs-form-field-wrapper">
                            <label class="cozmoslabs-form-field-label" for="loginWithSelect"><?php esc_html_e( 'Allow Users to Log in With:', 'profile-builder' ); ?></label>

                            <select name="wppb_general_settings[loginWith]" class="wppb-select" id="loginWithSelect">
                                <option value="usernameemail" <?php if ( $wppb_generalSettings['loginWith'] == 'usernameemail' ) echo 'selected'; ?>><?php esc_html_e( 'Username and Email', 'profile-builder' ); ?></option>
                                <option value="username" <?php if ( $wppb_generalSettings['loginWith'] == 'username' ) echo 'selected'; ?>><?php esc_html_e( 'Username', 'profile-builder' ); ?></option>
                                <option value="email" <?php if ( $wppb_generalSettings['loginWith'] == 'email' ) echo 'selected'; ?>><?php esc_html_e( 'Email', 'profile-builder' ); ?></option>
                            </select>

                            <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'Choose what the user will be logging in with.', 'profile-builder' ); ?></p>
                        </div>

                    </div>

                    <div class="cozmoslabs-form-subsection-wrapper">
                        <h4 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Security', 'profile-builder' ); ?></h4>

                        <div class="cozmoslabs-form-field-wrapper">
                            <label class="cozmoslabs-form-field-label" for="minimumPasswordLength"><?php esc_html_e( 'Minimum Password Length', 'profile-builder' ); ?></label>
                            <input type="text" name="wppb_general_settings[minimum_password_length]" class="wppb-text" id="minimumPasswordLength" value="<?php if( !empty( $wppb_generalSettings['minimum_password_length'] ) ) echo esc_attr( $wppb_generalSettings['minimum_password_length'] ); ?>"/>

                            <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'Enter the minimum characters the password should have. Leave empty for no minimum limit', 'profile-builder' ); ?></p>
                        </div>


                        <div class="cozmoslabs-form-field-wrapper">
                            <label class="cozmoslabs-form-field-label" for="minimumPasswordStrength"><?php esc_html_e( 'Minimum Password Strength', 'profile-builder' ); ?></label>

                            <select name="wppb_general_settings[minimum_password_strength]" class="wppb-select" id="minimumPasswordStrength">
                                <option value=""><?php esc_html_e( 'Disabled', 'profile-builder' ); ?></option>
                                <option value="short" <?php if ( !empty($wppb_generalSettings['minimum_password_strength']) && $wppb_generalSettings['minimum_password_strength'] == 'short' ) echo 'selected'; ?>><?php esc_html_e( 'Very weak', 'profile-builder' ); ?></option>
                                <option value="bad" <?php if ( !empty($wppb_generalSettings['minimum_password_strength']) && $wppb_generalSettings['minimum_password_strength'] == 'bad' ) echo 'selected'; ?>><?php esc_html_e( 'Weak', 'profile-builder' ); ?></option>
                                <option value="good" <?php if ( !empty($wppb_generalSettings['minimum_password_strength']) && $wppb_generalSettings['minimum_password_strength'] == 'good' ) echo 'selected'; ?>><?php esc_html_e( 'Medium', 'profile-builder' ); ?></option>
                                <option value="strong" <?php if ( !empty($wppb_generalSettings['minimum_password_strength']) && $wppb_generalSettings['minimum_password_strength'] == 'strong' ) echo 'selected'; ?>><?php esc_html_e( 'Strong', 'profile-builder' ); ?></option>
                            </select>

                            <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'A stronger password strength will probably force the user to not reuse passwords from other websites.', 'profile-builder' ); ?></p>
                        </div>


                        <div class="cozmoslabs-form-field-wrapper">
                            <label class="cozmoslabs-form-field-label" for="lostPasswordPage"><?php esc_html_e( 'Password Recovery Page', 'profile-builder' ); ?></label>

                            <select name="wppb_general_settings[lost_password_page]" class="wppb-select" id="lostPasswordPage">
                                <option value=""> <?php esc_html_e( 'None', 'profile-builder' ); ?></option>
                                <?php
                                $args = array(
                                    'post_type' => 'page',
                                    'post_status' => 'publish',
                                    'numberposts' => -1,
                                    'orderby' => 'name',
                                    'order' => 'ASC'
                                );
                                $pages = get_posts( $args );

                                foreach ( $pages as $key => $value ){
                                    echo '<option value="'.esc_attr( $value->guid ).'"';
                                    if ( isset( $wppb_generalSettings['lost_password_page'] ) && $wppb_generalSettings['lost_password_page'] == $value->guid )
                                        echo ' selected';

                                    echo '>' . esc_html( $value->post_title ) . '</option>';
                                }
                                ?>
                            </select>

                            <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'Select the page which contains the "[wppb-recover-password]" shortcode.', 'profile-builder' ); ?></p>
                        </div>
                    </div>

                    <div class="cozmoslabs-form-subsection-wrapper">
                        <h4 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Two-Factor Authentication', 'profile-builder' ); ?></h4>

                        <?php if( defined( 'WPPB_PAID_PLUGIN_DIR' ) && file_exists( WPPB_PAID_PLUGIN_DIR . '/features/two-factor-authentication/class-two-factor-authentication.php' ) ) : ?>

                            <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                                <?php
                                $wppb_two_factor_authentication_settings = get_option( 'wppb_two_factor_authentication_settings', 'not_found' );

                                $enabled = 'no';
                                if ( $wppb_two_factor_authentication_settings !== 'not_found' && is_array( $wppb_two_factor_authentication_settings ) && isset( $wppb_two_factor_authentication_settings['enabled'] ) ) {
                                    $enabled = $wppb_two_factor_authentication_settings['enabled'];
                                }
                                ?>

                                <label class="cozmoslabs-form-field-label" for="wppb-auth-enable"><?php esc_html_e('Two-Factor Authentication', 'profile-builder'); ?></label>

                                <div class="cozmoslabs-toggle-container">
                                    <input type="checkbox" name="wppb_two_factor_authentication_settings[enabled]" id="wppb-auth-enable" value="yes" <?php echo ($enabled === 'yes') ? 'checked' : ''; ?> >
                                    <label class="cozmoslabs-toggle-track" for="wppb-auth-enable"></label>
                                </div>

                                <?php
                                $current_user = wp_get_current_user();
                                ?>

                                <script type="text/javascript">
                                jQuery(document).ready(function($) {
                                    // Handle 2FA enable toggle
                                    $('#wppb-auth-enable').on('change', function() {
                                        if ($(this).is(':checked')) {
                                            $('#wppb-auth-roles-selector').show();
                                            $('#wppb-force-2fa-enable').closest('.cozmoslabs-form-field-wrapper').show();
                                            $('#wppb-2fa-require-totp-profile-edit').show();
                                            if ($('#wppb-force-2fa-enable').is(':checked')) {
                                                $('#wppb-force-2fa-roles-selector').show();
                                                $('#wppb-force-2fa-grace-period').show();
                                                $('#wppb-force-2fa-edit-profile-page-selector').show();

                                            }
                                        } else {
                                            $('#wppb-auth-roles-selector').hide();
                                            $('#wppb-force-2fa-enable').closest('.cozmoslabs-form-field-wrapper').hide();
                                            $('#wppb-force-2fa-roles-selector').hide();
                                            $('#wppb-force-2fa-grace-period').hide();
                                            $('#wppb-force-2fa-edit-profile-page-selector').hide();

                                            $('#wppb-2fa-require-totp-profile-edit').hide();
                                        }
                                    });

                                    // Handle force 2FA enable toggle
                                    $('#wppb-force-2fa-enable').on('change', function() {
                                        if ($(this).is(':checked')) {
                                            $('#wppb-force-2fa-roles-selector').show();
                                            $('#wppb-force-2fa-grace-period').show();
                                            $('#wppb-force-2fa-edit-profile-page-selector').show();

                                        } else {
                                            $('#wppb-force-2fa-roles-selector').hide();
                                            $('#wppb-force-2fa-grace-period').hide();
                                            $('#wppb-force-2fa-edit-profile-page-selector').hide();

                                        }
                                    });

                                    // Handle changes to "Enable Authenticator For" roles
                                    $('#wppb-auth-enable-roles').on('change', function() {
                                        updateForce2FARolesDropdown();
                                    });

                                    // Function to update the Force 2FA roles dropdown based on enabled roles
                                    function updateForce2FARolesDropdown() {
                                        var enabledRoles = $('#wppb-auth-enable-roles').val() || [];
                                        var force2FARolesSelect = $('#wppb-force-2fa-roles');
                                        var currentlySelected = force2FARolesSelect.val() || [];
                                        
                                        // Clear current options
                                        force2FARolesSelect.empty();
                                        
                                        // Get all available roles from the original select
                                        var allRoleOptions = $('#wppb-auth-enable-roles option');
                                        
                                        // If '*' (all roles) is selected, show all roles
                                        if (enabledRoles.indexOf('*') !== -1) {
                                            allRoleOptions.each(function() {
                                                var roleValue = $(this).val();
                                                if (roleValue !== '*') { // Skip the "ALL ROLES" option
                                                    var option = $('<option></option>')
                                                        .attr('value', roleValue)
                                                        .text($(this).text());
                                                    if (currentlySelected.indexOf(roleValue) !== -1) {
                                                        option.prop('selected', true);
                                                    }
                                                    force2FARolesSelect.append(option);
                                                }
                                            });
                                        } else {
                                            // Only show roles that are specifically enabled for 2FA
                                            enabledRoles.forEach(function(roleKey) {
                                                allRoleOptions.each(function() {
                                                    if ($(this).val() === roleKey) {
                                                        var option = $('<option></option>')
                                                            .attr('value', roleKey)
                                                            .text($(this).text());
                                                        if (currentlySelected.indexOf(roleKey) !== -1) {
                                                            option.prop('selected', true);
                                                        }
                                                        force2FARolesSelect.append(option);
                                                    }
                                                });
                                            });
                                        }
                                        
                                        // Trigger select2 update
                                        force2FARolesSelect.trigger('change');
                                    }

                                    // Initial state
                                    $('#wppb-auth-enable').trigger('change');
                                    updateForce2FARolesDropdown();

                                    // Force 2FA Confirmation Logic
                                    var currentUserRoles = <?php echo json_encode( $current_user->roles ); ?>;
                                    var currentUser2FAEnabled = <?php echo ( get_user_option( 'wppb_auth_enabled', $current_user->ID ) === 'enabled' ) ? 'true' : 'false'; ?>;

                                    // Store initial state
                                    var initialForce2FAEnabled = $('#wppb-force-2fa-enable').is(':checked');
                                    var initialMain2FAEnabled = $('#wppb-auth-enable').is(':checked');
                                    var initialSelectedRoles = $('#wppb-force-2fa-roles').val() || [];
                                    initialSelectedRoles.sort();

                                    // Create Modal
                                    $('body').append(
                                        '<div id="wppb-force-2fa-modal" style="display:none; position:fixed; z-index:10000; left:0; top:0; width:100%; height:100%; overflow:auto; background-color:rgba(0,0,0,0.4);">' +
                                            '<div style="background-color:#fefefe; margin:15% auto; padding:20px; border:1px solid #888; width:500px; max-width:90%; border-radius:4px; box-shadow:0 4px 6px rgba(0,0,0,0.1);">' +
                                                '<h3 style="margin-top:0;">' + <?php echo json_encode( esc_html__( 'Confirm Two-Factor Authentication Enforcement', 'profile-builder' ) ); ?> + '</h3>' +
                                                '<div id="wppb-force-2fa-message"></div>' +
                                                '<div style="margin-top:20px; text-align:right;">' +
                                                    '<button type="button" id="wppb-force-2fa-cancel" class="button">' + <?php echo json_encode( esc_html__( 'Cancel', 'profile-builder' ) ); ?> + '</button> ' +
                                                    '<button type="button" id="wppb-force-2fa-confirm" class="button button-primary">' + <?php echo json_encode( esc_html__( 'Confirm & Save', 'profile-builder' ) ); ?> + '</button>' +
                                                '</div>' +
                                            '</div>' +
                                        '</div>'
                                    );

                                    // Intercept form submission
                                    $('form').on('submit', function(e) {
                                        // Only check if we are on the general settings page and submitting the main form
                                        if ($(this).find('input[name="option_page"]').val() !== 'wppb_general_settings') {
                                            return;
                                        }

                                        var force2FAEnabled = $('#wppb-force-2fa-enable').is(':checked');
                                        var main2FAEnabled = $('#wppb-auth-enable').is(':checked');
                                        var selectedRoles = $('#wppb-force-2fa-roles').val() || [];
                                        selectedRoles.sort();

                                        // Check if settings have changed
                                        var settingsChanged = false;
                                        
                                        // Case 1: Force 2FA Enabled status changed
                                        if (force2FAEnabled !== initialForce2FAEnabled) {
                                            settingsChanged = true;
                                        }
                                        
                                        // Case 2: Roles selection changed (only if Force 2FA is enabled)
                                        if (force2FAEnabled) {
                                            if (selectedRoles.length !== initialSelectedRoles.length) {
                                                settingsChanged = true;
                                            } else {
                                                for (var i = 0; i < selectedRoles.length; i++) {
                                                    if (selectedRoles[i] !== initialSelectedRoles[i]) {
                                                        settingsChanged = true;
                                                        break;
                                                    }
                                                }
                                            }
                                        }

                                        // Case 3: Main 2FA enabled status changed (only if Force 2FA is enabled)
                                        // This covers the case where Force 2FA was already checked but inactive because Main 2FA was off
                                        if (force2FAEnabled && main2FAEnabled !== initialMain2FAEnabled) {
                                            settingsChanged = true;
                                        }

                                        // Only show popup if Force 2FA is enabled AND Main 2FA is enabled AND settings have changed
                                        if (force2FAEnabled && main2FAEnabled && settingsChanged) {
                                            // Check if we already confirmed
                                            if ($(this).data('force-2fa-confirmed')) {
                                                return;
                                            }

                                            var selectedRolesText = [];
                                            
                                            $('#wppb-force-2fa-roles option:selected').each(function() {
                                                selectedRolesText.push($(this).text());
                                            });

                                            if (selectedRoles.length > 0) {
                                                e.preventDefault();
                                                var form = $(this);

                                                var message = '<p>' + <?php echo json_encode( esc_html__( 'You are enforcing 2FA for the following user roles:', 'profile-builder' ) ); ?> + ' <strong>' + selectedRolesText.join(', ') + '</strong>.</p>';
                                                message += '<p>' + <?php echo json_encode( esc_html__( 'Users with these roles will be required to set up 2FA on their next login.', 'profile-builder' ) ); ?> + '</p>';

                                                // Check if current user is affected
                                                var userAffected = false;
                                                for (var i = 0; i < currentUserRoles.length; i++) {
                                                    if (selectedRoles.indexOf(currentUserRoles[i]) !== -1) {
                                                        userAffected = true;
                                                        break;
                                                    }
                                                }

                                                if (userAffected && !currentUser2FAEnabled) {
                                                    message += '<div class="notice notice-warning inline" style="margin: 10px 0; padding: 10px; display: block !important;"><p><strong>' + <?php echo json_encode( esc_html__( 'Warning:', 'profile-builder' ) ); ?> + '</strong> ' +
                                                               <?php echo json_encode( esc_html__( 'Based on the configured settings, your account is also required to set up 2FA. Once you confirm this change, you will be redirected to set it up.', 'profile-builder' ) ); ?> + '</p></div>';
                                                }

                                                $('#wppb-force-2fa-message').html(message);
                                                $('#wppb-force-2fa-modal').show();

                                                // Handle Confirm
                                                $('#wppb-force-2fa-confirm').off('click').on('click', function() {
                                                    $('#wppb-force-2fa-modal').hide();
                                                    form.data('force-2fa-confirmed', true);
                                                    form.submit();
                                                });

                                                // Handle Cancel
                                                $('#wppb-force-2fa-cancel').off('click').on('click', function() {
                                                    $('#wppb-force-2fa-modal').hide();
                                                });
                                            }
                                        }
                                    });
                                });
                                </script>

                                <div class="cozmoslabs-toggle-description">
                                    <label for="wppb-auth-enable" class="cozmoslabs-description"><?php esc_html_e( 'Enable the Google Authenticator functionality.', 'profile-builder' ); ?></label>
                                </div>
                            </div>

                            <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch" id="wppb-2fa-require-totp-profile-edit" <?php echo $enabled === 'no' ? 'style="display: none;"' : '' ?> >
                                <?php
                                $require_totp_profile_edit = 'yes';
                                if ( $wppb_two_factor_authentication_settings !== 'not_found' && is_array( $wppb_two_factor_authentication_settings ) && isset( $wppb_two_factor_authentication_settings['require_totp_profile_edit'] ) ) {
                                    $require_totp_profile_edit = $wppb_two_factor_authentication_settings['require_totp_profile_edit'];
                                }
                                ?>


                                <label class="cozmoslabs-form-field-label" for="wppb-require-totp-profile-edit"><?php esc_html_e('Require TOTP Verification on Profile Edit', 'profile-builder'); ?></label>

                                <div class="cozmoslabs-toggle-container">
                                    <input type="checkbox" name="wppb_two_factor_authentication_settings[require_totp_profile_edit]" id="wppb-require-totp-profile-edit" value="yes" <?php echo ($require_totp_profile_edit === 'yes') ? 'checked' : ''; ?> >
                                    <label class="cozmoslabs-toggle-track" for="wppb-require-totp-profile-edit"></label>
                                </div>

                                <div class="cozmoslabs-toggle-description">
                                    <label for="wppb-require-totp-profile-edit" class="cozmoslabs-description"><?php esc_html_e( 'Require users with Two-Factor Authentication enabled to verify their TOTP code every time they edit their profile. This setting is enabled by default.', 'profile-builder' ); ?></label>
                                </div>
                            </div>

                            <div class="cozmoslabs-form-field-wrapper" id="wppb-auth-roles-selector" <?php echo $enabled === 'no' ? 'style="display: none;"' : '' ?> >
                                <?php
                                $roles = get_editable_roles( );
                                $network_roles = array( );
                                if ( !empty( $wppb_two_factor_authentication_settings['roles'] ) )
                                    $network_roles = is_array( $wppb_two_factor_authentication_settings['roles'] ) ? $wppb_two_factor_authentication_settings['roles'] : array( $wppb_two_factor_authentication_settings['roles'] );
                                ?>

                                <label class="cozmoslabs-form-field-label" for="wppb-auth-enable-roles"><?php esc_html_e( 'Enable Authenticator For', 'profile-builder' ); ?></label>

                                <select name="wppb_two_factor_authentication_settings[roles][]" id="wppb-auth-enable-roles" class="wppb-select wppb-select2" multiple>
                                    <?php
                                    echo '<option value="*"' . (in_array('*', $network_roles, true) ? ' selected' : '') . '>'. esc_html__('ALL ROLES', 'profile-builder') .'</option>';

                                    foreach ($roles as $role_key => $role) {
                                        echo '<option value="' . esc_attr($role_key) . '"' . (in_array($role_key, $network_roles, true) ? ' selected' : '') . '>' . esc_html($role['name']) . '</option>';
                                    }
                                    ?>
                                </select>

                                <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( '"ALL ROLES" - Two-Factor Authentication will be enabled for all user roles.', 'profile-builder' ); ?></p>
                            </div>

                            <hr style="margin: 20px 0; border: 0; border-top: 1px solid #ddd;">

                            <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                                <?php
                                $force_2fa_enabled = 'no';
                                if ( $wppb_two_factor_authentication_settings !== 'not_found' && is_array( $wppb_two_factor_authentication_settings ) && isset( $wppb_two_factor_authentication_settings['force_2fa_enabled'] ) ) {
                                    $force_2fa_enabled = $wppb_two_factor_authentication_settings['force_2fa_enabled'];
                                }
                                ?>

                                <label class="cozmoslabs-form-field-label" for="wppb-force-2fa-enable"><?php esc_html_e('Force 2FA for Selected Roles', 'profile-builder'); ?></label>

                                <div class="cozmoslabs-toggle-container">
                                    <input type="checkbox" name="wppb_two_factor_authentication_settings[force_2fa_enabled]" id="wppb-force-2fa-enable" value="yes" <?php echo ($force_2fa_enabled === 'yes') ? 'checked' : ''; ?> >
                                    <label class="cozmoslabs-toggle-track" for="wppb-force-2fa-enable"></label>
                                </div>

                                <div class="cozmoslabs-toggle-description">
                                    <label for="wppb-force-2fa-enable" class="cozmoslabs-description"><?php esc_html_e( 'Force users with selected roles to set up Two-Factor Authentication. Users will be redirected to their profile page if 2FA is not configured.', 'profile-builder' ); ?></label>
                                </div>
                            </div>

                            <div class="cozmoslabs-form-field-wrapper" id="wppb-force-2fa-roles-selector" <?php echo $force_2fa_enabled === 'no' ? 'style="display: none;"' : '' ?> >
                                <?php
                                $force_2fa_roles = array( );
                                if ( !empty( $wppb_two_factor_authentication_settings['force_2fa_roles'] ) )
                                    $force_2fa_roles = is_array( $wppb_two_factor_authentication_settings['force_2fa_roles'] ) ? $wppb_two_factor_authentication_settings['force_2fa_roles'] : array( $wppb_two_factor_authentication_settings['force_2fa_roles'] );
                                ?>

                                <label class="cozmoslabs-form-field-label" for="wppb-force-2fa-roles"><?php esc_html_e( 'Force 2FA For', 'profile-builder' ); ?></label>

                                <select name="wppb_two_factor_authentication_settings[force_2fa_roles][]" id="wppb-force-2fa-roles" class="wppb-select wppb-select2" multiple>
                                    <?php
                                    // Only show roles that are enabled for 2FA
                                    $enabled_roles = isset( $wppb_two_factor_authentication_settings['roles'] ) ? 
                                                   $wppb_two_factor_authentication_settings['roles'] : array();
                                    
                                    // If '*' (all roles) is selected, show all roles
                                    if ( in_array( '*', $enabled_roles, true ) ) {
                                        foreach ($roles as $role_key => $role) {
                                            echo '<option value="' . esc_attr($role_key) . '"' . (in_array($role_key, $force_2fa_roles, true) ? ' selected' : '') . '>' . esc_html($role['name']) . '</option>';
                                        }
                                    } else {
                                        // Only show roles that are specifically enabled for 2FA
                                        foreach ($enabled_roles as $enabled_role) {
                                            if ( isset( $roles[$enabled_role] ) ) {
                                                echo '<option value="' . esc_attr($enabled_role) . '"' . (in_array($enabled_role, $force_2fa_roles, true) ? ' selected' : '') . '>' . esc_html($roles[$enabled_role]['name']) . '</option>';
                                            }
                                        }
                                    }
                                    ?>
                                </select>

                                <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'Select which user roles will be forced to set up Two-Factor Authentication. Only roles that have 2FA enabled above are available.', 'profile-builder' ); ?></p>
                            </div>

                            <div class="cozmoslabs-form-field-wrapper" id="wppb-force-2fa-grace-period" <?php echo $force_2fa_enabled === 'no' ? 'style="display: none;"' : '' ?> >
                                <?php
                                $grace_period_days = 0;
                                if ( !empty( $wppb_two_factor_authentication_settings['grace_period_days'] ) )
                                    $grace_period_days = intval( $wppb_two_factor_authentication_settings['grace_period_days'] );
                                ?>

                                <label class="cozmoslabs-form-field-label" for="wppb-force-2fa-grace-period-days"><?php esc_html_e( 'Grace Period', 'profile-builder' ); ?></label>

                                <input type="number" name="wppb_two_factor_authentication_settings[grace_period_days]" id="wppb-force-2fa-grace-period-days" class="wppb-text" min="0" max="365" value="<?php echo esc_attr( $grace_period_days ); ?>" />

                                <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'Number of days users have to set up Two-Factor Authentication. Set to 0 for immediate enforcement.', 'profile-builder' ); ?></p>
                            </div>

                            <div class="cozmoslabs-form-field-wrapper" id="wppb-force-2fa-edit-profile-page-selector" <?php echo $force_2fa_enabled === 'no' ? 'style="display: none;"' : '' ?> >
                                <?php
                                $edit_profile_page_id = 'admin';
                                if ( !empty( $wppb_two_factor_authentication_settings['edit_profile_page_id'] ) ) {
                                    $edit_profile_page_id = $wppb_two_factor_authentication_settings['edit_profile_page_id'];
                                }

                                // Get pages with Edit Profile shortcode or block
                                if ( defined( 'WPPB_PAID_PLUGIN_DIR' ) && file_exists( WPPB_PAID_PLUGIN_DIR . '/features/two-factor-authentication/class-two-factor-authentication.php' ) ) {
                                    require_once( WPPB_PAID_PLUGIN_DIR . '/features/two-factor-authentication/class-two-factor-authentication.php' );
                                    $edit_profile_pages = WPPB_Two_Factor_Authenticator::get_edit_profile_pages();
                                } else {
                                    $edit_profile_pages = array();
                                }
                                ?>

                                <label class="cozmoslabs-form-field-label" for="wppb-force-2fa-edit-profile-page"><?php esc_html_e( 'Redirect to Edit Profile Page', 'profile-builder' ); ?></label>

                                <select name="wppb_two_factor_authentication_settings[edit_profile_page_id]" id="wppb-force-2fa-edit-profile-page" class="wppb-select">
                                    <option value="admin" <?php selected( $edit_profile_page_id, 'admin' ); ?>><?php esc_html_e( 'Admin Profile Page', 'profile-builder' ); ?></option>
                                    <?php
                                    if ( !empty( $edit_profile_pages ) ) {
                                        foreach ( $edit_profile_pages as $page ) {
                                            echo '<option value="' . esc_attr( $page['ID'] ) . '" ' . selected( $edit_profile_page_id, $page['ID'], false ) . '>' . esc_html( $page['title'] ) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>

                                <div class="cozmoslabs-description cozmoslabs-description-space-left">
                                    <?php 
                                    if ( empty( $edit_profile_pages ) ) {
                                        echo '<div class="cozmoslabs-description cozmoslabs-description-upsell" style="margin-top: 10px;">' . esc_html__( 'No pages with Edit Profile forms found. Please create a page with the [wppb-edit-profile] shortcode or block.', 'profile-builder' ) . '</div>';
                                    } else {
                                        esc_html_e( 'Select which page users should be redirected to when they need to set up Two-Factor Authentication.', 'profile-builder' );
                                    }
                                    ?>
                                </div>
                            </div>



                        <?php else : ?>

                            <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">

                                <label class="cozmoslabs-form-field-label" for="wppb-2fa-enable"><?php esc_html_e('Two-Factor Authentication', 'profile-builder'); ?></label>

                                <div class="cozmoslabs-toggle-container">
                                    <input type="checkbox" name="wppb_two_factor_authentication_free" id="wppb-2fa-enable" value="yes">
                                    <label class="cozmoslabs-toggle-track" for="wppb-2fa-enable"></label>
                                </div>

                                <div class="cozmoslabs-toggle-description">
                                    <label for="wppb-2fa-enable" class="cozmoslabs-description"><?php esc_html_e( 'Enable the Google Authenticator functionality.', 'profile-builder' ); ?></label>
                                </div>

                                <p class="cozmoslabs-description cozmoslabs-description-upsell" id="wppb-2fa-upgrade-notice" style="display: none;">
                                    <?php printf( esc_html__( 'Increase the security of your user accounts with 2 Factor Authentication by upgrading to %1$sBasic or Pro%2$s versions.', 'profile-builder' ), '<a href="https://www.cozmoslabs.com/wordpress-profile-builder/?utm_source=wpbackend&utm_medium=clientsite&utm_content=settings-2fa&utm_campaign=PBFree#pricing" target="_blank">', '</a>' );?>
                                </p>

                            </div>

                        <?php endif; ?>
                    </div>

                    <div class="cozmoslabs-form-subsection-wrapper">
                        <h4 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Other features', 'profile-builder' ); ?></h4>

                        <div class="cozmoslabs-form-field-wrapper">
                            <label class="cozmoslabs-form-field-label" for="hideAdminBarFor"><?php esc_html_e( 'Hide Admin Bar for User Roles', 'profile-builder' ); ?></label>

                            <select name="wppb_general_settings[hide_admin_bar_for][]" class="wppb-select wppb-select2" id="hideAdminBarFor" multiple>
                                <?php
                                global $wp_roles;
                                $general_settings = get_option( 'wppb_general_settings' );
                                $selected_roles = isset($general_settings['hide_admin_bar_for']) ? $general_settings['hide_admin_bar_for'] : '';

                                echo '<option value="allUserRoles"' . ( ( !empty( $selected_roles )  && in_array( 'allUserRoles', $selected_roles ) ) ? ' selected' : '' ) . '>' . esc_html__( 'All User Roles',  'profile-builder' ) . '</option>';
                                echo '<option value="allUserRolesExceptAdmin"' . ( ( !empty( $selected_roles )  && in_array( 'allUserRolesExceptAdmin', $selected_roles ) ) ? ' selected' : '' ) . '>' . esc_html__( 'All User Roles Except Admin',  'profile-builder' ) . '</option>';

                                foreach ( $wp_roles->roles as $role ) {
                                    $key = $role['name'];

                                    echo '<option value="'.esc_attr( $key ).'"' . ( ( !empty( $selected_roles )  && in_array( $key, $selected_roles ) ) ? ' selected' : '' ) . '>' . esc_html( translate_user_role( $key ) ) . '</option>';
                                }

                                ?>
                            </select>

                            <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'Hide the WordPress Admin Bar for these user roles. You can select multiple roles to hide it for.', 'profile-builder' ); ?></p>
                        </div>

                        <?php
                        if( file_exists( WPPB_PLUGIN_DIR.'/features/roles-editor/roles-editor.php' ) ) {
                            ?>

                            <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                                <label class="cozmoslabs-form-field-label" for="rolesEditorSelect"><?php esc_html_e('Roles Editor', 'profile-builder'); ?></label>

                                <div class="cozmoslabs-toggle-container">
                                    <input type="checkbox" name="wppb_general_settings[rolesEditor]" id="rolesEditorSelect" value="yes" <?php echo (!empty($wppb_generalSettings['rolesEditor']) && $wppb_generalSettings['rolesEditor'] === 'yes') ? 'checked' : ''; ?> >
                                    <label class="cozmoslabs-toggle-track" for="rolesEditorSelect"></label>
                                </div>

                                <div class="cozmoslabs-toggle-description">
                                    <label for="rolesEditorSelect" class="cozmoslabs-description wppb-roles-editor-details"><?php esc_html_e( 'Easily create new custom user roles or customize any existing user role capabilities.', 'profile-builder' ); ?></label>
                                </div>

                                <p class="cozmoslabs-description cozmoslabs-description-space-left wppb-roles-editor-link"><?php printf( esc_html__( 'You can add / edit user roles at %1$sUsers > Roles Editor%2$s.', 'profile-builder' ), '<a href="'. esc_url( get_bloginfo( 'url' ) ).'/wp-admin/edit.php?post_type=wppb-roles-editor">', '</a>' )?></p>
                            </div>

                        <?php } ?>

                        <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                            <label class="cozmoslabs-form-field-label" for="extraFieldsLayout"><?php esc_html_e('Load CSS', 'profile-builder'); ?></label>

                            <div class="cozmoslabs-toggle-container">
                                <input type="checkbox" id="extraFieldsLayout" name="wppb_general_settings[extraFieldsLayout]"<?php echo ( ( isset( $wppb_generalSettings['extraFieldsLayout'] ) && ( $wppb_generalSettings['extraFieldsLayout'] == 'default' ) ) ? ' checked' : '' ); ?> value="default">
                                <label class="cozmoslabs-toggle-track" for="extraFieldsLayout"></label>
                            </div>
                            <div class="cozmoslabs-toggle-description">
                                <label for="extraFieldsLayout" class="cozmoslabs-description"><?php printf( esc_html__( 'You can find the default file here: %1$s', 'profile-builder' ), '<a href="'.dirname( plugin_dir_url( __FILE__ ) ).'/assets/css/style-front-end.css" target="_blank">'.dirname( dirname( plugin_basename( __FILE__ ) ) ).'/assets/css/style-front-end.css</a>' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
                            </div>
                        </div>

                    </div>

                    <?php do_action( 'wppb_extra_general_settings', $wppb_generalSettings ); ?>

                    <input type="hidden" name="action" value="update" />

                </div>

                <div class="submit cozmoslabs-submit">
                    <h3 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Update Settings', 'profile-builder' ) ?></h3>
                    <div class="cozmoslabs-publish-button-group">
                        <p class="submit">
                            <input type="submit" class="button-primary" value="<?php esc_html_e( 'Save Changes', 'profile-builder' ); ?>" />
                        </p>
                    </div>
                </div>

            </form>

        </div>

    </div>

<?php
}


/*
 * Function that sanitizes the general settings
 *
 * @param array $wppb_generalSettings
 *
 * @since v.2.0.7
 */
function wppb_general_settings_sanitize( $wppb_generalSettings ) {
    $wppb_generalSettings = apply_filters( 'wppb_general_settings_sanitize_extra', $wppb_generalSettings );

	if( !empty( $wppb_generalSettings ) ){
		foreach( $wppb_generalSettings as $settings_name => $settings_value ){
			if( $settings_name == "minimum_password_length" || $settings_name == "activationLandingPage" )
				$wppb_generalSettings[$settings_name] = absint( $settings_value );
			elseif( $settings_name == "extraFieldsLayout" || $settings_name == "emailConfirmation" || $settings_name == "adminApproval" || $settings_name == "loginWith" || $settings_name == "minimum_password_strength" )
				$wppb_generalSettings[$settings_name] = sanitize_text_field( $settings_value );
			elseif( $settings_name == "adminApprovalOnUserRole" ){
				if( is_array( $settings_value ) && !empty( $settings_value ) ){
					foreach( $settings_value as $key => $value ){
						$wppb_generalSettings[$settings_name][$key] = sanitize_text_field( $value );
					}
				}
			}
		}
	}

    return $wppb_generalSettings;
}


/*
 * Function that pushes settings errors to the user
 *
 * @since v.2.0.7
 */
function wppb_general_settings_admin_notices() {
    settings_errors( 'wppb_general_settings' );
}
add_action( 'admin_notices', 'wppb_general_settings_admin_notices' );


/*
 * Function that return user roles
 *
 * @since v.2.2.0
 *
 * @return array
 */
function wppb_adminApproval_onUserRole() {
	global $wp_roles;

	$wp_roles = new WP_Roles();

	$roles = $wp_roles->get_names();

	unset( $roles['administrator'] );

	return $roles;
}



/*
 * Generate the Form Designs Preview Showcase
 *
 */
function wppb_display_form_designs_preview() {
    $form_designs_data = array(
        array(
            'id' => 'form-style-default',
            'name' => 'Default',
            'images' => array(
                'main' => WPPB_PLUGIN_URL.'assets/images/pb-default-forms.jpg',
            ),
        ),
        array(
            'id' => 'form-style-1',
            'name' => 'Sublime',
            'images' => array(
                'main' => WPPB_PLUGIN_URL.'assets/images/style1-slide1.jpg',
                'slide1' => WPPB_PLUGIN_URL.'assets/images/style1-slide2.jpg',
                'slide2' => WPPB_PLUGIN_URL.'assets/images/style1-slide3.jpg',
            ),
        ),
        array(
            'id' => 'form-style-2',
            'name' => 'Greenery',
            'images' => array(
                'main' => WPPB_PLUGIN_URL.'assets/images/style2-slide1.jpg',
                'slide1' => WPPB_PLUGIN_URL.'assets/images/style2-slide2.jpg',
                'slide2' => WPPB_PLUGIN_URL.'assets/images/style2-slide3.jpg',
            ),
        ),
        array(
            'id' => 'form-style-3',
            'name' => 'Slim',
            'images' => array(
                'main' => WPPB_PLUGIN_URL.'assets/images/style3-slide1.jpg',
                'slide1' => WPPB_PLUGIN_URL.'assets/images/style3-slide2.jpg',
                'slide2' => WPPB_PLUGIN_URL.'assets/images/style3-slide3.jpg',
            ),
        )
    );

    $output = '<div id="wppb-forms-design-browser">';

    foreach ( $form_designs_data as $form_design ) {

        if ( $form_design['id'] != 'form-style-default' ) {
            $preview_button = '<div class="wppb-forms-design-preview button-secondary" id="' . $form_design['id'] . '-info">Preview</div>';
            $title = esc_html__( 'Available in the Pro versions of the plugin', 'profile-builder' );
        }
        else {
            $preview_button = '';
            $title = '';
        }

        $output .= '<div class="wppb-forms-design" id="'. $form_design['id'] .'" title="'. $title .'">
                        <label>
                            <input type="radio" id="wppb-fd-option-' . $form_design['id'] . '" value="' . $form_design['id'] . '" name="" disabled ' . ( $form_design['id'] == 'form-style-default' ? 'checked' : '' ) .'>
                            ' . $form_design['name'] . '</label>
                        <div class="wppb-forms-design-screenshot">
                            <img src="' . $form_design['images']['main'] . '" alt="Form Design">
                            '. $preview_button .'
                        </div>
                    </div>';

        $img_count = 0;
        $image_list = '';
        foreach ( $form_design['images'] as $image ) {
            $img_count++;
            $active_img = ( $img_count == 1 ) ? ' active' : '';
            $image_list .= '<img class="wppb-forms-design-preview-image'. $active_img .'" src="'. $image .'">';
        }

        if ( $img_count > 1 ) {
            $previous_button = '<div class="wppb-slideshow-button wppb-forms-design-sildeshow-previous disabled" data-theme-id="'. $form_design['id'] .'" data-slideshow-direction="previous"> < </div>';
            $next_button = '<div class="wppb-slideshow-button wppb-forms-design-sildeshow-next" data-theme-id="'. $form_design['id'] .'" data-slideshow-direction="next"> > </div>';
            $justify_content = 'space-between';
        }
        else {
            $previous_button = $next_button = '';
            $justify_content = 'center';
        }

        $output .= '<div id="modal-'. $form_design['id'] .'" class="wppb-forms-design-modal" title="'. $form_design['name'] .'">
                        <div class="wppb-forms-design-modal-slideshow" style="justify-content: '. $justify_content .'">
                            '. $previous_button .'
                            <div class="wppb-forms-design-modal-images">
                                '. $image_list .'
                            </div>
                            '. $next_button .'
                        </div>
                    </div>';

    }

    $output .= '</div>';

    $output .= '<p class="cozmoslabs-description cozmoslabs-description-upsell">'. sprintf( esc_html__( 'You can now beautify your forms using pre-made templates. Enable %3$sForm Designs%4$s by upgrading to %1$sBasic or PRO versions%2$s.', 'profile-builder' ), '<a href="https://www.cozmoslabs.com/wordpress-profile-builder/?utm_source=pb-general-settings&utm_medium=client-site&utm_campaign=pb-form-design-templates#pricing" target="_blank">', '</a>', '<strong>', '</strong>' ) .'</p>';

    return $output;
}


/*
 * Generate the Register Version Form
 *
 */
function wppb_add_register_version_form() {
    $status          = wppb_get_serial_number_status();
    $license         = wppb_get_serial_number();
    $license_details = get_option( 'wppb_license_details', false );

    if( !empty( $license ) ){
        // process license so it doesn't get displayed in back-end
        $license_length = strlen( $license );
        $license        = substr_replace( $license, '***************', 7, $license_length - 14 );
    }
    ?>

    <h4 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Register Website', 'profile-builder' ) ?></h4>

    <form method="post" action="<?php echo !is_multisite() ? 'options.php' : 'edit.php'; ?>">
        <?php settings_fields( 'wppb_license_key' ); ?>

        <div class="cozmoslabs-form-field-wrapper cozmoslabs-form-field-serial-number">

            <label class="cozmoslabs-form-field-label" for="wppb_license_key"><?php esc_html_e( 'License key', 'profile-builder' ); ?></label>

            <div class="cozmoslabs-serial-wrap__holder">
                <input id="wppb_license_key" name="wppb_license_key" type="text" class="regular-text" value="<?php echo esc_attr( $license ); ?>" />
                <?php wp_nonce_field( 'wppb_license_nonce', 'wppb_license_nonce' ); ?>

                <?php if( $status !== false && $status == 'valid' ) {
                    $button_name =  'wppb_edd_license_deactivate';
                    $button_value = __('Deactivate License', 'profile-builder' );

                    if( empty( $details['invalid'] ) )
                        echo '<span title="'. esc_html__( 'Active on this site', 'profile-builder' ) .'" class="wppb-active-license dashicons dashicons-yes"></span>';
                    else
                        echo '<span title="'. esc_html__( 'Your license is invalid', 'profile-builder' ) .'" class="wppb-invalid-license dashicons dashicons-warning"></span>';

                } else {
                    $button_name =  'wppb_edd_license_activate';
                    $button_value = __('Activate License', 'profile-builder');
                }
                ?>
                <input type="submit" class="button-secondary" name="<?php echo esc_attr( $button_name ); ?>" value="<?php echo esc_attr( $button_value ); ?>"/>
            </div>

            <?php if( $status != 'expired' && ( !empty( $license_details ) && !empty( $license_details->expires ) && $license_details->expires !== 'lifetime' ) && ( ( !isset( $license_details->subscription_status ) || $license_details->subscription_status != 'active' ) && strtotime( $license_details->expires ) < strtotime( '+14 days' ) ) ) : ?>
                <div class="cozmoslabs-description-container yellow">
                    <p class="cozmoslabs-description"><?php echo wp_kses_post( sprintf( __( 'Your %s license is about to expire on %s', 'profile-builder' ), '<strong>' . PROFILE_BUILDER . '</strong>', '<strong>' . date_i18n( get_option( 'date_format' ), strtotime( $license_details->expires ) ) . '</strong>' ) ); ?>
                    <p class="cozmoslabs-description"><?php echo wp_kses_post( sprintf( __( 'Please %sRenew Your Licence%s to continue receiving access to new features, premium addons, product downloads & automatic updates — including important security patches and WordPress compatibility.', 'profile-builder' ), "<a href='https://www.cozmoslabs.com/account/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PBPro&utm_content=license-about-to-expire' target='_blank'>", "</a>" ) ); ?></p>
                </div>
            <?php elseif( $status == 'expired' ) : ?>
                <div class="cozmoslabs-description-container red">
                    <p class="cozmoslabs-description"><?php echo wp_kses_post( sprintf( __( 'Your %s license has expired.', 'profile-builder' ), '<strong>' . PROFILE_BUILDER . '</strong>' ) ); ?>
                    <p class="cozmoslabs-description"><?php echo wp_kses_post( sprintf( __( 'Please %1$sRenew Your Licence%2$s  to continue receiving access to new features, premium addons, product downloads & automatic updates — including important security patches and WordPress compatibility.', 'profile-builder' ), '<a href="https://www.cozmoslabs.com/account/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PBPro&utm_content=license-expired" target="_blank">', '</a>' ) ); ?></p>
                </div>
            <?php elseif( $status == 'no_activations_left' ) : ?>
                <div class="cozmoslabs-description-container red">
                    <p class="cozmoslabs-description"><?php echo wp_kses_post( sprintf( __( 'Your %s license has reached its activation limit.', 'profile-builder' ), '<strong>' . PROFILE_BUILDER . '</strong>' ) ); ?>
                    <p class="cozmoslabs-description"><?php echo wp_kses_post( sprintf( __( '%sUpgrade now%s for unlimited activations and extra features like multiple registration and edit profile forms, userlisting, custom redirects and more.', 'profile-builder' ), '<a href="https://www.cozmoslabs.com/account/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PBPro&utm_content=license-activation-limit" target="_blank">', '</a>' ) ); ?>
                </div>
            <?php elseif( empty( $license ) || $status != 'valid' ) : ?>
                <div class="cozmoslabs-description-container">
                    <p class="cozmoslabs-description"><?php echo wp_kses_post( sprintf( __( 'Enter your license key. Your license key can be found in your %sCozmoslabs account%s. ', 'profile-builder' ), '<a href="https://www.cozmoslabs.com/account/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PBPro&utm_content=license-missing" target="_blank">', '</a>' ) ); ?></p>
                    <p class="cozmoslabs-description"><?php echo wp_kses_post( sprintf( __( 'You can use this core version of Profile Builder for free. For priority support and advanced functionality, %sa license key is required%s.', 'profile-builder' ), '<a href="https://www.cozmoslabs.com/wordpress-profile-builder/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PBPro&utm_content=license-missing#pricing" target="_blank">', '</a>' ) ); ?></p>
                </div>
            <?php endif; ?>

        </div>
    </form>

    <?php
}


/*
 * Load scripts and styles we need on the page ( ex. Select2 )
 *
 */
function wppb_load_necessary_scripts() {
    wp_enqueue_script( 'wppb-select2', WPPB_PLUGIN_URL . 'assets/js/select2/select2.min.js', array(), PROFILE_BUILDER_VERSION );
    wp_enqueue_script( 'wppb-select2-compat', WPPB_PLUGIN_URL . 'assets/js/select2-compat.js', array(), PROFILE_BUILDER_VERSION );
    wp_enqueue_style( 'wppb_select2_css', WPPB_PLUGIN_URL .'assets/css/select2/select2.min.css', array(), PROFILE_BUILDER_VERSION );
}