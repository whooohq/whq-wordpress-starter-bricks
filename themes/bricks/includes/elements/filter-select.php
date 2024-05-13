<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Filter_Select extends Filter_Element {
	public $name        = 'filter-select';
	public $icon        = 'ti-widget-alt';
	public $filter_type = 'select';

	public function get_label() {
		return esc_html__( 'Filter', 'bricks' ) . ' - ' . esc_html__( 'Select', 'bricks' );
	}

	public function set_controls() {
		$filter_controls = $this->get_filter_controls();

		if ( ! empty( $filter_controls ) ) {
			$this->controls = array_merge( $this->controls, $filter_controls );
		}

		// INPUT
		$this->controls['inputSep'] = [
			'label' => esc_html__( 'Input', 'bricks' ),
			'type'  => 'separator',
		];

		$this->controls['placeholder'] = [
			'label'  => esc_html__( 'Placeholder', 'bricks' ),
			'type'   => 'text',
			'inline' => true,
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

	/**
	 * Populate options from user input and set to $this->populated_options
	 * Not in Beta
	 */
	private function populate_user_options() {
		$options = ! empty( $this->settings['options'] ) ? Helpers::parse_textarea_options( $this->settings['options'] ) : false;

		if ( ! empty( $options ) ) {
			foreach ( $options as $option ) {
				$this->populated_options[] = [
					'value' => $option,
					'text'  => $option,
					'class' => '',
				];
			}
		}
	}

	/**
	 * Setup filter
	 *
	 * If is a sort input
	 * - Set sorting options
	 * If is a filter input
	 * - Prepare sources
	 * - Set data_source
	 * - Set final options
	 *
	 * - Set data-brx-filter attribute
	 */
	private function set_as_filter() {
		$settings = $this->settings;

		// Check required filter settings
		if ( empty( $settings['filterQueryId'] ) ) {
			return;
		}

		// Filter or Sort
		$filter_action = $settings['filterAction'] ?? 'filter';

		if ( $filter_action === 'filter' ) {
			// A filter input must have filterSource
			if ( empty( $settings['filterSource'] ) ) {
				return;
			}

			$this->prepare_sources();

			// User wish to use what options as filter options
			switch ( $settings['filterSource'] ) {
				case 'taxonomy':
					$this->set_data_source_from_taxonomy();
					break;
				case 'wpField':
					$this->set_data_source_from_wp_field();
					break;
				case 'customField':
					$this->set_data_source_from_custom_field();
					break;
			}

			$this->set_options();

		} else {
			// User wish to use what options as sort options
			$this->setup_sort_options();
		}

		// Insert filter settings as data-brx-filter attribute
		$filter_settings                 = $this->get_common_filter_settings();
		$filter_settings['filterSource'] = $settings['filterSource'] ?? false;

		$this->set_attribute( '_root', 'data-brx-filter', wp_json_encode( $filter_settings ) );
	}

	private function set_options_placeholder() {
		$user_placeholder = $this->settings['placeholder'] ?? '';

		// Add placeholder option
		if ( ! empty( $user_placeholder ) ) {
			// Find placeholder option from populated options
			$placeholder_option = array_filter(
				$this->populated_options,
				function( $option ) {
					return isset( $option['is_placeholder'] ) && $option['is_placeholder'] === true;
				}
			);

			// Placeholder option not found: Add it to the beginning of the options
			if ( empty( $placeholder_option ) ) {
				$this->populated_options = array_merge(
					[
						[
							'value'          => '',
							'text'           => $user_placeholder,
							'class'          => 'placeholder',
							'is_placeholder' => true,
						]
					],
					$this->populated_options
				);
			} else {
				// Placeholder option found: Update text
				$this->populated_options = array_map(
					function( $option ) use ( $user_placeholder ) {
						if ( isset( $option['is_placeholder'] ) && $option['is_placeholder'] === true ) {
							$option['text'] = $user_placeholder;
						}

						return $option;
					},
					$this->populated_options
				);
			}
		}
	}

	public function render() {
		$settings      = $this->settings;
		$current_value = isset( $settings['value'] ) ? $settings['value'] : '';

		// In filter AJAX call: filterValue is the current filter value
		if ( isset( $settings['filterValue'] ) ) {
			$current_value = $settings['filterValue'];
		}

		$this->input_name = $settings['name'] ?? "form-field-{$this->id}";

		if ( $this->is_filter_input() ) {
			$this->set_as_filter();
		} else {
			// Not in Beta
			// $this->populate_user_options();
		}

		// Return: No filter source selected
		$filter_action = $this->settings['filterAction'] ?? 'filter';
		if ( $filter_action === 'filter' && empty( $settings['filterSource'] ) ) {
			return $this->render_element_placeholder(
				[
					'title' => esc_html__( 'No filter source selected.', 'bricks' ),
				]
			);
		}

		$this->set_attribute( '_root', 'name', $this->input_name );

		$this->set_options_placeholder();

		echo "<select {$this->render_attributes('_root')}>";

		// Generate options HTML
		foreach ( $this->populated_options as $option ) {
			$option_value    = esc_attr( strip_tags( $option['value'] ) );
			$option_text     = strip_tags( $option['text'] );
			$option_class    = ! empty( $option['class'] ) ? esc_attr( trim( $option['class'] ) ) : '';
			$option_selected = selected( $current_value, $option_value, false );
			$option_disabled = isset( $option['disabled'] ) ? 'disabled' : '';

			echo '<option value="' . $option_value . '" ' .
			( ! empty( $option_class ) ? "class='{$option_class}'" : '' ) . ' ' .
			$option_selected . ' ' .
			$option_disabled . '>' .
			$option_text . '</option>';

		}

		echo '</select>';
	}
}
