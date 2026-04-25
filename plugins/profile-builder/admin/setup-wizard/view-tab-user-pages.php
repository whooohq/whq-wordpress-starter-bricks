<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<?php
$this->set_existing_user_pages();
?>

<h3><?php esc_html_e( 'Quick Setup Wizard', 'profile-builder' ); ?></h3>
<p class="cozmoslabs-description"><?php esc_html_e( 'Ready to welcome your new users?', 'profile-builder' ); ?></p>

<p class="info">
    <?php esc_html_e( 'To offer your users a welcoming experience, we\'ll need to create a few pages designed specifically for registration, login, account management and password reset.', 'profile-builder' ); ?>
</p>

<p class="info">
    <?php esc_html_e( 'You can select only one page or all of them. The pages can also be setup later manually.', 'profile-builder' ); ?>
</p>

<form class="wppb-setup-form wppb-setup-form-user-pages" method="post">

    <div class="wppb-setup-user-pages-field">
        <div class="wppb-setup-user-pages-field__icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M15.5 9.5C16.0523 9.5 16.5 9.05228 16.5 8.5C16.5 7.94772 16.0523 7.5 15.5 7.5C14.9477 7.5 14.5 7.94772 14.5 8.5C14.5 9.05228 14.9477 9.5 15.5 9.5ZM15.5 11C16.8807 11 18 9.88071 18 8.5C18 7.11929 16.8807 6 15.5 6C14.1193 6 13 7.11929 13 8.5C13 9.88071 14.1193 11 15.5 11ZM13.25 17V15C13.25 13.4812 12.0188 12.25 10.5 12.25H6.5C4.98122 12.25 3.75 13.4812 3.75 15V17H5.25V15C5.25 14.3096 5.80964 13.75 6.5 13.75H10.5C11.1904 13.75 11.75 14.3096 11.75 15V17H13.25ZM20.25 15V17H18.75V15C18.75 14.3096 18.1904 13.75 17.5 13.75H15V12.25H17.5C19.0188 12.25 20.25 13.4812 20.25 15ZM9.5 8.5C9.5 9.05228 9.05228 9.5 8.5 9.5C7.94772 9.5 7.5 9.05228 7.5 8.5C7.5 7.94772 7.94772 7.5 8.5 7.5C9.05228 7.5 9.5 7.94772 9.5 8.5ZM11 8.5C11 9.88071 9.88071 11 8.5 11C7.11929 11 6 9.88071 6 8.5C6 7.11929 7.11929 6 8.5 6C9.88071 6 11 7.11929 11 8.5Z" fill="#1E1E1E"/>
            </svg>
        </div>

        <div class="wppb-setup-user-pages-field__title">
            <h4><?php esc_html_e( 'Registration', 'profile-builder' ); ?></h4>
            <p><?php esc_html_e( 'New users can register, add their information and even automatically login after submitting the form. You can customize it with custom fields like birthday or favorite car brand.', 'profile-builder' ); ?></p>
        </div>

        <?php if( empty( $this->user_pages['register_page'] ) ) : ?>
            <div class="cozmoslabs-toggle-switch">
                <div class="cozmoslabs-toggle-container">
                    <input type="checkbox" id="register" name="wppb_user_pages[register]" value="1" />
                    <label class="cozmoslabs-toggle-track" for="register"></label>
                </div>
            </div>
        <?php else : ?>
            <a class="button button-secondary" href="<?php echo esc_url( get_permalink( $this->user_pages['register_page'] ) ); ?>"><?php esc_html_e( 'View Page', 'profile-builder' ); ?></a>
        <?php endif; ?>
    </div>

    <div class="wppb-setup-user-pages-field">
        <div class="wppb-setup-user-pages-field__icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <g clip-path="url(#clip0_7_1705)">
                    <mask id="path-1-inside-1_7_1705" fill="white">
                        <rect x="6" y="10" width="12" height="10" rx="1"/>
                    </mask>
                    <rect x="6" y="10" width="12" height="10" rx="1" stroke="#1E1E1E" stroke-width="3" mask="url(#path-1-inside-1_7_1705)"/>
                    <path d="M15 10V7C15 5.34315 13.6569 4 12 4V4C10.3431 4 9 5.34315 9 7V10" stroke="#1E1E1E" stroke-width="1.5"/>
                </g>
                <defs>
                    <clipPath id="clip0_7_1705">
                        <rect width="24" height="24" fill="white"/>
                    </clipPath>
                </defs>
            </svg>
        </div>

        <div class="wppb-setup-user-pages-field__title">
            <h4><?php esc_html_e( 'Login', 'profile-builder' ); ?></h4>
            <p><?php esc_html_e( 'Once users have an account, they can use the login page to get access to the restricted content you might offer.', 'profile-builder' ); ?></p>
        </div>

        <?php if( empty( $this->user_pages['login_page'] ) ) : ?>
            <div class="cozmoslabs-toggle-switch">
                <div class="cozmoslabs-toggle-container">
                    <input type="checkbox" id="login" name="wppb_user_pages[login]" value="1" />
                    <label class="cozmoslabs-toggle-track" for="login"></label>
                </div>
            </div>
        <?php else : ?>
            <a class="button button-secondary" href="<?php echo esc_url( get_permalink( $this->user_pages['login_page'] ) ); ?>"><?php esc_html_e( 'View Page', 'profile-builder' ); ?></a>
        <?php endif; ?>
    </div>

    <div class="wppb-setup-user-pages-field">
        <div class="wppb-setup-user-pages-field__icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M14 20H20V7.86207L12 4L4 7.86207V20H10H14ZM14 18.5H18.5V8.80358L12 5.66565L5.5 8.80358V18.5H10V13H14V18.5Z" fill="#1E1E1E"/>
            </svg>
        </div>

        <div class="wppb-setup-user-pages-field__title">
            <h4><?php esc_html_e( 'Edit Profile', 'profile-builder' ); ?></h4>
            <p><?php esc_html_e( 'Through the edit profile page, users can manage their profile information or fill in extra details that we’re not asked during registration.', 'profile-builder' ); ?></p>
        </div>

        <?php if( empty( $this->user_pages['edit_profile_page'] ) ) : ?>
            <div class="cozmoslabs-toggle-switch">
                <div class="cozmoslabs-toggle-container">
                    <input type="checkbox" id="edit-profile" name="wppb_user_pages[edit_profile]" value="1" />
                    <label class="cozmoslabs-toggle-track" for="edit-profile"></label>
                </div>
            </div>
        <?php else : ?>
            <a class="button button-secondary" href="<?php echo esc_url( get_permalink( $this->user_pages['edit_profile_page'] ) ); ?>"><?php esc_html_e( 'View Page', 'profile-builder' ); ?></a>
        <?php endif; ?>
    </div>

    <div class="wppb-setup-user-pages-field">
        <div class="wppb-setup-user-pages-field__icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="9" cy="12" r="2.75" stroke="#1E1E1E" stroke-width="2.5"/>
                <rect x="11" y="10.75" width="8" height="2.5" fill="#1E1E1E"/>
                <rect x="15" y="12" width="2.5" height="4" fill="#1E1E1E"/>
            </svg>
        </div>

        <div class="wppb-setup-user-pages-field__title">
            <h4><?php esc_html_e( 'Password reset', 'profile-builder' ); ?></h4>
            <p><?php esc_html_e( 'A simple form where users can reset their password in case they forgot it.', 'profile-builder' ); ?></p>
        </div>

        <?php if( empty( $this->user_pages['lost_password_page'] ) ) : ?>
            <div class="cozmoslabs-toggle-switch">
                <div class="cozmoslabs-toggle-container">
                    <input type="checkbox" id="password-reset" name="wppb_user_pages[reset_password]" value="1" />
                    <label class="cozmoslabs-toggle-track" for="password-reset"></label>
                </div>
            </div>
        <?php else : ?>
            <a class="button button-secondary" href="<?php echo esc_url( get_permalink( $this->user_pages['lost_password_page'] ) ); ?>"><?php esc_html_e( 'View Page', 'profile-builder' ); ?></a>
        <?php endif; ?>
    </div>

    <div class="wppb-setup-form-button">
        <input type="submit" class="button primary button-primary button-hero" value="<?php esc_html_e( 'Continue', 'profile-builder' ); ?>" />
    </div>

    <?php wp_nonce_field( 'wppb-setup-wizard-nonce', 'wppb_setup_wizard_nonce' ); ?>
</form>
