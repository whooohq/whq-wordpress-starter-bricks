<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if( ! class_exists( 'CL_Addons_List_Table' ) ) {
    require_once( WPPB_PLUGIN_DIR . '/assets/lib/cl-add-ons-listing/cl-add-ons-listing.php' );
}

/**
 * Function that creates the "Add-Ons" submenu page
 *
 * @since v.2.1.0
 *
 * @return void
 */
function wppb_register_add_ons_submenu_page() {
    add_submenu_page( 'profile-builder', __( 'Add-Ons', 'profile-builder' ), __( 'Add-Ons', 'profile-builder' ), 'manage_options', 'profile-builder-add-ons', 'wppb_add_ons_content' );
}
add_action( 'admin_menu', 'wppb_register_add_ons_submenu_page', 28 );


/**
 * Function that adds content to the "Add-Ons" submenu page
 *
 * @since v.2.1.0
 *
 * @return string
 */
function wppb_add_ons_content() {
    //initialize the object
    $pb_addons_listing                                   = new CL_Addons_List_Table();
    $pb_addons_listing->images_folder                    = WPPB_PLUGIN_URL.'assets/images/add-ons/';
    $pb_addons_listing->text_domain                      = 'profile-builder';
    $pb_addons_listing->header                           = array( 'title' => __('Profile Builder Add-ons', 'profile-builder' ) );
    $pb_addons_listing->current_version                  = PROFILE_BUILDER;
    $pb_addons_listing->tooltip_header                   = __( 'Profile Builder Add-ons', 'profile-builder' );
    $pb_addons_listing->tooltip_content                  = sprintf( __( 'You need an active license to have access to the addon. %1$sRenew%2$s or %3$spurchase a new one here%4$s.', 'profile-builder' ), '<a target="_blank" href="https://www.cozmoslabs.com/account/?utm_source=pb-addons&utm_medium=client-site&utm_campaign=pb-expired-license">', '</a>', '<a target="_blank" href="https://www.cozmoslabs.com/wordpress-profile-builder/?utm_source=pb-addons&utm_medium=client-site&utm_campaign=pb-expired-license#pricing">', '</a>' );
    $pb_addons_listing->tooltip_content_license_inactive = sprintf( __( 'To activate this add-on, you must first %senter your license key%s.', 'profile-builder' ), '<a target="_blank" href="'.admin_url( 'admin.php?page=profile-builder-general-settings' ).'">', '</a>' );


    //Add Pro Section
    $pb_addons_listing->section_header      = array( 'title' => __( 'Pro Add-ons', 'profile-builder' ), 'description' => __( 'These Add-ons are available with the Pro and Agency license', 'profile-builder' )  );
    $pb_addons_listing->section_header_free = array( 'title' => __( 'Pro Add-ons', 'profile-builder' ), 'description' => sprintf( __( 'Get access to these Add-ons with a Pro or Agency license. %sBuy now%s', 'profile-builder' ), '<a href="https://www.cozmoslabs.com/wordpress-profile-builder/?utm_source=pb-addons-pro&utm_medium=client-site&utm_campaign=pb-pro-addons-upsell#pricing" target="_blank">', '</a>' ) );
    $pb_addons_listing->section_versions    = array( 'Profile Builder Pro', 'Profile Builder Agency', 'Profile Builder Unlimited' );
    $pb_addons_listing->items               = array(
        array(  'slug' => 'wppb_multipleRegistrationForms',
            'type'        => 'add-on',
            'name'        => __( 'Multiple Registration Forms', 'profile-builder' ),
            'description' => __( 'Set up multiple registration forms with different fields for certain user roles. Helps capture different information from different types of users.', 'profile-builder' ),
            'icon'        => 'pb-add-on-multiple-registration-forms-logo.png',
            'doc_url'     => 'https://www.cozmoslabs.com/docs/profile-builder/modules/multiple-registration-forms/?utm_source=pb-addons-pro&utm_medium=client-site&utm_campaign=pb-multiple-registration-addon',
        ),
        array(  'slug' => 'wppb_multipleEditProfileForms',
            'type'        => 'add-on',
            'name'        => __( 'Multiple Edit Profile Forms', 'profile-builder' ),
            'description' => __( 'Allow different user roles to edit their specific information. Set up multiple edit-profile forms with different fields for certain user roles.', 'profile-builder' ),
            'icon'        => 'pb-add-on-multiple-edit-profile-forms-icon.png',
            'doc_url'     => 'https://www.cozmoslabs.com/docs/profile-builder/modules/multiple-edit-profile-forms/?utm_source=pb-addons-pro&utm_medium=client-site&utm_campaign=pb-multiple-edit-profile-addon',
        ),
        array(  'slug' => 'wppb_userListing',
            'type'        => 'add-on',
            'name'        => __( 'User Listing', 'profile-builder' ),
            'description' => __( 'Easy to edit templates for listing your users as well as creating single user pages.', 'profile-builder' ),
            'icon'        => 'pb-add-on-userlisting-logo.png',
            'doc_url'     => 'https://www.cozmoslabs.com/docs/profile-builder/modules/user-listing/?utm_source=pb-addons-pro&utm_medium=client-site&utm_campaign=pb-user-listing-addon',
        ),
        array(  'slug' => 'wppb_customRedirect',
            'type'        => 'add-on',
            'name'        => __( 'Custom Redirects', 'profile-builder' ),
            'description' => __( 'Redirect users after login, after they first register or when they try to access the default WordPress dashboard, login, lost password and registration forms.', 'profile-builder' ),
            'icon'        => 'pb-add-on-custom-redirects-logo.png',
            'doc_url'     => 'https://www.cozmoslabs.com/docs/profile-builder/modules/custom-redirects/?utm_source=pb-addons-pro&utm_medium=client-site&utm_campaign=pb-custom-redirects-addon',
        ),
        array(  'slug' => 'wppb_fileRestriction',
            'type'        => 'add-on',
            'name'        => __( 'Files Restriction', 'profile-builder' ),
            'description' => __( 'Protect your Media Library by restricting direct links to media files according to user roles.', 'profile-builder' ),
            'icon'        => 'pb-add-on-file-restriction-logo.png',
            'doc_url'     => 'https://www.cozmoslabs.com/add-ons/files-restriction/?utm_source=pb-addons-pro&utm_medium=client-site&utm_campaign=pb-files-restriction-addon',
        ),
        array(  'slug' => 'wppb_repeaterFields',
            'type'        => 'add-on',
            'name'        => __( 'Repeater Fields', 'profile-builder' ),
            'description' => __( 'The Repeater Field Module makes it really easy to add repeater front-end fields or groups of fields to your user profile. Integration with both the Email Customizer and User Listing modules, makes creating advanced user profiles possible.', 'profile-builder' ),
            'icon'        => 'pb-add-on-repeater-fields-logo.png',
            'doc_url'     => 'https://www.cozmoslabs.com/docs/profile-builder/modules/repeater-fields/?utm_source=pb-addons-pro&utm_medium=client-site&utm_campaign=pb-repeater-fields-addon',
        ),
        array(  'slug' => 'buddypress',
            'type'         => 'add-on',
            'name'         => __( 'BuddyPress', 'profile-builder' ),
            'description'  => __( 'This integration add-on allows extending BuddyPress user profiles with Profile Builder user fields.', 'profile-builder' ),
            'icon'         => 'pb-add-on-buddypress-logo.png',
            'doc_url'      => 'https://www.cozmoslabs.com/docs/profile-builder/add-ons/buddypress/?utm_source=pb-addons-pro&utm_medium=client-site&utm_campaign=pb-buddypress-addon',
            'download_url' => 'https://www.cozmoslabs.com/add-ons/buddypress/?utm_source=wpbackend&utm_medium=clientsite&utm_content=add-on-page&utm_campaign=PBFree',
        ),
    );
    $pb_addons_listing->add_section();

    //Add Basic section
    $pb_addons_listing->section_header = array( 'title' => __( 'Basic Add-ons', 'profile-builder' ), 'description' => __( 'These Add-ons are available with the Basic, Pro and Agency license', 'profile-builder' )  );
    $pb_addons_listing->section_header_free = array( 'title' => __( 'Basic Add-ons', 'profile-builder' ), 'description' => sprintf( __( 'Get access to these Add-ons with a Basic, Pro or Agency license. %sBuy now%s', 'profile-builder' ), '<a href="https://www.cozmoslabs.com/wordpress-profile-builder/?utm_source=pb-addons-basic&utm_medium=client-site&utm_campaign=pb-basic-addons-upsell#pricing" target="_blank">', '</a>' ) );
    $pb_addons_listing->section_versions = array( 'Profile Builder Pro', 'Profile Builder Hobbyist', 'Profile Builder Basic', 'Profile Builder Agency', 'Profile Builder Unlimited' );
    $pb_addons_listing->items = array(
        array(  'slug' => 'form-fields-in-columns',
            'type'         => 'add-on',
            'name'         => __( 'Form Fields in Columns', 'profile-builder' ),
            'description'  => __( 'Extends the functionality of Profile Builder by adding the possibility to organize fields in columns.', 'profile-builder' ),
            'icon'         => 'pb-add-on-form-fields-in-columns-logo.png',
            'doc_url'      => 'https://www.cozmoslabs.com/docs/profile-builder/add-ons/form-fields-in-columns/?utm_source=pb-addons-basic&utm_medium=client-site&utm_campaign=pb-field-columns-addon',
            'download_url' => 'https://www.cozmoslabs.com/add-ons/form-fields-in-columns/?utm_source=wpbackend&utm_medium=clientsite&utm_content=add-on-page&utm_campaign=PBFree',
        ),
        array(  'slug' => 'social-connect',
                'type'         => 'add-on',
                'name'         => __( 'Social Connect', 'profile-builder' ),
                'description'  => __( 'Easily configure and enable social login on your website. Users can login with social platforms like Facebook, Google or X.', 'profile-builder' ),
                'icon'         => 'pb-add-on-social-connect-logo.png',
                'doc_url'      => 'https://www.cozmoslabs.com/docs/profile-builder/add-ons/social-connect/?utm_source=pb-addons-basic&utm_medium=client-site&utm_campaign=pb-social-connect-addon',
                'download_url' => 'https://www.cozmoslabs.com/add-ons/social-connect/?utm_source=wpbackend&utm_medium=clientsite&utm_content=add-on-page&utm_campaign=PBFree'
        ),
        array(  'slug' => 'woocommerce',
                'type'         => 'add-on',
                'name'         => __( 'WooCommerce Sync', 'profile-builder' ),
                'description'  => __( 'Syncs Profile Builder with WooCommerce, allowing you to manage the user Shipping and Billing fields from WooCommerce with Profile Builder.', 'profile-builder' ),
                'icon'         => 'pb-add-on-woocommerce-logo.png',
                'doc_url'      => 'https://www.cozmoslabs.com/docs/profile-builder/add-ons/woocommerce-sync/?utm_source=pb-addons-basic&utm_medium=client-site&utm_campaign=pb-woo-sync-addon',
                'download_url' => 'https://www.cozmoslabs.com/add-ons/woocommerce-sync/?utm_source=wpbackend&utm_medium=clientsite&utm_content=add-on-page&utm_campaign=PBFree',
        ),
        array(  'slug' => 'multi-step-forms',
                'type'         => 'add-on',
                'name'         => __( 'Multi Step Forms', 'profile-builder' ),
                'description'  => __( 'Extends the functionality of Profile Builder by adding the possibility of having multi-page registration and edit-profile forms.', 'profile-builder' ),
                'icon'         => 'pb-add-on-multi-step-forms-logo.png',
                'doc_url'      => 'https://www.cozmoslabs.com/docs/profile-builder/add-ons/multi-step-forms/?utm_source=pb-addons-basic&utm_medium=client-site&utm_campaign=pb-multi-step-addon',
                'download_url' => 'https://www.cozmoslabs.com/add-ons/multi-step-forms/?utm_source=wpbackend&utm_medium=clientsite&utm_content=add-on-page&utm_campaign=PBFree',
        ),
        array(  'slug' => 'mailchimp-integration',
                'type'         => 'add-on',
                'name'         => __( 'MailChimp', 'profile-builder' ),
                'description'  => __( 'Easily associate MailChimp list fields with Profile Builder fields and set advanced settings for each list.', 'profile-builder' ),
                'icon'         => 'pb-add-on-mailchimp-logo.png',
                'doc_url'      => 'https://www.cozmoslabs.com/docs/profile-builder/add-ons/mailchimp/?utm_source=pb-addons-basic&utm_medium=client-site&utm_campaign=pb-mailchimp-addon',
                'download_url' => 'https://www.cozmoslabs.com/add-ons/profile-builder-mailchimp/?utm_source=wpbackend&utm_medium=clientsite&utm_content=add-on-page&utm_campaign=PBFree',
        ),
        array(  'slug' => 'bbpress',
                'type'         => 'add-on',
                'name'         => __( 'bbPress', 'profile-builder' ),
                'description'  => __( 'This add-on allows you to integrate Profile Builder with the popular forums plugin, bbPress.', 'profile-builder' ),
                'icon'         => 'pb-add-on-bbpress-logo.png',
                'doc_url'      => 'https://www.cozmoslabs.com/docs/profile-builder/add-ons/bbpress/?utm_source=pb-addons-basic&utm_medium=client-site&utm_campaign=pb-bbpress-addon',
                'download_url' => 'https://www.cozmoslabs.com/add-ons/bbpress/?utm_source=wpbackend&utm_medium=clientsite&utm_content=add-on-page&utm_campaign=PBFree',
        ),
        array(  'slug' => 'campaign-monitor',
                'type'         => 'add-on',
                'name'         => __( 'Campaign Monitor', 'profile-builder' ),
                'description'  => __( 'Easily associate Campaign Monitor client list fields with Profile Builder fields. Use Profile Builder Campaign Monitor Widget to add more subscribers to your lists.', 'profile-builder' ),
                'icon'         => 'pb-add-on-campaign-monitor-logo.png',
                'doc_url'      => 'https://www.cozmoslabs.com/docs/profile-builder/add-ons/campaign-monitor/?utm_source=pb-addons-basic&utm_medium=client-site&utm_campaign=pb-campaign-monitor-addon',
                'download_url' => 'https://www.cozmoslabs.com/add-ons/profile-builder-campaign-monitor/?utm_source=wpbackend&utm_medium=clientsite&utm_content=add-on-page&utm_campaign=PBFree',
        ),
        array(  'slug' => 'field-visibility',
                'type'         => 'add-on',
                'name'         => __( 'Field Visibility', 'profile-builder' ),
                'description'  => __( 'Extends the functionality of Profile Builder by allowing you to change visibility options for the extra fields.', 'profile-builder' ),
                'icon'         => 'pb-add-on-field-visibility-logo.png',
                'doc_url'      => 'https://www.cozmoslabs.com/docs/profile-builder/add-ons/field-visibility/?utm_source=pb-addons-basic&utm_medium=client-site&utm_campaign=pb-field-visibility-addon',
                'download_url' => 'https://www.cozmoslabs.com/add-ons/field-visibility/?utm_source=wpbackend&utm_medium=clientsite&utm_content=add-on-page&utm_campaign=PBFree',
        ),
        array(  'slug' => 'edit-profile-approved-by-admin',
                'type'         => 'add-on',
                'name'         => __( 'Edit Profile Approved by Admin', 'profile-builder' ),
                'description'  => __( 'Extends the functionality of Profile Builder by allowing administrators to approve profile changes made by users on individual fields.', 'profile-builder' ),
                'icon'         => 'pb-add-on-edit-profile-updates-approved-by-admins-logo.png',
                'doc_url'      => 'https://www.cozmoslabs.com/docs/profile-builder/add-ons/edit-profile-approved-by-admin/?utm_source=pb-addons-basic&utm_medium=client-site&utm_campaign=pb-profile-approved-admin-addon',
                'download_url' => 'https://www.cozmoslabs.com/add-ons/edit-profile-approved-by-admin/?utm_source=wpbackend&utm_medium=clientsite&utm_content=add-on-page&utm_campaign=PBFree',
        ),
        array(  'slug' => 'custom-profile-menus',
                'type'         => 'add-on',
                'name'         => __( 'Custom Profile Menus', 'profile-builder' ),
                'description'  => __( 'Add custom menu items like Login/Logout or just Logout button and Login/Register/Edit Profile in iFrame Popup.', 'profile-builder' ),
                'icon'         => 'pb-add-on-custom-profile-menus-logo.png',
                'doc_url'      => 'https://www.cozmoslabs.com/docs/profile-builder/add-ons/custom-profile-menus/?utm_source=pb-addons-basic&utm_medium=client-site&utm_campaign=pb-custom-profile-menus-addon',
                'download_url' => 'https://www.cozmoslabs.com/add-ons/custom-profile-menus/?utm_source=wpbackend&utm_medium=clientsite&utm_content=add-on-page&utm_campaign=PBFree',
        ),
        array(  'slug' => 'mailpoet-integration',
                'type'         => 'add-on',
                'name'         => __( 'MailPoet', 'profile-builder' ),
                'description'  => __( 'Allow users to subscribe to your MailPoet lists directly from the Register and Edit Profile forms.', 'profile-builder' ),
                'icon'         => 'pb-add-on-mailpoet-logo.png',
                'doc_url'      => 'https://www.cozmoslabs.com/docs/profile-builder/add-ons/mailpoet/?utm_source=pb-addons-basic&utm_medium=client-site&utm_campaign=pb-mailpoet-addon',
                'download_url' => 'https://www.cozmoslabs.com/add-ons/mailpoet/?utm_source=wpbackend&utm_medium=clientsite&utm_content=add-on-page&utm_campaign=PBFree',
        )
    );
    $pb_addons_listing->add_section();

    //Add Free section
    $pb_addons_listing->section_header = array( 'title' => __('Free Add-ons', 'profile-builder' ), 'description' => __('These Add-ons are available in all versions of Profile Builder', 'profile-builder')  );
    $pb_addons_listing->section_versions = array( 'Profile Builder Pro', 'Profile Builder Hobbyist', 'Profile Builder Free', 'Profile Builder Basic', 'Profile Builder Agency', 'Profile Builder Unlimited' );
    $pb_addons_listing->items = array(
        array(  'slug' => 'user-profile-picture',
                'type'         => 'add-on',
                'name'         => __( 'User Profile Picture', 'profile-builder' ),
                'description'  => __( 'Set or remove a custom profile image for a user using the standard WordPress media upload tool.', 'profile-builder' ),
                'icon'         => 'pb-add-on-user-profile-picture-logo.png',
                'doc_url'      => 'https://www.cozmoslabs.com/user-profile-picture/?utm_source=pb-addons-free&utm_medium=client-site&utm_campaign=pb-profile-picture-addon',
                'download_url' => 'https://wordpress.org/plugins/metronet-profile-picture/',
                'version'      => 'free',
        ),
        array(  'slug' => 'import-export',
                'type'         => 'add-on',
                'name'         => __( 'Import and Export', 'profile-builder' ),
                'description'  => __( 'With the help of this add-on you will be able to export all Profile Builder Settings data to a .json. You can then use this file as a back-up or you can import this data on another instance of Profile Builder.', 'profile-builder' ),
                'icon'         => 'pb-add-on-import-export-logo.png',
                'doc_url'      => 'https://www.cozmoslabs.com/docs/profile-builder/add-ons/import-export-pb-settings/?utm_source=pb-addons-free&utm_medium=client-site&utm_campaign=pb-import-export-addon',
                'download_url' => 'https://www.cozmoslabs.com/add-ons/import-export/?utm_source=wpbackend&utm_medium=clientsite&utm_content=add-on-page&utm_campaign=PBFree',
                'version'      => 'free',
        ),
        array(  'slug' => 'custom-css-classes-on-fields',
                'type'         => 'add-on',
                'name'         => __( 'Custom CSS Classes on Fields', 'profile-builder' ),
                'description'  => __( 'This add-on extends the functionality of Profile Builder by allowing you to add custom css classes for fields.', 'profile-builder' ),
                'icon'         => 'pb-add-on-classes-on-fields-logo.png',
                'doc_url'      => 'https://www.cozmoslabs.com/docs/profile-builder/add-ons/custom-css-classes-on-fields-for-profile-builder/?utm_source=pb-addons-free&utm_medium=client-site&utm_campaign=pb-custom-css-addon',
                'download_url' => 'https://www.cozmoslabs.com/add-ons/custom-css-classes-fields/?utm_source=wpbackend&utm_medium=clientsite&utm_content=add-on-page&utm_campaign=PBFree',
                'version'      => 'free',
        ),
        array(  'slug' => 'maximum-character-length',
                'type'         => 'add-on',
                'name'         => __( 'Maximum Character Length', 'profile-builder' ),
                'description'  => __( 'Using this addon you can limit the maximum number of characters a user can type in a field added and managed with Profile Builder.', 'profile-builder' ),
                'icon'         => 'pb-add-on-maximum-character-length-logo.png',
                'doc_url'      => 'https://www.cozmoslabs.com/docs/profile-builder/add-ons/maximum-character-length/?utm_source=pb-addons-free&utm_medium=client-site&utm_campaign=pb-max-character-length-addon',
                'download_url' => 'https://www.cozmoslabs.com/add-ons/maximum-character-length/?utm_source=wpbackend&utm_medium=clientsite&utm_content=add-on-page&utm_campaign=PBFree',
                'version'      => 'free',
        ),
        array(  'slug' => 'labels-edit',
                'type'         => 'add-on',
                'name'         => __( 'Labels Edit', 'profile-builder' ),
                'description'  => __( 'This add-on extends the functionality of our plugin and let us easily edit all Profile Builder labels.', 'profile-builder' ),
                'icon'         => 'pb-add-on-labels-edit-logo.png',
                'doc_url'      => 'https://www.cozmoslabs.com/docs/profile-builder/add-ons/labels-edit/?utm_source=pb-addons-free&utm_medium=client-site&utm_campaign=pb-labels-edit-addon',
                'download_url' => 'https://www.cozmoslabs.com/add-ons/labels-edit/?utm_source=wpbackend&utm_medium=clientsite&utm_content=add-on-page&utm_campaign=PBFree',
                'version'      => 'free',
        ),
        array(  'slug' => 'gdpr-communication-preferences',
                'type'         => 'add-on',
                'name'         => __( 'GDPR Communication Preferences', 'profile-builder' ),
                'description'  => __( 'This add-on plugin adds a GDPR Communication preferences field to Profile Builder.', 'profile-builder' ),
                'icon'         => 'pb-add-on-gdpr-communication-preferences-logo.png',
                'doc_url'      => 'https://www.cozmoslabs.com/add-ons/gdpr-communication-preferences/?utm_source=pb-addons-free&utm_medium=client-site&utm_campaign=pb-gdpr-addon',
                'download_url' => 'https://www.cozmoslabs.com/add-ons/gdpr-communication-preferences/?utm_source=wpbackend&utm_medium=clientsite&utm_content=add-on-page&utm_campaign=PBFree',
                'version'      => 'free',
        ),
    );
    $pb_addons_listing->add_section();



    //Add Recommended Plugins
    $pb_addons_listing->section_header = array( 'title' => __('Recommended Plugins', 'profile-builder' ), 'description' => __('These plugins are compatible with all versions of Profile Builder', 'profile-builder')  );
    $pb_addons_listing->section_versions = array( 'Profile Builder Pro', 'Profile Builder Hobbyist', 'Profile Builder Free', 'Profile Builder Basic', 'Profile Builder Agency', 'Profile Builder Unlimited' );
    $pb_addons_listing->items = array(
        array(  'slug' => 'translatepress-multilingual/index.php',
            'type'         => 'plugin',
            'name'         => __( 'TranslatePress', 'profile-builder' ),
            'description'  => __( 'Translate your Profile Builder forms with a WordPress translation plugin that anyone can use. It offers a simpler way to translate WordPress sites, with full support for WooCommerce and site builders.', 'profile-builder' ),
            'icon'         => 'pb-add-on-translatepress-logo.png',
            'doc_url'      => 'https://translatepress.com/docs/translatepress/?utm_source=pb-addons&utm_medium=client-site&utm_campaign=pb-tp-upsell',
            'download_url' => 'https://wordpress.org/plugins/translatepress-multilingual/',
        ),
        array(  'slug' => 'paid-member-subscriptions/index.php',
            'type'         => 'plugin',
            'name'         => __( 'Paid Member Subscriptions', 'profile-builder' ),
            'description'  => __( 'Unlock the Subscriptions Field in Profile Builder and offer paid memberships for your site. With Paid Member Subscriptions your registration forms will allow your users to sign up for paid accounts.', 'profile-builder' ),
            'icon'         => 'pb-add-on-pms-logo.png',
            'doc_url'      => 'https://www.cozmoslabs.com/docs/paid-member-subscriptions/?utm_source=pb-addons&utm_medium=client-site&utm_campaign=pb-pms-upsell',
            'download_url' => 'https://wordpress.org/plugins/paid-member-subscriptions/'
        ),
        array(  'slug' => 'wp-webhooks/wp-webhooks.php',
            'type'         => 'plugin',
            'name'         => __( 'WP Webhooks Automations', 'profile-builder' ),
            'description'  => __( 'Easily create powerful no-code automations that connect Profile Builder actions, like sign-ups or edits to a user\'s profile, to actions from other plugins, sites, and apps.', 'profile-builder' ),
            'icon'         => 'pb-add-on-wp-webhooks-icon.jpg',
            'doc_url'      => 'https://wp-webhooks.com/integrations/?utm_source=pb-addons&utm_medium=client-site&utm_campaign=pb-wpwh-upsell',
            'download_url' => 'https://wordpress.org/plugins/wp-webhooks/'
        ),
        array(  'slug' => 'client-portal/index.php',
            'type'         => 'plugin',
            'name'         => __( 'Client Portal', 'profile-builder' ),
            'description'  => __( 'Create private pages for your website users that only an administrator can edit.', 'profile-builder' ),
            'icon'         => 'pb-add-on-client-portal-logo.png',
            'doc_url'      => 'https://www.cozmoslabs.com/add-ons/client-portal/?utm_source=pb-addons&utm_medium=client-site&utm_campaign=pb-client-portal-upsell',
            'download_url' => 'https://www.cozmoslabs.com/add-ons/client-portal/?utm_source=wpbackend&utm_medium=clientsite&utm_content=add-on-page&utm_campaign=PBFree'
        ),
        array(  'slug' => 'custom-login-page-templates/custom-login-templates.php',
            'type'         => 'plugin',
            'name'         => __( 'Custom Login Page Templates', 'profile-builder' ),
            'description'  => __( 'Customizes the default WordPress Login Page with different templates, logo and background uploads and also adds support for custom CSS.', 'profile-builder' ),
            'icon'         => 'pb-add-on-custom-login-page-templates-logo.png',
            'doc_url'      => 'https://www.cozmoslabs.com/add-ons/custom-login-page-templates/?utm_source=pb-addons&utm_medium=client-site&utm_campaign=pb-login-templates-upsell',
            'download_url' => 'https://www.cozmoslabs.com/add-ons/custom-login-page-templates/?utm_source=wpbackend&utm_medium=clientsite&utm_content=add-on-page&utm_campaign=PBFree'
        ),
        array(  'slug' => 'passwordless-login/passwordless_login.php',
            'type'         => 'plugin',
            'name'         => __( 'Passwordless Login', 'profile-builder' ),
            'description'  => __( 'WordPress Passwordless Login is a plugin that allows your users to login without a password.', 'profile-builder' ),
            'icon'         => 'pb-add-on-passwordless-login-logo.png',
            'doc_url'      => 'https://www.cozmoslabs.com/docs/profile-builder/add-ons/passwordless-login/?utm_source=pb-addons&utm_medium=client-site&utm_campaign=pb-passwordless-upsell',
            'download_url' => 'https://www.cozmoslabs.com/add-ons/passwordless-login/?utm_source=wpbackend&utm_medium=clientsite&utm_content=add-on-page&utm_campaign=PBFree'
        ),
    );
    $pb_addons_listing->add_section();


    //Display the whole listing
    $pb_addons_listing->display_addons();
}


