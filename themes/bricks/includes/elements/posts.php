<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Posts extends Element {
	public $category     = 'wordpress';
	public $name         = 'posts';
	public $icon         = 'ti-layout-media-overlay';
	public $css_selector = '.bricks-layout-inner';
	public $scripts      = [ 'bricksIsotope' ];

	// @var array Arguments passed to WP_Query.
	public $query_vars = null;

	public function get_label() {
		return esc_html__( 'Posts', 'bricks' );
	}

	public function enqueue_scripts() {
		$layout = ! empty( $this->settings['layout'] ) ? $this->settings['layout'] : 'grid';

		// Load IsotopeJS
		if ( isset( $this->settings['filter'] ) || $layout === 'masonry' ) {
			wp_enqueue_script( 'bricks-isotope' );
			wp_enqueue_style( 'bricks-isotope' );
		}
	}

	public function set_control_groups() {
		$this->control_groups['layout'] = [
			'title' => esc_html__( 'Layout', 'bricks' ),
			'tab'   => 'content',
		];

		$this->control_groups['image'] = [
			'title' => esc_html__( 'Image', 'bricks' ),
			'tab'   => 'content',
		];

		$this->control_groups['fields'] = [
			'title' => esc_html__( 'Fields', 'bricks' ),
			'tab'   => 'content',
		];

		$this->control_groups['content'] = [
			'title' => esc_html__( 'Content', 'bricks' ),
			'tab'   => 'content',
		];

		$this->control_groups['overlay'] = [
			'title' => esc_html__( 'Overlay', 'bricks' ),
			'tab'   => 'content',
		];

		$this->control_groups['filter'] = [
			'title' => esc_html__( 'Filter', 'bricks' ),
			'tab'   => 'content',
		];

		$this->control_groups['pagination'] = [
			'title' => esc_html__( 'Pagination', 'bricks' ),
			'tab'   => 'content',
		];

	}

	public function set_controls() {
		$this->controls['_gradient']['css'][0]['selector'] = '.image';

		// QUERY

		$this->controls['query'] = [
			'tab'     => 'content',
			'label'   => esc_html__( 'Query', 'bricks' ),
			'type'    => 'query',
			'popup'   => true,
			'inline'  => true,
			'exclude' => [ 'objectType' ],
		];

		// LAYOUT

		$this->controls['layout'] = [
			'tab'         => 'content',
			'group'       => 'layout',
			'label'       => esc_html__( 'Layout', 'bricks' ),
			'type'        => 'select',
			'options'     => [
				'list'    => esc_html__( 'List', 'bricks' ),
				'grid'    => esc_html__( 'Grid', 'bricks' ),
				'masonry' => esc_html__( 'Masonry', 'bricks' ),
				'metro'   => esc_html__( 'Metro', 'bricks' ),
			],
			'placeholder' => esc_html__( 'Grid', 'bricks' ),
			'inline'      => true,
		];

		// @since 1.2.1 (replaces deprecated 'imagePosition' to set alignment for every breakpoint)
		$this->controls['direction'] = [
			'tab'      => 'content',
			'group'    => 'layout',
			'label'    => esc_html__( 'Direction', 'bricks' ) . ' (' . esc_html__( 'Item', 'bricks' ) . ')',
			'type'     => 'direction',
			'css'      => [
				[
					'property' => 'flex-direction',
					'selector' => '.bricks-layout-wrapper[data-layout=list] .bricks-layout-inner',
				],
			],
			'inline'   => true,
			'required' => [ 'layout', '=', 'list' ],
		];

		$this->controls['columns'] = [
			'tab'         => 'content',
			'group'       => 'layout',
			'label'       => esc_html__( 'Columns', 'bricks' ),
			'type'        => 'number',
			'min'         => 1,
			'css'         => [
				[
					'property' => '--columns',
					'selector' => '.bricks-layout-wrapper',
				],
			],
			'rerender'    => true,
			'placeholder' => 2,
			'required'    => [ 'layout', '!=', [ 'list', 'metro' ] ],
		];

		$this->controls['gutter'] = [
			'tab'      => 'content',
			'group'    => 'layout',
			'label'    => esc_html__( 'Spacing', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => '--gutter',
					'selector' => '.bricks-layout-wrapper',
				],
			],
			'rerender' => true,
			'default'  => '30px',
		];

		$this->controls['firstPostFullWidth'] = [
			'tab'      => 'content',
			'group'    => 'layout',
			'label'    => esc_html__( 'First post full width', 'bricks' ),
			'type'     => 'checkbox',
			'css'      => [
				// CSS Grid
				[
					'selector' => '[data-layout="grid"] .bricks-layout-item:first-child',
					'property' => 'grid-column',
					'value'    => '1 / -1',
				],

				// IsotopeJS ('filter' on)
				[
					'selector' => '[data-layout="grid"] .bricks-layout-item:first-child',
					'property' => 'width',
					'value'    => '100%',
				],
			],
			'rerender' => true,
			'required' => [ 'layout', '!=', [ 'list', 'masonry', 'metro' ] ],
		];

		// IMAGE

		$this->controls['imageDisable'] = [
			'tab'   => 'content',
			'group' => 'image',
			'label' => esc_html__( 'Disable image', 'bricks' ),
			'type'  => 'checkbox',
		];

		$this->controls['imageLink'] = [
			'tab'      => 'content',
			'group'    => 'image',
			'label'    => esc_html__( 'Link image', 'bricks' ),
			'type'     => 'checkbox',
			'required' => [ 'layout', '=', [ '', 'list', 'grid', 'masonry' ] ],
		];

		$this->controls['alternate'] = [
			'tab'      => 'content',
			'group'    => 'image',
			'label'    => esc_html__( 'Alternate images', 'bricks' ),
			'type'     => 'checkbox',
			'required' => [ 'layout', '=', 'list' ],
		];

		$this->controls['imagePosition'] = [
			'tab'         => 'content',
			'group'       => 'image',
			'label'       => esc_html__( 'Image position', 'bricks' ),
			'type'        => 'select',
			'options'     => [
				'right' => esc_html__( 'Right', 'bricks' ),
				'left'  => esc_html__( 'Left', 'bricks' ),
			],
			'inline'      => true,
			'placeholder' => esc_html__( 'Left', 'bricks' ),
			'required'    => [ 'layout', '=', 'list' ],
		];

		$this->controls['width'] = [
			'tab'      => 'content',
			'group'    => 'image',
			'label'    => esc_html__( 'Image width', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'flex-basis',
					'selector' => '.bricks-layout-wrapper[data-layout=list] img',
				],
				[
					'property' => 'max-width',
					'selector' => '.bricks-layout-wrapper[data-layout=grid] img',
				],
				[
					'property' => 'max-width',
					'selector' => '.bricks-layout-inner > a',
				],
				[
					'property' => 'max-width',
					'selector' => '.overlay-wrapper',
				],
			],
			'rerender' => true, // NOTE: Undocumented (causes AJAX call, so don't use on control 'slider' etc.)
			'required' => [ 'layout', '=', [ '', 'list', 'grid' ] ],
		];

		$this->controls['height'] = [
			'tab'      => 'content',
			'group'    => 'image',
			'label'    => esc_html__( 'Image height', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'height',
					'selector' => '.bricks-layout-wrapper[data-layout=list] img',
				],
				[
					'property' => 'height',
					'selector' => '.bricks-layout-wrapper[data-layout=grid] img',
				],
				[
					'property' => 'height',
					'selector' => '.overlay-wrapper',
				],
			],
			'rerender' => true, // NOTE: Undocumented (causes AJAX call, so don't use on control 'slider' etc.)
			'required' => [ 'layout', '=', [ '', 'list', 'grid' ] ],
		];

		$this->control_options['imageRatio']['custom'] = esc_html__( 'Custom', 'bricks' );

		$this->controls['imageRatio'] = [
			'tab'         => 'content',
			'group'       => 'image',
			'label'       => esc_html__( 'Image ratio', 'bricks' ) . ' (' . esc_html__( 'Grid', 'bricks' ) . ')',
			'type'        => 'select',
			'options'     => $this->control_options['imageRatio'],
			'inline'      => true,
			'placeholder' => esc_html__( 'None', 'bricks' ),
			'required'    => [ 'layout', '=', [ '', 'grid' ] ],
		];

		/**
		 * Custom aspect ratio (remove control from style tab)
		 *
		 * @since 1.9.7
		 */
		unset( $this->controls['_aspectRatio'] );
		$this->controls['_aspectRatio'] = [
			'tab'      => 'content',
			'group'    => 'image',
			'label'    => esc_html__( 'Aspect ratio', 'bricks' ) . ' (' . esc_html__( 'Grid', 'bricks' ) . ')',
			'type'     => 'text',
			'inline'   => true,
			'dd'       => false,
			'css'      => [
				[
					'selector' => '[data-layout="grid"] .image',
					'property' => 'aspect-ratio',
				],
			],
			'required' => [ 'imageRatio', '=', 'custom' ],
		];

		$this->controls['imageSize'] = [
			'tab'      => 'content',
			'group'    => 'image',
			'label'    => esc_html__( 'Image size', 'bricks' ),
			'type'     => 'select',
			'options'  => $this->control_options['imageSizes'],
			'required' => [ 'imageDisable', '=', '' ],
		];

		// FIELDS

		$this->controls = array_replace_recursive( $this->controls, $this->get_post_fields() );

		// CONTENT

		$this->controls = array_replace_recursive( $this->controls, $this->get_post_content() );

		// OVERLAY

		$this->controls = array_replace_recursive( $this->controls, $this->get_post_overlay() );

		// FILTER

		$this->controls['filter'] = [
			'tab'         => 'content',
			'group'       => 'filter',
			'type'        => 'select',
			'label'       => esc_html__( 'Taxonomy', 'bricks' ),
			'options'     => Setup::$control_options['taxonomies'],
			'placeholder' => esc_html__( 'None', 'bricks' ),
		];

		$this->controls['filterTextAlign'] = [
			'tab'         => 'content',
			'group'       => 'filter',
			'type'        => 'text-align',
			'label'       => esc_html__( 'Text align', 'bricks' ),
			'css'         => [
				[
					'property' => 'text-align',
					'selector' => '.bricks-isotope-filters',
				],
			],
			'inline'      => true,
			'placeholder' => esc_html__( 'Center', 'bricks' ),
			'required'    => [ 'filter', '!=', '' ],
		];

		$this->controls['filterBackground'] = [
			'tab'      => 'content',
			'group'    => 'filter',
			'type'     => 'color',
			'label'    => esc_html__( 'Background', 'bricks' ),
			'css'      => [
				[
					'property' => 'background-color',
					'selector' => '.bricks-isotope-filters li',
				],
			],
			'required' => [ 'filter', '!=', '' ],
		];

		$this->controls['filterBackgroundActive'] = [
			'tab'      => 'content',
			'group'    => 'filter',
			'type'     => 'color',
			'label'    => esc_html__( 'Background active', 'bricks' ),
			'css'      => [
				[
					'property' => 'background',
					'selector' => '.bricks-isotope-filters .active',
				],
			],
			'required' => [ 'filter', '!=', '' ],
		];

		$this->controls['filterBorder'] = [
			'tab'      => 'content',
			'group'    => 'filter',
			'type'     => 'border',
			'label'    => esc_html__( 'Border', 'bricks' ),
			'css'      => [
				[
					'property' => 'border',
					'selector' => '.bricks-isotope-filters li',
				],
			],
			'required' => [ 'filter', '!=', '' ],
		];

		$this->controls['filterTypography'] = [
			'tab'      => 'content',
			'group'    => 'filter',
			'type'     => 'typography',
			'label'    => esc_html__( 'Typography', 'bricks' ),
			'css'      => [
				[
					'property' => 'font',
					'selector' => '.bricks-isotope-filters li',
				],
			],
			'required' => [ 'filter', '!=', '' ],
		];

		$this->controls['filterTypographyActive'] = [
			'tab'      => 'content',
			'group'    => 'filter',
			'type'     => 'typography',
			'label'    => esc_html__( 'Typography active', 'bricks' ),
			'css'      => [
				[
					'property' => 'font',
					'selector' => '.bricks-isotope-filters .active',
				],
			],
			'required' => [ 'filter', '!=', '' ],
		];

		$this->controls['filterMargin'] = [
			'tab'         => 'content',
			'group'       => 'filter',
			'type'        => 'spacing',
			'label'       => esc_html__( 'Margin', 'bricks' ),
			'css'         => [
				[
					'property' => 'margin',
					'selector' => '.bricks-isotope-filters li',
				],
			],
			'placeholder' => [
				'top'    => 0,
				'right'  => 0,
				'bottom' => 30,
				'left'   => 0,
			],
			'required'    => [ 'filter', '!=', '' ],
		];

		$this->controls['filterPadding'] = [
			'tab'         => 'content',
			'group'       => 'filter',
			'type'        => 'spacing',
			'label'       => esc_html__( 'Padding', 'bricks' ),
			'css'         => [
				[
					'property' => 'padding',
					'selector' => '.bricks-isotope-filters li',
				],
			],
			'placeholder' => [
				'top'    => 0,
				'right'  => 20,
				'bottom' => 0,
				'left'   => 20,
			],
			'required'    => [ 'filter', '!=', '' ],
		];

		// NAVIGATION

		$this->controls['postsNavigation'] = [
			'tab'   => 'content',
			'group' => 'pagination',
			'type'  => 'checkbox',
			'label' => esc_html__( 'Show', 'bricks' ),
		];

		$this->controls['postsNavigationMargin'] = [
			'deprecated'  => true, // @since 1.5
			'tab'         => 'content',
			'group'       => 'pagination',
			'type'        => 'spacing',
			'label'       => esc_html__( 'Spacing', 'bricks' ),
			'css'         => [
				[
					'property' => 'margin',
					'selector' => '.bricks-pagination ul li',
				],
			],
			'placeholder' => [
				'top'    => 0,
				'right'  => 10,
				'bottom' => 0,
				'left'   => 10,
			],
			'required'    => [ 'postsNavigation', '!=', '' ],
		];

		$this->controls['postsNavigationTextAlign'] = [
			'deprecated'  => true, // @since 1.5
			'tab'         => 'content',
			'group'       => 'pagination',
			'type'        => 'text-align',
			'label'       => esc_html__( 'Text align', 'bricks' ),
			'css'         => [
				[
					'property' => 'text-align',
					'selector' => '.bricks-pagination',
				],
				[
					'property' => 'display',
					'selector' => '.bricks-pagination',
					'value'    => 'block',
				],
			],
			'inline'      => true,
			'placeholder' => esc_html__( 'Center', 'bricks' ),
			'exclude'     => [
				'justify',
			],
			'required'    => [ 'postsNavigation', '!=', '' ],
		];

		$this->controls['postsNavigationJustifyContent'] = [
			'tab'     => 'content',
			'group'   => 'pagination',
			'label'   => esc_html__( 'Alignment', 'bricks' ),
			'type'    => 'justify-content',
			'exclude' => 'space',
			'inline'  => true,
			'css'     => [
				[
					'selector' => '.bricks-pagination ul',
					'property' => 'justify-content',
				],
			],
		];

		$this->controls['postsNavigationHeight'] = [
			'tab'      => 'content',
			'group'    => 'pagination',
			'type'     => 'number',
			'units'    => true,
			'label'    => esc_html__( 'Height', 'bricks' ),
			'css'      => [
				[
					'property' => 'height',
					'selector' => '.bricks-pagination ul .page-numbers',
				],
			],
			'required' => [ 'postsNavigation', '!=', '' ],
		];

		$this->controls['postsNavigationWidth'] = [
			'tab'      => 'content',
			'group'    => 'pagination',
			'type'     => 'number',
			'units'    => true,
			'label'    => esc_html__( 'Width', 'bricks' ),
			'css'      => [
				[
					'property' => 'width',
					'selector' => '.bricks-pagination ul .page-numbers',
				],
			],
			'required' => [ 'postsNavigation', '!=', '' ],
		];

		$this->controls['postsNavigationGap'] = [
			'tab'         => 'content',
			'group'       => 'pagination',
			'label'       => esc_html__( 'Spacing', 'bricks' ),
			'type'        => 'number',
			'units'       => true,
			'css'         => [
				[
					'selector' => '.bricks-pagination ul',
					'property' => 'gap',
				],
			],
			'placeholder' => 20,
			'required'    => [ 'postsNavigation', '!=', '' ],
		];

		$this->controls['postsNavigationBackground'] = [
			'tab'      => 'content',
			'group'    => 'pagination',
			'type'     => 'color',
			'label'    => esc_html__( 'Background', 'bricks' ),
			'css'      => [
				[
					'property' => 'background',
					'selector' => '.bricks-pagination ul .page-numbers',
				],
			],
			'required' => [ 'postsNavigation', '!=', '' ],
		];

		$this->controls['postsNavigationBorder'] = [
			'tab'      => 'content',
			'group'    => 'pagination',
			'type'     => 'border',
			'label'    => esc_html__( 'Border', 'bricks' ),
			'css'      => [
				[
					'property' => 'border',
					'selector' => '.bricks-pagination ul .page-numbers',
				],
			],
			'required' => [ 'postsNavigation', '!=', '' ],
		];

		$this->controls['postsNavigationTypography'] = [
			'tab'      => 'content',
			'group'    => 'pagination',
			'type'     => 'typography',
			'label'    => esc_html__( 'Typography', 'bricks' ),
			'css'      => [
				[
					'property' => 'font',
					'selector' => '.bricks-pagination .page-numbers',
				],
			],
			'required' => [ 'postsNavigation', '!=', '' ],
		];

		// CURRENT PAGE

		$this->controls['postsNavigationActiveSeparator'] = [
			'tab'      => 'content',
			'group'    => 'pagination',
			'type'     => 'separator',
			'label'    => esc_html__( 'Current', 'bricks' ),
			'required' => [ 'postsNavigation', '!=', '' ],
		];

		$this->controls['postsNavigationBackgroundActive'] = [
			'tab'      => 'content',
			'group'    => 'pagination',
			'type'     => 'color',
			'label'    => esc_html__( 'Background', 'bricks' ),
			'css'      => [
				[
					'property' => 'background',
					'selector' => '.bricks-pagination ul .page-numbers.current',
				],
			],
			'required' => [ 'postsNavigation', '!=', '' ],
		];

		$this->controls['postsNavigationBorderActive'] = [
			'tab'      => 'content',
			'group'    => 'pagination',
			'type'     => 'border',
			'label'    => esc_html__( 'Border', 'bricks' ),
			'css'      => [
				[
					'property' => 'border',
					'selector' => '.bricks-pagination ul .page-numbers.current',
				],
			],
			'required' => [ 'postsNavigation', '!=', '' ],
		];

		$this->controls['postsNavigationTypographyActive'] = [
			'tab'      => 'content',
			'group'    => 'pagination',
			'type'     => 'typography',
			'label'    => esc_html__( 'Typography', 'bricks' ),
			'css'      => [
				[
					'property' => 'font',
					'selector' => '.bricks-pagination ul .page-numbers.current',
				],
			],
			'required' => [ 'postsNavigation', '!=', '' ],
		];
	}

	public function render() {
		$settings    = $this->settings;
		$is_frontend = $this->is_frontend;
		$layout      = ! empty( $settings['layout'] ) ? $settings['layout'] : 'grid';
		$filter      = ! empty( $settings['filter'] ) ? $settings['filter'] : false;
		$columns     = ! empty( $settings['columns'] ) ? $settings['columns'] : 2;
		$use_isotope = $layout === 'masonry' || $filter;

		/**
		 * Check if current request is a load more/infinite scroll request
		 *
		 * If so, do not render wrappers.
		 *
		 * 'query_result' API call is also considered a load more/infinite scroll request.
		 *
		 * @since 1.8.1
		 */
		$is_load_more_request = Api::is_current_endpoint( 'load_query_page' ) || Api::is_current_endpoint( 'query_result' );

		// Skip rendering wrappers if this is a load more request
		if ( ! $is_load_more_request ) {
			$wrapper_classes = [ 'bricks-layout-wrapper' ];

			// Load IsotopeJS
			if ( $use_isotope ) {
				$wrapper_classes[] = 'isotope';
			}

			if ( $layout === 'list' ) {
				if ( isset( $settings['alternate'] ) ) {
					$wrapper_classes[] = 'alternate';
				}

				if ( ! isset( $settings['imageDisable'] ) && ! empty( $settings['imagePosition'] ) ) {
					$wrapper_classes[] = "image-position-{$settings['imagePosition']}";
				}
			}

			$this->set_attribute( 'item-sizer', 'class', 'bricks-isotope-sizer' );

			$this->set_attribute( 'ul', 'class', $wrapper_classes );

			$this->set_attribute( 'ul', 'data-layout', $layout );
		}

		// Posts query
		add_filter( 'bricks/posts/query_vars', [ $this, 'maybe_set_preview_query' ], 10, 3 );

		$query = new Query(
			[
				'id'       => $this->id,
				'settings' => $settings,
			]
		);

		$posts_query = $query->query_result;

		remove_filter( 'bricks/posts/query_vars', [ $this, 'maybe_set_preview_query' ], 10, 3 );

		// No results: Empty by default (@since 1.4)
		if ( ! $posts_query->found_posts ) {
			echo $query->get_no_results_content();

			return;
		}

		$post_index = 0;

		while ( $posts_query->have_posts() ) {
			$posts_query->the_post();
			$post = get_post();

			// Include brxe-{element.id} class for AJAX calls if Pagination element AJAX enabled (@see #33nr345)
			$item_classes = [ 'bricks-layout-item', 'repeater-item', "brxe-{$this->id}" ];

			// Filter by category/post_tag
			if ( $filter ) {
				$post_terms = wp_get_post_terms( $post->ID, $filter );

				foreach ( $post_terms as $term ) {
					// Skip 'uncategorized' category
					if ( $term->slug !== 'uncategorized' ) {
						$item_classes[] = $term->slug;
					}
				}
			}

			$this->set_attribute( "item-$post_index", 'class', $item_classes );

			// Post wrapper
			$this->set_attribute( "post-wrapper-$post_index", 'class', 'bricks-layout-inner' );

			// Reset classes before next loop
			$item_classes = [];
			$post_index++;
		}

		wp_reset_postdata();

		// STEP: Render

		// Do not render the wrappers on infinite scroll request
		if ( ! $is_load_more_request ) {
			echo "<div {$this->render_attributes( '_root' )}>";

			if ( $filter ) {
				$terms = get_terms(
					[
						'taxonomy'   => $filter,
						'hide_empty' => false,
					]
				);

				echo '<ul id="bricks-isotope-filters-' . sanitize_html_class( $this->id ) . '" class="bricks-isotope-filters">';
				echo '<li class="active" data-filter="*">' . esc_html__( 'All', 'bricks' ) . '</li>';

				foreach ( $terms as $term ) {
					if ( $term->slug !== 'uncategorized' ) {
						echo '<li data-filter=".' . esc_attr( $term->slug ) . '">' . esc_attr( $term->name ) . '</li>';
					}
				}

				echo '</ul>';
			}

			echo "<ul {$this->render_attributes( 'ul' )}>";
		}

		// Figure image wrapper classes
		$image_wrapper_classes = [ 'image-wrapper' ];

		$image_atts = [ 'class' => 'image css-filter' ];

		if ( $layout === 'grid' && ! empty( $settings['imageRatio'] ) ) {
			$image_atts['class'] .= " bricks-aspect-{$settings['imageRatio']}";
		}

		if ( $this->lazy_load() ) {
			$image_atts['class'] .= ' bricks-lazy-hidden';
			$image_atts['class'] .= ' bricks-lazy-load-isotope';
		}

		$post_index = 0;

		while ( $posts_query->have_posts() ) {
			$posts_query->the_post();

			$post = get_post();

			// Overlay wrapper
			$overlay_wrapper_html = '';

			if ( isset( $settings['fields'] ) && is_array( $settings['fields'] ) ) {
				$overlay_fields = [];

				foreach ( $settings['fields'] as $field ) {
					if ( isset( $field['overlay'] ) || $layout === 'metro' ) {
						$overlay_fields[] = $field;
					}
				}

				if ( count( $overlay_fields ) ) {
					$this->set_attribute(
						"overlay-wrapper-$post_index",
						'class',
						[
							'overlay-wrapper',
							isset( $settings['overlayAlign'] ) ? $settings['overlayAlign'] : '',
							isset( $settings['overlayOnHover'] ) ? 'show-on-hover' : '',
							isset( $settings['overlayAnimation'] ) ? $settings['overlayAnimation'] : '',
						]
					);

					$overlay_wrapper_html .= "<div {$this->render_attributes( "overlay-wrapper-$post_index" )}>";
					$overlay_wrapper_html .= '<div class="overlay-inner">';
					$overlay_wrapper_html .= Frontend::get_content_wrapper( $settings, $overlay_fields, $post );
					$overlay_wrapper_html .= '</div>';
					$overlay_wrapper_html .= '</div>';
				}
			}

			echo "<li {$this->render_attributes( "item-$post_index" )}>";

			echo "<div {$this->render_attributes( "post-wrapper-$post_index" )}>";

			// Image
			$disable_image = isset( $settings['imageDisable'] );
			$has_image     = ! $disable_image && has_post_thumbnail( $post->ID );

			// Render overlay_wrapper_html (for layout 'metro' & 'list' even if there is no featured image)
			if ( $has_image || ( $layout === 'metro' || $layout === 'list' ) ) {
				// Render figure > img
				$this->set_attribute( "image-wrapper-$post_index", 'class', $image_wrapper_classes );

				$direction = $settings['direction'] ?? 'column';

				// Render .image-wrapper <figure>: Direction is 'row' OR 'column' && post has featured image
				$render_image_wrapper = $layout !== 'list' || ( $layout === 'list' && ! $disable_image && ( strpos( $direction, 'row' ) !== false || ( $direction === 'column' && $has_image ) ) );

				if ( $render_image_wrapper ) {
					echo "<figure {$this->render_attributes( "image-wrapper-$post_index" )}>";
				}

				// Link image
				$overlay_has_links = strpos( $overlay_wrapper_html, '<a ' ) !== false;

				if ( isset( $settings['imageLink'] ) && ! $overlay_has_links ) {
					echo '<a href="' . get_the_permalink( $post->ID ) . '">';
				}

				if ( $has_image ) {
					$image_size = $settings['imageSize'] ?? BRICKS_DEFAULT_IMAGE_SIZE;

					echo wp_get_attachment_image( get_post_thumbnail_id( $post->ID ), $image_size, false, $image_atts );
				}

				echo $overlay_wrapper_html;

				if ( isset( $settings['imageLink'] ) && ! $overlay_has_links ) {
					echo '</a>';
				}

				if ( $render_image_wrapper ) {
					echo '</figure>';
				}
			}

			// Content
			if ( isset( $settings['fields'] ) && is_array( $settings['fields'] ) ) {
				$content_fields = [];

				foreach ( $settings['fields'] as $field ) {
					if ( ! isset( $field['overlay'] ) && $layout !== 'metro' ) {
						$content_fields[] = $field;
					}
				}

				if ( count( $content_fields ) ) {
					$this->set_attribute(
						"content-wrapper-$post_index",
						'class',
						[
							'content-wrapper',
							isset( $settings['contentAlign'] ) ? $settings['contentAlign'] : '',
						]
					);

					echo "<div {$this->render_attributes( "content-wrapper-$post_index" )}>";
					echo Frontend::get_content_wrapper( $settings, $content_fields, $post );
					echo '</div>';
				}
			}

			echo '</div>';
			echo '</li>';

			$post_index++;
		}

		wp_reset_postdata();

		// Add infinite scroll information to isotope sizer
		$this->render_query_loop_trail( $posts_query, 'item-sizer' );

		// Skip rendering wrappers if this is a load more request
		if ( ! $is_load_more_request ) {
			// 'item-sizer' used to add infinite scroll attributes
			echo "<li {$this->render_attributes( 'item-sizer' )}></li>";

			if ( $use_isotope ) {
				echo '<li class="bricks-gutter-sizer"></li>';
			}

			echo '</ul>';

			if ( isset( $settings['postsNavigation'] ) ) {
				$current_page = isset( $posts_query->query_vars['paged'] ) ? $posts_query->query_vars['paged'] : 1;
				$total_pages  = $posts_query->max_num_pages;

				echo Helpers::posts_navigation( $current_page, $total_pages );
			}

			echo '</div>';
		}
	}
}
