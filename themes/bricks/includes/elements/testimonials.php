<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Testimonials extends Element {
	public $category     = 'general';
	public $name         = 'testimonials';
	public $icon         = 'ti-comment-alt';
	public $css_selector = '.swiper-slide';
	public $scripts      = [ 'bricksSwiper' ];
	public $draggable    = false;

	public function get_label() {
		return esc_html__( 'Testimonials', 'bricks' );
	}

	public function enqueue_scripts() {
		wp_enqueue_script( 'bricks-swiper' );
		wp_enqueue_style( 'bricks-swiper' );
	}

	public function set_control_groups() {
		$this->control_groups['testimonials'] = [
			'title' => esc_html__( 'Testimonials', 'bricks' ),
			'tab'   => 'content',
		];

		$this->control_groups['settings'] = [
			'title' => esc_html__( 'Settings', 'bricks' ),
			'tab'   => 'content',
		];

		$this->control_groups['image'] = [
			'title' => esc_html__( 'Image', 'bricks' ),
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
		$this->controls['_margin']['css'][0]['selector']  = '';
		$this->controls['_padding']['css'][0]['selector'] = '';

		// TESTIMONIALS

		$this->controls['items'] = [
			'tab'           => 'content',
			'group'         => 'testimonials',
			'placeholder'   => esc_html__( 'Testimonials', 'bricks' ),
			'type'          => 'repeater',
			'selector'      => 'swiperJs',
			'titleProperty' => 'name',
			'fields'        => [
				'content' => [
					'label' => esc_html__( 'Content', 'bricks' ),
					'type'  => 'textarea'
				],

				'name'    => [
					'label' => esc_html__( 'Name', 'bricks' ),
					'type'  => 'text',
				],

				'title'   => [
					'label' => esc_html__( 'Title', 'bricks' ),
					'type'  => 'text',
				],

				'image'   => [
					'label' => esc_html__( 'Image', 'bricks' ),
					'type'  => 'image',
				],
			],
			'default'       => [
				[
					'content' => 'Lorem ipsum dolor ist amte. Consectetuer adipiscing eilt. Aenean commodo ligula egget dolor. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus.',
					'name'    => 'KIM NATAMO',
					'title'   => 'Developer',
					'image'   => [
						'url'  => 'https://source.unsplash.com/random/600x600?face,man',
						'full' => 'https://source.unsplash.com/random/600x600?face,man',
					],
				],
			],
		];

		// SETTINGS

		$swiper_controls = self::get_swiper_controls();

		$this->controls['initialSlide']   = $swiper_controls['initialSlide'];
		$this->controls['slidesToShow']   = $swiper_controls['slidesToShow'];
		$this->controls['slidesToScroll'] = $swiper_controls['slidesToScroll'];
		$this->controls['gutter']         = $swiper_controls['gutter'];

		$this->controls['alignItems'] = [
			'tab'     => 'content',
			'group'   => 'settings',
			'label'   => esc_html__( 'Align items', 'bricks' ),
			'type'    => 'justify-content',
			'exclude' => 'space',
			'css'     => [
				[
					'property' => 'justify-content',
					'selector' => '.repeater-item',
				],
			],

			'inline'  => true,
		];

		$this->controls['textAlign'] = [
			'tab'    => 'content',
			'group'  => 'settings',
			'type'   => 'text-align',
			'label'  => esc_html__( 'Text align', 'bricks' ),
			'css'    => [
				[
					'property' => 'text-align',
				],
			],
			'inline' => true,
		];

		$this->controls['effect']   = $swiper_controls['effect'];
		$this->controls['infinite'] = $swiper_controls['infinite'];
		$this->controls['random']   = [
			'tab'   => 'content',
			'group' => 'settings',
			'label' => esc_html__( 'Random order', 'bricks' ),
			'type'  => 'checkbox',
		];

		$this->controls['centerMode']      = $swiper_controls['centerMode'];
		$this->controls['disableLazyLoad'] = $swiper_controls['disableLazyLoad'];
		$this->controls['autoplay']        = $swiper_controls['autoplay'];
		$this->controls['pauseOnHover']    = $swiper_controls['pauseOnHover'];
		$this->controls['autoplaySpeed']   = $swiper_controls['autoplaySpeed'];
		$this->controls['speed']           = $swiper_controls['speed'];

		// IMAGE

		$this->controls['imageAlign'] = [
			'tab'     => 'content',
			'group'   => 'image',
			'label'   => esc_html__( 'Image align', 'bricks' ),
			'type'    => 'align-items',
			'exclude' => 'stretch',
			'css'     => [
				[
					'property' => 'align-items',
					'selector' => '.repeater-item',
				],
			],
			'inline'  => true,
		];

		$this->controls['imagePosition'] = [
			'tab'         => 'content',
			'group'       => 'image',
			'label'       => esc_html__( 'Image position', 'bricks' ),
			'type'        => 'select',
			'options'     => [
				'top'    => esc_html__( 'Top', 'bricks' ),
				'right'  => esc_html__( 'Right', 'bricks' ),
				'bottom' => esc_html__( 'Bottom', 'bricks' ),
				'left'   => esc_html__( 'Left', 'bricks' ),
			],
			'inline'      => true,
			'reset'       => true,
			'placeholder' => esc_html__( 'Left', 'bricks' ),
		];

		$this->controls['imageSize'] = [
			'tab'         => 'content',
			'group'       => 'image',
			'label'       => esc_html__( 'Image size', 'bricks' ),
			'type'        => 'number',
			'units'       => true,
			'css'         => [
				[
					'property' => 'width',
					'selector' => '.image',
				],
				[
					'property' => 'height',
					'selector' => '.image',
				],
			],
			'placeholder' => 60,
		];

		$this->controls['imageBorder'] = [
			'tab'   => 'content',
			'group' => 'image',
			'label' => esc_html__( 'Image border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.image',
				],
			],
		];

		$this->controls['imageBoxShadow'] = [
			'tab'   => 'content',
			'group' => 'image',
			'label' => esc_html__( 'Image box shadow', 'bricks' ),
			'type'  => 'box-shadow',
			'css'   => [
				[
					'property' => 'box-shadow',
					'selector' => '.image',
				],
			],
		];

		// ARROWS

		$this->controls['arrows']                     = $swiper_controls['arrows'];
		$this->controls['arrowHeight']                = $swiper_controls['arrowHeight'];
		$this->controls['arrowWidth']                 = $swiper_controls['arrowWidth'];
		$this->controls['arrowBackground']            = $swiper_controls['arrowBackground'];
		$this->controls['arrowBorder']                = $swiper_controls['arrowBorder'];
		$this->controls['arrowTypography']            = $swiper_controls['arrowTypography'];
		$this->controls['arrowTypography']['default'] = [
			'color' => [
				'hex' => Setup::get_default_color( 'body' ),
			],
		];

		$this->controls['prevArrowSeparator'] = $swiper_controls['prevArrowSeparator'];
		$this->controls['prevArrow']          = $swiper_controls['prevArrow'];
		$this->controls['prevArrowTop']       = $swiper_controls['prevArrowTop'];
		$this->controls['prevArrowRight']     = $swiper_controls['prevArrowRight'];
		$this->controls['prevArrowBottom']    = $swiper_controls['prevArrowBottom'];
		$this->controls['prevArrowLeft']      = $swiper_controls['prevArrowLeft'];

		$this->controls['nextArrowSeparator'] = $swiper_controls['nextArrowSeparator'];
		$this->controls['nextArrow']          = $swiper_controls['nextArrow'];
		$this->controls['nextArrowTop']       = $swiper_controls['nextArrowTop'];
		$this->controls['nextArrowRight']     = $swiper_controls['nextArrowRight'];
		$this->controls['nextArrowBottom']    = $swiper_controls['nextArrowBottom'];
		$this->controls['nextArrowLeft']      = $swiper_controls['nextArrowLeft'];

		// DOTS

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

		/**
		 * Tab: Style
		 */

		// Delete control '_typography'
		unset( $this->controls['_typography'] );

		$this->controls['typographyContent'] = [
			'tab'   => 'style',
			'group' => '_typography',
			'label' => esc_html__( 'Testimonial', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.testimonial-content-wrapper',
				],
			],
		];

		$this->controls['typographyName'] = [
			'tab'   => 'style',
			'group' => '_typography',
			'label' => esc_html__( 'Name', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.testimonial-name',
				],
			],
		];

		$this->controls['typographyTitle'] = [
			'tab'   => 'style',
			'group' => '_typography',
			'label' => esc_html__( 'Title', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.testimonial-title',
				],
			],
		];

		// DEFAULTS

		// NOTE: Not is use. Conflict with other settings
		// $this->controls['_gradient']['default']['cssSelector'] = '.swiper-slide';
		 $this->controls['_border']['css'][0]['selector']    = '.swiper-slide';
		 $this->controls['_boxShadow']['css'][0]['selector'] = '.swiper-slide';

		 unset( $this->controls['slidesToShow']['default'] );
		 unset( $this->controls['slidesToScroll']['default'] );
		 unset( $this->controls['gutter']['default'] );
		 unset( $this->controls['arrows']['default'] );
		 unset( $this->controls['dots']['default'] );
		 unset( $this->controls['height']['default'] );
	}

	public function render() {
		$settings = $this->settings;
		$items    = ! empty( $settings['items'] ) ? $settings['items'] : false;

		if ( ! $items ) {
			return $this->render_element_placeholder(
				[
					'title' => esc_html__( 'No testimonials added.', 'bricks' ),
				]
			);
		}

		// Random testimonial order
		if ( isset( $settings['random'] ) ) {
			$testimonial_keys = array_keys( $items );
			shuffle( $testimonial_keys );
			$testimonials = [];

			foreach ( $testimonial_keys as $key ) {
				$testimonials[ $key ] = $items[ $key ];
			}
		} else {
			$testimonials = $items;
		}

		// Meta wrapper
		$meta_wrapper_classes[] = 'testimonial-meta-wrapper';

		$meta_wrapper_classes[] = ! empty( $settings['imagePosition'] ) ? "image-position-{$settings['imagePosition']}" : 'image-position-left';

		$this->set_attribute( 'meta-wrapper', 'class', $meta_wrapper_classes );

		$options = [
			'slidesPerView'  => isset( $settings['slidesToShow'] ) ? intval( $settings['slidesToShow'] ) : 1,
			'slidesPerGroup' => isset( $settings['slidesToScroll'] ) ? intval( $settings['slidesToScroll'] ) : 1,
			'autoHeight'     => true,
			'speed'          => isset( $settings['speed'] ) ? intval( $settings['speed'] ) : 300,
			'effect'         => isset( $settings['effect'] ) ? $settings['effect'] : 'slide',
			'spaceBetween'   => isset( $settings['gutter'] ) ? intval( $settings['gutter'] ) : 0,
			'initialSlide'   => isset( $settings['initialSlide'] ) ? intval( $settings['initialSlide'] ) : 0,
			'loop'           => isset( $settings['infinite'] ),
			'centeredSlides' => isset( $settings['centerMode'] ),
		];

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

		$breakpoint_options = Helpers::generate_swiper_breakpoint_data_options( $settings );

		// Has slidesPerView/slidesPerGroup set on non-desktop breakpoints
		if ( count( $breakpoint_options ) > 1 ) {
			unset( $options['slidesPerView'] );
			unset( $options['slidesPerGroup'] );

			$options['breakpoints'] = $breakpoint_options;
		}

		$this->set_attribute( 'swiper', 'class', 'bricks-swiper-container' );
		$this->set_attribute( 'swiper', 'data-script-args', wp_json_encode( $options ) );

		// Render
		?>
		<div <?php echo $this->render_attributes( '_root' ); ?>>
			<div <?php echo $this->render_attributes( 'swiper' ); ?>>
				<div class="swiper-wrapper">
					<?php
					foreach ( $testimonials as $index => $testimonial ) {
						echo '<div class="repeater-item swiper-slide">';

						if ( isset( $testimonial['content'] ) ) {
							$this->set_attribute( "content-$index", 'class', [ 'testimonial-content-wrapper' ] );

							echo "<div {$this->render_attributes( "content-$index" )}>{$testimonial['content']}</div>";
						}

						if ( isset( $testimonial['name'] ) || isset( $testimonial['title'] ) || isset( $testimonial['image'] ) ) {
							echo "<div {$this->render_attributes( 'meta-wrapper' )}>";

							if ( ! empty( $testimonial['image'] ) ) {
								if ( ! empty( $testimonial['image']['useDynamicData'] ) ) {
									$images = $this->render_dynamic_data_tag( $testimonial['image']['useDynamicData'], 'image' );
									$size   = isset( $testimonial['image']['size'] ) ? $testimonial['image']['size'] : BRICKS_DEFAULT_IMAGE_SIZE;
									$url    = $images ? wp_get_attachment_image_url( $images[0], $size ) : $testimonial['image']['url'];
								} else {
									$url = $testimonial['image']['url'];
								}
								if ( $this->lazy_load() ) {
									echo '<div class="image css-filter bricks-lazy-hidden" data-style="background-image: url(' . esc_url( $url ) . ')"></div>';
								} else {
									echo '<div class="image css-filter" style="background-image: url(' . esc_url( $url ) . ')"></div>';
								}
							}

							if ( isset( $testimonial['name'] ) || isset( $testimonial['title'] ) ) {
								echo '<div class="testimonial-meta-data">';

								if ( isset( $testimonial['name'] ) ) {
									$this->set_attribute( "name-$index", 'class', [ 'testimonial-name' ] );

									echo "<div {$this->render_attributes( "name-$index" )}>{$testimonial['name']}</div>";
								}

								if ( isset( $testimonial['title'] ) ) {
									$this->set_attribute( "title-$index", 'class', [ 'testimonial-title' ] );

									echo "<div {$this->render_attributes( "title-$index" )}>{$testimonial['title']}</div>";
								}

								echo '</div>';
							}

							echo '</div>';
						}

						echo '</div>';
					}
					?>
				</div>
			</div>

			<?php echo $this->render_swiper_nav(); ?>
		</div>
		<?php
	}
}
