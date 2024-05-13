<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Slider extends Element {
	public $category   = 'media';
	public $name       = 'slider';
	public $icon       = 'ti-layout-slider';
	public $scripts    = [ 'bricksSwiper' ];
	public $draggable  = false;
	public $loop_index = 0;

	public function get_label() {
		return esc_html__( 'Slider', 'bricks' );
	}

	public function enqueue_scripts() {
		wp_enqueue_script( 'bricks-swiper' );
		wp_enqueue_style( 'bricks-swiper' );
	}

	public function set_control_groups() {
		$this->control_groups['settings'] = [
			'title' => esc_html__( 'Settings', 'bricks' ),
			'tab'   => 'content',
		];

		$this->control_groups['arrows'] = [
			'title' => esc_html__( 'Arrows', 'bricks' ),
			'tab'   => 'content',
		];

		$this->control_groups['dots'] = [
			'title' => esc_html__( 'Dots', 'bricks' ),
			'tab'   => 'content',
		];
	}

	public function set_controls() {
		$this->controls['_gradient']['css'][0]['selector'] = '.image';

		$this->controls['items'] = [
			'tab'         => 'content',
			'placeholder' => esc_html__( 'Slide', 'bricks' ),
			'type'        => 'repeater',
			'checkLoop'   => true,
			'selector'    => 'swiperJs',
			'fields'      => [
				'title'            => [
					'label' => esc_html__( 'Title', 'bricks' ),
					'type'  => 'text',
				],

				'titleTag'         => [
					'label'       => esc_html__( 'Title Tag', 'bricks' ),
					'type'        => 'select',
					'options'     => [
						'h1' => 'h1',
						'h2' => 'h2',
						'h3' => 'h3',
						'h4' => 'h4',
						'h5' => 'h5',
						'h6' => 'h6',
					],
					'inline'      => true,
					'placeholder' => 'h3',
				],

				'content'          => [
					'label' => esc_html__( 'Content', 'bricks' ),
					'type'  => 'editor',
				],

				'buttonText'       => [
					'label' => esc_html__( 'Button text', 'bricks' ),
					'type'  => 'text',
				],

				'buttonStyle'      => [
					'label'    => esc_html__( 'Button style', 'bricks' ),
					'type'     => 'select',
					'options'  => Setup::$control_options['styles'],
					'inline'   => true,
					'info'     => __( 'Customize in "Settings" group.', 'bricks' ),
					'default'  => 'light',
					'required' => [ 'buttonText', '!=', '' ],
				],

				'buttonSize'       => [
					'label'    => esc_html__( 'Button size', 'bricks' ),
					'type'     => 'select',
					'options'  => Setup::$control_options['buttonSizes'],
					'inline'   => true,
					'required' => [ 'buttonText', '!=', '' ],
				],

				'buttonWidth'      => [
					'label'    => esc_html__( 'Button width', 'bricks' ),
					'type'     => 'number',
					'units'    => true,
					'css'      => [
						[
							'property' => 'width',
							'selector' => '.bricks-button',
						],
					],
					'required' => [ 'buttonText', '!=', '' ],
				],

				'buttonLink'       => [
					'label'    => esc_html__( 'Button link', 'bricks' ),
					'type'     => 'link',
					'required' => [ 'buttonText', '!=', '' ],
				],

				'buttonBackground' => [
					'label'    => esc_html__( 'Button background', 'bricks' ),
					'type'     => 'color',
					'css'      => [
						[
							'property' => 'background-color',
							'selector' => '.bricks-button',
						],
					],
					'required' => [ 'buttonText', '!=', '' ],
				],

				'buttonBorder'     => [
					'label'    => esc_html__( 'Button border', 'bricks' ),
					'type'     => 'border',
					'css'      => [
						[
							'property' => 'border',
							'selector' => '.bricks-button',
						],
					],
					'required' => [ 'buttonText', '!=', '' ],
				],

				'buttonBoxShadow'  => [
					'label'    => esc_html__( 'Button box shadow', 'bricks' ),
					'type'     => 'box-shadow',
					'css'      => [
						[
							'property' => 'box-shadow',
							'selector' => '.bricks-button',
						],
					],
					'required' => [ 'buttonText', '!=', '' ],
				],

				'buttonTypography' => [
					'label'    => esc_html__( 'Button typography', 'bricks' ),
					'type'     => 'typography',
					'css'      => [
						[
							'property' => 'font',
							'selector' => '.bricks-button',
						],
					],
					'required' => [ 'buttonText', '!=', '' ],
				],

				'background'       => [
					'label'   => esc_html__( 'Background', 'bricks' ),
					'type'    => 'background',
					'exclude' => 'video',
				],

				'overlay'          => [
					'label'   => esc_html__( 'Overlay', 'bricks' ),
					'type'    => 'color',
					'exclude' => 'video',
					'css'     => [
						[
							'property' => 'background-color',
							'selector' => '.image:after',
						],
					],
				],
			],
			'default'     => [
				[
					'title'      => esc_html__( 'I am a slide', 'bricks' ),
					'content'    => esc_html__( 'Content goes here ..', 'bricks' ),
					'buttonText' => esc_html__( 'Click me', 'bricks' ),
					'buttonLink' => [
						'url' => '#',
					],
					'background' => [
						'color' => [
							'hex' => Setup::get_default_color( 'background-dark' ),
						],
					],
				],
				[
					'title'      => esc_html__( 'Just another slide', 'bricks' ),
					'content'    => esc_html__( 'More content to come ..', 'bricks' ),
					'buttonText' => esc_html__( 'Learn more', 'bricks' ),
					'buttonLink' => [
						'url' => '#',
					],
					'background' => [
						'color' => [
							'hex' => Setup::get_default_color( 'background-dark' ),
						],
					],
				],
			],
		];

		$this->controls = array_replace_recursive( $this->controls, $this->get_loop_builder_controls() );

		// SETTINGS
		$swiper_controls = self::get_swiper_controls();

		$this->controls['slidesToShow']                 = $swiper_controls['slidesToShow'];
		$this->controls['slidesToScroll']               = $swiper_controls['slidesToScroll'];
		$this->controls['gutter']                       = $swiper_controls['gutter'];
		$this->controls['height']                       = $swiper_controls['height'];
		$this->controls['height']['label']              = esc_html__( 'Min. height', 'bricks' );
		$this->controls['height']['placeholder']        = '50vh';
		$this->controls['height']['css'][0]['property'] = 'min-height';
		$this->controls['effect']                       = $swiper_controls['effect'];
		$this->controls['swiperLoop']                   = $swiper_controls['swiperLoop'];
		$this->controls['speed']                        = $swiper_controls['speed'];
		$this->controls['disableLazyLoad']              = $swiper_controls['disableLazyLoad'];
		$this->controls['autoplay']                     = $swiper_controls['autoplay'];
		$this->controls['pauseOnHover']                 = $swiper_controls['pauseOnHover'];
		$this->controls['autoplaySpeed']                = $swiper_controls['autoplaySpeed'];

		// Title

		$this->controls['titleSeparator'] = [
			'tab'   => 'content',
			'group' => 'settings',
			'label' => esc_html__( 'Title', 'bricks' ),
			'type'  => 'separator',
		];

		$this->controls['titleMargin'] = [
			'tab'   => 'content',
			'group' => 'settings',
			'label' => esc_html__( 'Title margin', 'bricks' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'margin',
					'selector' => '.slider-content .title',
				],
			],
		];

		$this->controls['titleTypography'] = [
			'tab'   => 'style',
			'group' => 'settings',
			'label' => esc_html__( 'Title typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.title',
				],
			],
		];

		// Content

		$this->controls['contentSeparator'] = [
			'tab'   => 'content',
			'group' => 'settings',
			'label' => esc_html__( 'Content', 'bricks' ),
			'type'  => 'separator',
		];

		$this->controls['contentWidth'] = [
			'tab'   => 'content',
			'group' => 'settings',
			'label' => esc_html__( 'Content width', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'css'   => [
				[
					'property' => 'width',
					'selector' => '.slider-content',
				]
			],
		];

		$this->controls['contentBackgroundColor'] = [
			'tab'   => 'content',
			'group' => 'settings',
			'label' => esc_html__( 'Content background', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.slider-content',
				]
			],
		];

		$this->controls['contentTypography'] = [
			'tab'   => 'style',
			'group' => 'settings',
			'label' => esc_html__( 'Content typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.content',
				],
			],
		];

		$this->controls['contentMargin'] = [
			'tab'   => 'content',
			'group' => 'settings',
			'label' => esc_html__( 'Content margin', 'bricks' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'margin',
					'selector' => '.slider-content',
				],
			],
		];

		$this->controls['contentPadding'] = [
			'tab'         => 'content',
			'group'       => 'settings',
			'label'       => esc_html__( 'Content padding', 'bricks' ),
			'type'        => 'spacing',
			'css'         => [
				[
					'property' => 'padding',
					'selector' => '.slider-content',
				],
			],
			'placeholder' => [
				'top'    => 30,
				'right'  => 60,
				'bottom' => 30,
				'left'   => 60,
			],
		];

		$this->controls['contentAlignHorizontal'] = [
			'tab'     => 'content',
			'group'   => 'settings',
			'label'   => esc_html__( 'Content align horizontal', 'bricks' ),
			'type'    => 'justify-content',
			'exclude' => 'space',
			'css'     => [
				[
					'property' => 'justify-content',
					'selector' => '.swiper-slide',
				],
			],
		];

		$this->controls['contentAlignVertical'] = [
			'tab'     => 'content',
			'group'   => 'settings',
			'label'   => esc_html__( 'Content align vertical', 'bricks' ),
			'type'    => 'align-items',
			'exclude' => 'stretch',
			'css'     => [
				[
					'property' => 'align-items',
					'selector' => '.swiper-slide',
				],
			],
		];

		$this->controls['contentTextAlign'] = [
			'tab'   => 'content',
			'group' => 'settings',
			'type'  => 'text-align',
			'label' => esc_html__( 'Content text align', 'bricks' ),
			'css'   => [
				[
					'property' => 'text-align',
					'selector' => '.slider-content',
				],
			],
		];

		// Button

		$this->controls['buttonSeparator'] = [
			'tab'   => 'content',
			'group' => 'settings',
			'label' => esc_html__( 'Button', 'bricks' ),
			'type'  => 'separator',
		];

		$this->controls['buttonStyle'] = [
			'tab'     => 'content',
			'group'   => 'settings',
			'label'   => esc_html__( 'Button style', 'bricks' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => Setup::$control_options['styles'],
			'default' => 'light',
		];

		$this->controls['buttonSize'] = [
			'tab'     => 'content',
			'group'   => 'settings',
			'label'   => esc_html__( 'Button size', 'bricks' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => Setup::$control_options['buttonSizes'],
		];

		$this->controls['buttonWidth'] = [
			'label'    => esc_html__( 'Button width', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'width',
					'selector' => '.bricks-button',
				],
			],
			'required' => [ 'buttonText', '!=', '' ],
		];

		$this->controls['buttonBackground'] = [
			'tab'   => 'content',
			'group' => 'settings',
			'label' => esc_html__( 'Background', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.bricks-button',
				],
			],
		];

		$this->controls['buttonBorder'] = [
			'tab'   => 'content',
			'group' => 'settings',
			'label' => esc_html__( 'Border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.bricks-button',
				],
			],
		];

		$this->controls['buttonBoxShadow'] = [
			'tab'   => 'content',
			'group' => 'settings',
			'label' => esc_html__( 'Box shadow', 'bricks' ),
			'type'  => 'box-shadow',
			'css'   => [
				[
					'property' => 'box-shadow',
					'selector' => '.bricks-button',
				],
			],
		];

		$this->controls['buttonTypography'] = [
			'tab'   => 'content',
			'group' => 'settings',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.bricks-button',
				],
			],
		];

		// Background

		$this->controls['backgroundSeparator'] = [
			'tab'   => 'content',
			'group' => 'settings',
			'label' => esc_html__( 'Background', 'bricks' ),
			'type'  => 'separator',
		];

		$this->controls['backgroundPositionTop'] = [
			'tab'   => 'content',
			'group' => 'settings',
			'label' => esc_html__( 'Top', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'css'   => [
				[
					'property' => 'top',
					'selector' => '.image',
				]
			],
		];

		$this->controls['backgroundPositionRight'] = [
			'tab'   => 'content',
			'group' => 'settings',
			'label' => esc_html__( 'Right', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'css'   => [
				[
					'property' => 'right',
					'selector' => '.image',
				]
			],
		];

		$this->controls['backgroundPositionBottom'] = [
			'tab'   => 'content',
			'group' => 'settings',
			'label' => esc_html__( 'Bottom', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'css'   => [
				[
					'property' => 'bottom',
					'selector' => '.image',
				]
			],
		];

		$this->controls['backgroundPositionLeft'] = [
			'tab'   => 'content',
			'group' => 'settings',
			'label' => esc_html__( 'Left', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'css'   => [
				[
					'property' => 'left',
					'selector' => '.image',
				]
			],
		];

		// Arrows

		$this->controls['arrows']          = $swiper_controls['arrows'];
		$this->controls['arrowHeight']     = $swiper_controls['arrowHeight'];
		$this->controls['arrowWidth']      = $swiper_controls['arrowWidth'];
		$this->controls['arrowBackground'] = $swiper_controls['arrowBackground'];
		$this->controls['arrowBorder']     = $swiper_controls['arrowBorder'];
		$this->controls['arrowTypography'] = $swiper_controls['arrowTypography'];

		$this->controls['prevArrowSeparator'] = $swiper_controls['prevArrowSeparator'];
		$this->controls['prevArrow']          = $swiper_controls['prevArrow'];
		$this->controls['prevArrowTop']       = $swiper_controls['prevArrowTop'];
		$this->controls['prevArrowRight']     = $swiper_controls['prevArrowRight'];
		$this->controls['prevArrowBottom']    = $swiper_controls['prevArrowBottom'];
		$this->controls['prevArrowLeft']      = $swiper_controls['prevArrowLeft'];
		$this->controls['prevArrowTransform'] = $swiper_controls['prevArrowTransform'];

		$this->controls['nextArrowSeparator'] = $swiper_controls['nextArrowSeparator'];
		$this->controls['nextArrow']          = $swiper_controls['nextArrow'];
		$this->controls['nextArrowTop']       = $swiper_controls['nextArrowTop'];
		$this->controls['nextArrowRight']     = $swiper_controls['nextArrowRight'];
		$this->controls['nextArrowBottom']    = $swiper_controls['nextArrowBottom'];
		$this->controls['nextArrowLeft']      = $swiper_controls['nextArrowLeft'];
		$this->controls['nextArrowTransform'] = $swiper_controls['nextArrowTransform'];

		// Dots

		$this->controls['dots']            = $swiper_controls['dots'];
		$this->controls['dotsDynamic']     = $swiper_controls['dotsDynamic'];
		$this->controls['dotsVertical']    = $swiper_controls['dotsVertical'];
		$this->controls['dotsHeight']      = $swiper_controls['dotsHeight'];
		$this->controls['dotsWidth']       = $swiper_controls['dotsWidth'];
		$this->controls['dotsTop']         = $swiper_controls['dotsTop'];
		$this->controls['dotsRight']       = $swiper_controls['dotsRight'];
		$this->controls['dotsBottom']      = $swiper_controls['dotsBottom'];
		$this->controls['dotsLeft']        = $swiper_controls['dotsLeft'];
		$this->controls['dotsBorder']      = $swiper_controls['dotsBorder'];
		$this->controls['dotsColor']       = $swiper_controls['dotsColor'];
		$this->controls['dotsActiveColor'] = $swiper_controls['dotsActiveColor'];
		$this->controls['dotsSpacing']     = $swiper_controls['dotsSpacing'];
	}

	public function render() {
		$settings = $this->settings;
		$slides   = ! empty( $settings['items'] ) ? $settings['items'] : false;

		if ( ! $slides ) {
			return $this->render_element_placeholder( [ 'title' => esc_html__( 'No slide added.', 'bricks' ) ] );
		}

		$options = [
			'slidesPerView'  => isset( $settings['slidesToShow'] ) ? intval( $settings['slidesToShow'] ) : 1,
			'slidesPerGroup' => isset( $settings['slidesToScroll'] ) ? intval( $settings['slidesToScroll'] ) : 1,
			'speed'          => isset( $settings['speed'] ) ? intval( $settings['speed'] ) : 300,
			'effect'         => isset( $settings['effect'] ) ? $settings['effect'] : 'slide',
			'spaceBetween'   => isset( $settings['gutter'] ) ? intval( $settings['gutter'] ) : 0,
			'initialSlide'   => isset( $settings['initialSlide'] ) ? intval( $settings['initialSlide'] ) : 0,
			'loop'           => isset( $settings['swiperLoop'] ) && $settings['swiperLoop'] === 'disable' ? false : true,
			'centeredSlides' => isset( $settings['centerMode'] ),
		];

		$breakpoint_options = Helpers::generate_swiper_breakpoint_data_options( $settings );

		// Has slidesPerView/slidesPerGroup set on non-desktop breakpoints
		if ( count( $breakpoint_options ) > 1 ) {
			unset( $options['slidesPerView'] );
			unset( $options['slidesPerGroup'] );

			$options['breakpoints'] = $breakpoint_options;
		}

		if ( isset( $settings['autoplay'] ) ) {
			$options['autoplay'] = Helpers::generate_swiper_autoplay_options( $settings );
		}

		// Arrow navigation
		if ( isset( $settings['arrows'] ) ) {
			$options['navigation'] = true;
		}

		// Dots
		if ( isset( $settings['dots'] ) ) {
			$options['pagination'] = true;

			if ( isset( $settings['dotsDynamic'] ) && ! isset( $settings['dotsVertical'] ) ) {
				$options['dynamicBullets'] = true;
			}
		}

		// Query Loop
		if ( isset( $settings['hasLoop'] ) ) {
			$query = new Query(
				[
					'id'       => $this->id,
					'settings' => $settings,
				]
			);

			if ( $query->count === 0 ) {
				// No results: Empty by default (@since 1.4)
				$no_results_content = $query->get_no_results_content();

				if ( empty( $no_results_content ) ) {
					return $this->render_element_placeholder( [ 'title' => esc_html__( 'No results', 'bricks' ) ] );
				}
			}
		}

		$this->set_attribute( 'swiper', 'class', 'bricks-swiper-container' );
		$this->set_attribute( 'swiper', 'data-script-args', wp_json_encode( $options ) );

		// RENDER
		echo "<div {$this->render_attributes( '_root' )}>";

		if ( isset( $settings['hasLoop'] ) && $query->count === 0 ) {
			echo $no_results_content;
		} else {
			echo "<div {$this->render_attributes( 'swiper' )}>";
			echo '<div class="swiper-wrapper">';

			// Query Loop
			if ( isset( $settings['hasLoop'] ) ) {
				$slide = $slides[0];

				echo $query->render( [ $this, 'render_repeater_item' ], compact( 'slide' ) );

				// Destroy query to explicitly remove it from the global store
				$query->destroy();
				unset( $query );
			}

			// Static slides
			else {
				foreach ( $slides as $slide ) {
					echo self::render_repeater_item( $slide );
				}
			}
			echo '</div>';
			echo '</div>';

			echo $this->render_swiper_nav();
		}

		echo '</div>';
	}

	public function render_repeater_item( $slide ) {
		$settings = $this->settings;
		$index    = $this->loop_index;

		// Lazy load: Add slide background-color/image via inline style
		$slide_styles = [];

		// Slide background color
		if ( ! empty( $slide['background']['color'] ) ) {
			$slide_styles[] = 'background-color: ' . Assets::generate_css_color( $slide['background']['color'] );
		}

		// Dynamic data background image
		if ( ! empty( $slide['background']['image']['useDynamicData'] ) ) {
			$images = $this->render_dynamic_data_tag( $slide['background']['image']['useDynamicData'], 'image' );

			if ( isset( $images[0] ) ) {
				$slide['background']['image']['url'] = is_numeric( $images[0] ) ? wp_get_attachment_image_url( $images[0], 'full' ) : $images[0];
			} else {
				// Reset the image url (in a loop it could be set to the page featured image in the builder)
				$slide['background']['image']['url'] = '';
			}
		}

		if ( ! empty( $slide['background']['image']['url'] ) ) {
			$slide_styles[] = 'background-image: url(' . esc_url( $slide['background']['image']['url'] ) . ')';
		}

		foreach ( [ 'attachment', 'position', 'repeat', 'size' ] as $property ) {
			if ( ! isset( $slide['background'][ $property ] ) ) {
				continue;
			}

			$background_value = $slide['background'][ $property ];

			if ( $property == 'position' && $slide['background']['position'] == 'custom' ) {

				if ( isset( $slide['background']['positionX'] ) ) {
					$background_position[] = $slide['background']['positionX'];
				} else {
					$background_position[] = 'center';
				}

				if ( isset( $slide['background']['positionY'] ) ) {
					$background_position[] = $slide['background']['positionY'];
				} else {
					$background_position[] = 'center';
				}

				$slide_styles[] = 'background-position: ' . implode( ' ', $background_position );
			} elseif ( $property == 'size' && $slide['background']['size'] == 'custom' ) {
				$background_value = ! empty( $slide['background']['custom'] ) ? $slide['background']['custom'] : 'cover';

				$slide_styles[] = "background-size: $background_value";
			} else {
				$slide_styles[] = "background-$property: $background_value";
			}
		}

		// Check if different image is set on mobile (if so, don't lazy load)
		if ( $this->lazy_load() ) {
			$lazy_load_background = true;

			foreach ( Breakpoints::$breakpoints as $breakpoint ) {
				$key = $breakpoint['key'];

				if ( isset( $slide[ "background:$key" ] ) ) {
					$lazy_load_background = false;
				}
			}

			if ( $lazy_load_background ) {
				$this->set_attribute( "slide-background-{$index}", 'class', 'image css-filter bricks-lazy-hidden' );

				if ( count( $slide_styles ) ) {
					$this->set_attribute( "slide-background-{$index}", 'data-style', join( '; ', $slide_styles ) );
				}
			} else {
				$this->set_attribute( "slide-background-{$index}", 'class', 'image css-filter' );
			}
		}

		// Disable lazy load
		else {
			$this->set_attribute( "slide-background-{$index}", 'class', 'image css-filter' );
			$this->set_attribute( "slide-background-{$index}", 'style', join( '; ', $slide_styles ) );
		}

		// Slide button
		$button_classes = [ 'bricks-button' ];

		if ( isset( $slide['buttonStyle'] ) ) {
			$button_classes[] = "bricks-background-{$slide['buttonStyle']}";
		} elseif ( isset( $settings['buttonStyle'] ) ) {
			$button_classes[] = "bricks-background-{$settings['buttonStyle']}";
		}

		if ( isset( $slide['buttonSize'] ) ) {
			$button_classes[] = $slide['buttonSize'];
		} elseif ( isset( $settings['buttonSize'] ) ) {
			$button_classes[] = $settings['buttonSize'];
		}

		$this->set_attribute( "slide-button-{$index}", 'class', $button_classes );

		// Link
		if ( isset( $slide['buttonLink'] ) ) {
			$this->set_link_attributes( "slide-button-{$index}", $slide['buttonLink'] );
		}

		// Render
		ob_start();
		?>

		<div class="repeater-item swiper-slide" data-brx-swiper-index="<?php echo $index; ?>">
			<div class="slider-content">
				<?php
				if ( isset( $slide['title'] ) && ! empty( $slide['title'] ) ) {
					$tag = isset( $slide['titleTag'] ) ? esc_html( $slide['titleTag'] ) : 'h3';

					$this->set_attribute( "title-$index", $tag );
					$this->set_attribute( "title-$index", 'class', [ 'title' ] );

					echo "<{$this->render_attributes( "title-{$index}" )}>{$slide['title']}</{$tag}>";
				}

				$content = ! empty( $slide['content'] ) ? $slide['content'] : false;

				if ( $content ) {
					$this->set_attribute( "content-$index", 'class', [ 'content' ] );

					$content = $this->render_dynamic_data( $content );

					echo "<div {$this->render_attributes( "content-{$index}" )}>" . Helpers::parse_editor_content( $content ) . '</div>';
				}

				if ( isset( $slide['buttonText'] ) && ! empty( $slide['buttonText'] ) ) {
					echo "<a {$this->render_attributes( "slide-button-{$index}" )}>{$slide['buttonText']}</a>";
				}
				?>
			</div>

			<div <?php echo $this->render_attributes( "slide-background-{$index}" ); ?>></div>
		</div>

		<?php
		$html = ob_get_clean();

		$this->loop_index++;

		return $html;
	}
}
