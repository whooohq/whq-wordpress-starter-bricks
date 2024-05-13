<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Post_Excerpt extends Element {
	public $category = 'single';
	public $name     = 'post-excerpt';
	public $icon     = 'ti-paragraph';

	public function get_label() {
		return esc_html__( 'Excerpt', 'bricks' );
	}

	public function set_controls() {
		$this->controls['info'] = [
			'tab'     => 'content',
			'type'    => 'info',
			'content' => sprintf( '<a href="https://codex.wordpress.org/Excerpt" target="_blank">%s</a>', esc_html__( 'Learn more on wordpress.org', 'bricks' ) ),
		];

		$this->controls['length'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Excerpt length', 'bricks' ),
			'type'        => 'number',
			'max'         => 999,
			'placeholder' => 15,
		];

		$this->controls['more'] = [
			'tab'            => 'content',
			'label'          => esc_html__( 'More text', 'bricks' ),
			'type'           => 'text',
			'inline'         => true,
			'small'          => true,
			'hasDynamicData' => false,
			'placeholder'    => '...',
		];

		// @since 1.6.2
		$this->controls['keepHTML'] = [
			'tab'     => 'content',
			'label'   => esc_html__( 'Keep formatting', 'bricks' ),
			'type'    => 'checkbox',
			'default' => false,
		];
	}

	public function render() {
		// Inside a Query Loop
		if ( Query::is_looping() ) {
			$loop_object = Query::get_loop_object();

			// Not looping a WP_Post query
			if ( ! is_a( $loop_object, 'WP_Post' ) ) {
				if ( ! empty( $loop_object->description ) ) {
					$this->render_description( $loop_object->description );
				} else {
					$this->render_no_excerpt();
				}

				return;
			}
		}

		// Not inside a Query Loop: Use taxonomy or author description
		elseif ( is_archive() ) {
			$queried_object = get_queried_object();

			if ( ! empty( $queried_object->description ) ) {
				$this->render_description( $queried_object->description );
			} else {
				$this->render_no_excerpt();
			}

			return;
		}

		// We are in a Query Loop and looping a WP_Post or we are in a single post
		$this->render_post_excerpt();
	}

	/**
	 * Render taxonomy or author description
	 *
	 * @param string $description
	 *
	 * @since 1.6.2
	 */
	public function render_description( $description ) {
		$settings = $this->settings;

		$length    = isset( $settings['length'] ) ? $settings['length'] : 15;
		$more      = isset( $settings['more'] ) ? $settings['more'] : '&hellip;';
		$keep_html = isset( $settings['keepHTML'] );

		$text = Helpers::trim_words( $description, $length, $more, $keep_html );

		echo '<div ' . $this->render_attributes( '_root' ) . '>' . $text . '</div>';
	}

	/**
	 * Render post excerpt
	 *
	 * @since 1.6.2
	 */
	public function render_post_excerpt() {
		$settings = $this->settings;

		$length    = isset( $settings['length'] ) ? $settings['length'] : 15;
		$more      = isset( $settings['more'] ) ? $settings['more'] : '&hellip;';
		$keep_html = isset( $settings['keepHTML'] );

		$excerpt = Helpers::get_the_excerpt( $this->post_id, $length, $more, $keep_html );
		$excerpt = apply_filters( 'the_excerpt', $excerpt );

		if ( ! $excerpt ) {
			$this->render_no_excerpt();
		}

		echo "<div {$this->render_attributes( '_root' )}>$excerpt</div>";
	}

	/**
	 * Render no excerpt
	 *
	 * @since 1.6.2
	 */
	public function render_no_excerpt() {
		return $this->render_element_placeholder( [ 'title' => esc_html__( 'No excerpt found.', 'bricks' ) ] );
	}
}
