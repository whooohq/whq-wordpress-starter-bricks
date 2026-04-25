<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

abstract class Element {
	/**
	 * Gutenberg block name: 'core/heading', etc.
	 *
	 * Mapping of Gutenberg block to Bricks element to load block post_content in Bricks and save Bricks data as WordPress post_content.
	 */
	public $block = null;

	// Builder
	public $element;
	public $category;
	public $name;
	public $label;
	public $keywords;
	public $icon;
	public $controls;
	public $control_groups;
	public $control_options;
	public $css_selector;
	public $scripts         = [];
	public $post_id         = 0;
	public $draggable       = true;  // false to prevent dragging over entire element in builder
	public $deprecated      = false; // true to hide element in panel (editing of existing deprecated element still works)
	public $panel_condition = [];    // array conditions to show the element in the panel

	// Frontend
	public $id;
	public $tag        = 'div';
	public $attributes = [];
	public $settings;
	public $theme_styles = [];

	public $is_frontend = false;

	/**
	 * Custom attributes
	 *
	 * true: renders custom attributes on element '_root' (= default)
	 * false: handle custom attributes in element render_attributes( 'xxx', true ) function (e.g. Nav Menu)
	 *
	 * @since 1.3
	 */
	public $custom_attributes = true;

	/**
	 * Nestable elements
	 *
	 * @since 1.5
	 */
	public $nestable = false;      // true to allow to insert child elements (e.g. Container, Div)
	public $nestable_item;         // First child of nestable element (Use as blueprint for nestable children & when adding repeater item)
	public $nestable_children;     // Array of children elements that are added inside nestable element when it's added to the canvas.
	public $nestable_hide = false; // Boolean to hide nestable in Structure & prevent dragging (true if no full access)
	public $nestable_html = '';    // Nestable HTML with placeholder for element 'children'

	public $vue_component;         // Set specific Vue component to render element in builder (e.g. 'bricks-nestable' for Section, Container, Div)

	public $original_query = '';

	public function __construct( $element = null ) {
		$this->element           = $element;
		$this->label             = $this->get_label();
		$this->keywords          = $this->get_keywords();
		$this->is_frontend       = isset( $element['is_frontend'] ) ? $element['is_frontend'] : bricks_is_frontend();
		$this->id                = ! empty( $element['id'] ) ? $element['id'] : Helpers::generate_random_id( false );
		$this->settings          = ! empty( $element['settings'] ) ? $element['settings'] : [];
		$this->nestable_item     = $this->get_nestable_item();
		$this->nestable_children = $this->get_nestable_children();
		// No longer in use (@since 1.9.7) to show nestable for all users
		// $this->nestable_hide     = Capabilities::current_user_has_full_access() === false;

		// To distinguish non-layout nestables (slider-nested, etc.) in Vue render
		if ( $this->nestable && ! $this->is_layout_element() ) {
			$this->nestable_html = true;
		}

		// Element-specific theme style settings
		if ( ! empty( $element['themeStyles'] ) ) {
			$this->theme_styles = $element['themeStyles'];
		} elseif ( ! empty( Theme_Styles::$active_settings[ $this->name ] ) ) {
			$this->theme_styles = Theme_Styles::$active_settings[ $this->name ];
		}

		$this->tag = $this->get_tag();
	}

	/**
	 * Populate element data (when element is requested)
	 *
	 * Builder: Load all elements
	 * Frontend: Load only requested elements
	 *
	 * @since 1.0
	 */
	public function load() {
		$this->control_options = Setup::$control_options;

		// Control groups
		$this->control_groups = [];
		$this->set_common_control_groups();
		$this->set_control_groups();

		// @see: https://academy.bricksbuilder.io/article/filter-bricks-elements-element_name-control_groups
		$this->control_groups = apply_filters( "bricks/elements/$this->name/control_groups", $this->control_groups );

		// Controls
		$this->controls = [];
		$this->set_controls_before();
		$this->set_controls();
		$this->set_controls_after();

		// @see: https://academy.bricksbuilder.io/article/filter-bricks-elements-element_name-controls
		$this->controls = apply_filters( "bricks/elements/$this->name/controls", $this->controls );

		// Set CSS selector
		if ( ! empty( $this->css_selector ) ) {
			$this->set_css_selector( $this->css_selector );
		}

		// NOTE: Undocumented @see: https://academy.bricksbuilder.io/article/filter-bricks-elements-element_name-scripts (@since 1.5.5)
		$this->scripts = apply_filters( "bricks/elements/$this->name/scripts", $this->scripts );

		// Frontend
		$this->add_actions();
		$this->add_filters();
	}

	/**
	 * Add element-specific WordPress actions to run in constructor
	 *
	 * @since 1.0
	 */
	public function add_actions() {}

	/**
	 * Add element-specific WordPress filters to run in constructor
	 *
	 * E.g. 'nav_menu_item_title' filter in Element_Nav_Menu
	 *
	 * @since 1.0
	 */
	public function add_filters() {}

	/**
	 * Set default CSS selector of each control with 'css' property
	 *
	 * To target specific element child tag (such as 'a' in 'button' etc.)
	 * Avoids having to set CSS selector manually for each element control.
	 *
	 * @since 1.0
	 */
	public function set_css_selector( $custom_css_selector ) {
		foreach ( $this->controls as $key => $value ) {
			if ( isset( $this->controls[ $key ]['css'] ) && is_array( $this->controls[ $key ]['css'] ) ) {
				foreach ( $this->controls[ $key ]['css'] as $index => $value ) {
					if ( ! isset( $this->controls[ $key ]['css'][ $index ]['selector'] ) ) {
						$this->controls[ $key ]['css'][ $index ]['selector'] = $custom_css_selector;
					}
				}
			}
		}
	}

	public function get_label() {
		// Fallback: Use element name if element class has no get_label() defined
		return str_replace( '-', ' ', $this->name );
	}

	public function get_keywords() {
		return [];
	}

	/**
	 * Return element tag
	 *
	 * Default: 'div'
	 * Next:    $tag set in theme styles
	 * Last:    $tag set in element settings
	 *
	 * Custom tag: Check element 'tag' and 'customTag' settings.
	 *
	 * @since 1.4
	 */
	public function get_tag() {
		$tag = $this->tag ? $this->tag : 'div';

		// Get 'tag' from theme styles (@see element-heading.php)
		if ( ! empty( $this->theme_styles['tag'] ) ) {
			$tag = $this->theme_styles['tag'];
		}

		$settings = $this->settings;

		// Get 'tag' from element setting
		if ( ! empty( $settings['tag'] ) ) {
			if ( $settings['tag'] === 'custom' ) {
				// Return custom tag
				if ( ! empty( $settings['customTag'] ) ) {
					return $settings['customTag'];
				}
			}

			// Return settings tag
			else {
				return $settings['tag'];
			}
		}

		// Return default element tag
		return $tag;
	}

	/**
	 * Element-specific control groups
	 *
	 * @since 1.0
	 */
	public function set_control_groups() {}

	/**
	 * Element-specific controls
	 *
	 * @since 1.0
	 */
	public function set_controls() {}

	/**
	 * Control groups used by all elements under 'style' tab
	 *
	 * @since 1.0
	 */
	public function set_common_control_groups() {
		$this->control_groups['_layout'] = [
			'title' => esc_html__( 'Layout', 'bricks' ),
			'tab'   => 'style',
		];

		$this->control_groups['_typography'] = [
			'title' => esc_html__( 'Typography', 'bricks' ),
			'tab'   => 'style',
		];

		$this->control_groups['_background'] = [
			'title' => esc_html__( 'Background', 'bricks' ),
			'tab'   => 'style',
		];

		$this->control_groups['_border'] = [
			'title' => esc_html__( 'Border / Box Shadow', 'bricks' ),
			'tab'   => 'style',
		];

		$this->control_groups['_gradient'] = [
			'title' => esc_html__( 'Gradient / Overlay', 'bricks' ),
			'tab'   => 'style',
		];

		if ( $this->is_layout_element() ) {
			$this->control_groups['_shapes'] = [
				'title' => esc_html__( 'Shape Dividers', 'bricks' ),
				'tab'   => 'style',
			];
		}

		$this->control_groups['_transform'] = [
			'title' => esc_html__( 'Transform', 'bricks' ),
			'tab'   => 'style',
		];

		$this->control_groups['_css'] = [
			'title' => 'CSS',
			'tab'   => 'style',
		];

		$this->control_groups['_attributes'] = [
			'title' => esc_html__( 'Attributes', 'bricks' ),
			'tab'   => 'style',
		];
	}

	/**
	 * Controls used by all elements under 'style' tab
	 *
	 * @since 1.0
	 */
	public function set_controls_before() {
		// For pseudo-elements like :before & :after (@since 1.3.5)
		$this->controls['_content'] = [
			'tab'    => 'style',
			'label'  => esc_html__( 'Content', 'bricks' ),
			'type'   => 'text',
			'hidden' => true,
			'css'    => [
				[
					'property' => 'content',
					'value'    => '"%s"', // Surround content value with double quotes
				]
			],
		];

		// LAYOUT

		// Spacing

		$this->controls['_spacingSeparator'] = [
			'tab'   => 'style',
			'group' => '_layout',
			'label' => esc_html__( 'Spacing', 'bricks' ),
			'type'  => 'separator',
		];

		$this->controls['_margin'] = [
			'tab'   => 'style',
			'group' => '_layout',
			'label' => esc_html__( 'Margin', 'bricks' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'margin',
					'selector' => '',
				]
			],
		];

