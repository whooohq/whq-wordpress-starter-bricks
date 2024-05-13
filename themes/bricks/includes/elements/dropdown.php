<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Dropdown extends Element {
	public $category         = 'general';
	public $name             = 'dropdown';
	public $icon             = 'ti-arrow-circle-down';
	public $scripts          = [ 'bricksSubmenuPosition' ];
	public $nestable         = true;
	public $nestable_actions = false;
	public $tag              = 'li';

	public function get_label() {
		return esc_html__( 'Dropdown', 'bricks' );
	}

	public function get_keywords() {
		return [ 'menu', 'nestable' ];
	}

	public function set_control_groups() {
		$this->control_groups['icon'] = [
			'title' => esc_html__( 'Icon', 'bricks' ),
		];

		$this->control_groups['caret'] = [
			'title' => esc_html__( 'Caret', 'bricks' ),
		];

		$this->control_groups['content'] = [
			'title' => esc_html__( 'Content', 'bricks' ),
		];

		$this->control_groups['megamenu'] = [
			'title' => esc_html__( 'Mega menu', 'bricks' ),
		];

		$this->control_groups['multilevel'] = [
			'title' => esc_html__( 'Multilevel', 'bricks' ),
		];
	}

	public function set_controls() {
		$this->controls['tag'] = [
			'label'       => esc_html__( 'HTML tag', 'bricks' ),
			'type'        => 'text',
			'inline'      => true,
			'fullAccess'  => true,
			'placeholder' => $this->tag,
		];

		$this->controls['text'] = [
			'label'   => esc_html__( 'Text', 'bricks' ),
			'type'    => 'text',
			'default' => esc_html__( 'Dropdown', 'bricks' ),
		];

		$this->controls['link'] = [
			'label'   => esc_html__( 'Link to', 'bricks' ),
			'type'    => 'link',
			'exclude' => [
				'ariaLabel', // set below to add to <button>
				'lightboxImage',
				'lightboxVideo',
			],
		];

		$this->controls['ariaLabel'] = [
			'label'       => esc_html__( 'Attribute', 'bricks' ) . ': aria-label',
			'type'        => 'text',
			'placeholder' => esc_html__( 'Toggle dropdown', 'bricks' ),
		];

		// ICON

		$this->controls['icon'] = [
			'group' => 'icon',
			'label' => esc_html__( 'Icon', 'bricks' ),
			'type'  => 'icon',
		];

		$this->controls['iconPadding'] = [
			'group' => 'icon',
			'label' => esc_html__( 'Icon padding', 'bricks' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'padding',
					'selector' => '.brx-submenu-toggle button',
				],
			],
		];

		$this->controls['gap'] = [
			'group' => 'icon',
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
			'group'       => 'icon',
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
			'group' => 'icon',
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
			'group' => 'icon',
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
			'group'  => 'icon',
			'type'   => 'transform',
			'label'  => esc_html__( 'Icon transform', 'bricks' ),
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
			'group'  => 'icon',
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
			'group'          => 'icon',
			'label'          => esc_html__( 'Icon transition', 'bricks' ),
			'type'           => 'text',
			'hasDynamicData' => false,
			'inline'         => true,
			'css'            => [
				[
					'property' => 'transition',
					'selector' => '.brx-submenu-toggle button',
				],
			],
		];

		// CARET (add .caret to .brxe-dropdown)

		$this->controls['caretSize'] = [
			'group'    => 'caret',
			'type'     => 'number',
			'units'    => true,
			'rerender' => true,
			'label'    => esc_html__( 'Size', 'bricks' ),
			'css'      => [
				[
					'property' => 'border-width',
					'selector' => '> .brx-dropdown-content::before',
				],
			],
		];

		$this->controls['caretColor'] = [
			'group'    => 'caret',
			'type'     => 'color',
			'label'    => esc_html__( 'Color', 'bricks' ),
			'css'      => [
				[
					'property' => 'border-bottom-color',
					'selector' => '> .brx-dropdown-content::before',
				],
			],
			'required' => [ 'caretSize', '!=', 0 ],
		];

		$this->controls['caretTransform'] = [
			'group'    => 'caret',
			'label'    => esc_html__( 'Transform', 'bricks' ),
			'type'     => 'transform',
			'css'      => [
				[
					'property' => 'transform',
					'selector' => '> .brx-dropdown-content::before',
				],
			],
			'required' => [ 'caretSize', '!=', 0 ],
		];

		$this->controls['caretPosition'] = [
			'group'    => 'caret',
			'label'    => esc_html__( 'Position', 'bricks' ),
			'type'     => 'dimensions',
			'css'      => [
				[
					'selector' => '> .brx-dropdown-content::before',
				],
			],
			'required' => [ 'caretSize', '!=', 0 ],
		];

		// CONTENT

		$this->controls['static'] = [
			'group'       => 'content',
			'label'       => esc_html__( 'Position', 'bricks' ) . ': ' . esc_html__( 'Static', 'bricks' ),
			'type'        => 'checkbox',
			'description' => esc_html__( 'Enable to position in document flow (e.g. inside offcanvas).', 'bricks' ),
		];

		$this->controls['staticInfo'] = [
			'group'    => 'content',
			'type'     => 'info',
			'content'  => esc_html__( 'Static dropdown content always toggles on click, not hover.', 'bricks' ),
			'required' => [ 'static', '!=', '' ],
		];

		$this->controls['toggleOn'] = [
			'group'       => 'content',
			'label'       => esc_html__( 'Toggle on', 'bricks' ),
			'type'        => 'select',
			'inline'      => true,
			'options'     => [
				'click' => esc_html__( 'Click', 'bricks' ),
				'hover' => esc_html__( 'Hover', 'bricks' ),
				'both'  => esc_html__( 'Click or hover', 'bricks' ),
			],
			'placeholder' => esc_html__( 'Hover', 'bricks' ),
			'required'    => [ 'static', '=', '' ],
		];

		$this->controls['contentWidth'] = [
			'group'    => 'content',
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

		$this->controls['contentTransition'] = [
			'group'          => 'content',
			'label'          => esc_html__( 'Transition', 'bricks' ),
			'type'           => 'text',
			'hasDynamicData' => false,
			'inline'         => true,
			'css'            => [
				[
					'property' => 'transition',
					'selector' => '> .brx-dropdown-content',
				],
			],
		];

		$this->controls['contentTransform'] = [
			'group'  => 'content',
			'type'   => 'transform',
			'label'  => esc_html__( 'Transform', 'bricks' ),
			'inline' => true,
			'small'  => true,
			'css'    => [
				[
					'property' => 'transform',
					'selector' => '> .brx-dropdown-content',
				],
			],
		];

		$this->controls['contentTransformOpen'] = [
			'group'  => 'content',
			'type'   => 'transform',
			'label'  => esc_html__( 'Transform', 'bricks' ) . ' (' . esc_html__( 'Open', 'bricks' ) . ')',
			'inline' => true,
			'small'  => true,
			'css'    => [
				[
					'property' => 'transform',
					'selector' => '&.open > .brx-dropdown-content',
				],
			],
		];

		$this->controls['contentBackground'] = [
			'group' => 'content',
			'label' => esc_html__( 'Background', 'bricks' ),
			'type'  => 'background',
			'css'   => [
				[
					'property' => 'background',
					'selector' => '.brx-dropdown-content',
				],
			],
		];

		$this->controls['contentBorder'] = [
			'group' => 'content',
			'label' => esc_html__( 'Border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.brx-dropdown-content',
				],
			],
		];

		$this->controls['contentBoxShadow'] = [
			'group' => 'content',
			'label' => esc_html__( 'Box shadow', 'bricks' ),
			'type'  => 'box-shadow',
			'css'   => [
				[
					'property' => 'box-shadow',
					'selector' => '.brx-dropdown-content',
				],
			],
		];

		$this->controls['contentTypography'] = [
			'group' => 'content',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.brx-dropdown-content',
				],
			],
		];

		// CONTENT - ITEM

		$this->controls['contentItemSep'] = [
			'group' => 'content',
			'label' => esc_html__( 'Item', 'bricks' ),
			'type'  => 'separator',
		];

		$this->controls['contentItemJustifyContent'] = [
			'group' => 'content',
			'label' => esc_html__( 'Justify content', 'bricks' ),
			'type'  => 'justify-content',
			'css'   => [
				[
					'property' => 'justify-content',
					'selector' => '.brx-dropdown-content > li > a',
				],
				[
					'property' => 'justify-content',
					'selector' => '.brx-submenu-toggle',
				],
				// Make sure link fills up all available space
				[
					'property' => 'width',
					'selector' => '.brx-dropdown-content > li > a',
					'value'    => '100%',
					'required' => 'space-between',
				],
				[
					'property' => 'width',
					'selector' => '.brx-submenu-toggle a',
					'value'    => '100%',
					'required' => 'space-between',
				],
			],
		];

		$this->controls['contentItemPadding'] = [
			'group' => 'content',
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
				[
					'property' => 'padding',
					'selector' => '&.brx-has-megamenu .brx-dropdown-content > *',
				],
			],
		];

		$this->controls['contentItemBackground'] = [
			'group' => 'content',
			'label' => esc_html__( 'Background', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.brx-dropdown-content > li > a',
				],
				[
					'property' => 'background-color',
					'selector' => '.brx-dropdown-content .brx-submenu-toggle',
				],
				[
					'property' => 'background-color',
					'selector' => '&.brx-has-megamenu .brx-dropdown-content > *',
				],
			],
		];

		$this->controls['contentItemBorder'] = [
			'group' => 'content',
			'label' => esc_html__( 'Border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.brx-dropdown-content > li > a',
				],
				[
					'property' => 'border',
					'selector' => '.brx-dropdown-content .brx-submenu-toggle',
				],
				[
					'property' => 'border',
					'selector' => '&.brx-has-megamenu .brx-dropdown-content > *',
				],
			],
		];

		$this->controls['contentItemTypography'] = [
			'group' => 'content',
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
				[
					'property' => 'font',
					'selector' => '&.brx-has-megamenu .brx-dropdown-content > *',
				],
			],
		];

		$this->controls['contentItemTransition'] = [
			'group'          => 'content',
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

		// CONTENT - ACTIVE

		$this->controls['contentItemActiveSep'] = [
			'group' => 'content',
			'label' => esc_html__( 'Active', 'bricks' ),
			'type'  => 'separator',
		];

		$this->controls['contentItemBackgroundActive'] = [
			'group' => 'content',
			'label' => esc_html__( 'Background', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.brx-dropdown-content > li [aria-current="page"]',
				],
				[
					'property' => 'background-color',
					'selector' => '.brx-dropdown-content > li .aria-current',
				],
				// Mega menu
				[
					'property' => 'background-color',
					'selector' => '&.brx-has-megamenu .brx-dropdown-content [aria-current="page"]',
				],
			],
		];

		$this->controls['contentItemBorderActive'] = [
			'group' => 'content',
			'label' => esc_html__( 'Border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.brx-dropdown-content > li [aria-current="page"]',
				],
				[
					'property' => 'border',
					'selector' => '.brx-dropdown-content > li .aria-current',
				],
				// Mega menu
				[
					'property' => 'border',
					'selector' => '&.brx-has-megamenu .brx-dropdown-content [aria-current="page"]',
				],
			],
		];

		$this->controls['contentItemTypographyActive'] = [
			'group' => 'content',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.brx-dropdown-content > li [aria-current="page"]',
				],
				[
					'property' => 'font',
					'selector' => '.brx-dropdown-content > li .aria-current',
				],
				// Mega menu
				[
					'property' => 'font',
					'selector' => '&.brx-has-megamenu .brx-dropdown-content [aria-current="page"]',
				],
			],
		];

		// MEGA MENU

		$this->controls['megaMenu'] = [
			'group'       => 'megamenu',
			'label'       => esc_html__( 'Enable', 'bricks' ),
			'type'        => 'checkbox',
			'description' => esc_html__( 'By default, covers entire available width.', 'bricks' ),
		];

		$this->controls['megaMenuSelector'] = [
			'group'       => 'megamenu',
			'label'       => esc_html__( 'CSS selector', 'bricks' ) . ' (' . esc_html__( 'Horizontal', 'bricks' ) . ')',
			'type'        => 'text',
			'inline'      => true,
			'description' => esc_html__( 'Use width & horizontal position of target node.', 'bricks' ),
			'required'    => [ 'megaMenu', '=', true ],
		];

		$this->controls['megaMenuSelectorVertical'] = [
			'group'       => 'megamenu',
			'label'       => esc_html__( 'CSS selector', 'bricks' ) . ' (' . esc_html__( 'Vertical', 'bricks' ) . ')',
			'type'        => 'text',
			'inline'      => true,
			'description' => esc_html__( 'Use vertical position of target node.', 'bricks' ),
			'required'    => [ 'megaMenu', '=', true ],
		];

		// MULTI LEVEL

		$this->controls['multiLevel'] = [
			'group'       => 'multilevel',
			'label'       => esc_html__( 'Enable', 'bricks' ),
			'type'        => 'checkbox',
			'description' => esc_html__( 'Show only active dropdown. Toggle on click. Inner dropdowns inherit multilevel.', 'bricks' ),
		];

		$this->controls['multiLevelBackText'] = [
			'group'  => 'multilevel',
			'label'  => esc_html__( 'Back', 'bricks' ) . ': ' . esc_html__( 'Text', 'bricks' ),
			'type'   => 'text',
			'inline' => true,
		];

		$this->controls['multiLevelBackTypography'] = [
			'group'  => 'multilevel',
			'label'  => esc_html__( 'Back', 'bricks' ) . ': ' . esc_html__( 'Typography', 'bricks' ),
			'type'   => 'typography',
			'inline' => true,
			'css'    => [
				[
					'property' => 'font',
					'selector' => '.brx-multilevel-back',
				],
			],
		];

		$this->controls['multiLevelBackBackground'] = [
			'group'  => 'multilevel',
			'label'  => esc_html__( 'Back', 'bricks' ) . ': ' . esc_html__( 'Background', 'bricks' ),
			'type'   => 'color',
			'inline' => true,
			'css'    => [
				[
					'property' => 'background-color',
					'selector' => '.brx-multilevel-back',
				],
			],
		];
	}

	public function get_nestable_children() {
		$dropdown_content = [
			'name'      => 'div',
			'label'     => esc_html__( 'Content', 'bricks' ),
			'deletable' => false,
			'cloneable' => false,
			'settings'  => [
				'_hidden' => [
					'_cssClasses' => 'brx-dropdown-content',
				],
				'tag'     => 'ul',
			],
			'children'  => [
				[
					'name'     => 'text-link',
					'label'    => 'Nav link',
					'settings' => [
						'text' => 'Dropdown link 1',
						'link' => [
							'type' => 'external',
							'url'  => '#',
						],
					],
				],
				[
					'name'     => 'text-link',
					'label'    => 'Nav link',
					'settings' => [
						'text' => 'Dropdown link 2',
						'link' => [
							'type' => 'external',
							'url'  => '#',
						],
					],
				],
			],
		];

		return [ $dropdown_content ];
	}

	public function render() {
		$settings = $this->settings;

		$text = isset( $settings['text'] ) ? $this->render_dynamic_data( $settings['text'] ) : null;

		// Default toggle SVG (icon click toggle dropdown)
		$icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 12 12" fill="none"><path d="M1.50002 4L6.00002 8L10.5 4" stroke-width="1.5"></path></svg>';

		if ( ! empty( $settings['icon'] ) ) {
			$icon = self::render_icon( $settings['icon'] );
		}

		/**
		 * Add .caret class if caretSize is set
		 *
		 * @since 1.8.5 Check classes settings too.
		 */
		if ( isset( $settings['caretSize'] ) || $this->element_classes_have( 'caretSize' ) ) {
			$this->set_attribute( '_root', 'class', 'caret' );
		}

		if ( isset( $settings['megaMenu'] ) ) {
			$this->set_attribute( '_root', 'class', 'brx-has-megamenu' );
		}

		$toggle_on = ! empty( $settings['toggleOn'] ) ? $settings['toggleOn'] : 'hover';

		if ( isset( $settings['multiLevel'] ) ) {
			$this->set_attribute( '_root', 'class', 'brx-has-multilevel' );
			$this->set_attribute( '_root', 'data-back-text', ! empty( $settings['multiLevelBackText'] ) ? esc_attr( $settings['multiLevelBackText'] ) : esc_html__( 'Back', 'bricks' ) );

			// Multi level dropdowns must be toggled on click
			$toggle_on = 'click';
		}

		if ( isset( $settings['static'] ) ) {
			$this->set_attribute( '_root', 'data-static', 'true' );

			// Multi level dropdowns must be toggled on click
			$toggle_on = 'click';
		}

		if ( $toggle_on !== 'hover' ) {
			$this->set_attribute( '_root', 'data-toggle', esc_attr( $toggle_on ) );
		}

		if ( ! empty( $settings['megaMenuSelector'] ) ) {
			$this->set_attribute( '_root', 'data-mega-menu', $settings['megaMenuSelector'] );
		}

		if ( ! empty( $settings['megaMenuSelectorVertical'] ) ) {
			$this->set_attribute( '_root', 'data-mega-menu-vertical', $settings['megaMenuSelectorVertical'] );
		}

		$output = "<{$this->tag} {$this->render_attributes( '_root' )}>";

		$link = ! empty( $settings['link'] ) ? $settings['link'] : false;

		if ( $text !== null ) {
			if ( $link ) {
				$this->set_link_attributes( 'link', $link );
				$text = "<a {$this->render_attributes( 'link' )}>$text</a>";
			} else {
				$text = '<span>' . $text . '</span>';
			}
		}

		// Dropdown toggle (contains text & icon)
		$output .= is_string( $text ) && strpos( $text, 'aria-current' ) !== false ? '<div class="brx-submenu-toggle aria-current">' : '<div class="brx-submenu-toggle">';

		if ( $text ) {
			$output .= $text;
		}

		if ( $icon ) {
			$aria_label = ! empty( $settings['ariaLabel'] ) ? $settings['ariaLabel'] : esc_html__( 'Toggle dropdown', 'bricks' );

			$output .= '<button aria-expanded="false" aria-label="' . esc_attr( $aria_label ) . '">';
			$output .= $icon;
			$output .= '</button>';
		}

		$output .= '</div>';

		$output .= Frontend::render_children( $this );

		$output .= "</{$this->tag}>";

		echo $output;
	}
}
