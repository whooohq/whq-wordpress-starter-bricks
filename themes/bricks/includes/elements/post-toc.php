<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Post_Table_Of_Contents extends Element {
	public $category     = 'single';
	public $name         = 'post-toc';
	public $icon         = 'ti-list';
	public $css_selector = '.toc-list';
	public $scripts      = [ 'bricksTableOfContents' ];

	public function get_label() {
		return esc_html__( 'Table of contents', 'bricks' );
	}

	public function get_keywords() {
		return [ 'toc' ];
	}

	public function enqueue_scripts() {
		wp_enqueue_script( 'bricks-tocbot' );
	}

	public function set_controls() {
		$this->controls['contentSelector'] = [
			'label'       => esc_html__( 'Content selector', 'bricks' ),
			'type'        => 'text',
			'placeholder' => '.brxe-post-content',
			'description' => esc_html__( 'Fallback', 'bricks' ) . ': #brx-content',
		];

		$this->controls['headingSelectors'] = [
			'label'       => esc_html__( 'Heading selectors', 'bricks' ),
			'type'        => 'text',
			'inline'      => true,
			'placeholder' => 'h2, h3',
		];

		$this->controls['ignoreSelector'] = [
			'label'       => esc_html__( 'Ignore selector', 'bricks' ),
			'type'        => 'text',
			'inline'      => true,
			'placeholder' => '.toc-ignore',
		];

		$this->controls['collapseInactive'] = [
			'label'    => esc_html__( 'Collapse inactive', 'bricks' ),
			'type'     => 'checkbox',
			'rerender' => true,
		];

		$this->controls['noWrap'] = [
			'label' => esc_html__( 'No wrap', 'bricks' ),
			'type'  => 'checkbox',
		];

		$this->controls['sticky'] = [
			'label'    => esc_html__( 'Sticky', 'bricks' ),
			'type'     => 'checkbox',
			'rerender' => true,
		];

		$this->controls['stickyTop'] = [
			'label'    => esc_html__( 'Top', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'top',
					'selector' => '&[data-sticky]',
				],
			],
			'required' => [ 'sticky', '=', true ],
		];

		$this->controls['headingsOffset'] = [
			'label'       => esc_html__( 'Headings offset', 'bricks' ) . ' (px)',
			'type'        => 'number',
			'placeholder' => 0,
		];

		// ITEM

		$this->controls['itemSep'] = [
			'label' => esc_html__( 'Item', 'bricks' ),
			'type'  => 'separator',
		];

		$this->controls['itemPadding'] = [
			'label' => esc_html__( 'Padding', 'bricks' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'padding',
					'selector' => '.toc-list-item',
				],
			],
		];

		$this->controls['itemBorder'] = [
			'label' => esc_html__( 'Border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.toc-link::before',
				],
			],
		];

		$this->controls['itemTypography'] = [
			'label' => esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.toc-link',
				],
			],
		];

		// ACTIVE

		$this->controls['activeSep'] = [
			'label' => esc_html__( 'Active', 'bricks' ),
			'type'  => 'separator',
		];

		$this->controls['itemBorderActive'] = [
			'label' => esc_html__( 'Border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.toc-link.is-active-link::before',
				],
			],
		];

		// NOTE: Requires linkPadding (<a>)
		// $this->controls['itemBackgroundActive'] = [
		// 'label' => esc_html__( 'Background color', 'bricks' ),
		// 'type'  => 'color',
		// 'css'   => [
		// [
		// 'property' => 'background-color',
		// 'selector' => '.toc-link.is-active-link',
		// ],
		// ],
		// ];

		$this->controls['itemTypographyActive'] = [
			'label' => esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.toc-link.is-active-link',
				],
			],
		];
	}

	public function render() {
		$settings = $this->settings;

		if ( ! empty( $settings['sticky'] ) ) {
			$this->set_attribute( '_root', 'data-sticky', 'true' );
		}

		if ( isset( $settings['noWrap'] ) ) {
			$this->set_attribute( '_root', 'data-nowrap', 'true' );
		}

		if ( ! empty( $settings['contentSelector'] ) ) {
			$this->set_attribute( '_root', 'data-content-selector', $settings['contentSelector'] );
		}

		if ( ! empty( $settings['headingSelectors'] ) ) {
			$this->set_attribute( '_root', 'data-heading-selectors', $settings['headingSelectors'] );
		}

		if ( isset( $settings['ignoreSelector'] ) ) {
			$this->set_attribute( '_root', 'data-ignore-selector', $settings['ignoreSelector'] );
		}

		if ( isset( $settings['collapseInactive'] ) ) {
			$this->set_attribute( '_root', 'data-collapse-inactive', 'true' );
		}

		if ( isset( $settings['headingsOffset'] ) ) {
			$this->set_attribute( '_root', 'data-headings-offset', $settings['headingsOffset'] );
		}

		$this->set_attribute( '_root', 'aria-label', esc_html__( 'Table of contents', 'bricks' ) );

		// Smooth scroll enabled via Bricks settings
		if ( Database::get_setting( 'smoothScroll' ) ) {
			$this->set_attribute( '_root', 'data-smooth-scroll', true );
		}

		echo "<nav {$this->render_attributes( '_root' )}></nav>";
	}
}