		$this->controls['_padding'] = [
			'tab'   => 'style',
			'group' => '_layout',
			'label' => esc_html__( 'Padding', 'bricks' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'padding',
				]
			],
		];

		// Sizing: (width, height)

		$this->controls['_sizingSeparator'] = [
			'tab'   => 'style',
			'group' => '_layout',
			'label' => esc_html__( 'Sizing', 'bricks' ),
			'type'  => 'separator',
		];

		$this->controls['_width'] = [
			'tab'   => 'style',
			'group' => '_layout',
			'label' => esc_html__( 'Width', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'css'   => [
				[
					'property' => 'width',
					'selector' => '',
				],
			],
		];

		$this->controls['_widthMin'] = [
			'tab'   => 'style',
			'group' => '_layout',
			'label' => esc_html__( 'Min. width', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'css'   => [
				[
					'property' => 'min-width',
					'selector' => '',
				],
			],
		];

		/**
		 * Use max-width: 100% by default for all elements
		 * Avoid horizontal scrollbar when setting 'width' instead of 'max-width'.
		 */
		$this->controls['_widthMax'] = [
			'tab'   => 'style',
			'group' => '_layout',
			'label' => esc_html__( 'Max. width', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'css'   => [
				[
					'property' => 'max-width',
					'selector' => '',
				],
			],
			// 'placeholder' => '100%', // Outcommented (@since 1.8.2)
		];

		$this->controls['_height'] = [
			'tab'   => 'style',
			'group' => '_layout',
			'label' => esc_html__( 'Height', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'css'   => [
				[
					'property' => 'height',
				],
			],
			'info'  => __( 'Set to "100vh" for full height.', 'bricks' ),
		];

		$this->controls['_heightMin'] = [
			'tab'   => 'style',
			'group' => '_layout',
			'label' => esc_html__( 'Min. height', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'css'   => [
				[
					'property' => 'min-height',
				],
			],
		];

		$this->controls['_heightMax'] = [
			'tab'   => 'style',
			'group' => '_layout',
			'label' => esc_html__( 'Max. height', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'css'   => [
				[
					'property' => 'max-height',
				],
			],
		];

		// aspect-ratio control (@since 1.9)
		$this->controls['_aspectRatio'] = [
			'tab'         => 'style',
			'group'       => '_layout',
			'label'       => esc_html__( 'Aspect ratio', 'bricks' ),
			'type'        => 'text',
			'inline'      => true,
			'small'       => true,
			'dd'          => false,
			'placeholder' => '',
			'css'         => [
				[
					'property' => 'aspect-ratio',
				],
			],
		];

		// POSITIONING

		$this->controls['_positionSeparator'] = [
			'tab'   => 'style',
			'group' => '_layout',
			'label' => esc_html__( 'Positioning', 'bricks' ),
			'type'  => 'separator',
		];

		$this->controls['_position'] = [
			'tab'     => 'style',
			'group'   => '_layout',
			'label'   => esc_html__( 'Position', 'bricks' ),
			'type'    => 'select',
			'options' => Setup::$control_options['position'],
			'css'     => [
				[
					'property' => 'position',
					'selector' => '',
				],
			],
			'inline'  => true,
		];

		$this->controls['_positionInfo'] = [
			'type'     => 'info',
			'content'  => esc_html__( 'Set "Top" value to make this element "sticky".', 'bricks' ),
			'tab'      => 'style',
			'group'    => '_layout',
			'required' => [ '_position', '=', 'sticky' ],
		];

		$this->controls['_top'] = [
			'tab'   => 'style',
			'group' => '_layout',
			'label' => esc_html__( 'Top', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'css'   => [
				[
					'property' => 'top',
					'selector' => '',
				],
			],
		];

		$this->controls['_right'] = [
			'tab'   => 'style',
			'group' => '_layout',
			'label' => esc_html__( 'Right', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'css'   => [
				[
					'property' => 'right',
					'selector' => '',
				],
			],
		];

		$this->controls['_bottom'] = [
			'tab'   => 'style',
			'group' => '_layout',
			'label' => esc_html__( 'Bottom', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'css'   => [
				[
					'property' => 'bottom',
					'selector' => '',
				],
			],
		];

		$this->controls['_left'] = [
			'tab'   => 'style',
			'group' => '_layout',
			'label' => esc_html__( 'Left', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'css'   => [
				[
					'property' => 'left',
					'selector' => '',
				],
			],
		];

		$this->controls['_zIndex'] = [
			'tab'         => 'style',
			'group'       => '_layout',
			'label'       => esc_html__( 'Z-index', 'bricks' ),
			'type'        => 'number',
			'css'         => [
				[
					'property' => 'z-index',
					'selector' => '',
				],
			],
			'min'         => -999,
			'placeholder' => 0,
		];

		if ( ! $this->is_layout_element() ) {
			$this->controls['_order'] = [
				'tab'         => 'style',
				'group'       => '_layout',
				'label'       => esc_html__( 'Order', 'bricks' ),
				'type'        => 'number',
				'css'         => [
					[
						'selector' => '',
						'property' => 'order',
					],
				],
				'min'         => -999,
				'placeholder' => 0,
			];
		}

		/**
		 * Scroll snap
		 *
		 * https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_Scroll_Snap
		 *
		 * @since 1.9.3
		 */
		if ( ! isset( Settings::$controls['page'] ) ) {
			Settings::set_controls();
		}

		$page_settings         = Settings::$controls['page'] ?? [];
		$page_setting_controls = $page_settings['controls'] ?? [];

		// Use page settings to set scroll snap controls
		foreach ( $page_setting_controls as $key => $value ) {
			if ( strpos( $key, 'scrollSnap' ) === 0 && $key !== 'scrollSnapSelector' ) {
				$key                             = "_$key";
				$this->controls[ $key ]          = $value;
				$this->controls[ $key ]['group'] = '_layout';

				if ( $key === '_scrollSnapType' ) {
					if ( ! empty( $this->controls[ $key ]['options'] ) ) {
						$this->controls[ $key ]['options']['x mandatory'] = 'Mandatory (' . esc_html__( 'x-axis', 'bricks' ) . ')';
						$this->controls[ $key ]['options']['x proximity'] = 'Proximity (' . esc_html__( 'x-axis', 'bricks' ) . ')';
					}

					$this->controls[ $key ]['css'] = [
						[
							'property' => 'scroll-snap-type',
						]
					];
				}

				if ( $key === '_scrollSnapAlign' && isset( $this->controls[ $key ]['placeholder'] ) ) {
					unset( $this->controls[ $key ]['placeholder'] );
				}

				// Remove CSS 'selector' property
				if ( isset( $this->controls[ $key ]['css'] ) ) {
					foreach ( $this->controls[ $key ]['css'] as $index => $value ) {
						if ( isset( $this->controls[ $key ]['css'][ $index ]['selector'] ) ) {
							unset( $this->controls[ $key ]['css'][ $index ]['selector'] );
						}
					}
				}
			}
		}

		// Misc

		$this->controls['_miscSeparator'] = [
			'tab'   => 'style',
			'group' => '_layout',
			'label' => esc_html__( 'Misc', 'bricks' ),
			'type'  => 'separator',
		];

		if ( ! $this->is_layout_element() ) {
			$this->controls['_display'] = [
				'tab'       => 'style',
				'group'     => '_layout',
				'label'     => esc_html__( 'Display', 'bricks' ),
				'type'      => 'select',
				'options'   => [
					'flex'         => 'flex',
					'inline-flex'  => 'inline-flex',
					'block'        => 'block',
					'inline-block' => 'inline-block',
					'inline'       => 'inline',
					'none'         => 'none',
				],
				'add'       => true,
				'inline'    => true,
				'lowercase' => true,
				'css'       => [
					[
						'selector' => '',
						'property' => 'display',
					],
					/**
					 * Use 'required' property to add CSS rule if display is set to 'grid'
					 *
					 * @prev 1.7.2: Used .brx-grid class on nestable to set align-items to initial.
					 *
					 * @since 1.7.2
					 */
					[
						'selector' => '',
						'property' => 'align-items',
						'value'    => 'initial',
						'required' => 'grid',
					],
				],
			];
		}

		$this->controls['_visibility'] = [
			'tab'     => 'style',
			'group'   => '_layout',
			'label'   => esc_html__( 'Visibility', 'bricks' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => [
				'visible'  => 'visible',
				'hidden'   => 'hidden',
				'collapse' => 'collapse',
			],
			'css'     => [
				[
					'property' => 'visibility',
				]
			],
		];

		$this->controls['_overflow'] = [
			'tab'            => 'style',
			'group'          => '_layout',
			'label'          => esc_html__( 'Overflow', 'bricks' ),
			'type'           => 'text',
			'css'            => [
				[
					'property' => 'overflow',
				]
			],
			'inline'         => true,
			'hasDynamicData' => false,
			'placeholder'    => 'visible',
		];

		$this->controls['_opacity'] = [
			'tab'         => 'style',
			'group'       => '_layout',
			'label'       => esc_html__( 'Opacity', 'bricks' ),
			'type'        => 'number',
			'step'        => '.01',
			'min'         => '0',
			'max'         => '1',
			'large'       => true,
			'placeholder' => 1,
			'css'         => [
				[
					'property' => 'opacity',
				]
			],
		];

		$this->controls['_cursor'] = [
			'tab'         => 'style',
			'group'       => '_layout',
			'label'       => esc_html__( 'Cursor', 'bricks' ),
			'type'        => 'select',
			'options'     => [
				'generalGroupTitle'   => esc_html__( 'General', 'bricks' ),
				'auto'                => 'auto',
				'default'             => 'default',
				'none'                => 'none',

				'linkGroupTitle'      => esc_html__( 'Link & status', 'bricks' ),
				'pointer'             => 'pointer',
				'context-menu'        => 'context-menu',
				'help'                => 'help',
				'progress'            => 'progress',
				'wait'                => 'wait',

				'selectionGroupTitle' => esc_html__( 'Selection', 'bricks' ),
				'cell'                => 'cell',
				'crosshair'           => 'crosshair',
				'text'                => 'text',
				'vertical-text'       => 'vertical-text',

				'dndGroupTitle'       => esc_html__( 'Drag & drop', 'bricks' ),
				'alias'               => 'alias',
				'copy'                => 'copy',
				'move'                => 'move',
				'no-drop'             => 'no-drop',
				'not-allowed'         => 'not-allowed',
				'grab'                => 'grab',
				'grabbing'            => 'grabbing',

				'zoomGroupTitle'      => esc_html__( 'Zoom', 'bricks' ),
				'zoom-in'             => 'zoom-in',
				'zoom-out'            => 'zoom-out',

				'scrollGroupTitle'    => esc_html__( 'Resize', 'bricks' ),
				'col-resize'          => 'col-resize',
				'row-resize'          => 'row-resize',
				'n-resize'            => 'n-resize',
				'e-resize'            => 'e-resize',
				's-resize'            => 's-resize',
				'w-resize'            => 'w-resize',
				'ne-resize'           => 'ne-resize',
				'nw-resize'           => 'nw-resize',
				'se-resize'           => 'se-resize',
				'sw-resize'           => 'sw-resize',
				'ew-resize'           => 'ew-resize',
				'ns-resize'           => 'ns-resize',
				'nesw-resize'         => 'nesw-resize',
				'nwse-resize'         => 'nwse-resize',
				'all-scroll'          => 'all-scroll',
			],
			'css'         => [
				[
					'selector' => '',
					'property' => 'cursor',
				]
			],
			'inline'      => true,
			'placeholder' => 'auto',
		];

		// isolation select control (@since 1.9)
		$this->controls['_isolation'] = [
			'tab'     => 'style',
			'group'   => '_layout',
			'label'   => esc_html__( 'Isolation', 'bricks' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => [
				'auto'    => 'auto',
				'isolate' => 'isolate',
			],
			'css'     => [
				[
					'property' => 'isolation',
				]
			],
		];

		// mix-blend-mode control (@since 1.9)
		$this->controls['_mixBlendMode'] = [
			'tab'     => 'style',
			'group'   => '_layout',
			'label'   => 'Mix blend mode', // Don't localize
			'type'    => 'select',
			'inline'  => true,
			'options' => Setup::$control_options['blendMode'],
			'css'     => [
				[
					'property' => 'mix-blend-mode',
				]
			],
		];

		$this->controls['_pointerEvents'] = [
			'tab'    => 'style',
			'group'  => '_layout',
			'label'  => 'Pointer events', // Don't localize
			'type'   => 'text',
			'inline' => true,
			'dd'     => false,
			'css'    => [
				[
					'property' => 'pointer-events',
				]
			],
		];

		// Flex controls (for non-layout elements: no section, container, div)
		if ( ! $this->is_layout_element() ) {
			$this->controls['_flexSeparator'] = [
				'tab'   => 'style',
				'group' => '_layout',
				'label' => esc_html__( 'Flex', 'bricks' ),
				'type'  => 'separator',
			];

			$this->controls['_flexDirection'] = [
				'tab'      => 'style',
				'group'    => '_layout',
				'label'    => esc_html__( 'Direction', 'bricks' ),
				'tooltip'  => [
					'content'  => 'flex-direction',
					'position' => 'top-left',
				],
				'type'     => 'direction',
				'css'      => [
					[
						'selector' => '',
						'property' => 'flex-direction',
					],
				],
				'inline'   => true,
				'rerender' => true,
				'required' => [ '_display', '=', 'flex' ],
			];

			$this->controls['_alignSelf'] = [
				'tab'     => 'style',
				'group'   => '_layout',
				'label'   => esc_html__( 'Align self', 'bricks' ),
				'type'    => 'align-items',
				'tooltip' => [
					'content'  => 'align-self',
					'position' => 'top-left',
				],
				'css'     => [
					[
						'selector' => '',
						'property' => 'align-self',
					],
				],
			];

			$this->controls['_justifyContent'] = [
				'tab'      => 'style',
				'group'    => '_layout',
				'label'    => esc_html__( 'Align main axis', 'bricks' ),
				'tooltip'  => [
					'content'  => 'justify-content',
					'position' => 'top-left',
				],
				'type'     => 'justify-content',
				'css'      => [
					[
						'selector' => '',
						'property' => 'justify-content',
					],
				],
				'required' => [ '_display', '=', [ 'flex', 'inline-flex' ] ],
			];

			$this->controls['_alignItems'] = [
				'tab'      => 'style',
				'group'    => '_layout',
				'label'    => esc_html__( 'Align cross axis', 'bricks' ),
				'tooltip'  => [
					'content'  => 'align-items',
					'position' => 'top-left',
				],
				'type'     => 'align-items',
				'css'      => [
					[
						'selector' => '',
						'property' => 'align-items',
					],
				],
				'required' => [ '_display', '=', [ 'flex', 'inline-flex' ] ],
			];

			$this->controls['_gap'] = [
				'tab'      => 'style',
				'group'    => '_layout',
				'label'    => esc_html__( 'Gap', 'bricks' ),
				'type'     => 'number',
				'units'    => true,
				'css'      => [
					[
						'property' => 'gap',
						'selector' => '',
					],
				],
				'required' => [ '_display', '=', [ 'flex', 'inline-flex' ] ],
			];

			$this->controls['_flexGrow'] = [
				'tab'         => 'style',
				'group'       => '_layout',
				'label'       => esc_html__( 'Flex grow', 'bricks' ),
				'type'        => 'number',
				'tooltip'     => [
					'content'  => 'flex-grow',
					'position' => 'top-left',
				],
				'css'         => [
					[
						'selector' => '',
						'property' => 'flex-grow',
					],
				],
				'min'         => 0,
				'placeholder' => 0,
			];

			$this->controls['_flexShrink'] = [
				'tab'         => 'style',
				'group'       => '_layout',
				'label'       => esc_html__( 'Flex shrink', 'bricks' ),
				'type'        => 'number',
				'tooltip'     => [
					'content'  => 'flex-shrink',
					'position' => 'top-left',
				],
				'css'         => [
					[
						'selector' => '',
						'property' => 'flex-shrink',
					],
				],
				'min'         => 0,
				'placeholder' => 1,
			];

			$this->controls['_flexBasis'] = [
				'tab'            => 'style',
				'group'          => '_layout',
				'label'          => esc_html__( 'Flex basis', 'bricks' ),
				'type'           => 'text',
				'tooltip'        => [
					'content'  => 'flex-basis',
					'position' => 'top-left',
				],
				'css'            => [
					[
						'selector' => '',
						'property' => 'flex-basis',
					],
				],
				'inline'         => true,
				'small'          => true,
				'hasDynamicData' => false,
				'placeholder'    => 'auto',
			];
		}

		// TYPOGRAPHY

		$this->controls['_typography'] = [
			'tab'   => 'style',
			'group' => '_typography',
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
				],
			],
			'popup' => false,
		];

		// BACKGROUND

		$this->controls['_background'] = [
			'tab'   => 'style',
			'group' => '_background',
			'type'  => 'background',
			'css'   => [
				[
					'property' => 'background',
				]
			],
			'popup' => false,
		];

		// SHAPES
		if ( $this->is_layout_element() ) {
			$this->controls['_shapeDividers'] = [
				'tab'           => 'style',
				'group'         => '_shapes',
				'placeholder'   => esc_html__( 'Shape', 'bricks' ),
				'type'          => 'repeater',
				'pasteStyles'   => true,
				'titleProperty' => 'shape',
				'fields'        => [
					'shape'           => [
						'type'        => 'select',
						'searchable'  => true,
						'options'     => [
							'custom'                   => esc_html__( 'Custom', 'bricks' ),
							'cloud'                    => esc_html__( 'Cloud', 'bricks' ),
							'drops'                    => esc_html__( 'Drops', 'bricks' ),
							'grid-round'               => esc_html__( 'Grid (Round)', 'bricks' ),
							'grid-square'              => esc_html__( 'Grid (Square)', 'bricks' ),
							'round'                    => esc_html__( 'Round', 'bricks' ),
							'square'                   => esc_html__( 'Square', 'bricks' ),
							'stroke'                   => esc_html__( 'Stroke', 'bricks' ),
							'stroke-2'                 => esc_html__( 'Stroke #2', 'bricks' ),
							'tilt'                     => esc_html__( 'Tilt', 'bricks' ),
							'triangle'                 => esc_html__( 'Triangle', 'bricks' ),
							'triangle-concave'         => esc_html__( 'Triangle concave', 'bricks' ),
							'triangle-convex'          => esc_html__( 'Triangle convex', 'bricks' ),
							'triangle-double'          => esc_html__( 'Triangle double', 'bricks' ),
							'wave'                     => esc_html__( 'Wave', 'bricks' ),
							'waves'                    => esc_html__( 'Waves', 'bricks' ),
							'wave-brush'               => esc_html__( 'Wave brush', 'bricks' ),
							'zigzag'                   => esc_html__( 'Zigzag', 'bricks' ),

							'vertical-cloud'           => esc_html__( 'Vertical - Cloud', 'bricks' ),
							'vertical-drops'           => esc_html__( 'Vertical - Drops', 'bricks' ),
							'vertical-pixels'          => esc_html__( 'Vertical - Pixels', 'bricks' ),
							'vertical-stroke'          => esc_html__( 'Vertical - Stroke', 'bricks' ),
							'vertical-stroke-2'        => esc_html__( 'Vertical - Stroke #2', 'bricks' ),
							'vertical-tilt'            => esc_html__( 'Vertical - Tilt', 'bricks' ),
							'vertical-triangle'        => esc_html__( 'Vertical - Triangle', 'bricks' ),
							'vertical-triangle-double' => esc_html__( 'Vertical - Triangle double', 'bricks' ),
							'vertical-wave'            => esc_html__( 'Vertical - Wave', 'bricks' ),
							'vertical-waves'           => esc_html__( 'Vertical - Waves', 'bricks' ),
							'vertical-wave-brush'      => esc_html__( 'Vertical - Wave brush', 'bricks' ),
							'vertical-zigzag'          => esc_html__( 'Vertical - Zigzag', 'bricks' ),

							// 'custom' => esc_html__( 'Custom', 'bricks' ), // MAYBE: add custom SVG control
						],
						'placeholder' => esc_html__( 'Select shape', 'bricks' ),
					],

					'shapeCustom'     => [
						'label'       => esc_html__( 'Custom shape', 'bricks' ) . ' (SVG)',
						'type'        => 'svg',
						'description' => sprintf(
							// translators: %s: link to MDN
							esc_html__( 'If the shape doesn\'t take up all available space add %s to the "svg" tag.', 'bricks' ),
							'<a href="https://developer.mozilla.org/en-US/docs/Web/SVG/Attribute/preserveAspectRatio" target="_blank">preserveAspectRatio="none"</a>'
						),
						'required'    => [ 'shape', '=', 'custom' ],
					],

					'fill'            => [
						'label'    => esc_html__( 'Fill color', 'bricks' ),
						'type'     => 'color',
						'required' => [ 'shape', '!=', '' ],
					],

					'front'           => [
						'label'    => esc_html__( 'Front', 'bricks' ),
						'type'     => 'checkbox',
						'inline'   => true,
						'required' => [ 'shape', '!=', '' ],
					],

					'flipHorizontal'  => [
						'label'    => esc_html__( 'Flip', 'bricks' ) . ' ' . esc_html__( 'x-axis', 'bricks' ),
						'type'     => 'checkbox',
						'inline'   => true,
						'small'    => true,
						'required' => [ 'shape', '!=', '' ],
					],

					'flipVertical'    => [
						'label'    => esc_html__( 'Flip', 'bricks' ) . ' ' . esc_html__( 'y-axis', 'bricks' ),
						'type'     => 'checkbox',
						'inline'   => true,
						'small'    => true,
						'required' => [ 'shape', '!=', '' ],
					],

					'overflow'        => [
						'label'    => esc_html__( 'Overflow', 'bricks' ),
						'type'     => 'checkbox',
						'inline'   => true,
						'small'    => true,
						'required' => [ 'shape', '!=', '' ],
					],

					'height'          => [
						'label'    => esc_html__( 'Height', 'bricks' ),
						'type'     => 'number',
						'units'    => true,
						'required' => [ 'shape', '!=', '' ],
					],

					'width'           => [
						'label'    => esc_html__( 'Width', 'bricks' ),
						'type'     => 'number',
						'units'    => true,
						'required' => [ 'shape', '!=', '' ],
					],

					'rotate'          => [
						'label'    => esc_html__( 'Rotate', 'bricks' ) . ' °',
						'type'     => 'number',
						'unit'     => 'deg',
						'required' => [ 'shape', '!=', '' ],
					],

					'horizontalAlign' => [
						'label'       => esc_html__( 'Horizontal align', 'bricks' ),
						'type'        => 'align-items',
						'exclude'     => 'stretch',
						'inline'      => true,
						'placeholder' => esc_html__( 'Select', 'bricks' ),
						'required'    => [ 'shape', '!=', '' ],
					],

					'verticalAlign'   => [
						'label'       => esc_html__( 'Vertical align', 'bricks' ),
						'type'        => 'justify-content',
						'exclude'     => 'space',
						'inline'      => true,
						'placeholder' => esc_html__( 'Select', 'bricks' ),
						'required'    => [ 'shape', '!=', '' ],
					],

					'top'             => [
						'label'    => esc_html__( 'Top', 'bricks' ),
						'type'     => 'number',
						'units'    => true,
						'required' => [ 'shape', '!=', '' ],
					],

					'right'           => [
						'label'    => esc_html__( 'Right', 'bricks' ),
						'type'     => 'number',
						'units'    => true,
						'required' => [ 'shape', '!=', '' ],
					],

					'bottom'          => [
						'label'    => esc_html__( 'Bottom', 'bricks' ),
						'type'     => 'number',
						'units'    => true,
						'required' => [ 'shape', '!=', '' ],
					],

					'left'            => [
						'label'    => esc_html__( 'Left', 'bricks' ),
						'type'     => 'number',
						'units'    => true,
						'required' => [ 'shape', '!=', '' ],
					],

				],
			];
		}

		// Exclude background video control from non-layout elements
		if ( ! $this->is_layout_element() ) {
			$this->controls['_background']['exclude'] = 'video';
		}

		// GRADIENT

		$this->controls['_gradient'] = [
			'tab'   => 'style',
			'group' => '_gradient',
			'type'  => 'gradient',
			'css'   => [
				[
					'property' => 'background-image',
				],
			],
		];

		// BORDER

		$this->controls['_border'] = [
			'tab'   => 'style',
			'group' => '_border',
			'type'  => 'border',
			'label' => esc_html__( 'Border', 'bricks' ),
			'css'   => [
				[
					'property' => 'border',
				],
			],
		];

		$this->controls['_boxShadow'] = [
			'tab'   => 'style',
			'group' => '_border',
			'label' => esc_html__( 'Box shadow', 'bricks' ),
			'type'  => 'box-shadow',
			'css'   => [
				[
					'property' => 'box-shadow',
				],
			],
		];

		// TRANSFORM

		$this->controls['_transform'] = [
			'tab'         => 'style',
			'group'       => '_transform',
			'type'        => 'transform',
			'label'       => esc_html__( 'Transform', 'bricks' ),
			'css'         => [
				[
					'property' => 'transform',
				],
			],
			'inline'      => true,
			'small'       => true,
			'description' => sprintf(
				'<a href="https://developer.mozilla.org/en-US/docs/Web/CSS/transform" target="_blank" rel="noopener">%s</a>',
				esc_html__( 'Learn more about CSS transform', 'bricks' )
			),
		];

		$this->controls['_transformOrigin'] = [
			'tab'            => 'style',
			'group'          => '_transform',
			'type'           => 'text',
			'label'          => esc_html__( 'Transform origin', 'bricks' ),
			'css'            => [
				[
					'property' => 'transform-origin',
				],
			],
			'inline'         => true,
			'hasDynamicData' => false,
			'description'    => sprintf(
				'<a href="https://developer.mozilla.org/en-US/docs/Web/CSS/transform-origin" target="_blank" rel="noopener">%s</a>',
				esc_html__( 'Learn more about CSS transform-origin', 'bricks' )
			),
			'placeholder'    => 'center',
		];

		// CSS

		$this->controls['_cssFilters'] = [
			'tab'           => 'style',
			'group'         => '_css',
			'label'         => esc_html__( 'CSS Filters', 'bricks' ),
			'titleProperty' => 'type',
			'type'          => 'filters',
			'inline'        => true,
			'small'         => true,
			'css'           => [
				[
					'property' => 'filter',
				],
			],
			'description'   => sprintf(
				'<a target="_blank" href="https://developer.mozilla.org/en-US/docs/Web/CSS/filter#Syntax">%s</a>',
				esc_html__( 'Learn more about CSS filters', 'bricks' )
			),
		];

		$this->controls['_cssTransition'] = [
			'tab'            => 'style',
			'group'          => '_css',
			'label'          => esc_html__( 'Transition', 'bricks' ),
			'class'          => 'ltr',
			'css'            => [
				[
					'property' => 'transition',
					'selector' => isset( $this->css_selector ) ? $this->css_selector : '',
				],
			],
			'type'           => 'text',
			'hasDynamicData' => false,
			'description'    => sprintf(
				'<a href="https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_Transitions/Using_CSS_transitions" target="_blank">%s</a>',
				esc_html__( 'Learn more about CSS transitions', 'bricks' )
			),
		];

		$this->controls['_cssCustom'] = [
			'tab'         => 'style',
			'group'       => '_css',
			'label'       => esc_html__( 'Custom CSS', 'bricks' ),
			'type'        => 'code',
			'pasteStyles' => true,
			'css'         => [], // NOTE: Undocumented (@since 1.5.1) return true instead of array with 'property' and 'selector' data to output as plain CSS
			'description' => esc_html__( 'Use "%root%" to target the element wrapper.', 'bricks' ) . ' ' . esc_html__( 'Add "%root%" via keyboard shortcut "r + TAB".', 'bricks' ),
			'placeholder' => "%root% {\n  color: firebrick;\n}",
		];

		$this->controls['_cssClasses'] = [
			'tab'            => 'style',
			'group'          => '_css',
			'label'          => esc_html__( 'CSS classes', 'bricks' ),
			'class'          => 'ltr',
			'type'           => 'text',
			'hasDynamicData' => false,
			'description'    => esc_html__( 'Separated by space. Without class dot.', 'bricks' ),
		];

		$this->controls['_cssId'] = [
			'tab'            => 'style',
			'group'          => '_css',
			'label'          => esc_html__( 'CSS ID', 'bricks' ),
			'class'          => 'ltr',
			'type'           => 'text',
			'hasDynamicData' => true, // NOTE: True @since 1.7.1
			'description'    => esc_html__( 'No spaces. No pound (#) sign.', 'bricks' ),
		];

		// ATTRIBUTES

		$this->controls['_attributes'] = [
			'tab'           => 'style',
			'group'         => '_attributes',
			'placeholder'   => esc_html__( 'Attributes', 'bricks' ),
			'type'          => 'repeater',
			'titleProperty' => 'name',
			'fields'        => [
				'name'  => [
					'label'    => esc_html__( 'Name', 'bricks' ),
					'type'     => 'text',
					'rerender' => false,
				],
				'value' => [
					'label'    => esc_html__( 'Value', 'bricks' ),
					'type'     => 'text',
					'rerender' => false,
				],
			],
		];

		$this->controls['infoAttributes'] = [
			'tab'     => 'style',
			'group'   => '_attributes',
			// translators: %s: link to article
			'content' => sprintf( esc_html__( '%s will be added to the most relevant HTML node.', 'bricks' ), Helpers::article_link( 'custom-attributes', esc_html__( 'Custom attributes', 'bricks' ) ) ),
			'type'    => 'info',
		];
	}

	/**
	 * Controls used by all elements under 'style' tab
	 *
	 * @since 1.0
	 */
	public function set_controls_after() {
		// NOTE: Entry animations are deprecated @since 1.6 in favor of element interactions: Run new converter option!
		$this->controls['_animationSeparator'] = [
			'tab'        => 'style',
			'group'      => '_layout',
			'label'      => esc_html__( 'Animation', 'bricks' ),
			'type'       => 'separator',
			'deprecated' => true,
		];

		$this->controls['_animationInfo'] = [
			'tab'      => 'style',
			'group'    => '_layout',
			'content'  => 'The "Entry animation" settings below are deprecated since 1.6. Please convert them under "Bricks > Settings > General > Converter", and use the new <a href="https://academy.bricksbuilder.io/article/interactions/" target="_blank">Interactions</a> for all new animations.',
			'required' => [ '_animation', '!=', '' ],
			'type'     => 'info',
		];

		$this->controls['_animation'] = [
			'tab'         => 'style',
			'group'       => '_layout',
			'label'       => esc_html__( 'Entry animation', 'bricks' ),
			'type'        => 'select',
			'searchable'  => true,
			'options'     => Setup::$control_options['animationTypes'],
			'inline'      => true,
			'placeholder' => esc_html__( 'None', 'bricks' ),
			'deprecated'  => true,
		];

		$this->controls['_animationDuration'] = [
			'tab'            => 'style',
			'group'          => '_layout',
			'label'          => esc_html__( 'Animation duration', 'bricks' ),
			'type'           => 'select',
			'searchable'     => true,
			'options'        => [
				'very-slow' => esc_html__( 'Very slow', 'bricks' ),
				'slow'      => esc_html__( 'Slow', 'bricks' ),
				'normal'    => esc_html__( 'Normal', 'bricks' ),
				'fast'      => esc_html__( 'Fast', 'bricks' ),
				'very-fast' => esc_html__( 'Very fast', 'bricks' ),
				'custom'    => esc_html__( 'Custom', 'bricks' ),
			],
			'inline'         => true,
			'hasDynamicData' => false,
			'placeholder'    => esc_html__( 'Normal', 'bricks' ) . ' (1s)',
			'required'       => [ '_animation', '!=', '' ],
			'deprecated'     => true,
		];

		$this->controls['_animationDurationCustom'] = [
			'tab'            => 'style',
			'group'          => '_layout',
			'label'          => esc_html__( 'Animation duration', 'bricks' ) . ' (' . esc_html__( 'Custom', 'bricks' ) . ')',
			'type'           => 'text',
			'inline'         => true,
			'hasDynamicData' => false,
			'info'           => '500ms | 1s',
			'required'       => [ '_animationDuration', '=', 'custom' ],
			'deprecated'     => true,
		];

		$this->controls['_animationDelay'] = [
			'tab'         => 'style',
			'group'       => '_layout',
			'label'       => esc_html__( 'Animation delay', 'bricks' ),
			'type'        => 'text',
			'inline'      => true,
			'placeholder' => 0,
			'info'        => '500ms | -2.5s',
			'required'    => [ '_animation', '!=', '' ],
			'deprecated'  => true,
		];
	}

	/**
	 * Get default data
	 *
	 * @since 1.0
	 */
	public function get_default_data() {
		return [
			'label'         => $this->label,
			'name'          => $this->name,
			'controls'      => $this->controls,
			'controlGroups' => $this->control_groups,
		];
	}

	/**
	 * Builder: Element placeholder HTML
	 *
	 * @since 1.0
	 */
	final public function render_element_placeholder( $data = [], $type = 'info' ) {
		if ( $this->is_frontend ) {
			return;
		}

		if ( ! isset( $data['icon-class'] ) ) {
			$data['icon-class'] = $this->icon;
		}

		// For custom context menu
		$data['id'] = $this->id;

		echo Helpers::get_element_placeholder( $data, $type );
	}

	/**
	 * Return element attribute: id
	 *
	 * @since 1.5
	 *
	 * @since 1.7.1: Parse dynamic data for _cssId (same for _cssClasses)
	 */
	public function get_element_attribute_id() {
		return Helpers::get_element_attribute_id( $this->id, $this->settings );
	}

	/**
	 * Set element root attributes (element ID, classes, etc.)
	 *
	 * @since 1.4
	 */
	public function set_root_attributes() {
		$element      = $this->element;
		$nestable     = $this->nestable;
		$element_id   = $this->id;
		$element_name = $this->name;
		$settings     = ! empty( $element['settings'] ) && is_array( $element['settings'] ) ? $element['settings'] : [];
		$attributes   = [];

		$has_css_settings = self::has_css_settings( $settings );

		// Parent element is 'slider-nested' & 'pagination' is enabled: Ensure slide 'id' is added (needed for 'aria-controls' a11y)
		if ( $nestable ) {
			$parent_id = ! empty( $element['parent'] ) ? $element['parent'] : false;

			if ( $parent_id ) {
				$parent_element = ! empty( Frontend::$elements[ $parent_id ] ) ? Frontend::$elements[ $parent_id ] : false;

				if ( $parent_element ) {
					if (
						isset( $parent_element['name'] ) &&
						$parent_element['name'] === 'slider-nested' &&
						isset( $parent_element['settings']['pagination'] )
					) {
						$has_css_settings = true;
					}
				}
			}
		}

		/**
		 * STEP: Add element 'id' attribute
		 *
		 * IF:
		 * - Custom 'id' set
		 *
		 * OR:
		 * - Not inside query loop
		 * - Has CSS setting
		 * - Not a global element
		 */
		$global_element_id = Helpers::get_global_element( $element, 'global' );

		if (
			! empty( $settings['_cssId'] ) ||
			( ! Query::is_looping() && $has_css_settings && ! $global_element_id ) ||
			$element_name === 'offcanvas' // Offcanvas: Always add 'id' attribute to ensure it works with 'Selector' setting of the Toggle element
		) {
			$attributes['id'] = $this->get_element_attribute_id();
		}

		// STEP: Add element classes
		$classes = [];

		// Query loop item: Use class name instead of ID as main selector (every item uses same styling rules)
		// Always add element class as loop item element can contain CSS settings.
		if ( Query::is_looping() ) {
			$classes[] = "brxe-{$element_id}";
		}

		// Global element: Use class name instead of ID as main selector (global element can occur multiple times on a page)
		if ( $global_element_id && $has_css_settings ) {
			$classes[] = "brxe-{$global_element_id}";
		}

		$classes[] = sanitize_html_class( "brxe-{$this->name}" );

		// IS CSS grid (@since 1.6.1)
		if ( ! empty( $settings['_display'] ) && $settings['_display'] === 'grid' ) {
			$classes[] = 'brx-grid';
		}

		// Element global classes
		if ( ! empty( $settings['_cssGlobalClasses'] ) ) {
			$classes = array_merge( $classes, self::get_element_global_classes( $settings['_cssGlobalClasses'] ) );
		}

		// STEP: data-loop
		if ( Query::is_looping() ) {
			// Custom element ID, transform it into a class
			if ( ! empty( $settings['_cssId'] ) ) {
				$classes[] = $this->get_element_attribute_id();
			}
		}

		/**
		 * Add link attributes (class, href, rel, target) to layout element (section, container, block, div, etc.)
		 *
		 * If HTML tag is "a" (@since 1.9)
		 *
		 * @since 1.2.1
		 */
		if ( $this->is_layout_element() && ! empty( $settings['link']['type'] ) && $this->tag === 'a' ) {
			$this->set_link_attributes( 'link', $settings['link'] );

			$container_link = isset( $this->attributes['link'] ) ? $this->attributes['link'] : [];

			foreach ( $container_link as $key => $value ) {
				if ( $key === 'class' ) {
					$classes = array_merge( $classes, $value );

					continue;
				}

				if ( is_array( $value ) && count( $value ) ) {
					$value = $value[0];
				}

				$attributes[ $key ] = $value;
			}
		}

		// Custom classes (Control group: CSS)
		if ( ! empty( $settings['_cssClasses'] ) ) {
			$classes[] = $settings['_cssClasses'];
		}

		// Custom '_hidden' classes (@since 1.5)
		if ( ! empty( $settings['_hidden']['_cssClasses'] ) ) {
			$classes[] = $settings['_hidden']['_cssClasses'];
		}

		/**
		 * Set & use 'data-script-id' attribute to init scripts with (bricksSplide, bricksSwiper, etc.)
		 *
		 * Generate & use random script ID inside query loop.
		 *
		 * @since 1.4
		 */
		if ( ! empty( $this->scripts ) ) {
			$attributes['data-script-id'] = Query::is_any_looping() ? Helpers::generate_random_id( false ) : $this->id;
		}

		// Frontend: Lazy load nestable background images (section, container, block, div, etc.)
		if ( $this->lazy_load() && $nestable ) {
			$classes[] = 'bricks-lazy-hidden';
		}

		// Parse CSS classes for dynamic data (@since 1.7.1)
		foreach ( $classes as $index => $class_name ) {
			if ( strpos( $class_name, '{' ) !== false && strpos( $class_name, '}' ) !== false ) {
				$classes[ $index ] = bricks_render_dynamic_data( $class_name );
			}
		}

		$attributes['class'] = $classes;

		/**
		 * Add custom attributes (unless element has $custom_attributes = false like "Nav Menu")
		 */
		if ( ! empty( $settings['_attributes'] ) && $this->custom_attributes === true ) {
			// Add custom attributes (overwrites existing $attributes if needed)
			$custom_attributes = $this->get_custom_attributes( $settings );

			if ( is_array( $custom_attributes ) ) {
				foreach ( $custom_attributes as $att_key => $att_val ) {
					$attributes[ $att_key ] = $att_val;
				}
			}
		}

		// NOTE: Undocumented
		$attributes = apply_filters( 'bricks/element/set_root_attributes', $attributes, $this );

		$this->attributes['_root'] = $attributes;
	}

	/**
	 * Return true if element has 'css' settings
	 *
	 * @return boolean
	 *
	 * @since 1.5
	 */
	public function has_css_settings( $settings ) {
		// Builder: Always add element 'id' & class (needed in query loop to render 2..last items)
		if ( bricks_is_builder_call() ) {
			return true;
		}

		/**
		 * Always add element 'id' for the following elements:
		 *
		 * Nav menu: to add 'mobileMenu' <style> tag to <head> which contain element 'id'
		 *
		 * @since 1.5.1
		 *
		 * @since 1.8 'nav-nested'
		 */
		if ( in_array( $this->name, [ 'nav-menu', 'nav-nested' ] ) ) {
			return true;
		}

		// Experimental element ID & class setting not enabled: Always add element ID & class
		if ( ! isset( Database::$global_settings['elementAttsAsNeeded'] ) ) {
			return true;
		}

		$has_css_settings = false;

		$element_attribute_id = $this->get_element_attribute_id();

		// STEP: Check for 'css' setting
		foreach ( $settings as $key => $value ) {
			// Remove pseudo class & breapkoint keys to get plain control
			if ( $key && strpos( $key, ':' ) ) {
				$control_key_parts = explode( ':', $key );

				// First part is plain control key
				if ( count( $control_key_parts ) > 1 ) {
					$key = $control_key_parts[0];
				}
			}

			$control = ! empty( $this->controls[ $key ] ) ? $this->controls[ $key ] : false;

			// Check for breakpoint settings
			if ( ! $control ) {
				foreach ( Breakpoints::$breakpoints as $bp ) {
					$breakpoint_key = $bp['key'];

					// Setting contains breakpoint key (e.g.: "_background:tablet_portrait")
					if ( $breakpoint_key !== 'desktop' && strpos( $key, ":$breakpoint_key" ) ) {
						$has_css_settings = true;
						break;
					}
				}
			}

			if ( ! $control ) {
				continue;
			}

			// Loop over repeater items to see if it contains any CSS settings
			if ( ! empty( $control['type'] ) && $control['type'] === 'repeater' ) {
				if ( is_array( $value ) ) {
					foreach ( $value as $repeater_item ) {
						if ( is_array( $repeater_item ) ) {
							foreach ( $repeater_item as $repeater_key => $repeater_value ) {
								$repeater_control = ! empty( $this->controls[ $key ]['fields'][ $repeater_key ] ) ? $this->controls[ $key ]['fields'][ $repeater_key ] : false;

								if ( isset( $repeater_control['css'] ) ) {
									$has_css_settings = true;
								}
							}
						}
					}
				}
			}

			// Icon has 'css' property, but only used if 'svg' option is selected
			if ( ! empty( $control['type'] ) && $control['type'] === 'icon' ) {
				// Skip icon font
				if ( ! empty( $value['icon'] ) ) {
					continue;
				}

				// Return true: Property besides 'library' and 'svg' set (e.g. height, width, etc.)
				if ( ! empty( $value['svg'] ) && count( $value['svg'] ) > 2 ) {
					$has_css_settings = true;
					break;
				}
			}

			// Is CSS control
			if ( isset( $control['css'] ) ) {
				$has_css_settings = true;
				break;
			}

			// Check for element ID use in custom CSS
			if ( $key === '_cssCustom' ) {
				if ( strpos( $value, $element_attribute_id ) !== false ) {
					$has_css_settings = true;
					break;
				}
			}
		}

		if ( $has_css_settings ) {
			return true;
		}

		// STEP: Global settings 'customCss' contain element ID
		if ( ! empty( Database::$global_settings['customCss'] ) && strpos( Database::$global_settings['customCss'], $element_attribute_id ) !== false ) {
			return true;
		}

		// STEP: Page settings 'customCss' contain element ID
		if ( ! empty( Database::$page_settings['customCss'] ) && strpos( Database::$page_settings['customCss'], $element_attribute_id ) !== false ) {
			return true;
		}

		return false;
	}

	/**
	 * Convert the global classes ids into the classes names
	 *
	 * @param array $class_ids The global classes ids.
	 *
	 * @return array
	 */
	public static function get_element_global_classes( $class_ids ) {
		$global_classes = Database::$global_data['globalClasses'];

		if ( empty( $global_classes ) || empty( $class_ids ) ) {
			return [];
		}

		$element_classes = [];

		$class_ids_names = wp_list_pluck( $global_classes, 'name', 'id' );

		foreach ( $class_ids as $class_id ) {
			if ( ! isset( $class_ids_names[ $class_id ] ) ) {
				continue;
			}

			$element_classes[] = $class_ids_names[ $class_id ];
		}

		return $element_classes;
	}

	/**
	 * Set HTML element attribute + value(s)
	 *
	 * @param string       $key         Element identifier.
	 * @param string       $attribute   Attribute to set value(s) for.
	 * @param string|array $value       Set single value (string) or values (array).
	 *
	 * @since 1.0
	 */
	public function set_attribute( $key, $attribute, $value = null ) {
		if ( is_array( $value ) ) {
			foreach ( $value as $val ) {
				$this->attributes[ $key ][ $attribute ][] = $val;
			}

			return;
		}

		if ( empty( $value ) && ! is_numeric( $value ) ) {
			$this->attributes[ $key ][ $attribute ] = '';

			return;
		}

		// Attribute with value already exists, but is not an array: Convert to array first and add new value
		if ( isset( $this->attributes[ $key ][ $attribute ] ) && ! is_array( $this->attributes[ $key ][ $attribute ] ) ) {
			$this->attributes[ $key ][ $attribute ] = [
				$this->attributes[ $key ][ $attribute ],
				$value,
			];

			return;
		}

		$this->attributes[ $key ][ $attribute ][] = $value;
	}

	/**
	 * Set link attributes
	 *
	 * Helper to set attributes for control type 'link'
	 *
	 * @since 1.0
	 *
	 * @param string $attribute_key Desired key for set_attribute.
	 * @param string $link_settings Element control type 'link' settings.
	 */
	public function set_link_attributes( $attribute_key, $link_settings ) {
		$link_type = $link_settings['type'] ?? '';

		if ( ! $link_type ) {
			return;
		}

		// Internal link
		if ( $link_type === 'internal' && isset( $link_settings['postId'] ) ) {
			$permalink = get_the_permalink( $link_settings['postId'] );

			$this->set_attribute( $attribute_key, 'href', $permalink );
		}

		// External link
		elseif ( $link_type === 'external' && isset( $link_settings['url'] ) ) {
			$this->set_attribute( $attribute_key, 'href', bricks_render_dynamic_data( $link_settings['url'], get_the_ID(), 'link' ) );
		}

		// Lightbox image or video: Set lightbox ID through 'data-pswp-id' attribute
		if ( strpos( $link_type, 'lightbox' ) !== false && isset( $link_settings['lightboxId'] ) ) {
			$this->set_attribute( $attribute_key, 'data-pswp-id', $link_settings['lightboxId'] );
		}

		/**
		 * Lightbox
		 *
		 * Photoswipe required width & height
		 *
		 * Lightbox image: Uses whatever intrinsic width & height the image has.
		 * Lightbox video: Default is 1280x720 (16:9).
		 */
		$lightbox_width  = Theme_Styles::$active_settings['general']['lightboxWidth'] ?? 1280;
		$lightbox_height = Theme_Styles::$active_settings['general']['lightboxHeight'] ?? 720;

		// Lightbox image
		if ( $link_type === 'lightboxImage' ) {
			$lightbox_image = $link_settings['lightboxImage'] ?? false;
			$image_size     = $lightbox_image['size'] ?? BRICKS_DEFAULT_IMAGE_SIZE;
			$image_url      = $lightbox_image['url'] ?? false;
			$image_id       = $lightbox_image['id'] ?? 0;
			$image          = $image_id ? wp_get_attachment_image_src( $image_id, $image_size ) : false;

			// Dynamic data lightbox image
			if ( ! empty( $lightbox_image['useDynamicData'] ) ) {
				$image = Integrations\Dynamic_Data\Providers::render_tag( $lightbox_image['useDynamicData'], get_the_ID(), 'image', [ 'size' => $image_size ] );

				if ( ! empty( $image[0] ) ) {
					$image_url = $image[0];

					// DD is image ID, not URL
					if ( is_numeric( $image[0] ) ) {
						$image = wp_get_attachment_image_src( $image[0], $image_size );
					}
				}
			}

			if ( $image ) {
				$image_url       = $image[0] ?? '';
				$lightbox_width  = $image[1] ?? '';
				$lightbox_height = $image[2] ?? '';
			}

			$this->set_attribute( $attribute_key, 'href', $image_url );
			$this->set_attribute( $attribute_key, 'class', 'bricks-lightbox' );
			$this->set_attribute( $attribute_key, 'data-pswp-src', $image_url );
			$this->set_attribute( $attribute_key, 'data-pswp-width', $lightbox_width );
			$this->set_attribute( $attribute_key, 'data-pswp-height', $lightbox_height );
		}

		// Lightbox video
		if ( $link_type === 'lightboxVideo' && isset( $link_settings['lightboxVideo'] ) ) {
			$video_url = bricks_render_dynamic_data( $link_settings['lightboxVideo'] );

			$this->set_attribute( $attribute_key, 'href', $video_url );
			$this->set_attribute( $attribute_key, 'class', 'bricks-lightbox' );
			$this->set_attribute( $attribute_key, 'data-pswp-width', $lightbox_width );
			$this->set_attribute( $attribute_key, 'data-pswp-height', $lightbox_height );
			$this->set_attribute( $attribute_key, 'data-pswp-video-url', $video_url );
		}

		// Dynamic data link
		if ( $link_type === 'meta' && ! empty( $link_settings['useDynamicData'] ) ) {
			// Check for the old dynamic data format
			$link_dd_tag = $link_settings['useDynamicData']['name'] ?? (string) $link_settings['useDynamicData'];

			// It is a composed link e.g. "https://my-domain.com/?p={post_id}" (@since 1.5.4)
			if ( strpos( $link_dd_tag, '{' ) !== 0 || substr_count( $link_dd_tag, '}' ) > 1 ) {
				$context = 'text';
			}

			// It is a dynamic data tag only e.g. "{post_url}"
			else {
				$context = 'link';
			}

			$href = bricks_render_dynamic_data( $link_dd_tag, get_the_ID(), $context );

			$this->set_attribute( $attribute_key, 'href', $href );
		}

		// Media link
		if ( $link_type === 'media' && isset( $link_settings['mediaData']['id'] ) ) {
			$this->set_attribute( $attribute_key, 'href', wp_get_attachment_url( $link_settings['mediaData']['id'] ) );
		}

		if ( isset( $link_settings['rel'] ) ) {
			$this->set_attribute( $attribute_key, 'rel', $link_settings['rel'] );
		}

		if ( isset( $link_settings['newTab'] ) ) {
			$this->set_attribute( $attribute_key, 'target', '_blank' );
		}

		if ( isset( $link_settings['title'] ) ) {
			$this->set_attribute( $attribute_key, 'title', $link_settings['title'] );
		}

		if ( isset( $link_settings['ariaLabel'] ) ) {
			$this->set_attribute( $attribute_key, 'aria-label', $link_settings['ariaLabel'] );
		}

		// Set aria-current="page" attribute to the link if it points to the current page. (@since 1.8)
		$this->maybe_set_aria_current( $link_settings, $attribute_key );
	}

	/**
	 * Maybe set aria-current="page" attribute to the link if it points to the current page.
	 *
	 * Example: nav-nested active nav item background color.
	 *
	 * NOTE: url_to_postid() returns 0 if URL contains the port like https://bricks.local:49581/blog/
	 *
	 * @since 1.8
	 */
	public function maybe_set_aria_current( $link_settings, $attribute_key ) {
		$link_type = $link_settings['type'] ?? false;
		$link_url  = false;

		switch ( $link_type ) {
			case 'internal':
				$link_url = ! empty( $link_settings['postId'] ) ? get_permalink( $link_settings['postId'] ) : false;
				break;

			case 'external':
				$link_url = $link_settings['url'] ?? false;
				break;

			case 'meta':
				$link_url = $link_settings['useDynamicData'] ?? false;
				break;
		}

		// Return: Link URL is "#"
		if ( $link_url === '#' ) {
			return;
		}

		if ( $link_url ) {
			$link_url = bricks_render_dynamic_data( $link_url );
		}

		// Set aria-current="page" attribute to the link if it points to the current page
		if ( $link_url && Helpers::maybe_set_aria_current_page( $link_url ) ) {
			$this->set_attribute( $attribute_key, 'aria-current', 'page' );
		}
	}

	/**
	 * Remove attribute
	 *
	 * @param string      $key        Element identifier.
	 * @param string      $attribute  Attribute to remove.
	 * @param string|null $value Set to remove single value instead of entire attribute.
	 *
	 * @since 1.0
	 */
	public function remove_attribute( $key, $attribute, $value = null ) {
		if ( ! isset( $this->attributes[ $key ] ) || ! is_array( $this->attributes[ $key ] ) ) {
			return;
		}

		if ( isset( $value ) ) {
			// Remove single attribute value
			$key_to_remove = array_search( $value, $this->attributes[ $key ][ $attribute ] );
			array_splice( $this->attributes[ $key ][ $attribute ], $key_to_remove, 1 );
		} else {
			// Remove entire attribute
			$key_to_remove = array_search( $attribute, $this->attributes[ $key ] );
			array_splice( $this->attributes[ $key ], $key_to_remove, 1 );
		}
	}

	/**
	 * Render HTML attributes for specific element
	 *
	 * @param string  $key                   Attribute identifier.
	 * @param boolean $add_custom_attributes true to get custom atts for elements where we don't add them to the wrapper (Nav Menu).
	 *
	 * @since 1.0
	 */
	public function render_attributes( $key, $add_custom_attributes = false ) {
		// @see: https://academy.bricksbuilder.io/article/filter-bricks-element-render_attributes/ (@since 1.3.7)
		$attributes = apply_filters( 'bricks/element/render_attributes', $this->attributes, $key, $this );

		// Return: No attributes set for this element
		if ( ! isset( $attributes[ $key ] ) ) {
			return;
		}

		$attribute_strings = [];

		// Add custom attributes and overwrite existing $attributes
		if ( $add_custom_attributes ) {
			$custom_attributes = $this->get_custom_attributes( $this->settings );

			if ( is_array( $custom_attributes ) ) {
				foreach ( $custom_attributes as $att_key => $att_val ) {
					$attributes[ $key ][ $att_key ] = $att_val;
				}
			}
		}

		foreach ( $attributes[ $key ] as $key => $value ) {
			// Empty, non-numeric value
			if ( empty( $value ) && ! is_numeric( $value ) ) {
				$attribute_strings[] = $key;
			}

			// Array value
			else {
				if ( is_array( $value ) ) {
					// Filter out empty values
					$value = array_filter(
						$value,
						function( $val ) {
							return ! empty( $val ) || is_numeric( $val );
						}
					);

					$value = join( ' ', $value );
				}

				// Escape HTML attribute value according to its type
				$value = filter_var( $value, FILTER_VALIDATE_URL ) ? esc_url( $value ) : esc_attr( $value );

				$attribute_strings[] = "$key=\"$value\"";
			}
		}

		return join( ' ', $attribute_strings );
	}

	/**
	 * Calculate element custom attributes based on settings (dynamic data too)
	 *
	 * @since 1.3
	 */
	public function get_custom_attributes( $settings = [] ) {
		if ( empty( $settings['_attributes'] ) || ! is_array( $settings['_attributes'] ) ) {
			return [];
		}

		$attributes = [];

		foreach ( $settings['_attributes'] as $index => $field ) {
			if ( ! empty( $field['name'] ) ) {
				// Use 'esc_attr' instead of 'sanitize_title' to avoid removing ':' (e.g. AlpineJS)
				$key = esc_attr( $field['name'] );

				$attributes[ $key ] = isset( $field['value'] ) ? bricks_render_dynamic_data( $field['value'], $this->post_id ) : '';
			}
		}

		return $attributes;
	}

	public static function stringify_attributes( $attributes = [] ) {
		$string = [];

		foreach ( $attributes as $key => $values ) {
			$string[] = $key . '="' . ( is_array( $values ) ? join( ' ', $values ) : $values ) . '"';
		}

		return join( ' ', $string );
	}

	/**
	 * Enqueue element-specific styles and scripts
	 *
	 * @since 1.0
	 */
	public function enqueue_scripts() {}

	/**
	 * Element HTML render
	 *
	 * @since 1.0
	 */
	public function render() {}

	/**
	 * Element HTML render in builder via x-template
	 *
	 * @since 1.0
	 */
	public static function render_builder() {}

	/**
	 * Builder: Get nestable item
	 *
	 * Use as blueprint for nestable children & when adding repeater item.
	 *
	 * @since 1.5
	 */
	public function get_nestable_item() {}

	/**
	 * Builder: Array of child elements added when inserting new nestable element
	 *
	 * @since 1.5
	 */
	public function get_nestable_children() {}

	/**
	 * Frontend: Lazy load (images, videos)
	 *
	 * Global settings 'disableLazyLoad': Disable lazy load altogether
	 * Page settings 'disableLazyLoad': Disable lazy load on this page (@since 1.8.6)
	 * Element settings 'disableLazyLoad': Carousel, slider, testimonials (= bricksSwiper) (@since 1.4)
	 *
	 * @since 1.0
	 */
	public function lazy_load() {
		// Skip lazy load: Custom HTML attribute set to loading=eager (@since 1.6)
		$custom_attributes = ! empty( $this->settings['_attributes'] ) ? $this->settings['_attributes'] : [];

		$skip_lazy_load = false;

		if ( is_array( $custom_attributes ) ) {
			foreach ( $custom_attributes as $attr ) {
				if (
					isset( $attr['name'] ) && $attr['name'] === 'loading' &&
					isset( $attr['value'] ) && $attr['value'] === 'eager'
				) {
					$skip_lazy_load = true;
				}
			}

			// Skip loading=eager
			if ( $skip_lazy_load ) {
				return false;
			}
		}

		return $this->is_frontend &&
			! bricks_is_ajax_call() &&
			! bricks_is_rest_call() &&
			! isset( Database::$global_settings['disableLazyLoad'] ) &&
			! isset( Database::$page_settings['disableLazyLoad'] ) &&
			! isset( $this->settings['disableLazyLoad'] );
	}

	/**
	 * Enqueue element scripts & styles, set attributes, render
	 *
	 * @since 1.0
	 */
	public function init() {
		// Enqueue scripts & styles
		$this->enqueue_scripts();

		// Set global $post with builder AJAX/REST API submitted postId to retrieve correct post object (unless it is looping)
		if ( Query::is_looping() && Query::get_loop_object_type() == 'post' ) {
			$post_id = Query::get_loop_object_id();
		} else {
			/**
			 * Changed from Database::$page_data['preview_or_post_id'] to Database::$page_data['original_post_id'] to ensure setup_query runs inside of a template
			 *
			 * @since 1.5.7
			 *
			 * NOTE: Undocumented
			 */
			$post_id = apply_filters( 'bricks/builder/data_post_id', isset( Database::$page_data['original_post_id'] ) ? Database::$page_data['original_post_id'] : Database::$page_data['preview_or_post_id'] );
		}

		$this->set_post_id( $post_id );

		// Set root attributes
		$this->set_root_attributes();

		/**
		 * Setup query if post or page direct edit with Bricks (#861m48kv4)
		 *
		 * If bricks_is_builder_call(), shouldn't setup query if looping.
		 *
		 * @since 1.8
		 */
		$setup_preview_query = Helpers::is_bricks_template( $post_id ) || ( bricks_is_builder_call() && ! Query::is_looping() );

		if ( $setup_preview_query ) {
			$this->setup_query( $post_id );
		}

		$render_element = true;

		// Check element conditions (@since 1.5.4)
		if ( ! empty( $this->settings['_conditions'] ) ) {
			$render_element = Conditions::check( $this->settings['_conditions'], $this );
		}

		// NOTE: Undocumented (@since 1.5 to interject element render)
		$render_element = apply_filters( 'bricks/element/render', $render_element, $this );

		if ( $render_element ) {
			/** To interject element settings for translation plugins, etc.
			 *
			 * https://academy.bricksbuilder.io/article/filter-bricks-element-settings/
			 *
			 * @since 1.5
			 */
			$this->settings = apply_filters( 'bricks/element/settings', $this->settings, $this );

			$this->render();
		}

		// @since 1.8 - Restore query if post or page direct edit with Bricks (#861m48kv4)
		if ( $setup_preview_query ) {
			$this->restore_query();
		}
	}

	/**
	 * Calculate column width
	 */
	public function calc_column_width( $columns_count = 1, $max = false ) {
		$column_width = floor( 100 / intval( $columns_count ) );

		if ( is_int( $max ) && $columns_count > $max ) {
			return floor( 100 / intval( $max ) );
		}

		return $column_width;
	}

	/**
	 * Column width calculator
	 *
	 * @param int $columns Number of columns.
	 * @param int $count   Total amount of items.
	 */
	public function column_width( $columns, $count ) {
		// If more columns are requested than available use $count instead of $columns
		if ( $columns > $count ) {
			$width = 100 / $count;
		} else {
			$width = 100 / $columns;
		}

		return $width;
	}

	/**
	 * Post fields
	 *
	 * Shared between elements: Carousel, Posts, Products, etc.
	 *
	 * @since 1.0
	 */
	public function get_post_fields() {
		$post_controls = [];

		$post_controls['fields'] = [
			'tab'           => 'content',
			'group'         => 'fields',
			'placeholder'   => esc_html__( 'Field', 'bricks' ),
			'type'          => 'repeater',
			'selector'      => 'fieldId',
			'titleProperty' => 'dynamicData',
			'fields'        => [

				'dynamicData'       => [
					'label' => esc_html__( 'Dynamic data', 'bricks' ),
					'type'  => 'text',
				],

				'tag'               => [
					'label'       => esc_html__( 'HTML tag', 'bricks' ),
					'type'        => 'select',
					'options'     => [
						'div' => 'div',
						'p'   => 'p',
						'h1'  => 'h1',
						'h2'  => 'h2',
						'h3'  => 'h3',
						'h4'  => 'h4',
						'h5'  => 'h5',
						'h6'  => 'h6',
					],
					'inline'      => true,
					'placeholder' => 'div',
				],

				'dynamicMargin'     => [
					'label' => esc_html__( 'Margin', 'bricks' ),
					'type'  => 'spacing',
					'css'   => [
						[
							'property' => 'margin',
						],
					],
				],

				'dynamicPadding'    => [
					'label' => esc_html__( 'Padding', 'bricks' ),
					'type'  => 'spacing',
					'css'   => [
						[
							'property' => 'padding',
						],
					],
				],

				'dynamicBackground' => [
					'label' => esc_html__( 'Background color', 'bricks' ),
					'type'  => 'color',
					'css'   => [
						[
							'property' => 'background-color',
						],
					],
				],

				'dynamicBorder'     => [
					'label' => esc_html__( 'Border', 'bricks' ),
					'type'  => 'border',
					'css'   => [
						[
							'property' => 'border',
						],
					],
				],

				'dynamicTypography' => [
					'label' => esc_html__( 'Typography', 'bricks' ),
					'type'  => 'typography',
					'css'   => [
						[
							'property' => 'font',
						],
					],
				],

				// Overlay

				'overlay'           => [
					'label'       => esc_html__( 'Overlay', 'bricks' ),
					'type'        => 'checkbox',
					'inline'      => true,
					'description' => esc_html__( 'Precedes "Link Image" setting.', 'bricks' ),
				],

			],

			'default'       => [
				[
					'dynamicData'   => '{post_title:link}',
					'tag'           => 'h3',
					'dynamicMargin' => [
						'top'    => 20,
						'right'  => 0,
						'bottom' => 20,
						'left'   => 0,
					],
					'id'            => Helpers::generate_random_id( false ),
				],
				[
					'dynamicData' => '{post_excerpt:20}',
					'id'          => Helpers::generate_random_id( false ),
				],
			],
		];

		return $post_controls;
	}

	/**
	 * Post content
	 *
	 * Shared between elements: Carousel, Posts
	 *
	 * @since 1.0
	 */
	public function get_post_content() {
		$post_content = [];

		$post_content['contentAlign'] = [
			'tab'     => 'content',
			'group'   => 'content',
			'type'    => 'select',
			'label'   => esc_html__( 'Alignment', 'bricks' ),
			'options' => [
				'top left'      => esc_html__( 'Top left', 'bricks' ),
				'top center'    => esc_html__( 'Top center', 'bricks' ),
				'top right'     => esc_html__( 'Top right', 'bricks' ),

				'middle left'   => esc_html__( 'Middle left', 'bricks' ),
				'middle center' => esc_html__( 'Middle center', 'bricks' ),
				'middle right'  => esc_html__( 'Middle right', 'bricks' ),

				'bottom left'   => esc_html__( 'Bottom left', 'bricks' ),
				'bottom center' => esc_html__( 'Bottom center', 'bricks' ),
				'bottom right'  => esc_html__( 'Bottom right', 'bricks' ),
			],
			'inline'  => true,
		];

		// NOTE: Necessary as Isotope doesn't play nice with flexbox, but float
		// https://github.com/metafizzy/isotope/issues/1234
		$post_content['contentHeight'] = [
			'tab'      => 'content',
			'group'    => 'content',
			'label'    => esc_html__( 'Min. height', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'min-height',
					'selector' => '.content-wrapper',
				],
			],
			'rerender' => true,
		];

		$post_content['contentMargin'] = [
			'tab'   => 'content',
			'group' => 'content',
			'type'  => 'spacing',
			'label' => esc_html__( 'Margin', 'bricks' ),
			'css'   => [
				[
					'property' => 'margin',
					'selector' => '.content-wrapper',
				],
			],
		];

		$post_content['contentPadding'] = [
			'tab'   => 'content',
			'group' => 'content',
			'type'  => 'spacing',
			'label' => esc_html__( 'Padding', 'bricks' ),
			'css'   => [
				[
					'property' => 'padding',
					'selector' => '.content-wrapper',
				],
			],
		];

		$post_content['contentBackground'] = [
			'tab'   => 'content',
			'group' => 'content',
			'type'  => 'color',
			'label' => esc_html__( 'Background color', 'bricks' ),
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.content-wrapper',
				],
			],
		];

		return $post_content;
	}

	/**
	 * Post overlay
	 *
	 * Shared between elements: Carousel, Posts
	 *
	 * @since 1.0
	 */
	public function get_post_overlay() {
		$post_overlay = [];

		$post_overlay['overlayOnHover'] = [
			'tab'         => 'content',
			'group'       => 'overlay',
			'label'       => esc_html__( 'Show on hover', 'bricks' ),
			'type'        => 'checkbox',
			'inline'      => true,
			'small'       => true,
			'description' => esc_html__( 'Always shows in builder for editing.', 'bricks' ),
		];

		$post_overlay['overlayAnimation'] = [
			'tab'      => 'content',
			'group'    => 'overlay',
			'label'    => esc_html__( 'Fade in animation', 'bricks' ),
			'type'     => 'select',
			'options'  => [
				'fade-in-up'    => esc_html__( 'Fade in up', 'bricks' ),
				'fade-in-right' => esc_html__( 'Fade in right', 'bricks' ),
				'fade-in-down'  => esc_html__( 'Fade in down', 'bricks' ),
				'fade-in-left'  => esc_html__( 'Fade in left', 'bricks' ),
				'zoom-in'       => esc_html__( 'Zoom in', 'bricks' ),
				'zoom-out'      => esc_html__( 'Zoom out', 'bricks' ),
			],
			'inline'   => true,
			'required' => [ 'overlayOnHover', '!=', '' ],
		];

		$post_overlay['overlayAlign'] = [
			'tab'     => 'content',
			'group'   => 'overlay',
			'type'    => 'select',
			'label'   => esc_html__( 'Alignment', 'bricks' ),
			'options' => [
				'top left'      => esc_html__( 'Top left', 'bricks' ),
				'top center'    => esc_html__( 'Top center', 'bricks' ),
				'top right'     => esc_html__( 'Top right', 'bricks' ),

				'middle left'   => esc_html__( 'Middle left', 'bricks' ),
				'middle center' => esc_html__( 'Middle center', 'bricks' ),
				'middle right'  => esc_html__( 'Middle right', 'bricks' ),

				'bottom left'   => esc_html__( 'Bottom left', 'bricks' ),
				'bottom center' => esc_html__( 'Bottom center', 'bricks' ),
				'bottom right'  => esc_html__( 'Bottom right', 'bricks' ),
			],
			'inline'  => true,
		];

		$post_overlay['overlayMargin'] = [
			'tab'   => 'content',
			'group' => 'overlay',
			'type'  => 'spacing',
			'label' => esc_html__( 'Margin', 'bricks' ),
			'css'   => [
				[
					'property' => 'margin',
					'selector' => '.overlay-wrapper',
				],
			],
		];

		$post_overlay['overlayPadding'] = [
			'tab'   => 'content',
			'group' => 'overlay',
			'type'  => 'spacing',
			'label' => esc_html__( 'Padding', 'bricks' ),
			'css'   => [
				[
					'property' => 'padding',
					'selector' => '.overlay-wrapper',
				],
			],
		];

		$post_overlay['overlayBackground'] = [
			'tab'   => 'content',
			'group' => 'overlay',
			'type'  => 'color',
			'label' => esc_html__( 'Background color', 'bricks' ),
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.overlay-wrapper',
				],
			],
		];

		$post_overlay['overlayInnerBackground'] = [
			'tab'   => 'content',
			'group' => 'overlay',
			'type'  => 'color',
			'label' => esc_html__( 'Inner background color', 'bricks' ),
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.overlay-inner',
				],
			],
		];

		return $post_overlay;
	}

	/**
	 * Get swiper controls
	 *
	 * Elements: Carousel, Slider, Team Members.
	 *
	 * @since 1.0
	 */
	public static function get_swiper_controls() {
		$controls['height'] = [
			'tab'         => 'content',
			'group'       => 'settings',
			'label'       => esc_html__( 'Height', 'bricks' ),
			'type'        => 'number',
			'units'       => true,
			'css'         => [
				[
					'property' => 'height',
					'selector' => '.swiper-slide',
				],
			],
			'placeholder' => 300,
		];

		$controls['gutter'] = [
			'tab'         => 'content',
			'group'       => 'settings',
			'label'       => esc_html__( 'Spacing', 'bricks' ) . ' (px)',
			'type'        => 'number',
			'placeholder' => 0,
		];

		$controls['imageRatio'] = [
			'tab'       => 'content',
			'group'     => 'settings',
			'label'     => esc_html__( 'Image ratio', 'bricks' ),
			'type'      => 'select',
			'options'   => Setup::$control_options['imageRatio'],
			'default'   => 'ratio-square',
			'clearable' => false,
			'inline'    => true,
		];

		$controls['initialSlide'] = [
			'tab'         => 'content',
			'group'       => 'settings',
			'label'       => esc_html__( 'Initial slide', 'bricks' ),
			'type'        => 'number',
			'min'         => 0,
			'max'         => 10,
			'placeholder' => 0,
		];

		$controls['slidesToShow'] = [
			'tab'         => 'content',
			'group'       => 'settings',
			'label'       => esc_html__( 'Items to show', 'bricks' ),
			'type'        => 'number',
			'min'         => 1,
			'max'         => 10,
			'placeholder' => 1,
			'breakpoints' => true, // NOTE: Undocumented (allows setting non-CSS settings per breakpoint: Carousel, Slider, etc.)
			'required'    => [ 'effect', '!=', [ 'fade', 'cube', 'flip' ] ],
		];

		$controls['slidesToScroll'] = [
			'tab'         => 'content',
			'group'       => 'settings',
			'label'       => esc_html__( 'Items to scroll', 'bricks' ),
			'type'        => 'number',
			'min'         => 1,
			'max'         => 10,
			'placeholder' => 1,
			'breakpoints' => true,
			'required'    => [ 'effect', '!=', [ 'fade', 'cube', 'flip' ] ],
		];

		$controls['effect'] = [
			'tab'         => 'content',
			'group'       => 'settings',
			'label'       => esc_html__( 'Effect', 'bricks' ),
			'type'        => 'select',
			'options'     => [
				'slide'     => esc_html__( 'Slide', 'bricks' ),
				'fade'      => esc_html__( 'Fade', 'bricks' ),
				'cube'      => esc_html__( 'Cube', 'bricks' ),
				'coverflow' => esc_html__( 'Coverflow', 'bricks' ),
				'flip'      => esc_html__( 'Flip', 'bricks' ),
			],
			'inline'      => true,
			'placeholder' => esc_html__( 'Slide', 'bricks' ),
		];

		// @since 1.9.3
		$controls['swiperLoop'] = [
			'tab'         => 'content',
			'group'       => 'settings',
			'label'       => esc_html__( 'Loop', 'bricks' ),
			'placeholder' => esc_html__( 'Enable', 'bricks' ),
			'type'        => 'select',
			'inline'      => true,
			'options'     => [
				'enable'  => esc_html__( 'Enable', 'bricks' ),
				'disable' => esc_html__( 'Disable', 'bricks' ),
			],
		];

		$controls['infinite'] = [
			'tab'     => 'content',
			'group'   => 'settings',
			'label'   => esc_html__( 'Loop', 'bricks' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$controls['centerMode'] = [
			'tab'   => 'style',
			'group' => 'settings',
			'label' => esc_html__( 'Center mode', 'bricks' ),
			'type'  => 'checkbox',
		];

		$controls['disableLazyLoad'] = [
			'tab'   => 'style',
			'group' => 'settings',
			'label' => esc_html__( 'Disable lazy load', 'bricks' ),
			'type'  => 'checkbox',
		];

		$controls['adaptiveHeight'] = [
			'tab'   => 'content',
			'group' => 'settings',
			'label' => esc_html__( 'Adaptive height', 'bricks' ),
			'type'  => 'checkbox',
		];

		$controls['autoplay'] = [
			'tab'   => 'content',
			'group' => 'settings',
			'label' => esc_html__( 'Autoplay', 'bricks' ),
			'type'  => 'checkbox',
		];

		$controls['pauseOnHover'] = [
			'tab'      => 'content',
			'group'    => 'settings',
			'label'    => esc_html__( 'Pause on hover', 'bricks' ),
			'type'     => 'checkbox',
			'required' => [ 'autoplay', '!=', '' ],
		];

		$controls['stopOnLastSlide'] = [
			'tab'      => 'content',
			'group'    => 'settings',
			'label'    => esc_html__( 'Stop on last slide', 'bricks' ),
			'type'     => 'checkbox',
			'info'     => esc_html__( 'No effect with loop enabled', 'bricks' ),
			'required' => [ 'autoplay', '!=', '' ],
		];

		$controls['autoplaySpeed'] = [
			'tab'         => 'content',
			'group'       => 'settings',
			'label'       => esc_html__( 'Autoplay delay in ms', 'bricks' ),
			'type'        => 'number',
			'required'    => [ 'autoplay', '!=', '' ],
			'placeholder' => 3000,
		];

		$controls['speed'] = [
			'tab'         => 'content',
			'group'       => 'settings',
			'label'       => esc_html__( 'Animation speed in ms', 'bricks' ),
			'type'        => 'number',
			'min'         => 1,
			'placeholder' => 300,
		];

		// Arrows

		$controls['arrows'] = [
			'tab'      => 'content',
			'group'    => 'arrows',
			'label'    => esc_html__( 'Show arrows', 'bricks' ),
			'type'     => 'checkbox',
			'rerender' => true,
			'default'  => true,
		];

		$controls['arrowHeight'] = [
			'tab'         => 'content',
			'group'       => 'arrows',
			'label'       => esc_html__( 'Height', 'bricks' ),
			'type'        => 'number',
			'units'       => true,
			'css'         => [
				[
					'property' => 'height',
					'selector' => '.swiper-button',
				],
			],
			'placeholder' => 50,
			'required'    => [ 'arrows', '!=', '' ],
		];

		$controls['arrowWidth'] = [
			'tab'         => 'content',
			'group'       => 'arrows',
			'label'       => esc_html__( 'Width', 'bricks' ),
			'type'        => 'number',
			'units'       => true,
			'css'         => [
				[
					'property' => 'width',
					'selector' => '.swiper-button',
				],
			],
			'placeholder' => 50,
			'required'    => [ 'arrows', '!=', '' ],
		];

		$controls['arrowBackground'] = [
			'tab'      => 'content',
			'group'    => 'arrows',
			'label'    => esc_html__( 'Background', 'bricks' ),
			'type'     => 'color',
			'css'      => [
				[
					'property' => 'background-color',
					'selector' => '.swiper-button',
				],
			],
			'required' => [ 'arrows', '!=', '' ],
		];

		$controls['arrowBorder'] = [
			'tab'      => 'content',
			'group'    => 'arrows',
			'label'    => esc_html__( 'Border', 'bricks' ),
			'type'     => 'border',
			'css'      => [
				[
					'property' => 'border',
					'selector' => '.swiper-button',
				],
			],
			'required' => [ 'arrows', '!=', '' ],
		];

		$controls['arrowTypography'] = [
			'tab'      => 'content',
			'group'    => 'arrows',
			'label'    => esc_html__( 'Typography', 'bricks' ),
			'type'     => 'typography',
			'css'      => [
				[
					'property' => 'font',
					'selector' => '.swiper-button',
				],
			],
			'exclude'  => [
				'font-family',
				'font-weight',
				'font-style',
				'text-align',
				'letter-spacing',
				'line-height',
				'text-transform',
			],
			'required' => [ 'arrows', '!=', '' ],
		];

		$controls['prevArrowSeparator'] = [
			'tab'      => 'content',
			'group'    => 'arrows',
			'label'    => esc_html__( 'Prev arrow', 'bricks' ),
			'type'     => 'separator',
			'required' => [ 'arrows', '!=', '' ],
		];

		$controls['prevArrow'] = [
			'tab'      => 'content',
			'group'    => 'arrows',
			'label'    => esc_html__( 'Prev arrow', 'bricks' ),
			'type'     => 'icon',
			'default'  => [
				'library' => 'ionicons',
				'icon'    => 'ion-ios-arrow-back',
			],
			'css'      => [
				[
					'selector' => '.bricks-swiper-button-prev > *',
				],
			],
			'required' => [ 'arrows', '!=', '' ],
			'rerender' => true,
		];

		$controls['prevArrowTop'] = [
			'tab'      => 'content',
			'group'    => 'arrows',
			'label'    => esc_html__( 'Top', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'top',
					'selector' => '.bricks-swiper-button-prev',
				],
			],
			'required' => [ 'arrows', '!=', '' ],
		];

		$controls['prevArrowRight'] = [
			'tab'      => 'content',
			'group'    => 'arrows',
			'label'    => esc_html__( 'Right', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'right',
					'selector' => '.bricks-swiper-button-prev',
				],
			],
			'required' => [ 'arrows', '!=', '' ],
		];

		$controls['prevArrowBottom'] = [
			'tab'      => 'content',
			'group'    => 'arrows',
			'label'    => esc_html__( 'Bottom', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'bottom',
					'selector' => '.bricks-swiper-button-prev',
				],
			],
			'required' => [ 'arrows', '!=', '' ],
		];

		$controls['prevArrowLeft'] = [
			'tab'      => 'content',
			'group'    => 'arrows',
			'label'    => esc_html__( 'Left', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'left',
					'selector' => '.bricks-swiper-button-prev',
				],
			],
			'default'  => '50px',
			'required' => [ 'arrows', '!=', '' ],
		];

		$controls['prevArrowTransform'] = [
			'tab'      => 'content',
			'group'    => 'arrows',
			'label'    => esc_html__( 'Transform', 'bricks' ),
			'type'     => 'transform',
			'css'      => [
				[
					'property' => 'transform',
					'selector' => '.bricks-swiper-button-prev',
				],
			],
			'required' => [ 'arrows', '!=', '' ],
		];

		$controls['nextArrowSeparator'] = [
			'tab'      => 'content',
			'group'    => 'arrows',
			'label'    => esc_html__( 'Next arrow', 'bricks' ),
			'type'     => 'separator',
			'required' => [ 'arrows', '!=', '' ],
		];

		$controls['nextArrow'] = [
			'tab'      => 'content',
			'group'    => 'arrows',
			'label'    => esc_html__( 'Next arrow', 'bricks' ),
			'type'     => 'icon',
			'default'  => [
				'library' => 'ionicons',
				'icon'    => 'ion-ios-arrow-forward',
			],
			'css'      => [
				[
					'selector' => '.bricks-swiper-button-next > *',
				],
			],
			'required' => [ 'arrows', '!=', '' ],
			'rerender' => true,
		];

		$controls['nextArrowTop'] = [
			'tab'      => 'content',
			'group'    => 'arrows',
			'label'    => esc_html__( 'Top', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'top',
					'selector' => '.bricks-swiper-button-next',
				],
			],
			'required' => [ 'arrows', '!=', '' ],
		];

		$controls['nextArrowRight'] = [
			'tab'      => 'content',
			'group'    => 'arrows',
			'label'    => esc_html__( 'Right', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'right',
					'selector' => '.bricks-swiper-button-next',
				],
			],
			'default'  => '50px',
			'required' => [ 'arrows', '!=', '' ],
		];

		$controls['nextArrowBottom'] = [
			'tab'      => 'content',
			'group'    => 'arrows',
			'label'    => esc_html__( 'Bottom', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'bottom',
					'selector' => '.bricks-swiper-button-next',
				],
			],
			'required' => [ 'arrows', '!=', '' ],
		];

		$controls['nextArrowLeft'] = [
			'tab'      => 'content',
			'group'    => 'arrows',
			'label'    => esc_html__( 'Left', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'left',
					'selector' => '.bricks-swiper-button-next',
				],
			],
			'required' => [ 'arrows', '!=', '' ],
		];

		$controls['nextArrowTransform'] = [
			'tab'      => 'content',
			'group'    => 'arrows',
			'label'    => esc_html__( 'Transform', 'bricks' ),
			'type'     => 'transform',
			'css'      => [
				[
					'property' => 'transform',
					'selector' => '.bricks-swiper-button-next',
				],
			],
			'required' => [ 'arrows', '!=', '' ],
		];

		// Dots

		$controls['dots'] = [
			'tab'      => 'content',
			'group'    => 'dots',
			'label'    => esc_html__( 'Show dots', 'bricks' ),
			'type'     => 'checkbox',
			'inline'   => true,
			'rerender' => true,
		];

		$controls['dotsDynamic'] = [
			'tab'      => 'content',
			'group'    => 'dots',
			'label'    => esc_html__( 'Dynamic dots', 'bricks' ),
			'type'     => 'checkbox',
			'inline'   => true,
			'required' => [ 'dots', '!=', '' ],
		];

		$controls['dotsVertical'] = [
			'tab'      => 'content',
			'group'    => 'dots',
			'label'    => esc_html__( 'Vertical', 'bricks' ),
			'type'     => 'checkbox',
			'inline'   => true,
			'css'      => [
				[
					'property' => 'flex-direction',
					'selector' => '.swiper-pagination-bullets',
					'value'    => 'column',
				],
			],
			'rerender' => true,
			'required' => [ 'dots', '!=', '' ],
		];

		$controls['dotsHeight'] = [
			'tab'      => 'content',
			'group'    => 'dots',
			'label'    => esc_html__( 'Height', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'units'    => [
				'px' => [
					'min' => 1,
					'max' => 100,
				],
			],
			'css'      => [
				[
					'property' => 'height',
					'selector' => '.swiper-pagination-bullet',
				],
			],
			'required' => [ 'dots', '!=', '' ],
		];

		$controls['dotsWidth'] = [
			'tab'      => 'content',
			'group'    => 'dots',
			'label'    => esc_html__( 'Width', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'units'    => [
				'px' => [
					'min' => 1,
					'max' => 100,
				],
			],
			'css'      => [
				[
					'property' => 'width',
					'selector' => '.swiper-pagination-bullet',
				],
			],
			'required' => [ 'dots', '!=', '' ],
		];

		$controls['dotsTop'] = [
			'tab'      => 'content',
			'group'    => 'dots',
			'label'    => esc_html__( 'Top', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'top',
					'selector' => '.bricks-swiper-container + .swiper-pagination-bullets',
				],
			],
			'required' => [ 'dots', '!=', '' ],
		];

		$controls['dotsRight'] = [
			'tab'      => 'content',
			'group'    => 'dots',
			'label'    => esc_html__( 'Right', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'right',
					'selector' => '.bricks-swiper-container + .swiper-pagination-bullets',
				],
				[
					'property' => 'left',
					'value'    => 'auto',
					'selector' => '.bricks-swiper-container + .swiper-pagination-bullets',
				],
				[
					'property' => 'transform',
					'selector' => '.bricks-swiper-container + .swiper-pagination-bullets',
					'value'    => 'translateX(0)',
				],
			],
			'required' => [ 'dots', '!=', '' ],
		];

		$controls['dotsBottom'] = [
			'tab'      => 'content',
			'group'    => 'dots',
			'label'    => esc_html__( 'Bottom', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'bottom',
					'selector' => '.bricks-swiper-container + .swiper-pagination-bullets',
				],
			],
			'required' => [ 'dots', '!=', '' ],
		];

		$controls['dotsLeft'] = [
			'tab'      => 'content',
			'group'    => 'dots',
			'label'    => esc_html__( 'Left', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'left',
					'selector' => '.bricks-swiper-container + .swiper-pagination-bullets',
				],
				[
					'property' => 'transform',
					'selector' => '.bricks-swiper-container + .swiper-pagination-bullets',
					'value'    => 'translateX(0)',
				],
			],
			'required' => [ 'dots', '!=', '' ],
		];

		$controls['dotsBorder'] = [
			'tab'      => 'content',
			'group'    => 'dots',
			'label'    => esc_html__( 'Border', 'bricks' ),
			'type'     => 'border',
			'css'      => [
				[
					'property' => 'border',
					'selector' => '.swiper-pagination-bullet',
				],
			],
			'required' => [ 'dots', '!=', '' ],
		];

		$controls['dotsColor'] = [
			'tab'      => 'content',
			'group'    => 'dots',
			'label'    => esc_html__( 'Color', 'bricks' ),
			'type'     => 'color',
			'css'      => [
				[
					'property' => 'background-color',
					'selector' => '.swiper-pagination-bullet',
				],
				[
					'property' => 'color',
					'selector' => '.swiper-pagination-bullet',
				],
			],
			'required' => [ 'dots', '!=', '' ],
		];

		$controls['dotsActiveColor'] = [
			'tab'      => 'content',
			'group'    => 'dots',
			'label'    => esc_html__( 'Active color', 'bricks' ),
			'type'     => 'color',
			'css'      => [
				[
					'property' => 'background-color',
					'selector' => '.swiper-pagination-bullet-active',
				],
				[
					'property' => 'color',
					'selector' => '.swiper-pagination-bullet-active',
				],
			],
			'required' => [ 'dots', '!=', '' ],
		];

		$controls['dotsSpacing'] = [
			'tab'      => 'content',
			'group'    => 'dots',
			'label'    => esc_html__( 'Margin', 'bricks' ),
			'type'     => 'spacing',
			'css'      => [
				[
					'property' => 'margin',
					'selector' => '.swiper-pagination-bullet',
				],
			],
			'required' => [ 'dots', '!=', '' ],
		];

		return $controls;
	}

	/**
	 * Render swiper nav: Navigation (arrows) & pagination (dots)
	 *
	 * Elements: Carousel, Slider, Team Members.
	 *
	 * @param array $options SwiperJS options.
	 *
	 * @since 1.4
	 */
	public function render_swiper_nav( $options = false ) {
		$options = $options ? $options : $this->settings;

		$output = '';

		// Dots (pagination)
		if ( isset( $options['dots'] ) ) {
			$output .= '<div class="swiper-pagination"></div>';
		}

		// ARROWS (navigation)
		if ( isset( $options['arrows'] ) ) {
			// Prev arrow
			$prev_arrow = false;

			// Check: Element & theme style settings
			if ( ! empty( $options['prevArrow'] ) ) {
				$prev_arrow = self::render_icon( $options['prevArrow'] );
			} elseif ( ! empty( Theme_Styles::$active_settings[ $this->name ]['prevArrow'] ) ) {
				$prev_arrow = self::render_icon( Theme_Styles::$active_settings[ $this->name ]['prevArrow'] );
			}

			if ( $prev_arrow ) {
				$output .= '<div class="swiper-button bricks-swiper-button-prev">' . $prev_arrow . '</div>';
			}

			// Next arrow
			$next_arrow = false;

			// Check: Element & theme style settings
			if ( ! empty( $options['nextArrow'] ) ) {
				$next_arrow = self::render_icon( $options['nextArrow'] );
			} elseif ( ! empty( Theme_Styles::$active_settings[ $this->name ]['nextArrow'] ) ) {
				$next_arrow = self::render_icon( Theme_Styles::$active_settings[ $this->name ]['nextArrow'] );
			}

			if ( $next_arrow ) {
				$output .= '<div class="swiper-button bricks-swiper-button-next">' . $next_arrow . '</div>';
			}
		}

		return $output;
	}

	/**
	 * Custom loop builder controls
	 *
	 * Shared between Container, Template, ...
	 *
	 * @since 1.3.7
	 */
	public function get_loop_builder_controls( $group = '' ) {
		$controls = [];

		$controls['hasLoop'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Use query loop', 'bricks' ),
			'type'  => 'checkbox',
		];

		$controls['query'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Query', 'bricks' ),
			'type'     => 'query',
			'popup'    => true,
			'inline'   => true,
			'required' => [ 'hasLoop', '!=', '' ],
		];

		if ( ! empty( $group ) ) {
			foreach ( $controls as $key => $control ) {
				$controls[ $key ]['group'] = $group;
			}
		}

		return $controls;
	}

	/**
	 * Render the query loop trail
	 *
	 * Trail enables infinite scroll
	 *
	 * @since 1.5
	 *
	 * @param Query  $query    The query object.
	 * @param string $node_key The element key to add the query data attributes (used in the posts element).
	 *
	 * @return string
	 */
	public function render_query_loop_trail( $query, $node_key = '' ) {
		$settings = ! empty( $this->element['settings'] ) ? $this->element['settings'] : [];

		if ( ! $this->is_frontend || bricks_is_rest_call() ) {
			return '';
		}

		$render   = empty( $node_key );
		$node_key = empty( $node_key ) ? 'trail' : $node_key;

		$page = isset( $query->query_vars['paged'] ) ? $query->query_vars['paged'] : 1;

		// This will cause no results got no queryLoopInstances generated in frontend (@since 1.9.6)
		// if ( $page == 1 && $query->max_num_pages == 1 ) {
		// return;
		// }

		// Query trail class (load more or infinite scroll)
		$this->set_attribute( $node_key, 'class', 'brx-query-trail' );

		// Is Live Search: So JavaScript will hide it's results container if input value is empty
		if ( isset( $settings['query']['is_live_search'] ) ) {
			$this->set_attribute( $node_key, 'data-brx-live-search', true );
		}

		// Infinite scroll class
		if ( isset( $settings['query']['infinite_scroll'] ) ) {
			$this->set_attribute( $node_key, 'class', 'brx-infinite-scroll' );
		}

		// AJAX loader (@since 1.9)
		if ( ! empty( $settings['query']['ajax_loader_animation'] ) ) {
			$ajax_loader_data = [
				'animation' => $settings['query']['ajax_loader_animation'],
				'selector'  => isset( $settings['query']['ajax_loader_selector'] ) ? $settings['query']['ajax_loader_selector'] : '',
				'color'     => isset( $settings['query']['ajax_loader_color'] ) ? Assets::generate_css_color( $settings['query']['ajax_loader_color'] ) : '',
				'scale'     => isset( $settings['query']['ajax_loader_scale'] ) ? $settings['query']['ajax_loader_scale'] : '',
			];

			$this->set_attribute( $node_key, 'data-brx-ajax-loader', wp_json_encode( $ajax_loader_data ) );
		}

		// Element ID
		$this->set_attribute( $node_key, 'data-query-element-id', $this->id );

		// Unset 'queryEditor' value as not needed in the frontend (@since 1.9.1)
		if ( isset( $query->query_vars['queryEditor'] ) ) {
			unset( $query->query_vars['queryEditor'] );
		}
		// Query vars: needed to make sure the context is the same if the query was merged with the global query (@since 1.5.1)
		$this->set_attribute( $node_key, 'data-query-vars', wp_json_encode( $query->query_vars ) );

		// Pagination
		$this->set_attribute( $node_key, 'data-page', $page );
		$this->set_attribute( $node_key, 'data-max-pages', $query->max_num_pages );

		// Observer margin (only px or %)
		if ( ! empty( $settings['query']['infinite_scroll_margin'] ) ) {
			$offset = $settings['query']['infinite_scroll_margin'];

			if ( strpos( $offset, 'px' ) === false && strpos( $offset, '%' ) === false ) {
				$offset = intval( $offset ) . 'px';
			}

			$this->set_attribute( $node_key, 'data-observer-margin', $offset );
		}

		if ( $render ) {
			echo "<div {$this->render_attributes( 'trail' )}></div>";
		}
	}

	/**
	 * Get the dynamic data for a specific tag
	 *
	 * @param string $tag Dynamic data tag.
	 * @param string $context text, image, media, link.
	 * @param array  $args Needed to set size for avatar image.
	 * @param string $post_id Post ID.
	 *
	 * @return mixed
	 */
	public function render_dynamic_data_tag( $tag = '', $context = 'text', $args = [], $post_id = 0 ) {
		if ( ! $post_id ) {
			$post_id = Query::is_looping() && Query::get_loop_object_type() == 'post' ? Query::get_loop_object_id() : $this->post_id;
		}

		return Integrations\Dynamic_Data\Providers::render_tag( $tag, $post_id, $context, $args );
	}

	/**
	 * Render dynamic data tags on a string
	 *
	 * @param string $content
	 *
	 * @return mixed
	 */
	public function render_dynamic_data( $content = '' ) {
		$post_id = Query::is_looping() && Query::get_loop_object_type() == 'post' ? Query::get_loop_object_id() : $this->post_id;

		return bricks_render_dynamic_data( $content, $post_id );
	}

	/**
	 * Set Post ID
	 *
	 * @param int $post_id
	 *
	 * @return void
	 */
	public function set_post_id( $post_id = 0 ) {
		$this->post_id = $post_id;
	}

	/**
	 * Setup query for templates according to 'templatePreviewType'
	 *
	 * To alter builder template and template preview query. NOT the frontend!
	 *
	 * 1. Set element $post_id
	 * 2. Populate query_args from"Populate content" settings and set it to global $wp_query
	 *
	 * @param integer $post_id
	 *
	 * @since 1.0
	 */
	public function setup_query( $post_id = 0 ) {
		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}

		// STEP: Set post ID to template preview ID if direct edit or single template preview
		$template_settings     = Helpers::get_template_settings( $post_id );
		$template_preview_type = Helpers::get_template_setting( 'templatePreviewType', $post_id );

		// @since 1.8 - Set preview type if direct edit page or post with Bricks (#861m48kv4)
		if ( bricks_is_builder_call() && empty( $template_settings ) && ! Helpers::is_bricks_template( $post_id ) ) {
			$template_preview_type = 'direct-edit';
		}

		if ( in_array( $template_preview_type, [ 'direct-edit', 'single' ] ) ) {
			// @since 1.8 - If direct edit page or post with Bricks, use the $post_id (#861m48kv4)
			$template_preview_post_id = ( $template_preview_type === 'direct-edit' ) ? $post_id : Helpers::get_template_setting( 'templatePreviewPostId', $post_id );

			if ( $template_preview_post_id ) {
				// Set the global $post to affect the entire WP environment (needed for WooCommerce)
				global $post;
				$post = get_post( $template_preview_post_id );
				setup_postdata( $post );

				// Set the preview ID as the Post ID before render this element (@since 1.5.7)
				$this->set_post_id( $template_preview_post_id );
			}
		}

		// STEP: Populate query_args from populate content settings. Moved the logic to helpers class (@since 1.9.1)
		$query_args = Helpers::get_template_preview_query_vars( $post_id );

		// Init query with template preview args
		if ( ! empty( $query_args ) && is_array( $query_args ) ) {
			// Store $wp_query in $original_query to restore it via restore_query() after element has been rendered
			global $wp_query;
			$this->original_query = $wp_query;
			// This is still needed in template preview (i.e. Pagination element if targeting main query)
			$wp_query = new \WP_Query( $query_args );
		}
	}

	/**
	 * Restore custom query after element render()
	 *
	 * @since 1.0
	 */
	public function restore_query() {
		if ( ! $this->original_query ) {
			return;
		}

		global $wp_query;

		$wp_query = $this->original_query;

		// Need to reset the global $post environment because on setup_query() we might have change it
		wp_reset_postdata();
	}

	/**
	 * Render control 'icon' HTML (either font icon 'i' or 'svg' HTML)
	 *
	 * @param array $icon Contains either 'icon' CSS class or 'svg' URL data.
	 * @param array $attributes Additional icon HTML attributes.
	 *
	 * @see ControlIcon.vue
	 * @return string SVG HMTL string
	 *
	 * @since 1.2.1
	 */
	public static function render_icon( $icon, $attributes = [] ) {
		if ( ! is_array( $attributes ) ) {
			$attributes = [];
		}

		// Is flat array (key is index, not an attribute name): Items are list of class names
		if ( isset( $attributes[0] ) ) {
			$attributes = [
				'class' => $attributes,
			];
		}

		$classes = [];

		// STEP: Render SVG
		$svg_id = ! empty( $icon['svg']['id'] ) ? $icon['svg']['id'] : false;

		if ( $svg_id ) {
			$svg_path = get_attached_file( $svg_id );
			$svg      = Helpers::file_get_contents( $svg_path );

			if ( ! $svg ) {
				return;
			}

			if ( isset( $icon['fill'] ) ) {
				$classes[] = 'fill';
			}

			if ( isset( $icon['stroke'] ) ) {
				$classes[] = 'stroke';
			}

			$attributes['class'] = empty( $attributes['class'] ) ? $classes : array_merge( $attributes['class'], $classes );

			// Add attributes to SVG HTML string
			return self::render_svg( $svg, $attributes );
		}

		// STEP: Render icon font
		elseif ( ! empty( $icon['icon'] ) ) {
			$classes[] = $icon['icon'];

			$attributes['class'] = empty( $attributes['class'] ) ? $classes : array_merge( $attributes['class'], $classes );

			$attributes = self::stringify_attributes( $attributes );

			return "<i {$attributes}></i>";
		}
	}

	/**
	 * Add attributes to SVG HTML string
	 *
	 * @since 1.4
	 */
	public static function render_svg( $svg = '', $attributes = [] ) {
		// STEP: Remove any potential "<xml " code before <svg
		$svg_tag_start = strpos( $svg, '<svg ' );
		$svg           = substr_replace( $svg, '', 0, $svg_tag_start );

		// STEP: Remove the custom HTML ID (if any) to avoid conflict with the default element ID
		preg_match( '/<svg ([a-z][a-z0-9]*)[^>]*?(\/?)>/i', $svg, $matches );

		$svg_tag = ! empty( $matches[0] ) ? $matches[0] : false;

		if ( $svg_tag ) {
			$svg_without_id = preg_replace( '/id="([\w-]*)"/i', '', $svg_tag );

			$svg = str_replace( $svg_tag, $svg_without_id, $svg );
		}

		// STEP: add the new attributes
		foreach ( $attributes as $key => $values ) {
			$start = strpos( $svg, $key . '="' );
			$end   = strpos( $svg, '>' );

			$value = is_array( $values ) ? join( ' ', $values ) : $values;

			// Add values to existing attribute
			if ( $start && $start < $end ) {
				$svg = substr_replace( $svg, "$value ", $start + strlen( $key ) + 2, 0 );
			}

			// Create attribute + value on node
			else {
				$attribute_string = $key . '="' . $value . '" ';

				$svg = substr_replace( $svg, $attribute_string, 5, 0 );
			}
		}

		return trim( $svg );
	}

	/**
	 * Change query if we are previewing a CPT archive template (set in-builder via "Populated Content")
	 *
	 * @since 1.4
	 */
	public function maybe_set_preview_query( $query_vars, $settings, $element_id ) {
		$post_id = $this->post_id;

		// Return: Not a template OR no 'post_type' condition set
		if ( ! Helpers::is_bricks_template( $post_id ) || ! empty( $query_vars['post_type'] ) ) {
			return $query_vars;
		}

		$preview_type = Helpers::get_template_setting( 'templatePreviewType', $post_id );

		if ( $preview_type === 'archive-cpt' ) {
			$preview_post_type = Helpers::get_template_setting( 'templatePreviewPostType', $post_id );

			if ( $preview_post_type ) {
				$query_vars['post_type'] = $preview_post_type;
			}
		}

		return $query_vars;
	}

	/**
	 * Is layout element: Section, Container, Block, Div
	 *
	 * For element control visibility in builder (flex controls, shape divider, etc.)
	 *
	 * @return boolean
	 *
	 * @since 1.5
	 */
	public function is_layout_element() {
		$layout_element_names = [ 'section', 'container', 'block', 'div' ];

		// NOTE: Undocumented
		$layout_element_names = apply_filters( 'bricks/is_layout_element', $layout_element_names );

		return in_array( $this->name, $layout_element_names );
	}

	/**
	 * Generate breakpoint-specific @media rules for nav menu & mobile menu toggle
	 *
	 * If not set to 'always' or 'never'
	 *
	 * @since 1.5.1
	 */
	public function generate_mobile_menu_inline_css( $settings = [], $breakpoint = '' ) {
		$breakpoint_width    = ! empty( $breakpoint['width'] ) ? intval( $breakpoint['width'] ) : 0;
		$base_width          = Breakpoints::$base_width;
		$nav_menu_inline_css = '';

		if ( $breakpoint_width ) {
			if ( $breakpoint_width > $base_width ) {
				if ( Breakpoints::$is_mobile_first ) {
					$nav_menu_inline_css .= "@media (max-width: {$breakpoint_width}px) {\n";
				} else {
					$nav_menu_inline_css .= "@media (min-width: {$breakpoint_width}px) {\n";
				}
			} else {
				if ( Breakpoints::$is_mobile_first ) {
					$nav_menu_inline_css .= "@media (min-width: {$breakpoint_width}px) {\n";
				} else {
					$nav_menu_inline_css .= "@media (max-width: {$breakpoint_width}px) {\n";
				}
			}

			$element_id = $this->get_element_attribute_id();

			// Nav menu
			if ( $this->name === 'nav-menu' ) {
				$nav_menu_inline_css .= "#{$element_id} .bricks-nav-menu-wrapper { display: none; }\n";
				$nav_menu_inline_css .= "#{$element_id} .bricks-mobile-menu-toggle { display: block; }\n";
			}

			// Nav nested
			elseif ( $this->name === 'nav-nested' ) {
				$nav_menu_inline_css .= "#{$element_id} .brx-toggle-div { display: inline-flex; }\n";
				$nav_menu_inline_css .= "#{$element_id} .brxe-toggle { display: inline-flex; }\n";

				// NOTE: Using element ID doesn't allow "Nav items" settings to overwrite it
				// $nav_menu_inline_css .= "#{$element_id} .brx-nav-nested-items {
				$nav_menu_inline_css .= "[data-script-id={$this->id}] .brx-nav-nested-items {
					opacity: 0;
					visibility: hidden;
					gap: 0;
					position: fixed;
					z-index: 1001;
					top: 0;
					right: 0;
					bottom: 0;
					left: 0;
					display: flex;
					align-items: center;
					justify-content: center;
					flex-direction: column;
					background-color: #fff;
					overflow-y: scroll;
					flex-wrap: nowrap;
				}\n";

				$nav_menu_inline_css .= "#{$element_id}.brx-open .brx-nav-nested-items {
					opacity: 1;
					visibility: visible;
				}\n";
			}

			$nav_menu_inline_css .= '}';
		}

		return $nav_menu_inline_css;
	}

	/**
	 * Return true if any of the element classes contains a match
	 *
	 * @param array $values_to_check Array of values to check the global class settings for.
	 *
	 * @see image.php 'popupOverlay', video.php 'overlay', etc.
	 *
	 * @since 1.7.1
	 */
	public function element_classes_have( $values_to_check = [] ) {
		if ( ! is_array( $values_to_check ) ) {
			$values_to_check = [ $values_to_check ];
		}

		$element_classes = ! empty( $this->settings['_cssGlobalClasses'] ) ? $this->settings['_cssGlobalClasses'] : false;

		if ( ! $element_classes ) {
			return false;
		}

		$class_has = false;

		$global_classes = Database::$global_data['globalClasses'];

		// Loop over element class settings
		foreach ( $element_classes as $element_class ) {
			$class_index = array_search( $element_class, array_column( $global_classes, 'id' ) );

			if ( empty( $global_classes[ $class_index ] ) ) {
				continue;
			}

			foreach ( $values_to_check as $value ) {
				// Global class has setting with $value
				if ( strpos( wp_json_encode( $global_classes[ $class_index ] ), $value ) ) {
					$class_has = true;
				}
			}
		}

		return $class_has;
	}
}
