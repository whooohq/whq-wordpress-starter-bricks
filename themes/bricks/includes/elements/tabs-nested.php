<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Tabs_Nested extends Element {
	public $category = 'general';
	public $name     = 'tabs-nested';
	public $icon     = 'ti-layout-tab';
	public $scripts  = [ 'bricksTabs' ];
	public $nestable = true;

	public function get_label() {
		return esc_html__( 'Tabs', 'bricks' ) . ' (' . esc_html__( 'Nestable', 'bricks' ) . ')';
	}

	public function get_keywords() {
		return [ 'nestable' ];
	}

	public function set_control_groups() {
		$this->control_groups['title'] = [
			'title' => esc_html__( 'Title', 'bricks' ),
		];

		$this->control_groups['content'] = [
			'title' => esc_html__( 'Content', 'bricks' ),
		];
	}

	public function set_controls() {
		$this->controls['direction'] = [
			'label'       => esc_html__( 'Direction', 'bricks' ),
			'tooltip'     => [
				'content'  => 'flex-direction',
				'position' => 'top-left',
			],
			'type'        => 'direction',
			'css'         => [
				[
					'property' => 'flex-direction',
				],
			],
			'inline'      => true,
			'rerender'    => true,
			'description' => esc_html__( 'Set "ID" on tab menu "Div" to open a tab via anchor link.', 'bricks' ) . ' ' . esc_html__( 'No spaces. No pound (#) sign.', 'bricks' ),
		];

		// TITLE

		$this->controls['titleWidth'] = [
			'group'       => 'title',
			'label'       => esc_html__( 'Width', 'bricks' ),
			'type'        => 'number',
			'units'       => true,
			'css'         => [
				[
					'selector' => '> .tab-menu .tab-title',
					'property' => 'width',
				],
			],
			'placeholder' => 'auto',
		];

		$this->controls['titleMargin'] = [
			'group' => 'title',
			'label' => esc_html__( 'Margin', 'bricks' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'margin',
					'selector' => '> .tab-menu .tab-title',
				],
			],
		];

		$this->controls['titlePadding'] = [
			'group'   => 'title',
			'label'   => esc_html__( 'Padding', 'bricks' ),
			'type'    => 'spacing',
			'css'     => [
				[
					'property' => 'padding',
					'selector' => '> .tab-menu .tab-title',
				],
			],
			'default' => [
				'top'    => 20,
				'right'  => 20,
				'bottom' => 20,
				'left'   => 20,
			],
		];

		$this->controls['titleBackgroundColor'] = [
			'group' => 'title',
			'label' => esc_html__( 'Background', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '> .tab-menu .tab-title',
				],
			],
		];

		$this->controls['titleBorder'] = [
			'group' => 'title',
			'label' => esc_html__( 'Border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '> .tab-menu .tab-title',
				],
			],
		];

		$this->controls['titleTypography'] = [
			'group' => 'title',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '> .tab-menu .tab-title',
				],
			],
		];

		// ACTIVE TITLE

		$this->controls['titleActiveSeparator'] = [
			'group'      => 'title',
			'label'      => esc_html__( 'Active', 'bricks' ),
			'type'       => 'separator',
			'fullAccess' => true,
		];

		$this->controls['titleActiveBackgroundColor'] = [
			'group'   => 'title',
			'label'   => esc_html__( 'Background color', 'bricks' ),
			'type'    => 'color',
			'css'     => [
				[
					'property' => 'background-color',
					'selector' => '> .tab-menu .tab-title.brx-open',
				],
			],
			'default' => [
				'hex' => '#dddedf',
			],
		];

		$this->controls['titleActiveBorder'] = [
			'group' => 'title',
			'label' => esc_html__( 'Border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '> .tab-menu .tab-title.brx-open',
				],
			],
		];

		$this->controls['titleActiveTypography'] = [
			'group' => 'title',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '> .tab-menu .tab-title.brx-open',
				],
			],
		];

		// CONTENT

		$this->controls['contentMargin'] = [
			'group' => 'content',
			'label' => esc_html__( 'Margin', 'bricks' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'margin',
					'selector' => '> .tab-content',
				],
			],
		];

		$this->controls['contentPadding'] = [
			'group'   => 'content',
			'label'   => esc_html__( 'Padding', 'bricks' ),
			'type'    => 'spacing',
			'css'     => [
				[
					'property' => 'padding',
					'selector' => '> .tab-content',
				],
			],
			'default' => [
				'top'    => 20,
				'right'  => 20,
				'bottom' => 20,
				'left'   => 20,
			],
		];

		$this->controls['contentColor'] = [
			'group' => 'content',
			'label' => esc_html__( 'Text color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'color',
					'selector' => '> .tab-content',
				],
			],
		];

		$this->controls['contentBackgroundColor'] = [
			'group' => 'content',
			'label' => esc_html__( 'Background color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '> .tab-content',
				],
			],
		];

		$this->controls['contentBorder'] = [
			'group'   => 'content',
			'label'   => esc_html__( 'Border', 'bricks' ),
			'type'    => 'border',
			'css'     => [
				[
					'property' => 'border',
					'selector' => '> .tab-content',
				],
			],
			'default' => [
				'width' => [
					'top'    => 1,
					'right'  => 1,
					'bottom' => 1,
					'left'   => 1,
				],
				'style' => 'solid',
			],
		];
	}

	/**
	 * Get child elements
	 *
	 * @return array Array of child elements.
	 *
	 * @since 1.5
	 */
	public function get_nestable_children() {
		/**
		 * NOTE: Required classes for element styling & script:
		 *
		 * .tab-menu
		 * .tab-title
		 * .tab-content
		 * .tab-pane
		 */
		return [
			// Title
			[
				'name'     => 'block',
				'label'    => esc_html__( 'Tab menu', 'bricks' ),
				'settings' => [
					'_direction' => 'row',
					'_hidden'    => [
						'_cssClasses' => 'tab-menu',
					],
				],
				'children' => [
					[
						'name'     => 'div',
						'label'    => esc_html__( 'Title', 'bricks' ),
						'settings' => [
							'_hidden' => [
								'_cssClasses' => 'tab-title',
							],
						],
						'children' => [
							[
								'name'     => 'text-basic',
								'settings' => [
									'text' => esc_html__( 'Title', 'bricks' ) . ' 1',
								],
							],
						],
					],

					[
						'name'     => 'div',
						'label'    => esc_html__( 'Title', 'bricks' ),
						'settings' => [
							'_hidden' => [
								'_cssClasses' => 'tab-title',
							],
						],
						'children' => [
							[
								'name'     => 'text-basic',
								'settings' => [
									'text' => esc_html__( 'Title', 'bricks' ) . ' 2',
								],
							],
						],
					],
				],
			],

			// Content
			[
				'name'     => 'block',
				'label'    => esc_html__( 'Tab content', 'bricks' ),
				'settings' => [
					'_hidden' => [
						'_cssClasses' => 'tab-content',
					],
				],
				'children' => [
					[
						'name'     => 'block',
						'label'    => esc_html__( 'Pane', 'bricks' ),
						'settings' => [
							'_hidden' => [
								'_cssClasses' => 'tab-pane',
							],
						],
						'children' => [
							[
								'name'     => 'text',
								'settings' => [
									'text' => esc_html__( 'Content goes here ..', 'bricks' ) . ' (1)',
								],
							],
						],
					],

					[
						'name'     => 'block',
						'label'    => esc_html__( 'Pane', 'bricks' ),
						'settings' => [
							'_hidden' => [
								'_cssClasses' => 'tab-pane',
							],
						],
						'children' => [
							[
								'name'     => 'text',
								'settings' => [
									'text' => esc_html__( 'Content goes here ..', 'bricks' ) . ' (2)',
								],
							],
						],
					],
				],
			],
		];
	}

	public function render() {
		$settings = $this->settings;

		$output = "<div {$this->render_attributes( '_root' )}>";

		// Render children elements (= individual items)
		$output .= Frontend::render_children( $this );

		$output .= '</div>';

		echo $output;
	}
}
