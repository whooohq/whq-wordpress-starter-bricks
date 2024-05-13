<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Text_Basic extends Element {
	public $block    = 'core/paragraph';
	public $category = 'basic';
	public $name     = 'text-basic';
	public $icon     = 'ti-align-justify';

	public function get_label() {
		return esc_html__( 'Basic Text', 'bricks' );
	}

	public function set_controls() {
		$this->controls['text'] = [
			'tab'         => 'content',
			'type'        => 'textarea',
			'lineBreak'   => 'br',
			'default'     => esc_html__( 'Here goes your text ... Select any part of your text to access the formatting toolbar.', 'bricks' ),
			'description' => esc_html__( 'Select text on canvas to format it. To add headings, paragraphs, and images use the "Rich Text" element.', 'bricks' ),
		];

		$this->controls['tag'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'HTML tag', 'bricks' ),
			'type'        => 'select',
			'options'     => [
				'div'        => 'div',
				'p'          => 'p',
				'span'       => 'span',
				'figcaption' => 'figcaption',
				'address'    => 'address',
				'figure'     => 'figure',
				'custom'     => esc_html__( 'Custom', 'bricks' ),
			],
			'lowercase'   => true,
			'inline'      => true,
			'placeholder' => 'div',
		];

		$this->controls['customTag'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Custom tag', 'bricks' ),
			'type'        => 'text',
			'inline'      => true,
			'placeholder' => 'div',
			'required'    => [ 'tag', '=', 'custom' ],
		];

		$this->controls['link'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Link to', 'bricks' ),
			'type'  => 'link',
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

		// Set $no_root to true if content contains {do_action:...} (@since 1.9.1, @see #2yddfub)
		$no_root = preg_match( '/{do_action:/', $content );

		// Resolve some {do_action} not fully working in certain cases (@see #862je3dz8)
		$content = $this->render_dynamic_data( $content );

		// Enforce words limit (@since 1.9.3)
		if ( ! empty( $settings['wordsLimit'] ) && is_numeric( $settings['wordsLimit'] ) ) {
			$more    = $settings['readMore'] ?? '';
			$content = Helpers::trim_words( $content, $settings['wordsLimit'], $more, true );
		}

		// Link
		if ( ! empty( $settings['link'] ) ) {
			$this->set_link_attributes( '_root', $settings['link'] );
			$this->tag = 'a';
		}

		if ( $no_root && ! bricks_is_builder() && ! bricks_is_builder_call() ) {
			echo $content;
		} else {
			echo "<{$this->tag} {$this->render_attributes( '_root' )}>{$content}</{$this->tag}>";
		}
	}

	public static function render_builder() { ?>
		<script type="text/x-template" id="tmpl-bricks-element-text-basic">
			<contenteditable
				:key="tag"
				:name="name"
				controlKey="text"
				toolbar="style align link"
				lineBreak="br"
				:settings="settings"/>
		</script>
		<?php
	}

	public function convert_element_settings_to_block( $settings ) {
		if ( ! isset( $settings['text'] ) ) {
			return;
		}

		$block = [
			'blockName'    => $this->block,
			'attrs'        => [],
			'innerContent' => [ trim( $settings['text'] ) ],
		];

		return $block;
	}

	// NOTE: Convert block to element settings: Use Bricks "Rich Text" element instead
	// public function convert_block_to_element_settings( $block, $attributes ) {}
}
