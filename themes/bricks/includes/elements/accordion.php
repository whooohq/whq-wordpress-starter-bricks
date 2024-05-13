<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Accordion extends Element {
	public $category     = 'general';
	public $name         = 'accordion';
	public $icon         = 'ti-layout-accordion-merged';
	public $scripts      = [ 'bricksAccordion' ];
	public $css_selector = '.accordion-item';
	public $loop_index   = 0;

	public function get_label() {
		return esc_html__( 'Accordion', 'bricks' );
	}

	public function set_control_groups() {
		$this->control_groups['title'] = [
			'title' => esc_html__( 'Title', 'bricks' ),
			'tab'   => 'content',
		];

		$this->control_groups['content'] = [
			'title' => esc_html__( 'Content', 'bricks' ),
			'tab'   => 'content',
		];
	}

	public function set_controls() {
		$this->controls['accordions'] = [
			'tab'         => 'content',
			'placeholder' => esc_html__( 'Accordion', 'bricks' ),
			'type'        => 'repeater',
			'checkLoop'   => true,
			'description' => esc_html__( 'Set "ID" on items above to open via anchor link.', 'bricks' ) . ' ' . esc_html__( 'No spaces. No pound (#) sign.', 'bricks' ),
			'fields'      => [
				'title'    => [
					'label' => esc_html__( 'Title', 'bricks' ),
					'type'  => 'text',
				],
				'anchorId' => [
					'label' => esc_html__( 'ID', 'bricks' ),
					'type'  => 'text',
				],
				'subtitle' => [
					'label' => esc_html__( 'Subtitle', 'bricks' ),
					'type'  => 'text',
				],
				'content'  => [
					'label' => esc_html__( 'Content', 'bricks' ),
					'type'  => 'editor',
				],
			],
			'default'     => [
				[
					'title'    => esc_html__( 'Item', 'bricks' ),
					'subtitle' => esc_html__( 'I am a so called subtitle.', 'bricks' ),
					'content'  => esc_html__( 'Content goes here ..', 'bricks' ),
				],
				[
					'title'    => esc_html__( 'Item', 'bricks' ) . ' 2',
					'subtitle' => esc_html__( 'I am a so called subtitle.', 'bricks' ),
					'content'  => esc_html__( 'Content goes here ..', 'bricks' ),
				],
			],
		];

		$this->controls = array_replace_recursive( $this->controls, $this->get_loop_builder_controls() );

		$this->controls['expandFirstItem'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Expand first item', 'bricks' ),
			'type'  => 'checkbox',
		];

		$this->controls['independentToggle'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Independent toggle', 'bricks' ),
			'type'        => 'checkbox',
			'description' => esc_html__( 'Enable to open & close an item without toggling other items.', 'bricks' ),
		];

		$this->controls['transition'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Transition', 'bricks' ) . ' (ms)',
			'type'        => 'number',
			'placeholder' => 200,
		];

		// TITLE

		$this->controls['titleTag'] = [
			'tab'         => 'content',
			'group'       => 'title',
			'label'       => esc_html__( 'HTML tag', 'bricks' ),
			'type'        => 'select',
			'options'     => [
				'div' => 'div',
				'h1'  => 'h1',
				'h2'  => 'h2',
				'h3'  => 'h3',
				'h4'  => 'h4',
				'h5'  => 'h5',
				'h6'  => 'h6',
			],
			'inline'      => true,
			'placeholder' => 'h5',
		];

		$this->controls['icon'] = [
			'tab'     => 'content',
			'group'   => 'title',
			'label'   => esc_html__( 'Icon', 'bricks' ),
			'type'    => 'icon',
			'default' => [
				'icon'    => 'ion-ios-arrow-forward',
				'library' => 'ionicons',
			],
		];

		$this->controls['iconTypography'] = [
			'tab'      => 'content',
			'group'    => 'title',
			'label'    => esc_html__( 'Icon typography', 'bricks' ),
			'type'     => 'typography',
			'css'      => [
				[
					'property' => 'font',
					'selector' => '.accordion-title{pseudo} .icon', // NOTE: Undocumented (@since 1.3.5)
				],
			],
			'required' => [ 'icon.icon', '!=', '' ],
		];

		$this->controls['iconExpanded'] = [
			'tab'     => 'content',
			'group'   => 'title',
			'label'   => esc_html__( 'Icon expanded', 'bricks' ),
			'type'    => 'icon',
			'default' => [
				'icon'    => 'ion-ios-arrow-down',
				'library' => 'ionicons',
			],
		];

		$this->controls['iconExpandedTypography'] = [
			'tab'      => 'content',
			'group'    => 'title',
			'label'    => esc_html__( 'Icon expanded typography', 'bricks' ),
			'type'     => 'typography',
			'css'      => [
				[
					'property' => 'font',
					'selector' => '.accordion-title{pseudo} .icon.expanded',
				],
			],
			'exclude'  => [
				'font-family',
				'font-weight',
				'font-style',
				'text-align',
				'text-decoration',
				'text-transform',
				'line-height',
				'letter-spacing',
			],
			'required' => [ 'iconExpanded.icon', '!=', '' ],
		];

		$this->controls['iconPosition'] = [
			'tab'         => 'content',
			'group'       => 'title',
			'label'       => esc_html__( 'Icon position', 'bricks' ),
			'type'        => 'select',
			'options'     => $this->control_options['iconPosition'],
			'inline'      => true,
			'placeholder' => esc_html__( 'Right', 'bricks' ),
			'required'    => [ 'icon', '!=', '' ],
		];

		$this->controls['iconRotate'] = [
			'tab'         => 'content',
			'group'       => 'title',
			'label'       => esc_html__( 'Icon rotate in °', 'bricks' ),
			'type'        => 'number',
			'unit'        => 'deg',
			'css'         => [
				[
					'property' => 'transform:rotate',
					'selector' => '.brx-open .title + .icon',
				],
			],
			'small'       => false,
			'description' => esc_html__( 'Icon rotation for expanded accordion.', 'bricks' ),
			'required'    => [ 'icon', '!=', '' ],
		];

		$this->controls['titleMargin'] = [
			'tab'   => 'content',
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
			'tab'   => 'content',
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

		$this->controls['titleTypography'] = [
			'tab'   => 'content',
			'group' => 'title',
			'label' => esc_html__( 'Title typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.accordion-title{pseudo} .title',
				],
			],
		];

		$this->controls['subtitleTypography'] = [
			'tab'   => 'content',
			'group' => 'title',
			'label' => esc_html__( 'Subtitle typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.accordion-subtitle',
				],
			],
		];

		$this->controls['titleBackgroundColor'] = [
			'tab'   => 'content',
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
			'tab'   => 'content',
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

		$this->controls['titleActiveBoxShadow'] = [
			'tab'   => 'content',
			'group' => 'title',
			'label' => esc_html__( 'Box shadow', 'bricks' ),
			'type'  => 'box-shadow',
			'css'   => [
				[
					'property' => 'box-shadow',
					'selector' => '.accordion-title-wrapper',
				],
			],
		];

		$this->controls['titleActiveTypography'] = [
			'tab'   => 'content',
			'group' => 'title',
			'label' => esc_html__( 'Active typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.brx-open .title',
				],
			],
		];

		$this->controls['titleActiveBackgroundColor'] = [
			'tab'   => 'content',
			'group' => 'title',
			'label' => esc_html__( 'Active background', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.brx-open .accordion-title-wrapper',
				],
			],
		];

		$this->controls['titleActiveBorder'] = [
			'tab'   => 'content',
			'group' => 'title',
			'label' => esc_html__( 'Active border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.brx-open .accordion-title-wrapper',
				],
			],
		];

		// CONTENT

		$this->controls['contentMargin'] = [
			'tab'   => 'content',
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
			'tab'   => 'content',
			'group' => 'content',
			'label' => esc_html__( 'Padding', 'bricks' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'padding',
					'selector' => '.accordion-content-wrapper',
				],
			],
		];

		$this->controls['contentTypography'] = [
			'tab'   => 'content',
			'group' => 'content',
			'label' => esc_html__( 'Content typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.accordion-content-wrapper',
				],
			],
		];

		$this->controls['contentBackgroundColor'] = [
			'tab'   => 'content',
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
			'tab'   => 'content',
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
	}

	public function render() {
		$settings     = $this->settings;
		$theme_styles = $this->theme_styles;

		// Icon
		$icon = false;

		if ( ! empty( $settings['icon'] ) ) {
			$icon = self::render_icon( $settings['icon'], [ 'icon' ] );
		} elseif ( ! empty( $theme_styles['accordionIcon'] ) ) {
			$icon = self::render_icon( $theme_styles['accordionIcon'], [ 'icon' ] );
		}

		// Icon expanded
		$icon_expanded = false;

		if ( ! empty( $settings['iconExpanded'] ) ) {
			$icon_expanded = self::render_icon( $settings['iconExpanded'], [ 'icon', 'expanded' ] );
		} elseif ( ! empty( $theme_styles['accordionIconExpanded'] ) ) {
			$icon_expanded = self::render_icon( $theme_styles['accordionIconExpanded'], [ 'icon', 'expanded' ] );
		}

		$title_classes = [ 'accordion-title' ];

		if ( $icon && ! empty( $settings['iconPosition'] ) ) {
			$title_classes[] = "icon-{$settings['iconPosition']}";
		}

		$this->set_attribute( 'accordion-title', 'class', $title_classes );

		// STEP: Render Accordionss
		$accordions = ! empty( $settings['accordions'] ) ? $settings['accordions'] : false;

		if ( ! $accordions ) {
			return $this->render_element_placeholder(
				[
					'title' => esc_html__( 'No accordion item added.', 'bricks' ),
				]
			);
		}

		$title_tag = ! empty( $settings['titleTag'] ) ? $settings['titleTag'] : 'h5';

		// Expand first item, Independent toggle
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

		$output = "<ul {$this->render_attributes( '_root' )}>";

		// Query Loop
		if ( isset( $settings['hasLoop'] ) ) {
			$query = new Query(
				[
					'id'       => $this->id,
					'settings' => $settings,
				]
			);

			$accordion = $accordions[0];

			$output .= $query->render( [ $this, 'render_repeater_item' ], compact( 'accordion', 'title_tag', 'icon', 'icon_expanded' ) );

			// Destroy query to explicitly remove it from the global store
			$query->destroy();
			unset( $query );
		} else {
			foreach ( $accordions as $index => $accordion ) {
				$output .= self::render_repeater_item( $accordion, $title_tag, $icon, $icon_expanded );
			}
		}

		$output .= '</ul>';

		echo $output;
	}

	public function render_repeater_item( $accordion, $title_tag, $icon, $icon_expanded ) {
		$settings = $this->settings;
		$index    = $this->loop_index;
		$output   = '';

		// Set 'id' to open & scroll to specific tab (@since 1.8.6)
		if ( ! empty( $accordion['anchorId'] ) ) {
			$this->set_attribute( "accordion-item-$index", 'id', $accordion['anchorId'] );
		}

		$this->set_attribute( "accordion-item-$index", 'class', [ 'accordion-item' ] );

		$output .= "<li {$this->render_attributes( "accordion-item-$index" )}>";

		if ( ! empty( $accordion['title'] ) || ! empty( $accordion['subtitle'] ) ) {
			$this->set_attribute( "accordion-title-wrapper-$index", 'class', [ 'accordion-title-wrapper' ] );

			$output .= '<div class="accordion-title-wrapper">';

			if ( ! empty( $accordion['title'] ) ) {
				$output .= "<div {$this->render_attributes( 'accordion-title' )}>";

				$this->set_attribute( "accordion-title-$index", 'class', [ 'title' ] );

				$output .= "<$title_tag {$this->render_attributes( "accordion-title-$index" )}>" . $this->render_dynamic_data( $accordion['title'] ) . "</$title_tag>";

				if ( $icon_expanded ) {
					$output .= $icon_expanded;
				}

				if ( $icon ) {
					$output .= $icon;
				}

				$output .= '</div>';
			}

			if ( ! empty( $accordion['subtitle'] ) ) {
				$this->set_attribute( "accordion-subtitle-$index", 'class', [ 'accordion-subtitle' ] );

				$output .= "<div {$this->render_attributes( "accordion-subtitle-$index" )}>" . $this->render_dynamic_data( $accordion['subtitle'] ) . '</div>';
			}

			$output .= '</div>';
		}

		$content = ! empty( $accordion['content'] ) ? $accordion['content'] : false;

		if ( $content ) {
			$this->set_attribute( "accordion-content-$index", 'class', [ 'accordion-content-wrapper' ] );

			$content = $this->render_dynamic_data( $content );

			$content = Helpers::parse_editor_content( $content );

			$output .= "<div {$this->render_attributes( "accordion-content-$index" )}>$content</div>";
		}

		$output .= '</li>';

		$this->loop_index++;

		return $output;
	}
}
