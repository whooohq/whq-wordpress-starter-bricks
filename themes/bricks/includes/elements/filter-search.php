<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Filter_Search extends Filter_Element {
	public $name        = 'filter-search';
	public $icon        = 'ti-search';
	public $filter_type = 'search';

	public function get_label() {
		return esc_html__( 'Filter', 'bricks' ) . ' - ' . esc_html__( 'Search', 'bricks' );
	}

	public function set_controls() {
		// SORT / FILTER
		$filter_controls = $this->get_filter_controls();

		if ( ! empty( $filter_controls ) ) {
			$filter_controls['filterInputDebounce'] = [
				'type'        => 'number',
				'label'       => esc_html__( 'Debounce', 'bricks' ) . ' (ms)',
				'placeholder' => 500,
				'required'    => [ 'filterApplyOn', '!=', 'click' ],
			];

			$filter_controls['filterMinChars'] = [
				'type'        => 'number',
				'label'       => esc_html__( 'Min. characters', 'bricks' ),
				'placeholder' => 3,
			];

			$this->controls = array_merge( $this->controls, $filter_controls );
		}

		// INPUT
		$this->controls['inputSep'] = [
			'label' => esc_html__( 'Input', 'bricks' ),
			'type'  => 'separator',
		];

		$this->controls['placeholder'] = [
			'label'       => esc_html__( 'Placeholder', 'bricks' ),
			'type'        => 'text',
			'inline'      => true,
			'placeholder' => esc_html__( 'Search', 'bricks' ),
		];

		$this->controls['placeholderTypography'] = [
			'label' => esc_html__( 'Placeholder typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '&::placeholder',
				],
			],
		];
	}

	private function set_as_filter() {
		$settings = $this->settings;

		// In filter AJAX call: filterValue is the current filter value
		$current_value = $settings['filterValue'] ?? '';
		$this->set_attribute( '_root', 'value', $current_value );

		// Insert filter settings as data-brx-filter attribute
		$filter_settings = $this->get_common_filter_settings();

		// min chars
		$filter_settings['filterMinChars'] = $settings['filterMinChars'] ?? 3;

		$this->set_attribute( '_root', 'data-brx-filter', wp_json_encode( $filter_settings ) );

		$this->input_name = 's'; // For search query arg
	}

	public function render() {
		$this->input_name = $this->settings['name'] ?? "form-field-{$this->id}";
		$placeholder      = $this->settings['placeholder'] ?? esc_html__( 'Search', 'bricks' );

		if ( $this->is_filter_input() ) {
			$this->set_as_filter();
		}

		$this->set_attribute( '_root', 'name', $this->input_name );
		$this->set_attribute( '_root', 'placeholder', $placeholder );
		$this->set_attribute( '_root', 'aria-label', $placeholder );
		$this->set_attribute( '_root', 'type', 'search' );
		$this->set_attribute( '_root', 'autocomplete', 'off' );
		$this->set_attribute( '_root', 'spellcheck', 'false' );

		echo "<input {$this->render_attributes('_root')}></input>";
	}
}
