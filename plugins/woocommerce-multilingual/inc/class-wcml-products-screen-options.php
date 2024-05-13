<?php

/**
 * Class WCML_Screen_Options
 */
class WCML_Products_Screen_Options {

	/**
	 * Setup hooks.
	 */
	public function init() {
		add_filter( 'default_hidden_columns', [ $this, 'filter_screen_options' ], 10, 2 );
		add_filter( 'wpml_hide_management_column', [ $this, 'sitepress_screen_option_filter' ], 10, 2 );
	}

	/**
	 * Hide management column by default for products.
	 *
	 * @param bool   $is_visible
	 * @param string $post_type
	 *
	 * @return bool
	 */
	public function sitepress_screen_option_filter( $is_visible, $post_type ) {
		if ( 'product' === $post_type ) {
			$is_visible = false;
		}

		return $is_visible;
	}

	/**
	 * Set default option for translations management column.
	 *
	 * @param string[]  $hidden
	 * @param WP_Screen $screen
	 *
	 * @return string[]
	 */
	public function filter_screen_options( $hidden, $screen ) {
		if ( 'edit-product' === $screen->id ) {
			$hidden[] = 'icl_translations';
		}
		return $hidden;
	}
}
