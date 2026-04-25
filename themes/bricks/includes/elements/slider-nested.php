<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Slider_Nested extends Element {
	public $category = 'media';
	public $name     = 'slider-nested';
	public $icon     = 'ti-layout-slider';
	public $scripts  = [ 'bricksSplide' ];
	public $nestable = true;

	public function get_label() {
		return esc_html__( 'Slider', 'bricks' ) . ' (' . esc_html__( 'Nestable', 'bricks' ) . ')';
	}

	public function get_keywords() {
		return [ 'slider', 'testimonials', 'carousel', 'nestable' ];
	}

	public function enqueue_scripts() {
		wp_enqueue_script( 'bricks-splide' );
		wp_enqueue_style( 'bricks-splide' );
	}

	public function set_control_groups() {
		$this->control_groups['options'] = [
			'title' => esc_html__( 'Options', 'bricks' ),
		];

		$this->control_groups['slide'] = [
			'title' => esc_html__( 'Slide', 'bricks' ),
		];

		$this->control_groups['arrows'] = [
			'title' => esc_html__( 'Arrows', 'bricks' ),
		];

		$this->control_groups['pagination'] = [
			'title' => esc_html__( 'Pagination', 'bricks' ),
		];
	}

	public function set_controls() {
		$this->controls['_height']['css'][0]['selector'] = '.splide__slide';
		$this->controls['_height']['css'][0]['selector'] = '.splide__slide';
		$this->controls['_height']['css'][0]['selector'] = '.splide__slide';

		$this->controls['_background']['default'] = [
			'color' => [
				'hex' => '#e6e7e8',
			],
		];

		// Slides: Array of nestable element.children (@since 1.5)
		$this->controls['_children'] = [
			'type'          => 'repeater',
			'titleProperty' => 'label',
			'items'         => 'children', // NOTE: Undocumented
		];

		// OPTIONS

		// Use custom 'options' instead of settings below
		$this->controls['optionsType'] = [
			'group'       => 'options',
			'label'       => esc_html__( 'Options type', 'bricks' ),
			'type'        => 'select',
			'options'     => [
				'default' => esc_html__( 'Default', 'bricks' ),
				'custom'  => esc_html__( 'Custom', 'bricks' ),
			],
			'inline'      => true,
			'placeholder' => esc_html__( 'Default', 'bricks' ),
			'fullAccess'  => true,
		];

		$this->controls['options'] = [
			'group'       => 'options',
			'label'       => esc_html__( 'Custom options', 'bricks' ),
			'type'        => 'code',
			'required'    => [ 'optionsType', '=', 'custom' ],
			'description' => esc_html__( 'Provide your own options in JSON format', 'bricks' ) . ' (<a href="https://splidejs.com/guides/options" target="_blank" rel="noopener">' . esc_html__( 'learn more', 'bricks' ) . '</a>).',
			'fullAccess'  => true,
		];

		// Fixed settings:

		$this->controls['type'] = [
			'group'       => 'options',
			'label'       => esc_html__( 'Type', 'bricks' ),
			'type'        => 'select',
			'options'     => [
				'loop'  => esc_html__( 'Loop', 'bricks' ),
				'slide' => esc_html__( 'Slide', 'bricks' ),
				'fade'  => esc_html__( 'Fade', 'bricks' ),
			],
			'inline'      => true,
			'placeholder' => esc_html__( 'Loop', 'bricks' ),
			'required'    => [ 'optionsType', '!=', 'custom' ],
			'fullAccess'  => true,
		];

		$this->controls['direction'] = [
			'group'       => 'options',
			'label'       => esc_html__( 'Direction', 'bricks' ),
			'type'        => 'select',
			'options'     => [
				'ltr' => esc_html__( 'Left to right', 'bricks' ),
				'rtl' => esc_html__( 'Right to left', 'bricks' ),
				'ttb' => esc_html__( 'Vertical', 'bricks' ),
			],
			'inline'      => true,
			'placeholder' => esc_html__( 'Left to right', 'bricks' ),
			'breakpoints' => true,
			'required'    => [ 'optionsType', '!=', 'custom' ],
			'fullAccess'  => true,
		];

		$this->controls['keyboard'] = [
			'group'       => 'options',
			'label'       => esc_html__( 'Keyboard', 'bricks' ),
			'type'        => 'select',
			'options'     => [
				'false'   => esc_html__( 'Off', 'bricks' ),
				'focused' => esc_html__( 'Focused', 'bricks' ),
				'global'  => esc_html__( 'Global', 'bricks' ),
			],
			'inline'      => true,
			'placeholder' => esc_html__( 'Global', 'bricks' ),
			'breakpoints' => true,
			'required'    => [ 'optionsType', '!=', 'custom' ],
			'fullAccess'  => true,
		];

		/**
		 * NOTE: Natively not supported for horizontal slides
		 *
		 * Added custom solution to calculate height: https://github.com/Splidejs/splide/issues/227#issuecomment-997330823
		 *
		 * @since 1.9.1
		 */
		$this->controls['autoHeight'] = [
			'group'      => 'options',
			'label'      => esc_html__( 'Auto height', 'bricks' ),
			'type'       => 'checkbox',
			'inline'     => true,
			'required'   => [ 'optionsType', '!=', 'custom' ],
			'fullAccess' => true,
		];

		$this->controls['autoHeightInfo'] = [
			'group'    => 'options',
			'content'  => esc_html__( 'Using "Auto height" might lead to CLS (Cumulative Layout Shift).', 'bricks' ),
			'type'     => 'info',
			'required' => [ 'autoHeight', '=', true ],
		];

		$this->controls['height'] = [
			'group'       => 'options',
			'label'       => esc_html__( 'Height', 'bricks' ),
			'type'        => 'number',
			'units'       => true,
			'placeholder' => '50vh',
			'breakpoints' => true,
			'required'    => [
				[ 'optionsType', '!=', 'custom' ],
				[ 'autoHeight', '=', '' ],
			],
			'fullAccess'  => true,
		];

		$this->controls['gap'] = [
			'group'       => 'options',
			'label'       => esc_html__( 'Spacing', 'bricks' ),
			'type'        => 'number',
			'units'       => true,
			'placeholder' => 0,
			'breakpoints' => true,
			'required'    => [ 'optionsType', '!=', 'custom' ],
			'fullAccess'  => true,
		];

		$this->controls['start'] = [
			'group'       => 'options',
			'label'       => esc_html__( 'Start index', 'bricks' ),
			'type'        => 'number',
			'placeholder' => 0,
			'required'    => [ 'optionsType', '!=', 'custom' ],
			'fullAccess'  => true,
		];

		$this->controls['perPage'] = [
			'group'       => 'options',
			'label'       => esc_html__( 'Items to show', 'bricks' ),
			'type'        => 'number',
			'placeholder' => 1,
			'breakpoints' => true,
			'required'    => [
				[ 'optionsType', '!=', 'custom' ],
				[ 'type', '!=', 'fade' ],
			],
			'fullAccess'  => true,
		];

		$this->controls['perMove'] = [
			'group'       => 'options',
			'label'       => esc_html__( 'Items to scroll', 'bricks' ),
			'type'        => 'number',
			'placeholder' => 1,
			'breakpoints' => true,
			'required'    => [
				[ 'optionsType', '!=', 'custom' ],
				[ 'type', '!=', 'fade' ],
			],
			'fullAccess'  => true,
		];

		$this->controls['speed'] = [
			'group'       => 'options',
			'label'       => esc_html__( 'Speed in ms', 'bricks' ),
			'type'        => 'number',
			'placeholder' => 400,
			'breakpoints' => true,
			'required'    => [ 'optionsType', '!=', 'custom' ],
			'fullAccess'  => true,
		];

		// @since 1.9.2
		$this->controls['focus'] = [
			'group'       => 'options',
			'info'        => esc_html__( 'Number', 'bricks' ),
			'label'       => esc_html__( 'Focus', 'bricks' ),
			'type'        => 'number',
			'breakpoints' => true,
			'desc'        => sprintf(
				'<a href="https://splidejs.com/guides/options/#focus" target="_blank">%s</a>',
				esc_html__( 'Determines which slide should be active if the carousel has multiple slides in a page.', 'bricks' ),
			),
			'required'    => [
				[ 'optionsType', '!=', 'custom' ],
				[ 'perPage', '>=', 2 ],
			],
			'fullAccess'  => true,
		];

		// AUTOPLAY

		$this->controls['autoplaySeparator'] = [
			'group'      => 'options',
			'label'      => esc_html__( 'Autoplay', 'bricks' ),
			'type'       => 'separator',
			'required'   => [ 'optionsType', '!=', 'custom' ],
			'fullAccess' => true,
		];

		$this->controls['autoplay'] = [
			'group'      => 'options',
			'label'      => esc_html__( 'Autoplay', 'bricks' ),
			'type'       => 'checkbox',
			'required'   => [ 'optionsType', '!=', 'custom' ],
			'fullAccess' => true,
		];

		$this->controls['pauseOnHover'] = [
			'group'      => 'options',
			'label'      => esc_html__( 'Pause on hover', 'bricks' ),
			'type'       => 'checkbox',
			'inline'     => true,
			'required'   => [
				[ 'optionsType', '!=', 'custom' ],
				[ 'autoplay', '!=', '' ],
			],
			'fullAccess' => true,
		];

		$this->controls['pauseOnFocus'] = [
			'group'      => 'options',
			'label'      => esc_html__( 'Pause on focus', 'bricks' ),
			'type'       => 'checkbox',
			'inline'     => true,
			'required'   => [
				[ 'optionsType', '!=', 'custom' ],
				[ 'autoplay', '!=', '' ],
			],
			'fullAccess' => true,
		];

		$this->controls['interval'] = [
			'group'       => 'options',
			'label'       => esc_html__( 'Interval in ms', 'bricks' ),
			'type'        => 'number',
			'placeholder' => 3000,
			'required'    => [
				[ 'optionsType', '!=', 'custom' ],
				[ 'autoplay', '!=', '' ],
			],
			'fullAccess'  => true,
		];

		// REWIND: If 'type' != 'loop'

		$this->controls['rewindSeparator'] = [
			'group'      => 'options',
			'label'      => esc_html__( 'Rewind', 'bricks' ),
			'type'       => 'separator',
			'required'   => [
				[ 'optionsType', '!=', 'custom' ],
				[ 'type', '!=', [ '', 'loop' ] ],
			],
			'fullAccess' => true,
		];

		$this->controls['rewind'] = [
			'group'      => 'options',
			'label'      => esc_html__( 'Rewind', 'bricks' ),
			'type'       => 'checkbox',
			'inline'     => true,
			'required'   => [
				[ 'optionsType', '!=', 'custom' ],
				[ 'type', '!=', [ '', 'loop' ] ],
			],
			'fullAccess' => true,
		];

		$this->controls['rewindByDrag'] = [
			'group'      => 'options',
			'label'      => esc_html__( 'Rewind by drag', 'bricks' ),
			'type'       => 'checkbox',
			'inline'     => true,
			'required'   => [
				[ 'optionsType', '!=', 'custom' ],
				[ 'type', '!=', [ '', 'loop' ] ],
				[ 'rewind', '!=', '' ]
			],
			'fullAccess' => true,
		];

		$this->controls['rewindSpeed'] = [
			'group'      => 'options',
			'label'      => esc_html__( 'Speed in ms', 'bricks' ),
			'type'       => 'number',
			'inline'     => true,
			'required'   => [
				[ 'optionsType', '!=', 'custom' ],
				[ 'type', '!=', [ '', 'loop' ] ],
				[ 'rewind', '!=', '' ]
			],
			'fullAccess' => true,
		];

		// SLIDE

		$this->controls['slidePadding'] = [
			'group' => 'slide',
			'label' => esc_html__( 'Padding', 'bricks' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'padding',
					'selector' => '.splide__slide',
				],
			],
		];

		$this->controls['slideAlignHorizontal'] = [
			'group'   => 'slide',
			'label'   => esc_html__( 'Align horizontal', 'bricks' ),
			'type'    => 'align-items',
			'exclude' => 'stretch',
			'inline'  => true,
			'css'     => [
				[
					'property' => 'align-items',
					'selector' => '.splide__slide',
				],
			],
		];

		$this->controls['slideAlignVertical'] = [
			'group'   => 'slide',
			'label'   => esc_html__( 'Align vertical', 'bricks' ),
			'type'    => 'justify-content',
			'exclude' => 'space',
			'inline'  => true,
			'css'     => [
				[
					'property' => 'justify-content',
					'selector' => '.splide__slide',
				],
			],
		];

		$this->controls['slideBackground'] = [
			'group'   => 'slide',
			'label'   => esc_html__( 'Background', 'bricks' ),
			'type'    => 'background',
			'css'     => [
				[
					'property' => 'background',
					'selector' => '.splide__slide',
				],
			],
			'exclude' => 'video',
		];

		$this->controls['slideBorder'] = [
			'group' => 'slide',
			'label' => esc_html__( 'Border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.splide__slide',
				],
			],
		];

		// NOTE: Not in use as slider has overflow: hidden
		// $this->controls['slideBoxShadow'] = [
		// 'group' => 'slide',
		// 'label' => esc_html__( 'Box shadow', 'bricks' ),
		// 'type'  => 'box-shadow',
		// 'css'   => [
		// [
		// 'property' => 'box-shadow',
		// 'selector' => '.splide__slide',
		// ],
		// ],
		// ];

		// Arrows

		$this->controls['arrows'] = [
			'group'      => 'arrows',
			'label'      => esc_html__( 'Show', 'bricks' ),
			'type'       => 'checkbox',
			'inline'     => true,
			'rerender'   => true,
			'fullAccess' => true,
		];

		$this->controls['arrowHeight'] = [
			'group'       => 'arrows',
			'label'       => esc_html__( 'Height', 'bricks' ),
			'type'        => 'number',
			'units'       => true,
			'css'         => [
				[
					'property' => 'height',
					'selector' => '.splide__arrow',
				],
			],
			'placeholder' => 50,
			'required'    => [ 'arrows', '!=', '' ],
		];

		$this->controls['arrowWidth'] = [
			'group'       => 'arrows',
			'label'       => esc_html__( 'Width', 'bricks' ),
			'type'        => 'number',
			'units'       => true,
			'css'         => [
				[
					'property' => 'width',
					'selector' => '.splide__arrow',
				],
			],
			'placeholder' => 50,
			'required'    => [ 'arrows', '!=', '' ],
		];

		$this->controls['arrowBackground'] = [
			'group'    => 'arrows',
			'label'    => esc_html__( 'Background', 'bricks' ),
			'type'     => 'color',
			'css'      => [
				[
					'property' => 'background-color',
					'selector' => '.splide__arrow',
				],
			],
			'required' => [ 'arrows', '!=', '' ],
		];

		$this->controls['arrowBorder'] = [
			'group'    => 'arrows',
			'label'    => esc_html__( 'Border', 'bricks' ),
			'type'     => 'border',
			'css'      => [
				[
					'property' => 'border',
					'selector' => '.splide__arrow',
				],
			],
			'required' => [ 'arrows', '!=', '' ],
		];

		$this->controls['arrowColor'] = [
			'group'    => 'arrows',
			'label'    => esc_html__( 'Color', 'bricks' ),
			'type'     => 'color',
			'css'      => [
				[
					'property' => 'color',
					'selector' => '.splide__arrow',
				],
				[
					'property' => 'fill',
					'selector' => '.splide__arrow svg',
				],
			],
			'required' => [ 'arrows', '!=', '' ],
		];

		$this->controls['arrowSize'] = [
			'group'    => 'arrows',
			'label'    => esc_html__( 'Size', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'font-size',
					'selector' => '.splide__arrow',
				],
				[
					'property' => 'height',
					'selector' => '.splide__arrow svg',
				],
				[
					'property' => 'width',
					'selector' => '.splide__arrow svg',
				],
				[
					'property' => 'min-height',
					'selector' => '.splide__arrow',
				],
				[
					'property' => 'min-width',
					'selector' => '.splide__arrow',
				],
			],
			'required' => [ 'arrows', '!=', '' ],
		];

		// text-shadow (@since 1.8.5)
		$this->controls['arrowTextShadow'] = [
			'group'    => 'arrows',
			'label'    => esc_html__( 'Text shadow', 'bricks' ),
			'type'     => 'text-shadow',
			'css'      => [
				[
					'property' => 'text-shadow',
					'selector' => '.splide__arrow',
				],
			],
			'required' => [ 'arrows', '!=', '' ],
		];

		// Custom arrows typography
		$this->controls['arrowTypography'] = [
			'deprecated' => true, // @since 1.8.5 (use 'arrowTextShadow' setting above)
			'group'      => 'arrows',
			'label'      => esc_html__( 'Typography', 'bricks' ),
			'type'       => 'typography',
			'css'        => [
				[
					'property' => 'font',
					'selector' => '.splide__arrow',
				],
			],
			'exclude'    => [
				'font-family',
				'font-weight',
				'font-style',
				'text-align',
				'letter-spacing',
				'line-height',
				'text-decoration',
				'text-transform',
			],
			'required'   => [
				[ 'arrows', '!=', '' ],
				[ 'prevArrow.icon', '!=', '' ],
				[ 'nextArrow.icon', '!=', '' ],
			],
		];

		$this->controls['disabledArrowSep'] = [
			'group'    => 'arrows',
			'label'    => esc_html__( 'Disabled', 'bricks' ),
			'type'     => 'separator',
			'required' => [ 'arrows', '!=', '' ],
		];

		$this->controls['arrowDisabledBackground'] = [
			'group'    => 'arrows',
			'label'    => esc_html__( 'Background', 'bricks' ),
			'type'     => 'color',
			'css'      => [
				[
					'property' => 'background-color',
					'selector' => '.splide__arrow:disabled',
				],
			],
			'required' => [ 'arrows', '!=', '' ],
		];

		$this->controls['arrowDisabledBorder'] = [
			'group'    => 'arrows',
			'label'    => esc_html__( 'Border', 'bricks' ),
			'type'     => 'border',
			'css'      => [
				[
					'property' => 'border',
					'selector' => '.splide__arrow:disabled',
				],
			],
			'required' => [ 'arrows', '!=', '' ],

		];

		$this->controls['arrowDisabledColor'] = [
			'group'    => 'arrows',
			'label'    => esc_html__( 'Color', 'bricks' ),
			'type'     => 'color',
			'css'      => [
				[
					'property' => 'color',
					'selector' => '.splide__arrow:disabled',
				],
				[
					'property' => 'fill',
					'selector' => '.splide__arrow:disabled svg',
				],
			],
			'required' => [ 'arrows', '!=', '' ],
		];

		$this->controls['arrowDisabledOpacity'] = [
			'group'    => 'arrows',
			'label'    => esc_html__( 'Opacity', 'bricks' ),
			'type'     => 'number',
			'inline'   => true,
			'min'      => 0,
			'max'      => 1,
			'step'     => 0.1,
			'css'      => [
				[
					'property' => 'opacity',
					'selector' => '.splide__arrow:disabled',
				],
			],
			'required' => [ 'arrows', '!=', '' ],
		];

		// PREV ARROW

		$this->controls['prevArrowSeparator'] = [
			'group'      => 'arrows',
			'label'      => esc_html__( 'Prev arrow', 'bricks' ),
			'type'       => 'separator',
			'required'   => [ 'arrows', '!=', '' ],
			'fullAccess' => true,
		];

		$this->controls['prevArrow'] = [
			'group'    => 'arrows',
			'label'    => esc_html__( 'Prev arrow', 'bricks' ),
			'type'     => 'icon',
			'rerender' => true,
			'css'      => [
				[
					'selector' => '.splide__arrow--prev > *',
				],
			],
			'required' => [ 'arrows', '!=', '' ],
		];

		$this->controls['prevArrowTop'] = [
			'group'    => 'arrows',
			'label'    => esc_html__( 'Top', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'top',
					'selector' => '.splide__arrow--prev',
				],
			],
			'required' => [ 'arrows', '!=', '' ],
		];

		$this->controls['prevArrowRight'] = [
			'group'    => 'arrows',
			'label'    => esc_html__( 'Right', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'right',
					'selector' => '.splide__arrow--prev',
				],
			],
			'required' => [ 'arrows', '!=', '' ],
		];

		$this->controls['prevArrowBottom'] = [
			'group'    => 'arrows',
			'label'    => esc_html__( 'Bottom', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'bottom',
					'selector' => '.splide__arrow--prev',
				],
			],
			'required' => [ 'arrows', '!=', '' ],
		];

		$this->controls['prevArrowLeft'] = [
			'group'    => 'arrows',
			'label'    => esc_html__( 'Left', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'left',
					'selector' => '.splide__arrow--prev',
				],
			],
			'required' => [ 'arrows', '!=', '' ],
		];

		$this->controls['prevArrowTransform'] = [
			'group'    => 'arrows',
			'label'    => esc_html__( 'Transform', 'bricks' ),
			'type'     => 'transform',
			'css'      => [
				[
					'property' => 'transform',
					'selector' => '.splide__arrow--prev',
				],
			],
			'required' => [ 'arrows', '!=', '' ],
		];

		$this->controls['prevArrowTransformInfo'] = [
			'group'    => 'arrows',
			'content'  => esc_html__( 'Please make sure to set the "Scale X" value inside the transform setting above to "-1".', 'bricks' ),
			'type'     => 'info',
			'required' => [
				[ 'arrows', '!=', '' ],
				[ 'prevArrow', '=', '' ],
				[ 'prevArrowTransform', '!=', '' ],
			],
		];

		// NEXT ARROW

		$this->controls['nextArrowSeparator'] = [
			'group'      => 'arrows',
			'label'      => esc_html__( 'Next arrow', 'bricks' ),
			'type'       => 'separator',
			'required'   => [ 'arrows', '!=', '' ],
			'fullAccess' => true,
		];

		$this->controls['nextArrow'] = [
			'group'    => 'arrows',
			'label'    => esc_html__( 'Next arrow', 'bricks' ),
			'type'     => 'icon',
			'rerender' => true,
			'css'      => [
				[
					'selector' => '.splide__arrow--next > *',
				],
			],
			'required' => [ 'arrows', '!=', '' ],
		];

		$this->controls['nextArrowTop'] = [
			'group'    => 'arrows',
			'label'    => esc_html__( 'Top', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'top',
					'selector' => '.splide__arrow--next',
				],
			],
			'required' => [ 'arrows', '!=', '' ],
		];

		$this->controls['nextArrowRight'] = [
			'group'    => 'arrows',
			'label'    => esc_html__( 'Right', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'right',
					'selector' => '.splide__arrow--next',
				],
			],
			'required' => [ 'arrows', '!=', '' ],
		];

		$this->controls['nextArrowBottom'] = [
			'group'    => 'arrows',
			'label'    => esc_html__( 'Bottom', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'bottom',
					'selector' => '.splide__arrow--next',
				],
			],
			'required' => [ 'arrows', '!=', '' ],
		];

		$this->controls['nextArrowLeft'] = [
			'group'    => 'arrows',
			'label'    => esc_html__( 'Left', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'left',
					'selector' => '.splide__arrow--next',
				],
			],
			'required' => [ 'arrows', '!=', '' ],
		];

		$this->controls['nextArrowTransform'] = [
			'group'    => 'arrows',
			'label'    => esc_html__( 'Transform', 'bricks' ),
			'type'     => 'transform',
			'css'      => [
				[
					'property' => 'transform',
					'selector' => '.splide__arrow--next',
				],
			],
			'required' => [ 'arrows', '!=', '' ],
		];

		// Pagination (dots)

		$this->controls['pagination'] = [
			'group'      => 'pagination',
			'label'      => esc_html__( 'Show', 'bricks' ),
			'type'       => 'checkbox',
			'inline'     => true,
			'rerender'   => true,
			'default'    => true,
			'fullAccess' => true,
		];

		$this->controls['paginationSpacing'] = [
			'group'       => 'pagination',
			'label'       => esc_html__( 'Margin', 'bricks' ),
			'type'        => 'spacing',
			'css'         => [
				[
					'property' => 'margin',
					'selector' => '.splide__pagination .splide__pagination__page',
				],
			],
			'placeholder' => [
				'top'    => '5px',
				'right'  => '5px',
				'bottom' => '5px',
				'left'   => '5px',
			],
			'required'    => [ 'pagination', '!=', '' ],
		];

		$this->controls['paginationHeight'] = [
			'group'       => 'pagination',
			'label'       => esc_html__( 'Height', 'bricks' ),
			'type'        => 'number',
			'units'       => true,
			'units'       => [
				'px' => [
					'min' => 1,
					'max' => 100,
				],
			],
			'css'         => [
				[
					'property' => 'height',
					'selector' => '.splide__pagination .splide__pagination__page',
				],
			],
			'placeholder' => '10px',
			'required'    => [ 'pagination', '!=', '' ],
		];

		$this->controls['paginationWidth'] = [
			'group'       => 'pagination',
			'label'       => esc_html__( 'Width', 'bricks' ),
			'type'        => 'number',
			'units'       => true,
			'units'       => [
				'px' => [
					'min' => 1,
					'max' => 100,
				],
			],
			'css'         => [
				[
					'property' => 'width',
					'selector' => '.splide__pagination .splide__pagination__page',
				],
			],
			'placeholder' => '10px',
			'required'    => [ 'pagination', '!=', '' ],
		];

		$this->controls['paginationColor'] = [
			'group'    => 'pagination',
			'label'    => esc_html__( 'Color', 'bricks' ),
			'type'     => 'color',
			'css'      => [
				[
					'property' => 'color',
					'selector' => '.splide__pagination .splide__pagination__page',
				],
				[
					'property' => 'background-color',
					'selector' => '.splide__pagination .splide__pagination__page',
				],
			],
			'required' => [ 'pagination', '!=', '' ],
		];

		$this->controls['paginationBorder'] = [
			'group'    => 'pagination',
			'label'    => esc_html__( 'Border', 'bricks' ),
			'type'     => 'border',
			'css'      => [
				[
					'property' => 'border',
					'selector' => '.splide__pagination .splide__pagination__page',
				],
			],
			'required' => [ 'pagination', '!=', '' ],
		];

		// ACTIVE

		$this->controls['paginationActiveSeparator'] = [
			'group'      => 'pagination',
			'label'      => esc_html__( 'Active', 'bricks' ),
			'type'       => 'separator',
			'required'   => [ 'pagination', '!=', '' ],
			'fullAccess' => true,
		];

		$this->controls['paginationHeightActive'] = [
			'group'    => 'pagination',
			'label'    => esc_html__( 'Height', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'height',
					'selector' => '.splide__pagination .splide__pagination__page.is-active',
				],
			],
			'required' => [ 'pagination', '!=', '' ],
		];

		$this->controls['paginationWidthActive'] = [
			'group'    => 'pagination',
			'label'    => esc_html__( 'Width', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'width',
					'selector' => '.splide__pagination .splide__pagination__page.is-active',
				],
			],
			'required' => [ 'pagination', '!=', '' ],
		];

		$this->controls['paginationColorActive'] = [
			'group'    => 'pagination',
			'label'    => esc_html__( 'Color', 'bricks' ),
			'type'     => 'color',
			'css'      => [
				[
					'property' => 'color',
					'selector' => '.splide__pagination .splide__pagination__page.is-active',
				],
				[
					'property' => 'background-color',
					'selector' => '.splide__pagination .splide__pagination__page.is-active',
				],
			],
			'required' => [ 'pagination', '!=', '' ],
		];

		$this->controls['paginationBorderActive'] = [
			'group'    => 'pagination',
			'label'    => esc_html__( 'Border', 'bricks' ),
			'type'     => 'border',
			'css'      => [
				[
					'property' => 'border',
					'selector' => '.splide__pagination .splide__pagination__page.is-active',
				],
			],
			'required' => [ 'pagination', '!=', '' ],
		];

		// POSITION

		$this->controls['paginationPositionSeparator'] = [
			'group'      => 'pagination',
			'label'      => esc_html__( 'Position', 'bricks' ),
			'type'       => 'separator',
			'required'   => [ 'pagination', '!=', '' ],
			'fullAccess' => true,
		];

		$this->controls['paginationTop'] = [
			'group'    => 'pagination',
			'label'    => esc_html__( 'Top', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'top',
					'selector' => '.splide__pagination',
				],
				[
					'property' => 'bottom',
					'value'    => 'auto',
					'selector' => '.splide__pagination',
				],
			],
			'required' => [ 'pagination', '!=', '' ],
		];

		$this->controls['paginationRight'] = [
			'group'    => 'pagination',
			'label'    => esc_html__( 'Right', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'right',
					'selector' => '.splide__pagination',
				],
				[
					'property' => 'left',
					'value'    => 'auto',
					'selector' => '.splide__pagination',
				],
				[
					'property' => 'transform',
					'selector' => '.splide__pagination',
					'value'    => 'translateX(0)',
				],
			],
			'required' => [ 'pagination', '!=', '' ],
		];

		$this->controls['paginationBottom'] = [
			'group'    => 'pagination',
			'label'    => esc_html__( 'Bottom', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'bottom',
					'selector' => '.splide__pagination',
				],
			],
			'required' => [ 'pagination', '!=', '' ],
		];

		$this->controls['paginationLeft'] = [
			'group'    => 'pagination',
			'label'    => esc_html__( 'Left', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'left',
					'selector' => '.splide__pagination',
				],
				[
					'property' => 'right',
					'value'    => 'auto',
					'selector' => '.splide__pagination',
				],
				[
					'property' => 'transform',
					'selector' => '.splide__pagination',
					'value'    => 'translateX(0)',
				],
			],
			'required' => [ 'pagination', '!=', '' ],
		];
	}

	public function get_nestable_item() {
		return [
			'name'     => 'block',
			'label'    => esc_html__( 'Slide', 'bricks' ) . ' {item_index}',
			'children' => [
				[
					'name'     => 'heading',
					'settings' => [
						'text' => esc_html__( 'Slide', 'bricks' ) . ' {item_index}',
					],
				],
				[
					'name'     => 'button',
					'settings' => [
						'text'  => esc_html__( 'I am a button', 'bricks' ),
						'size'  => 'lg',
						'style' => 'primary',
					],
				],
			],
		];
	}

	public function get_nestable_children() {
		$children = [];

		for ( $i = 0; $i < 3; $i++ ) {
			$item = $this->get_nestable_item();

			// Replace {item_index} with $index
			$item       = wp_json_encode( $item );
			$item       = str_replace( '{item_index}', $i + 1, $item );
			$item       = json_decode( $item, true );
			$children[] = $item;
		}

		return $children;
	}

	/**
	 * Render arrows (use custom HTML solution as splideJS only accepts SVG path via 'arrowPath')
	 */
	public function render_arrows() {
		$prev_arrow = ! empty( $this->settings['prevArrow'] ) ? self::render_icon( $this->settings['prevArrow'] ) : false;
		$next_arrow = ! empty( $this->settings['nextArrow'] ) ? self::render_icon( $this->settings['nextArrow'] ) : false;

		if ( ! $prev_arrow && ! $next_arrow ) {
			return;
		}

		$output = '<div class="splide__arrows custom">';

		if ( $prev_arrow ) {
			$output .= '<button class="splide__arrow splide__arrow--prev" type="button">' . $prev_arrow . '</button>';
		}

		if ( $next_arrow ) {
			$output .= '<button class="splide__arrow splide__arrow--next" type="button">' . $next_arrow . '</button>';
		}

		$output .= '</div>';

		return $output;
	}

	/**
	 * Render individual slides
	 */
	public function render() {
		$settings = $this->settings;

		$splide_class = [ 'splide' ];

		// To allpy required CSS when using autoHeight (@since 1.9.1)
		if ( ! empty( $settings['autoHeight'] ) ) {
			$splide_class[] = 'brx-auto-height';
		}

		$this->set_attribute( '_root', 'class', $splide_class );

		/**
		 * Library: splideJS (replaces swiperJS)
		 *
		 * - Lighter (30KB instead of 139KB)
		 * - Less issues
		 * - More flexible breakpoints (via mediaQuery)
		 * - 'gap' in any unit
		 * - vertical slider via 'direction: ttb' (top to bottom)
		 *
		 * NOTE: Arrows only support SVG files by default (no icon fonts, etc.)
		 *
		 * https://splidejs.com/guides/options
		 *
		 * splideJS @since 1.5
		 *
		 * TODO 'mediaQuery' for custom breakpoints
		 */
		$type = ! empty( $settings['type'] ) ? $settings['type'] : 'loop';

		// Spacing requires a unit
		$gap = ! empty( $settings['gap'] ) ? $settings['gap'] : 0;

		// Add default unit
		if ( is_numeric( $gap ) ) {
			$gap = "{$gap}px";
		}

		// Arrows
		$arrows = isset( $settings['arrows'] );

		if ( $arrows ) {
			// Custom arrows set OR use default splide SVG arrows if no custom arrows set
			$arrows = ( ! empty( $settings['prevArrow'] ) && ! empty( $settings['nextArrow'] ) ) || ( empty( $settings['prevArrow'] ) && empty( $settings['nextArrow'] ) );
		}

		$splide_options = [
			'type'         => $type,
			'direction'    => ! empty( $settings['direction'] ) ? $settings['direction'] : ( is_rtl() ? 'rtl' : 'ltr' ),
			'keyboard'     => ! empty( $settings['keyboard'] ) ? $settings['keyboard'] : 'global', // 'focused', false
			'height'       => ! empty( $settings['height'] ) ? $settings['height'] : '50vh',
			'gap'          => $gap,
			'start'        => ! empty( $settings['start'] ) ? $settings['start'] : 0,
			'perPage'      => ! empty( $settings['perPage'] ) && $type !== 'fade' ? $settings['perPage'] : 1,
			'perMove'      => ! empty( $settings['perMove'] ) && $type !== 'fade' ? $settings['perMove'] : 1,
			'speed'        => ! empty( $settings['speed'] ) ? $settings['speed'] : 400,
			'interval'     => ! empty( $settings['interval'] ) ? $settings['interval'] : 3000,
			'autoHeight'   => isset( $settings['autoHeight'] ),
			'autoplay'     => isset( $settings['autoplay'] ),
			'pauseOnHover' => isset( $settings['pauseOnHover'] ),
			'pauseOnFocus' => isset( $settings['pauseOnFocus'] ),
			'arrows'       => $arrows,
			'pagination'   => isset( $settings['pagination'] ),
		];

		if ( isset( $settings['focus'] ) ) {
			$splide_options['focus'] = $settings['focus'];
		}

		// Auto height enabled: Set height to "auto"
		if ( isset( $settings['autoHeight'] ) ) {
			$splide_options['height'] = 'auto';
		}

		if ( isset( $settings['rewind'] ) && $type !== 'loop' ) {
			$splide_options['rewind'] = $settings['rewind'];

			if ( ! empty( $settings['rewindSpeed'] ) ) {
				$splide_options['rewindSpeed'] = $settings['rewindSpeed'];
			}

			if ( isset( $settings['rewindByDrag'] ) ) {
				$splide_options['rewindByDrag'] = $settings['rewindByDrag'];
			}
		}

		// STEP: Add settings per breakpoints to splide options
		$breakpoints = [];

		foreach ( Breakpoints::$breakpoints as $breakpoint ) {
			foreach ( array_keys( $splide_options ) as $option ) {
				$setting_key      = "$option:{$breakpoint['key']}";
				$breakpoint_width = ! empty( $breakpoint['width'] ) ? $breakpoint['width'] : false;
				$setting_value    = isset( $settings[ $setting_key ] ) ? $settings[ $setting_key ] : false;

				// Spacing requires a unit
				if ( $option === 'gap' ) {
					// Add default unit
					if ( is_numeric( $setting_value ) ) {
						$setting_value = "{$setting_value}px";
					}
				}

				if ( $breakpoint_width && $setting_value !== false ) {
					$breakpoints[ $breakpoint_width ][ $option ] = $setting_value;
				}
			}
		}

		if ( count( $breakpoints ) ) {
			$splide_options['breakpoints'] = $breakpoints;
		}

		// Buider: Disable splideJS drag to allow for Bricks DnD
		if ( ! $this->is_frontend ) {
			$splide_options['autoplay'] = false;
			$splide_options['noDrag']   = '.bricks-draggable-item';
			// $splide_options['drag'] = false;
		}

		// Custom options (provided as valid JSON string)
		$options_type = ! empty( $settings['optionsType'] ) ? $settings['optionsType'] : 'default';

		if ( $options_type === 'custom' && ! empty( $settings['options'] ) ) {
			$splide_options = trim( stripslashes( $settings['options'] ) );
		}

		if ( is_array( $splide_options ) ) {
			$splide_options = wp_json_encode( $splide_options );
		}

		// Remove line breaks
		$splide_options = str_replace( [ "\r", "\n", ' ' ], '', $splide_options );

		$this->set_attribute( '_root', 'data-splide', trim( $splide_options ) );

		$output = "<div {$this->render_attributes( '_root' )}>";

		$output .= '<div class="splide__track">';
		$output .= '<div class="splide__list">';

		// Render children elements (= individual items)
		$output .= Frontend::render_children( $this );

		$output .= '</div>'; // .splide__track
		$output .= '</div>'; // .splide__list

		if ( isset( $settings['arrows'] ) ) {
			$output .= $this->render_arrows();
		}

		$output .= '</div>'; // _root

		echo $output;
	}
}
