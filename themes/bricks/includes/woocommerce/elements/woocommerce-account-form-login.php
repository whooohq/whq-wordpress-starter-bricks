<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Woocommerce_Account_Form_Login extends Woo_Element {
	public $name            = 'woocommerce-account-form-login';
	public $icon            = 'fa fa-address-card';
	public $panel_condition = [ 'templateType', '=', 'wc_account_form_login' ];

	public function get_label() {
		return esc_html__( 'Account', 'bricks' ) . ' - ' . esc_html__( 'Login form', 'bricks' );
	}

	public function set_control_groups() {
		$this->control_groups['fields'] = [
			'title' => esc_html__( 'Fields', 'bricks' ),
		];

		$this->control_groups['rememberMe'] = [
			'title' => esc_html__( 'Remember me', 'bricks' ),
		];

		$this->control_groups['submitButton'] = [
			'title' => esc_html__( 'Submit button', 'bricks' ),
		];

		$this->control_groups['lostPassword'] = [
			'title' => esc_html__( 'Lost password', 'bricks' ),
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

		// REMEMBER ME
		$this->controls['rememberMeDisable'] = [
			'group' => 'rememberMe',
			'type'  => 'checkbox',
			'label' => esc_html__( 'Disable', 'bricks' ),
		];

		$this->controls['rememberMeTypography'] = [
			'group' => 'rememberMe',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.woocommerce-form-login__rememberme',
				]
			],
		];

		// LOST PASSWORD
		$this->controls['lostPasswordDisable'] = [
			'group' => 'lostPassword',
			'type'  => 'checkbox',
			'label' => esc_html__( 'Disable', 'bricks' ),
		];

		$this->controls['lostPasswordTypography'] = [
			'group' => 'lostPassword',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.woocommerce-LostPassword a',
				]
			],
		];
	}

	/**
	 * NOTE: Not in use as impossible to render only login or register form inside Woo template
	 */
	public function __render() {
		/**
		 * STEP: Get the login form Woo template
		 *
		 * No need to pass any required PHP variables. All done via $_POST.
		 */

		ob_start();

		wc_get_template( 'myaccount/form-login.php', [] );

		$woo_template = ob_get_clean();

		// Render Woo template
		echo "<div {$this->render_attributes( '_root' )}>{$woo_template}</div>";
	}

	public function render() {
		$this->set_attribute( '_root', 'class', 'woocommerce-form woocommerce-form-login login' );
		$this->set_attribute( '_root', 'method', 'post' );

		echo "<form {$this->render_attributes( '_root' )}>" . $this->get_login_form_content() . '</form>';
	}

	private function get_login_form_content() {
		$settings = $this->settings;

		ob_start();
		do_action( 'woocommerce_login_form_start' );
		?>

		<div class="form-group username">
			<?php
			$username       = ! empty( $_POST['username'] ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : '';
			$username_label = esc_html__( 'Username or email address', 'woocommerce' );

			echo '<label for="username">' . $username_label . ' <span class="required">*</span></label>';
			echo '<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="username" autocomplete="username" spellcheck="false" value="' . $username . '" />';
			?>
		</div>

		<div class="form-group password">
			<?php
			echo '<label for="password">' . esc_html__( 'Password', 'woocommerce' ) . ' <span class="required">*</span></label>';

			// Builder: Add span to wrap password input manually (no JS enqueued) to show password toggle icon
			if ( bricks_is_builder() || bricks_is_builder_call() ) {
				echo '<span class="password-input">';
			}

			echo '<input class="woocommerce-Input woocommerce-Input--text input-text" type="password" name="password" id="password" autocomplete="current-password"" />';

			if ( bricks_is_builder() || bricks_is_builder_call() ) {
				echo '<span class="show-password-input"></span>';
				echo '</span>';
			}
			?>
		</div>

		<?php do_action( 'woocommerce_login_form' ); ?>

		<?php if ( ! isset( $settings['hideRememberMe'] ) ) { ?>
		<div class="form-group remember">
			<label class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme">
				<input class="woocommerce-form__input woocommerce-form__input-checkbox" name="rememberme" type="checkbox" id="rememberme" value="forever" /> <span><?php esc_html_e( 'Remember me', 'woocommerce' ); ?></span>
			</label>
		</div>
		<?php } ?>

		<div class="form-group submit">
			<?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>

			<button type="submit" class="woocommerce-button button woocommerce-form-login__submit<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" name="login" value="<?php esc_attr_e( 'Log in', 'woocommerce' ); ?>">
				<?php esc_html_e( 'Log in', 'woocommerce' ); ?>
			</button>
		</div>

		<?php if ( ! isset( $settings['hideLostPassword'] ) ) { ?>
		<div class="woocommerce-LostPassword lost_password">
			<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php esc_html_e( 'Lost your password?', 'woocommerce' ); ?></a>
		</div>
		<?php } ?>

		<?php
		do_action( 'woocommerce_login_form_end' );

		return ob_get_clean();
	}
}
