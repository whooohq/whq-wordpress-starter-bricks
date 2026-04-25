<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Pie_Chart extends Element {
	public $category = 'general';
	public $name     = 'pie-chart';
	public $icon     = 'ti-pie-chart';
	public $scripts  = [ 'bricksPieChart' ];

	public function get_label() {
		return esc_html__( 'Pie Chart', 'bricks' );
	}

	public function enqueue_scripts() {
		wp_enqueue_script( 'bricks-piechart' );
	}

	public function set_controls() {
		$this->controls['percent'] = [
			'tab'       => 'content',
			'label'     => esc_html__( 'Percentage', 'bricks' ),
			'type'      => 'number',
			'default'   => 60,
			'min'       => 0,
			'max'       => 100,
			'clearable' => false,
			'rerender'  => true,
		];

		$this->controls['size'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Chart size in px', 'bricks' ),
			'type'        => 'number',
			'unit'        => 'px',
			'inline'      => true,
			'css'         => [
				[
					'property' => 'height',
				],
			],
			'placeholder' => 160,
			'rerender'    => true,
		];

		$this->controls['lineWidth'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Line width in px', 'bricks' ),
			'type'        => 'number',
			'placeholder' => 8,
			'rerender'    => true,
		];

		$this->controls['lineCap'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Line cap', 'bricks' ),
			'type'        => 'select',
			'options'     => [
				'butt'   => esc_html__( 'Butt', 'bricks' ),
				'round'  => esc_html__( 'Round', 'bricks' ),
				'square' => esc_html__( 'Square', 'bricks' ),
			],
			'placeholder' => esc_html__( 'Square', 'bricks' ),
			'inline'      => true,
			'rerender'    => true,
		];

		$this->controls['content'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Content', 'bricks' ),
			'type'        => 'select',
			'options'     => [
				'percent' => esc_html__( 'Percent', 'bricks' ),
				'icon'    => esc_html__( 'Icon', 'bricks' ),
				'text'    => esc_html__( 'Text', 'bricks' ),
			],
			'placeholder' => esc_html__( 'None', 'bricks' ),
			'default'     => 'percent',
			'inline'      => true,
			'rerender'    => true,
		];

		$this->controls['icon'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Icon', 'bricks' ),
			'type'     => 'icon',
			'required' => [ 'content', '=', 'icon' ],
			'rerender' => true,
		];

		$this->controls['text'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Text', 'bricks' ),
			'type'     => 'text',
			'inline'   => true,
			'rerender' => true,
			'required' => [ 'content', '=', 'text' ],
		];

		$this->controls['barColor'] = [
			'tab'     => 'content',
			'label'   => esc_html__( 'Bar color', 'bricks' ),
			'type'    => 'color',
			'default' => [
				'hex' => Setup::get_default_color( 'primary' ),
			],
		];

		$this->controls['trackColor'] = [
			'tab'     => 'content',
			'label'   => esc_html__( 'Track color', 'bricks' ),
			'type'    => 'color',
			'default' => [
				'hex' => Setup::get_default_color( 'background-light' ),
			],
		];

		$this->controls['scaleLength'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Scale length in px', 'bricks' ),
			'type'     => 'number',
			'rerender' => true,
		];

		$this->controls['scaleColor'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Scale color', 'bricks' ),
			'type'     => 'color',
			'required' => [ 'scaleLength', '!=', '' ],
			'rerender' => true,
		];
	}

	public function render() {
		$settings = $this->settings;

		$this->set_attribute( '_root', 'data-percent', $settings['percent'] );

		if ( ! empty( $settings['scaleColor']['rgb'] ) ) {
			$scale_color = $settings['scaleColor']['rgb'];
		} elseif ( ! empty( $settings['scaleColor']['hex'] ) ) {
			$scale_color = $settings['scaleColor']['hex'];
		} else {
			$scale_color = '';
		}

		if ( $scale_color ) {
			$this->set_attribute( '_root', 'data-scale-color', $scale_color );
		}

		$color_palettes = Database::$global_data['colorPalette'];

		$bar_color   = ! empty( $settings['barColor'] ) ? Assets::generate_css_color( $settings['barColor'] ) : 'transparent';
		$track_color = ! empty( $settings['trackColor'] ) ? Assets::generate_css_color( $settings['trackColor'] ) : 'transparent';

		// Is global color: Get HEX/RGB/RAW value from color palette
		$bar_color_id   = ! empty( $settings['barColor']['id'] ) ? $settings['barColor']['id'] : false;
		$track_color_id = ! empty( $settings['trackColor']['id'] ) ? $settings['trackColor']['id'] : false;

		if ( $bar_color_id || $track_color_id ) {
			foreach ( $color_palettes as $index => $palette ) {
				$colors = ! empty( $palette['colors'] ) ? $palette['colors'] : [];

				foreach ( $colors as $color ) {
					if ( $bar_color_id === $color['id'] ) {
						if ( ! empty( $color['rgb'] ) ) {
							$bar_color = $color['rgb'];
						} elseif ( ! empty( $color['hex'] ) ) {
							$bar_color = $color['hex'];
						} elseif ( ! empty( $color['raw'] ) ) {
							$bar_color = $color['raw'];
						}
					}

					if ( $track_color_id === $color['id'] ) {
						if ( ! empty( $color['rgb'] ) ) {
							$track_color = $color['rgb'];
						} elseif ( ! empty( $color['hex'] ) ) {
							$track_color = $color['hex'];
						} elseif ( ! empty( $color['raw'] ) ) {
							$track_color = $color['raw'];
						}
					}
				}
			}
		}

		$this->set_attribute( '_root', 'data-bar-color', $bar_color );
		$this->set_attribute( '_root', 'data-track-color', $track_color );

		$size = ! empty( $settings['size'] ) ? intval( $settings['size'] ) : 160;
		$this->set_attribute( '_root', 'data-size', $size );

		$line_width = ! empty( $settings['lineWidth'] ) ? intval( $settings['lineWidth'] ) : 8;
		$this->set_attribute( '_root', 'data-line-width', $line_width );

		$line_cap = ! empty( $settings['lineCap'] ) ? $settings['lineCap'] : 'square';
		$this->set_attribute( '_root', 'data-line-cap', $line_cap );

		$this->set_attribute( '_root', 'data-scale-length', isset( $settings['scaleLength'] ) ? intval( $settings['scaleLength'] ) : 0 );

		// Render
		echo "<div {$this->render_attributes( '_root' )}>";

		$content = ! empty( $this->settings['content'] ) ? $this->settings['content'] : false;

		if ( $content ) {
			echo '<span class="content">';

			switch ( $content ) {
				case 'percent':
					echo "{$this->settings['percent']}%";
					break;

				case 'icon':
					echo isset( $settings['icon'] ) ? self::render_icon( $settings['icon'] ) : '';
					break;

				case 'text':
					echo "<span>{$this->settings['text']}</span>";
					break;
			}

			echo '</span>';
		}

		echo '</div>';
	}
}
