<?php
namespace Bricks\Integrations\Dynamic_Data;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Providers {
	/**
	 * Holds the providers
	 *
	 * @var array
	 */
	private $providers_keys = [];

	/**
	 * Holds the providers instances
	 *
	 * @var array
	 */
	private $providers = [];

	/**
	 * Holds the tags instances
	 *
	 * @var array
	 */
	private $tags = [];

	public function __construct( $providers ) {
		$this->providers_keys = $providers;
	}

	public static function register( $providers = [] ) {
		$instance = new self( $providers );

		// Priority set to 10000 due to CMB2 priority
		add_action( 'init', [ $instance, 'register_providers' ], 10000 );

		// Register providers during WP REST API call (priority 7 to run before register_tags() on WP REST API)
		add_action( 'rest_api_init', [ $instance, 'register_providers' ], 7 );

		// Register tags before wp_enqueue_scripts (but not before wp to get the post custom fields)
		// Priority = 8 to run before Setup::init_control_options
		add_action( 'wp', [ $instance, 'register_tags' ], 8 );

		// Hook "wp" doesn't run on AJAX/REST API calls so we need this to register the tags when rendering elements (needed for Posts element) or fetching dynamic data content
		add_action( 'admin_init', [ $instance, 'register_tags' ], 8 );
		add_action( 'rest_api_init', [ $instance, 'register_tags' ], 8 );

		add_filter( 'bricks/dynamic_tags_list', [ $instance, 'add_tags_to_builder' ] );

		// Render dynamic data in builder too (when template preview post ID is set)
		add_filter( 'bricks/frontend/render_data', [ $instance, 'render' ], 10, 2 );

		add_filter( 'bricks/dynamic_data/render_content', [ $instance, 'render' ], 10, 3 );

		add_filter( 'bricks/dynamic_data/render_tag', [ $instance, 'get_tag_value' ], 10, 3 );
	}

	public function register_providers() {
		foreach ( $this->providers_keys as $provider ) {
			$classname = 'Bricks\Integrations\Dynamic_Data\Providers\Provider_' . str_replace( ' ', '_', ucwords( str_replace( '-', ' ', $provider ) ) );

			if ( $classname::load_me() ) {
				$this->providers[ $provider ] = new $classname( str_replace( '-', '_', $provider ) );
			}
		}
	}

	public function register_tags() {
		foreach ( $this->providers as $key => $provider ) {
			$this->tags = array_merge( $this->tags, $provider->get_tags() );
		}
	}

	public function get_tags() {
		return $this->tags;
	}

	/**
	 * Adds tags to the tags picker list (used in the builder)
	 *
	 * @param array $tags
	 * @return array
	 */
	public function add_tags_to_builder( $tags ) {
		$list = $this->get_tags();

		foreach ( $list as $tag ) {
			if ( isset( $tag['deprecated'] ) ) {
				continue;
			}

			$tags[] = [
				'name'  => $tag['name'],
				'label' => $tag['label'],
				'group' => $tag['group']
			];
		}

		return $tags;
	}

