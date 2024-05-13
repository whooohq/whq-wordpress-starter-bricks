<?php
namespace Bricks\Integrations\Wpml;

use Bricks\Elements;
use Bricks\Database;
use Bricks\Helpers;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Wpml {
	public static $is_active = false;
	public $wpml_identifier  = 'Bricks';

	public function __construct() {
		self::$is_active = self::is_wpml_active();

		if ( ! self::$is_active ) {
			return;
		}

		add_action( 'init', [ $this, 'init_elements' ] );

		// WPML (@since 1.7)
		if ( function_exists( 'icl_object_id' ) ) {
			add_filter( 'bricks/database/bricks_get_all_templates_by_type_args', [ $this, 'wpml_get_posts_args' ] );
		}

		add_filter( 'wpml_page_builder_support_required', [ $this, 'wpml_page_builder_support_required' ], 10, 1 );
		add_action( 'wpml_page_builder_register_strings', [ $this, 'wpml_page_builder_register_strings' ], 10, 2 );
		add_action( 'wpml_page_builder_string_translated', [ $this, 'wpml_page_builder_string_translated' ], 10, 5 );

		// Addressing all page builder "Corner cases"
		// https://git.onthegosystems.com/glue-plugins/wpml/wpml-page-builders/-/wikis/Integrating-a-page-builder-with-WPML#corner-cases
		add_filter( 'wpml_pb_is_editing_translation_with_native_editor', [ $this, 'wpml_pb_is_editing_translation_with_native_editor' ], 10, 2 );
		add_filter( 'wpml_pb_is_page_builder_page', [ $this, 'wpml_pb_is_page_builder_page' ], 10, 2 );

		// Hide WPML language switcher for specific Bricks admin pages
		add_action( 'admin_head', [ $this, 'hide_wpml_language_switcher_for_bricks' ] );

		// WPML Media Translation
		add_filter( 'wp_get_attachment_image_src', [ $this, 'translate_attachment_image_src' ], 10, 3 );

		add_filter( 'bricks/builder/post_title', [ $this, 'add_langugage_to_post_title' ], 10, 2 );
	}

	/**
	 * Hide the WPML language switcher on specified Bricks admin pages.
	 */
	public function hide_wpml_language_switcher_for_bricks() {
		global $pagenow;

		$bricks_admin_pages_to_hide_language_switcher = [ 'bricks-settings' ];

		if ( $pagenow == 'admin.php' && isset( $_GET['page'] ) && in_array( $_GET['page'], $bricks_admin_pages_to_hide_language_switcher ) ) {
			echo '<style>
				#wp-admin-bar-WPML_ALS {
					display: none !important;
				}
			</style>';
		}
	}

	/**
	 * Check if WPML plugin is active
	 *
	 * @return boolean
	 */
	public static function is_wpml_active() {
		return class_exists( 'SitePress' );
	}

	/**
	 * Init WPML elements
	 */
	public function init_elements() {
		$wpml_elements = [ 'wpml-language-switcher' ];

		foreach ( $wpml_elements as $element_name ) {
			$wpml_element_file = BRICKS_PATH . "includes/integrations/wpml/elements/$element_name.php";

			// Get the class name from the element name
			$class_name = str_replace( '-', '_', $element_name );
			$class_name = ucwords( $class_name, '_' );
			$class_name = "Bricks\\$class_name";

			if ( is_readable( $wpml_element_file ) ) {
				Elements::register_element( $wpml_element_file, $element_name, $class_name );
			}
		}
	}

	/**
	 * WPML: Add 'suppress_filters' => false query arg to get templates of currently viewed language
	 *
	 * @param array $query_args
	 * @return array
	 *
	 * @since 1.7
	 */
	public function wpml_get_posts_args( $query_args ) {
		if ( ! isset( $query_args['suppress_filters'] ) ) {
			$query_args['suppress_filters'] = false;
		}

		return $query_args;
	}

	/**
	 * WMPL: Register 'Bricks' identifier for WPML
	 *
	 * https://git.onthegosystems.com/glue-plugins/wpml/wpml-page-builders/-/wikis/Integrating-a-page-builder-with-WPML#declaring-support-for-a-page-builder
	 *
	 * @since 1.8
	 */
	public function wpml_page_builder_support_required( $plugins ) {
		$plugins[] = $this->wpml_identifier; // = 'Bricks'

		return $plugins;
	}

	/**
	 * WPML: Register text strings of Bricks elements for translation in WPML
	 *
	 * @param \WP_Post|stdClass $post
	 * @param array             $package_data
	 *
	 * @since 1.8
	 */
	public function wpml_page_builder_register_strings( $post, $package_data ) {
		// Return: Package is not for 'Bricks'
		if ( $package_data['kind'] !== $this->wpml_identifier ) {
			return;
		}

		$template_type = get_post_meta( $post->ID, BRICKS_DB_TEMPLATE_TYPE, true );

		switch ( $template_type ) {
			case 'header':
				$bricks_elements = Database::get_data( $post->ID, 'header' );
				break;
			case 'footer':
				$bricks_elements = Database::get_data( $post->ID, 'footer' );
				break;
			default:
				$bricks_elements = Database::get_data( $post->ID, 'content' );
				break;
		}

		if ( empty( $bricks_elements ) || ! is_array( $bricks_elements ) ) {
			return;
		}

		// Loop over all elements and register their text values
		foreach ( $bricks_elements as $element ) {
			$this->process_element( $element, $post );
		}
	}

	private function process_element( $element, $post ) {
		$element_name     = ! empty( $element['name'] ) ? $element['name'] : false;
		$element_settings = ! empty( $element['settings'] ) ? $element['settings'] : false;
		$element_config   = Elements::get_element( [ 'name' => $element_name ] );
		$element_controls = ! empty( $element_config['controls'] ) ? $element_config['controls'] : false;
		$element_label    = ! empty( $element_config['label'] ) ? $element_config['label'] : $element_name;

		if ( ! $element_settings || ! $element_name || ! is_array( $element_controls ) ) {
			return;
		}

		$translatable_control_types = [ 'text', 'textarea', 'editor', 'repeater', 'link' ];

		// Loop over element controls to get translatable settings
		foreach ( $element_controls as $key => $control ) {
			$this->process_control( $key, $control, $element_settings, $element, $element_label, $translatable_control_types, $post );
		}
	}

	private function process_control( $key, $control, $element_settings, $element, $element_label, $translatable_control_types, $post ) {
		$control_type = ! empty( $control['type'] ) ? $control['type'] : false;

		if ( ! in_array( $control_type, $translatable_control_types ) ) {
			return;
		}

		// Exclude certain controls from translation according to their key (@since 1.9.2)
		$exclude_control_from_translation = [ 'customTag', '_gridTemplateColumns', '_gridTemplateRows', '_cssId', 'targetSelector' ];

		if ( in_array( $key, $exclude_control_from_translation ) ) {
			return;
		}

		$string_value = ! empty( $element_settings[ $key ] ) ? $element_settings[ $key ] : '';

		if ( $control_type == 'repeater' && isset( $control['fields'] ) ) {
			$this->process_repeater_control( $key, $control, $element_settings, $element, $element_label, $translatable_control_types, $post );
			return;
		}

		// If control type is link, specifically process the URL
		if ( $control_type === 'link' && isset( $string_value['url'] ) ) {
			$string_value = $string_value['url'];
		}

		if ( ! is_string( $string_value ) || empty( $string_value ) ) {
			return;
		}

		$string_id = "{$element['id']}_$key"; // Set WPML string ID to "$element_id-$setting_key"
		$this->register_wpml_string( $string_value, $string_id, $element_label, $post, $control_type );
	}

	private function process_repeater_control( $key, $control, $element_settings, $element, $element_label, $translatable_control_types, $post ) {
		$repeater_items = ! empty( $element_settings[ $key ] ) ? $element_settings[ $key ] : [];

		if ( is_array( $repeater_items ) ) {
			foreach ( $repeater_items as $repeater_index => $repeater_item ) {
				if ( is_array( $repeater_item ) ) {
					foreach ( $repeater_item as $repeater_key => $repeater_value ) {
						// Get the type of this field, check if it's one of the accepted types
						$repeater_field_type = isset( $control['fields'][ $repeater_key ]['type'] ) ? $control['fields'][ $repeater_key ]['type'] : false;
						if ( ! in_array( $repeater_field_type, $translatable_control_types ) ) {
							continue;
						}

						$string_value = ! empty( $repeater_value ) ? $repeater_value : '';

						// If control type is link, get the URL
						if ( $repeater_field_type === 'link' && isset( $string_value['url'] ) ) {
							$string_value = $string_value['url'];
						}

						if ( ! is_string( $string_value ) || empty( $string_value ) ) {
							continue;
						}

						$string_id = "{$element['id']}_{$key}_{$repeater_index}_{$repeater_key}";
						$this->register_wpml_string( $string_value, $string_id, $element_label, $post );
					}
				}
			}
		}
	}

	/**
	 * Helper function to register a string for translation in WPML
	 */
	private function register_wpml_string( $string_value, $string_id, $element_label, $post, $control_type = null ) {
		if ( ! $string_value ) {
			return;
		}

		$string_title = "Bricks ($element_label)"; // Title of the string used in the translation

		// Determine the string type based on control type
		if ( $control_type == 'textarea' ) {
			$string_type = 'TEXTAREA';
		} else {
			$string_type = 'LINE'; // 'LINE', 'TEXTAREA', 'VISUAL'
		}

		$package_data = [
			'kind'    => $this->wpml_identifier,
			'name'    => $post->ID,
			'post_id' => $post->ID,
			'title'   => "Bricks (ID {$post->ID})",
		];

		/**
		 * First, we need to extract the text content and register it as package strings.
		 * We use the term "package" because these strings belong to the post and the package is the entity that groups these strings.
		 *
		 * https://wpml.org/wpml-hook/wpml_register_string/
		 */
		do_action( 'wpml_register_string', $string_value, $string_id, $package_data, $string_title, $string_type );
	}

	/**
	 * WPML: Translated strings are applied to the translated post.
	 *
	 * https://git.onthegosystems.com/glue-plugins/wpml/wpml-page-builders/-/wikis/Integrating-a-page-builder-with-WPML#applying-the-string-translations-in-post-translation
	 *
	 * @param string            $package_kind
	 * @param int               $translated_post_id
	 * @param \WP_Post|stdClass $original_post
	 * @param array             $string_translations
	 * @param string            $lang
	 *
	 * @since 1.8 NOTE: This is a modified version of the original function
	 */
	public function wpml_page_builder_string_translated( $package_kind, $translated_post_id, $original_post, $string_translations, $lang ) {
		// Return: Package is not for 'Bricks'
		if ( $package_kind !== $this->wpml_identifier ) {
			return;
		}

		$original_post_id = $original_post->ID;

		/**
		 * Steps:
		 *
		 * 1. Get Bricks data from original post
		 * 2. Update template type
		 * 3. Update Bricks data with the translated strings
		 * 4. Update template settings if this is a template
		 * 5. Save to the translated post
		 */

		$area          = 'content';
		$template_type = get_post_meta( $original_post_id, BRICKS_DB_TEMPLATE_TYPE, true );

		// Update the BRICKS_DB_TEMPLATE_TYPE of the translated post with the value from the original post
		update_post_meta( $translated_post_id, BRICKS_DB_TEMPLATE_TYPE, $template_type );

		if ( $template_type === 'header' || $template_type === 'footer' ) {
			$area = $template_type;
		}

		$bricks_elements = Database::get_data( $original_post_id, $area );

		if ( ! is_array( $bricks_elements ) ) {
			return;
		}

		// Loop over translations for this post
		foreach ( $string_translations as $string_id => $translation ) {
			// Split the string ID to extract various details (like element ID, setting key, repeater index, etc.)
			$string_parts = explode( '_', $string_id );

			$element_id  = isset( $string_parts[0] ) ? $string_parts[0] : false;
			$setting_key = isset( $string_parts[1] ) ? $string_parts[1] : false;

			// If it's a link, update the URL
			if ( $setting_key === 'link' ) {
				foreach ( $bricks_elements as $index => $element ) {
					if ( $element['id'] === $element_id && isset( $translation[ $lang ]['value'] ) ) {
						$bricks_elements[ $index ]['settings'][ $setting_key ]['url'] = $translation[ $lang ]['value'];
					}
				}
				continue;
			}

			if ( count( $string_parts ) > 3 && isset( $string_parts[2] ) && isset( $string_parts[3] ) ) {
				// Split the string ID to extract various details (like element ID, setting key, repeater index, etc.)
				$string_parts = explode( '_', $string_id );

				// Assign values to $element_id and $setting_key if the corresponding parts are set, else assign false
				$element_id  = $string_parts[0] ?? false;
				$setting_key = $string_parts[1] ?? false;

				// If there are more than 3 parts in the string ID, it indicates this string belongs to a repeater field
				if ( count( $string_parts ) > 3 && isset( $string_parts[2] ) && isset( $string_parts[3] ) ) {
					$repeater_index = $string_parts[2];  // The repeater item index
					$repeater_key   = $string_parts[3];  // The repeater item key

					// Loop through elements to update the repeater field value with the translation
					foreach ( $bricks_elements as $index => $element ) {
						if ( $element['id'] === $element_id && isset( $translation[ $lang ]['value'] ) ) {
							// Define the path for readability
							$path = &$bricks_elements[ $index ]['settings'][ $setting_key ][ $repeater_index ][ $repeater_key ];

							// If $repeater_key is 'link', update the 'url', else update the repeater's specific field with its translated value
							if ( $repeater_key === 'link' && isset( $path['url'] ) ) {
								$path['url'] = $translation[ $lang ]['value'];
							} elseif ( isset( $path ) ) {
								$path = $translation[ $lang ]['value'];
							}
						}
					}
					continue;  // Skip further processing and jump to the next iteration
				}
			}

			if ( ! $element_id || ! $setting_key ) {
				continue;
			}

			// Loop over element and replace their text
			foreach ( $bricks_elements as $index => $element ) {
				// STEP: Check if this is a 'Template' element and replace the template ID with the translated template ID if it exists (@since 1.9.4)
				if ( $element['name'] ?? null === 'template' ) {
					// Fetch the original template ID from the element settings
					$original_template_id = $element['settings']['template'] ?? null;

					if ( $original_template_id ) {
						// Fetch the translated ID of the linked 'bricks_template' post
						$translated_template_id = apply_filters( 'wpml_object_id', $original_template_id, BRICKS_DB_TEMPLATE_SLUG, true, $lang );

						// Check if the translated ID is valid; if not, retain the original ID
						if ( $translated_template_id ) {
							// Replace the original ID with the translated ID
							$bricks_elements[ $index ]['settings']['template'] = $translated_template_id;
						}
					}
				}

				// STEP: Replace the text of the element with the translated text
				if ( $element['id'] === $element_id && isset( $translation[ $lang ]['value'] ) ) {
					$bricks_elements[ $index ]['settings'][ $setting_key ] = $translation[ $lang ]['value'];
				}
			}
		}

		// Save the original post data which now contains the translations
		$meta_key = Database::get_bricks_data_key( $area );

		update_post_meta( $translated_post_id, $meta_key, $bricks_elements );

		// Update template settings if this is a template
		if ( get_post_type( $translated_post_id ) === BRICKS_DB_TEMPLATE_SLUG ) {
			// Get the template settings from the original post
			$original_template_settings = Helpers::get_template_settings( $original_post->ID );

			// Set the original template settings on the translated post
			Helpers::set_template_settings( $translated_post_id, $original_template_settings );
		}

		/**
		 * STEP: Clear unique_inline_css T
		 *
		 * To regenerate CSS file for secondary languages without triggering return on line 2356 in assets.php
		 */
		if ( Database::get_setting( 'cssLoading' ) == 'file' ) {
			\Bricks\Assets::$unique_inline_css = [];
		}
	}

	/**
	 * Translation edited with Bricks (POST 'bricks-is-builder' set)
	 *
	 * Skip translating this post save.
	 *
	 * https://git.onthegosystems.com/glue-plugins/wpml/wpml-page-builders/-/wikis/Integrating-a-page-builder-with-WPML#1-the-translation-is-edited-with-the-page-builder-editor-instead-of-a-wpml-translation-editor
	 *
	 * @param bool $is_translation_with_native_editor
	 * @param int  $translated_post_id
	 *
	 * @since 1.8
	 */
	public function wpml_pb_is_editing_translation_with_native_editor( $is_translation_with_native_editor, $translated_post_id ) {
		if ( ! $is_translation_with_native_editor && isset( $_POST['bricks-is-builder'] ) ) {
			$post_id = ! empty( $_POST['postId'] ) ? intval( $_POST['postId'] ) : false;

			return $translated_post_id === $post_id;
		}

		return $is_translation_with_native_editor;
	}

	/**
	 * Check if post is built & rendered with Bricks
	 *
	 * https://git.onthegosystems.com/glue-plugins/wpml/wpml-page-builders/-/wikis/Integrating-a-page-builder-with-WPML#2-the-original-page-or-post-is-not-built-with-the-page-builder
	 *
	 * @param bool              $is_pb_post
	 * @param \WP_Post|stdClass $post
	 *
	 * @since 1.8
	 */
	public function wpml_pb_is_page_builder_page( $is_pb_post, $post ) {
		if ( ! $is_pb_post ) {
			$post_id       = $post->ID;
			$area          = 'content';
			$template_type = get_post_meta( $post_id, BRICKS_DB_TEMPLATE_TYPE, true );

			if ( $template_type === 'header' || $template_type === 'footer' ) {
				$area = $template_type;
			}

			$meta_key    = Database::get_bricks_data_key( $area );
			$bricks_data = get_post_meta( $post_id, $meta_key, true );

			// Post has Bricks data && is rendered with Bricks
			$editor_mode                    = get_post_meta( $post_id, BRICKS_DB_EDITOR_MODE, true );
			$built_and_rendered_with_bricks = $bricks_data && $editor_mode === 'bricks';

			return $built_and_rendered_with_bricks;
		}

		return $is_pb_post;
	}

	/**
	 * Modify the wp_get_attachment_image_src output to return the translated image src.
	 *
	 * @param array        $image          The array containing the image src and dimensions.
	 * @param int          $attachment_id  The attachment ID.
	 * @param string|array $size           Image size.
	 *
	 * @return array
	 */
	public function translate_attachment_image_src( $image, $attachment_id, $size ) {
		$translated_id = $this->get_translated_attachment_id( $attachment_id );

		// If the translated ID is different than the original, get the src for the translated image.
		if ( $translated_id !== $attachment_id ) {
			$image = wp_get_attachment_image_src( $translated_id, $size );
		}

		return $image;
	}

	/**
	 * Translate the attachment ID to the current language's version.
	 *
	 * @param int $attachment_id
	 *
	 * @return int
	 */
	public function get_translated_attachment_id( $attachment_id ) {
		return apply_filters( 'wpml_object_id', $attachment_id, 'attachment', true );
	}

	/**
	 * Add language code to post title
	 *
	 * @param string $title   The original title of the page.
	 * @param int    $page_id The ID of the page.
	 * @return string The modified title with the language suffix.
	 */
	public function add_langugage_to_post_title( $title, $page_id ) {
		if ( isset( $_GET['addLanguageToPostTitle'] ) ) {
			$language_info = apply_filters( 'wpml_post_language_details', null, $page_id );
			$language_code = ! empty( $language_info['language_code'] ) ? strtoupper( $language_info['language_code'] ) : '';

			if ( $language_code ) {
				return "[$language_code] $title";
			}
		}

		// Return the original title if conditions are not met
		return $title;
	}
}
