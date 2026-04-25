<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Nav_Nested extends Element {
	public $category = 'general';
	public $name     = 'nav-nested';
	public $icon     = 'ti-menu';
	public $tag      = 'nav';
	public $scripts  = [ 'bricksNavNested', 'bricksSubmenuListeners', 'bricksSubmenuPosition' ];
	public $nestable = true;

	public function get_label() {
		return esc_html__( 'Nav', 'bricks' ) . ' (' . esc_html__( 'Nestable', 'bricks' ) . ')';
	}

	public function get_keywords() {
		return [ 'menu', 'nestable' ];
	}

	public function set_control_groups() {
		$this->control_groups['item'] = [
			'title' => esc_html__( 'Top level', 'bricks' ) . ' (' . esc_html__( 'Item', 'bricks' ) . ')',
		];

		$this->control_groups['dropdown'] = [
			'title' => esc_html__( 'Dropdown', 'bricks' ),
		];

		$this->control_groups['mobile-menu'] = [
			'title' => esc_html__( 'Mobile menu', 'bricks' ),
		];
	}

	public function set_controls() {
		// Apply transitions to menu items (@since 1.8.2)
		$this->controls['_cssTransition']['css'] = [
			[
				'property' => 'transition',
				'selector' => '.menu-item',
			],
			[
				'property' => 'transition',
				'selector' => '.menu-item a',
			],
			[
				'property' => 'transition',
				'selector' => '.brx-submenu-toggle > *',
			],
			[
				'property' => 'transition',
				'selector' => '.brxe-dropdown',
			],
			[
				'property' => 'transition',
				'selector' => '.brx-dropdown-content a',
			],
		];

		$this->controls['tag'] = [
			'label'          => esc_html__( 'HTML tag', 'bricks' ),
			'type'           => 'text',
			'lowercase'      => true,
			'inline'         => true,
			'fullAccess'     => true,
			'hasDynamicData' => false,
			'placeholder'    => 'nav',
		];

		$this->controls['ariaLabel'] = [
			'label'          => 'aria-label',
			'type'           => 'text',
			'inline'         => true,
			'hasDynamicData' => false,
			'placeholder'    => esc_html__( 'Menu', 'bricks' ),
		];

		// TOP LEVEL (ITEM)

		$this->controls['gap'] = [
			'group' => 'item',
			'label' => esc_html__( 'Gap', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'css'   => [
				[
					'property' => 'gap',
					'selector' => '.brx-nav-nested-items',
				],
			],
		];

		$this->controls['itemJustifyContent'] = [
			'group'     => 'item',
			'label'     => esc_html__( 'Justify content', 'bricks' ),
			'type'      => 'justify-content',
			'direction' => 'row',
			'exclude'   => [
				'space',
			],
			'inline'    => true,
			'css'       => [
				[
					'property' => 'justify-content',
					'selector' => '&.brx-open .brx-nav-nested-items > *',
				],
				[
					'property' => 'justify-content',
					'selector' => '&.brx-open .brx-submenu-toggle',
				],
			],
		];

		$this->controls['itemPadding'] = [
			'group' => 'item',
			'label' => esc_html__( 'Padding', 'bricks' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'padding',
					'selector' => '.brx-nav-nested-items > li > a',
				],
				[
					'property' => 'padding',
					'selector' => '.brx-nav-nested-items > li > .brx-submenu-toggle > *',
				],
				// Close mobile menu toggle
				[
					'property' => 'padding',
					'selector' => '&.brx-open .brx-nav-nested-items > li > button.brx-toggle-div',
				],
			],
		];

		$this->controls['itemBackgroundColor'] = [
			'group' => 'item',
			'label' => esc_html__( 'Background color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.brx-nav-nested-items > li{pseudo} > a',
				],
				[
					'property' => 'background-color',
					'selector' => '.brx-nav-nested-items > li{pseudo} > .brx-submenu-toggle',
				],
			],
		];

		$this->controls['itemBorder'] = [
			'group' => 'item',
			'label' => esc_html__( 'Border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.brx-nav-nested-items > li{pseudo} > a',
				],
				[
					'property' => 'border',
					'selector' => '.brx-nav-nested-items > li{pseudo} > .brx-submenu-toggle',
				],
			],
		];

		$this->controls['itemTypography'] = [
			'group' => 'item',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.brx-nav-nested-items > li{pseudo} > a',
				],
				[
					'property' => 'font',
					'selector' => '.brx-nav-nested-items > li{pseudo} > .brx-submenu-toggle > *',
				],
			],
		];

		$this->controls['itemTransform'] = [
			'group' => 'item',
			'label' => esc_html__( 'Transform', 'bricks' ),
			'type'  => 'transform',
			'css'   => [
				[
					'property' => 'transform',
					'selector' => '.brx-nav-nested-items > li{pseudo} > a',
				],
				[
					'property' => 'transform',
					'selector' => '.brx-nav-nested-items > li{pseudo} > .brx-submenu-toggle',
				],
			],
		];

		$this->controls['itemTransition'] = [
			'group'          => 'item',
			'label'          => esc_html__( 'Transition', 'bricks' ),
			'type'           => 'text',
			'hasDynamicData' => false,
			'inline'         => true,
			'css'            => [
				[
					'property' => 'transition',
					'selector' => '.brx-nav-nested-items > li',
				],
				[
					'property' => 'transition',
					'selector' => '.brx-nav-nested-items > li{pseudo} > a',
				],
				[
					'property' => 'transition',
					'selector' => '.brx-nav-nested-items > li{pseudo} > .brx-submenu-toggle',
				],
				[
					'property' => 'transition',
					'selector' => '.brx-nav-nested-items > li{pseudo} > .brx-submenu-toggle > *',
				],
			],
		];

		// ACTIVE LINK (CURRENT PAGE)

		$this->controls['itemActiveSep'] = [
			'group' => 'item',
			'label' => esc_html__( 'Active', 'bricks' ),
			'type'  => 'separator',
		];

		$this->controls['itemBackgroundColorActive'] = [
			'group' => 'item',
			'label' => esc_html__( 'Background color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.brx-nav-nested-items > li{pseudo} > [aria-current="page"]',
				],
				[
					'property' => 'background-color',
					'selector' => '.brx-nav-nested-items > li{pseudo} > .brx-submenu-toggle.aria-current',
				],
			],
		];

		$this->controls['itemBorderActive'] = [
			'group' => 'item',
			'label' => esc_html__( 'Border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.brx-nav-nested-items > li{pseudo} > [aria-current="page"]',
				],
				[
					'property' => 'border',
					'selector' => '.brx-nav-nested-items > li{pseudo} > .brx-submenu-toggle.aria-current',
				],
			],
		];

		$this->controls['itemTypographyActive'] = [
			'group' => 'item',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.brx-nav-nested-items > li{pseudo} > [aria-current="page"]',
				],
				[
					'property' => 'font',
					'selector' => '.brx-nav-nested-items > li{pseudo} > .brx-submenu-toggle.aria-current > *',
				],
			],
		];

		// DROPDOWN

		// DROPDOWN - ICON

		$this->controls['iconSep'] = [
			'group'       => 'dropdown',
			'type'        => 'separator',
			'label'       => esc_html__( 'Icon', 'bricks' ),
			'description' => esc_html__( 'Edit dropdown to set icon individually.', 'bricks' ),
		];

		$this->controls['iconPadding'] = [
			'group' => 'dropdown',
			'label' => esc_html__( 'Icon padding', 'bricks' ),
			'type'  => 'spacing',
			'css'   => [
				// Apply to top level (@since 1.8.4)
				[
					'property' => 'padding',
					'selector' => '.brx-nav-nested-items > li > .brx-submenu-toggle button',
				],
				[
					'property' => 'padding',
					'selector' => '.brx-submenu-toggle button',
				],
			],
		];

		$this->controls['iconGap'] = [
			'group' => 'dropdown',
			'label' => esc_html__( 'Gap', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'large' => true,
			'css'   => [
				[
					'property' => 'gap',
					'selector' => '.brx-submenu-toggle',
				],
			],
		];

		$this->controls['iconPosition'] = [
			'group'       => 'dropdown',
			'label'       => esc_html__( 'Icon position', 'bricks' ),
			'type'        => 'select',
			'options'     => $this->control_options['iconPosition'],
			'inline'      => true,
			'placeholder' => esc_html__( 'Right', 'bricks' ),
			'css'         => [
				[
					'selector' => '.brx-submenu-toggle',
					'property' => 'flex-direction',
					'value'    => 'row-reverse',
					'required' => 'left',
				],
			],
		];

		$this->controls['iconSize'] = [
			'group' => 'dropdown',
			'label' => esc_html__( 'Icon size', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'css'   => [
				[
					'property' => 'font-size',
					'selector' => '.brx-submenu-toggle button',
				],
			],
		];

		$this->controls['iconColor'] = [
			'group' => 'dropdown',
			'label' => esc_html__( 'Icon color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'color',
					'selector' => '.brx-submenu-toggle button',
				],
			],
		];

		$this->controls['iconTransform'] = [
			'group'  => 'dropdown',
			'type'   => 'transform',
			'label'  => esc_html__( 'icon transform', 'bricks' ),
			'inline' => true,
			'small'  => true,
			'css'    => [
				[
					'property' => 'transform',
					'selector' => '.brx-submenu-toggle button',
				],
			],
		];

		$this->controls['iconTransformOpen'] = [
			'group'  => 'dropdown',
			'type'   => 'transform',
			'label'  => esc_html__( 'Icon transform', 'bricks' ) . ' (' . esc_html__( 'Open', 'bricks' ) . ')',
			'inline' => true,
			'small'  => true,
			'css'    => [
				[
					'property' => 'transform',
					'selector' => '.brx-submenu-toggle button[aria-expanded="true"]',
				],
			],
		];

		$this->controls['iconTransition'] = [
			'group'          => 'dropdown',
			'label'          => esc_html__( 'Icon transition', 'bricks' ),
			'type'           => 'text',
			'hasDynamicData' => false,
			'inline'         => true,
			'css'            => [
				[
					'property' => 'transition',
					'selector' => '.brx-submenu-toggle button[aria-expanded]',
				],
			],
		];

		// DROPDOWN - CONTENT

		$this->controls['dropdownContentSep'] = [
			'group'       => 'dropdown',
			'type'        => 'separator',
			'label'       => esc_html__( 'Content', 'bricks' ),
			'description' => esc_html__( 'Sub menu, mega menu, or multilevel area.', 'bricks' ),
		];

		$this->controls['dropdownContentWidth'] = [
			'group'    => 'dropdown',
			'label'    => esc_html__( 'Min. width', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'large'    => true,
			'css'      => [
				[
					'property' => 'min-width',
					'selector' => '.brx-dropdown-content',
				],
			],
			'rerender' => true,
		];

		$this->controls['dropdownBackgroundColor'] = [
			'group' => 'dropdown',
			'label' => esc_html__( 'Background color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.brx-dropdown-content',
				],
			],
		];

		$this->controls['dropdownBorder'] = [
			'group' => 'dropdown',
			'label' => esc_html__( 'Border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.brx-dropdown-content',
				],
			],
		];

		$this->controls['dropdownBoxShadow'] = [
			'group' => 'dropdown',
			'label' => esc_html__( 'Box shadow', 'bricks' ),
			'type'  => 'box-shadow',
			'css'   => [
				[
					'property' => 'box-shadow',
					'selector' => '.brx-dropdown-content',
				],
			],
		];

		$this->controls['dropdownTypography'] = [
			'group' => 'dropdown',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.brx-dropdown-content',
				],
			],
		];

		$this->controls['dropdownTransform'] = [
			'group'  => 'dropdown',
			'type'   => 'transform',
			'label'  => esc_html__( 'Transform', 'bricks' ),
			'inline' => true,
			'small'  => true,
			'css'    => [
				[
					'property' => 'transform',
					'selector' => '.brx-nav-nested-items > .brxe-dropdown > .brx-dropdown-content',
				],
			],
		];

		$this->controls['dropdownTransformOpen'] = [
			'group'  => 'dropdown',
			'type'   => 'transform',
			'label'  => esc_html__( 'Transform', 'bricks' ) . ' (' . esc_html__( 'Open', 'bricks' ) . ')',
			'inline' => true,
			'small'  => true,
			'css'    => [
				[
					'property' => 'transform',
					'selector' => '.brx-nav-nested-items > .brxe-dropdown.open > .brx-dropdown-content',
				],
			],
		];

		$this->controls['dropdownTransition'] = [
			'group'          => 'dropdown',
			'label'          => esc_html__( 'Transition', 'bricks' ),
			'type'           => 'text',
			'hasDynamicData' => false,
			'inline'         => true,
			'css'            => [
				[
					'property' => 'transition',
					'selector' => '.brx-dropdown-content',
				],
			],
		];

		$this->controls['dropdownZindex'] = [
			'group'       => 'dropdown',
			'label'       => esc_html__( 'Z-index', 'bricks' ),
			'type'        => 'number',
			'large'       => true,
			'css'         => [
				[
					'property' => 'z-index',
					'selector' => '.brxe-dropdown',
				],
			],
			'placeholder' => 1001,
		];

		// DROPDOWN - ITEM

		$this->controls['dropdownItemSep'] = [
			'group' => 'dropdown',
			'label' => esc_html__( 'Item', 'bricks' ),
			'type'  => 'separator',
		];

		$this->controls['dropdownPadding'] = [
			'group' => 'dropdown',
			'label' => esc_html__( 'Padding', 'bricks' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'padding',
					'selector' => '.brx-dropdown-content > li > a',
				],
				[
					'property' => 'padding',
					'selector' => '.brx-dropdown-content .brx-submenu-toggle > *',
				],
			],
		];

		$this->controls['dropdownItemBackground'] = [
			'group' => 'dropdown',
			'label' => esc_html__( 'Background', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.brx-dropdown-content > li',
				],
				[
					'property' => 'background-color',
					'selector' => '.brx-dropdown-content .brx-submenu-toggle',
				],
			],
		];

		$this->controls['dropdownItemBorder'] = [
			'group' => 'dropdown',
			'label' => esc_html__( 'Border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.brx-dropdown-content > li:not(.brxe-dropdown)',
				],
				[
					'property' => 'border',
					'selector' => '.brx-dropdown-content .brx-submenu-toggle',
				],
			],
		];

		$this->controls['dropdownItemTypography'] = [
			'group' => 'dropdown',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.brx-dropdown-content > li > a',
				],
				[
					'property' => 'font',
					'selector' => '.brx-dropdown-content .brx-submenu-toggle > *',
				],
			],
		];

		$this->controls['dropdownItemTransition'] = [
			'group'          => 'dropdown',
			'label'          => esc_html__( 'Transition', 'bricks' ),
			'type'           => 'text',
			'hasDynamicData' => false,
			'inline'         => true,
			'css'            => [
				[
					'property' => 'transition',
					'selector' => '.brx-dropdown-content > li',
				],
				[
					'property' => 'transition',
					'selector' => '.brx-dropdown-content > li > a',
				],
				[
					'property' => 'transition',
					'selector' => '.brx-dropdown-content .brx-submenu-toggle',
				],
				[
					'property' => 'transition',
					'selector' => '&.brx-has-megamenu .brx-dropdown-content > *',
				],
			],
		];

		// DROPDOWN: MULTILEVEL

		$this->controls['multiLevelSep'] = [
			'group'       => 'dropdown',
			'label'       => esc_html__( 'Multilevel', 'bricks' ),
			'type'        => 'separator',
			'description' => esc_html__( 'Show only active dropdown. Toggle on click. Inner dropdowns inherit multilevel.', 'bricks' ),
		];

		$this->controls['multiLevel'] = [
			'group' => 'dropdown',
			'label' => esc_html__( 'Enable', 'bricks' ),
			'type'  => 'checkbox',
		];

		$this->controls['multiLevelBackText'] = [
			'group'    => 'dropdown',
			'label'    => esc_html__( 'Back', 'bricks' ) . ': ' . esc_html__( 'Text', 'bricks' ),
			'type'     => 'text',
			'inline'   => true,
			'required' => [ 'multiLevel', '=', true ],
		];

		$this->controls['multiLevelBackTypography'] = [
			'group'    => 'dropdown',
			'label'    => esc_html__( 'Back', 'bricks' ) . ': ' . esc_html__( 'Typography', 'bricks' ),
			'type'     => 'typography',
			'inline'   => true,
			'required' => [ 'multiLevel', '=', true ],
			'css'      => [
				[
					'property' => 'font',
					'selector' => '.brx-multilevel-back',
				],
			],
		];

		$this->controls['multiLevelBackBackground'] = [
			'group'    => 'dropdown',
			'label'    => esc_html__( 'Back', 'bricks' ) . ': ' . esc_html__( 'Background', 'bricks' ),
			'type'     => 'color',
			'inline'   => true,
			'required' => [ 'multiLevel', '=', true ],
			'css'      => [
				[
					'property' => 'background-color',
					'selector' => '.brx-multilevel-back',
				],
			],
		];

		// MOBILE MENU

		$this->controls['mobileMenuSep'] = [
			'group'       => 'mobile-menu',
			'type'        => 'separator',
			'description' => esc_html__( 'Insert "Toggle" element after "Nav items" to show/hide your mobile menu.', 'bricks' ),
		];

		/**
		 * NOTE: Undocumented '_addedClasses' controlKey
		 *
		 * Stored in builder state only. Not saved as setting.
		 *
		 * Processed in ControlCheckbox.vue to add additional 'class' in builder while editing.
		 *
		 * @since 1.8
		 */
		$this->controls['_addedClasses'] = [
			'group' => 'mobile-menu',
			'label' => esc_html__( 'Keep open while styling', 'bricks' ),
			'type'  => 'checkbox',
			'class' => 'brx-open',
		];

		// Show mobile menu toggle on breakpoint
		$breakpoints        = Breakpoints::$breakpoints;
		$breakpoint_options = [];

		foreach ( $breakpoints as $index => $breakpoint ) {
			$breakpoint_options[ $breakpoint['key'] ] = $breakpoint['label'];
		}

		$breakpoint_options['always'] = esc_html__( 'Always', 'bricks' );
		$breakpoint_options['never']  = esc_html__( 'Never', 'bricks' );

		$this->controls['mobileMenu'] = [
			'group'       => 'mobile-menu',
			'label'       => Breakpoints::$is_mobile_first ? esc_html__( 'Hide at breakpoint', 'bricks' ) : esc_html__( 'Show at breakpoint', 'bricks' ),
			'type'        => 'select',
			'options'     => $breakpoint_options,
			'rerender'    => true,
			'placeholder' => esc_html__( 'Mobile landscape', 'bricks' ),
		];

		$this->controls['mobileMenuWidth'] = [
			'group' => 'mobile-menu',
			'label' => esc_html__( 'Width', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'large' => true,
			'css'   => [
				[
					'property' => 'width',
					'selector' => '&.brx-open .brx-nav-nested-items',
				],
			],
		];

		$this->controls['mobileMenuHeight'] = [
			'group' => 'mobile-menu',
			'label' => esc_html__( 'Height', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'large' => true,
			'css'   => [
				[
					'property' => 'height',
					'selector' => '&.brx-open .brx-nav-nested-items',
				],
			],
		];

		$this->controls['mobileMenuAlignItems'] = [
			'group' => 'mobile-menu',
			'label' => esc_html__( 'Align items', 'bricks' ),
			'type'  => 'align-items',
			'css'   => [
				[
					'property' => 'align-items',
					'selector' => '&.brx-open .brx-nav-nested-items',
				],
			],
		];

		$this->controls['mobileMenuJustifyContent'] = [
			'group' => 'mobile-menu',
			'label' => esc_html__( 'Justify content', 'bricks' ),
			'type'  => 'justify-content',
			'css'   => [
				[
					'property' => 'justify-content',
					'selector' => '&.brx-open .brx-nav-nested-items',
				],
			],
		];

		$this->controls['mobileMenuPosition'] = [
			'group' => 'mobile-menu',
			'label' => esc_html__( 'Position', 'bricks' ),
			'type'  => 'dimensions',
			'css'   => [
				[
					'selector' => '&.brx-open .brx-nav-nested-items',
				],
				[
					'selector' => '&.brx-open .brx-nav-nested-items',
					'property' => 'width',
					'value'    => 'auto',
				],
			],
		];

		$this->controls['mobileMenuBackgroundColor'] = [
			'group' => 'mobile-menu',
			'label' => esc_html__( 'Background color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '&.brx-open .brx-nav-nested-items',
				],
			],
		];

		// MOBILE MENU: ITEM

		$this->controls['mobileMenuItemSep'] = [
			'group' => 'mobile-menu',
			'type'  => 'separator',
			'label' => esc_html__( 'Item', 'bricks' ),
		];

		$this->controls['mobileMenuItemAlignItems'] = [
			'group'   => 'mobile-menu',
			'label'   => esc_html__( 'Align items', 'bricks' ),
			'type'    => 'align-items',
			'exclude' => [
				'stretch',
			],
			'inline'  => true,
			'css'     => [
				[
					'property' => 'justify-content',
					'selector' => '&.brx-open .brx-submenu-toggle',
					'value'    => 'flex-start',
					'required' => 'flex-start',
				],
				[
					'property' => 'text-align',
					'selector' => '&.brx-open li.menu-item',
					'value'    => 'left',
					'required' => 'flex-start',
				],

				[
					'property' => 'justify-content',
					'selector' => '&.brx-open .brx-submenu-toggle',
					'value'    => 'center',
					'required' => 'center',
				],
				[
					'property' => 'text-align',
					'selector' => '&.brx-open li.menu-item',
					'value'    => 'center',
					'required' => 'center',
				],

				[
					'property' => 'justify-content',
					'selector' => '&.brx-open .brx-submenu-toggle',
					'value'    => 'flex-end',
					'required' => 'flex-end',
				],
				[
					'property' => 'text-align',
					'selector' => '&.brx-open li.menu-item',
					'value'    => 'right',
					'required' => 'flex-end',
				],
			],
		];
	}

	public function get_nestable_children() {
		$children = [];

		// Text link 1
		$children[] = [
			'name'     => 'text-link',
			'label'    => 'Nav link',
			'settings' => [
				'text' => 'Home',
				'link' => [
					'type' => 'external',
					'url'  => '#',
				],
			],
		];

		// Text link 2
		$children[] = [
			'name'     => 'text-link',
			'label'    => 'Nav link',
			'settings' => [
				'text' => 'About',
				'link' => [
					'type' => 'external',
					'url'  => '#',
				],
			],
		];

		$dropdown_element = Elements::get_element( [ 'name' => 'dropdown' ] );

		$dropdown_children = ! empty( $dropdown_element['nestableChildren'] ) ? $dropdown_element['nestableChildren'] : [];

		$children[] = [
			'name'     => 'dropdown',
			'label'    => esc_html__( 'Dropdown', 'bricks' ),
			'children' => $dropdown_children,
			'settings' => [
				'text' => 'Dropdown',
			],
		];

		// Toggle (close mobile menu)
		$children[] = [
			'name'     => 'toggle',
			'label'    => esc_html__( 'Toggle', 'bricks' ) . ' (' . esc_html__( 'Close', 'bricks' ) . ': ' . esc_html__( 'Mobile', 'bricks' ) . ')',
			'settings' => [
				'_hidden' => [
					'_cssClasses' => 'brx-toggle-div',
				],
			],
		];

		return [
			// Nav items
			[
				'name'      => 'block',
				'label'     => esc_html__( 'Nav items', 'bricks' ),
				'children'  => $children,
				'deletable' => false, // Prevent deleting this element directly. NOTE: Undocumented (@since 1.8)
				'cloneable' => false, // Prevent cloning this element directly.  NOTE: Undocumented (@since 1.8)
				'settings'  => [
					'tag'     => 'ul',
					'_hidden' => [
						'_cssClasses' => 'brx-nav-nested-items',
					],
				],
			],

			// Toggle (open mobile menu)
			[
				'name'     => 'toggle',
				'label'    => esc_html__( 'Toggle', 'bricks' ) . ' (' . esc_html__( 'Open', 'bricks' ) . ': ' . esc_html__( 'Mobile', 'bricks' ) . ')',
				'settings' => [],
			],
		];
	}

	public function render() {
		$settings = $this->settings;

		$this->set_attribute( '_root', 'aria-label', ! empty( $settings['ariaLabel'] ) ? esc_attr( $settings['ariaLabel'] ) : esc_html__( 'Menu', 'bricks' ) );

		// Nav button: Show at breakpoint
		$show_nav_button_at = ! empty( $settings['mobileMenu'] ) ? $settings['mobileMenu'] : 'mobile_landscape';

		// Is mobile-first: Swap always <> never
		if ( Breakpoints::$is_mobile_first ) {
			if ( $show_nav_button_at === 'always' ) {
				$show_nav_button_at = 'never';
			} elseif ( $show_nav_button_at === 'never' ) {
				$show_nav_button_at = 'always';
			}
		}

		$this->set_attribute( '_root', 'data-toggle', $show_nav_button_at );

		// Multi level
		if ( isset( $settings['multiLevel'] ) ) {
			$this->set_attribute( '_root', 'class', 'multilevel' );
			$this->set_attribute( '_root', 'data-back-text', ! empty( $settings['multiLevelBackText'] ) ? esc_attr( $settings['multiLevelBackText'] ) : esc_html__( 'Back', 'bricks' ) );
		}

		$output = "<{$this->tag} {$this->render_attributes( '_root' )}>";

		$output .= Frontend::render_children( $this );

		if ( $show_nav_button_at !== 'never' ) {
			$nav_button_aria_label = ! empty( $settings['navButtonAriaLabel'] ) ? esc_attr( $settings['navButtonAriaLabel'] ) : esc_html__( 'Toggle menu', 'bricks' );

			// Builder: Add nav menu & mobile menu visibility via inline style
			if ( bricks_is_builder() || bricks_is_builder_call() ) {
				$breakpoint          = Breakpoints::get_breakpoint_by( 'key', $show_nav_button_at );
				$nav_menu_inline_css = $this->generate_mobile_menu_inline_css( $settings, $breakpoint );

				$output .= "<style>$nav_menu_inline_css</style>";
			}
		}

		$output .= "</{$this->tag}>";

		echo $output;
	}
}
