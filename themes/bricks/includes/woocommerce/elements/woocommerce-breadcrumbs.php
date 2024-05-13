<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Woocommerce_Breadcrumbs extends Element {
	public $category = 'woocommerce';
	public $name     = 'woocommerce-breadcrumbs';
	public $icon     = 'ti-line-dashed';

	public function get_label() {
		return esc_html__( 'Breadcrumbs', 'bricks' ) . ' (WooCommerce)';
	}

	public function set_control_groups() {
		$this->control_groups['separator'] = [
			'title' => esc_html__( 'Separator', 'bricks' ),
			'tab'   => 'content',
		];
	}

	public function set_controls() {
		$this->controls['beforeLabel'] = [
			'tab'    => 'content',
			'label'  => esc_html__( 'Before', 'bricks' ),
			'type'   => 'text',
			'inline' => true,
		];

		$this->controls['homeLabel'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Label', 'bricks' ) . ': ' . esc_html__( 'Home', 'bricks' ),
			'type'        => 'text',
			'inline'      => true,
			'placeholder' => esc_html__( 'Home', 'bricks' ),
		];

		// SEPARATOR

		$this->controls['separatorType'] = [
			'tab'         => 'content',
			'group'       => 'separator',
			'label'       => esc_html__( 'Type', 'bricks' ),
			'type'        => 'select',
			'options'     => [
				'text' => esc_html__( 'Text', 'bricks' ),
				'icon' => esc_html__( 'Icon', 'bricks' ),
			],
			'inline'      => true,
			'placeholder' => esc_html__( 'Text', 'bricks' ),
		];

		$this->controls['separatorText'] = [
			'tab'      => 'content',
			'group'    => 'separator',
			'label'    => esc_html__( 'Separator', 'bricks' ),
			'type'     => 'text',
			'inline'   => true,
			'default'  => '/',
			'required' => [ 'separatorType', '!=', 'icon' ],
		];

		$this->controls['separatorIcon'] = [
			'tab'      => 'content',
			'group'    => 'separator',
			'label'    => esc_html__( 'Icon', 'bricks' ),
			'type'     => 'icon',
			'rerender' => true,
			'required' => [ 'separatorType', '=', 'icon' ],
		];

		$this->controls['separatorIconTypography'] = [
			'tab'      => 'content',
			'group'    => 'separator',
			'label'    => esc_html__( 'Icon typography', 'bricks' ),
			'type'     => 'typography',
			'css'      => [
				[
					'property' => 'font',
					'selector' => 'i',
				],
			],
			'exclude'  => [
				'font-family',
				'font-weight',
				'font-style',
				'text-align',
				'text-decoration',
				'text-transform',
				'line-height',
				'letter-spacing',
			],
			'required' => [ 'separatorIcon.icon', '!=', '' ],
		];

		$this->controls['prefix'] = [
			'tab'    => 'content',
			'group'  => 'separator',
			'label'  => esc_html__( 'Prefix', 'bricks' ),
			'type'   => 'text',
			'inline' => true,
		];

		$this->controls['suffix'] = [
			'tab'    => 'content',
			'group'  => 'separator',
			'label'  => esc_html__( 'Suffix', 'bricks' ),
			'type'   => 'text',
			'inline' => true,
		];

		$this->controls['separatorMargin'] = [
			'tab'         => 'content',
			'group'       => 'separator',
			'label'       => esc_html__( 'Margin', 'bricks' ),
			'type'        => 'spacing',
			'css'         => [
				[
					'property' => 'margin',
					'selector' => '.separator',
				],
			],
			'placeholder' => [
				'top'    => 0,
				'right'  => 10,
				'bottom' => 0,
				'left'   => 10,
			],
		];
	}

	public function render() {
		// TODO: Render to "Home" only whenever you change a breadcrumb setting in the builder
		$settings = $this->settings;

		$separator_type = ! empty( $settings['separatorType'] ) ? $settings['separatorType'] : 'text';

		if ( $separator_type === 'icon' && ! empty( $settings['separatorIcon'] ) ) {
			$separator = self::render_icon( $settings['separatorIcon'], [ 'separator' ] );
		} elseif ( ! empty( $settings['separatorText'] ) ) {
			$separator = '<span class="separator">' . esc_html( $settings['separatorText'] ) . '</span>';
		} else {
			$separator = '<span class="separator"></span>';
		}

		$before = ! empty( $settings['beforeLabel'] ) ? '<span class="before">' . $settings['beforeLabel'] . '</span>' : '';

		$args = [
			'delimiter'   => $separator,
			'wrap_before' => '<nav>' . $before . '<span class="navigation">',
			'wrap_after'  => '</span></nav>',
			'before'      => ! empty( $settings['prefix'] ) ? $settings['prefix'] : '',
			'after'       => ! empty( $settings['suffix'] ) ? $settings['suffix'] : '',
			'home'        => ! empty( $settings['homeLabel'] ) ? $settings['homeLabel'] : esc_html__( 'Home', 'bricks' ),
		];

		echo "<div {$this->render_attributes( '_root' )}>";

		woocommerce_breadcrumb( $args );

		echo '</div>';
	}
}
