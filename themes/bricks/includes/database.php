<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Database {
	public static $posts_per_page   = 0;
	public static $active_templates = [
		'header'  => 0,
		'footer'  => 0,
		'content' => 0,
		'section' => 0, // Use in "Template" element
		'archive' => 0,
		'error'   => 0,
		'search'  => 0,
		'popup'   => [], // Array with popup template ids
	];

	public static $default_template_types = [
		'header',
		'footer',
		'archive',
		'search',
		'error',
		'wc_archive',
		'wc_product',
		'wc_cart',
		'wc_cart_empty',
		'wc_form_checkout',
		'wc_form_pay',
		'wc_thankyou',
		'wc_order_receipt',
		// Woo Phase 3
		'wc_account_dashboard',
		'wc_account_orders',
		'wc_account_view_order',
		'wc_account_downloads',
		'wc_account_addresses',
		'wc_account_form_edit_address',
		'wc_account_form_edit_account',
		'wc_account_form_login',
		'wc_account_form_lost_password',
		'wc_account_form_lost_password_confirmation',
		'wc_account_reset_password',
	];

	public static $header_position = 'top';
	public static $global_data     = [];
	public static $page_data       = [
		'preview_or_post_id' => 0,
	];
	public static $global_settings = [];
	public static $page_settings   = [];
	public static $adobe_fonts     = [];

	public function __construct() {
		self::get_global_data();

		add_action( 'pre_get_posts', [ $this, 'set_main_archive_query' ] );

		// Set active templates
		add_action( 'wp', [ $this, 'set_active_templates' ] );

		// Set page data (AJAX)
		add_action( 'wp_loaded', [ $this, 'set_ajax_page_data' ] );

		// Set page data (no AJAX)
		add_action( 'wp', [ $this, 'set_page_data' ] );

		// Set page data on REST API calls
		add_action( 'rest_api_init', [ $this, 'set_page_data' ] );

		add_action( 'update_option_' . BRICKS_DB_GLOBAL_CLASSES, [ $this, 'update_option_bricks_global_classes' ], 10, 3 );

		add_filter( 'wp_prepare_themes_for_js', [ $this, 'wp_prepare_themes_for_js' ] );
	}

	/**
	 * Support autoupdate
	 *
	 * To always show "Enable/disable auto-updates" link for Bricks.
	 * Otherwise, link only shows when an update is available.
	 */
	public function wp_prepare_themes_for_js( $prepared_themes ) {
		// Add auto update support for Bricks theme
		if ( isset( $prepared_themes['bricks']['autoupdate']['supported'] ) ) {
			$prepared_themes['bricks']['autoupdate']['supported'] = true;
		}

		return $prepared_themes;
	}

	/**
	 * Log every save of empty global classes to debug where it's coming from
	 *
	 * Triggered in Bricks via:
	 *
	 * ajax.php:      wp_ajax_bricks_save_post (save post in builder)
	 * templates.php: wp_ajax_bricks_import_template (template import)
	 * converter.php: wp_ajax_bricks_run_converter (run converter from Bricks settings)
	 *
	 * @link https://developer.wordpress.org/reference/hooks/update_option_option/
	 *
	 * @since 1.7
	 */
	public function update_option_bricks_global_classes( $old_value, $new_value, $option_name ) {
		if ( $option_name === BRICKS_DB_GLOBAL_CLASSES ) {
			$old_count = is_array( $old_value ) ? count( $old_value ) : 0;
			$new_count = is_array( $new_value ) ? count( $new_value ) : 0;

			// Record only global class saves where total number of global classes changed
			if ( $old_count !== $new_count ) {
				$current_user = wp_get_current_user();

				// Possible AJAX calls: Save post in builder, import templates, run converter
				$new_entry = [
					'timestamp' => time(),
					'referer'   => wp_get_referer(),
					'action'    => isset( $_POST['action'] ) ? sanitize_text_field( $_POST['action'] ) : '',
					'post_id'   => isset( $_POST['postId'] ) ? intval( $_POST['postId'] ) : '',
					'user_id'   => $current_user ? $current_user->ID : 0,
					'old_count' => $old_count,
					'new_count' => $new_count,
				];

				$saves = get_option( 'bricks_global_classes_changes', [] );

				if ( ! is_array( $saves ) ) {
					$saves = [];
				}

				// Keep the first 25 changes
				if ( count( $saves ) >= 25 ) {
					array_shift( $saves );
				}

				$saves[] = $new_entry;

				update_option( 'bricks_global_classes_changes', $saves, false );
			}
		}
	}

	/**
	 * Customize WP Main Query: Set all query_vars by user for archive/search/error template pages
	 * So the pagination will not encounter 404 errors
	 *
	 * @since 1.9.1
	 */
	public function set_main_archive_query( $query ) {
		if ( bricks_is_builder() || is_admin() || ! $query->is_main_query() ) {
			return;
		}

		$post_id              = 0;
		$set_active_templates = $query->is_archive || $query->is_search || $query->is_error || $query->is_home;

		// Archive, Search, Error, Home: Get the active template
		if ( $set_active_templates ) {
			// Set active templates
			self::set_active_templates( $query );

			// This is the template currently being used for archive/search/error/home
			$post_id = ! empty( self::$active_templates['content'] ) ? self::$active_templates['content'] : 0;
		}

		if ( $post_id ) {
			// Check if any Bricks data is set
			$bricks_data = self::get_data( $post_id );

			// Start to scan through if any query element is set, main objective is get the query settings for the main archive query
			if ( is_array( $bricks_data ) ) {
				/**
				 * STEP: Get nested template data
				 *
				 * Now $bricks_data contains all the data from the main template and all nested templates.
				 *
				 * @since 1.9.1
				 */
				$bricks_data = self::get_nested_template_data( $bricks_data );

				/**
				 * STEP: Arrange the $bricks_data array
				 *
				 * $bricks_data is not sorted by position following the builder structure, we do not know which main query settings should be used if more than 1 query ticked the is_archive_main_query
				 *
				 * @since 1.9.1
				 */
				$structured_element_ids = self::elements_sequence_in_builder( $bricks_data );

				// Loop through elements follow builder structure sequence, to get main archive query settings defined by the user, only the first one will be used (@since 1.9.1)
				$archive_query_set = false;

				foreach ( $structured_element_ids as $element_id ) {
					// Find element
					$element = self::get_element_by_id( $element_id, $bricks_data );

					if ( ! $element ) {
						continue;
					}

					// Certain elements 'is_archive_main_query' is not set inside query key.
					if ( isset( $element['settings']['is_archive_main_query'] ) ) {
						// WooCommerce Products element
						if ( $element['name'] === 'woocommerce-products' ) {
							$element['settings']['query']['is_archive_main_query'] = 1;
						}
					}

					// Exit if not a query element
					if ( empty( $element['settings']['query'] ) ) {
						continue;
					}
					// Exit foreach if main query is already set once
					if ( $archive_query_set ) {
						break;
					}

					$object_type = isset( $element['settings']['query']['objectType'] ) ? $element['settings']['query']['objectType'] : 'post';

					// Set if 'is_archive_main_query' set (still check 'posts_per_page' for backwards-compatibility)
					// We cannot check posts_per_page, any query element can set it, not bullet proof. (@since 1.9.1)
					if (
						in_array( $object_type, Query::archive_query_supported_object_types() ) &&
						isset( $element['settings']['query']['is_archive_main_query'] )
					) {
						// NOTE: If it's dynamic data, unable to parse the value, as inside this hook, the dynamic tags are not registered yet
						// Use the prepared query vars instead of raw element settings (@since 1.8)
						$query_vars = Query::prepare_query_vars_from_settings( $element['settings'], $element_id );

						foreach ( $query_vars as $key => $value ) {
							if ( in_array( $key, Query::archive_query_arguments() ) ) {
								$query->set( $key, $value );
							}
						}

						// Set flag to exit foreach
						$archive_query_set = true;
					}
				}
			}
		}

		/**
		 * Reset active templates
		 *
		 * @see #86bw4pmd0
		 * @since 1.9.2
		 */
		if ( $set_active_templates ) {
			self::$active_templates = [
				'header'  => 0,
				'footer'  => 0,
				'content' => 0,
				'section' => 0,
				'archive' => 0,
				'error'   => 0,
				'search'  => 0,
				'popup'   => [],
			];
		}
	}

	/**
	 * Set active templates for use throughout the theme
	 */
	public static function set_active_templates( $post_id = 0 ) {
		// Check if set_active_templates already ran
		if ( isset( self::$active_templates['post_id'] ) ) {
			return;
		}

		if ( ! $post_id || is_object( $post_id ) ) {
			$post_id = get_the_ID();
		}

		// NOTE: Set post ID to posts page. Code will try to find templates for the page defined as the blog page
		if ( is_home() ) {
			$post_id = get_option( 'page_for_posts' );
		}

		$post_id = intval( $post_id );

		$post_type = get_post_type( $post_id );

		$preview_type = ''; // Only applicable to templates

		$content_type = 'content'; // = default content type

		// Check if post is Bricks template
		if ( is_singular( BRICKS_DB_TEMPLATE_SLUG ) ) {
			$template_type = get_post_meta( $post_id, BRICKS_DB_TEMPLATE_TYPE, true );

			if ( in_array( $template_type, [ 'header', 'footer' ] ) ) {
				self::$active_templates[ $template_type ] = $post_id;

				$preview_type = Helpers::get_template_setting( 'templatePreviewType', $post_id );

				switch ( $preview_type ) {
					case 'single':
						$preview_id = Helpers::get_template_setting( 'templatePreviewPostId', $post_id );

						$content_type                      = 'content';
						self::$active_templates['content'] = $preview_id;
						break;

					case 'search':
						$content_type = 'search';
						break;

					case 'archive-recent-posts':
					case 'archive-author':
					case 'archive-date':
					case 'archive-cpt':
					case 'archive-term':
						$content_type = 'archive';
						break;
				}

			} else {
				self::$active_templates['content'] = $post_id;
				$content_type                      = $template_type;
			}
		}

		// All other cases (builder & frontend)
		else {
			// Find content type needed given the current page load query
			$tag_templates = [
				'is_404'               => 'error',
				'is_search'            => 'search',
				'is_home'              => 'content',
				'is_front_page'        => 'content',
				'is_singular'          => 'content',
				'is_product_taxonomy'  => 'wc_archive',
				'is_post_type_archive' => 'archive',
				'is_tax'               => 'archive',
				'is_author'            => 'archive',
				'is_date'              => 'archive',
				'is_archive'           => 'archive',
			];

			foreach ( $tag_templates as $tag => $type ) {
				if ( function_exists( $tag ) && call_user_func( $tag ) ) {
					$content_type = $type;

					if ( 'content' != $type ) {
						$post_type = '';
						$post_id   = 0;
					}

					break;
				}
			}
		}

		// NOTE: Undocumented
		$content_type = apply_filters( 'bricks/database/content_type', $content_type, $post_id );

		// NOTE: Undocumented
		$post_id = apply_filters( 'bricks/builder/data_post_id', $post_id );

		self::$active_templates['post_id']      = $post_id;
		self::$active_templates['post_type']    = $post_type;
		self::$active_templates['content_type'] = $content_type;

		// Get all available templates
		$template_ids = self::get_all_templates_by_type();

		// Preview id is only set if template is using populate content as single (with templatePreviewPostId)
		$preview_id = isset( $preview_id ) ? $preview_id : $post_id;

		// For each template part, try to find the best template available
		foreach ( [ 'header', 'footer', 'content' ] as $template_part ) {
			if ( ! empty( self::$active_templates[ $template_part ] ) ) {
				continue;
			}

			self::$active_templates[ $template_part ] = self::find_template_id( $template_ids, $template_part, $content_type, $preview_id, $preview_type );
		}

		// Get all popups
		self::$active_templates['popup'] = self::find_templates( $template_ids, 'popup', $preview_id, $preview_type );

		// Ensure popup being previewed is included
		if ( Templates::get_template_type( $post_id ) === 'popup' && ! in_array( $post_id, self::$active_templates['popup'] ) ) {
			self::$active_templates['popup'][] = $post_id;
		}

		// If $content_type != header, footer, content, section, popup; set $active_template = content
		if ( isset( $content_type ) && ! in_array( $content_type, [ 'header', 'footer', 'section', 'content', 'popup' ] ) ) {
			self::$active_templates[ $content_type ] = self::$active_templates['content'];
		}

		// No templates defined, set page/cpt content if Bricks is supported
		if ( ! empty( $post_id ) && Helpers::is_post_type_supported( $post_id ) && empty( self::$active_templates['content'] ) ) {
			self::$active_templates['content'] = $post_id;
		}

		/**
		 * Allow to modify the active templates
		 *
		 * @see https://academy.bricksbuilder.io/article/filter-bricks-active_templates/
		 *
		 * @since 1.8.4
		 */
		self::$active_templates = apply_filters( 'bricks/active_templates', self::$active_templates, $post_id, self::$active_templates['content_type'] );

		// Set header position (to use in bricksData.headerPosition)
		if ( self::$active_templates['header'] > 0 ) {
			$header_position       = Helpers::get_template_setting( 'headerPosition', intval( self::$active_templates['header'] ) );
			self::$header_position = isset( $header_position ) && ! empty( $header_position ) ? $header_position : 'top';
		}
	}

	/**
	 * Finds the most suitable template id for a specific context
	 *
	 * @param array  $template_ids Organized by type.
	 * @param string $template_part header, footer or content.
	 * @param string $content_type What type of content is expected: content, archive, search, error.
	 * @param string $post_id Current post_id or preview_id.
	 * @param string $preview_type If template, and populate content is set.
	 */
	public static function find_template_id( $template_ids, $template_part, $content_type, $post_id, $preview_type ) {
		$found_templates = []; // Hold all the found template ids for the context, with score 0.low XX.high [score=>template id]

		$disable_default_templates = self::get_setting( 'defaultTemplatesDisabled', false );

		$post_type = get_post_type( $post_id );

		// Loop for all the templates and template conditions and assign scores
		// 0 - Default (no condition set)
		// 1 - Default to a specific template type (I'm looking for a search template, and this is type search)
		// 2 - Entire website (condition = any)
		// 8 - Terms, specific archives, children of specific Post ID
		// 9 - Front page
		// 10 - Specific Post ID (best match)

		// 'body' list includes all template types != header, footer, section & popup
		$template_loop_type = $template_part === 'content' ? 'body' : $template_part;

		if ( empty( $template_ids[ $template_loop_type ] ) ) {
			return 0;
		}

		// Check template conditions
		foreach ( $template_ids[ $template_loop_type ] as $template_id ) {
			$template_conditions = Helpers::get_template_setting( 'templateConditions', $template_id );

			if ( ! $template_conditions ) {
				if ( ! $disable_default_templates ) {
					// No conditions, if defaults are possible, set it as default (but don't set a Search template as fallback of a Page content)
					if ( in_array( $template_part, [ 'header', 'footer' ] ) ) {
						$found_templates[0] = $template_id;
					}

					// If template_part is content, and this template type = content_type (search = search) then it might be a good default
					if ( 'content' === $template_part && 'content' !== $content_type && ! empty( $template_ids[ $content_type ] ) && in_array( $template_id, $template_ids[ $content_type ] ) ) {
						$found_templates[1] = $template_id;
					}
				}

				continue;
			}

			$found_templates = self::screen_conditions( $found_templates, $template_id, $template_conditions, $post_id, $preview_type );
		}

		// Return template id with highest score.
		if ( ! empty( $found_templates ) ) {
			$max = max( array_keys( $found_templates ) );

			return $found_templates[ $max ];
		}

		// No template found
		return 0;
	}

	/**
	 * Find all the templates available for a specific context based on the template conditions
	 *
	 * @param array  $template_ids List of templates per template type.
	 * @param string $template_part header, footer or content.
	 */
	public static function find_templates( $template_ids, $template_part, $post_id, $preview_type ) {
		$found_templates = [];

		$template_loop_type = $template_part === 'content' ? 'body' : $template_part;

		if ( empty( $template_ids[ $template_loop_type ] ) ) {
			return [];
		}

		// Check template conditions
		foreach ( $template_ids[ $template_loop_type ] as $template_id ) {
			$template_conditions = Helpers::get_template_setting( 'templateConditions', $template_id );

			$found = self::screen_conditions( [], $template_id, $template_conditions, $post_id, $preview_type );

			if ( ! empty( $found ) ) {
				$found_templates[] = $template_id;
			}
		}

		return $found_templates;
	}

	/**
	 * Undocumented function
	 */
	public static function get_all_templates_by_type() {
		// Last changed timestamp is set on Templates::flush_templates_cache()
		$last_changed = wp_cache_get_last_changed( 'bricks_' . BRICKS_DB_TEMPLATE_SLUG );

		// @since 1.7.1 - Prefix cache key with get_locale() to ensure correct templates are loaded for different languages (@see #862jdhqgr)
		$cache_key = get_locale() . '_all_templates_' . $last_changed;

		$output = wp_cache_get( $cache_key, 'bricks' );

		if ( $output === false ) {
			$args = [
				'post_type'      => BRICKS_DB_TEMPLATE_SLUG,
				'posts_per_page' => -1,
				'meta_query'     => [
					[
						'key'     => BRICKS_DB_TEMPLATE_TYPE,
						'compare' => 'EXISTS',
					],
				],
				'post_status'    => 'publish',
				'fields'         => 'ids',
			];

			/**
			 * Filter query args for get_posts()
			 *
			 * Currently used by WPML to get correct templates (@see #862j3xyg7)
			 *
			 * @since 1.7
			 */
			$args = apply_filters( 'bricks/database/bricks_get_all_templates_by_type_args', $args );

			$template_ids = get_posts( $args );

			$output = [];

			// Organize templates by type
			foreach ( $template_ids as $t_id ) {
				$type              = get_post_meta( $t_id, BRICKS_DB_TEMPLATE_TYPE, true );
				$output[ $type ][] = $t_id;

				if ( ! in_array( $type, [ 'header', 'footer', 'section', 'popup' ] ) ) {
					$output['body'][] = $t_id; // Adds to the 'body' template type all the other types like Content, Archive, Search Results, Error Page as they are a kind of body content
				}
			}

			wp_cache_set( $cache_key, $output, 'bricks', DAY_IN_SECONDS );
		}

		return $output;
	}

	/**
	 * Set default header/footer template
	 *
	 * If no template with matching templateCondition(s) has been set.
	 *
	 * Can be disabled via admin setting 'defaultTemplatesDisabled'.
	 *
	 * @since 1.0
	 */
	public static function set_default_template( $template_type = '' ) {
		if ( ! $template_type ) {
			return;
		}

		$disable_default_templates = self::get_setting( 'defaultTemplatesDisabled', false );

		// Return if 'defaultTemplatesDisabled' is set
		$current_template_type = get_post_meta( get_the_ID(), BRICKS_DB_TEMPLATE_TYPE, true );

		if ( $disable_default_templates && $current_template_type !== $template_type ) {
			return;
		}

		$template_ids = get_posts(
			[
				'post_type'      => BRICKS_DB_TEMPLATE_SLUG,
				'posts_per_page' => -1,
				'meta_query'     => [
					[
						'key'   => BRICKS_DB_TEMPLATE_TYPE,
						'value' => $template_type,
					],
				],
				'post_status'    => 'publish',
				'fields'         => 'ids',
			]
		);

		$template_id = count( $template_ids ) ? $template_ids[0] : false;

		if ( $template_id ) {
			self::$active_templates[ $template_type ] = intval( $template_id );
		}
	}

	/**
	 * Helper function to screen a set of template or theme style conditions and check if they apply given the context
	 *
	 * @param array  $found Holds array of found object IDs (the key is the score).
	 * @param string $object_id Could be template_id or the style_id.
	 * @param array  $conditions Template or Theme Style conditions.
	 * @param int    $post_id Real or Preview).
	 * @param string $preview_type The preview type (single, search, archive, etc.).
	 *
	 * @return array Found conditions array ($score => $object_id)
	 */
	public static function screen_conditions( $found, $object_id, $conditions, $post_id, $preview_type ) {
		if ( empty( $conditions ) ) {
			return $found;
		}

		$post_type = get_post_type( $post_id );

		$is_valid = true; // Used to exclude this object if an excluding condition applies

		$scores = []; // Holds scores of this object_id

		foreach ( $conditions as $condition ) {
			if ( ! $is_valid ) {
				break;
			}

			// Check if main template condition is set
			if ( ! isset( $condition['main'] ) ) {
				continue;
			}

			$exclude = isset( $condition['exclude'] );

			if ( ! empty( $post_id ) ) {
				// 1. Check if template was set for a specific post ID or children
				if ( $condition['main'] === 'ids' && isset( $condition['ids'] ) ) {

					// Specific post ID
					if ( in_array( $post_id, $condition['ids'] ) ) {
						$is_valid = ! $exclude;
						$scores[] = 10;
					}

					// Apply to child pages
					elseif ( isset( $condition['idsIncludeChildren'] ) ) {
						$ancestors = get_post_ancestors( $post_id );

						foreach ( $ancestors as $ancestor_id ) {
							if ( in_array( $ancestor_id, $condition['ids'] ) ) {
								$is_valid = ! $exclude;
								$scores[] = 8; // Less important than a template set for a specific ID
								break;
							}
						}
					}
				}

				// 2. Check if template was set for a specific term assigned to the post
				if ( $condition['main'] === 'terms' && isset( $condition['terms'] ) ) {
					$terms = $condition['terms'];

					foreach ( $terms as $term ) {
						$tax_term = explode( '::', $term );
						$taxonomy = $tax_term[0];
						$term     = $tax_term[1];

						$post_terms = wp_get_post_terms( $post_id, $taxonomy, [ 'fields' => 'ids' ] );

						if ( is_array( $post_terms ) && in_array( $term, $post_terms ) ) {
							$is_valid = ! $exclude;
							$scores[] = 8;
						}
					}
				}

				// 3. Check if template applies to a specific post type
				if ( $condition['main'] === 'postType' && isset( $condition['postType'] ) && in_array( $post_type, $condition['postType'] ) ) {
					$is_valid = ! $exclude;
					$scores[] = 7;
				}
			}

			// Archive (any/author/data/term)
			if ( is_archive() && $condition['main'] === 'archiveType' ) {
				if ( ! isset( $condition['archiveType'] ) ) {
					continue;
				}

				// Archive pages include category, tag, author, date, custom post type, and custom taxonomy based archives.
				if ( in_array( 'any', $condition['archiveType'] ) && ( is_archive() || strpos( $preview_type, 'archive' ) !== false ) ) {
					$is_valid = ! $exclude;
					$scores[] = 3;
				}

				// This condition allows for multiple values. Since is_archive includes all the following conditions we need to test them as well
				if ( in_array( 'postType', $condition['archiveType'] ) && ( is_post_type_archive() || $preview_type === 'archive-cpt' ) ) {
					if ( empty( $condition['archivePostTypes'] ) ) {
						$is_valid = ! $exclude;
						$scores[] = 7;
					} else {
						// Previewing a template with content set to a CPT archive
						if ( $preview_type === 'archive-cpt' ) {
							$preview_cpt = Helpers::get_template_setting( 'templatePreviewPostType', $post_id );

							if ( $preview_cpt && in_array( $preview_cpt, $condition['archivePostTypes'] ) ) {
								$is_valid = ! $exclude;
								$scores[] = 8;
							}
						}
						// or, check if the post type archive matches the post type condition
						elseif ( is_post_type_archive( $condition['archivePostTypes'] ) ) {
							$is_valid = ! $exclude;
							$scores[] = 8;
						}
					}
				} elseif ( in_array( 'author', $condition['archiveType'] ) && ( is_author() || $preview_type === 'archive-author' ) ) {
					$is_valid = ! $exclude;
					$scores[] = 8;
				} elseif ( in_array( 'date', $condition['archiveType'] ) && ( is_date() || $preview_type === 'archive-date' ) ) {
					$is_valid = ! $exclude;
					$scores[] = 8;
				} elseif ( in_array( 'term', $condition['archiveType'] ) && ( is_category() || is_tag() || is_tax() || $preview_type === 'archive-term' ) ) {
					// Apply template to selected archive terms
					if ( isset( $condition['archiveTerms'] ) && is_array( $condition['archiveTerms'] ) ) {

						// Previewing a template, with populate content set to archive of term
						if ( $preview_type === 'archive-term' ) {
							// Note the post_id here is the template post Id (because in this archive situation the preview_id was not set)
							$preview_term = Helpers::get_template_setting( 'templatePreviewTerm', $post_id );

							if ( ! empty( $preview_term ) ) {
								$preview_term     = explode( '::', $preview_term );
								$queried_taxonomy = isset( $preview_term[0] ) ? $preview_term[0] : '';
								$queried_term_id  = isset( $preview_term[1] ) ? intval( $preview_term[1] ) : '';
							}
						}

						// All the other situations in frontend: is_category() || is_tag() || is_tax()
						else {
							$queried_object = get_queried_object();

							if ( is_object( $queried_object ) ) {
								$queried_term_id  = intval( $queried_object->term_id );
								$queried_taxonomy = $queried_object->taxonomy;
							}
						}

						// Check if queried taxonomy and term_id matches any of the selected archive terms
						if ( ! empty( $queried_term_id ) && ! empty( $queried_taxonomy ) ) {
							foreach ( $condition['archiveTerms'] as $archive_term ) {
								$term_parts = explode( '::', $archive_term );
								$taxonomy   = $term_parts[0];
								$term_id    = $term_parts[1];

								if ( $queried_taxonomy === $taxonomy ) {
									if ( $queried_term_id === intval( $term_id ) ) {
										$is_valid = ! $exclude;
										$scores[] = 8;
										break;
									}

									// Applied for taxonomy::all (all terms of a taxonomy)
									elseif ( 'all' == $term_id ) {
										$is_valid = ! $exclude;
										$scores[] = 7;
										break;
									}

									// The condition includes child terms, check if the queried term id is child of the term id set in the condition
									elseif ( isset( $condition['archiveTermsIncludeChildren'] ) && term_is_ancestor_of( $term_id, $queried_term_id, $queried_taxonomy ) ) {
										$is_valid = ! $exclude;
										$scores[] = 8;
										break;
									}
								}
							}
						}
					}

					// Apply template to all archives terms
					else {
						$is_valid = ! $exclude;
						$scores[] = 4;
					}
				}

			} // End archive test

			// Check for search
			elseif ( $condition['main'] === 'search' && ( is_search() || $preview_type === 'search' ) ) {
				$is_valid = ! $exclude;
				$scores[] = 8;
			}

			// Check for error
			elseif ( $condition['main'] === 'error' && ( is_404() || $preview_type === 'error' ) ) {
				$is_valid = ! $exclude;
				$scores[] = 8;
			}

			// Check for front page (it might compete with single post rules)
			if ( $condition['main'] === 'frontpage' ) {

				// @since 1.7 - Only use 'page_on_front' option if we are in an AJAX calls (@see #862j4ay8x)
				if ( bricks_is_ajax_call() || bricks_is_rest_call() ) {
					// Use 'page_on_front' option as is_front_page() is not reliable in AJAX calls (@see #861m42jdb)
					$front_page_id = get_option( 'page_on_front' );
					$is_front_page = absint( $post_id ) == absint( $front_page_id );
				} else {
					$is_front_page = is_front_page();
				}

				if ( $is_front_page ) {
					$is_valid = ! $exclude;
					$scores[] = 9;
				}

			}

			// Check for entire website
			if ( $condition['main'] === 'any' ) {
				$is_valid = ! $exclude;
				$scores[] = 2;
			}

			/**
			 * For each template (and theme style) condition allow setting a score based on custom template conditions (which are set via: builder/settings/{$this->setting_type}/controls_data)
			 *
			 * https://academy.bricksbuilder.io/article/filter-bricks-screen_conditions-scores/
			 *
			 * @since 1.5.5
			 */
			$scores = apply_filters( 'bricks/screen_conditions/scores', $scores, $condition, $post_id, $preview_type );
		}

		if ( $is_valid ) {
			$scores = array_unique( $scores );

			foreach ( $scores as $score ) {
				$found[ $score ] = $object_id;
			}
		}

		return $found;
	}

	/**
	 * Check if header or footer is disabled (via page settings) for the current context
	 *
	 * Page setting keys: headerDisabled, footerDisabled
	 *
	 * @return bool
	 * @since 1.5.4
	 */
	public static function is_template_disabled( $template_type ) {
		$setting_key      = "{$template_type}Disabled";
		$original_post_id = self::$page_data['original_post_id'] ?? 0;

		// Return: Previewing header or footer template
		if ( $original_post_id && Templates::get_template_type( $original_post_id ) === $template_type ) {
			return false;
		}

		$template_id = self::$active_templates['content'] ?? 0;

		// Post rendered through template and post has Bricks data: Get page settings from post instead of template
		if ( $template_id && $template_id !== $original_post_id ) {
			$page_settings = get_post_meta( $original_post_id, BRICKS_DB_PAGE_SETTINGS, true );
			if ( isset( $page_settings[ $setting_key ] ) ) {
				return true;
			}
		}

		return isset( self::$page_settings[ $setting_key ] );
	}

	/**
	 * Get template elements
	 *
	 * @since 1.0
	 */
	public static function get_template_data( $content_type ) {
		switch ( $content_type ) {
			case 'header':
				if ( self::is_template_disabled( 'header' ) ) {
					return;
				}

				$meta_key = BRICKS_DB_PAGE_HEADER;
				break;

			case 'footer':
				if ( self::is_template_disabled( 'footer' ) ) {
					return;
				}

				$meta_key = BRICKS_DB_PAGE_FOOTER;
				break;

			default:
				$meta_key = BRICKS_DB_PAGE_CONTENT;
				break;
		}

		$template_id = self::$active_templates[ $content_type ] ?? false;

		// No template found: Return Bricks content data
		if (
			! is_archive() &&
			! is_search() &&
			! $template_id &&
			$content_type !== 'header' &&
			$content_type !== 'footer'
		) {
			$data = get_post_meta( get_the_ID(), BRICKS_DB_PAGE_CONTENT, true );

			return $data;
		}

		$data = get_post_meta( $template_id, $meta_key, true );

		return $data;
	}

	/**
	 * Get Bricks data by post_id and content_area (header/content/footer)
	 *
	 * @since 1.0
	 */
	public static function get_data( $post_id = 0, $content_area = '' ) {
		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}

		$meta_key = self::get_bricks_data_key( $content_area );

		$elements = get_post_meta( $post_id, $meta_key, true );

		return is_array( $elements ) ? $elements : [];
	}

	/**
	 * Get the Bricks data key for a specific template type (header/content/footer)
	 *
	 * @since 1.5.1
	 *
	 * @param string $content_area
	 * @return string
	 */
	public static function get_bricks_data_key( $content_area = '' ) {
		switch ( $content_area ) {
			case 'header':
				$meta_key = BRICKS_DB_PAGE_HEADER;
				break;

			case 'footer':
				$meta_key = BRICKS_DB_PAGE_FOOTER;
				break;

			default:
				$meta_key = BRICKS_DB_PAGE_CONTENT;
				break;
		}

		return $meta_key;
	}

	/**
	 * Get global settings from options table
	 *
	 * @since 1.0
	 */
	public static function get_setting( $key, $default = false ) {
		return isset( self::$global_settings[ $key ] ) ? self::$global_settings[ $key ] : $default;
	}

	/**
	 * Get global data from options table
	 *
	 * @since 1.0
	 */
	public static function get_global_data() {
		// Color palette
		if ( is_multisite() && BRICKS_MULTISITE_USE_MAIN_SITE_COLOR_PALETTE ) {
			self::$global_data['colorPalette'] = get_blog_option( get_main_site_id(), BRICKS_DB_COLOR_PALETTE, [] );
		} else {
			self::$global_data['colorPalette'] = get_option( BRICKS_DB_COLOR_PALETTE, [] );
		}

		// Global classes
		if ( is_multisite() && BRICKS_MULTISITE_USE_MAIN_SITE_CLASSES ) {
			self::$global_data['globalClasses'] = get_blog_option( get_main_site_id(), BRICKS_DB_GLOBAL_CLASSES, [] );
		} else {
			self::$global_data['globalClasses'] = get_option( BRICKS_DB_GLOBAL_CLASSES, [] );
		}

		// Builder: Global classes locked (@since 1.4)
		if ( bricks_is_builder() ) {
			if ( is_multisite() && BRICKS_MULTISITE_USE_MAIN_SITE_CLASSES ) {
				self::$global_data['globalClassesLocked'] = get_blog_option( get_main_site_id(), BRICKS_DB_GLOBAL_CLASSES_LOCKED, [] );
			} else {
				self::$global_data['globalClassesLocked'] = get_option( BRICKS_DB_GLOBAL_CLASSES_LOCKED, [] );
			}
		}

		// Global classes categories (@since 1.9.5)
		if ( is_multisite() && BRICKS_MULTISITE_USE_MAIN_SITE_CLASSES_CATEGORIES ) {
			self::$global_data['globalClassesCategories'] = get_blog_option( get_main_site_id(), BRICKS_DB_GLOBAL_CLASSES_CATEGORIES, [] );
		} else {
			self::$global_data['globalClassesCategories'] = get_option( BRICKS_DB_GLOBAL_CLASSES_CATEGORIES, [] );
		}

		$default_pseudo_classes = [
			':hover',
			':active',
			':focus',
		];

		if ( is_multisite() && BRICKS_MULTISITE_USE_MAIN_SITE_CLASSES ) {
			self::$global_data['pseudoClasses'] = get_blog_option( get_main_site_id(), BRICKS_DB_PSEUDO_CLASSES, $default_pseudo_classes );
		} else {
			self::$global_data['pseudoClasses'] = get_option( BRICKS_DB_PSEUDO_CLASSES, $default_pseudo_classes );
		}

		// Global elements
		if ( is_multisite() && BRICKS_MULTISITE_USE_MAIN_SITE_GLOBAL_ELEMENTS ) {
			self::$global_data['elements'] = get_blog_option( get_main_site_id(), BRICKS_DB_GLOBAL_ELEMENTS, [] );
		} else {
			self::$global_data['elements'] = get_option( BRICKS_DB_GLOBAL_ELEMENTS, [] );
		}

		// Global settings
		self::$global_data['settings'] = get_option( BRICKS_DB_GLOBAL_SETTINGS, [] );

		// Remove slashes from custom CSS & JS
		if ( is_array( self::$global_data['settings'] ) ) {
			self::$global_data['settings'] = stripslashes_deep( self::$global_data['settings'] );
		}

		// Set global gettings
		self::$global_settings = self::$global_data['settings'];

		// Adobe fonts: If project ID set (@since 1.7.1)
		if ( ! empty( self::$global_settings['adobeFontsProjectId'] ) ) {
			self::$adobe_fonts = get_option( BRICKS_DB_ADOBE_FONTS, [] );
		}
	}

	/**
	 * Set page data needed for AJAX calls (builder)
	 *
	 * @since 1.3
	 */
	public static function set_ajax_page_data() {
		if (
			! bricks_is_ajax_call() ||
			empty( $_POST['action'] ) ||
			strpos( $_POST['action'], 'bricks_' ) !== 0
		) {
			return;
		}

		// In the "bricks_regenerate_css_file" ajax call, the post ID is set in the "data" property
		$post_id = isset( $_POST['postId'] ) ? intval( $_POST['postId'] ) : ( isset( $_POST['data'] ) && is_numeric( $_POST['data'] ) ? intval( $_POST['data'] ) : 0 );

		self::$page_data['original_post_id'] = $post_id;
		self::$page_data['post_id']          = $post_id;

		/**
		 * Set current page type
		 *
		 * Currently in AJAX calls set it to empty string (can be improved in the future).
		 *
		 * @since 1.8
		 */
		self::$page_data['current_page_type'] = '';

		// Check for template preview post ID
		$template_preview_post_id = Helpers::get_template_setting( 'templatePreviewPostId', $post_id );

		self::$page_data['preview_or_post_id'] = empty( $template_preview_post_id ) ? $post_id : $template_preview_post_id;

		/**
		 * Set current page type if the this is bricks template
		 *
		 * Helpers::is_bricks_template() and Helpers::get_queried_object() rely on this.
		 *
		 * @since 1.9.5
		 */
		if ( Helpers::is_bricks_preview() && get_post_type( $post_id ) === BRICKS_DB_TEMPLATE_SLUG ) {
			self::$page_data['current_page_type'] = 'post';
		}
	}

	/**
	 * Get page data from post meta
	 *
	 * @since 1.0
	 */
	public static function set_page_data( $post_id = 0 ) {
		if ( ! $post_id || is_object( $post_id ) ) {
			$post_id = get_the_ID();
		}

		/**
		 * Frontend: Current page is not a single post page
		 *
		 * E.g.: archive, search results, author page, etc.
		 *
		 * To get the user_id on the author page, we need to get the queried object ID.
		 *
		 * @since 1.7.1
		 */
		if ( ! is_singular() && ! bricks_is_builder_call() ) {
			$post_id = get_queried_object_id();
		}

		// Home: Set post ID to posts page
		if ( is_home() ) {
			$post_id = get_option( 'page_for_posts' );
		}

		// NOTE: Undocumented
		$post_id = apply_filters( 'bricks/builder/data_post_id', $post_id );

		// @since 1.8 - Set current page type
		self::$page_data['current_page_type'] = apply_filters( 'bricks/builder/current_page_type', self::get_current_page_type( get_queried_object() ) );

		// Keep $original_post_id integrity. set_page_data() also runs on Assets::generate_inline_css() for inner templates
		self::$page_data['original_post_id'] = ! empty( self::$page_data['original_post_id'] ) ? self::$page_data['original_post_id'] : $post_id;

		// $preview_or_post_id gets populated with template preview post ID OR original post ID
		$template_preview_post_id = get_post_type( self::$page_data['original_post_id'] ) === BRICKS_DB_TEMPLATE_SLUG ? Helpers::get_template_setting( 'templatePreviewPostId', self::$page_data['original_post_id'] ) : 0;

		self::$page_data['preview_or_post_id'] = empty( $template_preview_post_id ) ? self::$page_data['original_post_id'] : $template_preview_post_id;

		self::$page_data['post_id'] = $post_id;

		// Page header
		$page_header               = self::get_data( $post_id, 'header' );
		self::$page_data['header'] = is_array( $page_header ) && count( $page_header ) ? $page_header : [];

		// Page content
		$page_content               = self::get_data( $post_id, 'content' );
		self::$page_data['content'] = is_array( $page_content ) && count( $page_content ) ? $page_content : [];

		// Page footer
		$page_footer               = self::get_data( $post_id, 'footer' );
		self::$page_data['footer'] = is_array( $page_footer ) && count( $page_footer ) ? $page_footer : [];

		/**
		 * Page settings
		 *
		 * Builder: Use $post_id
		 * Frontend: Use active template ID
		 *
		 * @see #86bx4t5v3
		 */
		$page_settings_id = $post_id;

		if ( ! bricks_is_builder() && ! empty( self::$active_templates['content'] ) ) {
			$page_settings_id = self::$active_templates['content'];
		}

		$page_settings = get_post_meta( $page_settings_id, BRICKS_DB_PAGE_SETTINGS, true );

		self::$page_data['settings'] = is_array( $page_settings ) && count( $page_settings ) ? $page_settings : [];

		/**
		 * Remove slashes from custom JS
		 *
		 * @since 1.9.5: Skip page settings custom CSS as its not auto-escaped as the global settings, which are stored in the options table
		 */
		if ( is_array( self::$page_data['settings'] ) ) {
			foreach ( self::$page_data['settings'] as $key => $value ) {
				if ( $key === 'customCss' ) {
					continue;
				}

				self::$page_data['settings'][ $key ] = stripslashes_deep( $value );
			}
		}

		// Set page gettings
		self::$page_settings = self::$page_data['settings'];
	}

	/**
	 * Return current page type, not considering AJAX calls
	 *
	 * @param object $object Queried object.
	 *
	 * @since 1.8
	 */
	public static function get_current_page_type( $object ) {
		if ( is_search() ) {
			return 'search';
		}

		if ( is_404() ) {
			return '404';
		}

		if ( is_a( $object, 'WP_Post' ) ) {
			return 'post';
		}

		if ( is_a( $object, 'WP_Term' ) ) {
			return 'term';
		}

		if ( is_a( $object, 'WP_User' ) ) {
			return 'user';
		}

		if ( is_a( $object, 'WP_Post_Type' ) ) {
			return 'archive';
		}

		if ( is_object( $object ) ) {
			return strtolower( get_class( $object ) );
		}
	}

	/**
	 * Recursively retrieve nested template data
	 *
	 * @return array
	 *
	 * @since 1.9.1
	 */
	public static function get_nested_template_data( $bricks_data = [] ) {
		// If the input is not an array, return it as is
		if ( ! is_array( $bricks_data ) ) {
			return $bricks_data;
		}

		// STEP: Find template elements in the array
		$found_template_elements = array_filter(
			$bricks_data,
			function( $element ) {
				return isset( $element['name'] ) && in_array( $element['name'], [ 'template' ] );
			}
		);

		// If no template elements found, return the original array
		if ( empty( $found_template_elements ) ) {
			return $bricks_data;
		}

		// STEP: Retrieve nested template data from the $found_template_elements
		$nested_template_data = [];

		foreach ( $found_template_elements as $element ) {
			$template_id = isset( $element['settings']['template'] ) ? $element['settings']['template'] : false;

			// If no template ID found, skip to the next element
			if ( ! $template_id ) {
				continue;
			}

			// Retrieve the template data using the template ID
			$template_data = get_post_meta( $template_id, BRICKS_DB_PAGE_CONTENT, true );

			// If template data found, merge it into the $nested_template_data
			if ( ! empty( $template_data ) && is_array( $template_data ) ) {
				// Store the template data in $nested_template_data
				$nested_template_data = array_replace_recursive( $nested_template_data, $template_data );
				// Store the template data in the page data (might be used later)
				self::$page_data['template_data'][ $template_id ] = $template_data;
			}
		}

		// STEP: Maybe there are nested template element inside $nested_template_data (recursion)
		$recursive_nested_template_data = self::get_nested_template_data( $nested_template_data );

		if ( ! empty( $recursive_nested_template_data ) ) {
			$bricks_data = array_merge_recursive( $bricks_data, $recursive_nested_template_data );
		}

		return $bricks_data;
	}

	/**
	 * Retrieve template data from template elements
	 *
	 * @since 1.9.1
	 */
	public static function get_template_elements_data( $elements = [] ) {
		// If no elements provided, return an empty array
		if ( empty( $elements ) ) {
			return [];
		}

		// Initialize the array to store nested template data
		$nested_template_data = [];

		// Process each element to retrieve nested templates
		foreach ( $elements as $element ) {
			$template_id = isset( $element['settings']['template'] ) ? $element['settings']['template'] : false;

			// If no template ID found, skip to the next element
			if ( ! $template_id ) {
				continue;
			}

			// Retrieve the template data using the template ID
			$template_data = self::get_data( $template_id );

			// If template data found, merge it into the $nested_template_data
			if ( ! empty( $template_data ) && is_array( $template_data ) ) {
				$nested_template_data = array_replace_recursive( $nested_template_data, $template_data );
				// Store the template data in the page data
				self::$page_data['template_data'][ $template_id ] = $template_data;
			}
		}

		return $nested_template_data;
	}

	/**
	 * Get elements sequence in builder
	 *
	 * This is used to determine the order of elements in the builder.
	 *
	 * @since 1.9.1
	 *
	 * @return array (sequence of ids)
	 */
	public static function elements_sequence_in_builder( $elements ) {
		$top_level_elements = [];

		// Get top level elements
		foreach ( $elements as $element ) {
			if ( ! isset( $element['parent'] ) || empty( $element['parent'] ) ) {
				$top_level_elements[] = $element;
			}
		}

		$sequence_of_ids = [];

		// Get sequence of ids starting from top level elements
		foreach ( $top_level_elements as $element ) {
			$sequence_of_ids[] = $element['id'];
			$sequence_of_ids   = array_merge( $sequence_of_ids, self::get_ids_by_children( $elements, $element ) );
		}

		return $sequence_of_ids;
	}

	/**
	 * Get sequence of ids by children
	 *
	 * @since 1.9.1
	 */
	public static function get_ids_by_children( $elements, $parent_element ) {
		$sequence     = [];
		$children_ids = isset( $parent_element['children'] ) ? $parent_element['children'] : false;
		// Follow the order of the children
		foreach ( $children_ids as $child_id ) {
			$sequence[]    = $child_id;
			$child_element = self::get_element_by_id( $child_id, $elements );

			if ( is_array( $child_element ) && isset( $child_element['children'] ) && ! empty( $child_element['children'] ) ) {
				$sequence = array_merge( $sequence, self::get_ids_by_children( $elements, $child_element ) ); // Recursion
			}
		}

		return $sequence;
	}

	/**
	 * Get the element by id from elements array
	 *
	 * @since 1.9.1
	 */
	public static function get_element_by_id( $element_id, $elements ) {
		$element = array_filter(
			$elements,
			function( $element ) use ( $element_id ) {
				return $element['id'] === $element_id;
			}
		);

		if ( ! empty( $element ) ) {
			return array_shift( $element );
		}

		return false;
	}
}
