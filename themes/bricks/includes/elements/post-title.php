<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Post_Title extends Element {
	public $category = 'single';
	public $name     = 'post-title';
	public $icon     = 'ti-text';
	public $tag      = 'h3';

	public function get_label() {
		return esc_html__( 'Post Title', 'bricks' );
	}

	public function set_controls() {
		$this->controls['titleInfo'] = [
			'tab'      => 'content',
			'content'  => esc_html__( 'Edit title: Settings > Page Settings > SEO', 'bricks' ),
			'type'     => 'info',
			'required' => [ 'postTitle', '=', '', 'pageSettings' ],
		];

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
			'clearable'   => false,
			'inline'      => true,
			'placeholder' => 'h3',
			'default'     => 'h1',
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
			'placeholder' => esc_html__( 'None', 'bricks' ),
		];

		$this->controls['style'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Style', 'bricks' ),
			'type'        => 'select',
			'options'     => $this->control_options['styles'],
			'inline'      => true,
			'placeholder' => esc_html__( 'None', 'bricks' ),
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
			'required' => [ 'suffix', '!=', '' ],
		];

		$this->controls['linkToPost'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Link to post', 'bricks' ),
			'type'  => 'checkbox',
		];

		if ( get_post_type() === BRICKS_DB_TEMPLATE_SLUG ) {
			$this->controls['context'] = [
				'tab'         => 'content',
				'label'       => esc_html__( 'Add context', 'bricks' ),
				'type'        => 'checkbox',
				'description' => esc_html__( 'Add context to title on archive/search templates.', 'bricks' ),
			];
		}
	}

	public function render() {
		$settings     = $this->settings;
		$prefix       = ! empty( $settings['prefix'] ) ? $settings['prefix'] : false;
		$suffix       = ! empty( $settings['suffix'] ) ? $settings['suffix'] : false;
		$context      = isset( $settings['context'] ) ? $settings['context'] : false;
		$link_to_post = isset( $settings['linkToPost'] );
		$output       = '';

		if ( $link_to_post ) {
			$output .= '<a href="' . get_the_permalink( $this->post_id ) . '">';
		}

		if ( $prefix ) {
			$this->set_attribute( 'prefix', 'class', [ 'post-prefix' ] );

			$output .= isset( $settings['prefixBlock'] ) ? "<div {$this->render_attributes( 'prefix' )}>{$prefix}</div>" : "<span {$this->render_attributes( 'prefix' )}>{$prefix}</span>";
		}

		$output .= Helpers::get_the_title( $this->post_id, $context );

		if ( $suffix ) {
			$this->set_attribute( 'suffix', 'class', [ 'post-suffix' ] );

			$output .= isset( $settings['suffixBlock'] ) ? "<div {$this->render_attributes( 'suffix' )}>{$suffix}</div>" : "<span {$this->render_attributes( 'suffix' )}>{$suffix}</span>";
		}

		if ( $link_to_post ) {
			$output .= '</a>';
		}

		if ( isset( $settings['type'] ) ) {
			$this->set_attribute( '_root', 'class', "bricks-type-{$settings['type']}" );
		}

		if ( isset( $settings['style'] ) ) {
			$this->set_attribute( '_root', 'class', "bricks-color-{$settings['style']}" );
		}

		echo "<{$this->tag} {$this->render_attributes( '_root' )}>$output</{$this->tag}>";
	}
}
