<?php

if (!defined('ABSPATH')) die('No direct access allowed');

if (!class_exists('Updraft_Deinstall_Dialog_v1')) {

	/**
	 * Class Updraft_Deinstall_Dialog
	 *
	 * Provides a customizable de-installation confirmation dialog for WordPress plugins,
	 * with support for jQuery UI or Thickbox modal dialogs. Handles AJAX-based confirmation 
	 * and executes plugin-specific actions based on user input.
	 */
	class Updraft_Deinstall_Dialog_v1 {

		/**
		 * Static library version.
		 *
		 * Update this value only when changes are made that affect the dialog’s
		 * JavaScript, CSS, or rendered HTML (e.g. altering assets, markup, or UI
		 * behavior). This ensures proper cache busting and prevents conflicts with
		 * previously enqueued scripts.
		 *
		 * Backend-only PHP changes that do not affect the output or assets do not
		 * require a version bump.
		 *
		 * @var string $version Version of the dialog script.
		 */
		const VERSION = 'v1';

		/**
		 * @var array $options Array of configuration options for the dialog.
		 */
		private $options = array();

		/**
		 * Constructor for Updraft_Deinstall_Dialog.
		 *
		 * Initializes options and sets up necessary hooks for enqueueing assets,
		 * handling AJAX requests, and adding plugin action links.
		 *
		 * @param array $options Required. Array of dialog options.
		 *  - 'script_handler'   => string Required. Dialog script handler name used in AJAX and enqueue hooks.
		 *  - 'plugin_slug'      => string Required. Main plugin file path relative to wp-content/plugins, e.g. updraftplus/updraftplus.php
		 *  - 'dialog_title'     => string Optional. Title for the confirmation dialog. Default 'Deactivation Confirmation'.
		 *  - 'deactivate_label' => string Optional. Label for the "Deactivate" button. Default 'Deactivate'.
		 *  - 'cancel_label'     => string Optional. Label for the "Cancel" button. Default 'Cancel'.
		 *  - 'dialog_type'      => string Optional. Type of dialog to display ('jqueryui' or 'thickbox'). Default 'jqueryui'.
		 *  - 'template_file'    => string Optional. Path to the HTML template for the dialog content. Default 'templates/dialog.php'.
		 *
		 * @throws InvalidArgumentException If required options 'script_handler' or 'plugin_slug' are missing.
		 */
		public function __construct($options) {
			if (!isset($options['script_handler'], $options['plugin_slug'])) throw new InvalidArgumentException('Missing required options.');
		
			if (isset($options['dialog_type']) && !in_array($options['dialog_type'], array('jqueryui', 'thickbox'))) unset($options['dialog_type']);
			
			// Set default options and merge with any passed options.
			$defaults = array(
				'dialog_title' => 'Deactivation Confirmation',
				'deactivate_label' => 'Deactivate',
				'cancel_label' => 'Cancel',
				'dialog_type' => 'jqueryui',
				'template_file' => plugin_dir_path(__FILE__).'templates/dialog.php',
				'show_on_network_admin' => true,
				'show_on_subsites' => true,
			);

			$this->options = array_merge($defaults, $options);

			add_action('admin_enqueue_scripts', array($this, 'enqueue_dialog_assets'));
			add_filter('plugin_action_links', array($this, 'modify_deactivate_link'), 10, 2);
			add_filter('network_admin_plugin_action_links', array($this, 'modify_deactivate_link'), 10, 2);
			add_action('wp_ajax_'.$this->options['script_handler'].'_deinstall_confirm', array($this, 'handle_deinstall_confirm'));
		}

		/**
		 * Enqueue dialog assets (JS) based on the specified dialog type.
		 *
		 * Only enqueues on the plugins page and localizes dialog data for use in JavaScript.
		 */
		public function enqueue_dialog_assets() {
			global $pagenow;

			if ('plugins.php' !== $pagenow) return;

			if ($this->options['dialog_type'] === 'thickbox') {
				wp_enqueue_script('thickbox');
				wp_enqueue_style('thickbox');
				$file = plugins_url('assets/js/deinstall-dialog-thickbox.js', __FILE__);
				$dependencies = array('jquery', 'thickbox');
			} else {
				$file = plugins_url('assets/js/deinstall-dialog-jqueryui.js', __FILE__);
				$dependencies = array('jquery', 'jquery-ui-dialog');
			}

			$script_handler = 'updraft-deinstall-dialog-'.$this->options['script_handler'].'-'.$this->options['dialog_type'].'-'.self::VERSION;

			wp_register_script($script_handler, $file, $dependencies, self::VERSION, true);

			$data = array(
				'nonce' => wp_create_nonce($this->options['script_handler'].'_deinstall_nonce'),
				'dialog_html' => $this->load_dialog_template($this->options['template_file']),
				'dialog_title' => $this->options['dialog_title'],
				'deactivate_label' => $this->options['deactivate_label'],
				'cancel_label' => $this->options['cancel_label'],
			);

			wp_localize_script(
				$script_handler,
				'updraft_deinstall_data_'.$this->options['script_handler'],
				$data
			);

			wp_enqueue_script($script_handler);
		}

		/**
		 * Load the HTML template for the dialog.
		 *
		 * @param string $file Path to the dialog template file.
		 * @return string HTML content of the dialog.
		 */
		public function load_dialog_template($file) {
			$form = '<form id="'.$this->options['script_handler'].'-deactivate-form">';
			ob_start();
			include $file;
			$form .= ob_get_clean();
			$form .= '</form>';

			return $form;
		}

		/**
		 * Handle AJAX request to confirm de-installation.
		 *
		 * Verifies nonce and triggers the main deinstallation action for the plugin.
		 */
		public function handle_deinstall_confirm() {
			if (!current_user_can('deactivate_plugins') && (!isset($_POST['_nonce']) || !wp_verify_nonce($_POST['_nonce'], $this->options['script_handler'].'_deinstall_nonce'))) {
				wp_send_json_error(array('message' => 'Nonce and/or capability verification failed.'), 403);
			}
			
			do_action($this->options['script_handler'].'_deinstall');
			wp_send_json_success();
		}

		/**
		 * Modify the plugin action links to attach deinstallation data attribute.
		 *
		 * @param array $links Array of action links for the plugin.
		 * @param string $file Current plugin file.
		 * @return array Modified array of action links.
		 */
		public function modify_deactivate_link($links, $file) {

			if (!is_array($links) || $this->options['plugin_slug'] !== $file) return $links;

			if (is_multisite()) {

				if (!is_network_admin() && empty($this->options['show_on_subsites'])) {
					return $links;
				}

				if (is_network_admin() && empty($this->options['show_on_network_admin'])) {
					return $links;
				}
			}
				foreach ($links as $key => $value) {
					if ($key != 'deactivate') continue;

					$links[$key] = preg_replace(
						'/<a(.*?)href="(.*?)"(.*?)>/i', 
						'<a$1href="$2"$3 onclick="window.updraft_deinstall_'.$this->options['dialog_type'].'_'.self::VERSION.'(event, \''.esc_attr($this->options['script_handler']).'\');">', 
						$value
					);
				}
			return $links;
		}
	}
}
