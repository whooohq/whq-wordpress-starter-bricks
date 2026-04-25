<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<h3><?php esc_html_e( 'You’re all setup and ready to go', 'profile-builder' ); ?></h3>
<p class="cozmoslabs-description"><?php esc_html_e( 'Profile Builder is almost ready to run your user registration and login.', 'profile-builder' ); ?></p>
<p class="cozmoslabs-description"><?php esc_html_e( 'You can always change these settings from the plugin settings page.', 'profile-builder' ); ?></p>

<div class="wppb-setup-next">

    <?php
    $hide_newsletter = get_user_meta( get_current_user_id(), 'wppb_setup_wizard_newsletter', true );

    if( empty( $hide_newsletter ) ) : ?>
        <div class="wppb-setup-newsletter">
            <p>
                <?php esc_html_e( 'Get valuable insights, tips, and strategies on how to create, grow and monetize your own membership and community websites with WordPress.', 'profile-builder' ) ?>
            </p>

            <div class="wppb-setup-newsletter__form">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18 6.75H6C5.30964 6.75 4.75 7.30964 4.75 8V16C4.75 16.6904 5.30964 17.25 6 17.25H18C18.6904 17.25 19.25 16.6904 19.25 16V8C19.25 7.30964 18.6904 6.75 18 6.75Z" stroke="#757575" stroke-width="1.5" />
                    <path d="M5 7L12 13L19 7" stroke="#757575" stroke-width="1.5" />
                </svg>

                <input type="email" name="email" value="<?php echo esc_html( get_option( 'admin_email' ) ) ?>">

                <a class="button button-secondary" href="#"><?php esc_html_e( 'Yes Please!', 'profile-builder' ) ?></a>
            </div>

            <div class="wppb-setup-newsletter__success">
                <?php esc_html_e( 'Please check your email to confirm the subscription.', 'profile-builder' ) ?>
            </div>

            <input type="hidden" id="wppb_nonce" name="wppb_nonce" value="<?php echo esc_attr( wp_create_nonce( 'dismiss_setup_wizard_newsletter_subscribe' ) ); ?>" />
        </div>
    <?php endif; ?>

    <?php $this->output_progress_steps(); ?>

    <div class="wppb-setup-form-button">
        <a class="button primary button-primary button-hero" href="<?php echo esc_url( admin_url( 'admin.php?page=profile-builder-dashboard' ) ); ?>"><?php esc_html_e( 'Continue to Your Profile Builder Dashboard', 'profile-builder' ); ?></a>
    </div>
</div>