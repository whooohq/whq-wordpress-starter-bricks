<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Container element not loaded
if ( ! class_exists( 'Element_Container' ) ) {
	return;
}

class Element_Div extends Element_Container {
	public $category = 'layout';
	public $name     = 'div';
	public $icon     = 'ti-layout-width-default-alt';
	public $nestable = true;

	public function get_label() {
		return 'Div';
	}
}
