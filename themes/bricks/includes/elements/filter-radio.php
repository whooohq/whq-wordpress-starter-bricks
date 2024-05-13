<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Filter_Radio extends Filter_Element {
	public $name        = 'filter-radio';
	public $icon        = 'ti-control-record';
	public $filter_type = 'radio';

	public function get_label() {
		return esc_html__( 'Filter', 'bricks' ) . ' - ' . esc_html__( 'Radio', 'bricks' );
	}

	public function set_controls() {
		// SORT / FILTER
		$filter_controls = $this->get_filter_controls();

		if ( ! empty( $filter_controls ) ) {
			$this->controls = array_merge( $this->controls, $filter_controls );
		}

		// MODE: Radio / Button
		$this->controls['modeSep'] = [
			'label' => esc_html__( 'Mode', 'bricks' ),
			'type'  => 'separator',
		];

		$this->controls['displayMode'] = [
			'label'       => esc_html__( 'Mode', 'bricks' ),
			'type'        => 'select',
			'inline'      => true,
			'options'     => [
				'radio'  => esc_html__( 'Radio', 'bricks' ),
				'button' => esc_html__( 'Button', 'bricks' ),
			],
			'placeholder' => esc_html__( 'Radio', 'bricks' ),
		];

		// BUTTON
		$this->controls['buttonSep'] = [
			'label'    => esc_html__( 'Button', 'bricks' ),
			'type'     => 'separator',
			'required' => [ 'displayMode', '=', 'button' ],
		];

		$this->controls['buttonSize'] = [
			'label'       => esc_html__( 'Size', 'bricks' ),
			'type'        => 'select',
			'options'     => $this->control_options['buttonSizes'],
			'inline'      => true,
			'placeholder' => esc_html__( 'Default', 'bricks' ),
			'required'    => [ 'displayMode', '=', 'button' ],
		];

		$this->controls['buttonStyle'] = [
			'label'       => esc_html__( 'Style', 'bricks' ),
			'type'        => 'select',
			'options'     => $this->control_options['styles'],
			'inline'      => true,
			'placeholder' => esc_html__( 'None', 'bricks' ),
			'required'    => [ 'displayMode', '=', 'button' ],
		];

		$this->controls['buttonCircle'] = [
			'label'    => esc_html__( 'Circle', 'bricks' ),
			'type'     => 'checkbox',
			'required' => [ 'displayMode', '=', 'button' ],
		];

		$this->controls['buttonOutline'] = [
			'label'    => esc_html__( 'Outline', 'bricks' ),
			'type'     => 'checkbox',
			'required' => [
				[ 'displayMode', '=', 'button' ],
				[ 'buttonStyle', '!=', '' ],
			],
		];

		// Style none: Show background color, border, typography controls
		$this->controls['buttonBackgroundColor'] = [
			'label'    => esc_html__( 'Background color', 'bricks' ),
			'type'     => 'color',
			'css'      => [
				[
					'property' => 'background-color',
					'selector' => '.bricks-button',
				],
			],
			'required' => [ 'displayMode', '=', 'button' ],
		];

		$this->controls['buttonBorder'] = [
			'label'    => esc_html__( 'Border', 'bricks' ),
			'type'     => 'border',
			'css'      => [
				[
					'property' => 'border-color',
					'selector' => '.bricks-button',
				],
			],
			'required' => [ 'displayMode', '=', 'button' ],
		];

		$this->controls['buttonTypography'] = [
			'label'    => esc_html__( 'Typography', 'bricks' ),
			'type'     => 'typography',
			'css'      => [
				[
					'property' => 'font',
					'selector' => '.bricks-button',
				],
			],
			'required' => [ 'displayMode', '=', 'button' ],
		];

		// Active button
		$this->controls['buttonActiveSep'] = [
			'label'    => esc_html__( 'Button', 'bricks' ) . ' (' . esc_html__( 'Active', 'bricks' ) . ')',
			'type'     => 'separator',
			'required' => [ 'displayMode', '=', 'button' ],
		];

		$this->controls['buttonActiveBackgroundColor'] = [
			'label'    => esc_html__( 'Background color', 'bricks' ),
			'type'     => 'color',
			'css'      => [
				[
					'property' => 'background-color',
					'selector' => '.bricks-button.active',
				],
			],
			'required' => [ 'displayMode', '=', 'button' ],
		];

		$this->controls['buttonActiveBorder'] = [
			'label'    => esc_html__( 'Border', 'bricks' ),
			'type'     => 'border',
			'css'      => [
				[
					'property' => 'border-color',
					'selector' => '.bricks-button.active',
				],
			],
			'required' => [ 'displayMode', '=', 'button' ],
		];

		$this->controls['buttonActiveTypography'] = [
			'label'    => esc_html__( 'Typography', 'bricks' ),
			'type'     => 'typography',
			'css'      => [
				[
					'property' => 'font',
					'selector' => '.bricks-button.active',
				],
			],
			'required' => [ 'displayMode', '=', 'button' ],
		];
	}

	/**
	 * Populate options from user input and set to $this->populated_options
	 * Not in Beta
	 */
	private function populate_user_options() {
		$settings = $this->settings;

		if ( ! isset( $settings['options'] ) ) {
			return;
		}

		$options      = [];
		$user_options = Helpers::parse_textarea_options( $settings['options'] );

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
		}

		// Sort
		else {
			$this->setup_sort_options();
		}

		// Insert filter settings as data-brx-filter attribute
		$filter_settings                 = $this->get_common_filter_settings();
		$filter_settings['filterSource'] = $settings['filterSource'] ?? false;

		$this->set_attribute( '_root', 'data-brx-filter', wp_json_encode( $filter_settings ) );
	}

	public function render() {
		$settings      = $this->settings;
		$current_value = isset( $settings['value'] ) ? $settings['value'] : '';

		// Return: No filter source selected
		$filter_action = $this->settings['filterAction'] ?? 'filter';
		if ( $filter_action === 'filter' && empty( $settings['filterSource'] ) ) {
			return $this->render_element_placeholder(
				[
					'title' => esc_html__( 'No filter source selected.', 'bricks' ),
				]
			);
		}

		// In filter AJAX call, filterValue is the current filter value
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

		$display_mode = $settings['displayMode'] ?? 'radio';

		if ( $display_mode === 'button' ) {
			$this->set_attribute( '_root', 'data-mode', 'button' );
		}

		echo "<ul {$this->render_attributes('_root')}>";

		foreach ( $this->populated_options as $option ) {
			$option_value    = esc_attr( strip_tags( $option['value'] ) );
			$option_text     = strip_tags( $option['text'] );
			$option_class    = esc_attr( $option['class'] );
			$option_checked  = $option_value === $current_value ? 'checked' : '';
			$option_disabled = isset( $option['disabled'] ) ? 'disabled' : '';
			$span_class      = '';

			// Mode: Button
			if ( $display_mode === 'button' ) {
				$span_class = 'bricks-button';

				if ( isset( $settings['buttonSize'] ) ) {
					$span_class .= ' ' . $settings['buttonSize'];
				}

				if ( isset( $settings['buttonStyle'] ) ) {
					if ( isset( $settings['buttonOutline'] ) ) {
						$span_class .= ' outline bricks-color-' . $settings['buttonStyle'];
					} else {
						$span_class .= ' bricks-background-' . $settings['buttonStyle'];
					}
				}

				if ( isset( $settings['buttonCircle'] ) ) {
					$span_class .= ' circle';
				}

				if ( $option_checked === 'checked' ) {
					$span_class .= ' active';
				}
			}

			echo '<li>';
			echo "<label class=\"$option_class\">";

			echo '<input type="radio" name="' . $this->input_name . '" value="' . $option_value . '" ' .
			$option_checked . ' ' .
			$option_disabled . '>';

			if ( $span_class ) {
				echo "<span class=\"$span_class\">";
			} else {
				echo '<span>';
			}

			echo $option_text;

			echo '</span>';

			echo '</label>';
			echo '</li>';
		}

		echo '</ul>';
	}
}
