<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Breadcrumbs extends Element {
	public $category = 'general';
	public $name     = 'breadcrumbs';
	public $icon     = 'ti-layout-menu-separated';

	public static $link_format           = '<a class="item" href="%s">%s</a>';
	public static $current_span_format   = '<span class="item" aria-current="page">%s</span>';
	public static $separator_span_format = '<span class="separator">%s</span>';

	public function get_label() {
		return esc_html__( 'Breadcrumbs', 'bricks' );
	}

	public function set_control_groups() {
		$this->control_groups['separator'] = [
			'title' => esc_html__( 'Separator', 'bricks' ),
		];

		$this->control_groups['item'] = [
			'title' => esc_html__( 'Item', 'bricks' ),
		];

		$this->control_groups['currentItem'] = [
			'title' => esc_html__( 'Current', 'bricks' ),
		];
	}

	public function set_controls() {
		$this->controls['homeLabel'] = [
			'label'       => esc_html__( 'Label', 'bricks' ) . ': ' . esc_html__( 'Home', 'bricks' ),
			'type'        => 'text',
			'inline'      => true,
			'placeholder' => esc_html__( 'Home', 'bricks' ),
		];

		$this->controls['gap'] = [
			'label' => esc_html__( 'Gap', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'css'   => [
				[
					'property' => 'gap',
				]
			],
		];

		// SEPERATOR

		$this->controls['separatorType'] = [
			'group'       => 'separator',
			'label'       => esc_html__( 'Separator', 'bricks' ),
			'type'        => 'select',
			'options'     => [
				'text' => esc_html__( 'Text', 'bricks' ),
				'icon' => esc_html__( 'Icon', 'bricks' ),
				'none' => esc_html__( 'None', 'bricks' ),
			],
			'inline'      => true,
			'placeholder' => esc_html__( 'Text', 'bricks' ),
		];

		$this->controls['separatorText'] = [
			'group'       => 'separator',
			'label'       => esc_html__( 'Separator', 'bricks' ),
			'type'        => 'text',
			'inline'      => true,
			'placeholder' => '/',
			'required'    => [ 'separatorType', '=', [ 'text', '' ] ],
		];

		$this->controls['separatorIcon'] = [
			'group'    => 'separator',
			'label'    => esc_html__( 'Icon', 'bricks' ),
			'type'     => 'icon',
			'required' => [ 'separatorType', '=', 'icon' ],
		];

		$this->controls['separatorColor'] = [
			'group' => 'separator',
			'label' => esc_html__( 'Color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'color',
					'selector' => '.separator',
				],
			],
		];

		$this->controls['separatorSize'] = [
			'group' => 'separator',
			'label' => esc_html__( 'Size', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'css'   => [
				[
					'property' => 'font-size',
					'selector' => '.separator',
				],
			],
		];

		// ITEM

		$this->controls['itemSep'] = [
			'group' => 'item',
			'label' => esc_html__( 'Item', 'bricks' ),
			'type'  => 'separator',
		];

		$this->controls['itemPadding'] = [
			'group' => 'item',
			'label' => esc_html__( 'Padding', 'bricks' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'padding',
					'selector' => '.item',
				],
			],
		];

		$this->controls['itemBackgroundColor'] = [
			'group' => 'item',
			'label' => esc_html__( 'Background color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.item',
				],
			],
		];

		$this->controls['itemBorder'] = [
			'group' => 'item',
			'label' => esc_html__( 'Border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.item',
				],
			],
		];

		$this->controls['itemTypography'] = [
			'group' => 'item',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.item',
				],
			],
		];

		// CURRENT ITEM

		$this->controls['currentItemPadding'] = [
			'group' => 'currentItem',
			'label' => esc_html__( 'Padding', 'bricks' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'padding',
					'selector' => '.item[aria-current="page"]',
				],
			],
		];

		$this->controls['currentItemBackgroundColor'] = [
			'group' => 'currentItem',
			'label' => esc_html__( 'Background color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.item[aria-current="page"]',
				],
			],
		];

		$this->controls['currentItemBorder'] = [
			'group' => 'currentItem',
			'label' => esc_html__( 'Border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.item[aria-current="page"]',
				],
			],
		];

		$this->controls['currentItemTypography'] = [
			'group' => 'currentItem',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.item[aria-current="page"]',
				],
			],
		];
	}

	public function render() {
		$this->set_attribute( '_root', 'aria-label', esc_html__( 'Breadcrumb', 'bricks' ) );

		echo "<nav {$this->render_attributes('_root')}>";
		echo $this->bricks_breadcrumbs();
		echo '</nav>';
	}

	/**
	 * Main function to generate the breadcrumbs
	 *
	 * @return string
	 *
	 * @since 1.8.1
	 */
	public function bricks_breadcrumbs() {
		$settings = $this->settings;

		// Get the current queried object
		$queried_obj = get_queried_object();

		// Prepare different formats to be used for sprintf
		$current_span_format   = self::$current_span_format;
		$separator_span_format = self::$separator_span_format;
		$link_format           = self::$link_format;

		$breadcrumb_items = [];

		// Single post or page
		if ( is_singular() ) {
			// Get the post type
			$post_type = get_post_type();

			// Get the post type label and URL
			$post_type_obj = get_post_type_object( $post_type );

			// Use plural if exists
			$post_type_label = '';

			if ( $post_type_obj ) {
				$post_type_label = $post_type_obj->labels->name ? $post_type_obj->label : $post_type_obj->labels->name;
			}

			// Post type is native WP post: Get the category
			if ( $post_type === 'post' ) {
				// Get the post categories
				$categories = get_the_category( $queried_obj->ID );

				// Get the first category
				$category = $categories[0];

				// Populate category parents
				if ( $category->parent ) {
					$parents          = self::populate_breadcrumbs_parent_links( $category->parent, 'category' );
					$breadcrumb_items = array_merge( $breadcrumb_items, $parents );
				}

				$breadcrumb_items[] = sprintf( $link_format, esc_url( get_category_link( $category->term_id ) ), esc_html( $category->name ) );

			} else {
				// Populate post type archive if post type has an archive
				$post_type_link = get_post_type_archive_link( $post_type );

				if ( $post_type_link ) {
					$breadcrumb_items[] = sprintf( $link_format, esc_url( $post_type_link ), esc_html( $post_type_label ) );
				}
			}

			$post = $queried_obj;

			// Populate parents
			if ( $post->post_parent ) {
				$parents          = self::populate_breadcrumbs_parent_links( $post->post_parent, 'single' );
				$breadcrumb_items = array_merge( $breadcrumb_items, $parents );
			}

			$breadcrumb_items[] = sprintf( $current_span_format, esc_html( $post->post_title ) );
		}

		// Home
		elseif ( is_home() ) {
			$posts_page_id = get_option( 'page_for_posts' );

			if ( $posts_page_id ) {
				$breadcrumb_items[] = sprintf( $current_span_format, get_the_title( $posts_page_id ) );
			}
		}

		// Category
		elseif ( is_category() ) {
			$category = $queried_obj;

			// Populate parents
			if ( $category->parent ) {
				$parents          = self::populate_breadcrumbs_parent_links( $category->parent, 'category' );
				$breadcrumb_items = array_merge( $breadcrumb_items, $parents );
			}

			$breadcrumb_items[] = sprintf( $current_span_format, esc_html( $category->name ) );
		}

		// Taxonomy
		elseif ( is_tax() ) {
			$taxonomy = $queried_obj;

			// Populate parents
			if ( $taxonomy->parent ) {
				$parents          = self::populate_breadcrumbs_parent_links( $taxonomy->parent, 'taxonomy' );
				$breadcrumb_items = array_merge( $breadcrumb_items, $parents );
			}

			$breadcrumb_items[] = sprintf( $current_span_format, esc_html( $taxonomy->name ) );
		}

		// Post type: Archive
		elseif ( is_post_type_archive() ) {
			$post_type = $queried_obj;

			if ( $post_type->labels && $post_type->label ) {
				// Use plural if exists
				$label              = $post_type->labels->name ? $post_type->labels->name : $post_type->label;
				$breadcrumb_items[] = sprintf( $current_span_format, esc_html( $label ) );
			}
		}

		// Author
		elseif ( is_author() ) {
			$author = $queried_obj;

			if ( $author->display_name ) {
				$breadcrumb_items[] = sprintf( $current_span_format, esc_html( $author->display_name ) );
			}
		}

		// Search
		elseif ( is_search() ) {
			$breadcrumb_items[] = sprintf( $current_span_format, esc_html__( 'Search results', 'bricks' ) );
		}

		// 404
		elseif ( is_404() ) {
			$breadcrumb_items[] = sprintf( $current_span_format, esc_html__( 'Not found', 'bricks' ) );
		}

		// Home link
		$home_link  = esc_url( home_url() );
		$home_label = ! empty( $settings['homeLabel'] ) ? $settings['homeLabel'] : esc_html__( 'Home', 'bricks' );
		$home_label = apply_filters( 'bricks/breadcrumbs/home_label', $home_label );

		// Separator
		$separator_type = ! empty( $settings['separatorType'] ) ? $settings['separatorType'] : 'text';
		$separator      = '/';

		switch ( $separator_type ) {
			case 'text':
				if ( ! empty( $settings['separatorText'] ) ) {
					$separator = $settings['separatorText'];
				}
				break;

			case 'icon':
				if ( ! empty( $settings['separatorIcon'] ) ) {
					$separator = self::render_icon( $settings['separatorIcon'] );
				}
				break;

			case 'none':
				$separator = '';
				break;
		}

		if ( $separator ) {
			$separator = sprintf( $separator_span_format, $separator );
		}

		$separator = apply_filters( 'bricks/breadcrumbs/separator', $separator );

		if ( is_front_page() ) {
			// Front page: Output only the home label
			$home_link        = sprintf( $current_span_format, $home_label );
			$breadcrumb_items = [ $home_link ];
		} else {
			// Not front page: Output the home link
			$home_link = sprintf( $link_format, $home_link, $home_label );

			// Move home link to the beginning of the breadcrumb items
			array_unshift( $breadcrumb_items, $home_link );
		}

		// Allow to filter breadcrumb items
		$breadcrumb_items = apply_filters( 'bricks/breadcrumbs/items', $breadcrumb_items );

		// Implode the breadcrumb items array
		$html = implode( $separator, $breadcrumb_items );

		return $html;
	}

	/**
	 * Populate breadcrumbs parent links
	 *
	 * @param int    $parent
	 * @param string $type
	 * @return array
	 *
	 * @since 1.8.1
	 */
	public function populate_breadcrumbs_parent_links( $parent, $type ) {
		if ( ! $parent ) {
			return [];
		}

		if ( ! in_array( $type, [ 'category', 'taxonomy', 'single' ] ) ) {
			return [];
		}

		$parents     = [];
		$loop_parent = $parent;
		$link_format = self::$link_format;

		while ( $loop_parent ) {
			$next_parent    = false;
			$invalid_object = false;

			switch ( $type ) {
				case 'single':
					// Get the parent
					$parent_object = get_post( $loop_parent );

					/**
					 * If the parent object is not a WP_Post object, skip it
					 * https://developer.wordpress.org/reference/functions/get_post/
					 *
					 * @since 1.8.2
					 */
					if ( ! is_a( $parent_object, 'WP_Post' ) ) {
						$invalid_object = true;
						break;
					}

					$next_parent        = $parent_object->post_parent;
					$parent_object_link = get_permalink( $parent_object->ID );
					$parent_object_name = $parent_object->post_title;
					break;

				case 'category':
					// Get the parent
					$parent_object = get_category( $loop_parent );

					/**
					 * If the parent object is not an object, skip it
					 * https://developer.wordpress.org/reference/functions/get_category/
					 *
					 * @since 1.8.2
					 */
					if ( ! is_object( $parent_object ) ) {
						$invalid_object = true;
						break;
					}

					$next_parent        = $parent_object->parent;
					$parent_object_link = get_category_link( $parent_object );
					$parent_object_name = $parent_object->name;
					break;

				case 'taxonomy':
					// Get the parent
					$parent_object = get_term( $loop_parent );

					/**
					 * If the parent object is not a WP_Term object, skip it
					 * https://developer.wordpress.org/reference/functions/get_term/
					 *
					 * @since 1.8.2
					 */
					if ( ! is_a( $parent_object, 'WP_Term' ) ) {
						$invalid_object = true;
						break;
					}

					$next_parent        = $parent_object->parent;
					$parent_object_link = get_term_link( $parent_object );
					$parent_object_name = $parent_object->name;
					break;
			}

			// Set the next parent
			$loop_parent = $next_parent ? $next_parent : false;

			if ( ! $invalid_object ) {
				// Skip unpublished post/page
				if ( $type === 'single' && $parent_object->post_status !== 'publish' ) {
					continue;
				}

				// Output the parent category link and separator
				$parents[] = sprintf( $link_format, esc_url( $parent_object_link ), esc_html( $parent_object_name ) );
			}
		}

		// Reverse the parents array
		$parents = array_reverse( $parents );

		return $parents;
	}
}
