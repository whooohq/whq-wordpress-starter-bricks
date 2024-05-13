<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Container element not loaded
if ( ! class_exists( 'Element_Container' ) ) {
	return;
}

class Element_Block extends Element_Container {
	public $category = 'layout';
	public $name     = 'block';
	public $icon     = 'ti-layout-width-full';
	public $nestable = true;

	public function get_label() {
		return esc_html__( 'Block', 'bricks' );
	}
}
