<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Sidebar extends Element {
	public $category = 'wordpress';
	public $name     = 'sidebar';
	public $icon     = 'ti-layout-sidebar-right';

	public function get_label() {
		return esc_html__( 'Sidebar', 'bricks' );
	}

	/**
	 * Load required WP styles on the frontend
	 *
	 * @since 1.8
	 */
	public function enqueue_scripts() {
		wp_enqueue_style( 'wp-block-library' );
		wp_enqueue_style( 'global-styles' );
	}

	public function set_controls() {
		// NOTE: wp_registered_sidebars is empty. Use db option 'sidebars_widgets' instead
		$sidebar_options = [];

		if ( bricks_is_builder() ) {
			$sidebars = get_option( 'sidebars_widgets', [] );

			foreach ( array_keys( $sidebars ) as $sidebar_key ) {
				if ( $sidebar_key === 'wp_inactive_widgets' || $sidebar_key === 'array_version' ) {
					continue;
				}

				$sidebar_label = str_replace( [ '-', '_' ], ' ', $sidebar_key );
				$sidebar_label = ucwords( $sidebar_label );

				$sidebar_options[ $sidebar_key ] = $sidebar_label;
			}
		}

		$this->controls['sidebar'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Sidebar', 'bricks' ),
			'type'        => 'select',
			'options'     => $sidebar_options,
			'inline'      => true,
			'placeholder' => esc_html__( 'Select', 'bricks' ),
			'description' => count( $sidebar_options ) ? '' : esc_html__( 'The active theme has no sidebars defined.', 'bricks' ),
		];

		$this->controls['margin'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Widget margin', 'bricks' ),
			'type'        => 'spacing',
			'css'         => [
				[
					'property' => 'margin',
					'selector' => '.bricks-widget-wrapper',
				],
			],
			'placeholder' => [
				'top'    => 0,
				'right'  => 0,
				'bottom' => 40,
				'left'   => 0,
			],
		];

		$this->controls['titleTypography'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Title typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.bricks-widget-title', // No longer working with new GB widgets
				],
				[
					'property' => 'font',
					'selector' => 'h1',
				],
				[
					'property' => 'font',
					'selector' => 'h2',
				],
				[
					'property' => 'font',
					'selector' => 'h3',
				],
				[
					'property' => 'font',
					'selector' => 'h4',
				],
				[
					'property' => 'font',
					'selector' => 'h5',
				],
				[
					'property' => 'font',
					'selector' => 'h6',
				],
			],
		];

		$this->controls['contentTypography'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Content typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property'  => 'font',
					'important' => true, // To precedes default 'line-height: 30px' in _sidebar.scss (#1yyf21b)
				],
			],
		];

		$this->controls['searchBackground'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Search background color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => 'input[type=search]',
				],
			],
		];

		$this->controls['searchBorder'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Search border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => 'input[type=search]',
				],
			],
		];
	}

	public function render() {
		$sidebar = ! empty( $this->settings['sidebar'] ) ? $this->settings['sidebar'] : false;

		if ( ! $sidebar ) {
			return $this->render_element_placeholder(
				[
					'title' => esc_html__( 'No sidebar selected.', 'bricks' ),
				]
			);
		}

		if ( is_active_sidebar( $sidebar ) ) {
			$this->set_attribute( '_root', 'class', sanitize_html_class( $sidebar ) );
			echo "<ul {$this->render_attributes( '_root' )}>";
			dynamic_sidebar( $sidebar );
			echo '</ul>';
		} else {
			return $this->render_element_placeholder(
				[
					'title' => esc_html__( 'Selected sidebar has no active widgets.', 'bricks' ),
				]
			);
		}
	}
}