/**
 * Add this for generating default options on first install
 */
add_action( 'admin_init', 'wppb_generate_modules_default_values' );
function wppb_generate_modules_default_values(){
    $wppb_module_settings = get_option( 'wppb_module_settings', 'not_found' );
    if ( $wppb_module_settings == 'not_found' ){
        $wppb_module_settings = 	array(	'wppb_userListing'					=> 'hide',
                                            'wppb_customRedirect'				=> 'hide',
                                            'wppb_fileRestriction'				=> 'hide',
                                            'wppb_emailCustomizer'				=> 'hide',
                                            'wppb_multipleEditProfileForms'		=> 'hide',
                                            'wppb_multipleRegistrationForms'	=> 'hide',
                                            'wppb_repeaterFields'				=> 'hide'
                                        );
        update_option( 'wppb_module_settings', $wppb_module_settings );
    }
}


/**
 * For add-ons (not plugins) the implementation is speciffic to each plugin ( PB/PMS/TP ) and is done through filters in the plugin itself
 */

/**
 * Function that determines if a PB add-on is active
 */
add_filter( 'cl_add_on_is_active', 'wppb_check_add_ons_activation', 10, 2 );
function wppb_check_add_ons_activation( $bool, $slug ){
    return wppb_check_if_add_on_is_active($slug);
}

