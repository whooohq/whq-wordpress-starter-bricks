<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Compatibility {
	public function __construct() {}

	public static function register() {
		$instance = new self();

		// Autoptimize (disable in builder)
		if ( bricks_is_builder() ) {
			add_filter(
				'autoptimize_filter_noptimize',
				function() {
					return true;
				}
			);
		}

		// Learndash
		add_filter( 'learndash_course_grid_post_extra_course_grids', [ $instance, 'learndash_course_grid_load_assets' ], 10, 2 );

		// Litespeed
		add_action( 'litespeed_init', [ $instance, 'litespeed_no_cache' ] );

		// Weglot
		if ( function_exists( 'weglot_get_current_language' ) ) {
			add_action( 'init', [ $instance, 'weglot_disable_translation' ] );
		}

		// Paid Memberships Pro: Restrict Bricks content (@since 1.5.4)
		if ( function_exists( 'pmpro_has_membership_access' ) ) {
			add_filter( 'bricks/render_with_bricks', [ $instance, 'pmpro_has_membership_access' ], 10, 1 );
		}

		// TranslatePress (@since 1.6)
		if ( bricks_is_builder() ) {
			// Not working as it runs too early (on plugins_loaded)
			// add_filter( 'trp_enable_translatepress', '__return_false' );

			add_filter( 'trp_allow_tp_to_run', '__return_false' );
			add_filter( 'trp_stop_translating_page', '__return_true' );

			// TranslatePress: Remove language switcher HTML in builder
			add_filter(
				'trp_floating_ls_html',
				function( $html ) {
					return '';
				}
			);
		}

		// Yith WooCommerce Product Add-Ons: dequeue script at priority 11 to make sure it's enqueued
		add_action( 'wp_enqueue_scripts', [ $instance, 'yith_wapo_dequeue_script' ], 11 );
	}

	/**
	 * Learndash Course Grid Add One: Load assets if shortcode found
	 *
	 * wp_enqueue_scripts for learndash_course_grid_load_resources() only loads pre 2.0 legacy assets from [ld_course_list]
	 *
	 * @see class-compatibility.php integration for Elementor
	 *
	 * @since 1.7
	 */
	public function learndash_course_grid_load_assets( $course_grids, $post ) {
		if ( ! is_a( $post, 'WP_Post' ) ) {
			return $course_grids;
		}

		$bricks_data = Helpers::get_bricks_data( $post->ID, 'content' );

		if ( $bricks_data && is_array( $bricks_data ) ) {
			$bricks_data = wp_json_encode( $bricks_data );
		}

		if ( function_exists( '\LearnDash\course_grid' ) ) {
			$tags = \LearnDash\course_grid()->skins->parse_content_shortcodes( $bricks_data, [] );
		}

		$course_grids[] = $tags;

		return $course_grids;
	}

	/**
	 * LiteSpeed Cache plugin: Ignore Bricks builder
	 *
	 * Tested with version 3.6.4
	 *
	 * @return void
	 */
	public function litespeed_no_cache() {
		if ( isset( $_GET['bricks'] ) && $_GET['bricks'] === 'run' ) {
			do_action( 'litespeed_disable_all', 'bricks editor' );
		}
	}

	/**
	 * Weglot: Disable Weglot translations inside the builder
	 *
	 * @since 1.8.6
	 *
	 * @return void
	 */
	public function weglot_disable_translation() {
		if ( isset( $_GET['bricks'] ) && $_GET['bricks'] == 'run' ) {
			add_filter( 'weglot_active_translation', '__return_false' );
		}
	}

	/**
	 * Check if user has membership access to Bricks content in Helpers::render_with_bricks
	 *
	 * @since 1.5.4
	 */
	public function pmpro_has_membership_access( $render ) {
		return pmpro_has_membership_access();
	}

	/**
	 * Yith WooCommerce Product Add-Ons: Dequeue script on builder as it conflicts with Bricks drag & drop
	 *
	 * @since 1.6.2
	 */
	public function yith_wapo_dequeue_script() {
		if ( bricks_is_builder() && wp_script_is( 'yith_wapo_front', 'enqueued' ) ) {
			wp_dequeue_script( 'yith_wapo_front' );
		}
	}
}
