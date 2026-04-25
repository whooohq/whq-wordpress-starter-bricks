<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Polylang_Language_Switcher extends \Bricks\Element {
	public $category = 'polylang';
	public $name     = 'polylang-language-switcher';
	public $icon     = 'fas fa-language';

	public function get_label() {
		return esc_html__( 'Language switcher', 'bricks' );
	}

	public function set_controls() {
		$this->controls['direction'] = [
			'label'    => esc_html__( 'Direction', 'bricks' ),
			'type'     => 'direction',
			'inline'   => true,
			'css'      => [
				[
					'property' => 'flex-direction',
					'selector' => '',
				],
			],
			'required' => [ 'dropdown', '=', false ],
		];

		$this->controls['gap'] = [
			'label'    => esc_html__( 'Gap', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'large'    => true,
			'css'      => [
				[
					'property' => 'gap',
					'selector' => '',
				],
			],
			'required' => [ 'dropdown', '=', false ],
		];

		$this->controls['showFlags'] = [
			'label'    => esc_html__( 'Show flags', 'bricks' ),
			'type'     => 'checkbox',
			'required' => [ 'dropdown', '=', false ],
		];

		$this->controls['flagSize'] = [
			'label'    => esc_html__( 'Flag size', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'large'    => true,
			'css'      => [
				[
					'property'  => 'width',
					'selector'  => 'img',
					'important' => true,
				],
				[
					'property' => 'height',
					'selector' => 'img',
					'value'    => 'auto !important',
				],
			],
			'required' => [ 'showFlags', '=', true ],
		];

		$this->controls['showNames'] = [
			'label'    => esc_html__( 'Show names', 'bricks' ),
			'type'     => 'checkbox',
			'required' => [ 'showFlags', '=', true ],
		];

		$this->controls['displayNamesAs'] = [
			'label'       => esc_html__( 'Display names as', 'bricks' ),
			'type'        => 'select',
			'inline'      => true,
			'options'     => [
				'name' => esc_html__( 'Name', 'bricks' ),
				'slug' => esc_html__( 'Slug', 'bricks' ),
			],
			'placeholder' => esc_html__( 'Name', 'bricks' ),
			'required'    => [ 'showNames', '=', true ],
		];

		$this->controls['hideIfEmpty'] = [
			'label' => esc_html__( 'Hide if empty', 'bricks' ),
			'type'  => 'checkbox',
		];

		$this->controls['forceHome'] = [
			'label' => esc_html__( 'Force home', 'bricks' ),
			'type'  => 'checkbox',
		];

		$this->controls['hideIfNoTranslation'] = [
			'label' => esc_html__( 'Hide if no translation', 'bricks' ),
			'type'  => 'checkbox',
		];

		$this->controls['hideCurrent'] = [
			'label'    => esc_html__( 'Hide current', 'bricks' ),
			'type'     => 'checkbox',
			'required' => [ 'dropdown', '=', false ],
		];

		// DROPDOWN

		$this->controls['dropdown'] = [
			'label' => esc_html__( 'Dropdown', 'bricks' ),
			'type'  => 'checkbox',
		];

		$this->controls['dropdownBackground'] = [
			'label'    => esc_html__( 'Dropdown', 'bricks' ) . ': ' . esc_html__( 'Background', 'bricks' ),
			'type'     => 'color',
			'css'      => [
				[
					'property' => 'background-color',
					'selector' => 'select',
				],
			],
			'required' => [ 'dropdown', '=', true ],
		];

		$this->controls['dropdownBorder'] = [
			'label'    => esc_html__( 'Dropdown', 'bricks' ) . ': ' . esc_html__( 'Border', 'bricks' ),
			'type'     => 'border',
			'css'      => [
				[
					'property' => 'border',
					'selector' => 'select',
				],
			],
			'required' => [ 'dropdown', '=', true ],
		];

		$this->controls['dropdownTypography'] = [
			'label'    => esc_html__( 'Dropdown', 'bricks' ) . ': ' . esc_html__( 'Typography', 'bricks' ),
			'type'     => 'typography',
			'css'      => [
				[
					'property' => 'font',
					'selector' => 'select',
				],
			],
			'required' => [ 'dropdown', '=', true ],
		];
	}

	public function render() {
		$settings = $this->settings;

		$args = [
			'dropdown'               => isset( $settings['dropdown'] ) ? $settings['dropdown'] : 0,
			'show_names'             => isset( $settings['showNames'] ) ? $settings['showNames'] : 0,
			'display_names_as'       => isset( $settings['displayNamesAs'] ) ? $settings['displayNamesAs'] : 'name',
			'show_flags'             => isset( $settings['showFlags'] ) ? $settings['showFlags'] : 0,
			'hide_if_empty'          => isset( $settings['hideIfEmpty'] ) ? $settings['hideIfEmpty'] : 1,
			'force_home'             => isset( $settings['forceHome'] ) ? $settings['forceHome'] : 0,
			'hide_if_no_translation' => isset( $settings['hideIfNoTranslation'] ) ? $settings['hideIfNoTranslation'] : 0,
			'hide_current'           => isset( $settings['hideCurrent'] ) ? $settings['hideCurrent'] : 0,
		];

		$tag = isset( $settings['dropdown'] ) ? 'div' : 'ul';

		echo "<$tag {$this->render_attributes( '_root' )}>";

		// Use Polylang function to render the language switcher
		// @see https://polylang.pro/doc/function-reference/
		if ( function_exists( 'pll_the_languages' ) ) {
			pll_the_languages( $args );
		} else {
			return $this->render_element_placeholder(
				[
					'icon-class' => 'ti-alert',
					'title'      => esc_html__( 'No Polylang languages found.', 'bricks' ),
				]
			);
		}

		echo "</$tag>";
	}
}
