<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Post_Taxonomy extends Element {
	public $category     = 'single';
	public $name         = 'post-taxonomy';
	public $icon         = 'ti-clip';
	public $css_selector = '.bricks-button';

	public function get_label() {
		return esc_html__( 'Taxonomy', 'bricks' );
	}

	public function set_controls() {
		$this->controls['_margin']['css'][0]['selector'] = '';

		$this->controls['taxonomy'] = [
			'tab'       => 'content',
			'label'     => esc_html__( 'Taxonomy', 'bricks' ),
			'type'      => 'select',
			'options'   => Setup::$control_options['taxonomies'],
			'clearable' => false,
			'default'   => 'post_tag',
		];

		$this->controls['linkDisable'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Disable link', 'bricks' ),
			'type'  => 'checkbox',
		];

		$this->controls['separator'] = [
			'tab'    => 'content',
			'label'  => esc_html__( 'Separator', 'bricks' ),
			'type'   => 'text',
			'inline' => true,
		];

		$term_order_by = Setup::$control_options['termsOrderBy'];
		unset( $term_order_by['include'] ); // Not needed in this element

		$this->controls['orderby'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Order by', 'bricks' ),
			'type'        => 'select',
			'inline'      => true,
			'options'     => $term_order_by,
			'placeholder' => esc_html__( 'Name', 'bricks' ),
		];

		$this->controls['order'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Order', 'bricks' ),
			'type'        => 'select',
			'inline'      => true,
			'options'     => Setup::$control_options['queryOrder'],
			'placeholder' => esc_html__( 'Ascending', 'bricks' ),
		];

		$this->controls['size'] = [
			'label'       => esc_html__( 'Size', 'bricks' ),
			'type'        => 'select',
			'options'     => $this->control_options['buttonSizes'],
			'inline'      => true,
			'reset'       => true,
			'placeholder' => esc_html__( 'Default', 'bricks' ),
		];

		$this->controls['style'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Style', 'bricks' ),
			'type'        => 'select',
			'options'     => $this->control_options['styles'],
			'inline'      => true,
			'placeholder' => esc_html__( 'None', 'bricks' ),
			'default'     => 'dark',
		];

		$this->controls['gap'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Spacing', 'bricks' ),
			'type'        => 'number',
			'units'       => true,
			'css'         => [
				[
					'selector' => '',
					'property' => 'gap',
				],
			],
			'placeholder' => 10,
		];

		$this->controls['icon'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Icon', 'bricks' ),
			'type'  => 'icon',
		];
	}

	public function render() {
		$settings = $this->settings;

		global $post;

		$post     = get_post( $this->post_id );
		$taxonomy = isset( $settings['taxonomy'] ) ? $settings['taxonomy'] : 'post_tag';
		$args     = [
			'fields'  => 'all',
			'orderby' => ! empty( $settings['orderby'] ) ? $settings['orderby'] : 'name',
			'order'   => ! empty( $settings['order'] ) ? $settings['order'] : 'ASC',
		];

		$terms = wp_get_post_terms( get_the_ID(), $taxonomy, $args );
		$terms = wp_list_filter( $terms, [ 'slug' => 'uncategorized' ], 'NOT' );

		if ( ! count( $terms ) ) {
			return $this->render_element_placeholder(
				[
					// translators: %s is the taxonomy name
					'title' => sprintf( esc_html__( 'This post has no %s terms.', 'bricks' ), ucfirst( get_taxonomy( $taxonomy )->name ) ),
				]
			);
		}

		$this->set_attribute( '_root', 'class', sanitize_html_class( $taxonomy ) );

		if ( ! empty( $settings['separator'] ) ) {
			$this->set_attribute( '_root', 'class', 'separator' );
		}

		$root_tag = empty( $settings['separator'] ) ? 'ul' : 'div';

		echo "<$root_tag {$this->render_attributes( '_root' )}>";

		$output = '';

		$icon = ! empty( $settings['icon'] ) ? self::render_icon( $settings['icon'] ) : false;

		foreach ( $terms as $index => $term_id ) {
			$term_object = get_term( $term_id );

			if ( empty( $settings['separator'] ) ) {
				$button_classes = [ 'bricks-button' ];

				if ( ! empty( $settings['size'] ) ) {
					$button_classes[] = $settings['size'];
				}

				if ( ! empty( $settings['style'] ) ) {
					$button_classes[] = "bricks-background-{$settings['style']}";
				}

				$this->set_attribute( "a-$index", 'class', $button_classes );
			}

			$html_tag = 'a';

			// Disable link and use <span> instead (@since 1.7.2)
			if ( isset( $settings['linkDisable'] ) ) {
				$html_tag = empty( $settings['separator'] ) ? 'span' : '';
			} else {
				$this->set_attribute( "a-$index", 'href', get_term_link( $term_id ) );
			}

			if ( empty( $settings['separator'] ) ) {
				$output .= '<li>';
			}

			if ( $html_tag ) {
				$output .= "<$html_tag {$this->render_attributes( "a-$index" )}>";
			}

			if ( $icon ) {
				$output .= $icon . '<span>';
			}

			$output .= $term_object->name;

			// Add separator (@since 1.7.2)
			if ( $html_tag !== 'a' && ! empty( $settings['separator'] ) && $index !== count( $terms ) - 1 ) {
				$output .= $settings['separator'];
			}

			if ( $icon ) {
				$output .= '</span>';
			}

			if ( $html_tag ) {
				$output .= "</$html_tag>";
			}

			// Add separator (@since 1.7.2)
			if ( $html_tag === 'a' && ! empty( $settings['separator'] ) && $index !== count( $terms ) - 1 ) {
				$output .= '<span>' . $settings['separator'] . '</span>';
			}

			if ( empty( $settings['separator'] ) ) {
				$output .= '</li>';
			}
		}

		echo $output;

		echo "</$root_tag>";
	}
}
