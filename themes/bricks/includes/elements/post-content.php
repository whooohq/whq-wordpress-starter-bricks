<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Post_Content extends Element {
	public $category = 'single';
	public $name     = 'post-content';
	public $icon     = 'ti-wordpress';

	public function enqueue_scripts() {
		wp_enqueue_style( 'wp-block-library' );
		wp_enqueue_style( 'global-styles' );
	}

	public function get_label() {
		return esc_html__( 'Post Content', 'bricks' );
	}

	public function set_controls() {
		$post_id = get_the_ID();

		$template_preview_post_id = Helpers::get_template_setting( 'templatePreviewPostId', $post_id );

		if ( $template_preview_post_id ) {
			$post_id = $template_preview_post_id;
		}

		$edit_link = get_edit_post_link( $post_id );

		$this->controls['info'] = [
			'tab'      => 'content',
			'type'     => 'info',
			'content'  => "<a href=\"$edit_link\" target=\"_blank\">" . esc_html__( 'Edit WordPress content (WP admin).', 'bricks' ) . '</a>',
			'required' => [ 'dataSource', '!=', 'bricks' ],
		];

		if ( BRICKS_DB_TEMPLATE_SLUG === get_post_type() ) {
			$this->controls['dataSource'] = [
				'tab'         => 'content',
				'label'       => esc_html__( 'Data source', 'bricks' ),
				'type'        => 'select',
				'options'     => [
					'editor' => 'WordPress',
					'bricks' => 'Bricks',
				],
				'inline'      => true,
				'placeholder' => 'WordPress',
			];
		}
	}

	public function render() {
		$settings    = $this->settings;
		$data_source = ! empty( $settings['dataSource'] ) ? $settings['dataSource'] : '';

		// To apply CSS flex when "Data Source" is set to "bricks"
		if ( $data_source ) {
			$this->set_attribute( '_root', 'data-source', $data_source );
		}

		$output = '';

		// STEP: Render Bricks data
		if ( $data_source === 'bricks' ) {
			// Previewing a template
			if ( Helpers::is_bricks_template( $this->post_id ) ) {
				return $this->render_element_placeholder(
					[
						'title'       => esc_html__( 'For better preview select content to show.', 'bricks' ),
						'description' => esc_html__( 'Go to: Settings > Template Settings > Populate Content', 'bricks' ),
					]
				);
			}

			// Get Bricks data
			$bricks_data = get_post_meta( $this->post_id, BRICKS_DB_PAGE_CONTENT, true );

			if ( empty( $bricks_data ) || ! is_array( $bricks_data ) ) {
				return $this->render_element_placeholder(
					[
						'title' => esc_html__( 'No Bricks data found.', 'bricks' ),
					]
				);
			}

			// Avoid infinite loop
			static $post_content_loop = 0;

			if ( $post_content_loop < 2 ) {
				$post_content_loop++;

				// Store the current main render_data self::$elements
				$store_elements = Frontend::$elements;

				// STEP: Disable lazy load in builder (required in builder when generating frontend data)
				$disable_global_lazy_load = isset( Database::$global_settings['disableLazyLoad'] );
				$disable_page_lazy_load   = isset( Database::$page_settings['disableLazyLoad'] );

				if ( bricks_is_builder_call() ) {
					Database::$global_settings['disableLazyLoad'] = true;
					Database::$page_settings['disableLazyLoad']   = true;
				}

				$output = Frontend::render_data( $bricks_data );

				// STEP: Restore original lazy load setting
				if ( bricks_is_builder_call() ) {
					if ( $disable_global_lazy_load ) {
						Database::$global_settings['disableLazyLoad'] = true;
					} else {
						unset( Database::$global_settings['disableLazyLoad'] );
					}

					if ( $disable_page_lazy_load ) {
						Database::$page_settings['disableLazyLoad'] = true;
					} else {
						unset( Database::$page_settings['disableLazyLoad'] );
					}
				}

				// Reset the main render_data self::$elements
				Frontend::$elements = $store_elements;

				// Add 'style' inline (elements & global classes) in the Builder or Frontend (with Query Loop + External Files)
				if ( bricks_is_builder_call() || ( Query::is_looping() && Database::get_setting( 'cssLoading' ) === 'file' ) ) {
					Assets::$inline_css['content'] = '';

					// Clear the list of elements already styled (@since 1.5)
					Assets::$css_looping_elements = [];

					Assets::generate_css_from_elements( $bricks_data, 'content' );
					$inline_css = Assets::$inline_css['content'];

					// Add global classes CSS (@since 1.5)
					$inline_css_global_classes = Assets::generate_global_classes();
					$inline_css               .= Assets::$inline_css['global_classes'];

					$output .= "\n <style>{$inline_css}</style>";
				}

				$post_content_loop--;
			}
		}

		// STEP: Render WordPress content
		else {
			global $wp_query;
			global $post;

			// Store current global post object
			$current_global_post = $post;
			$current_in_the_loop = $wp_query->in_the_loop;

			// Load current $post_id context
			$post = get_post( $this->post_id );
			setup_postdata( $post );

			// Set global in_the_loop()
			// Some plugins might rely on the `in_the_loop` check (e.g. BuddyBoss)
			$wp_query->in_the_loop = true;

			// Render the content like in the loop (@since 1.5)
			ob_start();

			// Render attachment for post type 'attachment' template (@since 1.5.5)
			if ( is_attachment() ) {
				add_filter( 'the_content', 'prepend_attachment' );
			}

			the_content();

			// Remove prepending attachment to the_content for avoid showing it in other element that use the_content (@since 1.5.5)
			if ( is_attachment() ) {
				remove_filter( 'the_content', 'prepend_attachment' );
			}

			$output = ob_get_clean();

			if ( bricks_is_builder_call() && ! $output ) {
				return $this->render_element_placeholder(
					[
						'title' => esc_html__( 'No WordPress added content found.', 'bricks' ),
					]
				);
			}

			$output .= Helpers::page_break_navigation();

			// Restores the global $post / in_the_loop
			setup_postdata( $current_global_post );
			$wp_query->in_the_loop = $current_in_the_loop;
		}

		// Only render if not empty
		if ( $output ) {
			echo "<div {$this->render_attributes( '_root' )}>$output</div>";
		}
	}
}