/**
 * Function that activates a PB add-on
 */
add_action( 'cl_add_ons_activate', 'wppb_activate_add_ons' );
function wppb_activate_add_ons( $slug ){
    wppb_activate_or_deactivate_add_on( $slug, 'show' );

    do_action( 'wppb_add_ons_activate', $slug );
}

/**
 * Function that deactivates a PB add-on
 */
add_action( 'cl_add_ons_deactivate', 'wppb_deactivate_add_ons' );
function wppb_deactivate_add_ons( $slug ){
    wppb_activate_or_deactivate_add_on( $slug, 'hide' );

    do_action( 'wppb_add_ons_deactivate', $slug );
}


/**
 * Function used to activate or deactivate a PB add-on
 */
function wppb_activate_or_deactivate_add_on( $slug, $action ){
    //the old modules part
    $wppb_module_settings = get_option( 'wppb_module_settings', 'not_found' );
    if ( $wppb_module_settings != 'not_found' ){
        foreach( $wppb_module_settings as $add_on_slug => $status ){
            if( $slug == $add_on_slug ){
                $wppb_module_settings[$add_on_slug] = $action;
            }
        }
    }
    update_option( 'wppb_module_settings', $wppb_module_settings );

    //the free addons part
    $wppb_free_add_ons_settings = get_option( 'wppb_free_add_ons_settings', array() );
    if ( !empty( $wppb_free_add_ons_settings ) ){

        if( $action == 'show' )
            $wppb_free_add_ons_settings[$slug] = true;
        elseif( $action == 'hide' )
            $wppb_free_add_ons_settings[$slug] = false;

    }
    update_option( 'wppb_free_add_ons_settings', $wppb_free_add_ons_settings );

    //the advanced addons part
    $wppb_advanced_add_ons_settings = get_option( 'wppb_advanced_add_ons_settings', array() );
    if ( !empty( $wppb_advanced_add_ons_settings ) ){

        if( $action == 'show' )
            $wppb_advanced_add_ons_settings[$slug] = true;
        elseif( $action == 'hide' )
            $wppb_advanced_add_ons_settings[$slug] = false;

    }
    update_option( 'wppb_advanced_add_ons_settings', $wppb_advanced_add_ons_settings );
}

/**
 * Add a notice on the add-ons page if the save was successful
 */
add_action('init', function()
{
    if ( isset($_GET['cl_add_ons_listing_success']) ){
        if( class_exists('WPPB_Add_General_Notices') ) {
            new WPPB_Add_General_Notices('cl_add_ons_listing_success',
                sprintf(__('%1$sAdd-ons settings saved successfully%2$s', 'profile-builder'), "<p>", "</p>"),
                'updated notice is-dismissible');
        }
    }
});