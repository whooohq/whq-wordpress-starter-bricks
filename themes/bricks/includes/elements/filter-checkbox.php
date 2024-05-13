<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Filter_Checkbox extends Filter_Element {
	public $name        = 'filter-checkbox';
	public $icon        = 'ti-check-box';
	public $filter_type = 'checkbox';

	public function get_label() {
		return esc_html__( 'Filter', 'bricks' ) . ' - ' . esc_html__( 'Checkbox', 'bricks' );
	}

	public function set_controls() {
		$filter_controls = $this->get_filter_controls();

		if ( ! empty( $filter_controls ) ) {
			$this->controls = array_merge( $this->controls, $filter_controls );
		}
	}

	/**
	 * Populate options from user input and set to $this->populated_options
	 *
	 * Not in Beta
	 */
	private function populate_user_options() {
		$options      = [];
		$user_options = ! empty( $this->settings['options'] ) ? Helpers::parse_textarea_options( $this->settings['options'] ) : false;

		if ( ! empty( $user_options ) ) {
			foreach ( $user_options as $option ) {
				$options[] = [
					'value' => $option,
					'text'  => $option,
					'class' => '',
				];
			}
		}

		$this->populated_options = $options;
	}

	public function is_filter_input() {
		return ! empty( $this->settings['filterQueryId'] ) && ! empty( $this->settings['filterSource'] );
	}

	/**
	 * Setup filter
	 */
	private function set_as_filter() {
		$settings = $this->settings;

		// Return: Required filter settings not set
		if ( empty( $settings['filterQueryId'] ) || empty( $settings['filterSource'] ) ) {
			return;
		}

		$this->prepare_sources();

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

		// Insert filter settings as data-brx-filter attribute
		$filter_settings                 = $this->get_common_filter_settings();
		$filter_settings['filterSource'] = $settings['filterSource'];

		$this->set_attribute( '_root', 'data-brx-filter', wp_json_encode( $filter_settings ) );
	}

	public function render() {
		$settings         = $this->settings;
		$this->input_name = $settings['name'] ?? "form-field-{$this->id}";

		if ( $this->is_filter_input() ) {
			$this->set_as_filter();
		} else {
			// Not in Beta
			// $this->populate_user_options();
		}

		// Set current value
		$current_value = isset( $settings['value'] ) ? $settings['value'] : [];

		// In filter AJAX call, filterValue is the current filter value
		if ( isset( $settings['filterValue'] ) ) {
			$current_value = $settings['filterValue'];
		}

		// Experiment to get current value from page query
		$page_filters = Query_Filters::$page_filters;

		// Disable pointer events for this filter if the taxonomy is same as the current page filter
		if ( ! empty( $page_filters ) && isset( $page_filters[ $this->input_name ] ) ) {
			$this->set_attribute( '_root', 'class', [ 'page-filtered' ] );
		}

		echo "<ul {$this->render_attributes('_root')}>";

		foreach ( $this->populated_options as $option ) {
			if ( empty( $option['text'] ) ) {
				continue;
			}

			$option_text     = strip_tags( trim( $option['text'] ) );
			$option_value    = esc_attr( strip_tags( $option['value'] ) );
			$option_class    = esc_attr( $option['class'] );
			$option_selected = in_array( $option_value, $current_value ) ? 'checked' : '';
			$option_disabled = isset( $option['disabled'] ) ? 'disabled' : '';

			// Maybe the value match with page_filters
			if ( ! empty( $page_filters ) && isset( $page_filters[ $this->input_name ] ) ) {
				if ( $page_filters[ $this->input_name ] === $option_value ) {
					$option_selected = 'checked';
				}
			}

			echo '<li>';
			echo "<label class=\"$option_class\">";

			echo '<input type="checkbox" name="' . $this->input_name . '" value="' . $option_value . '" ' .
			$option_selected . ' ' .
			$option_disabled . '>';

			echo "<span>$option_text</span>";

			echo '</label>';
			echo '</li>';
		}

		echo '</ul>';
	}
}