	/**
	 * Dynamic tag exists in $content: Replaces dynamic tag with requested data
	 *
	 * @param string  $content
	 * @param WP_Post $post
	 */
	public function render( $content, $post, $context = 'text' ) {
		/**
		 * \w: Matches any word character (alphanumeric & underscore).
		 * Equivalent to [A-Za-z0-9_]
		 * "À-ÖØ-öø-ÿ" Add the accented characters
		 * "-" Needed because some post types handles are like "my-post-type"
		 * ":" Needed for extra arguments to dynamic data tags (e.g. post_excerpt:20 or wp_user_meta:my_meta_key)
		 * "|" and "," needed for the post terms like {post_terms_post_tag:sep} where sep could be a pipe or comma
		 * "(", ")" and "'" for the function arguments of the dynamic tag {echo}
		 * "@" to support email addresses as arguments of the dynamic tag {echo} #3kazphp
		 * "u" modifier: Pattern strings are treated as UTF-8 to support Cyrillic, Arabic, etc. (@since 1.9.4)
		 *
		 * @see https://regexr.com/
		 */
		$pattern = '/{([\wÀ-ÖØ-öø-ÿ\-\s\.\/:\(\)\'@|,]+)}/u';

		// Get a list of tags to exclude from the Dynamic Data logic
		$exclude_tags = apply_filters( 'bricks/dynamic_data/exclude_tags', [] );

		/**
		 * STEP: Determine how many times we need to run the DD parser
		 *
		 * Previously we ran the parser by counting the number of open curly braces in the content. (@since 1.8)
		 * But this is not reliable because the content could contain curly braces in the code elements or any shortcodes.
		 * Causing the website to load extremely slow.
		 *
		 * @since 1.8.2 (#862jyyryg)
		 */
		// Get all registered tags except the excluded ones.
		// Example: [0 => "post_title", 1 => "woo_product_price", 2 => "echo"]
		$registered_tags = array_filter(
			array_keys( $this->get_tags() ),
			function( $tag ) use ( $exclude_tags ) {
				return ! in_array( $tag, $exclude_tags );
			}
		);

		$dd_tags_in_content = [];
		$dd_tags_found      = [];

		// Find all dynamic data tags in the content
		preg_match_all( $pattern, $content, $dd_tags_in_content );

		if ( ! empty( $dd_tags_in_content[1] ) ) {
			$dd_tags_in_content = $dd_tags_in_content[1];

			/**
			 * $dd_tags_in_content only matches the pattern, but some codes from Code element could match the pattern too.
			 * Example: function test() { return 'Hello World'; } will match the pattern, but it's not a dynamic data tag.
			 *
			 * Find all dynamic data tags in the content which starts with dynamic data tag from $registered_tags
			 * Cannot use array_in or array_intersect because $registered_tags only contains the tag name, somemore tags could have filters like {echo:my_function( 'Hello World' )
			 *
			 * Example: $registered_tags    = [0 => "post_title", 1 => "woo_product_price", 2 => "echo"]
			 * Example: $dd_tags_in_content = [0 => "post_title", 1 => "woo_product_price:value", 2 => "echo:my_function('Hello World')"]
			 */
			$dd_tags_found = array_filter(
				$dd_tags_in_content,
				function( $tag ) use ( $registered_tags ) {
					foreach ( $registered_tags as $all_tag ) {
						/**
						 * Skip WP custom field (starts with cf_)
						 *
						 * As Provider_Wp->get_site_meta_keys() can cause performance issues on larger sites
						 *
						 * @see #862k3f2md
						 * @since 1.8.3
						 */
						if ( strpos( $tag, 'cf_' ) === 0 ) {
							return true;
						}

						if ( strpos( $tag, $all_tag ) === 0 ) {
							return true;
						}
					}
					return false;
				}
			);
		}

		// Get the count of found dynamic data tags
		$dd_tag_count = count( $dd_tags_found );

		// STEP: Run the parser based on the count of found dynamic data tags
		for ( $i = 0; $i < $dd_tag_count; $i++ ) {
			preg_match_all( $pattern, $content, $matches );

			if ( empty( $matches[0] ) ) {
				return $content;
			}

			foreach ( $matches[1] as $key => $match ) {
				$tag = $matches[0][ $key ];

				if ( in_array( $match, $exclude_tags ) ) {
					continue;
				}

				$value = $this->get_tag_value( $match, $post, $context );

				// Value is a WP_Error: Set value to false to avoid error in builder (#862k4cyc8)
				if ( is_a( $value, 'WP_Error' ) ) {
					$value = false;
				}

				if ( $value && strpos( $value, '{echo:' ) !== false ) {
					continue;
				}

				$content = str_replace( $tag, $value, $content );
			}
		}

		return $content;
	}

