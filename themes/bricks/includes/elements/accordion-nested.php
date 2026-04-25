<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Accordion_Nested extends Element {
	public $category = 'general';
	public $name     = 'accordion-nested';
	public $icon     = 'ti-layout-accordion-merged';
	public $scripts  = [ 'bricksAccordion' ];
	public $nestable = true;

	public function get_label() {
		return esc_html__( 'Accordion', 'bricks' ) . ' (' . esc_html__( 'Nestable', 'bricks' ) . ')';
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
		// Array of nestable element.children (@since 1.5)
		$this->controls['_children'] = [
			'type'          => 'repeater',
			'titleProperty' => 'label',
			'items'         => 'children', // NOTE: Undocumented
			'description'   => esc_html__( 'Set "ID" on items above to open via anchor link.', 'bricks' ) . ' ' . esc_html__( 'No spaces. No pound (#) sign.', 'bricks' ),
		];

		$this->controls['expandFirstItem'] = [
			'label' => esc_html__( 'Expand first item', 'bricks' ),
			'type'  => 'checkbox',
		];

		$this->controls['independentToggle'] = [
			'label'       => esc_html__( 'Independent toggle', 'bricks' ),
			'type'        => 'checkbox',
			'description' => esc_html__( 'Enable to open & close an item without toggling other items.', 'bricks' ),
		];

		$this->controls['transition'] = [
			'label'       => esc_html__( 'Transition', 'bricks' ) . ' (ms)',
			'type'        => 'number',
			'placeholder' => 200,
		];

		// TITLE

		$this->controls['titleHeight'] = [
			'group'   => 'title',
			'label'   => esc_html__( 'Min. height', 'bricks' ),
			'type'    => 'number',
			'units'   => true,
			'css'     => [
				[
					'property' => 'min-height',
					'selector' => '.accordion-title-wrapper',
				],
			],
			'default' => '50px',
		];

		$this->controls['titleMargin'] = [
			'group' => 'title',
			'label' => esc_html__( 'Margin', 'bricks' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'margin',
					'selector' => '.accordion-title-wrapper',
				],
			],
		];

		$this->controls['titlePadding'] = [
			'group' => 'title',
			'label' => esc_html__( 'Padding', 'bricks' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'padding',
					'selector' => '.accordion-title-wrapper',
				],
			],
		];

		$this->controls['titleBackgroundColor'] = [
			'group' => 'title',
			'label' => esc_html__( 'Background color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.accordion-title-wrapper',
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
					'selector' => '.accordion-title-wrapper',
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
					'selector' => '.accordion-title-wrapper',
				],
				[
					'property' => 'font',
					'selector' => '.accordion-title-wrapper .brxe-heading',
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
			'group' => 'title',
			'label' => esc_html__( 'Background color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.brx-open .accordion-title-wrapper',
				],
			],
		];

		$this->controls['titleActiveBorder'] = [
			'group' => 'title',
			'label' => esc_html__( 'Border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.brx-open .accordion-title-wrapper',
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
					'selector' => '.brx-open .accordion-title-wrapper',
				],
				[
					'property' => 'font',
					'selector' => '.brx-open .accordion-title-wrapper .brxe-heading',
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
					'selector' => '.accordion-content-wrapper',
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
					'selector' => '.accordion-content-wrapper',
				],
			],
			'default' => [
				'top'    => 15,
				'right'  => 0,
				'bottom' => 15,
				'left'   => 0,
			],
		];

		$this->controls['contentBackgroundColor'] = [
			'group' => 'content',
			'label' => esc_html__( 'Background color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.accordion-content-wrapper',
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
					'selector' => '.accordion-content-wrapper',
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
					'selector' => '.accordion-content-wrapper',
				],
			],
		];
	}

	public function get_nestable_item() {
		/**
		 * NOTE: Required classes for element styling & script:
		 *
		 * .accordion-title-wrapper
		 * .accordion-content-wrapper
		 */

		return [
			'name'     => 'block',
			'label'    => esc_html__( 'Item', 'bricks' ),
			'children' => [
				[
					'name'     => 'block',
					'label'    => esc_html__( 'Title', 'bricks' ),
					'settings' => [
						'_alignItems'     => 'center',
						'_direction'      => 'row',
						'_justifyContent' => 'space-between',

						// NOTE: Undocumented (@since 1.5 to apply hard-coded hidden settings)
						'_hidden'         => [
							'_cssClasses' => 'accordion-title-wrapper',
						],
					],

					'children' => [
						[
							'name'     => 'heading',
							'settings' => [
								'text' => esc_html__( 'Accordion', 'bricks' ) . ' {item_index}',
								'tag'  => 'h5',
							],
						],
						[
							'name'     => 'icon',
							'settings' => [
								'icon'     => [
									'icon'    => 'ion-ios-arrow-forward',
									'library' => 'ionicons',
								],
								'iconSize' => '1em',
							],
						],
					],
				],

				[
					'name'     => 'block',
					'label'    => esc_html__( 'Content', 'bricks' ),
					'settings' => [
						'_hidden' => [
							'_cssClasses' => 'accordion-content-wrapper',
						],
					],
					'children' => [
						[
							'name'     => 'text',
							'settings' => [
								'text' => 'Lorem ipsum dolor ist amte, consectetuer adipiscing eilt. Aenean commodo ligula egget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quak felis, ultricies nec, pellentesque eu, pretium quid, sem.',
							],
						],
					],
				],
			],
		];
	}

	public function get_nestable_children() {
		$children = [];

		for ( $i = 0; $i < 2; $i++ ) {
			$item = $this->get_nestable_item();

			// Replace {item_index} with $index
			$item       = wp_json_encode( $item );
			$item       = str_replace( '{item_index}', $i + 1, $item );
			$item       = json_decode( $item, true );
			$children[] = $item;
		}

		return $children;
	}

	public function render() {
		$settings = $this->settings;

		// data-script-args: Expand first item & Independent toggle
		$data_script_args = [];

		foreach ( [ 'expandFirstItem', 'independentToggle' ] as $setting_key ) {
			if ( isset( $settings[ $setting_key ] ) ) {
				$data_script_args[] = $setting_key;
			}
		}

		if ( count( $data_script_args ) ) {
			$this->set_attribute( '_root', 'data-script-args', join( ',', $data_script_args ) );
		}

		// data-transition: Transition duration in ms
		if ( isset( $settings['transition'] ) ) {
			$this->set_attribute( '_root', 'data-transition', $settings['transition'] );
		}

		$output = "<div {$this->render_attributes( '_root' )}>";

		// Render children elements (= individual items)
		$output .= Frontend::render_children( $this );

		$output .= '</div>';

		echo $output;
	}
}
