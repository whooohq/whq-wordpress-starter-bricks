<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Html extends Element {
	public $block      = 'core/html';
	public $category   = 'general';
	public $name       = 'html';
	public $icon       = 'ti-html5';
	public $deprecated = true; // NOTE Undocumented

	public function get_label() {
		return esc_html__( 'HTML', 'bricks' );
	}

	public function set_controls() {
		$this->controls['html'] = [
			'tab'     => 'content',
			'label'   => esc_html__( 'Raw HTML', 'bricks' ),
			'type'    => 'code',
			'default' => "<h4>Raw HTML title</h4>\n<p>Just a simple paragraph ..</p>",
		];
	}

	public function render() {
		if ( ! empty( $this->settings['html'] ) ) {
			echo $this->settings['html'];
		} else {
			return $this->render_element_placeholder(
				[
					'title' => esc_html__( 'No HTML markup defined.', 'bricks' ),
				]
			);
		}
	}

	public static function render_builder() { ?>
		<script type="text/x-template" id="tmpl-bricks-element-html">
			<div v-if="settings.html" v-html="settings.html"></div>
			<div v-else v-html="renderElementPlaceholder()"></div>
		</script>
		<?php
	}

	public function convert_element_settings_to_block( $settings ) {
		$html = trim( $settings['html'] );

		$block = [
			'blockName'    => $this->block,
			'attrs'        => [],
			'innerContent' => isset( $html ) ? [ $html ] : '',
		];

		return $block;
	}

	public function convert_block_to_element_settings( $block, $attributes ) {
		return [ 'html' => trim( $block['innerHTML'] ) ];
	}
}
