<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Product_Title extends Element {
	public $category = 'woocommerce_product';
	public $name     = 'product-title';
	public $icon     = 'ti-text';
	public $tag      = 'h1';

	public function get_label() {
		return esc_html__( 'Product title', 'bricks' );
	}

	public function set_controls() {
		$this->controls['tag'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'HTML tag', 'bricks' ),
			'type'        => 'select',
			'options'     => [
				'h1' => 'h1',
				'h2' => 'h2',
				'h3' => 'h3',
				'h4' => 'h4',
				'h5' => 'h5',
				'h6' => 'h6',
			],
			'inline'      => true,
			'placeholder' => 'h1',
		];

		$this->controls['prefix'] = [
			'tab'    => 'content',
			'label'  => esc_html__( 'Prefix', 'bricks' ),
			'type'   => 'text',
			'inline' => true,
		];

		$this->controls['prefixBlock'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Prefix block', 'bricks' ),
			'type'     => 'checkbox',
			'inline'   => true,
			'required' => [ 'prefix', '!=', '' ],
		];

		$this->controls['suffix'] = [
			'tab'    => 'content',
			'label'  => esc_html__( 'Suffix', 'bricks' ),
			'type'   => 'text',
			'inline' => true,
		];

		$this->controls['suffixBlock'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Suffix block', 'bricks' ),
			'type'     => 'checkbox',
			'inline'   => true,
			'required' => [ 'suffix', '!=', '' ],
		];

	}

	public function render() {
		$settings = $this->settings;

		$prefix = ! empty( $settings['prefix'] ) ? $settings['prefix'] : false;
		$suffix = ! empty( $settings['suffix'] ) ? $settings['suffix'] : false;
		$output = '';

		if ( $prefix ) {
			$this->set_attribute( 'prefix', 'class', [ 'post-prefix' ] );

			$output .= isset( $settings['prefixBlock'] ) ? "<div {$this->render_attributes( 'prefix' )}>{$prefix}</div>" : "<span {$this->render_attributes( 'prefix' )}>{$prefix}</span>";
		}

		$output .= Helpers::get_the_title( $this->post_id );

		if ( $suffix ) {
			$this->set_attribute( 'suffix', 'class', [ 'post-suffix' ] );

			$output .= isset( $settings['suffixBlock'] ) ? "<div {$this->render_attributes( 'suffix' )}>{$suffix}</div>" : "<span {$this->render_attributes( 'suffix' )}>{$suffix}</span>";
		}

		echo "<{$this->tag} {$this->render_attributes( '_root' )}>{$output}</{$this->tag}>";
	}

	public static function render_builder() { ?>
		<script type="text/x-template" id="tmpl-brxe-product-title">
			<component :is="tag" class="product-title">
				<div v-if="settings.prefix && settings.prefixBlock" class="post-prefix" v-html="settings.prefix"></div>
				<span v-else-if="settings.prefix && !settings.prefixBlock" class="post-prefix" v-html="settings.prefix"></span>

				<span v-html="bricks.wp.post.title"></span>

				<div v-if="settings.suffix && settings.suffixBlock" class="post-suffix" v-html="settings.suffix"></div>
				<span v-else-if="settings.suffix && !settings.suffixBlock" class="post-suffix" v-html="settings.suffix"></span>
			</component>
		</script>
		<?php
	}
}
