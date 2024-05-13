<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Shortcode extends Element {
	public $block    = 'core/shortcode';
	public $category = 'wordpress';
	public $name     = 'shortcode';
	public $icon     = 'ti-shortcode';

	public function get_label() {
		return esc_html__( 'Shortcode', 'bricks' );
	}

	public function set_controls() {
		$this->controls['shortcode'] = [
			'label'       => esc_html__( 'Shortcode', 'bricks' ),
			'type'        => 'textarea',
			'placeholder' => '[gallery ids="72,73,74,75,76,77" columns="3"]',
			'rerender'    => true,
		];

		$this->controls['showPlaceholder'] = [
			'label' => esc_html__( 'Don\'t render in builder', 'bricks' ),
			'type'  => 'checkbox',
		];

		$this->controls['placeholderWidth'] = [
			'label'    => esc_html__( 'Placeholder', 'bricks' ) . ': ' . esc_html__( 'Width', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'required' => [ 'showPlaceholder', '=', true ],
		];

		$this->controls['placeholderHeight'] = [
			'label'    => esc_html__( 'Placeholder', 'bricks' ) . ': ' . esc_html__( 'Height', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'required' => [ 'showPlaceholder', '=', true ],
		];
	}

	public function render() {
		// Don't render shortcode in builder (@since 1.7.2)
		if (
			isset( $this->settings['showPlaceholder'] ) &&
			( bricks_is_builder_call() || bricks_is_builder_iframe() || bricks_is_builder_main() )
		) {
			return $this->render_element_placeholder(
				[
					'title' => esc_html__( 'Shortcode', 'bricks' ) . ': ' . esc_html__( 'Don\'t render in builder', 'bricks' ),
					'style' => [
						'width'  => ! empty( $this->settings['placeholderWidth'] ) ? $this->settings['placeholderWidth'] : '',
						'height' => ! empty( $this->settings['placeholderHeight'] ) ? $this->settings['placeholderHeight'] : '',
					],
				]
			);
		}

		$shortcode = ! empty( $this->settings['shortcode'] ) ? stripcslashes( $this->settings['shortcode'] ) : false;

		if ( ! $shortcode ) {
			return $this->render_element_placeholder( [ 'title' => esc_html__( 'No shortcode provided.', 'bricks' ) ] );
		}

		// Render dynamic data first - shortcode attributes might depend on it
		$shortcode = $this->render_dynamic_data( $shortcode );

		// Check: Is 'popup' template
		$template_id       = 0;
		$is_popup_template = false;

		if ( strpos( $shortcode, BRICKS_DB_TEMPLATE_SLUG ) !== false ) {
			$shortcode_text = str_replace( '[', '', $shortcode );
			$shortcode_text = str_replace( ']', '', $shortcode_text );

			$shortcode_atts = shortcode_parse_atts( $shortcode_text );
			$template_id    = ! empty( $shortcode_atts['id'] ) ? intval( $shortcode_atts['id'] ) : 0;

			if ( $template_id ) {
				$is_popup_template = Templates::get_template_type( $template_id ) === 'popup';
			}
		}

		// Return: Template has not been published (@since 1.7.1)
		if ( $template_id && get_post_type( $template_id ) === BRICKS_DB_TEMPLATE_SLUG && get_post_status( $template_id ) !== 'publish' ) {
			return $this->render_element_placeholder(
				[
					'title' => esc_html__( 'Template has not been published.', 'bricks' ),
				]
			);
		}

		// Get shortcode content
		$shortcode = do_shortcode( $shortcode );

		if ( empty( $shortcode ) ) {
			return $this->render_element_placeholder( [ 'title' => esc_html__( 'Shortcode content is empty', 'bricks' ) ] );
		}

		// Render 'popup' template without element root wrapper (@since 1.6)
		if ( $is_popup_template ) {
			echo $shortcode;
		} else {
			echo "<div {$this->render_attributes( '_root' )}>" . $shortcode . '</div>';
		}
	}

	public function convert_element_settings_to_block( $settings ) {
		$block = [
			'blockName'    => $this->block,
			'attrs'        => [],
			'innerContent' => isset( $settings['shortcode'] ) ? [ $settings['shortcode'] ] : [ '' ],
		];

		return $block;
	}

	public function convert_block_to_element_settings( $block, $attributes ) {
		$element_settings = [
			'shortcode' => isset( $block['innerContent'] ) && count( $block['innerContent'] ) ? $block['innerContent'][0] : '',
		];

		return $element_settings;
	}
}
