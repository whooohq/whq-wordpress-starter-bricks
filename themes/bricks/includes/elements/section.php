<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Container element not loaded
if ( ! class_exists( 'Element_Container' ) ) {
	return;
}

class Element_Section extends Element_Container {
	public $category = 'layout';
	public $name     = 'section';
	public $icon     = 'ti-layout-accordion-separated';
	public $tag      = 'section';
	public $nestable = true;

	public function get_label() {
		return esc_html__( 'Section', 'bricks' );
	}

	public function get_keywords() {
		return [];
	}

	/**
	 * Get child elements
	 *
	 * @return array Array of child elements.
	 *
	 * @since 1.5
	 */
	public function get_nestable_children() {
		return [
			[
				'name' => 'container',
			],
		];
	}
}
