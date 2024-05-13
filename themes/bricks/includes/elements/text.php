<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Text extends Element {
	public $block    = [ 'core/paragraph', 'core/list' ];
	public $category = 'basic';
	public $name     = 'text';
	public $icon     = 'ti-align-left';

	public function get_label() {
		return esc_html__( 'Rich Text', 'bricks' );
	}

	public function set_controls() {
		$this->controls['_background']['css'][0]['selector'] = '';
		$this->controls['_border']['css'][0]['selector']     = '';

		// Typography set in element should precede theme style link styles
		$this->controls['_typography']['css'][] = [
			'selector' => $this->css_selector . ' a',
			'property' => 'font',
		];

		// Inherit font-size set in typograhy on links to prevent issue with units like 'em' (@since 1.9.6)
		$this->controls['_typography']['css'][] = [
			'selector' => $this->css_selector . ' a',
			'property' => 'font-size',
			'value'    => 'inherit',
		];

		$this->controls['text'] = [
			'tab'     => 'content',
			'type'    => 'editor',
			'default' => '<p>' . esc_html__( 'Here goes your text ... Select any part of your text to access the formatting toolbar.', 'bricks' ) . '</p>',
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

		$this->controls['wordsLimit'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Words limit', 'bricks' ),
			'type'  => 'number',
			'min'   => 1,
		];

		$this->controls['readMore'] = [
			'tab'            => 'content',
			'label'          => esc_html__( 'Read more', 'bricks' ),
			'type'           => 'text',
			'inline'         => true,
			'hasDynamicData' => false,
			'required'       => [ 'wordsLimit', '!=', '' ],
		];
	}

	public function render() {
		$settings = $this->settings;

		if ( ! isset( $settings['text'] ) ) {
			return;
		}

		$content = $settings['text'];

		$content = $this->render_dynamic_data( $content );

		$content = Helpers::parse_editor_content( $content );

		// Trimming the content to the specified number of words while handling HTML tags properly (@since 1.9.3)
		if ( ! empty( $settings['wordsLimit'] ) && is_numeric( $settings['wordsLimit'] ) ) {
			$more    = $settings['readMore'] ?? '';
			$content = Helpers::trim_words( $content, $settings['wordsLimit'], $more, true );
		}

		if ( ! empty( $settings['type'] ) ) {
			$this->set_attribute( '_root', 'class', "bricks-type-{$settings['type']}" );
		}

		if ( ! empty( $settings['style'] ) ) {
			$this->set_attribute( '_root', 'class', "bricks-color-{$settings['style']}" );
		}

		echo "<div {$this->render_attributes( '_root' )}>{$content}</div>";
	}

	public static function render_builder() { ?>
		<script type="text/x-template" id="tmpl-bricks-element-text">
			<contenteditable
				:name="name"
				controlKey="text"
				toolbar="true"
				:settings="settings"
				:class="[
					settings.type ? `bricks-type-${settings.type}` : null,
					settings.style ? `bricks-color-${settings.style}` : null
				]"
			/>
		</script>
		<?php
	}

	public function convert_element_settings_to_block( $settings ) {
		$block = [ 'blockName' => $this->block ];

		$block['attrs']        = [];
		$block['innerContent'] = [];

		if ( ! isset( $settings['text'] ) ) {
			return;
		}

		$text = trim( $settings['text'] );

		// Get block type by HTML tag: <p>, <ul>, <ol>
		preg_match_all( '#<(p|ul|ol)>(.*?)</\1>#is', $text, $matches );

		$tags     = $matches[1];
		$contents = $matches[2];

		if ( ! isset( $tags[0] ) ) {
			return $block;
		}

		switch ( $tags[0] ) {
			case 'p':
				$block['blockName']    = 'core/paragraph';
				$block['innerContent'] = [ $text ];
				break;

			case 'ul':
				$block['blockName']    = 'core/list';
				$block['innerContent'] = [ $text ];
				break;

			case 'ol':
				$block['blockName']        = 'core/list';
				$block['innerContent']     = [ $text ];
				$block['attrs']['ordered'] = true;
				break;
		}

		return $block;
	}

	public function convert_block_to_element_settings( $block, $attributes ) {
		$text = $block['innerHTML'];

		// Check for content
		$has_text = strip_tags( $text ); // Remove <p> tag
		$has_text = str_replace( [ "\r", "\n" ], '', $has_text ); // Remove line breaks

		if ( ! $has_text ) {
			return;
		}

		return [ 'text' => $text ];
	}
}
