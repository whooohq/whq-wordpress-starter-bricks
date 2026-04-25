<?php
if (!defined('UPDRAFTPLUS_DIR')) die('No direct access allowed');

/**
 * Handles the UpdraftPlus deactivation popup modal.
 */
class UpdraftPlus_Deactivation {
		
	private static $instance = null;

	/**
	 * Get singleton instance
	 */
	public static function get_instance() {
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action('init', array($this, 'init_deinstall_dialog'));
	}

	/**
	 * Initialize the deactivation popup on admin plugins page or for AJAX requests
	 * Sets up the hooks required for initializing the deactivation dialog
	 */
	public function init_deinstall_dialog() {
		global $pagenow;
		if ((defined('WP_AJAX') && WP_AJAX) || 'admin-ajax.php' == $pagenow) {
			add_action('updraftplus_deinstall', array($this, 'handle_user_choice'));
			$this->instantiate_deinstall_dialog_object();
		} elseif ('plugins.php' == $pagenow) {
			add_action('admin_init', array($this, 'instantiate_deinstall_dialog_object'));
			add_action('updraftplus_admin_enqueue_scripts', array($this, 'enqueue_dialog_scripts'));
			if (!class_exists('UpdraftPlus_Addon_Autobackup')) {
				add_action('admin_footer', array($this, 'enqueue_admin_common_scripts'));
				add_action('admin_print_footer_scripts', array($this, 'add_footer_inline_script'));
			}
		}
	}

	/**
	 * Print additional javascript in the footer part of the page
	 */
	public function add_footer_inline_script() {
		if (!UpdraftPlus_Options::user_can_manage()) {
			return;
		}
		echo "<script type='text/javascript'>\n";
		// in free version on plugins.php page, since the deinstall dialog requires admin-common-js, the updraft_credentialtest_nonce variable should be declared because it's going to be used by the updraft_send_command for background operations, if not declared it will produce JS error in the browser's console; auto-backup does this too.
		echo "var updraft_credentialtest_nonce = updraft_credentialtest_nonce || '".esc_js(wp_create_nonce('updraftplus-credentialtest-nonce'))."';";
		echo "\n</script>";
	}

	/**
	 * Enqueue our admin common scripts/styles
	 */
	public function enqueue_admin_common_scripts() {
		global $updraftplus_admin;
		$updraftplus_admin->admin_enqueue_scripts();
	}

	/**
	 * Enqueue deactivation dialog scripts
	 */
	public function enqueue_dialog_scripts() {
		global $updraftplus;
		$enqueue_version = $updraftplus->use_unminified_scripts() ? $updraftplus->version.'.'.time() : $updraftplus->version;
		$updraft_min_or_not = $updraftplus->get_updraftplus_file_version();
		wp_enqueue_style('updraft-deactivation-popup', UPDRAFTPLUS_URL.'/css/updraftplus-deactivation'.$updraft_min_or_not.'.css', array('updraft-jquery-ui'), $enqueue_version);
		wp_enqueue_script('udp-deactivation-js', UPDRAFTPLUS_URL.'/js/updraftplus-deactivation'.$updraft_min_or_not.'.js', array('jquery'), $enqueue_version);
		wp_localize_script('udp-deactivation-js', 'upraftplusdialog', array(
			'deactivate' => esc_html__('Deactivate', 'updraftplus'),
			'remove' => esc_html__('Remove and deactivate', 'updraftplus'),
		));
	}

	/**
	 * Instantiate a deactivation dialog object with preconfigured settings
	 */
	public function instantiate_deinstall_dialog_object() {

		if (!class_exists('Updraft_Deinstall_Dialog_v1')) {
			updraft_try_include_file(
				'vendor/team-updraft/common-libs/src/updraft-plugin-deinstall-dialog/class-updraft-deinstall-dialog.php',
				'include_once'
			);
		}

		if (class_exists('Updraft_Deinstall_Dialog_v1')) {
			new Updraft_Deinstall_Dialog_v1(array(
				'script_handler'   => 'updraftplus',
				'plugin_slug'      => UPDRAFTPLUS_PLUGIN_SLUG,
				'dialog_title'     => esc_html__('Before you deactivate...', 'updraftplus'),
				'deactivate_label' => esc_html__('Deactivate', 'updraftplus'),
				'cancel_label'     => esc_html__('Cancel', 'updraftplus'),
				'template_file'    => UPDRAFTPLUS_DIR . '/templates/deactivation-popup-modal.php',
				'show_on_network_admin' => true,
				'show_on_subsites' => false,
			));
		}
	}

	/**
	 * Handle the AJAX request to save the user’s deactivation choice.
	 * The capability check is handled by the dialog library.
	 */
	public function handle_user_choice() {

		$choice = UpdraftPlus_Manipulation_Functions::fetch_superglobal('post', 'updraft_deinstall_option', 'no', true, null, 'sanitize_text_field');
		UpdraftPlus_Options::update_updraft_option('updraftplus_deinstall_option', $choice);
	}
}
