<?php
/**
 * Updraft Theme.
 */

if (!defined('ABSPATH')) die('Access denied.');

define('TU_THEME_DIR', dirname(__FILE__));
define('TU_THEME_URL', trailingslashit(plugins_url('', __FILE__)));

class TU_Theme {
	const VERSION = '0.1';

	protected static $_instance = null;

	/**
	 * Creates an instance of this class. Singleton Pattern
	 *
	 * @return object Instance of this class
	 */
	public static function instance() {
		if (empty(self::$_instance)) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Returns the version to use when enqueueing or loading scripts and styles.
	 * If WP_DEBUG is on then time is padded to the VERSION constant.
	 *
	 * @return string
	 */
	private function get_enqueue_version() {
		return (defined('WP_DEBUG') && WP_DEBUG) ? self::VERSION.'.'.time() : self::VERSION;
	}

	/**
	 * Constructor.
	 *
	 * @return self
	 */
	public function __construct() {
		add_action('wp_enqueue_scripts', array($this, 'load_css'), 20);
		add_action('wp_enqueue_scripts', array($this, 'load_js'), 20);
	}

	/**
	 * Returns all the handlebar template's contents keyed by their path and name.
	 *
	 * @return array
	 */
	public function get_handlebar_templates() {
		$base_directory = TU_THEME_DIR . '/components/';
		$templates_array = array();

		// Recursive directory iterator to find all .handlebars.html files
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($base_directory),
			RecursiveIteratorIterator::SELF_FIRST
		);

		foreach ($iterator as $file) {
			// Check for '.' and '..' and skip them.
			if ($file->getFilename() === '.' || $file->getFilename() === '..') {
				continue;
			}

			if ($file->isFile() && preg_match('/\.handlebars\.html$/', $file->getFilename())) {
				// Get relative path from base directory
				$relative_path = str_replace($base_directory, '', $file->getPathname());

				// Remove extension
				$relative_path = preg_replace('/\.handlebars\.html$/', '', $relative_path);

				// Replace directory separators with hyphens
				$key = str_replace(DIRECTORY_SEPARATOR, '-', $relative_path);

				// Store file contents
				$templates_array[$key] = file_get_contents($file->getPathname());
			}
		}

		return $templates_array;
	}

	/**
	 * Load CSS.
	 */
	public function load_css() {
		$enqueue_version = $this->get_enqueue_version();

		// Global critical styles.
		wp_enqueue_style('tu-global-theme', TU_THEME_URL.'theme.css', array(), $enqueue_version);

		// Allow projects to opt-out from theme colors and enqueue there own colors.
		if (apply_filters('tu_theme_load_colors', true)) {
			wp_enqueue_style('tu-theme-colors', TU_THEME_URL.'theme-colors.css', array(), $enqueue_version);
		}
	}

	/**
	 * Load JS.
	 */
	public function load_js() {
		$enqueue_version = $this->get_enqueue_version();

		wp_enqueue_script('tu-theme-handlebars-library', TU_THEME_URL . 'handlebar-library/handlebars.min.js', array(), $enqueue_version, false);
		wp_enqueue_script('tu-theme', TU_THEME_URL . 'theme.js', array('tu-theme-handlebars-library'), $enqueue_version, false);
		wp_localize_script(
			'tu-theme',
			'TU_Theme',
			array(
				'handlebar_templates' => $this->get_handlebar_templates(),
			)
		);
	}
}