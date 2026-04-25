<?php

/**
 * Captcha class
 *
 * @since 1.6
 */

namespace dologin;

defined('WPINC') || exit;

class Captcha extends Instance
{
	/**
	 * Display recaptcha
	 *
	 * @since  1.6
	 */
	public function show()
	{
		wp_register_script('dologin_cf_api', 'https://challenges.cloudflare.com/turnstile/v0/api.js', array(), null);
		wp_enqueue_script('dologin_cf_api');

		echo '<div class="cf-turnstile" data-sitekey="' . Conf::val('cf_pub_key') . '"></div>';
	}

	/**
	 * Validate recaptcha
	 *
	 * @since  1.6
	 */
	public function authenticate()
	{
		// Validate
		if (empty($_POST['cf-turnstile-response'])) {
			throw new \Exception('captcha_missing');
		}

		// Check if stored token matches, then bypass
		if ($this->_validate_token()) {
			defined('debug') && debug('✅ bypassed, token matched');
			return;
		}

		$url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
		$data = array(
			'secret' 	=> Conf::val('cf_priv_key'),
			'response' 	=> $_POST['cf-turnstile-response'],
			'remoteip' => IP::me(),
		);

		$res = wp_remote_post($url, array('body' => $data, 'timeout' => 15, 'sslverify' => false));

		if (is_wp_error($res)) {
			$error_message = $res->get_error_message();
			throw new \Exception($error_message);
		}

		$res = json_decode($res['body'], true);
		defined('debug') && debug('2fa challenge res:', $res);

		if (empty($res['success'])) {
			$err_code = !empty($res['error-codes'][0]) ? $res['error-codes'][0] : 'error';

			throw new \Exception($err_code);
		}

		// Mark this session as trusted, to prevent duplicate check when submitting 2FA
		$this->_store_token();

		defined('debug') && debug('✅ passed');
	}

	/**
	 * Store token for 2nd step verification use
	 *
	 * @since 4.2
	 */
	private function _store_token() {
		$token = md5($_POST['cf-turnstile-response'] . IP::me());
		$expiration = 5 * MINUTE_IN_SECONDS;
		set_transient($this->_generate_token_tag(), $token, $expiration);
	}

	/**
	 * Generate the token tag to use in storage
	 *
	 * @since 4.2
	 */
	private function _generate_token_tag() {
		$tag = IP::me();
		if (!empty($_POST['log'])) {
			$tag = $_POST['log'];
		}
		if (!empty($_POST['user_login'])) {
			$tag = $_POST['user_login'];
		}
		return 'dologin_tmp_data_' . md5($tag);
	}

	/**
	 * One time token validation and delete
	 *
	 * @since 4.2
	 */
	private function _validate_token() {
		$token = md5($_POST['cf-turnstile-response'] . IP::me());
		$transient_key = $this->_generate_token_tag();
		$stored_token = get_transient($transient_key);

		if ($stored_token === $token) {
			delete_transient($transient_key);
			return true;
		}

		return false;
	}
}
