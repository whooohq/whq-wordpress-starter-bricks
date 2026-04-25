<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Filter_Element extends Element {
	public $category          = 'filter';
	public $input_name        = '';
	public $filter_type       = '';
	public $filtered_source   = [];
	public $choices_source    = [];
	public $data_source       = [];
	public $populated_options = [];
	public $page_filter_value = [];

	public function get_keywords() {
		return [ 'input', 'form', 'field', 'filter' ];
	}

	/**
	 * Retrieve the standard controls for filter inputs for frontend
	 */
	public function get_common_filter_settings() {
		if ( ! Helpers::enabled_query_filters() ) {
			return [];
		}

		return [
			'filterId'            => $this->id,
			'targetQueryId'       => $this->settings['filterQueryId'],
			'filterAction'        => $this->settings['filterAction'] ?? 'filter', // 'filter' or 'sort
			'filterType'          => $this->filter_type,
			'filterMethod'        => $this->settings['filterMethod'] ?? 'ajax',
			'filterApplyOn'       => $this->settings['filterApplyOn'] ?? 'change',
			'filterInputDebounce' => $this->settings['filterInputDebounce'] ?? 500,
		];
	}

	/**
	 * Determine whether this input is a filter input
	 * Will be overriden by each input if needed
	 *
	 * @return boolean
	 */
	public function is_filter_input() {
		return ! empty( $this->settings['filterQueryId'] );
	}

	public function prepare_sources() {
		// Get target query id
		$query_id = $this->settings['filterQueryId'];

		// Get filtered data from index
		$this->filtered_source = Query_Filters::get_filtered_data_from_index( $this->id, Query_Filters::get_filter_object_ids( $query_id ) );

		// Get choices data from index - for custom field filter
		$this->choices_source = Query_Filters::get_filtered_data_from_index( $this->id, Query_Filters::get_filter_object_ids( $query_id, 'original' ) );
	}

	public function set_data_source_from_taxonomy() {
		$settings = $this->settings;
		$taxonomy = $settings['filterTaxonomy'] ?? false;

		if ( ! $taxonomy ) {
			return;
		}

		// Get terms and never hide empty, we will handle it later when populating options
		$terms = get_terms(
			[
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
			]
		);

		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
			$data_source = [];

			// Set default placeholder
			$taxonomy_obj   = get_taxonomy( $taxonomy );
			$taxonomy_label = $taxonomy_obj->labels->all_items;

			// Add an empty option
			if ( $this->name === 'filter-radio' ) {
				$data_source[] = [
					'value'    => '',
					'value_id' => '',
					'text'     => $taxonomy_label,
					'class'    => 'brx-input-radio-option-empty',
					'is_all'   => true,
					'parent'   => 0,
					'children' => [],
				];
			}

			if ( $this->name === 'filter-select' ) {
				$data_source[] = [
					'value'          => '',
					'valued_id'      => '',
					'text'           => $taxonomy_label,
					'class'          => 'placeholder',
					'is_placeholder' => true,
					'parent'         => 0,
					'children'       => [],
				];
			}

			$choices_source = $this->choices_source ?? [];

			foreach ( $terms as $term ) {
				// We need to use the count from choices source
				$count = 0;

				if ( ! empty( $choices_source ) ) {
					foreach ( $choices_source as $choice ) {
						if ( $choice['filter_value'] === $term->slug ) {
							$count = $choice['count'];
							break;
						}
					}
				}

				$data_source[] = [
					'value'    => $term->slug,
					'value_id' => $term->term_id,
					'text'     => $term->name,
					'count'    => $count,
					'parent'   => $term->parent,
					'children' => [],
				];
			}

			// Set data source
			$this->data_source = $data_source;

			// Change the input name to match the taxonomy
			$this->input_name = $taxonomy;
		}
	}

	/**
	 * Similar to set_data_source_from_custom_field, but separate for easier maintenance in the future
	 */
	public function set_data_source_from_wp_field() {
		$settings             = $this->settings;
		$field_type           = $settings['sourceFieldType'] ?? 'post';
		$label_mapping        = $settings['labelMapping'] ?? 'value';
		$custom_label_mapping = $settings['customLabelMapping'] ?? false;
		$selected_field       = false;
		$data_source          = [];

		switch ( $field_type ) {
			case 'post':
				$selected_field = $settings['wpPostField'] ?? false;

				if ( ! $selected_field ) {
					return;
				}

				$selected_field_label = $this->controls['wpPostField']['options'][ $selected_field ] ?? esc_html__( 'Option', 'bricks' );

				// Use choices source
				$choices_source = $this->choices_source ?? [];

				// Set a placeholder option if this is a select input
				if ( $this->filter_type === 'select' ) {
					$data_source[] = [
						'value'          => '',
						'text'           => sprintf( '%s %s', esc_html__( 'Select', 'bricks' ), $selected_field_label ),
						'class'          => 'placeholder',
						'is_placeholder' => true,
					];
				}

				// Add an empty option for radio input
				if ( $this->filter_type === 'radio' ) {
					$data_source[] = [
						'value'    => '',
						'text'     => sprintf( esc_html__( 'All %s', 'bricks' ), $selected_field_label ),
						'class'    => 'brx-input-radio-option-empty',
						'is_all'   => true,
						'parent'   => 0,
						'children' => [],
					];
				}

				if ( ! empty( $choices_source ) ) {
					foreach ( $choices_source as $choices ) {
						// meta_value can be string 0, or empty string
						$field_value = isset( $choices['filter_value'] ) ? $choices['filter_value'] : '';
						$label       = isset( $choices['filter_value_display'] ) ? $choices['filter_value_display'] : 'No label';

						// Maybe use custom label mapping
						if ( $label_mapping === 'custom' && ! empty( $custom_label_mapping ) ) {
							// Find the label from custom label mapping array, use optionLabel if optionMetaValue is match with $meta_value
							foreach ( $custom_label_mapping as $mapping ) {
								$find_meta_value = isset( $mapping['optionMetaValue'] ) ? $mapping['optionMetaValue'] : '';

								if ( $find_meta_value === $field_value || $find_meta_value === $label ) {
									$label = $mapping['optionLabel'];
									break;
								}
							}
						}

						$data_source[] = [
							'value'          => $field_value,
							'text'           => $label,
							'count'          => $choices['count'],
							'parent'         => 0,
							'wp_field'       => $field_type,
							'selected_field' => $selected_field,
						];
					}
				}

				// Name is important when build query vars in frontend
				switch ( $selected_field ) {
					case 'post_date':
						$this->input_name = 'date';
						break;

					case 'post_modified':
						$this->input_name = 'modified';
						break;

					case 'post_author':
						$this->input_name = 'author';
						break;

					case 'post_id':
						$this->input_name = 'p';
						break;

					default:
						$this->input_name = $selected_field;
						break;
				}

				break;

			// Not in Beta
			case 'user':
				break;

			// Not in Beta
			case 'term':
				break;
		}

		// Set data source
		$this->data_source = $data_source;
	}

	public function set_data_source_from_custom_field() {
		$settings = $this->settings;

		$source_field_type    = $settings['sourceFieldType'] ?? 'post';
		$custom_field_key     = $settings['customFieldKey'] ?? false;
		$compare_operator     = $settings['fieldCompareOperator'] ?? 'IN';
		$label_mapping        = $settings['labelMapping'] ?? 'value';
		$custom_label_mapping = $settings['customLabelMapping'] ?? false;

		if ( ! $source_field_type || ! $custom_field_key ) {
			return;
		}

		$data_source = [];

		switch ( $source_field_type ) {
			case 'post':
				// Use choices source
				$choices_source = $this->choices_source ?? [];

				// Set a placeholder option if this is a select input
				if ( $this->filter_type === 'select' ) {
					$data_source[] = [
						'value'          => '',
						'text'           => sprintf( '%s %s', esc_html__( 'Select', 'bricks' ), esc_html__( 'Option', 'bricks' ) ),
						'class'          => 'placeholder',
						'is_placeholder' => true,
					];
				}

				// Add an empty option for radio input
				if ( $this->filter_type === 'radio' ) {
					$data_source[] = [
						'value'    => '',
						'text'     => esc_html__( 'All', 'bricks' ),
						'class'    => 'brx-input-radio-option-empty',
						'is_all'   => true,
						'parent'   => 0,
						'children' => [],
					];
				}

				if ( ! empty( $choices_source ) ) {
					foreach ( $choices_source as $choices ) {
						$meta_key = $custom_field_key;
						$compare  = $compare_operator;
						$is_all   = false;

						// meta_value can be string 0, or empty string
						$meta_value = isset( $choices['filter_value'] ) ? $choices['filter_value'] : '';
						$label      = isset( $choices['filter_value_display'] ) ? $choices['filter_value_display'] : 'No label';

						// Maybe use custom label mapping
						if ( $label_mapping === 'custom' && ! empty( $custom_label_mapping ) ) {
							// Find the label from custom label mapping array, use optionLabel if optionMetaValue is match with $meta_value
							foreach ( $custom_label_mapping as $mapping ) {
								if ( $mapping['optionMetaValue'] === $meta_value ) {
									$label = $mapping['optionLabel'];
									break;
								}
							}
						}

						if ( ! $meta_key ) {
							continue;
						}

						$data_source[] = [
							'value'    => $meta_value,
							'text'     => $label,
							'count'    => $choices['count'],
							'parent'   => 0,
							'meta_key' => $meta_key,
							'compare'  => $compare,
							'is_all'   => $is_all,
						];
					}
				}

				break;

			// Not in Beta
			case 'user':
				break;

			// Not in Beta
			case 'term':
				break;
		}

		$this->data_source = $data_source;
	}

	public function set_options() {
		$settings      = $this->settings;
		$hide_empty    = isset( $settings['filterHideEmpty'] );
		$hide_count    = isset( $settings['filterHideCount'] );
		$hierarchical  = isset( $settings['filterHierarchical'] );
		$filter_source = $settings['filterSource'] ?? false;
		$query_id      = $settings['filterQueryId'] ?? false;

		// Now we have data source and filtered source, we can populate options
		$options             = [];
		$filtered_source     = $this->filtered_source;
		$data_source         = $this->data_source;
		$query_results_count = Integrations\Dynamic_Data\Providers::render_tag( "{query_results_count:$query_id}", $this->post_id );

		// STEP: Hierarchical display logic
		if ( $hierarchical && $filter_source === 'taxonomy' ) {
			$cloned_data_source = $data_source;
			$sorted_source      = [];
			self::sort_terms_hierarchically( $cloned_data_source, $sorted_source );

			$flattened_source = [];
			self::flatten_terms_hierarchically( $sorted_source, $flattened_source );

			// TODO: Update children_ids on every depth recursively update_children_ids
			$data_source = $flattened_source;
		}

		foreach ( $data_source as $option_index => $source ) {
			$option = [
				'value'          => $source['value'] ?? '',
				'text'           => $source['text'] ?? '',
				'class'          => $source['class'] ?? '',
				'is_all'         => $source['is_all'] ?? false,
				'is_placeholder' => $source['is_placeholder'] ?? false,
				'count'          => $source['count'] ?? 0,
				'depth'          => $source['depth'] ?? 0,
				'children_ids'   => $source['children_ids'] ?? [],
			];

			// Get count from filtered data
			if ( ! $option['is_all'] && ! $option['is_placeholder'] ) {
				$count = $option['count'];

				/**
				 * Decide whether use count from filtered data or from data source
				 * If currently have active filters,
				 */
				$use_filtered_count = false;
				$active_filters     = Query_Filters::$active_filters;

				if ( ! empty( $active_filters ) ) {
					$active_filter_ids = array_keys( $active_filters );

					// If this filter is not active, or there are more than 1 active filters, use filtered count
					if ( ! in_array( $this->id, $active_filter_ids ) || count( $active_filter_ids ) > 1 ) {
						$use_filtered_count = true;
					}
				}

				/**
				 * Page filter.
				 * If we are in archive, taxonomy page. Should show the count from filtered data.
				 */
				$page_filters = Query_Filters::$page_filters;

				if ( ! empty( $page_filters ) ) {
					$use_filtered_count = true;
				}

				if ( $use_filtered_count ) {
					$count = 0;

					// Get count from filtered data
					foreach ( $filtered_source as $filtered ) {
						if ( $filtered['filter_value'] === $option['value'] ) {
							$count = $filtered['count'];
							break;
						}
					}
				}

				// Update option count
				$option['count'] = $count;
			}

			// If target query results count is 0, set the count to 0
			if ( $query_results_count == 0 ) {
				$option['count'] = 0;
			}

			// Disable the option if count is 0
			if ( $option['count'] === 0 && ! $option['is_all'] && ! $option['is_placeholder'] ) {
				$option['disabled'] = true;
				$option['class']   .= ' brx-option-disabled';

				// Add brx-option-empty class if hide empty is enabled
				if ( $hide_empty ) {
					$option['class'] .= ' brx-option-empty';
				}
			}

			// Use custom 'filterLabelAll' text
			if ( $option_index === 0 && isset( $settings['filterLabelAll'] ) ) {
				$option['text'] = $settings['filterLabelAll'];
			}

			// Update option text if no hide count
			if ( ! $hide_count && ! $option['is_all'] && ! $option['is_placeholder'] ) {
				$option['text'] .= ' (' . $option['count'] . ')';
			}

			// Maybe this data source has meta key
			if ( isset( $source['meta_key'] ) && ! empty( $source['meta_key'] ) ) {
				$meta_key        = $source['meta_key'];
				$compare         = $source['compare'] ?? 'IN';
				$option['value'] = $meta_key . '|' . $option['value'] . '|' . $compare;
			}

			// Maybe hierarchy
			if ( isset( $option['depth'] ) ) {
				// Add depth-n class
				$option['class'] .= ' depth-' . $option['depth'];

				// Add dash prefix to the text (except for radio input which is using button display mode)
				$indent = ! isset( $settings['displayMode'] ) || $settings['displayMode'] !== 'button';

				if ( $indent && $option['depth'] != 0 ) {
					// Custom indentation: Don't repeat
					if ( isset( $settings['filterChildIndentation'] ) ) {
						$option['text'] = esc_attr( $settings['filterChildIndentation'] ) . $option['text'];
					}
					// Default indentation: Repeat dash (one dash for each depth level)
					else {
						$option['text'] = str_repeat( '&mdash;', $option['depth'] ) . ' ' . $option['text'];
					}
				}
			}

			$option['class'] = trim( $option['class'] );

			$options[] = $option;
		}

		$this->populated_options = $options;
	}

	/**
	 * For input-select, input-radio
	 */
	public function setup_sort_options() {
		if ( ! in_array( $this->name, [ 'filter-select', 'filter-radio' ], true ) ) {
			return;
		}

		$settings = $this->settings;

		$sort_options = ! empty( $settings['sortOptions'] ) ? $settings['sortOptions'] : false;

		if ( ! $sort_options ) {
			return;
		}

		$options = [];

		if ( $this->name === 'filter-select' ) {
			// Add placeholder option
			$options[] = [
				'value'          => '',
				'text'           => sprintf( '%s %s', esc_html__( 'Select', 'bricks' ), esc_html__( 'Sort', 'bricks' ) ),
				'class'          => 'placeholder',
				'is_placeholder' => true,
			];
		}

		// if ( $this->name === 'filter-radio' ) {
			// Add an empty option
			// $options[] = [
			// 'value'    => '',
			// 'value_id' => '',
			// 'text'     => esc_html__( 'Sorting', 'bricks' ),
			// 'class'    => 'brx-input-radio-option-empty',
			// 'is_all'   => true,
			// 'parent'   => 0,
			// 'children' => [],
			// ];
		// }

		foreach ( $sort_options as $option ) {
			$sort_source = $option['optionSource'] ?? false;
			$label       = $option['optionLabel'] ?? false;

			if ( ! $sort_source || ! $label ) {
				continue;
			}

			$order = $option['optionOrder'] ?? 'ASC';
			$value = $sort_source . '_' . $order;

			if ( in_array( $sort_source, [ 'meta_value','meta_value_num' ], true ) ) {
				// Check if meta key is set
				if ( empty( $option['optionMetaKey'] ) ) {
					continue;
				}

				$prefix = 'brx_meta_';

				if ( $sort_source === 'meta_value_num' ) {
					$prefix = 'brx_metanum_';
				}

				$value = $prefix . $option['optionMetaKey'] . '_' . $order;
			}

			$options[] = [
				'value' => $value,
				'text'  => $label,
				'class' => '',
			];
		}

		$this->populated_options = $options;
	}

	/**
	 * Sort the terms hierarchically
	 */
	public static function sort_terms_hierarchically( &$data_source, &$new_source, $parentId = 0 ) {
		foreach ( $data_source as $i => $data ) {
			if ( isset( $data['is_placeholder'] ) ) {
				$new_source['placeholder'] = $data;
				unset( $data_source[ $i ] );
				continue;
			}

			if ( $data['parent'] == $parentId && isset( $data['value_id'] ) ) {
				$new_source[ $data['value_id'] ] = $data;
				unset( $data_source[ $i ] );
				continue;
			}
		}

		foreach ( $new_source as $parent_id => &$top_cat ) {
			$top_cat['children'] = [];
			self::sort_terms_hierarchically( $data_source, $top_cat['children'], $parent_id );
		}
	}

	/**
	 * Now we need to flatten the arrays.
	 * If no children, just push to $flattern and set depth to 0
	 * If has children, push the childrens to $flattern and set depth to its parent depth + 1 (recursively).
	 * The children must be placed under its parent
	 * Then save all nested children's value_id to children_ids key of its parent (recursively)
	 */
	public static function flatten_terms_hierarchically( &$source, &$flattern, $parentId = 0, $depth = 0 ) {
		foreach ( $source as $i => $data ) {
			if ( $data['parent'] == $parentId ) {
				$data['depth'] = $depth;
				$flattern[]    = $data;
				unset( $source[ $i ] );

				if ( ! empty( $data['children'] ) ) {
					// Save all children ids to children_ids key of its parent
					$children_ids                                       = array_values( array_column( $data['children'], 'value_id' ) );
					$flattern[ count( $flattern ) - 1 ]['children_ids'] = $children_ids;

					self::flatten_terms_hierarchically( $data['children'], $flattern, $data['value_id'], $depth + 1 );
				}
			}
		}

		// Unset children key
		foreach ( $flattern as $i => $term ) {
			unset( $flattern[ $i ]['children'] );
		}
	}

	/**
	 * Some of the flattened terms may have children_ids
	 * But we need to merge the children_ids to its parent recursively
	 * Not in Beta
	 */
	public static function update_children_ids( &$flattened_terms, &$updated_data_source ) {
		foreach ( $flattened_terms as $i => $term ) {
			$updated_data_source[ $i ] = $term;

			if ( ! empty( $term['children_ids'] ) && $term['depth'] > 0 ) {
				// Find the parent & merge the children_ids (recursively)
				foreach ( $updated_data_source as $j => $parent ) {
					if ( $parent['value_id'] === $term['parent'] ) {
						$updated_data_source[ $j ]['children_ids'] = array_merge( $updated_data_source[ $j ]['children_ids'], $term['children_ids'] );
						break;
					}
				}
			}
		}
	}

	/**
	 * Return query filter controls
	 *
	 * If element support query filters.
	 *
	 * Only common controls are returned.
	 * Each element might add or remove controls.
	 *
	 * @since 1.9.6
	 */
	public function get_filter_controls() {
		if ( ! in_array( $this->name, Query_Filters::supported_element_names() ) ) {
			return [];
		}

		$controls = [];

		$controls['filterQueryId'] = [
			'type'        => 'query-list',
			'label'       => esc_html__( 'Target query', 'bricks' ),
			'inline'      => true,
			'placeholder' => esc_html__( 'Select', 'bricks' ),
			'desc'        => esc_html__( 'Select the query this filter should target.', 'bricks' ) . ' ' . esc_html__( 'Only post queries are supported in this version.', 'bricks' ),
		];

		$controls['filterQueryIdInfo'] = [
			'type'     => 'info',
			'content'  => esc_html__( 'Target query has not been set. Without connecting a filter to a query, the filter has no effect.', 'bricks' ),
			'required' => [ 'filterQueryId', '=', '' ],
		];

		// Not in Beta
		// $controls['filterMethod'] = [
		// 'type'    => 'select',
		// 'label'   => esc_html__( 'Filter method', 'bricks' ),
		// 'options' => [
		// 'ajax'     => 'AJAX',
		// 'refresh'  => esc_html__( 'Refresh', 'bricks' ),
		// ],
		// ];

		$controls['filterApplyOn'] = [
			'type'        => 'select',
			'label'       => esc_html__( 'Apply on', 'bricks' ),
			'options'     => [
				'change' => esc_html__( 'Input', 'bricks' ),
				'click'  => esc_html__( 'Submit', 'bricks' ),
			],
			'inline'      => true,
			'placeholder' => esc_html__( 'Input', 'bricks' ),
			'required'    => [ 'filterQueryId', '!=', '' ],
		];

		// Select & radio input: Add filter & sort as filterActions option
		if ( in_array( $this->name, [ 'filter-select', 'filter-radio' ] ) ) {
			$controls['filterAction'] = [
				'type'        => 'select',
				'label'       => esc_html__( 'Action', 'bricks' ),
				'options'     => [
					'filter' => esc_html__( 'Filter', 'bricks' ),
					'sort'   => esc_html__( 'Sort', 'bricks' ),
				],
				'inline'      => true,
				'placeholder' => esc_html__( 'Filter', 'bricks' ),
				'required'    => [ 'filterQueryId', '!=', '' ],
			];
		}

		// Filter options for input-select, input-radio, input-checkbox, input-datepicker
		if ( in_array( $this->name, [ 'filter-checkbox', 'filter-datepicker', 'filter-radio', 'filter-range', 'filter-select' ] ) ) {
			$controls['filterSource'] = [
				'type'        => 'select',
				'label'       => esc_html__( 'Source', 'bricks' ),
				'inline'      => true,
				'options'     => [
					'taxonomy'    => esc_html__( 'Taxonomy', 'bricks' ),
					'wpField'     => esc_html__( 'WordPress field', 'bricks' ),
					'customField' => esc_html__( 'Custom field', 'bricks' ),
				],
				'placeholder' => esc_html__( 'Select', 'bricks' ),
				'required'    => [
					[ 'filterQueryId', '!=', '' ],
					[ 'filterAction', '!=', 'sort' ],
				],
			];

			$controls['filterTaxonomy'] = [
				'type'        => 'select',
				'label'       => esc_html__( 'Taxonomy', 'bricks' ),
				'inline'      => true,
				'options'     => Setup::get_taxonomies_options(),
				'placeholder' => esc_html__( 'Select', 'bricks' ),
				'required'    => [
					[ 'filterQueryId', '!=', '' ],
					[ 'filterAction', '!=', 'sort' ],
					[ 'filterSource', '=', 'taxonomy' ],
				],
			];

			$controls['filterHideEmpty'] = [
				'type'     => 'checkbox',
				'label'    => esc_html__( 'Hide empty', 'bricks' ),
				'required' => [
					[ 'filterQueryId', '!=', '' ],
					[ 'filterAction', '!=', 'sort' ],
				],
			];

			$controls['filterHideCount'] = [
				'type'     => 'checkbox',
				'label'    => esc_html__( 'Hide count', 'bricks' ),
				'required' => [
					[ 'filterQueryId', '!=', '' ],
					[ 'filterAction', '!=', 'sort' ],
				],
			];

			$controls['filterHierarchical'] = [
				'type'     => 'checkbox',
				'label'    => esc_html__( 'Hierarchical', 'bricks' ),
				'required' => [
					[ 'filterQueryId', '!=', '' ],
					[ 'filterAction', '!=', 'sort' ],
					[ 'filterSource', '=', 'taxonomy' ],
				],
			];

			// Indendation for checkbox, radio, select taxonomy
			if ( in_array( $this->name, [ 'filter-checkbox', 'filter-radio', 'filter-select' ] ) ) {
				$controls['filterChildIndentation'] = [
					'type'        => 'text',
					'inline'      => true,
					'small'       => true,
					'dd'          => false,
					'label'       => esc_html__( 'Indentation', 'bricks' ) . ': ' . esc_html__( 'Prefix', 'bricks' ),
					'placeholder' => '—',
					'required'    => [
						[ 'filterQueryId', '!=', '' ],
						[ 'filterAction', '!=', 'sort' ],
						[ 'filterSource', '=', 'taxonomy' ],
						[ 'filterHierarchical', '=', true ],
					],
				];
			}

			// Indentation gap for checkbox, radio, taxonomy
			if ( in_array( $this->name, [ 'filter-checkbox', 'filter-radio' ] ) ) {
				$controls['filterChildIndentationGap'] = [
					'type'     => 'number',
					'units'    => true,
					'dd'       => false,
					'label'    => esc_html__( 'Indentation', 'bricks' ) . ': ' . esc_html__( 'Gap', 'bricks' ),
					'required' => [
						[ 'filterQueryId', '!=', '' ],
						[ 'filterAction', '!=', 'sort' ],
						[ 'filterSource', '=', 'taxonomy' ],
						[ 'filterHierarchical', '=', true ],
					],
					'css'      => [
						[
							'selector' => '[class^="depth-"]:not(.depth-0)',
							'property' => 'margin-inline-start',
						],
					],
				];
			}

			// source field type so we can show the correct field options
			$controls['sourceFieldType'] = [
				'type'        => 'select',
				'label'       => esc_html__( 'Field type', 'bricks' ),
				'inline'      => true,
				'options'     => [
					'post' => esc_html__( 'Post', 'bricks' ),
					// 'user'     => esc_html__( 'User', 'bricks' ), // Not in Beta
					// 'term'     => esc_html__( 'Term', 'bricks' ), // Not in Beta
				],
				'placeholder' => esc_html__( 'Post', 'bricks' ),
				'required'    => [
					[ 'filterQueryId', '!=', '' ],
					[ 'filterAction', '!=', 'sort' ],
					[ 'filterSource', '=', [ 'wpField', 'customField' ] ],
				],
			];

			// source:post wpPostField - post date, post type, post status, post author, post modified date
			$controls['wpPostField'] = [
				'type'        => 'select',
				'label'       => esc_html__( 'Field', 'bricks' ),
				'inline'      => true,
				'options'     => [
					'post_id'     => esc_html__( 'Post ID', 'bricks' ),
					'post_type'   => esc_html__( 'Post type', 'bricks' ),
					'post_status' => esc_html__( 'Post status', 'bricks' ),
					'post_author' => esc_html__( 'Post author', 'bricks' ),
				],
				'placeholder' => esc_html__( 'Select', 'bricks' ),
				'required'    => [
					[ 'filterQueryId', '!=', '' ],
					[ 'filterAction', '!=', 'sort' ],
					[ 'filterSource', '=', 'wpField' ],
					[ 'sourceFieldType', '!=', [ 'user', 'term' ] ],
				],
			];

			// source:user wpUserField - user role, user display name, user nicename, user email, user url, user registered date
			// Not in Beta
			// $controls['wpUserField'] = [
			// 'type'  => 'select',
			// 'label' => esc_html__( 'Field', 'bricks' ),
			// 'options' => [
			// 'user_role' => esc_html__( 'User role', 'bricks' ),
			// 'display_name' => esc_html__( 'Display name', 'bricks' ),
			// 'user_nicename' => esc_html__( 'User nicename', 'bricks' ),
			// 'user_email' => esc_html__( 'User email', 'bricks' ),
			// 'user_url' => esc_html__( 'User url', 'bricks' ),
			// 'user_registered' => esc_html__( 'User registered date', 'bricks' ),
			// ],
			// 'placeholder' => esc_html__( 'Select', 'bricks' ),
			// 'required' => [
			// ['filterSource', '=', 'wpField'],
			// ['sourceFieldType', '=', 'user'],
			// ['filterAction', '!=', 'sort'],
			// ]
			// ];

			// source:term wpTermField - term name, term slug, taxonomy, term group
			// Not in Beta
			// $controls['wpTermField'] = [
			// 'type'  => 'select',
			// 'label' => esc_html__( 'Field', 'bricks' ),
			// 'options' => [
			// 'name' => esc_html__( 'Term name', 'bricks' ),
			// 'slug' => esc_html__( 'Term slug', 'bricks' ),
			// 'taxonomy' => esc_html__( 'Taxonomy', 'bricks' ),
			// 'term_group' => esc_html__( 'Term group', 'bricks' ),
			// ],
			// 'placeholder' => esc_html__( 'Select', 'bricks' ),
			// 'required' => [
			// ['filterSource', '=', 'wpField'],
			// ['sourceFieldType', '=', 'term'],
			// ['filterAction', '!=', 'sort'],
			// ]
			// ];

			$controls['customFieldKey'] = [
				'type'           => 'text',
				'label'          => esc_html__( 'Meta key', 'bricks' ),
				'inline'         => true,
				'hasDynamicData' => false,
				'required'       => [
					[ 'filterQueryId', '!=', '' ],
					[ 'filterSource', '=', 'customField' ],
				],
			];

			$controls['fieldCompareOperator'] = [
				'type'           => 'select',
				'label'          => esc_html__( 'Compare', 'bricks' ),
				'options'        => Setup::get_control_options( 'queryCompare' ),
				'inline'         => true,
				'hasDynamicData' => false,
				'placeholder'    => 'IN',
				'required'       => [
					[ 'filterQueryId', '!=', '' ],
					[ 'filterAction', '!=', 'sort' ],
					[ 'filterSource', '=', 'customField' ],
				],
			];

			// Radio & select filter label for first item (All)
			if ( in_array( $this->name, [ 'filter-radio', 'filter-select' ] ) ) {
				$controls['filterLabelAll'] = [
					'type'     => 'text',
					'inline'   => true,
					'dd'       => false,
					'label'    => esc_html__( 'Label', 'bricks' ) . ': ' . esc_html__( 'All', 'bricks' ),
					'required' => [
						[ 'filterQueryId', '!=', '' ],
						[ 'filterAction', '!=', 'sort' ],
					],
				];
			}

			$controls['labelMapping'] = [
				'type'        => 'select',
				'label'       => esc_html__( 'Label', 'bricks' ),
				'inline'      => true,
				'options'     => [
					'value'  => esc_html__( 'Value', 'bricks' ),
					'custom' => esc_html__( 'Custom', 'bricks' ),
					// 'acf'       => 'ACF', // Not in Beta
					// 'metabox'   => 'Metabox', // Not in Beta
					// 'jetengine' => 'JetEngine', // Not in Beta
				],
				'placeholder' => esc_html__( 'Value', 'bricks' ),
				'required'    => [
					[ 'filterQueryId', '!=', '' ],
					[ 'filterAction', '!=', 'sort' ],
					[ 'filterSource', '=', [ 'customField', 'wpField' ] ],
				],
			];

			$controls['customLabelMapping'] = [
				'type'          => 'repeater',
				'label'         => esc_html__( 'Label', 'bricks' ) . ': ' . esc_html__( 'Custom', 'bricks' ),
				'desc'          => esc_html__( 'Search and replace label value.', 'bricks' ),
				'titleProperty' => 'optionLabel',
				'fields'        => [
					'optionMetaValue' => [
						'type'           => 'text',
						'label'          => esc_html__( 'Find', 'bricks' ),
						'inline'         => true,
						'hasDynamicData' => false,
					],

					'optionLabel'     => [
						'type'           => 'text',
						'label'          => esc_html__( 'Replace with', 'bricks' ),
						'inline'         => true,
						'hasDynamicData' => false,
					],
				],
				'required'      => [
					[ 'filterQueryId', '!=', '' ],
					[ 'filterAction', '!=', 'sort' ],
					[ 'filterSource', '=', [ 'customField', 'wpField' ] ],
					[ 'labelMapping', '=', 'custom' ],
				],
			];

			// NOTE: Necessary to save & reload builder to generate filter index and populate options correctly
			$controls['filterApply'] = [
				'type'     => 'apply',
				'reload'   => true,
				'label'    => esc_html__( 'Update filter index', 'bricks' ),
				'desc'     => esc_html__( 'Click to apply the latest filter settings. This ensures all filter options are up-to-date.', 'bricks' ),
				'required' => [
					[ 'filterQueryId', '!=', '' ],
					[ 'filterAction', '!=', 'sort' ],
				],
			];
		}

		// Sorting controls
		if ( in_array( $this->name, [ 'filter-select', 'filter-radio' ] ) ) {
			$controls['sortOptions'] = [
				'type'          => 'repeater',
				'label'         => esc_html__( 'Sort options', 'bricks' ),
				'titleProperty' => 'optionLabel',
				'fields'        => [
					'optionLabel'   => [
						'type'           => 'text',
						'label'          => esc_html__( 'Label', 'bricks' ),
						'hasDynamicData' => false,
					],
					'optionSource'  => [
						'type'        => 'select',
						'label'       => esc_html__( 'Source', 'bricks' ),
						'options'     => Setup::get_control_options( 'queryOrderBy' ),
						'placeholder' => esc_html__( 'Select', 'bricks' ),
					],
					'optionMetaKey' => [
						'type'           => 'text',
						'label'          => esc_html__( 'Meta Key', 'bricks' ),
						'hasDynamicData' => false,
						'required'       => [
							[ 'optionSource', '=', [ 'meta_value','meta_value_num' ] ],
						],
					],
					'optionOrder'   => [
						'type'        => 'select',
						'label'       => esc_html__( 'Order', 'bricks' ),
						'options'     => [
							'ASC'  => esc_html__( 'Ascending', 'bricks' ),
							'DESC' => esc_html__( 'Descending', 'bricks' ),
						],
						'placeholder' => esc_html__( 'Ascending', 'bricks' ),
					]
				],
				'required'      => [
					[ 'filterQueryId', '!=', '' ],
					[ 'filterAction', '=', 'sort' ],
				],
			];
		}

		return $controls;
	}
}
