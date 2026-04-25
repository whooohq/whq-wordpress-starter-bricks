<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Filter_DatePicker extends Filter_Element {
	public $name        = 'filter-datepicker';
	public $icon        = 'ti-calendar';
	public $filter_type = 'datepicker';
	public $min_date    = null; // timestamp
	public $max_date    = null; // timestamp

	public function get_label() {
		return esc_html__( 'Filter', 'bricks' ) . ' - ' . esc_html__( 'Datepicker', 'bricks' );
	}

	public function enqueue_scripts() {
		wp_enqueue_script( 'bricks-flatpickr' );
		wp_enqueue_style( 'bricks-flatpickr' );

		// Load datepicker localisation
		$l10n = $this->settings['l10n'] ?? '';
		if ( $l10n ) {
			wp_enqueue_script( 'bricks-flatpickr-l10n', "https://npmcdn.com/flatpickr@4.6.13/dist/l10n/$l10n.js", [ 'bricks-flatpickr' ], '4.6.13' );
		}
	}

	public function set_control_groups() {
		$this->control_groups['input'] = [
			'title' => esc_html__( 'Input', 'bricks' ),
		];
	}

	public function set_controls() {
		// SORT / FILTER
		$filter_controls = $this->get_filter_controls();

		if ( ! empty( $filter_controls ) ) {
			if ( ! empty( $filter_controls['wpPostField']['options'] ) ) {
				$filter_controls['wpPostField']['options']['post_date']     = esc_html__( 'Post date', 'bricks' );
				$filter_controls['wpPostField']['options']['post_modified'] = esc_html__( 'Post modified date', 'bricks' );
			}

			unset( $filter_controls['filterSource']['options']['taxonomy'] );
			unset( $filter_controls['filterTaxonomy'] );
			unset( $filter_controls['filterHierarchical'] );
			unset( $filter_controls['filterTaxonomyHideEmpty'] );
			unset( $filter_controls['filterHideCount'] );
			unset( $filter_controls['filterHideEmpty'] );
			unset( $filter_controls['labelMapping'] );
			unset( $filter_controls['customLabelMapping'] );

			$filter_controls['enableTime'] = [
				'label'    => esc_html__( 'Enable time', 'bricks' ),
				'type'     => 'checkbox',
				'required' => [
					[ 'filterSource', '!=', '' ],
				]
			];

			$filter_controls['isDateRange'] = [
				'label'    => esc_html__( 'Date range', 'bricks' ),
				'type'     => 'checkbox',
				'required' => [
					[ 'filterSource', '!=', '' ],
				]
			];

			$filter_controls['useMinMax'] = [
				'label'       => esc_html__( 'Min/max date', 'bricks' ),
				'type'        => 'checkbox',
				'description' => esc_html__( 'Use min/max date from index table.', 'bricks' ),
				'required'    => [
					[ 'filterSource', '!=', '' ],
				]
			];

			$filter_controls['fieldCompareOperator']['required'] = [
				[ 'filterSource', '!=', '' ],
				[ 'isDateRange', '=', '' ],
			];

			$filter_controls['fieldCompareOperator']['options'] = [
				'is'     => '==',
				'before' => '<',
				'after'  => '>',
			];

			$filter_controls['fieldCompareOperator']['placeholder'] = '==';

			$this->controls = array_merge( $this->controls, $filter_controls );
		}

		// INPUT
		$this->controls['placeholder'] = [
			'group'       => 'input',
			'label'       => esc_html__( 'Placeholder', 'bricks' ),
			'type'        => 'text',
			'inline'      => true,
			'placeholder' => esc_html__( 'Date', 'bricks' ),
		];

		$this->controls['placeholderTypography'] = [
			'group' => 'input',
			'label' => esc_html__( 'Placeholder typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '&::placeholder',
				],
			],
		];

		$this->controls['l10n'] = [
			'group'       => 'input',
			'label'       => esc_html__( 'Language', 'bricks' ),
			'type'        => 'text',
			'inline'      => true,
			'description' => '<a href="https://github.com/flatpickr/flatpickr/tree/master/src/l10n" target="_blank">' . esc_html__( 'Language codes', 'bricks' ) . '</a> (de, es, fr, etc.)',
		];
	}

	public function is_filter_input() {
		return ! empty( $this->settings['filterQueryId'] ) && ! empty( $this->settings['filterSource'] );
	}

	/**
	 * Setup filter
	 * - Prepare sources
	 * - Get min/max date
	 * - Set data-brx-filter attribute
	 */
	private function set_as_filter() {
		$settings = $this->settings;

		// Check required filter settings
		if ( empty( $settings['filterQueryId'] ) || empty( $settings['filterSource'] ) ) {
			return;
		}

		$this->prepare_sources();

		/**
		 * Get min/max date from $this->choices_source
		 * not get from $this->filtered_choices because it will be awkward if selected date is not in the choices.
		 */
		if ( ! empty( $this->choices_source ) && isset( $settings['useMinMax'] ) ) {
			// date string format:YYYY-MM-DD HH:MM:SS, in filter_value key
			// Loop through choices_source to get min/max date
			foreach ( $this->choices_source as $choice ) {
				$choice_date = $choice['filter_value'] ?? false;

				if ( ! $choice_date ) {
					continue;
				}

				// Convert to timestamp
				$choice_date = strtotime( $choice_date );

				if ( ! $choice_date ) {
					continue;
				}

				// Set min/max date
				if ( ! $this->min_date || $choice_date < $this->min_date ) {
					$this->min_date = $choice_date;
				}

				if ( ! $this->max_date || $choice_date > $this->max_date ) {
					$this->max_date = $choice_date;
				}
			}
		}

		$field_type = $settings['sourceFieldType'] ?? 'post';
		$mode       = isset( $settings['isDateRange'] ) ? 'range' : 'single';
		$operator   = $settings['fieldCompareOperator'] ?? 'is';
		$field_info = false;
		$field_key  = false;

		// Build $field_info to be used by the JS filter in frontend
		if ( $settings['filterSource'] === 'wpField' ) {
			switch ( $field_type ) {
				case 'post':
					$field_key = $settings['wpPostField'] ?? false;
					break;

				// case 'user':
				// $field_key = $settings['wpUserField'] ?? false;
				// break;

				// case 'term':
				// $field_key = $settings['wpTermField'] ?? false;
				// break;
			}

			if ( $field_key ) {
				$field_info = [
					'field_type' => $field_type,
					'field_key'  => $field_key,
					'mode'       => $mode,
					'operator'   => $operator,
				];
			} else {
				return $this->render_element_placeholder(
					[
						'title' => esc_html__( 'Required', 'bricks' ) . ': ' . esc_html__( 'Field', 'bricks' )
					]
				);
			}
		}

		elseif ( $settings['filterSource'] === 'customField' ) {
			$meta_key = $settings['customFieldKey'] ?? false;

			if ( $meta_key ) {
				$field_info = [
					'field_type' => $field_type,
					'field_key'  => $meta_key,
					'mode'       => $mode,
					'operator'   => $operator,
				];
			} else {
				return $this->render_element_placeholder(
					[
						'title' => esc_html__( 'Required', 'bricks' ) . ': ' . esc_html__( 'Meta key', 'bricks' )
					]
				);
			}
		}

		if ( empty( $field_info ) ) {
			return;
		}

		// Insert filter settings as data-brx-filter attribute
		$filter_settings                 = $this->get_common_filter_settings();
		$filter_settings['filterSource'] = $settings['filterSource'];
		$filter_settings['fieldInfo']    = $field_info;

		$this->set_attribute( '_root', 'data-brx-filter', wp_json_encode( $filter_settings ) );
	}

	public function render() {
		$settings         = $this->settings;
		$placeholder      = $settings['placeholder'] ?? esc_html__( 'Date', 'bricks' );
		$this->input_name = $settings['name'] ?? "form-field-{$this->id}";

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

		// Datepicker options
		$time_24h = get_option( 'time_format' );
		$time_24h = strpos( $time_24h, 'H' ) !== false || strpos( $time_24h, 'G' ) !== false;

		$date_format = isset( $settings['enableTime'] ) ? get_option( 'date_format' ) . ' H:i' : get_option( 'date_format' );
		$mode        = isset( $settings['isDateRange'] ) ? 'range' : 'single';

		$datepicker_options = [
			'enableTime' => isset( $settings['enableTime'] ),
			'minTime'    => '',
			'maxTime'    => '',
			'altInput'   => true,
			'altFormat'  => $date_format,
			'dateFormat' => $date_format,
			'time_24hr'  => $time_24h,
			'mode'       => $mode, // single, multiple, range
		];

		if ( isset( $settings['useMinMax'] ) ) {
			if ( $this->min_date ) {
				// convert to date string following date format
				$min_date                      = date( $date_format, $this->min_date );
				$datepicker_options['minDate'] = $min_date;
			}

			if ( $this->max_date ) {
				// convert to date string following date format
				$max_date                      = date( $date_format, $this->max_date );
				$datepicker_options['maxDate'] = $max_date;
			}
		}

		// Localization
		if ( ! empty( $settings['l10n'] ) ) {
			$datepicker_options['locale'] = $settings['l10n'];
		}

		$this->set_attribute( '_root', 'data-bricks-datepicker-options', wp_json_encode( $datepicker_options ) );

		$this->set_attribute( '_root', 'name', $this->input_name );
		$this->set_attribute( '_root', 'placeholder', $placeholder );
		$this->set_attribute( '_root', 'type', 'text' );
		$this->set_attribute( '_root', 'autocomplete', 'off' );
		$this->set_attribute( '_root', 'aria-label', $placeholder );

		// In filter AJAX call, filterValue is the current filter value
		if ( isset( $settings['filterValue'] ) ) {
			$this->set_attribute( '_root', 'value', sanitize_text_field( $settings['filterValue'] ) );
		}

		echo "<input {$this->render_attributes('_root')}></input>";
	}
}
