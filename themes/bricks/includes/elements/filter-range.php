<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Filter_Range extends Filter_Element {
	public $name         = 'filter-range';
	public $icon         = 'ti-arrows-horizontal';
	public $filter_type  = 'range';
	private $min_value   = null;
	private $max_value   = null;
	private $current_min = null;
	private $current_max = null;

	public function get_label() {
		return esc_html__( 'Filter', 'bricks' ) . ' - ' . esc_html__( 'Range', 'bricks' );
	}
	public function set_control_groups() {
		$this->control_groups['label'] = [
			'title' => esc_html__( 'Label', 'bricks' ),
		];

		$this->control_groups['input'] = [
			'title'    => esc_html__( 'Input', 'bricks' ),
			'required' => [ 'displayMode', '=', 'input' ],
		];
	}

	public function set_controls() {
		// SORT / FILTER
		$filter_controls = $this->get_filter_controls();

		if ( ! empty( $filter_controls ) ) {
			// Support customField only
			unset( $filter_controls['filterSource']['options']['taxonomy'] );
			unset( $filter_controls['filterSource']['options']['wpField'] );
			unset( $filter_controls['filterTaxonomy'] );
			unset( $filter_controls['filterHierarchical'] );
			unset( $filter_controls['filterTaxonomyHideEmpty'] );
			unset( $filter_controls['filterHideCount'] );
			unset( $filter_controls['filterHideEmpty'] );
			unset( $filter_controls['labelMapping'] );
			unset( $filter_controls['customLabelMapping'] );
			unset( $filter_controls['fieldCompareOperator'] );

			$this->controls = array_merge( $this->controls, $filter_controls );
		}

		// MODE
		$this->controls['modeSep'] = [
			'type'  => 'separator',
			'label' => esc_html__( 'Mode', 'bricks' ),
			'desc'  => esc_html__( 'Min/max values are set automatically based on query loop results.', 'bricks' ),
		];

		$this->controls['displayMode'] = [
			'label'       => esc_html__( 'Mode', 'bricks' ),
			'type'        => 'select',
			'inline'      => true,
			'options'     => [
				'range' => esc_html__( 'Slider', 'bricks' ),
				'input' => esc_html__( 'Input', 'bricks' ),
			],
			'placeholder' => esc_html__( 'Slider', 'bricks' ),
		];

		$this->controls['step'] = [
			'label'    => esc_html__( 'Step', 'bricks' ),
			'type'     => 'number',
			'required' => [ 'displayMode', '=', 'input' ], // NOTE: Why limit step to input mode only?
		];

		// LABEL
		$this->controls['labelMin'] = [
			'group'  => 'label',
			'label'  => esc_html__( 'Min', 'bricks' ),
			'type'   => 'text',
			'inline' => true,
		];

		$this->controls['labelMax'] = [
			'group'  => 'label',
			'label'  => esc_html__( 'Max', 'bricks' ),
			'type'   => 'text',
			'inline' => true,
		];

		$this->controls['labelDirection'] = [
			'group'   => 'label',
			'label'   => esc_html__( 'Direction', 'bricks' ),
			'inline'  => true,
			'tooltip' => [
				'content'  => 'flex-direction',
				'position' => 'top-left',
			],
			'type'    => 'direction',
			'css'     => [
				[
					'property' => 'flex-direction',
					'selector' => '.min-max-wrap > *, .value-wrap > *',
				],
			],
		];

		$this->controls['labelGap'] = [
			'group' => 'label',
			'label' => esc_html__( 'Gap', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'css'   => [
				[
					'property' => 'gap',
					'selector' => '.value-wrap > span',
				],
				[
					'property' => 'gap',
					'selector' => '.min-max-wrap > div',
				],
			],
		];

		$this->controls['labelTypography'] = [
			'group' => 'label',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.label',
				],
			],
		];

		// Auto-set via JS: toLocaleString()
		$this->controls['labelThousandSeparator'] = [
			'group'    => 'label',
			'label'    => esc_html__( 'Thousand separator', 'bricks' ),
			'type'     => 'checkbox',
			'required' => [ 'displayMode','!=','input' ],
		];

		$this->controls['labelSeparatorText'] = [
			'group'       => 'label',
			'label'       => esc_html__( 'Separator', 'bricks' ),
			'type'        => 'text',
			'inline'      => true,
			'placeholder' => ',',
			'required'    => [
				[ 'displayMode', '!=', 'input' ],
				[ 'labelThousandSeparator', '=', true ],
			],
		];

		// INPUT
		$this->controls['placeholderMin'] = [
			'group'    => 'input',
			'label'    => esc_html__( 'Placeholder', 'bricks' ) . ' (' . esc_html__( 'Min', 'bricks' ) . ')',
			'type'     => 'text',
			'inline'   => true,
			'required' => [ 'displayMode', '=', 'input' ],
		];

		$this->controls['placeholderMax'] = [
			'group'    => 'input',
			'label'    => esc_html__( 'Placeholder', 'bricks' ) . ' (' . esc_html__( 'Max', 'bricks' ) . ')',
			'type'     => 'text',
			'inline'   => true,
			'required' => [ 'displayMode', '=', 'input' ],
		];

		$this->controls['inputBackgroundColor'] = [
			'group'    => 'input',
			'label'    => esc_html__( 'Background color', 'bricks' ),
			'type'     => 'color',
			'css'      => [
				[
					'property' => 'background-color',
					'selector' => '.min-max-wrap input',
				],
			],
			'required' => [ 'displayMode', '=', 'input' ],
		];

		$this->controls['inputBorder'] = [
			'group'    => 'input',
			'label'    => esc_html__( 'Border', 'bricks' ),
			'type'     => 'border',
			'css'      => [
				[
					'property' => 'border',
					'selector' => '.min-max-wrap input',
				],
			],
			'required' => [ 'displayMode', '=', 'input' ],
		];

		$this->controls['inputTypography'] = [
			'group'    => 'input',
			'label'    => esc_html__( 'Typography', 'bricks' ),
			'type'     => 'typography',
			'css'      => [
				[
					'property' => 'font',
					'selector' => '.min-max-wrap input',
				],
			],
			'required' => [ 'displayMode', '=', 'input' ],
		];

		$this->controls['inputWidth'] = [
			'group'    => 'input',
			'label'    => esc_html__( 'Width', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'width',
					'selector' => '.min-max-wrap input',
				],
			],
			'required' => [ 'displayMode', '=', 'input' ],
		];
	}

	private function set_as_filter() {
		$settings = $this->settings;

		// Check required filter settings
		if ( empty( $settings['filterQueryId'] ) || empty( $settings['filterSource'] ) ) {
			return;
		}

		$this->prepare_sources();

		$field_type = $settings['sourceFieldType'] ?? 'post';
		$mode       = 'range'; // not in use
		$operator   = 'is'; // not in use
		$field_info = false;

		// Build $field_info to be used by the JS filter in frontend
		if ( $settings['filterSource'] === 'customField' ) {
			$meta_key = $settings['customFieldKey'] ?? false;

			if ( $meta_key ) {
				$field_info = [
					'field_type' => $field_type,
					'field_key'  => $meta_key,
					'mode'       => $mode,
					'operator'   => $operator,
				];
			}
		}

		if ( empty( $field_info ) ) {
			return;
		}

		/**
		 * Get min/max date from $this->choices_source
		 */
		if ( ! empty( $this->choices_source ) ) {
			foreach ( $this->choices_source as $source ) {
				$choice_value = $source['filter_value'] ?? false;

				if ( ! $choice_value ) {
					continue;
				}

				// Force to convert to float
				$choice_value = (float) $choice_value;

				// Set min/max value, set as Integer, we only support Integer
				if ( $this->min_value === null || $choice_value < $this->min_value ) {
					// If the value is 1.9, it will be converted to 1
					$choice_value = floor( $choice_value );
					// Convert to integer - Set min value
					$this->min_value = (int) $choice_value;
				}

				if ( $this->max_value === null || $choice_value > $this->max_value ) {
					// If the value is 1.9, it will be converted to 2
					$choice_value = ceil( $choice_value );
					// Convert to integer - Set max value
					$this->max_value = (int) $choice_value;
				}
			}
		}

		// Insert filter settings as data-brx-filter attribute
		$filter_settings                 = $this->get_common_filter_settings();
		$filter_settings['filterSource'] = $settings['filterSource'];
		$filter_settings['fieldInfo']    = $field_info;

		// min, max, step values
		$filter_settings['min']  = $this->min_value ?? 0;
		$filter_settings['max']  = $this->max_value ?? 100;
		$filter_settings['step'] = $settings['step'] ?? 1;

		// thousand separator
		$display_mode = $settings['displayMode'] ?? 'range';
		if ( $display_mode === 'range' ) {
			$filter_settings['thousands'] = ! empty( $settings['labelThousandSeparator'] ) ? $settings['labelThousandSeparator'] : '';
			$filter_settings['separator'] = ! empty( $settings['labelSeparatorText'] ) ? $this->render_dynamic_data( $settings['labelSeparatorText'] ) : '';
		}

		$this->set_attribute( '_root', 'data-brx-filter', wp_json_encode( $filter_settings ) );
	}

	public function render() {
		$settings = $this->settings;

		if ( $this->is_filter_input() ) {
			$this->set_as_filter();
		}

		// Return: No filter source selected
		if ( empty( $settings['filterSource'] ) ) {
			return $this->render_element_placeholder(
				[
					'title' => esc_html__( 'No filter source selected.', 'bricks' ),
				]
			);
		}

		$this->current_min = $this->min_value ?? 0;
		$this->current_max = $this->max_value ?? 100;

		// In filter AJAX call, filterValue is the current filter value
		if ( isset( $settings['filterValue'] ) ) {
			// The expected value is an array, first element is min, second element is max
			$current_value = $settings['filterValue'];

			if ( is_array( $current_value ) ) {

				if ( isset( $current_value[0] ) && ! empty( $current_value[0] ) ) {
					$this->current_min = $current_value[0];
				}

				if ( isset( $current_value[1] ) && ! empty( $current_value[1] ) ) {
					$this->current_max = $current_value[1];
				}
			}
		}

		echo "<div {$this->render_attributes('_root')}>";

		// Range slider UI
		$this->maybe_render_range_slider();

		// Input fields UI - must be rendered
		$this->render_input_fields();

		echo '</div>'; // end root
	}

	private function maybe_render_range_slider() {
		$settings     = $this->settings;
		$display_mode = $settings['displayMode'] ?? 'range';
		$label_min    = ! empty( $settings['labelMin'] ) ? $this->render_dynamic_data( $settings['labelMin'] ) : '';
		$label_max    = ! empty( $settings['labelMax'] ) ? $this->render_dynamic_data( $settings['labelMax'] ) : '';
		$thousands    = ! empty( $settings['labelThousandSeparator'] ) ? $settings['labelThousandSeparator'] : '';
		$separator    = ! empty( $settings['labelSeparatorText'] ) ? $this->render_dynamic_data( $settings['labelSeparatorText'] ) : ',';

		if ( $display_mode !== 'range' ) {
			return;
		}

		echo '<div class="double-slider-wrap">';

		$this->set_attribute( 'min-range', 'type', 'range' );
		$this->set_attribute( 'min-range', 'class', 'min' );
		$this->set_attribute( 'min-range', 'name', "form-field-min-{$this->id}" );
		$this->set_attribute( 'min-range', 'min', $this->min_value ?? 0 );
		$this->set_attribute( 'min-range', 'max', $this->max_value ?? 100 );
		$this->set_attribute( 'min-range', 'value', $this->current_min );

		echo "<input {$this->render_attributes( 'min-range' )}></input>";

		$this->set_attribute( 'max-range', 'type', 'range' );
		$this->set_attribute( 'max-range', 'class', 'max' );
		$this->set_attribute( 'max-range', 'name', "form-field-max-{$this->id}" );
		$this->set_attribute( 'max-range', 'min', $this->min_value ?? 0 );
		$this->set_attribute( 'max-range', 'max', $this->max_value ?? 100 );
		$this->set_attribute( 'max-range', 'value', $this->current_max );

		echo "<input {$this->render_attributes( 'max-range' )}></input>";

		// Hardcode HTML
		echo '<div class="value-wrap">';

		$min_value = $this->current_min;
		$max_value = $this->current_max;

		if ( ! empty( $thousands ) ) {
			$min_value = number_format( $min_value, 0, '.', $separator );
			$max_value = number_format( $max_value, 0, '.', $separator );
		}

		$value_wrapper_html  = '<span class="lower">';
		$value_wrapper_html .= ! empty( $label_min ) ? '<span class="label">' . $label_min . '</span>' : '';
		$value_wrapper_html .= '<span class="value">' . $min_value . '</span>';
		$value_wrapper_html .= '</span>';

		$value_wrapper_html .= '<span class="upper">';
		$value_wrapper_html .= ! empty( $label_max ) ? '<span class="label">' . $label_max . '</span>' : '';
		$value_wrapper_html .= '<span class="value">' . $max_value . '</span>';
		$value_wrapper_html .= '</span>';

		echo $value_wrapper_html;

		echo '</div>';

		echo '</div>';
	}

	private function render_input_fields() {
		$settings        = $this->settings;
		$display_mode    = $settings['displayMode'] ?? 'range';
		$label_min       = ! empty( $settings['labelMin'] ) ? $this->render_dynamic_data( $settings['labelMin'] ) : '';
		$label_max       = ! empty( $settings['labelMax'] ) ? $this->render_dynamic_data( $settings['labelMax'] ) : '';
		$placeholder_min = ! empty( $settings['placeholderMin'] ) ? $this->render_dynamic_data( $settings['placeholderMin'] ) : esc_html__( 'Min', 'bricks' );
		$placeholder_max = ! empty( $settings['placeholderMax'] ) ? $this->render_dynamic_data( $settings['placeholderMax'] ) : esc_html__( 'Max', 'bricks' );

		$this->set_attribute( 'min-max-wrap', 'class', 'min-max-wrap' );

		if ( $display_mode === 'range' ) {
			// Hide input fields if range slider is used
			$this->set_attribute( 'min-max-wrap', 'style', 'display: none;' );
		}

		echo "<div {$this->render_attributes( 'min-max-wrap' )}>";

		// Min. value
		echo '<div class="min-wrap">';

		if ( ! empty( $label_min ) ) {
			echo '<span class="label">' . $label_min . '</span>';
		}

		$this->set_attribute( 'min-input', 'type', 'number' );
		$this->set_attribute( 'min-input', 'class', 'min' );
		$this->set_attribute( 'min-input', 'name', "form-field-min-{$this->id}" );
		$this->set_attribute( 'min-input', 'min', $this->min_value ?? 0 );
		$this->set_attribute( 'min-input', 'max', $this->max_value ?? 100 );
		$this->set_attribute( 'min-input', 'step', $settings['step'] ?? 1 );
		$this->set_attribute( 'min-input', 'placeholder', $placeholder_min );
		$this->set_attribute( 'min-input', 'value', $this->current_min );
		echo "<input {$this->render_attributes( 'min-input' )}></input>";

		echo '</div>';

		// Max. value
		echo '<div class="max-wrap">';

		if ( ! empty( $label_max ) ) {
			echo '<span class="label">' . $label_max . '</span>';
		}

		$this->set_attribute( 'max-input', 'type', 'number' );
		$this->set_attribute( 'max-input', 'class', 'max' );
		$this->set_attribute( 'max-input', 'name', "form-field-max-{$this->id}" );
		$this->set_attribute( 'max-input', 'min', $this->min_value ?? 0 );
		$this->set_attribute( 'max-input', 'max', $this->max_value ?? 100 );
		$this->set_attribute( 'max-input', 'step', $settings['step'] ?? 1 );
		$this->set_attribute( 'max-input', 'placeholder', $placeholder_max );
		$this->set_attribute( 'max-input', 'value', $this->current_max );
		echo "<input {$this->render_attributes( 'max-input' )}></input>";

		echo '</div>';

		echo '</div>';
	}
}
