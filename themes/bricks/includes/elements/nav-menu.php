<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Nav_Menu extends Element {
	public $category          = 'wordpress';
	public $name              = 'nav-menu';
	public $icon              = 'ti-menu';
	public $custom_attributes = false;
	public $scripts           = [ 'bricksSubmenuListeners', 'bricksSubmenuPosition' ];
	public $wp_nav_menu_items = [];

	public function get_label() {
		return esc_html__( 'Nav Menu', 'bricks' );
	}

	public function set_control_groups() {
		$this->control_groups['menu'] = [
			'title' => esc_html__( 'Top level', 'bricks' ),
		];

		$this->control_groups['sub-menu'] = [
			'title' => esc_html__( 'Sub menu', 'bricks' ),
		];

		$this->control_groups['mobile-menu'] = [
			'title' => esc_html__( 'Mobile menu', 'bricks' ),
		];

		$this->control_groups['megamenu'] = [
			'title' => esc_html__( 'Mega menu', 'bricks' ),
		];

		$this->control_groups['multilevel'] = [
			'title' => esc_html__( 'Multilevel', 'bricks' ),
		];
	}

	public function set_controls() {
		// @since 1.4: Apply transitions to menu items
		$this->controls['_cssTransition']['css'] = [
			[
				'property' => 'transition',
				'selector' => '.bricks-nav-menu li',
			],
			[
				'property' => 'transition',
				'selector' => '.bricks-nav-menu li a',
			],
			[
				'property' => 'transition',
				'selector' => '.bricks-mobile-menu li a',
			],
		];

		$nav_menus = [];

		if ( bricks_is_builder() ) {
			foreach ( wp_get_nav_menus() as $menu ) {
				$nav_menus[ $menu->term_id ] = $menu->name;
			}
		}

		$this->controls['menu'] = [
			'label'       => esc_html__( 'Menu', 'bricks' ) . ' (WordPress)',
			'type'        => 'select',
			'options'     => $nav_menus,
			'placeholder' => esc_html__( 'Select nav menu', 'bricks' ),
			'description' => sprintf( '<a href="' . admin_url( 'nav-menus.php' ) . '" target="_blank">' . esc_html__( 'Manage my menus in WordPress.', 'bricks' ) . '</a>' ),
		];

		$this->controls['menuAlignment'] = [
			'label'  => esc_html__( 'Alignment', 'bricks' ),
			'type'   => 'direction',
			'css'    => [
				[
					'property' => 'flex-direction',
					'selector' => '.bricks-nav-menu',
				],
			],
			'inline' => true,
		];

		// TOP LEVEL

		$this->controls['menuJustifyContent'] = [
			'group'   => 'menu',
			'label'   => esc_html__( 'Justify content', 'bricks' ),
			'type'    => 'justify-content',
			'inline'  => true,
			'exclude' => 'space',
			'css'     => [
				[
					'property' => 'justify-content',
					'selector' => '.bricks-nav-menu > li > a',
				],
				[
					'property' => 'justify-content',
					'selector' => '.bricks-nav-menu > li > .brx-submenu-toggle',
				],
			],
		];

		$this->controls['menuGap'] = [
			'group' => 'menu',
			'label' => esc_html__( 'Gap', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'css'   => [
				[
					'property' => 'gap',
					'selector' => '.bricks-nav-menu',
				]
			],
		];

		$this->controls['menuMargin'] = [
			'group' => 'menu',
			'label' => esc_html__( 'Margin', 'bricks' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'margin',
					'selector' => '.bricks-nav-menu > li',
				]
			],
		];

		$this->controls['menuPadding'] = [
			'group' => 'menu',
			'label' => esc_html__( 'Padding', 'bricks' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'padding',
					'selector' => '.bricks-nav-menu > li > a',
				],
				[
					'property' => 'padding',
					'selector' => '.bricks-nav-menu > li > .brx-submenu-toggle > *',
				],
			],
		];

		$this->controls['menuBackground'] = [
			'group' => 'menu',
			'type'  => 'background',
			'label' => esc_html__( 'Background', 'bricks' ),
			'css'   => [
				[
					'property' => 'background',
					'selector' => '.bricks-nav-menu > li > a',
				],
				[
					'property' => 'background',
					'selector' => '.bricks-nav-menu > li > .brx-submenu-toggle',
				],
			],
		];

		$this->controls['menuBorder'] = [
			'group' => 'menu',
			'type'  => 'border',
			'label' => esc_html__( 'Border', 'bricks' ),
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.bricks-nav-menu > li > a',
				],
				[
					'property' => 'border',
					'selector' => '.bricks-nav-menu > li > .brx-submenu-toggle',
				],
			],
		];

		$this->controls['menuTypography'] = [
			'group' => 'menu',
			'type'  => 'typography',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.bricks-nav-menu > li{pseudo} > a',
				],
				[
					'property' => 'font',
					'selector' => '.bricks-nav-menu > li{pseudo} > .brx-submenu-toggle > *',
				],
			],
		];

		$this->controls['menuActiveSep'] = [
			'group' => 'menu',
			'type'  => 'separator',
			'label' => esc_html__( 'Active', 'bricks' ),
		];

		$this->controls['menuActiveBackground'] = [
			'group' => 'menu',
			'label' => esc_html__( 'Active background', 'bricks' ),
			'type'  => 'background',
			'css'   => [
				[
					'property' => 'background',
					'selector' => '.bricks-nav-menu > .current-menu-item > a',
				],
				[
					'property' => 'background',
					'selector' => '.bricks-nav-menu > .current-menu-item > .brx-submenu-toggle',
				],
				// Submenu is current page: Apply top-level active state
				[
					'property' => 'background',
					'selector' => '.bricks-nav-menu > .current-menu-parent > a',
				],
				[
					'property' => 'background',
					'selector' => '.bricks-nav-menu > .current-menu-parent > .brx-submenu-toggle',
				],
				// Sub-submenu is current page: Apply top-level active state
				[
					'property' => 'background',
					'selector' => '.bricks-nav-menu > .current-menu-ancestor > a',
				],
				[
					'property' => 'background',
					'selector' => '.bricks-nav-menu > .current-menu-ancestor > .brx-submenu-toggle',
				],
			],
		];

		$this->controls['menuActiveBorder'] = [
			'group' => 'menu',
			'label' => esc_html__( 'Active border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.bricks-nav-menu .current-menu-item > a',
				],
				[
					'property' => 'border',
					'selector' => '.bricks-nav-menu .current-menu-item > .brx-submenu-toggle',
				],
				// Submenu is current page: Apply top-level active state
				[
					'property' => 'border',
					'selector' => '.bricks-nav-menu > .current-menu-parent > a',
				],
				[
					'property' => 'border',
					'selector' => '.bricks-nav-menu > .current-menu-parent > .brx-submenu-toggle',
				],
				// Sub-submenu is current page: Apply top-level active state
				[
					'property' => 'border',
					'selector' => '.bricks-nav-menu > .current-menu-ancestor > a',
				],
				[
					'property' => 'border',
					'selector' => '.bricks-nav-menu > .current-menu-ancestor > .brx-submenu-toggle',
				],
			],
		];

		$this->controls['menuActiveTypography'] = [
			'group' => 'menu',
			'label' => esc_html__( 'Active typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.bricks-nav-menu .current-menu-item > a',
				],
				[
					'property' => 'font',
					'selector' => '.bricks-nav-menu .current-menu-item > .brx-submenu-toggle > *',
				],
				// Submenu is current page: Apply top-level active state
				[
					'property' => 'font',
					'selector' => '.bricks-nav-menu > .current-menu-parent > a',
				],
				[
					'property' => 'font',
					'selector' => '.bricks-nav-menu > .current-menu-parent > .brx-submenu-toggle > *',
				],
				// Sub-submenu is current page: Apply top-level active state
				[
					'property' => 'font',
					'selector' => '.bricks-nav-menu > .current-menu-ancestor > a',
				],
				[
					'property' => 'font',
					'selector' => '.bricks-nav-menu > .current-menu-ancestor > .brx-submenu-toggle > *',
				],
			],
		];

		// ICON

		$this->controls['menuIconSep'] = [
			'group' => 'menu',
			'type'  => 'separator',
			'label' => esc_html__( 'Icon', 'bricks' ),
		];

		$this->controls['menuIcon'] = [
			'group'    => 'menu',
			'label'    => esc_html__( 'Icon', 'bricks' ) . ' (' . esc_html__( 'Sub menu', 'bricks' ) . ')',
			'type'     => 'icon',
			'rerender' => true,
			'css'      => [
				[
					'selector' => 'button',
				],
			],
		];

		$this->controls['menuIconTransform'] = [
			'group' => 'menu',
			'label' => esc_html__( 'Icon transform', 'bricks' ),
			'type'  => 'transform',
			'css'   => [
				[
					'property' => 'transform',
					'selector' => '.bricks-nav-menu button[aria-expanded="false"] > *',
				],
			],
		];

		$this->controls['menuIconTransformOpen'] = [
			'group' => 'menu',
			'label' => esc_html__( 'Icon transform', 'bricks' ) . ' (' . esc_html__( 'Open', 'bricks' ) . ')',
			'type'  => 'transform',
			'css'   => [
				[
					'property' => 'transform',
					'selector' => '.bricks-nav-menu button[aria-expanded="true"] > *',
				],
			],
		];

		$this->controls['menuIconTypography'] = [
			'group'   => 'menu',
			'label'   => esc_html__( 'Icon typography', 'bricks' ),
			'type'    => 'typography',
			'css'     => [
				[
					'property' => 'font',
					'selector' => '.bricks-nav-menu > li.menu-item-has-children > .brx-submenu-toggle{pseudo} button[aria-expanded]',
				],
			],
			'exclude' => [
				'font-family',
				'font-weight',
				'font-style',
				'text-align',
				'text-decoration',
				'text-transform',
				'letter-spacing',
			],
		];

		$this->controls['menuIconPosition'] = [
			'group'       => 'menu',
			'label'       => esc_html__( 'Icon position', 'bricks' ),
			'type'        => 'select',
			'options'     => $this->control_options['iconPosition'],
			'inline'      => true,
			'placeholder' => esc_html__( 'Right', 'bricks' ),
		];

		$this->controls['menuIconMargin'] = [
			'group' => 'menu',
			'label' => esc_html__( 'Icon margin', 'bricks' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'margin',
					'selector' => '.bricks-nav-menu .brx-submenu-toggle button',
				],
			],
		];

		$this->controls['menuIconPadding'] = [
			'group' => 'menu',
			'label' => esc_html__( 'Icon padding', 'bricks' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'padding',
					'selector' => '.bricks-nav-menu .brx-submenu-toggle button',
				],
			],
		];

		// SUB MENU

		$this->controls['submenuStatic'] = [
			'group'       => 'sub-menu',
			'label'       => esc_html__( 'Position', 'bricks' ) . ': ' . esc_html__( 'Static', 'bricks' ),
			'type'        => 'checkbox',
			'description' => esc_html__( 'Enable to position in document flow (e.g. inside offcanvas).', 'bricks' ),
		];

		$this->controls['submenuStaticInfo'] = [
			'group'    => 'sub-menu',
			'type'     => 'info',
			'content'  => esc_html__( 'Static dropdown content always toggles on click, not hover.', 'bricks' ),
			'required' => [ 'submenuStatic', '!=', '' ],
		];

		$this->controls['subMenuBackgroundList'] = [
			'group'   => 'sub-menu',
			'type'    => 'background',
			'label'   => esc_html__( 'Background', 'bricks' ),
			'exclude' => 'video',
			'css'     => [
				[
					'property' => 'background',
					'selector' => '.bricks-nav-menu .sub-menu',
				]
			],
		];

		$this->controls['subMenuBorder'] = [
			'group' => 'sub-menu',
			'label' => esc_html__( 'Border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.bricks-nav-menu .sub-menu',
				]
			],
		];

		$this->controls['subMenuBoxShadow'] = [
			'group' => 'sub-menu',
			'label' => esc_html__( 'Box shadow', 'bricks' ),
			'type'  => 'box-shadow',
			'css'   => [
				[
					'property' => 'box-shadow',
					'selector' => '.bricks-nav-menu .sub-menu',
				],
			],
		];

		$this->controls['subMenuTransform'] = [
			'group' => 'sub-menu',
			'label' => esc_html__( 'Transform', 'bricks' ),
			'type'  => 'transform',
			'css'   => [
				[
					'property' => 'transform',
					'selector' => '.bricks-nav-menu > li > .sub-menu',
				],
				[
					'property' => 'transform',
					'selector' => '.bricks-nav-menu > li > .brx-megamenu',
				],
			],
		];

		$this->controls['subMenuTransformOpen'] = [
			'group' => 'sub-menu',
			'label' => esc_html__( 'Transform', 'bricks' ) . ' (' . esc_html__( 'Open', 'bricks' ) . ')',
			'type'  => 'transform',
			'css'   => [
				[
					'property' => 'transform',
					'selector' => '.bricks-nav-menu > li.open > .sub-menu',
				],
				[
					'property' => 'transform',
					'selector' => '.bricks-nav-menu > li.open > .brx-megamenu',
				],
			],
		];

		// CARET (add .caret to first-level submenu)
		$caret_selector = '.bricks-nav-menu > li > .sub-menu.caret::before';

		$this->controls['caretSep'] = [
			'group' => 'sub-menu',
			'label' => esc_html__( 'Caret', 'bricks' ),
			'type'  => 'separator',
		];

		// 'caretSize' adds class .caret to first-level submenu
		$this->controls['caretSize'] = [
			'group'    => 'sub-menu',
			'type'     => 'number',
			'units'    => true,
			'rerender' => true,
			'label'    => esc_html__( 'Size', 'bricks' ),
			'css'      => [
				[
					'property' => 'border-width',
					'selector' => $caret_selector,
				],
			],
		];

		$this->controls['caretColor'] = [
			'group'    => 'sub-menu',
			'type'     => 'color',
			'label'    => esc_html__( 'Color', 'bricks' ),
			'css'      => [
				[
					'property' => 'border-bottom-color',
					'selector' => $caret_selector,
				],
			],
			'required' => [ 'caretSize', '!=', 0 ],
		];

		$this->controls['caretTransform'] = [
			'group'    => 'sub-menu',
			'label'    => esc_html__( 'Transform', 'bricks' ),
			'type'     => 'transform',
			'css'      => [
				[
					'property' => 'transform',
					'selector' => $caret_selector,
				],
			],
			'required' => [ 'caretSize', '!=', 0 ],
		];

		$this->controls['caretPosition'] = [
			'group'    => 'sub-menu',
			'label'    => esc_html__( 'Position', 'bricks' ),
			'type'     => 'dimensions',
			'css'      => [
				[
					'selector' => $caret_selector,
				],
			],
			'required' => [ 'caretSize', '!=', 0 ],
		];

		// ITEM

		$this->controls['subMenuItemSep'] = [
			'group' => 'sub-menu',
			'label' => esc_html__( 'Item', 'bricks' ),
			'type'  => 'separator',
		];

		$this->controls['subMenuJustifyContent'] = [
			'group'   => 'sub-menu',
			'label'   => esc_html__( 'Justify content', 'bricks' ),
			'type'    => 'justify-content',
			'inline'  => true,
			'exclude' => 'space',
			'css'     => [
				[
					'property' => 'justify-content',
					'selector' => '.bricks-nav-menu .sub-menu a',
				],
				[
					'property' => 'justify-content',
					'selector' => '.bricks-nav-menu .sub-menu button',
				],
			],
		];

		$this->controls['subMenuPadding'] = [
			'group' => 'sub-menu',
			'type'  => 'spacing',
			'label' => esc_html__( 'Padding', 'bricks' ),
			'css'   => [
				[
					'property' => 'padding',
					'selector' => '.bricks-nav-menu .sub-menu a',
				],
				[
					'property' => 'padding',
					'selector' => '.bricks-nav-menu .sub-menu button',
				],
			],
		];

		$this->controls['subMenuBackground'] = [
			'group' => 'sub-menu',
			'type'  => 'background',
			'label' => esc_html__( 'Background', 'bricks' ),
			'css'   => [
				[
					'property' => 'background',
					'selector' => '.bricks-nav-menu .sub-menu .menu-item',
				]
			],
		];

		$this->controls['subMenuItemBorder'] = [
			'group' => 'sub-menu',
			'label' => esc_html__( 'Border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.bricks-nav-menu .sub-menu > li',
				],
			],
		];

		$this->controls['subMenuTypography'] = [
			'group' => 'sub-menu',
			'type'  => 'typography',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.bricks-nav-menu .sub-menu > li{pseudo} > a',
				],
				[
					'property' => 'font',
					'selector' => '.bricks-nav-menu .sub-menu > li{pseudo} > .brx-submenu-toggle > *',
				],
			],
		];

		// ACTIVE

		$this->controls['subMenuActiveSep'] = [
			'group' => 'sub-menu',
			'type'  => 'separator',
			'label' => esc_html__( 'Active', 'bricks' ),
		];

		$this->controls['subMenuActiveBackground'] = [
			'group' => 'sub-menu',
			'type'  => 'background',
			'label' => esc_html__( 'Active background', 'bricks' ),
			'css'   => [
				[
					'property' => 'background',
					'selector' => '.bricks-nav-menu .sub-menu > .current-menu-item > a',
				],
				[
					'property' => 'background',
					'selector' => '.bricks-nav-menu .sub-menu > .current-menu-item > .brx-submenu-toggle',
				],
				// Sub-submenu is current page: Apply top-level active state
				[
					'property' => 'background',
					'selector' => '.bricks-nav-menu .sub-menu > .current-menu-ancestor > a',
				],
				[
					'property' => 'background',
					'selector' => '.bricks-nav-menu .sub-menu > .current-menu-ancestor > .brx-submenu-toggle',
				],
			],
		];

		$this->controls['subMenuActiveBorder'] = [
			'group' => 'sub-menu',
			'type'  => 'border',
			'label' => esc_html__( 'Active border', 'bricks' ),
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.bricks-nav-menu .sub-menu > .current-menu-item > a',
				],
				[
					'property' => 'border',
					'selector' => '.bricks-nav-menu .sub-menu > .current-menu-item > .brx-submenu-toggle',
				],
				// Sub-submenu is current page: Apply top-level active state
				[
					'property' => 'border',
					'selector' => '.bricks-nav-menu .sub-menu > .current-menu-ancestor > a',
				],
				[
					'property' => 'border',
					'selector' => '.bricks-nav-menu .sub-menu > .current-menu-ancestor > .brx-submenu-toggle',
				],
			],
		];

		$this->controls['subMenuActiveTypography'] = [
			'group' => 'sub-menu',
			'label' => esc_html__( 'Active typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.bricks-nav-menu .sub-menu > .current-menu-item > a',
				],
				[
					'property' => 'font',
					'selector' => '.bricks-nav-menu .sub-menu > .current-menu-item > .brx-submenu-toggle > *',
				],
				// Sub-submenu is current page: Apply top-level active state
				[
					'property' => 'font',
					'selector' => '.bricks-nav-menu .sub-menu > .current-menu-ancestor > a',
				],
				[
					'property' => 'font',
					'selector' => '.bricks-nav-menu .sub-menu > .current-menu-ancestor > .brx-submenu-toggle > *',
				],
			],
		];

		// ICON

		$this->controls['subMenuIconSep'] = [
			'group' => 'sub-menu',
			'type'  => 'separator',
			'label' => esc_html__( 'Icon', 'bricks' ),
		];

		$this->controls['subMenuIcon'] = [
			'group'    => 'sub-menu',
			'label'    => esc_html__( 'Icon', 'bricks' ),
			'type'     => 'icon',
			'rerender' => true,
			'css'      => [
				[
					'selector' => '.bricks-nav-menu .sub-menu .brx-submenu-toggle{pseudo} button',
				],
			],
		];

		$this->controls['subMenuIconSize'] = [
			'group' => 'sub-menu',
			'label' => esc_html__( 'Icon size', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'css'   => [
				[
					'property' => 'height',
					'selector' => '.bricks-nav-menu .sub-menu .brx-submenu-toggle svg',
				],
				[
					'property' => 'width',
					'selector' => '.bricks-nav-menu .sub-menu .brx-submenu-toggle svg',
				],
				[
					'property' => 'font-size',
					'selector' => '.bricks-nav-menu .sub-menu .brx-submenu-toggle i',
				],
			],
		];

		$this->controls['subMenuIconTransform'] = [
			'group' => 'sub-menu',
			'label' => esc_html__( 'Icon transform', 'bricks' ),
			'type'  => 'transform',
			'css'   => [
				[
					'property' => 'transform',
					'selector' => '.bricks-nav-menu .sub-menu button > *',
				],
			],
		];

		$this->controls['subMenuIconTransformOpen'] = [
			'group' => 'sub-menu',
			'label' => esc_html__( 'Icon transform', 'bricks' ) . ' (' . esc_html__( 'Open', 'bricks' ) . ')',
			'type'  => 'transform',
			'css'   => [
				[
					'property' => 'transform',
					'selector' => '.bricks-nav-menu .sub-menu button[aria-expanded="true"] > *',
				],
			],
		];

		$this->controls['subMenuIconTypography'] = [
			'group'   => 'sub-menu',
			'label'   => esc_html__( 'Icon typography', 'bricks' ),
			'type'    => 'typography',
			'css'     => [
				[
					'property' => 'font',
					'selector' => '.bricks-nav-menu .sub-menu .brx-submenu-toggle > a{pseudo} + button',
				],
			],
			'exclude' => [
				'font-family',
				'font-weight',
				'font-style',
				'text-align',
				'text-decoration',
				'text-transform',
				'letter-spacing',
			],
		];

		$this->controls['subMenuIconPosition'] = [
			'group'       => 'sub-menu',
			'label'       => esc_html__( 'Icon position', 'bricks' ),
			'type'        => 'select',
			'options'     => $this->control_options['iconPosition'],
			'inline'      => true,
			'placeholder' => esc_html__( 'Right', 'bricks' ),
		];

		$this->controls['subMenuIconMargin'] = [
			'group' => 'sub-menu',
			'label' => esc_html__( 'Icon margin', 'bricks' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'margin',
					'selector' => '.bricks-nav-menu .sub-menu .brx-submenu-toggle button',
				],
			],
		];

		$this->controls['subMenuIconPadding'] = [
			'group' => 'sub-menu',
			'label' => esc_html__( 'Icon padding', 'bricks' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'padding',
					'selector' => '.bricks-nav-menu .sub-menu .brx-submenu-toggle button',
				],
			],
		];

		// MOBILE MENU

		// Get all breakpoints except base (@since 1.5.1)
		$breakpoints        = Breakpoints::$breakpoints;
		$breakpoint_options = [];

		foreach ( $breakpoints as $index => $breakpoint ) {
			if ( ! isset( $breakpoint['base'] ) ) {
				$breakpoint_options[ $breakpoint['key'] ] = $breakpoint['label'];
			}
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

		$this->controls['mobileMenuPosition'] = [
			'group'       => 'mobile-menu',
			'label'       => esc_html__( 'Position', 'bricks' ),
			'type'        => 'select',
			'small'       => true,
			'options'     => [
				'right' => esc_html__( 'Right', 'bricks' ),
				'left'  => esc_html__( 'Left', 'bricks' ),
			],
			'inline'      => true,
			'placeholder' => esc_html__( 'Left', 'bricks' ),
		];

		$this->controls['mobileMenuTop'] = [
			'group' => 'mobile-menu',
			'label' => esc_html__( 'Top', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'large' => true,
			'css'   => [
				[
					'selector' => '.bricks-mobile-menu-wrapper',
					'property' => 'top',
				],
			],
		];

		$this->controls['mobileMenuWidth'] = [
			'group'       => 'mobile-menu',
			'label'       => esc_html__( 'Width', 'bricks' ),
			'type'        => 'number',
			'units'       => true,
			'large'       => true,
			'css'         => [
				[
					'property' => 'width',
					'selector' => '.bricks-mobile-menu-wrapper',
				],
			],
			'placeholder' => '300px',
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
					'selector' => '.bricks-mobile-menu-wrapper',
				],
			],
		];

		$this->controls['mobileMenuFadeIn'] = [
			'group' => 'mobile-menu',
			'label' => esc_html__( 'Fade in', 'bricks' ),
			'type'  => 'checkbox',
		];

		$this->controls['mobileMenuAlignment'] = [
			'group'       => 'mobile-menu',
			'label'       => esc_html__( 'Vertical', 'bricks' ),
			'type'        => 'justify-content',
			'exclude'     => 'space',
			'css'         => [
				[
					'property' => 'justify-content',
					'selector' => '.bricks-mobile-menu-wrapper',
				]
			],
			'inline'      => true,
			'placeholder' => esc_html__( 'Top', 'bricks' ),
		];

		$this->controls['mobileMenuAlignItems'] = [
			'group'       => 'mobile-menu',
			'label'       => esc_html__( 'Horizontal', 'bricks' ),
			'type'        => 'align-items',
			'exclude'     => 'stretch',
			'css'         => [
				[
					'property' => 'align-items',
					'selector' => '.bricks-mobile-menu-wrapper',
				],
				[
					'property' => 'justify-content',
					'selector' => '.bricks-mobile-menu-wrapper .brx-submenu-toggle',
				],
				[
					'property' => 'width',
					'selector' => '.bricks-mobile-menu-wrapper a',
					'value'    => 'auto',
				],
			],
			'inline'      => true,
			'placeholder' => esc_html__( 'Top', 'bricks' ),
		];

		$this->controls['mobileMenuTextAlign'] = [
			'group'  => 'mobile-menu',
			'type'   => 'text-align',
			'label'  => esc_html__( 'Text align', 'bricks' ),
			'inline' => true,
			'css'    => [
				[
					'property' => 'text-align',
					'selector' => '.bricks-mobile-menu-wrapper',
				]
			],
		];

		$this->controls['mobileMenuBackground'] = [
			'group' => 'mobile-menu',
			'type'  => 'background',
			'label' => esc_html__( 'Background', 'bricks' ),
			'css'   => [
				[
					'property' => 'background',
					'selector' => '.bricks-mobile-menu-wrapper:before',
				]
			],
		];

		$this->controls['mobileMenuBackgroundFilters'] = [
			'group'         => 'mobile-menu',
			'label'         => esc_html__( 'Background filters', 'bricks' ),
			'titleProperty' => 'type',
			'type'          => 'filters',
			'css'           => [
				[
					'property' => 'filter',
					'selector' => '.bricks-mobile-menu-wrapper:before',
				],
			],
			'inline'        => true,
			'small'         => true,
		];

		$this->controls['mobileMenuBoxShadow'] = [
			'group' => 'mobile-menu',
			'label' => esc_html__( 'Box shadow', 'bricks' ),
			'type'  => 'box-shadow',
			'css'   => [
				[
					'property' => 'box-shadow',
					'selector' => '.bricks-mobile-menu-wrapper:before',
				],
			],
		];

		$this->controls['mobileMenuOverlay'] = [
			'group'   => 'mobile-menu',
			'label'   => esc_html__( 'Overlay', 'bricks' ),
			'type'    => 'background',
			'exclude' => 'video',
			'css'     => [
				[
					'property' => 'background',
					'selector' => '.bricks-mobile-menu-overlay',
				],
			],
		];

		// MOBILE MENU: TOP LEVEL

		$this->controls['mobileMenuTopLevelSep'] = [
			'group' => 'mobile-menu',
			'type'  => 'separator',
			'label' => esc_html__( 'Top level', 'bricks' ),
		];

		$this->controls['mobileMenuPadding'] = [
			'group'       => 'mobile-menu',
			'type'        => 'spacing',
			'label'       => esc_html__( 'Padding', 'bricks' ),
			'css'         => [
				[
					'property' => 'padding',
					'selector' => '.bricks-mobile-menu > li > a',
				],
				[
					'property' => 'padding',
					'selector' => '.bricks-mobile-menu > li > .brx-submenu-toggle > *',
				],
			],
			'placeholder' => [
				'top'    => 0,
				'right'  => 30,
				'bottom' => 0,
				'left'   => 30,
			],
		];

		$this->controls['mobileMenuItemBackground'] = [
			'group' => 'mobile-menu',
			'label' => esc_html__( 'Background', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.bricks-mobile-menu > li > a',
				],
				[
					'property' => 'background-color',
					'selector' => '.bricks-mobile-menu > li > .brx-submenu-toggle',
				],
			],
		];

		$this->controls['mobileMenuItemBackgroundActive'] = [
			'group' => 'mobile-menu',
			'label' => esc_html__( 'Background', 'bricks' ) . ' (' . esc_html__( 'Active', 'bricks' ) . ')',
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.bricks-mobile-menu > li > a[aria-current="page"]',
				],
				[
					'property' => 'background-color',
					'selector' => '.bricks-mobile-menu > .current-menu-item > .brx-submenu-toggle',
				],
			],
		];

		$this->controls['mobileMenuBorder'] = [
			'group' => 'mobile-menu',
			'label' => esc_html__( 'Border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.bricks-mobile-menu > li > a',
				],
				[
					'property' => 'background-color',
					'selector' => '.bricks-mobile-menu > li > .brx-submenu-toggle',
				],
			],
		];

		$this->controls['mobileMenuTypography'] = [
			'group'   => 'mobile-menu',
			'type'    => 'typography',
			'label'   => esc_html__( 'Typography', 'bricks' ),
			'css'     => [
				[
					'property' => 'font',
					'selector' => '.bricks-mobile-menu > li > a',
				],
				[
					'property' => 'font',
					'selector' => '.bricks-mobile-menu > li > .brx-submenu-toggle > *',
				],
			],
			'exclude' => [ 'text-align' ],
		];

		$this->controls['mobileMenuActiveTypography'] = [
			'group'   => 'mobile-menu',
			'type'    => 'typography',
			'label'   => esc_html__( 'Typography', 'bricks' ) . ' (' . esc_html__( 'Active', 'bricks' ) . ')',
			'css'     => [
				[
					'property' => 'font',
					'selector' => '.bricks-mobile-menu [aria-current="page"]',
				],
				[
					'property' => 'font',
					'selector' => '.bricks-mobile-menu [aria-current="page"] + button',
				],
				[
					'property' => 'font',
					'selector' => '.bricks-mobile-menu > .current-menu-item > a',
				],
				[
					'property' => 'font',
					'selector' => '.bricks-mobile-menu > .current-menu-parent > a',
				],
				[
					'property' => 'font',
					'selector' => '.bricks-mobile-menu > .current-menu-item > .brx-submenu-toggle > *',
				],
				[
					'property' => 'font',
					'selector' => '.bricks-mobile-menu > .current-menu-parent > .brx-submenu-toggle > *',
				],
			],
			'exclude' => [ 'text-align' ],
		];

		// Toggle sub menu
		$this->controls['mobileMenuIcon'] = [
			'group'    => 'mobile-menu',
			'label'    => esc_html__( 'Icon', 'bricks' ) . ' (' . esc_html__( 'Sub menu', 'bricks' ) . ')',
			'type'     => 'icon',
			'rerender' => true,
			'css'      => [
				[
					'selector' => '.bricks-mobile-menu-wrapper .brx-submenu-toggle svg',
				],
			],
		];

		// Toggle sub menu
		$this->controls['mobileMenuCloseIcon'] = [
			'group'    => 'mobile-menu',
			'label'    => esc_html__( 'Close icon', 'bricks' ) . ' (' . esc_html__( 'Sub menu', 'bricks' ) . ')',
			'type'     => 'icon',
			'rerender' => true,
			'required' => [ 'mobileMenuIcon', '!=', '' ],
			'css'      => [
				[
					'selector' => '.bricks-mobile-menu-wrapper .brx-submenu-toggle svg.close',
				],
			],
		];

		$this->controls['mobileMenuIconTypography'] = [
			'group'    => 'mobile-menu',
			'label'    => esc_html__( 'Icon typography', 'bricks' ),
			'type'     => 'typography',
			'inline'   => true,
			'small'    => true,
			'required' => [ 'mobileMenuIcon.icon', '!=', '' ],
			'css'      => [
				[
					'property' => 'font',
					'selector' => '.bricks-mobile-menu > .menu-item-has-children .brx-submenu-toggle button',
				],
			],
			'exclude'  => [
				'font-family',
				'font-weight',
				'font-style',
				'text-align',
				'text-decoration',
				'text-transform',
				'letter-spacing',
			],
		];

		$this->controls['mobileMenuIconPosition'] = [
			'group'       => 'mobile-menu',
			'label'       => esc_html__( 'Icon position', 'bricks' ),
			'type'        => 'select',
			'options'     => $this->control_options['iconPosition'],
			'inline'      => true,
			'placeholder' => esc_html__( 'Right', 'bricks' ),
		];

		$this->controls['mobileMenuIconMargin'] = [
			'group' => 'mobile-menu',
			'label' => esc_html__( 'Icon margin', 'bricks' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'margin',
					'selector' => '.bricks-mobile-menu .menu-item-has-children .brx-submenu-toggle button',
				]
			],
		];

		// MOBILE MENU: SUB MENU

		$this->controls['subMenuSep'] = [
			'group'       => 'mobile-menu',
			'type'        => 'separator',
			'label'       => esc_html__( 'Sub menu', 'bricks' ),
			'description' => esc_html__( 'Keep open while styling', 'bricks' ),
		];

		$this->controls['mobileSubMenuPadding'] = [
			'group' => 'mobile-menu',
			'type'  => 'spacing',
			'label' => esc_html__( 'Padding', 'bricks' ),
			'css'   => [
				[
					'property' => 'padding',
					'selector' => '.bricks-mobile-menu .sub-menu > .menu-item > a',
				],
				[
					'property' => 'padding',
					'selector' => '.bricks-mobile-menu .sub-menu > .menu-item > .brx-submenu-toggle > *',
				],
			],
		];

		$this->controls['mobileSubMenuItemBackground'] = [
			'group' => 'mobile-menu',
			'label' => esc_html__( 'Background', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.bricks-mobile-menu .sub-menu > .menu-item > a',
				],
				[
					'property' => 'background-color',
					'selector' => '.bricks-mobile-menu .sub-menu > .menu-item > .brx-submenu-toggle',
				],
			],
		];

		$this->controls['mobileSubMenuItemBackgroundActive'] = [
			'group' => 'mobile-menu',
			'label' => esc_html__( 'Background', 'bricks' ) . ' (' . esc_html__( 'Active', 'bricks' ) . ')',
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.bricks-mobile-menu .sub-menu > .menu-item > a[aria-current="page"]',
				],
				[
					'property' => 'background-color',
					'selector' => '.bricks-mobile-menu  .sub-menu .current-menu-item > .brx-submenu-toggle',
				],
			],
		];

		$this->controls['mobileSubMenuBorder'] = [
			'group' => 'mobile-menu',
			'label' => esc_html__( 'Border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.bricks-mobile-menu .sub-menu > .menu-item',
				],
			],
		];

		$this->controls['mobileSubMenuTypography'] = [
			'group'   => 'mobile-menu',
			'type'    => 'typography',
			'label'   => esc_html__( 'Typography', 'bricks' ),
			'css'     => [
				[
					'property' => 'font',
					'selector' => '.bricks-mobile-menu .sub-menu > li > a',
				],
				[
					'property' => 'font',
					'selector' => '.bricks-mobile-menu .sub-menu > li > .brx-submenu-toggle > *',
				],
			],
			'exclude' => [ 'text-align' ],
		];

		$this->controls['mobileSubMenuActiveTypography'] = [
			'group'   => 'mobile-menu',
			'type'    => 'typography',
			'label'   => esc_html__( 'Active typography', 'bricks' ),
			'css'     => [
				[
					'property' => 'font',
					'selector' => '.bricks-mobile-menu .sub-menu > .current-menu-item > a',
				],
				[
					'property' => 'font',
					'selector' => '.bricks-mobile-menu .sub-menu > .current-menu-item > .brx-submenu-toggle > *',
				],
			],
			'exclude' => [ 'text-align' ],
		];

		// Hamburger toggle

		$this->controls['mobileMenuToggleSep'] = [
			'group' => 'mobile-menu',
			'type'  => 'separator',
			'label' => esc_html__( 'Hamburger toggle', 'bricks' ),
		];

		$this->controls['mobileMenuToggleWidth'] = [
			'group' => 'mobile-menu',
			'type'  => 'number',
			'units' => true,
			'label' => esc_html__( 'Toggle width', 'bricks' ),
			'css'   => [
				[
					'property'  => 'width',
					'selector'  => '.bricks-mobile-menu-toggle',
					'important' => true,
				],
				[
					'property'  => 'width',
					'selector'  => '.bricks-mobile-menu-toggle .bar-top',
					'important' => true,
				],
				[
					'property'  => 'width',
					'selector'  => '.bricks-mobile-menu-toggle .bar-center',
					'important' => true,
				],
				[
					'property'  => 'width',
					'selector'  => '.bricks-mobile-menu-toggle .bar-bottom',
					'important' => true,
				],
			],
		];

		$this->controls['mobileMenuToggleColor'] = [
			'group' => 'mobile-menu',
			'type'  => 'color',
			'label' => esc_html__( 'Color', 'bricks' ),
			'css'   => [
				[
					'property' => 'color',
					'selector' => '.bricks-mobile-menu-toggle',
				]
			],
		];

		$this->controls['mobileMenuToggleHide'] = [
			'group' => 'mobile-menu',
			'type'  => 'checkbox',
			'label' => esc_html__( 'Hide close', 'bricks' ),
			'css'   => [
				[
					'selector'  => '&.show-mobile-menu .bricks-mobile-menu-toggle',
					'property'  => 'display',
					'value'     => 'none',
					'important' => true,
				]
			],
		];

		$this->controls['mobileMenuToggleColorClose'] = [
			'group' => 'mobile-menu',
			'type'  => 'color',
			'label' => esc_html__( 'Color close', 'bricks' ),
			'css'   => [
				[
					'property'  => 'color',
					'selector'  => '&.show-mobile-menu .bricks-mobile-menu-toggle',
					'important' => true,
				]
			],
		];

		$this->controls['mobileMenuToggleClosePosition'] = [
			'group' => 'mobile-menu',
			'type'  => 'dimensions',
			'label' => esc_html__( 'Close position', 'bricks' ),
			'css'   => [
				[
					'selector' => '&.show-mobile-menu .bricks-mobile-menu-toggle',
					'property' => '',
				],
			],
		];

		// MEGA MENU

		$this->controls['megaMenu'] = [
			'group' => 'megamenu',
			'type'  => 'checkbox',
			'label' => esc_html__( 'Enable', 'bricks' ),
		];

		$this->controls['megaMenuInfo'] = [
			'group'    => 'megamenu',
			'type'     => 'info',
			'content'  => '<a href="' . admin_url( 'nav-menus.php' ) . '" target="_blank">' . esc_html__( 'Edit your WordPress menu item to set a Bricks mega menu template.', 'bricks' ) . '</a>',
			'required' => [ 'megaMenu', '!=', '' ],
		];

		$this->controls['megaMenuSelector'] = [
			'group'       => 'megamenu',
			'label'       => esc_html__( 'CSS selector', 'bricks' ),
			'type'        => 'text',
			'inline'      => true,
			'description' => esc_html__( 'Use width & horizontal position of target node.', 'bricks' ),
			'required'    => [ 'megaMenu', '=', true ],
		];

		$this->controls['megaMenuToggleOn'] = [
			'group'       => 'megamenu',
			'label'       => esc_html__( 'Toggle on', 'bricks' ),
			'type'        => 'select',
			'inline'      => true,
			'options'     => [
				'click' => esc_html__( 'Click', 'bricks' ),
				'hover' => esc_html__( 'Hover', 'bricks' ),
				'both'  => esc_html__( 'Click or hover', 'bricks' ),
			],
			'placeholder' => esc_html__( 'Hover', 'bricks' ),
			'required'    => [ 'megaMenu', '!=', '' ],
		];

		$this->controls['megaMenuTransition'] = [
			'group'          => 'megamenu',
			'label'          => esc_html__( 'Transition', 'bricks' ),
			'type'           => 'text',
			'hasDynamicData' => false,
			'inline'         => true,
			'required'       => [ 'megaMenu', '=', true ],
			'css'            => [
				[
					'property' => 'transition',
					'selector' => '.brx-megamenu',
				],
			],
		];

		$this->controls['megaMenuTransform'] = [
			'group'    => 'megamenu',
			'type'     => 'transform',
			'label'    => esc_html__( 'Transform', 'bricks' ),
			'inline'   => true,
			'small'    => true,
			'required' => [ 'megaMenu', '=', true ],
			'css'      => [
				[
					'property' => 'transform',
					'selector' => '.bricks-nav-menu > .brx-has-megamenu > .brx-megamenu',
				],
			],
		];

		$this->controls['megaMenuTransformOpen'] = [
			'group'    => 'megamenu',
			'type'     => 'transform',
			'label'    => esc_html__( 'Transform', 'bricks' ) . ' (' . esc_html__( 'Open', 'bricks' ) . ')',
			'inline'   => true,
			'small'    => true,
			'required' => [ 'megaMenu', '=', true ],
			'css'      => [
				[
					'property' => 'transform',
					'selector' => '.bricks-nav-menu > .brx-has-megamenu.open > .brx-megamenu',
				],
				[
					'property' => 'transform',
					'selector' => '.bricks-nav-menu > .brx-has-megamenu.open > .brx-megamenu',
				],
			],
		];

		// MULTILEVEL

		$this->controls['multiLevel'] = [
			'group' => 'multilevel',
			'label' => esc_html__( 'Enable', 'bricks' ),
			'type'  => 'checkbox',
		];

		$this->controls['multiLevelInfo'] = [
			'group'    => 'multilevel',
			'type'     => 'info',
			'content'  => '<a href="' . admin_url( 'nav-menus.php' ) . '" target="_blank">' . esc_html__( 'Edit your WordPress menu item to enable multilevel functionality.', 'bricks' ) . '</a>',
			'required' => [ 'multiLevel', '!=', '' ],
		];

		$this->controls['multiLevelBackText'] = [
			'group'    => 'multilevel',
			'label'    => esc_html__( 'Back', 'bricks' ) . ': ' . esc_html__( 'Text', 'bricks' ),
			'type'     => 'text',
			'inline'   => true,
			'required' => [ 'multiLevel', '=', true ],
		];

		$this->controls['multiLevelBackTypography'] = [
			'group'    => 'multilevel',
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

		$this->controls['multiLevelBackground'] = [
			'group'    => 'multilevel',
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
	}

	/**
	 * Render menu item & their sub menus recursively
	 *
	 * When using Nav menu inside dropdown content.
	 *
	 * @since 1.8
	 */
	public function render_menu_items_of_parent_id( $parent_id ) {
		$menu_items = $this->wp_nav_menu_items;

		if ( ! is_array( $menu_items ) || empty( $menu_items ) ) {
			return;
		}

		$output = '';

		// Find the menu item with menu_item_parent = $parent_id (@since 1.8.4)
		$populating_menu_items = array_filter(
			$menu_items,
			function( $menu_item ) use ( $parent_id ) {
				return $menu_item->menu_item_parent == $parent_id;
			}
		);

		foreach ( $populating_menu_items as $menu_item ) {
			$menu_item_settings = [
				'text' => $menu_item->title,
				'link' => [
					'type' => 'external',
					'url'  => $menu_item->url,
				],
			];

			/**
			 * Add advanced menu properties to text links
			 *
			 * New tab, CSS classes, rel, title attribute
			 *
			 * @since 1.9.5
			 */
			if ( $menu_item->target && $menu_item->target === '_blank' ) {
				$menu_item_settings['link']['newTab'] = true;
			}

			if ( ! empty( $menu_item->classes ) ) {
				$menu_item_settings['_cssClasses'] = is_array( $menu_item->classes ) ? implode( ' ', $menu_item->classes ) : $menu_item->classes;
			}

			if ( ! empty( $menu_item->xfn ) ) {
				$menu_item_settings['link']['rel'] = $menu_item->xfn;
			}

			if ( ! empty( $menu_item->attr_title ) ) {
				$menu_item_settings['_attributes'] = [
					[
						'name'  => 'title',
						'value' => $menu_item->attr_title,
					],
				];
			}

			// Render menu item link
			$menu_item_link = Frontend::render_element(
				[
					'name'     => 'text-link',
					'settings' => $menu_item_settings,
				]
			);

			$sub_menu_html = '';

			// Find sub menu items of current menu item (@since 1.8.4)
			$sub_menu_items = array_filter(
				$menu_items,
				function( $sub_menu_item ) use ( $menu_item ) {
					return $sub_menu_item->menu_item_parent == $menu_item->ID;
				}
			);

			// Render sub menu items - Recursion (@since 1.8.4)
			if ( ! empty( $sub_menu_items ) ) {
				$sub_menu_html = $this->render_menu_items_of_parent_id( $menu_item->ID );
			}

			// Render dropdown for sub menu
			if ( $sub_menu_html ) {
				$dropdown = Frontend::render_element(
					[
						'name'     => 'dropdown',
						'settings' => [
							'tag' => 'li',
						],
					]
				);

				// Insert menu item link before dropdown button
				$dropdown_button_start = strpos( $dropdown, '<button' );
				$dropdown              = substr_replace( $dropdown, $menu_item_link, $dropdown_button_start, 0 );

				// Insert sub menu items after dropdown button
				$sub_menu_html  = '<ul class="brx-dropdown-content">' . $sub_menu_html . '</ul>';
				$sub_menu_start = strlen( $dropdown ) - 5; // Before closing .dropdown </li>
				$dropdown       = substr_replace( $dropdown, $sub_menu_html, $sub_menu_start, 0 );

				$output .= $dropdown;
			}

			// Render menu item inside 'li' HTML tag
			else {
				$output .= '<li class="menu-item">' . $menu_item_link . '</li>';
			}
		}

		return $output;
	}

	public function render() {
		$settings = $this->settings;

		// Get menu (term ID)
		$menu                    = ! empty( $settings['menu'] ) ? $settings['menu'] : '';
		$this->wp_nav_menu_items = wp_get_nav_menu_items( $menu );

		// STEP: Check: Nav menu is inside dropdown content (@since 1.8)
		$parent_id                  = ! empty( $this->element['parent'] ) ? $this->element['parent'] : false;
		$parent_element             = $parent_id && ! empty( Frontend::$elements[ $parent_id ] ) ? Frontend::$elements[ $parent_id ] : false;
		$parent_element_classes     = $parent_element && ! empty( $parent_element['settings']['_hidden']['_cssClasses'] ) ? $parent_element['settings']['_hidden']['_cssClasses'] : '';
		$builder_is_inside_dropdown = isset( $this->element['insideDropdown'] );

		// Parent element is dropdown content: Render WP menu inside dropdown content
		if (
			$parent_element_classes === 'brx-dropdown-content' || // Frontend
			$builder_is_inside_dropdown // Builder (@see BricksElementPHP.vue)
		) {
			$menu_html = $this->render_menu_items_of_parent_id( 0 );

			// Builder render (BricksElementPHP.vue) requires one single rootNode
			if ( bricks_is_builder_call() && $builder_is_inside_dropdown ) {
				echo '<div class="brx-render-child-nodes">';
				echo $menu_html;
				echo '</div>';
			} else {
				echo $menu_html;
			}

			return;
		}

		// No nav menu selected: Use first registered menu
		if ( ! $menu || ! is_nav_menu( $menu ) ) {
			// Use first registered menu
			foreach ( wp_get_nav_menus() as $menu ) {
				$menu = $menu->term_id;
			}

			if ( ! $menu || ! is_nav_menu( $menu ) ) {
				return $this->render_element_placeholder(
					[
						'title' => esc_html__( 'No nav menu found.', 'bricks' ),
					]
				);
			} else {
				$this->wp_nav_menu_items = wp_get_nav_menu_items( $menu );
			}
		}

		// Return: Nav menu has no menu items
		if ( empty( $this->wp_nav_menu_items ) ) {
			return $this->render_element_placeholder(
				[
					'title' => esc_html__( 'Nav Menu', 'bricks' ) . ': ' . esc_html__( 'No menu items', 'bricks' )
				]
			);
		}

		// Hooks
		add_filter( 'nav_menu_css_class', [ $this, 'nav_menu_css_class' ], 10, 4 );
		add_filter( 'walker_nav_menu_start_el', [ $this, 'walker_nav_menu_start_el' ], 10, 4 );

		// Render
		echo "<div {$this->render_attributes( '_root' )}>";

		// STEP: Multilevel: Pass data attributes to nav menu walked class to add to <li.menu-item>
		$multilevel_atts = [];

		if ( isset( $settings['multiLevel'] ) ) {
			$multilevel_atts['data-toggle']    = 'click';
			$multilevel_atts['data-back-text'] = ! empty( $settings['multiLevelBackText'] ) ? $settings['multiLevelBackText'] : esc_html__( 'Back', 'bricks' );
		}

		// STEP: Megamenu: Pass data attributes to nav menu walked class to add to <li.menu-item>
		$megamenu_atts = [];

		if ( isset( $settings['megaMenu'] ) ) {
			$megamenu_atts['data-toggle'] = ! empty( $settings['megaMenuToggleOn'] ) ? esc_attr( $settings['megaMenuToggleOn'] ) : 'hover';

			if ( ! empty( $settings['megaMenuSelector'] ) ) {
				$megamenu_atts['data-mega-menu'] = $settings['megaMenuSelector'];
			}
		}

		$show_menu_toggle_at = isset( $settings['mobileMenu'] ) ? $settings['mobileMenu'] : 'mobile_landscape';

		// Is mobile-first: Swap always <> never
		if ( Breakpoints::$is_mobile_first ) {
			if ( $show_menu_toggle_at === 'always' ) {
				$show_menu_toggle_at = 'never';
			} elseif ( $show_menu_toggle_at === 'never' ) {
				$show_menu_toggle_at = 'always';
			}
		}

		if ( $show_menu_toggle_at !== 'always' ) {
			$this->set_attribute( 'nav', 'class', [ 'bricks-nav-menu-wrapper', $show_menu_toggle_at ] );

			echo "<nav {$this->render_attributes( 'nav', true )}>";

			wp_nav_menu(
				[
					'container'  => false,
					'menu_class' => 'bricks-nav-menu',
					'menu'       => $menu,
					'items_wrap' => '<ul id="%1$s" class="%2$s">%3$s</ul>',
					'walker'     => new \Aria_Walker_Nav_Menu(),
					'bricks'     => [
						'multiLevel'    => $multilevel_atts,
						'megaMenu'      => $megamenu_atts,
						'caret'         => isset( $settings['caretSize'] ),
						'submenuStatic' => isset( $settings['submenuStatic'] ),
					],
				]
			);

			// Builder: Add nav menu & mobile menu visibility via inline style
			if ( bricks_is_builder() || bricks_is_builder_call() ) {
				$breakpoint          = Breakpoints::get_breakpoint_by( 'key', $show_menu_toggle_at );
				$nav_menu_inline_css = $this->generate_mobile_menu_inline_css( $settings, $breakpoint );

				echo "<style>$nav_menu_inline_css</style>";
			}

			echo '</nav>';
		}

		$mobile_menu_toggle_classes = [ 'bricks-mobile-menu-toggle' ];

		if ( ! empty( $settings['mobileMenuToggleClosePosition'] ) ) {
			$mobile_menu_toggle_classes[] = 'fixed';
		}

		if ( $show_menu_toggle_at === 'always' ) {
			$mobile_menu_toggle_classes[] = 'always';
		}

		if ( $show_menu_toggle_at !== 'never' ) {
			?>
			<button class="<?php echo join( ' ', $mobile_menu_toggle_classes ); ?>" aria-haspopup="true" aria-label="<?php esc_attr_e( 'Mobile menu', 'bricks' ); ?>" aria-expanded="false">
				<span class="bar-top"></span>
				<span class="bar-center"></span>
				<span class="bar-bottom"></span>
			</button>
			<?php

			$mobile_menu_classes = [ 'bricks-mobile-menu-wrapper' ];

			$mobile_menu_classes[] = ! empty( $settings['mobileMenuPosition'] ) ? $settings['mobileMenuPosition'] : 'left';

			// Fade in
			if ( isset( $settings['mobileMenuFadeIn'] ) ) {
				$mobile_menu_classes[] = 'fade-in';
			}

			$this->set_attribute( 'nav-mobile', 'class', $mobile_menu_classes );

			echo "<nav {$this->render_attributes( 'nav-mobile', true )}>";

			wp_nav_menu(
				[
					'container'  => false,
					'menu_class' => 'bricks-mobile-menu',
					'menu'       => $menu,
					'items_wrap' => '<ul id="%1$s" class="%2$s">%3$s</ul>',
					'walker'     => new \Aria_Walker_Nav_Menu(),
					'bricks'     => [
						'multiLevel'    => $multilevel_atts,
						'megaMenu'      => $megamenu_atts,
						'caret'         => isset( $settings['caretSize'] ),
						'submenuStatic' => isset( $settings['submenuStatic'] ),
					],
				]
			);

			echo '</nav>';

			echo '<div class="bricks-mobile-menu-overlay"></div>';
		}

		echo '</div>'; // Closing '_root'

		// STEP: Remove filters after element render to prevent possbile conflicts with other nav menus
		remove_filter( 'nav_menu_css_class', [ $this, 'nav_menu_css_class' ], 10, 4 );
		remove_filter( 'walker_nav_menu_start_el', [ $this, 'walker_nav_menu_start_el' ], 10, 4 );
	}

	/**
	 * Add submenu toggle icon
	 * Render mega menu (desktop menu)
	 */
	public function walker_nav_menu_start_el( $output, $item, $depth, $args ) {
		$mega_menu_template_id = $this->get_mega_menu_template_id( $item->ID );

		// Return: Menu item has no children (submenu)
		if ( is_array( $item->classes ) && ! in_array( 'menu-item-has-children', $item->classes ) && ! $mega_menu_template_id ) {
			return $output;
		}

		// STEP: Render submenu toggle icon (mobile menu, desktop menu (top level, submenu))
		$settings        = $this->settings;
		$icon            = '';
		$icon_position   = 'right';
		$is_desktop_menu = isset( $args->menu_class ) && $args->menu_class === 'bricks-nav-menu';

		// STEP: Desktop menu

		// Desktop menu (li.bricks-nav-menu)
		if ( $is_desktop_menu ) {
			// Top level
			if ( $depth === 0 ) {
				if ( ! empty( $settings['menuIcon'] ) ) {
					$icon = self::render_icon( $settings['menuIcon'], [ 'menu-item-icon' ] );
				}

				$icon_position = ! empty( $settings['menuIconPosition'] ) ? $settings['menuIconPosition'] : 'right';
			}

			// Submenu
			else {
				if ( ! empty( $settings['subMenuIcon'] ) ) {
					$icon = self::render_icon( $settings['subMenuIcon'], [ 'menu-item-icon' ] );
				}

				$icon_position = ! empty( $settings['subMenuIconPosition'] ) ? $settings['subMenuIconPosition'] : 'right';
			}
		}

		// Mobile menu (li.bricks-mobile-menu)
		else {
			if ( ! empty( $settings['mobileMenuIcon'] ) ) {
				$icon = self::render_icon( $settings['mobileMenuIcon'], [ 'open' ] );
			}

			// Close submenu toggle icon
			if ( ! empty( $settings['mobileMenuCloseIcon'] ) ) {
				$icon .= self::render_icon( $settings['mobileMenuCloseIcon'], [ 'close' ] );
			}

			$icon_position = ! empty( $settings['mobileMenuIconPosition'] ) ? $settings['mobileMenuIconPosition'] : 'right';
		}

		// Default toggle SVG (@since 1.8)
		if ( ! $icon ) {
			$icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 12 12" fill="none" class="menu-item-icon"><path d="M1.50002 4L6.00002 8L10.5 4" stroke-width="1.5"></path></svg>';
		}

		if ( $output && strpos( $output, 'brx-submenu-toggle' ) === false ) {
			// Add icon HTML after menu item link
			// https://www.accessibility-developer-guide.com/examples/widgets/dropdown/
			$aria_label = $item->title . ' ' . esc_html__( 'Sub menu', 'bricks' );
			$icon       = '<button aria-expanded="false" aria-label="' . esc_attr( $aria_label ) . '">' . $icon . '</button>';
			$output     = '<div class="brx-submenu-toggle icon-' . $icon_position . '">' . $output . $icon . '</div>';
		}

		// STEP: Append mega menu template HTML to menu item (@since 1.8)
		if ( $mega_menu_template_id ) {
			$output .= '<div class="brx-megamenu" data-menu-id="' . $item->ID . '">';
			// TODO NEXT (1.8.1): Global class styles missing (https://app.clickup.com/t/863gy6rjb)
			$output .= do_shortcode( "[bricks_template id=\"$mega_menu_template_id\"]" );
			$output .= '</div>';
		}

		return $output;
	}

	/**
	 * Mega menu:  Add .brx-has-megamenu && .menu-item-has-children
	 * Multilevel: Add .brx-has-multilevel && .menu-item-has-children
	 * Builder:    Add .current-menu-item
	 *
	 * @since 1.5.3
	 */
	public function nav_menu_css_class( $classes, $menu_item, $args, $depth ) {
		if ( isset( $args->menu_class ) && $args->menu_class === 'bricks-nav-menu' ) {
			// STEP: Mega menu (desktop menu only)
			$mega_menu_template_id = $this->get_mega_menu_template_id( $menu_item->ID );

			if ( $mega_menu_template_id ) {
				if ( ! in_array( 'menu-item-has-children', $classes ) ) {
					$classes[] = 'menu-item-has-children';
				}

				$classes[] = 'brx-has-megamenu';
			}

			// STEP: Mega menu (desktop menu only)
			$is_multilevel = $this->is_multilevel( $menu_item->ID );

			if ( $is_multilevel ) {
				if ( ! in_array( 'menu-item-has-children', $classes ) ) {
					$classes[] = 'menu-item-has-children';
				}

				$classes[] = 'brx-has-multilevel';
			}
		}

		if ( ! bricks_is_builder() && ! bricks_is_builder_call() ) {
			return $classes;
		}

		if ( isset( $menu_item->object_id ) && $menu_item->object_id == $this->post_id ) {
			$classes[] = 'current-menu-item';
		}

		return $classes;
	}

	/**
	 * Return template ID of mega menu
	 *
	 * @since 1.8
	 */
	public function get_mega_menu_template_id( $menu_item_id ) {
		// Return: Mega menu not enabled
		if ( ! isset( $this->settings['megaMenu'] ) ) {
			return;
		}

		return get_post_meta( $menu_item_id, '_bricks_mega_menu_template_id', true );
	}

	/**
	 * Return true if multilevel is enabled
	 *
	 * @since 1.8
	 */
	public function is_multilevel( $menu_item_id ) {
		// Return: Multilevel not enabled
		if ( ! isset( $this->settings['multiLevel'] ) ) {
			return;
		}

		return get_post_meta( $menu_item_id, '_bricks_multilevel', true );
	}
}