	/**
	 * Get the value of a dynamic data tag
	 *
	 * @param string  $tag without curly brackets {}.
	 * @param WP_Post $post The post object.
	 * @param string  $context text, link, image.
	 */
	public function get_tag_value( $tag, $post, $context = 'text' ) {
		// Keep the original tag to be used later on in case we don't replace nonexistent tags
		$original_tag = $tag;

		$tags = $this->get_tags();

		// Check if tag has arguments
		$args = strpos( $tag, ':' ) > 0 ? explode( ':', $tag ) : [];

		if ( ! empty( $args ) ) {
			$tag = array_shift( $args );
		}

		if ( ! array_key_exists( $tag, $tags ) ) {
			// Last resort: Try to get field content if it is a WordPress custom field (@since 1.5.5)
			if ( strpos( $tag, 'cf_' ) === 0 ) {
				$meta_key = substr( $tag, 3 );

				$post_id = isset( $post->ID ) ? $post->ID : 0;

				// Get the field value
				$value = get_post_meta( $post_id, $meta_key, true );

				// NOTE: Undocumented
				$value = apply_filters( "bricks/dynamic_data/meta_value/$meta_key", $value, $post );

				$filters = $this->providers['wp']->get_filters_from_args( $args );

				// Format the value based on the filters and context
				return $this->providers['wp']->format_value_for_context( $value, $tag, $post_id, $filters, $context );
			}

			/**
			 * If true, Bricks replaces not existing DD tags with an empty string
			 *
			 * true caused unwanted replacement of inline <script> & <style> tag data.
			 *
			 * Set to false @since 1.4 to render all non-matching DD tags (#2ufh0uf)
			 *
			 * https://academy.bricksbuilder.io/article/filter-bricks-dynamic_data-replace_nonexistent_tags/
			 */
			$replace_tag = apply_filters( 'bricks/dynamic_data/replace_nonexistent_tags', false );

			return $replace_tag ? '' : '{' . $original_tag . '}';
		}

		$provider = $tags[ $tag ]['provider'];

		return $this->providers[ $provider ]->get_tag_value( $tag, $post, $args, $context );
	}

	public static function render_tag( $tag = '', $post_id = 0, $context = 'text', $args = [] ) {
		// Support for dynamic data picker and input text (@since 1.5)
		$tag = ! empty( $tag['name'] ) ? $tag['name'] : (string) $tag;

		$tag = trim( $tag );

		$tag = str_replace( [ '{', '}' ], '', $tag );

		// Image is user avatar (get_avatar_url): Set the size
		if ( $context === 'image' && in_array( $tag, [ 'wp_user_picture', 'author_avatar' ] ) && isset( $args['size'] ) ) {
			$all_image_sizes = \Bricks\Setup::get_image_sizes();

			if ( ! empty( $all_image_sizes[ $args['size'] ]['width'] ) ) {
				$tag = $tag . ':' . abs( $all_image_sizes[ $args['size'] ]['width'] );
			}
		}

		$post = get_post( $post_id );

		return apply_filters( 'bricks/dynamic_data/render_tag', $tag, $post, $context );
	}

	public static function render_content( $content, $post_id = 0, $context = 'text' ) {
		// Return: Content is a flat array (Example: 'user_role' element conditions @since 1.5.6)
		if ( is_array( $content ) && isset( $content[0] ) ) {
			return $content;
		}

		// Support for dynamic data picker and input text (@since 1.5)
		$content = ! empty( $content['name'] ) ? $content['name'] : (string) $content;

		// Return: $content doesn't contain opening DD tag character '{' (@since 1.5)
		if ( strpos( $content, '{' ) === false ) {
			return $content;
		}

		// Strip slashes for DD "echo" function to allow DD preview render in builder (@since 1.5.3)
		if ( strpos( $content, '{echo:' ) !== false ) {
			$content = stripslashes( $content );
		}

		$post_id = empty( $post_id ) ? get_the_ID() : $post_id;
		$post    = get_post( $post_id );

		return apply_filters( 'bricks/dynamic_data/render_content', $content, $post, $context );
	}

	public static function get_dynamic_tags_list() {
		// NOTE: Undocumented. This allows the dynamic data providers to add their tags to the builder
		$tags = apply_filters( 'bricks/dynamic_tags_list', [] );

		return $tags;
	}
}
