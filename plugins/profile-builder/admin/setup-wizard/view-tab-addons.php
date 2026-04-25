<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<h3><?php esc_html_e( 'Create the user experience you need for your project', 'profile-builder' ); ?></h3>
<p class="cozmoslabs-description"><?php esc_html_e( 'Enable addons and add extra features to your website', 'profile-builder' ); ?></p>

<form class="wppb-setup-form wppb-setup-form-addons" method="post">

    <?php $images_folder = WPPB_PLUGIN_URL . 'assets/images/add-ons/'; ?>

    <div class="wppb-setup-addons-list">

        <?php
        $pro_addons = array(
            array(
                'name' => esc_html__( 'User Listing', 'profile-builder' ),
                'slug' => 'wppb_userListing',
                'image' => 'pb-add-on-userlisting-logo.png',
                'description' => esc_html__( 'Easy to edit templates for listing your users as well as creating single user pages.', 'profile-builder' ),
                'notice' => esc_html__( 'Available in the Pro version', 'profile-builder' ),
            ),
            array(
                'name' => esc_html__( 'Custom Redirects', 'profile-builder' ),
                'slug' => 'wppb_customRedirect',
                'image' => 'pb-add-on-custom-redirects-logo.png',
                'description' => esc_html__( 'Redirect users after login, after they first register or when they try to access the default WordPress dashboard, login and registration forms.', 'profile-builder' ),
                'notice' => esc_html__( 'Available in the Pro version', 'profile-builder' ),
            ),
        );

        $basic_addons = array(
            array(
                'name' => esc_html__( 'Multi Step Forms', 'profile-builder' ),
                'slug' => 'multi-step-forms',
                'image' => 'pb-add-on-multi-step-forms-logo.png',
                'description' => esc_html__( 'Extends the functionality of Profile Builder by adding the possibility of having multi-page registration and edit-profile forms.', 'profile-builder' ),
                'notice' => esc_html__( 'Available in the Basic and Pro versions', 'profile-builder' ),
            ),
            array(
                'name' => esc_html__( 'Social Connect', 'profile-builder' ),
                'slug' => 'social-connect',
                'image' => 'pb-add-on-social-connect-logo.png',
                'description' => esc_html__( 'Easily configure and enable social login on your website. Users can login with social platforms like Facebook, Google or X.', 'profile-builder' ),
                'notice' => esc_html__( 'Available in the Basic and Pro versions', 'profile-builder' ),
            ),
        );

        $paid_version_addons = array_merge( $pro_addons, $basic_addons );

        foreach ( $paid_version_addons as $addon ) {
            $is_active = wppb_check_if_add_on_is_active( $addon['slug'] );
            $is_disabled = ( !defined( 'WPPB_PAID_PLUGIN_DIR' ) ) ? 'disabled' : '';
            $is_checked = ( $is_active && !$is_disabled ) ? 'checked' : '';
            $addon_slug = ( !$is_disabled ) ? $addon['slug'] : '';
            $addon_title = ( $is_disabled ) ? $addon['notice']  : '';

            ?>
            <div class="wppb-setup-addon <?php echo esc_html( $is_disabled ); ?>" title="<?php echo esc_html( $addon_title ); ?>">
                <div class="wppb-setup-addon__content">
                    <img class="wppb-setup-addon__logo" src="<?php echo esc_url( $images_folder . $addon['image'] ); ?>" alt="<?php echo esc_html( $addon['name'] ); ?>" />

                    <div class="wppb-setup-addon__details">
                        <h3><?php echo esc_html( $addon['name'] ); ?></h3>
                        <p><?php echo esc_html( $addon['description'] ); ?></p>
                    </div>
                </div>

                <div class="wppb-setup-addon__selector">
                    <div class="cozmoslabs-toggle-switch">
                        <div class="cozmoslabs-toggle-container">
                            <input type="checkbox" name="<?php echo esc_html( $addon_slug ); ?>" id="<?php echo esc_html( $addon_slug ); ?>" value="yes" <?php echo esc_html( $is_checked ); ?> >
                            <label class="cozmoslabs-toggle-track" for="<?php echo esc_html( $addon_slug ); ?>"></label>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        }
        ?>

    </div>
    
    <?php if( defined( 'WPPB_PAID_PLUGIN_DIR' ) ) : ?>
        <p class="wppb-setup-addons-info">
            <?php printf( esc_html__( 'Explore 20+ free and PRO addons from %1$s the Profile Builder admin page %2$s once the onboarding is complete.', 'profile-builder' ), '<strong>', '</strong>' ); ?>
        </p>
    <?php else: ?>
        <p class="wppb-setup-form-styles__upsell" style="padding-top: 14px; padding-bottom: 14px; font-size: 110%;">
            <?php printf( esc_html__( 'Get access to 20+ add-ons with a %sPro%s license. %sBuy Now%s', 'profile-builder' ), '<strong>', '</strong>', '<a href="https://www.cozmoslabs.com/wordpress-profile-builder/?utm_source=pb-setup-wizard&utm_medium=client-site&utm_campaign=pb-pro-addons-upsell#pricing" target="_blank">', '</a>' ); ?>
        </p>
    <?php endif; ?>

    <div class="wppb-setup-form-button">
        <input type="submit" class="button primary button-primary button-hero" value="<?php esc_html_e( 'Continue', 'profile-builder' ); ?>" />
    </div>

    <?php wp_nonce_field( 'wppb-setup-wizard-nonce', 'wppb_setup_wizard_nonce' ); ?>
</form>