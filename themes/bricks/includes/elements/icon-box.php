<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Icon_Box extends Element {
	public $category = 'general';
	public $name     = 'icon-box';
	public $icon     = 'ti-check-box';

	public function get_label() {
		return esc_html__( 'Icon Box', 'bricks' );
	}

	public function set_control_groups() {
		$this->control_groups['icon'] = [
			'title' => esc_html__( 'Icon', 'bricks' ),
			'tab'   => 'content',
		];

		$this->control_groups['content'] = [
			'title' => esc_html__( 'Content', 'bricks' ),
			'tab'   => 'content',
		];

		unset( $this->control_groups['_typography'] );
	}

	public function set_controls() {
		$this->controls['direction'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Direction', 'bricks' ),
			'type'     => 'direction',
			'css'      => [
				[
					'property' => 'flex-direction',
				],
			],
			'inline'   => true,
			'rerender' => true,
		];

		$this->controls['gap'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Spacing', 'bricks' ),
			'type'        => 'number',
			'units'       => true,
			'css'         => [
				[
					'property' => 'gap',
				],
			],
			'placeholder' => '', // 'normal' via computed styles
		];

		// Group: Icon

		$this->controls['icon'] = [
			'tab'      => 'content',
			'group'    => 'icon',
			'label'    => esc_html__( 'Icon', 'bricks' ),
			'type'     => 'icon',
			'default'  => [
				'library' => 'themify',
				'icon'    => 'ti-wordpress',
			],
			'rerender' => true,
		];

		$this->controls['verticalAlign'] = [
			'tab'     => 'content',
			'group'   => 'icon',
			'label'   => esc_html__( 'Align', 'bricks' ),
			'type'    => 'align-items',
			'exclude' => 'stretch',
			'css'     => [
				[
					'property' => 'align-self',
					'selector' => '.icon',
				],
			],
			'inline'  => true,
		];

		$this->controls['link'] = [
			'tab'   => 'content',
			'group' => 'icon',
			'label' => esc_html__( 'Link', 'bricks' ),
			'type'  => 'link',
		];

		$this->controls['iconMargin'] = [
			'tab'   => 'content',
			'group' => 'icon',
			'label' => esc_html__( 'Margin', 'bricks' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'margin',
					'selector' => '.icon',
				],
			],
		];

		$this->controls['iconPadding'] = [
			'tab'   => 'content',
			'group' => 'icon',
			'label' => esc_html__( 'Padding', 'bricks' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'padding',
					'selector' => '.icon',
				],
			],
		];

		$this->controls['iconPosition'] = [
			'deprecated'  => true, // No longer needed with 'direction' setting
			'tab'         => 'content',
			'group'       => 'icon',
			'label'       => esc_html__( 'Position', 'bricks' ),
			'type'        => 'select',
			'options'     => [
				'top'    => esc_html__( 'Top', 'bricks' ),
				'right'  => esc_html__( 'Right', 'bricks' ),
				'bottom' => esc_html__( 'Bottom', 'bricks' ),
				'left'   => esc_html__( 'Left', 'bricks' ),
			],
			'inline'      => true,
			'placeholder' => esc_html__( 'Top', 'bricks' ),
		];

		$this->controls['textAlign'] = [
			'deprecated' => true, // No longer needed with 'direction' setting
			'tab'        => 'content',
			'group'      => 'icon',
			'label'      => esc_html__( 'Text align', 'bricks' ),
			'type'       => 'text-align',
			'css'        => [
				[
					'property' => 'text-align',
				],
				[
					'property' => 'align-self',
					'selector' => '.icon',
				],
			],
			'inline'     => true,
		];

		$this->controls['iconSize'] = [
			'tab'      => 'content',
			'group'    => 'icon',
			'label'    => esc_html__( 'Size', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'font-size',
					'selector' => '.icon i',
				],
			],
			'required' => [ 'icon.icon', '!=', '' ],
		];

		$this->controls['iconHeight'] = [
			'tab'   => 'content',
			'group' => 'icon',
			'label' => esc_html__( 'Height', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'css'   => [
				[
					'property' => 'height',
					'selector' => '.icon',
				],
				[
					'property' => 'line-height',
					'selector' => '.icon',
				],
			],
		];

		$this->controls['iconWidth'] = [
			'tab'   => 'content',
			'group' => 'icon',
			'label' => esc_html__( 'Width', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'css'   => [
				[
					'property' => 'min-width',
					'selector' => '.icon',
				],
			],
		];

		$this->controls['iconColor'] = [
			'tab'      => 'content',
			'group'    => 'icon',
			'label'    => esc_html__( 'Color', 'bricks' ),
			'type'     => 'color',
			'css'      => [
				[
					'property' => 'color',
					'selector' => '.icon',
				],
				[
					'property' => 'color',
					'selector' => '.icon a',
				],
			],
			'required' => [ 'icon.icon', '!=', '' ],
		];

		$this->controls['iconBackgroundColor'] = [
			'tab'   => 'content',
			'group' => 'icon',
			'label' => esc_html__( 'Background color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.icon',
				],
			],
		];

		$this->controls['iconBorder'] = [
			'tab'   => 'content',
			'group' => 'icon',
			'label' => esc_html__( 'Border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.icon',
				],
			],
		];

		$this->controls['iconBoxShadow'] = [
			'tab'   => 'content',
			'group' => 'icon',
			'label' => esc_html__( 'Box shadow', 'bricks' ),
			'type'  => 'box-shadow',
			'css'   => [
				[
					'property' => 'box-shadow',
					'selector' => '.icon',
				],
			],
		];

		// Group: Content

		$this->controls['content'] = [
			'tab'     => 'content',
			'group'   => 'content',
			'type'    => 'editor',
			// 'toolbar' => true, // NOTE: Not in use
			'default' => '<h4>Icon box heading</h4><p>.. followed by some bogus content. Aenean commodo ligula egget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus.</p>',
		];

		unset( $this->controls['_typography'] );

		$this->controls['contentMargin'] = [
			'tab'   => 'style',
			'group' => 'content',
			'type'  => 'spacing',
			'label' => esc_html__( 'Margin', 'bricks' ),
			'css'   => [
				[
					'property' => 'margin',
					'selector' => '.content',
				],
			],
		];

		$this->controls['contentPadding'] = [
			'tab'   => 'style',
			'group' => 'content',
			'type'  => 'spacing',
			'label' => esc_html__( 'Padding', 'bricks' ),
			'css'   => [
				[
					'property' => 'padding',
					'selector' => '.content',
				],
			],
		];

		$this->controls['contentAlign'] = [
			'tab'     => 'content',
			'group'   => 'content',
			'label'   => esc_html__( 'Align', 'bricks' ),
			'type'    => 'align-items',
			'exclude' => 'stretch',
			'css'     => [
				[
					'property' => 'align-self',
					'selector' => '.content',
				],
			],
			'inline'  => true,
		];

		$this->controls['typographyHeading'] = [
			'tab'   => 'style',
			'group' => 'content',
			'type'  => 'typography',
			'label' => esc_html__( 'Heading typography', 'bricks' ),
			'css'   => [
				[
					'property' => 'font',
					'selector' => 'h1',
				],
				[
					'property' => 'font',
					'selector' => 'h2',
				],
				[
					'property' => 'font',
					'selector' => 'h3',
				],
				[
					'property' => 'font',
					'selector' => 'h4',
				],
				[
					'property' => 'font',
					'selector' => 'h5',
				],
				[
					'property' => 'font',
					'selector' => 'h6',
				],
			],
		];

		$this->controls['typographyBody'] = [
			'tab'   => 'style',
			'group' => 'content',
			'type'  => 'typography',
			'label' => esc_html__( 'Body typography', 'bricks' ),
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.content',
				],
			],
		];

		$this->controls['contentBackgroundColor'] = [
			'tab'   => 'style',
			'group' => 'content',
			'type'  => 'color',
			'label' => esc_html__( 'Background', 'bricks' ),
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.content',
				],
			],
		];

		$this->controls['contentBorder'] = [
			'tab'   => 'style',
			'group' => 'content',
			'type'  => 'border',
			'label' => esc_html__( 'Border', 'bricks' ),
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.content',
				],
			],
		];

		$this->controls['contentBoxShadow'] = [
			'tab'   => 'style',
			'group' => 'content',
			'type'  => 'box-shadow',
			'label' => esc_html__( 'Box shadow', 'bricks' ),
			'css'   => [
				[
					'property' => 'box-shadow',
					'selector' => '.content',
				],
			],
		];
	}

	public function render() {
		$settings = $this->settings;

		if ( ! empty( $settings['iconPosition'] ) ) {
			$this->set_attribute( '_root', 'class', $settings['iconPosition'] );
		}
		$this->set_attribute( 'content', 'class', [ 'content' ] );

		$output = "<div {$this->render_attributes( '_root' )}>";

		$icon = empty( $settings['icon'] ) ? false : self::render_icon( $settings['icon'] );

		if ( $icon ) {
			$output .= '<div class="icon">';

			if ( ! empty( $settings['link'] ) ) {
				$this->set_link_attributes( 'a', $settings['link'] );

				$output .= "<a {$this->render_attributes( 'a' )}>";
			}

			$output .= $icon;

			if ( ! empty( $settings['link'] ) ) {
				$output .= '</a>';
			}

			$output .= '</div>';
		}

		$content = ! empty( $settings['content'] ) ? $settings['content'] : false;

		if ( $content ) {
			$content = $this->render_dynamic_data( $content );

			$output .= "<div {$this->render_attributes( 'content' )}>" . Helpers::parse_editor_content( $content ) . '</div>';
		}

		$output .= '</div>';

		echo $output;
	}

	public static function render_builder() { ?>
		<script type="text/x-template" id="tmpl-bricks-element-icon-box">
			<component :is="tag" :class="[settings.iconPosition ? settings.iconPosition : null]">
				<div v-if="settings?.icon?.icon || settings?.icon?.svg" class="icon">
					<a v-if="settings.link"><icon-svg :iconSettings="settings.icon"/></a>
					<icon-svg v-else :iconSettings="settings.icon"/>
				</div>

				<contenteditable
					v-if="settings.content"
					class="content"
					:name="name"
					controlKey="content"
					toolbar="true"
					:settings="settings"/>
			</component>
		</script>
		<?php
	}
}
