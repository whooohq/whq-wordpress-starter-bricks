<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

abstract class Style_Base {
	public $id;
	public $label;
	public $settings;

	public function __construct() {
		$this->id       = $this->get_id();
		$this->label    = $this->get_label();
		$this->settings = $this->get_settings();
	}

	public function get_id() {}

	public function get_label() {}

	public function get_settings() {}

	public function get_style_data() {
		return [
			'id'       => $this->id,
			'label'    => $this->label,
			'settings' => $this->settings,
		];
	}
}
