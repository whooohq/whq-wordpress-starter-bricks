<?php
namespace Bricks\Integrations\Dynamic_Data\Providers;

use Bricks\Woocommerce;
use Bricks\Query;
use Bricks\Helpers;
use Bricks\Database;
use Bricks\Api;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Provider_Wp extends Base {
	public function register_tags() {
		$tags = $this->get_tags_config();

		foreach ( $tags as $key => $tag ) {
			$this->tags[ $key ] = [
				'name'     => '{' . $key . '}',
				'label'    => $tag['label'],
				'group'    => $tag['group'],
				'provider' => $this->name,
			];

			if ( ! empty( $tag['deprecated'] ) ) {
				$this->tags[ $key ]['deprecated'] = $tag['deprecated'];
			}

			if ( ! empty( $tag['render'] ) ) {
				$this->tags[ $key ]['render'] = $tag['render'];
			}
		}
	}

	public function get_tags_config() {
		$tags = [
			// Post
			'post_title'                 => [
				'label' => esc_html__( 'Post title', 'bricks' ),
				'group' => 'post'
			],

			'post_id'                    => [
				'label' => esc_html__( 'Post ID', 'bricks' ),
				'group' => 'post'
			],

			'post_url'                   => [
				'label' => esc_html__( 'Post link', 'bricks' ),
				'group' => 'post'
			],

			'post_date'                  => [
				'label' => esc_html__( 'Post date', 'bricks' ),
				'group' => 'post',
			],

			'post_modified'              => [
				'label' => esc_html__( 'Post modified date', 'bricks' ),
				'group' => 'post',
			],

			'post_time'                  => [
				'label' => esc_html__( 'Post time', 'bricks' ),
				'group' => 'post',
			],

			'post_comments_count'        => [
				'label' => esc_html__( 'Post comments count', 'bricks' ),
				'group' => 'post'
			],

			'post_comments'              => [
				'label' => esc_html__( 'Post comments', 'bricks' ),
				'group' => 'post'
			],

			'post_content'               => [
				'label' => esc_html__( 'Post content', 'bricks' ),
				'group' => 'post'
			],

			'post_excerpt'               => [
				'label' => esc_html__( 'Post excerpt', 'bricks' ),
				'group' => 'post'
			],

			'read_more'                  => [
				'label' => esc_html__( 'Read more', 'bricks' ),
				'group' => 'post',
			],

			// Image
			'featured_image'             => [
				'label' => esc_html__( 'Featured image', 'bricks' ),
				'group' => 'post',
			],

			'featured_image_tag'         => [
				'label'      => esc_html__( 'Featured image tag', 'bricks' ),
				'group'      => 'post',
				'deprecated' => 1
			],

			// Author
			'author_name'                => [
				'label' => esc_html__( 'Author name', 'bricks' ),
				'group' => 'author',
			],

			'author_bio'                 => [
				'label' => esc_html__( 'Author bio', 'bricks' ),
				'group' => 'author',
			],

			'author_email'               => [
				'label' => esc_html__( 'Author email', 'bricks' ),
				'group' => 'author',
			],

			'author_website'             => [
				'label' => esc_html__( 'Author website', 'bricks' ),
				'group' => 'author',
			],

			'author_archive_url'         => [
				'label' => esc_html__( 'Author archive URL', 'bricks' ),
				'group' => 'author',
			],

			'author_avatar'              => [
				'label' => esc_html__( 'Author avatar', 'bricks' ),
				'group' => 'author',
			],

			'author_meta'                => [
				'label' => esc_html__( 'Author meta - add key after :', 'bricks' ),
				'group' => 'author',
			],

			// Site
			'site_title'                 => [
				'label' => esc_html__( 'Site title', 'bricks' ),
				'group' => 'site',
			],

			'site_tagline'               => [
				'label' => esc_html__( 'Site tagline', 'bricks' ),
				'group' => 'site',
			],

			'site_url'                   => [
				'label' => esc_html__( 'Site URL', 'bricks' ),
				'group' => 'site',
			],

			'site_login'                 => [
				'label' => esc_html__( 'Login URL', 'bricks' ),
				'group' => 'site',
			],

			'site_logout'                => [
				'label' => esc_html__( 'Logout URL', 'bricks' ),
				'group' => 'site',
			],

			'url_parameter'              => [
				'label' => esc_html__( 'URL parameter - add key after :', 'bricks' ),
				'group' => 'site',
			],

			// Archive
			'archive_title'              => [
				'label' => esc_html__( 'Archive title', 'bricks' ),
				'group' => 'archive',
			],

			'archive_description'        => [
				'label' => esc_html__( 'Archive description', 'bricks' ),
				'group' => 'archive',
			],

			// Terms
			'term_id'                    => [
				'label'  => esc_html__( 'Term id', 'bricks' ),
				'group'  => 'terms',
				'render' => 'terms',
			],

			'term_name'                  => [
				'label'  => esc_html__( 'Term name', 'bricks' ),
				'group'  => 'terms',
				'render' => 'terms',
			],

			'term_slug'                  => [
				'label'  => esc_html__( 'Term slug', 'bricks' ),
				'group'  => 'terms',
				'render' => 'terms',
			],

			'term_count'                 => [
				'label'  => esc_html__( 'Term count', 'bricks' ),
				'group'  => 'terms',
				'render' => 'terms',
			],

			'term_url'                   => [
				'label'  => esc_html__( 'Term archive URL', 'bricks' ),
				'group'  => 'terms',
				'render' => 'terms',
			],

			'term_description'           => [
				'label'  => esc_html__( 'Term description', 'bricks' ),
				'group'  => 'terms',
				'render' => 'terms',
			],

			'term_meta'                  => [
				'label'  => esc_html__( 'Term meta - add key after :', 'bricks' ),
				'group'  => 'terms',
				'render' => 'terms',
			],

			// Date
			'current_date'               => [
				'label' => esc_html__( 'Current date', 'bricks' ) . ' (UTC)',
				'group' => 'date',
			],

			'current_wp_date'            => [
				'label' => esc_html__( 'Current date', 'bricks' ) . ' (WordPress)',
				'group' => 'date',
			],

			// Query
			'query_results_count'        => [
				'label' => esc_html__( 'Query results count', 'bricks' ),
				'group' => 'query',
			],

			'query_results_count_filter' => [
				'label' => esc_html__( 'Query results count', 'bricks' ) . ' (' . esc_html__( 'Filter', 'bricks' ) . ')',
				'group' => 'query',
			],

			/**
			 * Misc
			 *
			 * Live search results, etc.
			 *
			 * @since 1.9.6
			 */
			'search_term'                => [
				'label' => esc_html__( 'Search term', 'bricks' ),
				'group' => 'misc',
			],

			'search_term_filter'         => [
				'label' => esc_html__( 'Search term', 'bricks' ) . ' (' . esc_html__( 'Filter', 'bricks' ) . ')',
				'group' => 'misc',
			],
		];

		// User Profile fields
		$user_fields = [
			'id'           => esc_html__( 'User ID', 'bricks' ),
			'login'        => esc_html__( 'Username', 'bricks' ),
			'email'        => esc_html__( 'Email', 'bricks' ),
			'url'          => esc_html__( 'Website', 'bricks' ),
			'nicename'     => esc_html__( 'Nicename', 'bricks' ),
			'nickname'     => esc_html__( 'Nickname', 'bricks' ),
			'description'  => esc_html__( 'Bio', 'bricks' ),
			'first_name'   => esc_html__( 'First name', 'bricks' ),
			'last_name'    => esc_html__( 'Last name', 'bricks' ),
			'display_name' => esc_html__( 'Display name', 'bricks' ),
			'picture'      => esc_html__( 'Profile picture', 'bricks' ),
			'meta'         => esc_html__( 'User meta - add key after :', 'bricks' ),
		];

		foreach ( $user_fields as $key => $label ) {
			$tags[ 'wp_user_' . $key ] = [
				'label' => $label ,
				'group' => 'userProfile'
			];
		}

		// Add taxonomies related tags
		$taxs = get_taxonomies(
			[
				// 'public'  => true, (commented out @since 1.5, see: #38kj7az)
				'show_ui' => true,
			],
			'objects'
		);

		foreach ( $taxs as $tax ) {
			if ( in_array( $tax->name, [ BRICKS_DB_TEMPLATE_TAX_TAG, BRICKS_DB_TEMPLATE_TAX_BUNDLE ] ) ) {
				continue;
			}

			$tags[ 'post_terms_' . $tax->name ] = [
				'label'  => $tax->label,
				'group'  => 'terms',
				'render' => 'post_terms',
			];
		}

		// Echo
		if ( Helpers::code_execution_enabled() && ( ! bricks_is_builder() || ( bricks_is_builder() && \Bricks\Capabilities::current_user_can_execute_code() ) ) ) {
			$tags['echo'] = [
				'label' => esc_html__( 'Output PHP function', 'bricks' ),
				'group' => 'advanced',
			];
		}

		/**
		 * Do action
		 *
		 * @see https://academy.bricksbuilder.io/article/dynamic-data/#advanced
		 *
		 * @since 1.7
		 */
		$tags['do_action'] = [
			'label' => 'do_action',
			'group' => 'advanced',
		];

		// WordPress default custom fields
		$metas = $this->get_post_meta_keys();

		foreach ( $metas as $key ) {
			$label                = ucwords( str_replace( '_', ' ', $key ) );
			$tags[ 'cf_' . $key ] = [
				'label'  => $label,
				'group'  => 'customFields',
				'render' => 'post_metas',
			];
		}

		return $tags;
	}

	/**
	 * Returns a list of post meta keys (uses $post context)
	 *
	 * @return array
	 */
	public function get_post_meta_keys() {
		$list = [];

		/**
		 * Return: Is frontend
		 *
		 * Only retrieve WP custom fields (cf_) inside the builder.
		 * As get_site_meta_keys() can cause performance issues on large sites.
		 *
		 * @see #862k3f2md
		 * @since 1.8.3
		 */
		if ( ! bricks_is_builder() ) {
			return $list;
		}

		// Builder: Return empty array if user doesn't want to retrieve default WP custom fields
		if ( Database::get_setting( 'builderDisableWpCustomFields', false ) ) {
			return $list;
		}

		$meta_keys = $this->get_site_meta_keys();

		if ( empty( $meta_keys ) ) {
			return $list;
		}

		$exclude = [];

		// Exclude the ACF custom fields from the custom fields list
		if ( Provider_Acf::load_me() ) {
			$patterns = [];

			$acf_fields = Provider_Acf::get_fields();

			foreach ( $acf_fields as $field ) {
				$exclude[] = $field['name'];

				// Note: for the sake of simplification the nested repeaters are excluded based on the parent prefix
				if ( $field['type'] == 'repeater' && ! empty( $field['sub_fields'] ) ) {
					foreach ( $field['sub_fields'] as $sub_field ) {
						$patterns[] = "/{$field['name']}_(\d+)_{$sub_field['name']}(.?)/";
					}
				}

				// ACF Flexible content: Follows repeater logic (@since 1.6.2)
				if ( $field['type'] == 'flexible_content' && ! empty( $field['layouts'] ) ) {
					foreach ( $field['layouts'] as $layout ) {
						if ( ! empty( $layout['sub_fields'] ) ) {
							foreach ( $layout['sub_fields'] as $sub_field ) {
								$patterns[] = "/{$field['name']}_(\d+)_{$sub_field['name']}(.?)/";
							}
						}
					}
				}
			}

			if ( ! empty( $patterns ) ) {
				foreach ( $patterns as $pattern ) {
					// Excludes meta keys based on the patterns
					$meta_keys = preg_grep( $pattern, $meta_keys, PREG_GREP_INVERT );
				}
			}

			unset( $patterns );
		}

		// Exclude the Pods custom fields from the custom fields list
		if ( Provider_Pods::load_me() ) {
			$pods_fields = Provider_Pods::get_fields();

			foreach ( $pods_fields as $field ) {
				$exclude[] = $field['name'];
			}
		}

		// Exclude the Meta Box custom fields from the custom fields list
		if ( Provider_Metabox::load_me() ) {
			$metabox_fields = Provider_Metabox::get_fields();

			foreach ( $metabox_fields as $field ) {
				$exclude[] = $field['id'];

				if ( $field['type'] == 'group' && ! empty( $field['fields'] ) ) {
					foreach ( $field['fields'] as $sub_field ) {
						$exclude[] = "{$field['id']}_{$sub_field['id']}";
					}
				}
			}
		}

		// Exclude the CMB2 custom fields from the custom fields list
		if ( Provider_Cmb2::load_me() ) {
			$cmb2_fields = Provider_Cmb2::get_fields();

			foreach ( $cmb2_fields as $field ) {
				$exclude[] = $field['name'];
			}
		}

		// Exclude the Toolset custom fields from the custom fields list
		if ( Provider_Toolset::load_me() ) {
			$toolset_fields = Provider_Toolset::get_fields();

			foreach ( $toolset_fields as $field ) {
				$exclude[] = $field['meta_key'];
			}
		}

		if ( Provider_Jetengine::load_me() ) {
			$jetengine_fields = Provider_Jetengine::get_fields();

			foreach ( $jetengine_fields as $field ) {
				$exclude[] = $field['name'];
			}
		}

		// ignore post meta keys that start with '_' (invisible)
		foreach ( $meta_keys as $key ) {
			if ( '_' !== substr( $key, 0, 1 ) && ! in_array( $key, $exclude ) ) {
				$list[] = $key;
			}
		}

		return $list;
	}

	/**
	 * Main function to render the tag value for WordPress provider
	 *
	 * @param [type] $tag
	 * @param [type] $post
	 * @param [type] $args
	 * @param [type] $context
	 */
	public function get_tag_value( $tag, $post, $args, $context ) {
		$post_id = isset( $post->ID ) ? $post->ID : '';

		// STEP: Check for filter args
		$filters = $this->get_filters_from_args( $args );

		// STEP: Get the value
		$value = '';

		$render = isset( $this->tags[ $tag ]['render'] ) ? $this->tags[ $tag ]['render'] : $tag;

		$uri = ! empty( $_SERVER['REQUEST_URI'] ) ? parse_url( $_SERVER['REQUEST_URI'] ) : '';
		if ( is_array( $uri ) && ! empty( $uri ) ) {
			foreach ( $uri as $key => $val ) {
				if ( $val && strpos( $val, ':' ) !== false ) {
					unset( $filters['meta_key'] );
				}
			}
		}

		switch ( $render ) {
			// Post
			case 'post_id':
				$value = $post_id;
				break;

			case 'post_url':
				$value = get_permalink( $post_id );
				break;

			case 'post_title':
				$value = get_the_title( $post_id );
				break;

			case 'read_more':
				$value           = apply_filters( 'bricks/dynamic_data/read_more', __( 'Read more', 'bricks' ), $post );
				$filters['link'] = true;
				break;

			case 'post_date':
				$filters['object_type'] = 'date';
				$value                  = get_post_time( 'U', false, $post, false );
				break;

			case 'post_modified':
				$filters['object_type'] = 'date';
				$value                  = get_post_modified_time( 'U', false, $post, false );
				break;

			case 'post_time':
				$filters['object_type'] = 'date';
				$filters['meta_key']    = isset( $filters['meta_key'] ) ? $filters['meta_key'] : get_option( 'time_format' );
				$value                  = get_post_time( 'U', false, $post, false );
				break;

			case 'post_comments_count':
				$value = get_comments_number( $post );
				break;

			case 'post_comments':
				$comments_number = get_comments_number( $post );
				// translators: %s = the number of comments
				$value = sprintf( _nx( '%s comment', '%s comments', $comments_number, 'Translators: %s = the number of comments', 'bricks' ), $comments_number );
				break;

			case 'post_content':
				wp_enqueue_style( 'wp-block-library' );
				wp_enqueue_style( 'global-styles' );

				$value = $this->get_the_content( $post );

				// To prevent issues with embeded content (e.g. youtube videos)
				$filters['skip_sanitize'] = true;
				break;

			case 'post_excerpt':
				$num_words = ! empty( $filters['num_words'] ) ? $filters['num_words'] : 55;
				$keep_html = isset( $filters['format'] );
				$value     = '';

				// To prevent the content from being trimmed again in format_value_for_text()
				$filters['trimmed'] = true;

				// Inside a Query Loop
				if ( Query::is_looping() ) {
					$loop_object = Query::get_loop_object();

					if ( ! is_a( $loop_object, 'WP_Post' ) ) {
						// Not looping a WP_Post
						if ( $loop_object && ! empty( $loop_object->description ) ) {
							$value = Helpers::trim_words( $loop_object->description, $num_words, null, $keep_html );
						}

						break;
					}
				}

				// Not inside a Query Loop: Use taxonomy or author description
				// "Posts" element on archive page triggered here as well (@see #862j3v95v)
				elseif ( is_archive() ) {
					$queried_object = get_queried_object();

					// Get current element name
					$element_id   = Query::get_query_element_id();
					$element_data = Helpers::get_element_data( $post_id, $element_id );
					$element_name = $element_data['element']['name'] ?? '';

					// Use archive description if we are not in a "Posts" element (@since 1.7)
					if ( $queried_object && ! empty( $queried_object->description ) && $element_name !== 'posts' ) {
						$value = Helpers::trim_words( $queried_object->description, $num_words, null, $keep_html );

						break;
					}

					// For Posts element on archive page, continue to next block to use the post excerpt instead
				}

				// We are in a Query Loop and looping a WP_Post or we are in a single post
				$value = Helpers::get_the_excerpt( $post, $num_words, null, $keep_html );
				break;

			// Image
			case 'featured_image':
			case 'featured_image_tag':
				$filters['object_type'] = 'media';
				$filters['image']       = 'true';
				$value                  = get_post_thumbnail_id( $post_id );

				if ( Woocommerce::is_woocommerce_active() ) {
					// Cart item featured image inside woo cart loop (@since 1.8.5)
					$loop_object_type = Query::is_looping() ? Query::get_query_object_type() : false;

					if ( $loop_object_type === 'wooCart' ) {
						// Get the loop cart object
						$loop_object = Query::get_loop_object();
						$product     = isset( $loop_object['data'] ) && is_a( $loop_object['data'], 'WC_Product' ) ? $loop_object['data'] : wc_get_product( $post_id );

						/**
						 * Similar like $product->get_image() method in WC_Product class
						 *
						 * Returns the parent product featured image if the current product has no featured image (e.g. variable product)
						 *
						 * @since 1.8.6
						 */
						if ( $product->get_image_id() ) {
							$value = $product->get_image_id();
						} elseif ( $product->get_parent_id() ) {
							$parent_product = wc_get_product( $product->get_parent_id() );
							if ( $parent_product ) {
								$value = $product->get_image_id();
							}
						}
					}

					/**
					 * Get WooCommerce placeholder image if featured image is empty (@since 1.5.1)
					 * Move to bottom so empty $value will be replaced by WooCommerce placeholder image (@since 1.8.6)
					 */
					if ( empty( $value ) && get_post_type( $post ) === 'product' ) {
						$value = get_option( 'woocommerce_placeholder_image', 0 );
					}
				}
				break;

			// Author
			case 'author_id':
			case 'author_name':
			case 'author_bio':
			case 'author_email':
			case 'author_website':
			case 'author_avatar':
			case 'author_meta':
			case 'author_archive_url':
				/**
				 * Get user_id
				 *
				 * Get the author of current post inside query loop or a singular post.
				 *
				 * @since 1.7.1
				 */
				$user_id = is_singular() || ( Query::is_looping() && Query::get_loop_object_type() === 'post' ) ? $post->post_author : $post_id;

				/**
				 * $post_id might be empty in author archive page (when post ID 1 is removed, author ID 1 will be empty $post_id)
				 *
				 * @see $post_id render_content() inside providers.php
				 *
				 * Get the author of the queried object if we are on an author archive page.
				 *
				 * @since 1.8.2
				 */
				if ( is_author() && ! Query::is_any_looping() ) {
					$user_id = get_queried_object_id();
				}

				/**
				 * Frontend: Preview template author & not looping: Get template preview author
				 *
				 * @since 1.8.2
				 */
				if ( Helpers::is_bricks_template( $post_id ) && ! Query::is_looping() ) {
					$template_preview_author = Helpers::get_template_setting( 'templatePreviewAuthor', $post_id );
					$user_id                 = $template_preview_author ? $template_preview_author : $user_id;
				}

				$user = get_user_by( 'id', $user_id );

				/**
				 * Builder OR AJAX call (infinite scroll)
				 *
				 * @since 1.9.1
				 */
				if ( ! $user && $user_id == $post_id ) {
					// Preview template & not looping: Get template preview author
					$template_preview_author = Helpers::get_template_setting( 'templatePreviewAuthor', $post_id );
					$user_id                 = $template_preview_author ? $template_preview_author : $user_id;

					if ( $template_preview_author ) {
						$user = get_user_by( 'id', $template_preview_author );
					}

					// Get user as is_singular() check above is not working ($wp_query is not populated)
					else {
						$user = $post ? get_user_by( 'id', $post->post_author ) : null;
					}
				}

				// Separate author tag value logic into separate function (@since 1.9.6)
				$value = $user ? $this->get_author_tag_value( $tag, $user, $filters, $context ) : '';

				break;

			// User Profile fields
			case 'wp_user_id':
			case 'wp_user_login':
			case 'wp_user_email':
			case 'wp_user_url':
			case 'wp_user_nicename':
			case 'wp_user_nickname':
			case 'wp_user_description':
			case 'wp_user_first_name':
			case 'wp_user_last_name':
			case 'wp_user_display_name':
			case 'wp_user_picture':
			case 'wp_user_meta':
				$user = Query::get_loop_object_type() == 'user' ? Query::get_loop_object() : wp_get_current_user();

				/**
				 * AJAX popup user context when using the user dynamic data,
				 * Should use the context user instead of the current user.
				 *
				 * @since 1.9.4
				 */
				if ( is_author() && ! Query::is_any_looping() && Api::is_current_endpoint( 'load_popup_content' ) ) {
					$user = get_user_by( 'id', get_queried_object_id() );
				}

				$value = $this->get_user_tag_value( $tag, $user, $filters, $context );
				break;

			// Site
			case 'site_title':
				$value = get_bloginfo( 'name', 'display' );
				break;

			case 'site_tagline':
				$value = get_bloginfo( 'description', 'display' );
				break;

			case 'site_url':
				$value = get_bloginfo( 'url', 'display' );
				break;

			case 'site_login':
				$redirect_to = '';

				// Redirect to post_id: {site_login:5}
				if ( ! empty( $filters['num_words'] ) ) {
					$redirect_to = get_the_permalink( $filters['num_words'] );
					// Prevent the content from being trimmed in format_value_for_text() @since 1.9.2
					$filters['trimmed'] = true;
				}

				$value = wp_login_url( $redirect_to );
				break;

			case 'site_logout':
				$redirect_to = '';

				// Redirect to post_id: {site_logout:5}
				if ( ! empty( $filters['num_words'] ) ) {
					$redirect_to = get_the_permalink( $filters['num_words'] );
					// Prevent the content from being trimmed in format_value_for_text() @since 1.9.2
					$filters['trimmed'] = true;
				}

				$value = wp_logout_url( $redirect_to );
				break;

			case 'url_parameter':
				$parameter = $filters['meta_key'] ?? false;
				$value     = $parameter && isset( $_GET[ $parameter ] ) ? $_GET[ $parameter ] : '';

				// Parse URL to get query parameters if not found in $_GET (@since 1.7.1)
				$url_components = isset( $_SERVER['REQUEST_URI'] ) ? parse_url( $_SERVER['REQUEST_URI'] ) : '';

				if ( ! $value && ! empty( $url_components['query'] ) ) {
					parse_str( $url_components['query'], $parameters );
					$value = isset( $parameters[ $parameter ] ) ? $parameters[ $parameter ] : '';
				}

				// Support array value (@since 1.9.2)
				if ( ! empty( $value ) ) {
					if ( is_array( $value ) ) {
						$value = array_map( 'esc_attr', $value );
						$value = implode( ',', $value );
					} else {
						$value = esc_attr( $value );
					}
				}
				break;

			case 'current_date':
				$filters['object_type'] = 'date';
				$value                  = time();
				break;

			case 'current_wp_date':
				$filters['object_type'] = 'date';
				$value                  = current_time( 'timestamp' );
				break;

			// Terms
			case 'terms':
				$value = $this->get_term_tag_value( $tag, $filters, $context, $post_id );

				if ( ! empty( $filters['link'] ) ) {
					$object_type = Query::get_loop_object_type();

					if ( $object_type == 'term' ) {
						$filters['object_type'] = $object_type;
						$filters['object']      = Query::get_loop_object();
					}
				}

				break;

			// Archive
			case 'archive_title':
				if ( empty( $filters['add_context'] ) ) {
					add_filter( 'get_the_archive_title_prefix', '__return_empty_string' );
				}

				// TODO: Not properly populated in the builder (@use Helpers::get_the_title() logic)
				$value = get_the_archive_title();

				if ( empty( $filters['add_context'] ) ) {
					remove_filter( 'get_the_archive_title_prefix', '__return_empty_string' );
				}
				break;

			case 'archive_description':
				// TODO: Not properly populated in the builder (@use Helpers::get_the_title() logic)
				$value = get_the_archive_description();
				break;

			case 'post_terms':
				$value = $this->get_post_terms_value( $tag, $post, $filters, $context );
				break;

			case 'post_metas':
				$meta_key = substr( $tag, 3 );

				$value = get_post_meta( $post_id, $meta_key, true );

				// NOTE: Undocumented
				$value = apply_filters( "bricks/dynamic_data/meta_value/$meta_key", $value, $post );

				break;

			case 'echo':
				$value = $this->get_echo_callback_value( $filters, $context, $post );
				// @since 1.8 - New array_value filter.
				if ( isset( $filters['array_value'] ) && is_array( $value ) ) {
					// Force context to text
					$context = 'text';
					$value   = $this->return_array_value( $value, $filters );
				}
				break;

			case 'do_action':
				// Render do_action only on the frontend to avoid HTML node parsing issues & easier to locate do_action in the builder
				if ( ! bricks_is_builder() && ! bricks_is_builder_call() ) {
					$filters['skip_sanitize'] = true;
					$value                    = $this->get_do_action_callback_value( $filters, $context, $post );
				}
				break;

			case 'query_results_count':
			case 'query_results_count_filter':
				// Get the results count from query_history, not supporting nested queries (@since 1.9.1)
				$query_object = false;
				$element_id   = ! empty( $filters['meta_key'] ) ? $filters['meta_key'] : false;
				// Element ID provided: Get query object from query history
				if ( $element_id ) {
					if ( bricks_is_builder() ) {
						// $value = '[' . esc_html__( 'View on frontend', 'bricks' ) . ']';
						break;
					} else {
						$query_object = Query::get_query_by_element_id( $element_id, true );
					}
				} else {
					// No element ID provided, get the current query object
					$query_object = Query::get_query_object( Query::is_any_looping() );
				}

				// No query object found. Init query (@since 1.9.1.1)
				if ( ! $query_object ) {
					$element_data = Helpers::get_element_data( $post_id, $element_id );
					$element_name = $element_data['element']['name'] ?? '';

					if ( ! empty( $element_name ) && isset( $element_data['element']['settings'] ) ) {
						// Populate query settings for elements that is not using standard query controls (@since 1.9.3)
						if ( in_array( $element_name, [ 'carousel', 'related-posts' ] ) ) {
							$query_settings = Helpers::populate_query_vars_for_element( $element_data['element'], $post_id );

							/**
							 * Override query settings.
							 * Carousel 'posts' type should returning empty from this function as it is using standard query controls
							 */
							if ( ! empty( $query_settings ) ) {
								$element_data['element']['settings']['query'] = $query_settings;
							}

							/**
							 * If this is a carousel 'posts' type, $query_settings should be empty as the query_settings is populated from standard query controls
							 * However, if the standard query controls is empty (user use default posts query), then we need to set 'query' key so or we are not able to init query in next step
							 */
							elseif ( $element_name === 'carousel' && empty( $query_settings ) ) {
								$carousel_type = $element_data['element']['settings']['type'] ?? 'posts';
								if ( $carousel_type === 'posts' && empty( $element_data['element']['settings']['query'] ) ) {
									$element_data['element']['settings']['query'] = [];
								}
							}
						}

						// Only init query if query settings is available
						if ( isset( $element_data['element']['settings']['query'] ) ) {
							$query_object = new Query( $element_data['element'] );
							if ( $query_object ) {
								$query_object->destroy();
							}
						}
					}
				}

				if ( is_a( $query_object, 'Bricks\Query' ) ) {
					$value = $query_object->count;
				} else {
					$value = 0;
				}

				/**
				 * {query_results_count_filter} - wrap the value with a span for AJAX update when using query filter feature
				 * element ID is a must so we know which count to update after AJAX
				 *
				 * @since 1.9.6
				 */
				if ( $tag === 'query_results_count_filter' && $element_id ) {
					$filters['skip_sanitize'] = true;
					$value                    = '<span data-brx-qr-count="' . $element_id . '">' . $value . '</span>';
				}

				break;

			// @since 1.9.6
			case 'search_term':
			case 'search_term_filter':
				// Get the search term from get_query_var() if not found in $_GET
				$search_term = isset( $_GET['s'] ) ? $_GET['s'] : get_query_var( 's' );
				$value       = sanitize_text_field( $search_term );

				$search_query_id = isset( $filters['meta_key'] ) ? $filters['meta_key'] : false;
				if ( $search_query_id ) {
					// Get the search term from query_history
					$search_query = Query::get_query_by_element_id( $search_query_id, true );
					if ( $search_query && ! empty( $search_query->query_vars['s'] ) ) {
						$value = sanitize_text_field( $search_query->query_vars['s'] );
					}

					// {search_term_filter} - wrap the value with a span for AJAX update when using query filter feature
					if ( $tag === 'search_term_filter' ) {
						$filters['skip_sanitize'] = true;
						$value                    = '<span data-brx-ls-term="' . $search_query_id . '">' . $value . '</span>';
					}
				}

				break;
		}

		// STEP: Apply context (text, link, image, media)
		$value = $this->format_value_for_context( $value, $tag, $post_id, $filters, $context );

		return $value;
	}

	/**
	 * Perform the do_action() callback and return the value
	 *
	 * Example: {do_action:woocommerce_before_single_product}
	 *
	 * NOTE: It's not supported to pass arguments to the action as different actions can have different arguments.
	 *
	 * @since 1.7
	 */
	public function get_do_action_callback_value( $filters, $context, $post ) {
		if ( empty( $filters['meta_key'] ) ) {
			return '';
		}

		// Sanitize the action name
		$action = sanitize_text_field( $filters['meta_key'] );

		// Check if action exists
		if ( ! has_action( $action ) ) {
			return '';
		}

		// NOTE: Undocumented. Currently used by Bricks\Woocommerce
		do_action( 'bricks/dynamic_data/before_do_action', $action, $filters, $context, $post );

		// Get the value
		ob_start();

		/**
		 * It's not supported to pass arguments to the action as different actions can have different arguments
		 *
		 * If the add_action() is expecting more arguments, then error will be thrown
		 */
		do_action( $action );

		$value = ob_get_clean();

		// NOTE: Undocumented. Currently used by Bricks\Woocommerce
		do_action( 'bricks/dynamic_data/after_do_action', $action, $filters, $context, $post, $value );

		return $value;
	}

	public function get_echo_callback_value( $filters, $context, $post ) {
		if ( empty( $filters['meta_key'] ) ) {
			return '';
		}

		$callback = $filters['meta_key'];
		$args     = [];
		$chunk    = '';

		// STEP: There are arguments to be parsed
		if ( strpos( $callback, '(' ) !== false ) {
			$parts = explode( '(', $callback );

			// STEP: Callback
			$callback = trim( $parts[0] );

			// STEP: Arguments

			// Remove spaces and bracket leftover
			$parts[1] = rtrim( trim( $parts[1] ), ')' );

			// Parse arguments, e.g.: 'arg1,still arg1', 'arg2', arg3 (@since 1.5.3)
			if ( ! empty( $parts[1] ) ) {
				$in_quote = false;

				foreach ( str_split( $parts[1] ) as $char ) {
					// Skip spaces outside of a single quote (@since 1.9.1)
					if ( ! $in_quote && $char == ' ' ) {
						continue;
					}

					// Bump into a single quote
					if ( $char == '\'' ) {
						// Already inside of a single quote: this is a closing quote
						if ( $in_quote ) {
							// Add chunk as argument
							$args[] = $chunk;

							$chunk = '';
						}

						// Toggle quote status
						$in_quote = ! $in_quote;

						continue;
					}

					// Bump into comma, outside of a single quote
					if ( ! $in_quote && $char == ',' ) {
						// Non-empty chunk: Add it as argument
						if ( $chunk !== '' ) {
							$args[] = $chunk;

							$chunk = '';
						}

						continue;
					}

					// Still here, add char to the chunk
					$chunk .= $char;
				}
			}

			// We still have one argument: Add chunk as argument
			if ( $chunk !== '' ) {
				$args[] = $chunk;
			}

		} else {
			$callback = trim( $callback );
		}

		// Return: Function name is not whitelisted
		$whitelisted_function_names = apply_filters( 'bricks/code/echo_function_names', [] );

		if ( ! is_array( $whitelisted_function_names ) || ! in_array( $callback, $whitelisted_function_names ) ) {
			return '';
		}

		// Execute the callback + args
		try {
			return function_exists( $callback ) ? call_user_func_array( $callback, $args ) : '';
		}

		// Error handling (@since 1.5.3)
		catch ( \Exception $error ) {
			error_log( 'Exception: ' . print_r( $error->getMessage(), true ) );
		} catch ( \ParseError $error ) {
			error_log( 'ParseError: ' . print_r( $error->getMessage(), true ) );
		} catch ( \Error $error ) {
			error_log( 'Error: ' . print_r( $error->getMessage(), true ) );
		}

		return '';
	}

	/**
	 * Helper function to get the post content
	 *
	 * @param [type] $post
	 */
	public function get_the_content( $post ) {
		if ( empty( $post ) ) {
			return '';
		}

		$content = get_the_content( null, false, $post );
		$content = apply_filters( 'the_content', $content );

		return $content;
	}

	/**
	 * Render user related data
	 *
	 * @param [type]  $tag
	 * @param WP_User $user
	 * @param [type]  $filters
	 * @param [type]  $context
	 */
	public function get_user_tag_value( $tag, $user, $filters, $context ) {
		if ( empty( $user->ID ) ) {
			return '';
		}

		$value = '';

		$field_type = str_replace( [ 'wp_user_' ], '', $tag );

		switch ( $field_type ) {

			case 'id':
				$value = $user->ID;
				break;

			case 'login':
			case 'email':
			case 'url':
			case 'website':
			case 'nicename':
				$field_type = 'website' == $field_type ? 'url' : $field_type; // Legacy
				$field      = 'user_' . $field_type;
				$value      = isset( $user->{$field} ) ? $user->{$field} : '';
				break;

			case 'bio':
			case 'description':
			case 'first_name':
			case 'last_name':
			case 'display_name':
				$field_type = 'bio' == $field_type ? 'description' : $field_type; // Legacy
				$value      = isset( $user->{$field_type} ) ? $user->{$field_type} : '';
				break;

			case 'name':
				if ( ! empty( $user->first_name ) && ! empty( $user->last_name ) ) {
					$value = trim( $user->first_name . ' ' . $user->last_name );
				} else {
					$value = trim( $user->display_name );
				}
				break;

			case 'picture':
			case 'avatar':
				// If context = image, increase the default image size to 512px
				$default_size = $context === 'image' ? 512 : 96;

				$size = empty( $filters['num_words'] ) ? $default_size : $filters['num_words'];

				// translators: %s = the author name
				$alt = sprintf( esc_html__( 'Avatar image of %s', 'bricks' ), get_the_author_meta( 'display_name', $user->ID ) );

				$value = $context === 'link' || $context === 'image' ? get_avatar_url( $user->ID, [ 'size' => $size ] ) : get_avatar( $user->ID, $size, '', $alt );
				break;

			case 'nickname':
				$value = get_user_meta( $user->ID, 'nickname', true );
				break;

			case 'meta':
				if ( ! empty( $filters['meta_key'] ) ) {
					$value = get_user_meta( $user->ID, $filters['meta_key'], true );
				}
				break;
		}

		// NOTE: Undocumented
		$value = apply_filters( 'bricks/dynamic_data/user_value', $value, $field_type, $filters );

		return $value;
	}

	/**
	 * Render author related meta data
	 *
	 * Example: Author ID, which isn't available in get_user_meta()
	 *
	 * @param [type]  $tag
	 * @param WP_User $user
	 * @param [type]  $filters
	 * @param [type]  $context
	 *
	 * @since 1.9.6
	 */
	public function get_author_tag_value( $tag, $user, $filters, $context ) {
		if ( empty( $user->ID ) ) {
			return '';
		}

		$value      = '';
		$field_type = str_replace( [ 'author_' ], '', $tag );

		switch ( $field_type ) {
			// {author_name} remains same logic for backward compatibility
			case 'name':
				if ( ! empty( $user->first_name ) && ! empty( $user->last_name ) ) {
					$value = trim( $user->first_name . ' ' . $user->last_name );
				} else {
					$value = trim( $user->display_name );
				}
				break;

			// {author_avatar} remains same logic for backward compatibility
			case 'avatar':
				// If context = image, increase the default image size to 512px
				$default_size = $context === 'image' ? 512 : 96;

				$size = empty( $filters['num_words'] ) ? $default_size : $filters['num_words'];

				// translators: %s = the author name
				$alt = sprintf( esc_html__( 'Avatar image of %s', 'bricks' ), get_the_author_meta( 'display_name', $user->ID ) );

				$value = $context === 'link' || $context === 'image' ? get_avatar_url( $user->ID, [ 'size' => $size ] ) : get_avatar( $user->ID, $size, '', $alt );
				break;

			case 'archive_url':
				$value = get_author_posts_url( $user->ID );
				break;

			case 'bio':
			case 'email':
			case 'meta':
			case 'website':
				$key = $field_type;

				// {author_bio} set key to description
				if ( $field_type === 'bio' ) {
					$key = 'description';
				}

				// {author_website} set key to url
				if ( $field_type === 'website' ) {
					$key = 'url';
				}

				if ( ! empty( $filters['meta_key'] ) ) {
					$key = $filters['meta_key'];
				}

				$value = get_the_author_meta( $key, $user->ID );
				break;
		}

		// NOTE: Undocumented
		$value = apply_filters( 'bricks/dynamic_data/author_value', $value, $field_type, $filters );

		return $value;
	}

	/**
	 * Render Post Terms value
	 *
	 * @param [type] $tag
	 * @param [type] $post
	 * @param [type] $filters
	 * @param [type] $context
	 */
	public function get_post_terms_value( $tag, $post, $filters, $context ) {
		if ( ! isset( $post->ID ) ) {
			return '';
		}

		$taxonomy = str_replace( 'post_terms_', '', $tag );

		$terms = wp_get_post_terms( $post->ID, $taxonomy );

		if ( ! $terms || is_wp_error( $terms ) ) {
			return '';
		}

		// https://academy.bricksbuilder.io/article/filter-bricks-dynamic_data-post_terms_links/
		$has_links = apply_filters( 'bricks/dynamic_data/post_terms_links', true, $post, $taxonomy );

		$output = [];

		foreach ( $terms as $term ) {
			$item = $term->name;

			if ( $has_links ) {
				$url = get_term_link( $term );

				if ( ! empty( $url ) && ! is_wp_error( $url ) ) {
					$item = '<a href="' . esc_url( $url ) . '">' . $item . '</a>';
				}
			}

			$output[] = $item;
		}

		$sep = isset( $filters['meta_key'] ) ? $filters['meta_key'] : ', ';

		// https://academy.bricksbuilder.io/article/filter-bricks-dynamic_data-post_terms_separator/
		$sep = apply_filters( 'bricks/dynamic_data/post_terms_separator', $sep, $post, $taxonomy );

		return implode( $sep, $output );
	}

	public function get_term_tag_value( $tag, $filters, $context, $post_id ) {
		$looping_query_id = Query::is_any_looping();
		$object           = null;

		if ( ! empty( $looping_query_id ) ) {
			$object = Query::get_loop_object( $looping_query_id );
		}

		// Is taxonomy archive (check again is_tax() not working inside Posts element in archive template @since 1.7.2)
		if ( ! $object ) {
			$object = get_queried_object();
		}

		/**
		 * term_xx DD unable to parse correctly based on populate content (@since 1.9.5)
		 * TODO NOTE: No longer in use (@since 1.9.6). As it causes builder query issue (#86bx6wxxm)
		 * Should be tackled together with (#86bw6re4w)
		 */
		// if ( Helpers::is_bricks_preview() && ! Query::is_looping() ) {
		// $object = Helpers::get_queried_object( $post_id );
		// }

		// Not a WP_Term, leave
		if ( ! $object || ! is_a( $object, 'WP_Term' ) ) {
			return '';
		}

		switch ( $tag ) {
			case 'term_id':
				$value = $object->term_id;
				break;

			case 'term_name':
				$value = $object->name;
				break;

			case 'term_slug':
				$value = $object->slug;
				break;

			case 'term_count':
				$value = $object->count;
				break;

			case 'term_description':
				$value = $object->description;
				break;

			case 'term_url':
				$value = get_term_link( $object );
				break;

			case 'term_meta':
				if ( ! empty( $filters['meta_key'] ) ) {
					$value = get_term_meta( $object->term_id, $filters['meta_key'], true );
				}

				break;
		}

		return $value;
	}

	/**
	 * Get all the the unique postmeta keys
	 *
	 * @since 1.5.1
	 *
	 * @return array
	 */
	private function get_site_meta_keys() {
		$cache_key = 'brx_all_meta_keys';

		$meta_keys = wp_cache_get( $cache_key, 'bricks' );

		if ( $meta_keys === false ) {
			global $wpdb;

			// Query DB for all the unique meta keys (@see https://developer.wordpress.org/reference/classes/wpdb/get_results/)
			$meta_keys_results = $wpdb->get_results( "SELECT DISTINCT($wpdb->postmeta.meta_key) FROM $wpdb->postmeta WHERE 1", OBJECT );

			if ( empty( $meta_keys_results ) || ! is_array( $meta_keys_results ) ) {
				return [];
			}

			$meta_keys = wp_list_pluck( $meta_keys_results, 'meta_key' );

			wp_cache_set( $cache_key, $meta_keys, 'bricks', 5 * MINUTE_IN_SECONDS );
		}

		return $meta_keys;
	}
}
