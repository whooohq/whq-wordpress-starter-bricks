<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Post_Navigation extends Element {
	public $category = 'single';
	public $name     = 'post-navigation';
	public $icon     = 'ti-layout-menu-separated';

	public function get_label() {
		return esc_html__( 'Post Navigation', 'bricks' );
	}

	public function set_control_groups() {
		$this->control_groups['label'] = [
			'title' => esc_html__( 'Label', 'bricks' ),
			'tab'   => 'content',
		];

		$this->control_groups['title'] = [
			'title' => esc_html__( 'Title', 'bricks' ),
			'tab'   => 'content',
		];

		$this->control_groups['arrows'] = [
			'title' => esc_html__( 'Arrows', 'bricks' ),
			'tab'   => 'content',
		];

		$this->control_groups['image'] = [
			'title' => esc_html__( 'Image', 'bricks' ),
			'tab'   => 'content',
		];
	}

	public function set_controls() {
		$swiper_controls = self::get_swiper_controls();

		// LAYOUT

		$this->controls['_direction'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Direction', 'bricks' ),
			'type'     => 'direction',
			'css'      => [
				[
					'property' => 'flex-direction',
				],
			],
			'inline'   => true,
			'rerender' => false,
		];

		$this->controls['postWidth'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Max. post width', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'css'   => [
				[
					'selector' => 'a',
					'property' => 'width',
				],
			],
		];

		// QUERY

		$this->controls['inSameTerm'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'In same term', 'bricks' ),
			'type'        => 'checkbox',
			'description' => esc_html__( 'Posts should be in a same taxonomy term.', 'bricks' ),
		];

		$this->controls['excludedTerms'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Excluded terms', 'bricks' ),
			'type'        => 'select',
			'multiple'    => true,
			'options'     => bricks_is_builder() ? Helpers::get_terms_options() : [],
			'placeholder' => esc_html__( 'None', 'bricks' ),
		];

		$this->controls['taxonomy'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Taxonomy', 'bricks' ),
			'type'        => 'select',
			'options'     => Setup::$control_options['taxonomies'],
			'placeholder' => 'category',
			'required'    => [ 'inSameTerm', '!=', '' ],
		];

		// LABEL

		$this->controls['label'] = [
			'tab'     => 'content',
			'group'   => 'label',
			'label'   => esc_html__( 'Show label', 'bricks' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['prevLabel'] = [
			'tab'         => 'content',
			'group'       => 'label',
			'label'       => esc_html__( 'Prev label', 'bricks' ),
			'type'        => 'text',
			'inline'      => true,
			'placeholder' => esc_html__( 'Previous post', 'bricks' ),
			'required'    => [ 'label', '!=', '' ],
		];

		$this->controls['nextLabel'] = [
			'tab'         => 'content',
			'group'       => 'label',
			'label'       => esc_html__( 'Next label', 'bricks' ),
			'type'        => 'text',
			'inline'      => true,
			'placeholder' => esc_html__( 'Next post', 'bricks' ),
			'required'    => [ 'label', '!=', '' ],
		];

		$this->controls['labelTypography'] = [
			'tab'      => 'content',
			'group'    => 'label',
			'label'    => esc_html__( 'Label typography', 'bricks' ),
			'type'     => 'typography',
			'css'      => [
				[
					'selector' => '.label',
					'property' => 'font',
				],
			],
			'required' => [ 'label', '!=', '' ],
		];

		// TITLE

		$this->controls['title'] = [
			'tab'     => 'content',
			'group'   => 'title',
			'label'   => esc_html__( 'Show title', 'bricks' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['titleTag'] = [
			'tab'         => 'content',
			'group'       => 'title',
			'label'       => esc_html__( 'Title tag', 'bricks' ),
			'type'        => 'text',
			'inline'      => true,
			'small'       => true,
			'placeholder' => 'h5',
			'required'    => [ 'title', '!=', '' ],
		];

		$this->controls['titleTypography'] = [
			'tab'      => 'content',
			'group'    => 'title',
			'label'    => esc_html__( 'Title typography', 'bricks' ),
			'type'     => 'typography',
			'css'      => [
				[
					'selector' => '.title',
					'property' => 'font',
				],
			],
			'required' => [ 'title', '!=', '' ],
		];

		$this->controls['prevJustifyContent'] = [
			'tab'     => 'content',
			'group'   => 'title',
			'label'   => esc_html__( 'Alignment', 'bricks' ) . ': ' . esc_html__( 'Previous post', 'bricks' ),
			'type'    => 'justify-content',
			'exclude' => [ 'space' ],
			'css'     => [
				[
					'property' => 'justify-content',
					'selector' => '.prev-post',
				],
			],
		];

		$this->controls['nextJustifyContent'] = [
			'tab'     => 'content',
			'group'   => 'title',
			'label'   => esc_html__( 'Alignment', 'bricks' ) . ': ' . esc_html__( 'Next post', 'bricks' ),
			'type'    => 'justify-content',
			'exclude' => [ 'space' ],
			'css'     => [
				[
					'property' => 'justify-content',
					'selector' => '.next-post',
				],
			],
		];

		// ARROW

		$this->controls['prevArrow'] = $swiper_controls['prevArrow'];
		unset( $this->controls['prevArrow']['required'] );
		$this->controls['nextArrow'] = $swiper_controls['nextArrow'];
		unset( $this->controls['nextArrow']['required'] );

		$this->controls['arrowTypography'] = $swiper_controls['arrowTypography'];
		unset( $this->controls['arrowTypography']['required'] );

		// IMAGE

		$this->controls['image'] = [
			'tab'     => 'content',
			'group'   => 'image',
			'label'   => esc_html__( 'Show image', 'bricks' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['imageSize'] = [
			'tab'         => 'content',
			'group'       => 'image',
			'label'       => esc_html__( 'Size', 'bricks' ),
			'type'        => 'select',
			'options'     => $this->control_options['imageSizes'],
			'placeholder' => esc_html__( 'Thumbnail', 'bricks' ),
			'required'    => [ 'image', '!=', '' ],
		];

		$this->controls['imageHeight'] = [
			'tab'         => 'content',
			'group'       => 'image',
			'label'       => esc_html__( 'Height', 'bricks' ),
			'type'        => 'number',
			'units'       => true,
			'css'         => [
				[
					'selector' => '.image',
					'property' => 'height',
				],
			],
			'placeholder' => 60,
			'required'    => [ 'image', '!=', '' ],
		];

		$this->controls['imageWidth'] = [
			'tab'         => 'content',
			'group'       => 'image',
			'label'       => esc_html__( 'Width', 'bricks' ),
			'type'        => 'number',
			'units'       => true,
			'css'         => [
				[
					'selector' => '.image',
					'property' => 'width'
				],
			],
			'placeholder' => 60,
			'required'    => [ 'image', '!=', '' ],
		];

		$this->controls['imageBorder'] = [
			'tab'      => 'content',
			'group'    => 'image',
			'label'    => esc_html__( 'Border', 'bricks' ),
			'type'     => 'border',
			'css'      => [
				[
					'selector' => '.image',
					'property' => 'border',
				],
			],
			'required' => [ 'image', '!=', '' ],
		];
	}

	public function render() {
		$settings = $this->settings;
		$post_id  = $this->post_id;

		global $post;

		$post          = get_post( $post_id );
		$title_tag     = isset( $settings['titleTag'] ) ? $settings['titleTag'] : 'h5';
		$image_size    = isset( $settings['imageSize'] ) ? $settings['imageSize'] : 'thumbnail';
		$image_classes = [ 'image', 'css-filter' ];

		// Query terms
		$in_same_term   = isset( $settings['inSameTerm'] );
		$excluded_terms = isset( $settings['excludedTerms'] ) ? $settings['excludedTerms'] : [];
		$taxonomy       = $in_same_term && isset( $settings['taxonomy'] ) ? $settings['taxonomy'] : 'category';

		$excluded_term_ids = [];

		foreach ( $excluded_terms as $excluded_term ) {
			$excluded_term = explode( '::', $excluded_term );
			$taxonomy      = $excluded_term[0];
			$term_id       = $excluded_term[1];

			$excluded_term_ids[] = $term_id;
		}

		$prev_post = get_previous_post( $in_same_term, $excluded_term_ids, $taxonomy );
		$next_post = get_next_post( $in_same_term, $excluded_term_ids, $taxonomy );

		if ( ! $prev_post && ! $next_post ) {
			return $this->render_element_placeholder(
				[
					'title' => esc_html__( 'No previous or next posts found..', 'bricks' ),
				]
			);
		}

		$this->set_attribute( '_root', 'aria-label', esc_html__( 'Post navigation', 'bricks' ) );

		echo "<nav {$this->render_attributes( '_root' )}>";

		// Previous Post
		if ( $prev_post ) {
			echo '<a class="prev-post" href="' . get_the_permalink( $prev_post ) . '">';

			if ( isset( $settings['image'] ) && has_post_thumbnail( $prev_post ) ) {
				$prev_post_image_url = get_the_post_thumbnail_url( $prev_post->ID, $image_size );

				if ( $this->lazy_load() ) {
					$image_classes[] = 'bricks-lazy-hidden';
					$this->set_attribute( 'prev-image', 'data-style', "background-image:url($prev_post_image_url)" );
				} else {
					$this->set_attribute( 'prev-image', 'style', "background-image:url($prev_post_image_url)" );
				}

				$this->set_attribute( 'prev-image', 'class', $image_classes );

				echo "<div {$this->render_attributes( 'prev-image' )}></div>";
			}

			$prev_arrow = isset( $settings['prevArrow'] ) ? self::render_icon( $settings['prevArrow'] ) : false;

			if ( $prev_arrow ) {
				echo '<div class="swiper-button bricks-swiper-button-prev">' . $prev_arrow . '</div>';
			}

			if ( isset( $settings['title'] ) || isset( $settings['label'] ) ) {
				echo '<div class="content">';
				if ( isset( $settings['label'] ) ) {
					$prev_label = isset( $settings['prevLabel'] ) && ! empty( $settings['prevLabel'] ) ? $settings['prevLabel'] : esc_html__( 'Previous post', 'bricks' );

					echo "<span class=\"label\">$prev_label</span>";
				}

				if ( isset( $settings['title'] ) ) {
					echo '<' . esc_attr( $title_tag ) . ' class="title">' . get_the_title( $prev_post ) . '</' . esc_attr( $title_tag ) . '>';
				}
					echo '</div>';
			}

			echo '</a>';
		} else {
			// Needed to push next post to the right when no previous post exists & 'Max. post width' is set
			echo '<span class="prev-post hide"></span>';
		}

		// Next Post
		if ( $next_post ) {
			echo '<a class="next-post" href="' . get_the_permalink( $next_post ) . '">';

			if ( isset( $settings['title'] ) || isset( $settings['label'] ) ) {
				echo '<div class="content">';

				if ( isset( $settings['label'] ) ) {
					$prev_label = isset( $settings['nextLabel'] ) && ! empty( $settings['nextLabel'] ) ? $settings['nextLabel'] : esc_html__( 'Next post', 'bricks' );

					echo "<span class=\"label\">$prev_label</span>";
				}

				if ( isset( $settings['title'] ) ) {
					echo '<' . esc_attr( $title_tag ) . ' class="title">' . get_the_title( $next_post ) . '</' . esc_attr( $title_tag ) . '>';
				}

				echo '</div>';
			}

			$next_arrow = isset( $settings['nextArrow'] ) ? self::render_icon( $settings['nextArrow'] ) : false;

			if ( $next_arrow ) {
				echo '<div class="swiper-button bricks-swiper-button-next">' . $next_arrow . '</div>';
			}

			if ( isset( $settings['image'] ) && has_post_thumbnail( $next_post ) ) {
				$next_post_image_url = get_the_post_thumbnail_url( $next_post->ID, $image_size );

				if ( $this->lazy_load() ) {
					$image_classes[] = 'bricks-lazy-hidden';
					$this->set_attribute( 'next-image', 'data-style', "background-image:url($next_post_image_url)" );
				} else {
					$this->set_attribute( 'next-image', 'style', "background-image:url($next_post_image_url)" );
				}

				$this->set_attribute( 'next-image', 'class', $image_classes );

				echo "<div {$this->render_attributes( 'next-image' )}></div>";
			}

			echo '</a>';
		}

		echo '</nav>';
	}
}
