<?php
namespace Bricks\Integrations\Polylang;

use Bricks\Elements;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Polylang {
	public static $is_active = false;

	public function __construct() {
		self::$is_active = class_exists( 'Polylang' );

		if ( ! self::$is_active ) {
			return;
		}

		add_action( 'init', [ $this, 'init_elements' ] );

		add_filter( 'bricks/helpers/get_posts_args', [ $this, 'polylang_get_posts_args' ] );
		add_filter( 'bricks/ajax/get_pages_args', [ $this, 'polylang_get_posts_args' ] );

		add_action( 'wp_enqueue_scripts', [ $this, 'wp_enqueue_scripts' ] );

		add_filter( 'pll_copy_post_metas', [ $this, 'copy_bricks_post_metas' ], 10, 3 );

		add_filter( 'bricks/search_form/home_url', [ $this, 'modify_search_form_home_url' ] );

		add_filter( 'bricks/builder/post_title', [ $this, 'add_langugage_to_post_title' ], 10, 2 );
	}

	public function wp_enqueue_scripts() {
		wp_enqueue_style( 'bricks-polylang', BRICKS_URL_ASSETS . 'css/integrations/polylang.min.css', [ 'bricks-frontend' ], filemtime( BRICKS_PATH_ASSETS . 'css/integrations/polylang.min.css' ) );
	}

	/**
	 * Copy Bricks' post metas when duplicating a post
	 *
	 * @since 1.9.1
	 */
	public function copy_bricks_post_metas( $metas, $sync, $original_post_id ) {
		// Return: Do not copy metas when syncing (let Polylang handle it)
		if ( $sync ) {
			return $metas;
		}

		// Return: Do not copy Bricks' metas when the post is not rendered with Bricks
		if ( \Bricks\Helpers::get_editor_mode( $original_post_id ) !== 'bricks' ) {
			return $metas;
		}

		$meta_keys_to_check = [
			BRICKS_DB_TEMPLATE_TYPE,
			BRICKS_DB_EDITOR_MODE,
			BRICKS_DB_PAGE_SETTINGS,
			BRICKS_DB_TEMPLATE_SETTINGS,
		];

		$template_type = get_post_meta( $original_post_id, BRICKS_DB_TEMPLATE_TYPE, true );

		if ( $template_type === 'header' ) {
			$meta_keys_to_check[] = BRICKS_DB_PAGE_HEADER;
		} elseif ( $template_type === 'footer' ) {
			$meta_keys_to_check[] = BRICKS_DB_PAGE_FOOTER;
		} else {
			$meta_keys_to_check[] = BRICKS_DB_PAGE_CONTENT;
		}

		$additional_metas = [];

		// Add metas only if they exist
		foreach ( $meta_keys_to_check as $meta_key_to_check ) {
			if ( metadata_exists( 'post', $original_post_id, $meta_key_to_check ) ) {
				$additional_metas[] = $meta_key_to_check;
			}
		}

		return array_merge( $metas, $additional_metas );
	}

	/**
	 * Init Polylang elements
	 *
	 * polylang-language-switcher
	 */
	public function init_elements() {
		$polylang_elements = [
			'polylang-language-switcher',
		];

		foreach ( $polylang_elements as $element_name ) {
			$polylang_element_file = BRICKS_PATH . "includes/integrations/polylang/elements/$element_name.php";

			// Get the class name from the element name
			$class_name = str_replace( '-', '_', $element_name );
			$class_name = ucwords( $class_name, '_' );
			$class_name = "Bricks\\$class_name";

			if ( is_readable( $polylang_element_file ) ) {
				Elements::register_element( $polylang_element_file, $element_name, $class_name );
			}
		}
	}

	/**
	 * Set the query arg to get all the posts/pages languages
	 *
	 * @param array $query_args
	 * @return array
	 */
	public function polylang_get_posts_args( $query_args ) {
		if ( ! isset( $query_args['lang'] ) ) {
			$query_args['lang'] = '';
		}

		return $query_args;
	}

	/**
	 * Modify the search form action URL to use the home URL
	 *
	 * @param string $url
	 * @return string
	 *
	 * @since 1.9.4
	 */
	public function modify_search_form_home_url( $url ) {
		if ( function_exists( 'pll_home_url' ) ) {
			return pll_home_url();
		}

		return $url;
	}

	/*
	 * Add language code to post title
	 *
	 * @param string $title   The original title of the page.
	 * @param int    $page_id The ID of the page.
	 * @return string The modified title with the language suffix.
	 *
	 * @since 1.9.4
	 */
	public function add_langugage_to_post_title( $title, $page_id ) {
		if ( isset( $_GET['addLanguageToPostTitle'] ) ) {
			$language_code = function_exists( 'pll_get_post_language' ) ? strtoupper( pll_get_post_language( $page_id ) ) : '';

			if ( ! empty( $language_code ) ) {
				return "[$language_code] $title";
			}
		}

		// Return the original title if conditions are not met
		return $title;
	}
}
