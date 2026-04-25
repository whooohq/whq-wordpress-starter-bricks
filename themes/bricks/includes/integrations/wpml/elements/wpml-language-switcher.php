<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Wpml_Language_Switcher extends \Bricks\Element {
	public $category = 'wpml';
	public $name     = 'wpml-language-switcher';
	public $icon     = 'fas fa-language';

	public function get_label() {
		return esc_html__( 'Language switcher', 'bricks' );
	}

	public function set_controls() {
		$this->controls['info'] = [
			'type'    => 'info',
			'content' => esc_html__( 'Customize the language switcher from your WordPress dashboard', 'bricks' ) . ': <a href="' . admin_url( 'admin.php?page=sitepress-multilingual-cms%2Fmenu%2Flanguages.php#wpml-language-switcher-shortcode-action' ) . '" target="_blank">WPML > ' . esc_html__( 'Languages', 'bricks' ) . ' > ' . esc_html__( 'Custom language switchers', 'bricks' ) . '</a>',
		];
	}

	public function render() {
		// Get all active languages
		$languages = apply_filters( 'wpml_active_languages', null, 'skip_missing=0' );

		if ( empty( $languages ) ) {
			return $this->render_element_placeholder(
				[
					'icon-class' => 'ti-alert',
					'title'      => esc_html__( 'No languages found.', 'bricks' ),
				]
			);
		}

		$this->set_attribute( '_root', 'class', 'wpml-floating-language-switcher' );

		echo "<div {$this->render_attributes( '_root' )}>";

		do_action( 'wpml_add_language_selector' );

		echo '</div>';
	}
}
