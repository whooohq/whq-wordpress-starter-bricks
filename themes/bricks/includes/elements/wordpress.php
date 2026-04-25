<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_WordPress extends Element {
	public $category     = 'wordpress';
	public $name         = 'wordpress';
	public $icon         = 'ti-wordpress';
	public $css_selector = 'ul';

	public function get_label() {
		return 'WordPress';
	}

	public function set_controls() {
		$this->controls['widgetSeparator'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Widget', 'bricks' ),
			'type'  => 'separator',
		];

		$this->controls['type'] = [
			'tab'       => 'content',
			'label'     => esc_html__( 'Widget', 'bricks' ),
			'type'      => 'select',
			'options'   => [
				'archives'   => esc_html__( 'Archives', 'bricks' ),
				'calendar'   => esc_html__( 'Calendar', 'bricks' ),
				'categories' => esc_html__( 'Categories', 'bricks' ),
				'pages'      => esc_html__( 'Pages', 'bricks' ),
				'comments'   => esc_html__( 'Recent comments', 'bricks' ),
				'posts'      => esc_html__( 'Recent posts', 'bricks' ),
				'tagCloud'   => esc_html__( 'Tag cloud', 'bricks' ),
				'taxonomy'   => esc_html__( 'Taxonomy', 'bricks' ),
			],
			'inline'    => true,
			'clearable' => false,
			'default'   => 'posts',
		];

		$this->controls['icon'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Icon', 'bricks' ),
			'type'     => 'icon',
			'default'  => [
				'icon'    => 'ion-ios-arrow-forward',
				'library' => 'ionicons',
			],
			'required' => [ 'type', '=', [ 'archives', 'categories', 'pages', 'comments', 'posts', 'taxonomy' ] ],
		];

		$this->controls['iconTypography'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Icon typography', 'bricks' ),
			'type'     => 'typography',
			'css'      => [
				[
					'property' => 'font',
					'selector' => '.bricks-widget-wrapper i',
				],
			],
			'required' => [ 'icon.icon', '!=', '' ],
		];

		// Archives - Categories - Tag cloud - Taxonomy
		$this->controls['showCount'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Show count', 'bricks' ),
			'type'     => 'checkbox',
			'required' => [ 'type', '=', [ 'archives', 'categories', 'tagCloud', 'taxonomy' ] ],
		];

		// Pages
		$this->controls['sortBy'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Sort by', 'bricks' ),
			'type'     => 'select',
			'options'  => [
				'post_title'    => esc_html__( 'Page title', 'bricks' ),
				'post_date'     => esc_html__( 'Page date', 'bricks' ),
				'post_modified' => esc_html__( 'Page modified', 'bricks' ),
				'menu_order'    => esc_html__( 'Page order', 'bricks' ),
				'ID'            => esc_html__( 'Page ID', 'bricks' ),
			],
			'inline'   => true,
			'required' => [ 'type', '=', [ 'pages' ] ],
		];

		$this->controls['include'] = [
			'tab'         => 'content',
			'type'        => 'select',
			'label'       => esc_html__( 'Include', 'bricks' ),
			'optionsAjax' => [
				'action'   => 'bricks_get_posts',
				'postType' => 'page',
			],
			'multiple'    => true,
			'searchable'  => true,
			'required'    => [ 'type', '=', [ 'pages' ] ],
		];

		$this->controls['exclude'] = [
			'tab'         => 'content',
			'type'        => 'select',
			'label'       => esc_html__( 'Exclude', 'bricks' ),
			'optionsAjax' => [
				'action'   => 'bricks_get_posts',
				'postType' => 'page',
			],
			'multiple'    => true,
			'searchable'  => true,
			'required'    => [ 'type', '=', [ 'pages' ] ],
		];

		// Recent comments
		$this->controls['commentsNumber'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Number of comments', 'bricks' ),
			'type'     => 'number',
			'required' => [ 'type', '=', [ 'comments' ] ],
		];

		// Recent posts
		$this->controls['postsNumber'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Number of posts', 'bricks' ),
			'type'     => 'number',
			'min'      => -1,
			'required' => [ 'type', '=', [ 'posts' ] ],
		];

		$this->controls['direction'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Direction', 'bricks' ),
			'type'        => 'direction',
			'css'         => [
				[
					'property' => 'flex-direction',
					'selector' => '&.posts a',
				],
			],
			'inline'      => true,
			'placeholder' => esc_html__( 'Vertical', 'bricks' ),
			'required'    => [ 'type', '=', [ 'posts' ] ],
		];

		$this->controls['postsDate'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Show date', 'bricks' ),
			'type'     => 'checkbox',
			'required' => [ 'type', '=', [ 'posts' ] ],
		];

		$this->controls['postsFeaturedImage'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Show featured image', 'bricks' ),
			'type'     => 'checkbox',
			'required' => [ 'type', '=', [ 'posts' ] ],
		];

		$this->controls['postsFeaturedImageSize'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Featured image sizes', 'bricks' ),
			'type'     => 'select',
			'options'  => $this->control_options['imageSizes'],
			'required' => [ 'type', '=', [ 'posts' ] ],
		];

		$this->controls['postsImageWidth'] = [
			'tab'            => 'content',
			'label'          => esc_html__( 'Featured image width', 'bricks' ),
			'type'           => 'text',
			'css'            => [
				[
					'property' => 'width',
					'selector' => 'img',
				]
			],
			'inline'         => true,
			'small'          => true,
			'hasDynamicData' => false,
			'placeholder'    => esc_html__( 'auto', 'bricks' ),
			'description'    => esc_html__( 'I.e.: 200px / 50% etc.', 'bricks' ),
			'required'       => [ 'type', '=', [ 'posts' ] ],
		];

		$this->controls['postsImageHeight'] = [
			'tab'            => 'content',
			'label'          => esc_html__( 'Featured image height', 'bricks' ),
			'type'           => 'text',
			'css'            => [
				[
					'property' => 'height',
					'selector' => 'img',
				]
			],
			'inline'         => true,
			'small'          => true,
			'hasDynamicData' => false,
			'placeholder'    => esc_html__( 'auto', 'bricks' ),
			'description'    => esc_html__( 'I.e.: 200px / 50% etc.', 'bricks' ),
			'required'       => [ 'type', '=', [ 'posts' ] ],
		];

		$this->controls['postsTitleTypography'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Post title typography', 'bricks' ),
			'type'     => 'typography',
			'css'      => [
				[
					'property' => 'font',
					'selector' => '.post-title',
				]
			],
			'required' => [ 'type', '=', [ 'posts' ] ],
		];

		$this->controls['postsMetaTypography'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Post meta typography', 'bricks' ),
			'type'     => 'typography',
			'css'      => [
				[
					'property' => 'font',
					'selector' => '.post-meta',
				]
			],
			'required' => [ 'type', '=', [ 'posts' ] ],
		];

		// Tag cloud - Taxonomy
		$this->controls['taxonomy'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Taxonomy', 'bricks' ),
			'type'        => 'select',
			'options'     => Setup::$control_options['taxonomies'],
			'inline'      => true,
			'placeholder' => esc_html__( 'Tags', 'bricks' ),
			'required'    => [ 'type', '=', [ 'tagCloud', 'taxonomy' ] ],
		];

		// Title

		$this->controls['titleSeparator'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Title', 'bricks' ),
			'type'  => 'separator',
		];

		$this->controls['title'] = [
			'tab'    => 'content',
			'label'  => esc_html__( 'Title', 'bricks' ),
			'type'   => 'text',
			'inline' => true,
		];

		$this->controls['titletag'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Title tag', 'bricks' ),
			'type'        => 'select',
			'options'     => [
				'hero' => esc_html__( 'Hero', 'bricks' ),
				'lead' => esc_html__( 'Lead', 'bricks' ),
				'p'    => 'p',
				'h1'   => 'h1',
				'h2'   => 'h2',
				'h3'   => 'h3',
				'h4'   => 'h4',
				'h5'   => 'h5',
				'h6'   => 'h6',
			],
			'inline'      => true,
			'placeholder' => esc_html__( 'Heading 3', 'bricks' ),
		];

		$this->controls['titleBorder'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Title border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.bricks-widget-title',
				],
			],
		];

		$this->controls['titleTypography'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Title typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.bricks-widget-title',
				],
			],
		];

		$this->controls['contentTypography'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Content typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => 'ul',
				],
			],
		];
	}

	public function render() {
		$settings = $this->settings;
		$icon     = ! empty( $settings['icon'] ) ? self::render_icon( $settings['icon'] ) : '';
		$type     = ! empty( $settings['type'] ) ? $settings['type'] : '';

		if ( $type ) {
			$this->set_attribute( '_root', 'class', esc_attr( $settings['type'] ) );
		}

		echo "<div {$this->render_attributes( '_root' )}>";

		echo '<div class="bricks-widget-wrapper">';

		if ( ! empty( $settings['title'] ) ) {
			$title_tag = isset( $settings['titletag'] ) ? $settings['titletag'] : 'h3';

			$title_classes = [ 'bricks-widget-title' ];

			if ( $title_tag === 'hero' || $title_tag === 'lead' ) {
				$title_classes[] = "bricks-type-$title_tag";

				$title_tag = 'div';
			}

			$this->set_attribute( 'title', 'class', $title_classes );

			echo "<{$title_tag} {$this->render_attributes( 'title' )}>{$settings['title']}</{$title_tag}>";
		}

		if ( $type !== 'calendar' && $type !== 'tagCloud' ) {
			echo '<ul>';
		}

		switch ( $type ) {
			case 'archives':
				wp_get_archives(
					[
						'type'            => 'monthly',
						'limit'           => '',
						'format'          => 'html',
						'before'          => $icon,
						'after'           => '',
						'show_post_count' => isset( $settings['showCount'] ) ? $settings['showCount'] : false,
						'order'           => 'DESC',
						'post_type'       => 'post',
					]
				);
				break;

			case 'calendar':
				get_calendar();
				break;

			case 'categories':
				$categories_data   = get_categories( [ 'exclude' => 1 ] ); // Exclude 'uncategorized'
				$categories_output = '';

				foreach ( $categories_data as $category ) {
					$categories_output .= '<li>';
					$categories_output .= '<a href="' . get_category_link( $category->term_id ) . '">';

					if ( $icon ) {
						$categories_output .= $icon;
					}

					$categories_output .= $category->name;

					$categories_output .= '</a>';

					if ( isset( $settings['showCount'] ) ) {
						$categories_output .= ' <span>(' . $category->count . ')</span>';
					}

					$categories_output .= '</li>';
				}

				echo $categories_output;
				break;

			case 'pages':
				wp_list_pages(
					[
						'title_li'    => '',
						'link_before' => $icon,
						'sort_column' => isset( $settings['sortBy'] ) ? $settings['sortBy'] : '',
						'include'     => isset( $settings['include'] ) ? join( ',', $settings['include'] ) : '',
						'exclude'     => isset( $settings['exclude'] ) ? join( ',', $settings['exclude'] ) : '',
					]
				);
				break;

			case 'comments':
				$comments = get_comments(
					[
						'number'      => isset( $settings['commentsNumber'] ) ? $settings['commentsNumber'] : 5,
						'status'      => 'approve',
						'post_status' => 'publish',
					]
				);

				$comments_output = '';

				foreach ( $comments as $comment ) {
					$comments_output .= sprintf(
						// translators: %1$s is the comment author, %2$s is the post title
						_x( '%1$s on %2$s', 'bricks' ),
						'<li class="recentcomments">' . $icon . '<span class="comment-author-link">' . get_comment_author_link( $comment ) . '</span>',
						'<a href="' . esc_url( get_comment_link( $comment ) ) . '">' . get_the_title( $comment->comment_post_ID ) . '</a></li>'
					);
				}

				echo $comments_output;
				break;

			case 'posts':
				$posts_query = new \WP_Query(
					[
						'posts_per_page'      => isset( $settings['postsNumber'] ) ? $settings['postsNumber'] : 5,
						'no_found_rows'       => true,
						'post_status'         => 'publish',
						'ignore_sticky_posts' => true,
						'post__not_in'        => [ $this->post_id ], // Exclude currently viewed post
					]
				);

				$posts_output = '';

				foreach ( $posts_query->posts as $index => $post ) {
					$this->set_attribute( "link-$index", 'href', get_the_permalink( $post->ID ) );

					$posts_output .= '<li>';
					$posts_output .= '<a ' . $this->render_attributes( "link-$index" ) . '>';

					if ( isset( $settings['postsFeaturedImage'] ) && has_post_thumbnail( $post ) ) {
						$posts_output .= get_the_post_thumbnail(
							$post,
							isset( $settings['postsFeaturedImageSize'] ) ? $settings['postsFeaturedImageSize'] : BRICKS_DEFAULT_IMAGE_SIZE,
							[ 'class' => 'css-filter' ]
						);
					}

					$posts_output .= '<div class="post-data">';

					if ( $icon ) {
						$posts_output .= $icon;
					}

					$posts_output .= '<div class="post-data-inner">';

					$posts_output .= '<div class="post-title">' . get_the_title( $post->ID ) . '</div>';

					if ( isset( $settings['postsDate'] ) ) {
						$posts_output .= ' <div class="post-meta">' . get_the_date( '', $post->ID ) . '</div>';
					}

					$posts_output .= '</div>';

					$posts_output .= '</div>';

					$posts_output .= '</a>';
					$posts_output .= '</li>';
				}

				echo $posts_output;
				break;

			case 'tagCloud':
				wp_tag_cloud(
					[
						'show_count' => isset( $settings['showCount'] ) ? $settings['showCount'] : false,
						'taxonomy'   => isset( $settings['taxonomy'] ) ? $settings['taxonomy'] : 'post_tag',
					]
				);
				break;

			case 'taxonomy':
				$terms = get_terms(
					[
						'showCount' => isset( $settings['showCount'] ) ? $settings['showCount'] : false,
						'taxonomy'  => isset( $settings['taxonomy'] ) ? $settings['taxonomy'] : 'post_tag',
					]
				);

				$taxonomy_output = '';

				foreach ( $terms as $term ) {
					$term_id = isset( $term->term_id ) ? $term->term_id : false;

					if ( ! $term_id ) {
						continue;
					}

					$taxonomy_output .= '<li>';

					if ( $icon ) {
						$taxonomy_output .= $icon;
					}

					$taxonomy_output .= '<a href="' . get_term_link( $term_id ) . '">' . $term->name . '</a>';

					if ( isset( $settings['showCount'] ) ) {
						$term_object      = get_term( $term_id, $term->taxonomy );
						$taxonomy_output .= ' <span class="term-count">(' . $term_object->count . ')</span>';
					}

					$taxonomy_output .= '</li>';
				}

				echo $taxonomy_output;
				break;

			default:
				return $this->render_element_placeholder(
					[
						'title' => esc_html__( 'No WordPress widget type selected.', 'bricks' ),
					]
				);
				break;
		}

		if ( $type !== 'calendar' && $type !== 'tagCloud' ) {
			echo '</ul>';
		}

		echo '</div>';
		echo '</div>';
	}
}
