<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Woocommerce_Account_Form_Register extends Woo_Element {
	public $name            = 'woocommerce-account-form-register';
	public $icon            = 'fas fa-user-plus';
	public $panel_condition = [ 'templateType', '=', 'wc_account_form_login' ];

	public function get_label() {
		return esc_html__( 'Account', 'bricks' ) . ' - ' . esc_html__( 'Register form', 'bricks' );
	}

	public function set_control_groups() {
		$this->control_groups['fields'] = [
			'title' => esc_html__( 'Fields', 'bricks' ),
		];

		$this->control_groups['submitButton'] = [
			'title' => esc_html__( 'Submit button', 'bricks' ),
		];

		$this->control_groups['others'] = [
			'title' => esc_html__( 'Others', 'bricks' ),
		];
	}

	public function set_controls() {
		// FIELDS
		$fields_controls = $this->get_woo_form_fields_controls();
		$fields_controls = $this->controls_grouping( $fields_controls, 'fields' );

		// Remove as login/register form does not have any placeholder in Woo template
		unset( $fields_controls['hideLabels'] );
		unset( $fields_controls['hidePlaceholders'] );
		unset( $fields_controls['placeholderTypography'] );

		$this->controls = array_merge( $this->controls, $fields_controls );

		// SUBMIT BUTTON
		$submit_controls = $this->get_woo_form_submit_controls();
		$submit_controls = $this->controls_grouping( $submit_controls, 'submitButton' );
		$this->controls  = array_merge( $this->controls, $submit_controls );

		// OTHERS

		// Generate password text
		$this->controls['generatePasswordTextSep'] = [
			'group'       => 'others',
			'type'        => 'separator',
			'label'       => esc_html__( 'Generate password', 'bricks' ),
			'description' => esc_html__( 'This text is displayed when the "When creating an account, send the new user a link to set their password" option is enabled in WooCommerce.', 'bricks' ),
		];

		$this->controls['generatePasswordTypography'] = [
			'group' => 'others',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.generate-password-text',
				]
			],
		];

		// Privacy policy
		$this->controls['privacyPolicySeparator'] = [
			'tab'         => 'content',
			'group'       => 'others',
			'type'        => 'separator',
			'label'       => esc_html__( 'Privacy policy', 'bricks' ),
			'description' => esc_html__( 'This text is displayed when the "Registration privacy policy" text is filled in WooCommerce.', 'bricks' ),
		];

		$this->controls['privacyPolicyTypography'] = [
			'tab'   => 'content',
			'group' => 'others',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.woocommerce-privacy-policy-text',
				]
			],
		];

		// .woocommerce-privacy-policy-text a
		$this->controls['privacyPolicyLink'] = [
			'tab'   => 'content',
			'group' => 'others',
			'label' => esc_html__( 'Link', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.woocommerce-privacy-policy-text a',
				]
			],
		];
	}

	public function render() {
		// Return: Register form is not enabled
		if ( get_option( 'woocommerce_enable_myaccount_registration' ) !== 'yes' ) {
			return $this->render_element_placeholder(
				[
					'title' => '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=account' ) . '" target="_blank">' . esc_html__( 'Account creation on "My account" page is not enabled.', 'bricks' ) . '</a>',
				]
			);
		}

		$settings = $this->settings;

		$this->set_attribute( '_root', 'class', 'woocommerce-form woocommerce-form-register register' );

		$this->set_attribute( '_root', 'method', 'post' );

		ob_start();

		do_action( 'woocommerce_register_form_tag' );

		$additional_tags = ob_get_clean();

		$output = "<form {$this->render_attributes( '_root' )} {$additional_tags}>";

		$output .= $this->get_register_form();

		$output .= '</form>';

		echo $output;
	}

	/**
	 * See templates/myaccount/form-login.php
	 */
	private function get_register_form() {
		$settings = $this->settings;

		ob_start();

		do_action( 'woocommerce_register_form_start' ); ?>

		<?php if ( get_option( 'woocommerce_registration_generate_username' ) === 'no' ) { ?>
		<div class="form-group username">
			<?php
			echo '<label for="reg_username">' . esc_html__( 'Username', 'woocommerce' ) . ' <span class="required">*</span></label>';

			$username = ! empty( $_POST['username'] ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : '';
			echo '<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="reg_username" autocomplete="username" value="' . $username . '" />';
			?>
		</div>
		<?php } ?>

		<div class="form-group email">
			<label for="reg_email"><?php esc_html_e( 'Email address', 'woocommerce' ); ?> <span class="required">*</span></label>

			<?php
				echo sprintf(
					'<input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="email" id="reg_email" autocomplete="email" value="%s" />',
					( ! empty( $_POST['email'] ) ) ? esc_attr( wp_unslash( $_POST['email'] ) ) : ''
				);
			?>
		</div>

		<?php if ( get_option( 'woocommerce_registration_generate_password' ) === 'no' ) { ?>
			<div class="form-group password">
				<label for="reg_password"><?php esc_html_e( 'Password', 'woocommerce' ); ?>&nbsp;<span class="required">*</span></label>

				<?php
				// Builder: Add span to wrap password input manually (no JS enqueued) to show password toggle icon
				if ( bricks_is_builder() || bricks_is_builder_call() ) {
					echo '<span class="password-input">';
				}

				echo '<input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password" id="reg_password" autocomplete="new-password" />';

				if ( bricks_is_builder() || bricks_is_builder_call() ) {
					echo '<span class="show-password-input"></span></span>';
				}
				?>
			</div>
		<?php } ?>

		<?php if ( get_option( 'woocommerce_registration_generate_password' ) === 'yes' ) { ?>
			<div class="generate-password-text"><?php esc_html_e( 'A link to set a new password will be sent to your email address.', 'woocommerce' ); ?></div>
		<?php } ?>

		<?php do_action( 'woocommerce_register_form' ); ?>

		<div class="form-group submit">
			<?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>
			<button type="submit" class="woocommerce-Button woocommerce-button button<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?> woocommerce-form-register__submit" name="register" value="<?php esc_attr_e( 'Register', 'woocommerce' ); ?>"><?php esc_html_e( 'Register', 'woocommerce' ); ?></button>
		</div>

		<?php
		do_action( 'woocommerce_register_form_end' );

		return ob_get_clean();
	}
}
