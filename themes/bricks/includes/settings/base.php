<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

abstract class Settings_Base {
	public $setting_type; // page, template
	public $controls;
	public $control_groups;

	public function __construct( $type = '' ) {
		$this->setting_type   = $type;
		$this->controls       = [];
		$this->control_groups = [];

		$this->set_control_groups();
		$this->set_controls();
	}

	public function set_control_groups() {}

	public function set_controls() {}

	public function get_controls() {
		return $this->controls;
	}

	public function get_control_groups() {
		return $this->control_groups;
	}

	/**
	 * Get all controls data (controls and control_groups)
	 *
	 * @since 1.0
	 */
	public function get_controls_data() {
		$data = [
			'controls'      => $this->controls,
			'controlGroups' => $this->control_groups,
		];

		// https://academy.bricksbuilder.io/article/filter-builder-settings-type-controls_data/
		return apply_filters( "builder/settings/{$this->setting_type}/controls_data", $data );
	}
}
