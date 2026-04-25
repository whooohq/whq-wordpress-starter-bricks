<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Woocommerce_Products_Filters extends Element {
	public $category = 'woocommerce';
	public $name     = 'woocommerce-products-filter';
	public $icon     = 'ti-filter';
	public $scripts  = [ 'bricksWooProductsFilter' ];

	// Helper property
	public $products_element;

	public function get_label() {
		return esc_html__( 'Products filter', 'bricks' );
	}

	public function set_control_groups() {
		$this->control_groups['title'] = [
			'title' => esc_html__( 'Title', 'bricks' ),
			'tab'   => 'content',
		];
	}

	public function set_controls() {
		// FILTERS

		$this->controls['filters'] = [
			'tab'           => 'content',
			'type'          => 'repeater',
			'selector'      => '.filter-item',
			'titleProperty' => 'type',
			'fields'        => [
				'type'                      => [
					'label'   => esc_html__( 'Filter type', 'bricks' ),
					'type'    => 'select',
					'options' => [
						'taxonomy'  => esc_html__( 'Taxonomy', 'bricks' ),
						'attribute' => esc_html__( 'Product attribute', 'bricks' ),
						'other'     => esc_html__( 'Other', 'bricks' ),
					],
				],

				'taxonomyFilter'            => [
					'label'    => esc_html__( 'Filter by', 'bricks' ),
					'type'     => 'select',
					'options'  => $this->get_filters_list( 'taxonomy' ),
					'required' => [ 'type', '=', 'taxonomy' ],
				],

				'attributeFilter'           => [
					'label'    => esc_html__( 'Filter by', 'bricks' ),
					'type'     => 'select',
					'options'  => $this->get_filters_list( 'attribute' ),
					'required' => [ 'type', '=', 'attribute' ],
				],

				'otherFilter'               => [
					'tab'      => 'content',
					'group'    => 'filter',
					'label'    => esc_html__( 'Filter by', 'bricks' ),
					'type'     => 'select',
					'options'  => $this->get_filters_list( 'other' ),
					'required' => [ 'type', '=', 'other' ],
				],

				// Filter Control
				'inputType'                 => [
					'label'       => esc_html__( 'Filter input', 'bricks' ),
					'type'        => 'select',
					'options'     => [
						'dropdown' => esc_html__( 'Dropdown', 'bricks' ),
						'checkbox' => esc_html__( 'Checkbox', 'bricks' ),
						'radio'    => esc_html__( 'Radio list', 'bricks' ),
						'list'     => esc_html__( 'Text list', 'bricks' ),
						'box'      => esc_html__( 'Box list', 'bricks' ),
					],
					'placeholder' => esc_html__( 'None', 'bricks' ),
					'required'    => [ 'type', '=', [ 'attribute', 'taxonomy' ] ],
				],

				'inputStock'                => [
					'label'    => esc_html__( 'Filter input', 'bricks' ),
					'type'     => 'select',
					'options'  => [
						'radio'    => esc_html__( 'Radio list', 'bricks' ),
						'dropdown' => esc_html__( 'Dropdown', 'bricks' ),
						'list'     => esc_html__( 'Text list', 'bricks' ),
					],
					'required' => [ 'otherFilter', '=', [ 'stock' ] ],
				],

				'inputRating'               => [
					'label'    => esc_html__( 'Filter input', 'bricks' ),
					'type'     => 'select',
					'options'  => [
						'stars'    => esc_html__( 'Stars', 'bricks' ),
						'checkbox' => esc_html__( 'Checkbox', 'bricks' ),
						'radio'    => esc_html__( 'Radio list', 'bricks' ),
						'dropdown' => esc_html__( 'Dropdown', 'bricks' ),
						'list'     => esc_html__( 'Text list', 'bricks' ),
						'box'      => esc_html__( 'Box list', 'bricks' ),
					],
					'required' => [ 'otherFilter', '=', 'rating' ],
				],

				// Stars
				'starsIcon'                 => [
					'label'    => esc_html__( 'Rating icon', 'bricks' ),
					'type'     => 'icon',
					'required' => [ 'inputRating', '=', 'stars' ],
				],

				'starsIconTypography'       => [
					'label'    => esc_html__( 'Rating typography', 'bricks' ),
					'type'     => 'typography',
					'css'      => [
						[
							'selector' => '.filter .stars a',
							'property' => 'font',
						],
					],
					'exclude'  => [
						'font-family',
						'font-weight',
						'font-style',
						'line-height',
						'text-align',
						'text-decoration',
						'text-shadow',
						'text-transform',
						'letter-spacing',
					],
					'required' => [
						[ 'inputRating', '=', 'stars' ],
						[ 'starsIcon.icon', '!=', '' ],
					],
				],

				'starsIconActive'           => [
					'label'    => esc_html__( 'Active rating icon', 'bricks' ),
					'type'     => 'icon',
					'required' => [ 'inputRating', '=', 'stars' ],
				],

				'starsIconActiveTypography' => [
					'label'    => esc_html__( 'Active rating typography', 'bricks' ),
					'type'     => 'typography',
					'css'      => [
						[
							'selector' => '.filter .stars a.active',
							'property' => 'font',
						],
					],
					'exclude'  => [
						'font-family',
						'font-weight',
						'font-style',
						'line-height',
						'text-align',
						'text-decoration',
						'text-shadow',
						'text-transform',
						'letter-spacing',
					],
					'required' => [
						[ 'inputRating', '=', 'stars' ],
						[ 'starsIconActive.icon', '!=', '' ],
					],
				],

				// Related with taxonomies and attributes
				'anyOptionText'             => [
					'label'       => esc_html__( 'Any option text', 'bricks' ),
					'type'        => 'text',
					'placeholder' => esc_html__( 'Any', 'bricks' ),
					'required'    => [ 'inputType', '=', [ 'dropdown', 'radio' ] ],
				],

				'parentTermsOnly'           => [
					'tab'      => 'content',
					'group'    => 'filter',
					'label'    => esc_html__( 'Only parent terms', 'bricks' ),
					'type'     => 'checkbox',
					'inline'   => true,
					'required' => [ 'type', '=', [ 'attribute', 'taxonomy' ] ],
				],

				'showEmptyTerms'            => [
					'tab'      => 'content',
					'group'    => 'filter',
					'label'    => esc_html__( 'Show empty terms', 'bricks' ),
					'type'     => 'checkbox',
					'inline'   => true,
					'required' => [ 'type', '=', [ 'attribute', 'taxonomy' ] ],
				],

				// Slider
				'sliderMin'                 => [
					'tab'      => 'content',
					'group'    => 'filter',
					'label'    => esc_html__( 'Min. value', 'bricks' ),
					'type'     => 'number',
					'min'      => 0,
					'required' => [ 'otherFilter', '=', 'price' ],
				],

				'sliderMax'                 => [
					'label'    => esc_html__( 'Max. value', 'bricks' ),
					'type'     => 'number',
					'min'      => 0,
					'required' => [ 'otherFilter', '=', 'price' ],
				],

				// Reset Button [filter = reset]
				'resetText'                 => [
					'type'        => 'text',
					'placeholder' => esc_html__( 'Reset', 'bricks' ),
					'required'    => [ 'otherFilter', '=', 'reset' ],
				],

				'resetBackgroundColor'      => [
					'label'    => esc_html__( 'Background', 'bricks' ),
					'type'     => 'color',
					'css'      => [
						[
							'selector' => '.reset-button',
							'property' => 'background-color',
						],
					],
					'required' => [ 'otherFilter', '=', 'reset' ],
				],

				'resetBorder'               => [
					'type'     => 'border',
					'label'    => esc_html__( 'Border', 'bricks' ),
					'css'      => [
						[
							'selector' => '.reset-button',
							'property' => 'border',
						],
					],
					'required' => [ 'otherFilter', '=', 'reset' ],
				],

				'resetTypography'           => [
					'tab'      => 'content',
					'group'    => 'filter',
					'label'    => esc_html__( 'Typography', 'bricks' ),
					'type'     => 'typography',
					'css'      => [
						[
							'selector' => '.reset-button',
							'property' => 'font',
						],
					],
					'required' => [ 'otherFilter', '=', 'reset' ],
				],

				// Filter title

				'title'                     => [
					'type'        => 'text',
					'placeholder' => esc_html__( 'Filter title', 'bricks' ),
					'required'    => [
						[ 'type', '!=', '' ],
						[ 'hideTitle', '=', '' ],
					],
				],

				'hideTitle'                 => [
					'label'    => esc_html__( 'Hide title', 'bricks' ),
					'type'     => 'checkbox',
					'inline'   => true,
					'required' => [ 'type', '!=', '' ],
				],

				'collapse'                  => [
					'label'    => esc_html__( 'Collapse', 'bricks' ),
					'type'     => 'checkbox',
					'inline'   => true,
					'required' => [
						[ 'type', '!=', '' ],
						[ 'hideTitle', '=', '' ],
					],
				],

				// Search

				'searchBackground'          => [
					'label'    => esc_html__( 'Background', 'bricks' ),
					'type'     => 'color',
					'css'      => [
						[
							'selector' => 'input[type="search"]',
							'property' => 'background-color',
						],
					],
					'required' => [ 'otherFilter', '=', 'search' ],
				],

				'searchBorder'              => [
					'label'    => esc_html__( 'Border', 'bricks' ),
					'type'     => 'border',
					'css'      => [
						[
							'selector' => 'input[type="search"]',
							'property' => 'border',
						],
					],
					'required' => [ 'otherFilter', '=', 'search' ],
				],

				'searchBoxShadow'           => [
					'label'    => esc_html__( 'Box shadow', 'bricks' ),
					'type'     => 'box-shadow',
					'css'      => [
						[
							'selector' => 'input[type="search"]',
							'property' => 'box-shadow',
						],
					],
					'required' => [ 'otherFilter', '=', 'search' ],
				],

				'searchTypography'          => [
					'label'    => esc_html__( 'Typography', 'bricks' ),
					'type'     => 'typography',
					'css'      => [
						[
							'selector' => 'input[type="search"]',
							'property' => 'font',
						],
						[
							'selector' => 'input[type="search"]::placeholder',
							'property' => 'font',
						],
					],
					'required' => [ 'otherFilter', '=', 'search' ],
				],

				'searchIcon'                => [
					'label'    => esc_html__( 'Icon', 'bricks' ),
					'type'     => 'icon',
					'rerender' => true,
					'inline'   => true,
					'required' => [ 'otherFilter', '=', 'search' ],
				],
			],
		];

		$this->controls['spacing'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Spacing', 'bricks' ),
			'type'        => 'number',
			'units'       => true,
			'css'         => [
				[
					'property' => 'gap',
				],
			],
			'placeholder' => '30px',
		];

		// TITLE

		$this->controls['titleMargin'] = [
			'tab'         => 'content',
			'group'       => 'title',
			'label'       => esc_html__( 'Margin', 'bricks' ),
			'type'        => 'spacing',
			'css'         => [
				[
					'property' => 'margin',
					'selector' => '.title',
				],
			],
			'required'    => [ 'hideTitle', '=', '' ],
			'placeholder' => [
				'top'    => 0,
				'right'  => 0,
				'bottom' => '15px',
				'left'   => 0,
			],
		];

		$this->controls['titlePadding'] = [
			'tab'      => 'content',
			'group'    => 'title',
			'label'    => esc_html__( 'Padding', 'bricks' ),
			'type'     => 'spacing',
			'css'      => [
				[
					'property' => 'padding',
					'selector' => '.title',
				],
			],
			'required' => [ 'hideTitle', '=', '' ],
		];

		$this->controls['titleTypography'] = [
			'tab'      => 'content',
			'group'    => 'title',
			'label'    => esc_html__( 'Typography', 'bricks' ),
			'type'     => 'typography',
			'css'      => [
				[
					'property' => 'font',
					'selector' => '.title .title-tag',
				],
			],
			'required' => [ 'hideTitle', '=', '' ],
		];

		$this->controls['titleBackgroundColor'] = [
			'tab'   => 'content',
			'group' => 'title',
			'label' => esc_html__( 'Background color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.title',
				],
			],
		];

		$this->controls['titleBorder'] = [
			'tab'   => 'content',
			'group' => 'title',
			'label' => esc_html__( 'Border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.title',
				],
			],
		];

		// ICON

		$this->controls['iconSeparator'] = [
			'tab'   => 'content',
			'group' => 'title',
			'label' => esc_html__( 'Icon', 'bricks' ),
			'type'  => 'separator',
		];

		$this->controls['iconExpanded'] = [
			'tab'      => 'content',
			'group'    => 'title',
			'label'    => esc_html__( 'Icon expanded', 'bricks' ),
			'type'     => 'icon',
			'default'  => [
				'library' => 'ionicons',
				'icon'    => 'ion-ios-remove',
			],
			'rerender' => true,
			'required' => [ 'hideTitle', '=', '' ],
		];

		$this->controls['iconCollapsed'] = [
			'tab'      => 'content',
			'group'    => 'title',
			'label'    => esc_html__( 'Icon collapsed', 'bricks' ),
			'type'     => 'icon',
			'default'  => [
				'library' => 'ionicons',
				'icon'    => 'ion-ios-add',
			],
			'rerender' => true,
			'required' => [ 'hideTitle', '=', '' ],
		];

		$this->controls['iconTypography'] = [
			'tab'      => 'content',
			'group'    => 'title',
			'label'    => esc_html__( 'Icon typography', 'bricks' ),
			'type'     => 'typography',
			'css'      => [
				[
					'selector' => '.toggle',
					'property' => 'font',
				],
			],
			'required' => [ 'hideTitle', '=', '' ],
		];

		$this->controls['iconPosition'] = [
			'tab'         => 'content',
			'group'       => 'title',
			'label'       => esc_html__( 'Icon position', 'bricks' ),
			'type'        => 'select',
			'options'     => [
				'row'         => esc_html__( 'Right', 'bricks' ),
				'row-reverse' => esc_html__( 'Left', 'bricks' ),
			],
			'inline'      => true,
			'css'         => [
				[
					'property' => 'flex-direction',
					'selector' => '.title',
				],
			],
			'placeholder' => esc_html__( 'Right', 'bricks' ),
			'required'    => [ 'hideTitle', '=', '' ],
		];
	}

	public function render() {
		$settings = $this->settings;

		if ( empty( $settings['filters'] ) ) {
			return $this->render_element_placeholder( [ 'title' => esc_html__( 'Please set at least one product filter.', 'bricks' ) ] );
		}

		echo "<ul {$this->render_attributes( '_root' )}>";

		foreach ( $settings['filters'] as $index => $filter ) {
			$filter_type = $filter['type'] ?? '';
			$filter_by   = ! empty( $filter[ "{$filter_type}Filter" ] ) ? $filter[ "{$filter_type}Filter" ] : '';

			if ( $filter_by === 'price' ) {
				$control = 'slider';
			} elseif ( $filter_by === 'reset' ) {
				$control = 'reset';
			} elseif ( $filter_by === 'search' ) {
				$control = 'search';
			} elseif ( $filter_type === 'other' ) {
				// Rating, Stock
				$control = isset( $filter[ 'input' . ucfirst( $filter_by ) ] ) ? $filter[ 'input' . ucfirst( $filter_by ) ] : '';
			} else {
				$control = isset( $filter['inputType'] ) ? $filter['inputType'] : false;
			}

			if ( ! $control ) {
				echo $this->render_element_placeholder(
					[
						'title' => esc_html__( 'Please select a filter input.', 'bricks' ),
					]
				);

				continue;
			}

			// Control: Reset
			if ( $control === 'reset' ) {
				$this->render_control_reset( $filter );

				continue;
			}

			// Control: Product search
			elseif ( $control === 'search' ) {
				$this->render_control_search( $filter, $filter_by );

				continue;
			}

			// Prepare to render the regular filter
			$control = "render_control_$control";

			if ( ! isset( $filter['hideTitle'] ) ) {
				// Title
				if ( ! isset( $filter['title'] ) ) {
					$filters_list = $this->get_filters_list( $filter_type );
					$title        = array_key_exists( $filter_by, $filters_list ) ? $filters_list[ $filter_by ] : '';
				} else {
					$title = $filter['title'];
				}

				$icon_expanded  = isset( $settings['iconExpanded'] ) ? self::render_icon( $settings['iconExpanded'], [ 'toggle', 'expanded' ] ) : false;
				$icon_collapsed = isset( $settings['iconCollapsed'] ) ? self::render_icon( $settings['iconCollapsed'], [ 'toggle', 'collapsed' ] ) : false;

				$this->set_attribute( "title-$index", 'class', 'title' );
			}

			// Filter
			$filter_classes = [ 'filter-item' ];

			if ( ! isset( $filter['collapse'] ) || isset( $filter['hideTitle'] ) || ! empty( $_GET[ "b_$filter_by" ] ) ) {
				$filter_classes[] = 'open';
			}

			$this->set_attribute( "filter-$index", 'class', $filter_classes );

			// STEP: Render
			echo "<li {$this->render_attributes( "filter-$index" )}>";

			if ( ! isset( $filter['hideTitle'] ) ) {
				echo "<div {$this->render_attributes( "title-$index" )}>";
				echo '<h6 class="title-tag">' . esc_html( $title ) . '</h6>';

				if ( $icon_expanded ) {
					echo $icon_expanded; }

				if ( $icon_collapsed ) {
					echo $icon_collapsed;
				}

				echo '</div>';
			}

			// Render filter control (e.g.: render_control_dropdown( 'rating' ))
			$this->$control( $filter, $filter_by );

			echo '</li>';
		}

		echo '</ul>';
	}

	public function render_control_dropdown( $filter, $filter_by ) {
		$settings = $this->settings;

		// Query arg
		$query_arg = "b_$filter_by";

		// Filter title
		$title = isset( $filter['title'] ) ? $filter['title'] : '';

		// Filter options
		$options = $this->get_filter_options( $filter, $filter_by );

		// Current value
		$current_value = isset( $_GET[ $query_arg ] ) ? wp_unslash( $_GET[ $query_arg ] ) : '';

		// Take the first value if it's an array, otherwise use the value as is
		$current_value = sanitize_text_field( is_array( $current_value ) ? current( $current_value ) : $current_value );

		?>
		<form class="filter" method="get">
			<select name="<?php echo $query_arg; ?>" class="dropdown" aria-label="<?php echo esc_attr( $title ); ?>">
				<option value=""><?php echo ! empty( $filter['anyOptionText'] ) ? $filter['anyOptionText'] : esc_html__( 'Any', 'bricks' ); ?></option>

				<?php foreach ( $options as $option ) { ?>
					<option value="<?php echo esc_attr( $option['id'] ); ?>" <?php selected( $current_value, $option['id'] ); ?>><?php echo esc_html( $option['name'] ); ?></option>
				<?php } ?>
			</select>
			<input type="hidden" name="paged" value="1" />
			<?php wc_query_string_form_fields( null, [ $query_arg, 'submit', 'paged', 'product-page' ] ); ?>
		</form>
		<?php
	}

	public function render_control_radio( $filter, $filter_by ) {
		$settings = $this->settings;

		// Query arg
		$query_arg = "b_$filter_by";

		// Filter title
		$title = isset( $filter['title'] ) ? $filter['title'] : '';

		// Filter options
		$options = $this->get_filter_options( $filter, $filter_by );

		// Current value
		$current_value = isset( $_GET[ $query_arg ] ) ? wp_unslash( $_GET[ $query_arg ] ) : '';

		// Take the first value if it's an array, otherwise use the value as is
		$current_value = sanitize_text_field( is_array( $current_value ) ? current( $current_value ) : $current_value );

		$key_base = sanitize_key( $query_arg . '_' . $this->id . '_' );
		?>
		<form class="filter radio" method="get">
			<ul class="radio-buttons" aria-label="<?php echo esc_attr( $title ); ?>">
				<li>
					<input type="radio" id="<?php echo $key_base . 'novalue'; ?>" name="<?php echo $query_arg; ?>" value="">
					<label for="<?php echo $key_base . 'novalue'; ?>"><?php echo ! empty( $filter['anyOptionText'] ) ? $filter['anyOptionText'] : esc_html__( 'Any', 'bricks' ); ?></label>
				</li>

				<?php foreach ( $options as $option ) { ?>
				<li>
					<input type="radio" id="<?php echo $key_base . $option['id']; ?>" name="<?php echo $query_arg; ?>" value="<?php echo esc_attr( $option['id'] ); ?>" <?php checked( $current_value, $option['id'] ); ?>>
					<label for="<?php echo $key_base . $option['id']; ?>"><?php echo esc_html( $option['name'] ); ?></label>
				</li>
				<?php } ?>
			</ul>
			<input type="hidden" name="paged" value="1" />
			<?php wc_query_string_form_fields( null, [ $query_arg, 'submit', 'paged', 'product-page' ] ); ?>
		</form>
		<?php
	}

	public function render_control_checkbox( $filter, $filter_by ) {
		$settings = $this->settings;

		// Query arg
		$query_arg = "b_$filter_by";

		// Filter title
		$title = isset( $filter['title'] ) ? $filter['title'] : '';

		// Filter options
		$options = $this->get_filter_options( $filter, $filter_by );

		// Current value (= array)
		$current_value = isset( $_GET[ $query_arg ] ) ? (array) wp_unslash( $_GET[ $query_arg ] ) : [];

		// Sanitize the values
		$current_value = array_map( 'sanitize_text_field', $current_value );

		$key_base = sanitize_key( $query_arg . '_' . $this->id . '_' );
		?>
		<form class="filter checkbox" method="get">
			<ul class="checkboxes" aria-label="<?php echo esc_attr( $title ); ?>">
				<?php foreach ( $options as $option ) { ?>
					<li>
						<input type="checkbox" id="<?php echo $key_base . $option['id']; ?>" name="<?php echo $query_arg; ?>[]" value="<?php echo esc_attr( $option['id'] ); ?>" <?php checked( in_array( $option['id'], $current_value ) ); ?>>
					  <label for="<?php echo $key_base . $option['id']; ?>"><?php echo esc_html( $option['name'] ); ?></label>
					</li>
				<?php } ?>
			</ul>
			<input type="hidden" name="paged" value="1" />
			<?php wc_query_string_form_fields( null, [ $query_arg, 'submit', 'paged', 'product-page' ] ); ?>
		</form>
		<?php
	}

	public function render_control_list( $filter, $filter_by ) {
		$settings = $this->settings;

		// Query arg
		$query_arg = "b_$filter_by";

		// Filter title
		$title = isset( $filter['title'] ) ? $filter['title'] : '';

		// Filter options
		$options = $this->get_filter_options( $filter, $filter_by );

		// Current value
		$current_value = isset( $_GET[ $query_arg ] ) ? wp_unslash( $_GET[ $query_arg ] ) : '';

		// Take the first value if it's an array, otherwise use the value as is
		$current_value = sanitize_text_field( is_array( $current_value ) ? current( $current_value ) : $current_value );

		$base_url = esc_url( remove_query_arg( [ 'paged', 'product-page' ] ) );
		?>

		<div class="filter text-list">
			<ul class="text-list" aria-label="<?php echo esc_attr( $title ); ?>">
				<?php
				foreach ( $options as $option ) {
					if ( $current_value == $option['id'] ) {
						$class = 'class="current"';
						$url   = remove_query_arg( $query_arg, $base_url );
					} else {
						$class = '';
						$url   = add_query_arg( $query_arg, $option['id'], $base_url );
					}
					?>
					<li <?php echo $class; ?>>
						<a href="<?php echo esc_url( $url ); ?>" title="<?php echo esc_attr( $option['name'] ); ?>"><?php echo esc_html( $option['name'] ); ?></a>
					</li>
				<?php } ?>
			</ul>
		</div>
		<?php
	}

	public function render_control_box( $filter, $filter_by ) {
		$settings = $this->settings;

		// Query arg
		$query_arg = "b_$filter_by";

		// Filter title
		$title = isset( $filter['title'] ) ? $filter['title'] : '';

		// Filter options
		$options = $this->get_filter_options( $filter, $filter_by );

		// Current value (= array)
		$current_value = isset( $_GET[ $query_arg ] ) ? (array) wp_unslash( $_GET[ $query_arg ] ) : [];

		// Sanitize the values
		$current_value = array_map( 'sanitize_text_field', $current_value );

		$key_base = sanitize_key( $query_arg . '_' . $this->id . '_' );
		?>
		<form class="filter" method="get">
			<ul class="box-list" aria-label="<?php echo esc_attr( $title ); ?>">
				<?php foreach ( $options as $option ) { ?>
					<li class="<?php echo in_array( $option['id'], $current_value ) ? 'box checked' : 'box'; ?>">
						<label for="<?php echo $key_base . $option['id']; ?>"><?php echo esc_html( $option['name'] ); ?></label>
						<input style="display:none;" type="checkbox" id="<?php echo $key_base . $option['id']; ?>" name="<?php echo $query_arg; ?>[]" value="<?php echo esc_attr( $option['id'] ); ?>" <?php checked( in_array( $option['id'], $current_value ) ); ?>>
					</li>
				<?php } ?>
			</ul>
			<input type="hidden" name="paged" value="1" />
			<?php wc_query_string_form_fields( null, [ $query_arg, 'submit', 'paged', 'product-page' ] ); ?>
		</form>
		<?php
	}

	public function render_control_stars( $filter, $filter_by ) {
		$settings = $this->settings;

		// Query arg
		$query_arg = "b_$filter_by";

		// Filter title
		$title = isset( $filter['title'] ) ? $filter['title'] : '';

		// Current value
		$current_value = isset( $_GET[ $query_arg ] ) ? wp_unslash( $_GET[ $query_arg ] ) : '';

		// Take the first value if it's an array, otherwise use the value as is
		$current_value = absint( is_array( $current_value ) ? current( $current_value ) : $current_value );

		$selected = $current_value ? 'selected' : '';

		$base_url = esc_url( remove_query_arg( [ 'paged', 'product-page' ] ) );

		// Icons
		$icon        = isset( $filter['starsIcon'] ) ? self::render_icon( $filter['starsIcon'], [ 'icon' ] ) : false;
		$icon_active = isset( $filter['starsIconActive'] ) ? self::render_icon( $filter['starsIconActive'], [ 'icon', 'active' ] ) : false;

		if ( ! $icon && ! $icon_active ) {
			return $this->render_element_placeholder(
				[
					'title' => esc_html__( 'Please select rating icons.', 'bricks' ),
				]
			);
		}
		?>
		<div class="filter">
			<div class="stars <?php echo $selected; ?>" aria-label="<?php echo esc_attr( $title ); ?>">
				<?php
				foreach ( range( 1, 5 ) as $key ) {
					$class = $current_value && $key <= $current_value ? 'active' : '';
					$url   = $current_value == $key ? remove_query_arg( $query_arg, $base_url ) : add_query_arg( $query_arg, $key, $base_url );
					?>
					<a class="<?php echo $class; ?>" href="<?php echo esc_url( $url ); ?>">
						<span><?php echo $key; ?></span>
						<?php echo $icon; ?>
						<?php echo $icon_active; ?>
					</a>
				<?php } ?>
			</div>
		</div>
		<?php
	}

	public function render_control_slider( $filter, $filter_by ) {
		$settings = $this->settings;

		// Query args
		$min_arg = "min_$filter_by";
		$max_arg = "max_$filter_by";

		// Filter title
		$title = isset( $filter['title'] ) ? $filter['title'] : '';

		// Min and Max
		$min_value = absint( isset( $filter['sliderMin'] ) ? $filter['sliderMin'] : 0 );
		$max_value = absint( isset( $filter['sliderMax'] ) ? $filter['sliderMax'] : 200 );

		// Current value
		$value_min = absint( isset( $_GET[ $min_arg ] ) ? absint( $_GET[ $min_arg ] ) : $min_value );
		$value_max = absint( isset( $_GET[ $max_arg ] ) ? absint( $_GET[ $max_arg ] ) : $max_value );

		$key_base = sanitize_key( $this->id . '_' );
		?>
		<form class="filter" method="get">
			<div class="double-slider-wrap" aria-label="<?php echo esc_html( $title ); ?>" data-currency="<?php echo get_woocommerce_currency_symbol(); ?>">
				<label for="<?php echo $key_base . 'lower'; ?>" class="lower"><?php esc_html_e( 'Min. price', 'bricks' ); ?></label>
				<input id="<?php echo $key_base . 'lower'; ?>" class="lower" name="<?php echo $min_arg; ?>" type="range" min="<?php echo $min_value; ?>" max="<?php echo $max_value; ?>" step="1" value="<?php echo esc_attr( $value_min ); ?>">
				<label for="<?php echo $key_base . 'upper'; ?>" class="upper"><?php esc_html_e( 'Max. price', 'bricks' ); ?></label>
				<input id="<?php echo $key_base . 'upper'; ?>" class="upper" name="<?php echo $max_arg; ?>" type="range" min="<?php echo $min_value; ?>" max="<?php echo $max_value; ?>" step="1" value="<?php echo esc_attr( $value_max ); ?>">
				<div class="value-wrap">
					<span class="value lower"></span>
					<span class="value upper"></span>
				</div>
			</div>

			<input type="hidden" name="paged" value="1" />
			<?php wc_query_string_form_fields( null, [ $min_arg, $max_arg, 'submit', 'paged', 'product-page' ] ); ?>
		</form>
		<?php
	}

	public function render_control_reset( $filter ) {
		$settings = $this->settings;

		$reset_url = explode( '?', esc_url_raw( add_query_arg( [] ) ) );
		$reset_url = $reset_url[0];

		$text = isset( $filter['resetText'] ) ? trim( $filter['resetText'] ) : esc_html__( 'Reset', 'bricks' );
		?>
		<li class="filter-item">
			<a href="<?php echo esc_url( $reset_url ); ?>" class="reset-button"><?php echo esc_html( $text ); ?></a>
		</li>
		<?php
	}

	public function render_control_search( $filter, $filter_by ) {
		$query_arg     = "b_$filter_by";
		$current_value = isset( $_GET[ $query_arg ] ) ? wp_unslash( $_GET[ $query_arg ] ) : '';
		// Take the first value if it's an array, otherwise use the value as is
		$current_value = sanitize_text_field( is_array( $current_value ) ? current( $current_value ) : $current_value );
		$placeholder   = $filter['title'] ?? esc_html__( 'Search ...', 'bricks' );
		$icon          = isset( $filter['searchIcon'] ) ? self::render_icon( $filter['searchIcon'], [ 'icon' ] ) : false;
		?>
		<li class="filter-item search-form">
			<form role="search" method="get">
				<label class="screen-reader-text"><span><?php esc_html_e( 'Search ...', 'bricks' ); ?></span></label>
				<input type="search" value="<?php echo esc_attr( $current_value ); ?>" name="<?php echo esc_attr( $query_arg ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>" spellcheck="false" autocomplete="false" />
				<?php
				if ( $icon ) {
					echo '<button type="submit">' . $icon . '</button>';
				}
				?>
			</form>
		</li>
		<?php
	}

	public function get_filters_list( $filter_type ) {
		$list = Woocommerce_Helpers::get_filters_list( false );

		return array_key_exists( $filter_type, $list ) ? wp_list_pluck( $list[ $filter_type ], 'label', 'name' ) : [];
	}

	public function get_filter_options( $filter, $filter_by ) {
		$settings = $this->settings;

		if ( $filter_by === 'stock' ) {
			$stock_defaults = wc_get_product_stock_status_options();

			foreach ( $stock_defaults as $key => $label ) {
				$options[] = [
					'id'   => $key,
					'name' => $label
				];

				if ( 'instock' == $key ) {
					$options[] = [
						'id'   => 'lowstock',
						'name' => esc_html__( 'Low stock', 'bricks' )
					];
				}
			}

		} elseif ( $filter_by === 'rating' ) {
			foreach ( range( 1, 5 ) as $key ) {
				$options[] = [
					'id'   => $key,
					// translators: %s: rating
					'name' => sprintf( esc_html__( 'Rated %s out of 5', 'bricks' ), $key )
				];
			}
		} else {
			$options = [];

			$terms_args = [
				'taxonomy'   => $filter_by,
				'hide_empty' => empty( $filter['showEmptyTerms'] ),
			];

			$terms_include = $this->get_terms_include( $filter_by );

			if ( ! empty( $terms_include ) ) {
				$terms_args['include'] = $terms_include;
			}

			if ( isset( $filter['parentTermsOnly'] ) ) {
				$terms_args['parent'] = 0;
			}

			$terms = get_terms( $terms_args );

			if ( $terms ) {
				foreach ( $terms as $term ) {
					$options[] = [
						'id'   => $term->term_id,
						'name' => $term->name
					];
				}
			}
		}

		// NOTE: Undocumented
		$options = apply_filters( 'bricks/woocommerce/products_filters/options', $options, $settings );

		return $options;
	}

	/**
	 * If the products element is filtering the main query, return those specific terms
	 *
	 * @return array
	 */
	public function get_terms_include( $taxonomy ) {

		// STEP: Find the products filter element page/template data
		$element_data = Helpers::get_element_data( $this->post_id, $this->id );

		if ( empty( $element_data['elements'] ) ) {
			return false;
		}

		// STEP: Find the product element (or the query loop)
		$element = Woocommerce_Helpers::get_products_element( $element_data['elements'] );

		if ( ! $element ) {
			return false;
		}

		if ( $element['name'] === 'woocommerce-products' ) {
			if ( $taxonomy == 'product_cat' && ! empty( $element['settings']['categories'] ) ) {
				return $element['settings']['categories'];
			}

			if ( $taxonomy == 'product_tag' && ! empty( $element['settings']['tags'] ) ) {
				return $element['settings']['tags'];
			}
		}

		// Maybe container Query Loop (since 1.5)
		if ( ! empty( $element['settings']['query'] ) ) {

			$query_vars = Query::set_tax_query_vars( $element['settings']['query'] );

			if ( ! empty( $query_vars['tax_query'] ) && is_array( $query_vars['tax_query'] ) ) {

				$tax_values = [];

				foreach ( $query_vars['tax_query'] as $key => $condition ) {

					if ( ! isset( $condition['taxonomy'] ) ) {
						if ( is_array( $condition ) ) {
							array_walk( $condition, [ $this, 'get_tax_query_values' ], $tax_values );
						}

						continue;
					}

					$tax_values = $this->get_tax_query_values( $condition, $key, $tax_values );
				}

				return isset( $tax_values[ $taxonomy ] ) ? $tax_values[ $taxonomy ] : [];
			}
		}

		return [];
	}

	/**
	 * Helper method to get the tax_query terms per taxonomy
	 *
	 * @since 1.5
	 */
	public function get_tax_query_values( $condition, $key, $tax_values ) {
		if ( isset( $condition['taxonomy'] ) && ( empty( $condition['operator'] ) || $condition['operator'] == 'IN' ) ) {
			$tax_values[ $condition['taxonomy'] ] = $condition['terms'];
		}

		return $tax_values;
	}
}
