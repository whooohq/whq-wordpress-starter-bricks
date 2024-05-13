<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Heading extends Element {
	public $block    = 'core/heading';
	public $category = 'basic';
	public $name     = 'heading';
	public $icon     = 'ti-text';
	public $tag      = 'h3';

	public function get_label() {
		return esc_html__( 'Heading', 'bricks' );
	}

	public function set_control_groups() {
		$this->control_groups['separator'] = [
			'title'      => esc_html__( 'separator', 'bricks' ),
			'tab'        => 'content',
			'fullAccess' => true, // NOTE: Undocumented (show if user role has full_access capability)
		];
	}

	public function set_controls() {
		$this->controls['text'] = [
			'tab'         => 'content',
			'type'        => 'text',
			'default'     => esc_html__( 'I am a heading', 'bricks' ),
			'placeholder' => esc_html__( 'Here goes my heading ..', 'bricks' ),
		];

		$this->controls['tag'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'HTML tag', 'bricks' ),
			'type'        => 'select',
			'options'     => [
				'h1'     => 'h1',
				'h2'     => 'h2',
				'h3'     => 'h3',
				'h4'     => 'h4',
				'h5'     => 'h5',
				'h6'     => 'h6',
				'custom' => esc_html__( 'Custom', 'bricks' ),
			],
			'inline'      => true,
			'placeholder' => ! empty( $this->theme_styles['tag'] ) ? $this->theme_styles['tag'] : 'h3',
		];

		$this->controls['customTag'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Custom tag', 'bricks' ),
			'type'        => 'text',
			'inline'      => true,
			'placeholder' => 'div',
			'required'    => [ 'tag', '=', 'custom' ],
		];

		$this->controls['type'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Type', 'bricks' ),
			'type'        => 'select',
			'options'     => [
				'hero' => esc_html__( 'Hero', 'bricks' ),
				'lead' => esc_html__( 'Lead', 'bricks' ),
			],
			'inline'      => true,
			'reset'       => true,
			'placeholder' => esc_html__( 'None', 'bricks' ),
		];

		$this->controls['style'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Style', 'bricks' ),
			'type'        => 'select',
			'options'     => $this->control_options['styles'],
			'inline'      => true,
			'reset'       => true,
			'placeholder' => esc_html__( 'None', 'bricks' ),
		];

		$this->controls['link'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Link to', 'bricks' ),
			'type'  => 'link',
		];

		// Group: Separator

		$this->controls['separator'] = [
			'tab'         => 'content',
			'group'       => 'separator',
			'label'       => esc_html__( 'Separator', 'bricks' ),
			'type'        => 'select',
			'options'     => [
				'right' => esc_html__( 'Right', 'bricks' ),
				'left'  => esc_html__( 'Left', 'bricks' ),
				'both'  => esc_html__( 'Both', 'bricks' ),
				'none'  => esc_html__( 'None', 'bricks' ),
			],
			'inline'      => true,
			'pasteStyles' => true,
			'placeholder' => esc_html__( 'None', 'bricks' ),
		];

		$this->controls['separatorWidth'] = [
			'tab'   => 'content',
			'group' => 'separator',
			'label' => esc_html__( 'Width', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'css'   => [
				[
					'property' => 'width',
					'selector' => '.separator',
				],
				[
					'property' => 'flex-grow',
					'selector' => '.separator',
					'value'    => 0,
				],
				// To allow self-align heading
				[
					'property' => 'width',
					'selector' => '',
					'value'    => 'auto',
				],
			],
		];

		$this->controls['separatorHeight'] = [
			'tab'   => 'content',
			'group' => 'separator',
			'label' => esc_html__( 'Height', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'css'   => [
				[
					'property' => 'border-top-width',
					'selector' => '.separator',
				],
				[
					'property' => 'height',
					'selector' => '.separator',
				],
			],
		];

		$this->controls['separatorSpacing'] = [
			'tab'         => 'content',
			'group'       => 'separator',
			'label'       => esc_html__( 'Spacing', 'bricks' ),
			'type'        => 'number',
			'units'       => true,
			'small'       => false,
			'css'         => [
				[
					'property' => 'gap',
					'selector' => '&.has-separator',
				],
			],
			'placeholder' => 20,
		];

		$this->controls['separatorStyle'] = [
			'tab'     => 'content',
			'group'   => 'separator',
			'label'   => esc_html__( 'Style', 'bricks' ),
			'type'    => 'select',
			'options' => $this->control_options['borderStyle'],
			'css'     => [
				[
					'property' => 'border-top-style',
					'selector' => '.separator',
				],
			],
			'inline'  => true,
		];

		$this->controls['separatorAlignItems'] = [
			'tab'       => 'content',
			'group'     => 'separator',
			'label'     => esc_html__( 'Align', 'bricks' ),
			'type'      => 'align-items',
			'direction' => 'row',
			'exclude'   => 'stretch',
			'inline'    => true,
			'css'       => [
				[
					'property'  => 'align-items',
					'selector'  => '&.has-separator',
					'important' => true,
				],
			],
		];

		$this->controls['separatorColor'] = [
			'tab'   => 'content',
			'group' => 'separator',
			'label' => esc_html__( 'Color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'border-top-color',
					'selector' => '.separator',
				],
			],
		];
	}

	public function render() {
		$settings = $this->settings;

		if ( ! empty( $settings['type'] ) ) {
			$this->set_attribute( '_root', 'class', "bricks-type-{$settings['type']}" );
		}

		if ( ! empty( $settings['style'] ) ) {
			$this->set_attribute( '_root', 'class', "bricks-color-{$settings['style']}" );
		}

		// Separator (check theme style, then element settings)
		$separator = ! empty( $this->theme_styles['separator'] ) ? $this->theme_styles['separator'] : 'none';

		if ( ! empty( $settings['separator'] ) ) {
			$separator = $settings['separator'];
		}

		if ( $separator !== 'none' ) {
			$this->set_attribute( '_root', 'class', 'has-separator' );
		}

		// Render
		$output = "<{$this->tag} {$this->render_attributes( '_root' )}>";

		if ( $separator === 'left' || $separator === 'both' ) {
			$output .= '<span class="separator left"></span>';
		}

		if ( isset( $settings['link'] ) ) {
			$this->set_link_attributes( 'a', $settings['link'] );
			$output .= "<a {$this->render_attributes( 'a' )}>";
		}

		if ( isset( $settings['text'] ) ) {
			$output .= isset( $settings['separator'] ) ? '<span class="text">' . $settings['text'] . '</span>' : $settings['text'];
		}

		if ( isset( $settings['link'] ) ) {
			$output .= '</a>';
		}

		// Separator
		if ( $separator === 'right' || $separator === 'both' ) {
			$output .= '<span class="separator right"></span>';
		}

		$output .= "</{$this->tag}>";

		echo $output;
	}

	public static function render_builder() { ?>
		<script type="text/x-template" id="tmpl-bricks-element-heading">
			<component
				:is="tag"
				:name="name"
				:lineBreak="'br'"
				:class="[
					settings.type ? `bricks-type-${settings.type}` : null,
					settings.style ? `bricks-color-${settings.style}` : null,
					(settings.separator && settings.separator !== 'none') || (!settings.separator && themeStyles.separator && themeStyles.separator !== 'none') ? 'has-separator' : null
				]">
				<span v-if="settings.separator && (settings.separator === 'left' || settings.separator === 'both')" class="separator left"></span>
				<span v-else-if="!settings.separator && themeStyles.separator && (themeStyles.separator === 'left' || themeStyles.separator === 'both')" class="separator left"></span>

				<contenteditable
					v-if="settings.link"
					tag="a"
					:name="name"
					:lineBreak="'br'"
					controlKey="text"
					toolbar="style align"
					:settings="settings"/>

				<contenteditable
					v-else
					tag="div"
					:name="name"
					:lineBreak="'br'"
					controlKey="text"
					toolbar="style align"
					:settings="settings"/>

					<span v-if="settings.separator && (settings.separator === 'right' || settings.separator === 'both')" class="separator right"></span>
					<span v-else-if="!settings.separator && themeStyles.separator && (themeStyles.separator === 'right' || themeStyles.separator === 'both')" class="separator right"></span>
			</component>
		</script>
		<?php
	}

	public function convert_element_settings_to_block( $settings ) {
		$block = [ 'blockName' => $this->block ];

		$attrs = [ 'level' => isset( $settings['tag'] ) ? intval( str_replace( 'h', '', $settings['tag'] ) ) : 3 ];
		$tag   = 'h' . $attrs['level'];
		$text  = isset( $settings['text'] ) ? trim( $settings['text'] ) : '';
		$html  = "<$tag>$text</$tag>";

		if ( isset( $settings['_typography']['text-align'] ) ) {
			$attrs['align'] = $settings['_typography']['text-align'];
			$html           = '<' . $tag . ' class="has-text-align-' . esc_attr( $attrs['align'] ) . '">' . "$text</$tag>";
		}

		$block['attrs']        = $attrs;
		$block['innerContent'] = [ $html ];

		return $block;
	}

	public function convert_block_to_element_settings( $block, $attributes ) {
		$element_settings = [
			'text' => strip_tags( $block['innerHTML'], '<a><em><strong>' ),
			'tag'  => isset( $attributes['level'] ) ? 'h' . $attributes['level'] : 'h2',
		];

		if ( isset( $attributes['align'] ) ) {
			$element_settings['_typography']['text-align'] = $attributes['align'];
		}

		return $element_settings;
	}
}
