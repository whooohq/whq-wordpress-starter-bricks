<?php
namespace Bricks\Integrations\Dynamic_Data\Providers;

use Bricks\Helpers;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

abstract class Base implements Provider_Interface {

	/**
	 * Provider name
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * Holds the Tags instances for this provider's tags
	 *
	 * @var array
	 */
	public $tags = [];

	/**
	 * Holds the tags to be used in the Query Loop control (e.g. Repeater, Relationship...)
	 *
	 * @var array
	 */
	public $loop_tags = [];

	/**
	 * The Contructor
	 *
	 * @param string $name Provider name.
	 */
	public function __construct( $name ) {
		$this->name = $name;

		// Add the supported fields to the Query type control
		add_filter( 'bricks/setup/control_options', [ $this, 'add_control_options' ], 10, 1 );

		// Calculate and set the query results
		add_filter( 'bricks/query/run', [ $this, 'set_loop_query' ], 10, 2 );

		// Manage the loop object during loop iteration
		add_filter( 'bricks/query/loop_object', [ $this, 'set_loop_object' ], 10, 3 );
	}

	/**
	 * Useful to the check if the provider should be loaded or not.
	 *
	 * @return boolean
	 */
	public static function load_me() {
		return true;
	}

	/**
	 * This method loads the tags of this provider
	 *
	 * @return void
	 */
	public function register_tags() {}


	/**
	 * Getter for the list of tags instance of this provider
	 *
	 * @return array
	 */
	public function get_tags() {
		if ( empty( $this->tags ) ) {
			$this->register_tags();
		}
		return $this->tags;
	}

	/**
	 * Get the tag value based on the context
	 *
	 * @param string  $tag
	 * @param WP_Post $post
	 * @param array   $args
	 * @param string  $context text, link, image, media.
	 * @return array|string
	 */
	public function get_tag_value( $tag, $post, $args, $context ) {
		return '';
	}

	/**
	 * Calculate dynamic data filters according to the args received
	 *
	 * @param array $args
	 * @return array
	 */
	public function get_filters_from_args( $args ) {
		$filters = [
			'object_type' => '',
		];

		if ( empty( $args ) || ! is_array( $args ) ) {
			return $filters;
		}

		foreach ( $args as $arg ) {
			// Trim number of words or avatar size (in px)
			if ( is_numeric( $arg ) ) {
				$filters['num_words'] = $arg;
			}

			// Add context to the archive title
			elseif ( $arg == 'context' || $arg == 'prefix' ) {
				$filters['add_context'] = true;
			}

			// Output as image tag
			elseif ( $arg == 'image' ) {
				$filters['image'] = true;
			}

			// Wrap value in a link
			elseif ( $arg == 'link' ) {
				$filters['link'] = true;
			}

			// Open link in newTab
			elseif ( $arg == 'newTab' ) {
				$filters['newTab'] = true;
			}

			// Create a callable link
			elseif ( $arg == 'tel' ) {
				$filters['tel'] = true;
			}

			/**
			 * Return value instead of label
			 *
			 * Useful for dynamic data element conditions like MB checkbox_list, ACF true_false, etc. where the user can specify the value & label.
			 *
			 * @since 1.5.7
			 */
			elseif ( $arg == 'value' ) {
				$filters['value'] = true;
			}

			/**
			 * Return raw value (skip parsing DD tag)
			 *
			 * Useful to skip rendering one specific DD tag
			 *
			 * @since 1.6
			 */
			elseif ( $arg == 'raw' ) {
				$filters['raw'] = true;
			}

			/**
			 * Return URL
			 *
			 * Useful for field type 'file'
			 *
			 * NOTE: Undocumented
			 *
			 * @since 1.6
			 */
			elseif ( $arg == 'url' ) {
				$filters['url'] = true;
			}

			/**
			 * Keep formatting
			 *
			 * Useful for dynamic data with HTML
			 *
			 * Example: {post_excerpt:format}
			 *
			 * @since 1.6.2
			 */
			elseif ( $arg == 'format' ) {
				$filters['format'] = true;
			}

			/**
			 * Return plain text
			 *
			 * Strip HTML tags
			 *
			 * Example: {post_terms_category:plain}
			 *
			 * @since 1.7.2
			 */
			elseif ( $arg == 'plain' ) {
				$filters['plain'] = true;
			}

			/**
			 * Return array value
			 *
			 * Useful for dynamic data with array
			 *
			 * Example : {acf_link_field:array_value|title}
			 *
			 * @since 1.8
			 */
			elseif ( strpos( $arg, 'array_value|' ) === 0 ) {
				$filters['array_value'] = str_replace( 'array_value|', '', $arg );
			}

			// Default key: used for 1) user meta_key, 2) post terms separator or 3) image size, 4) date format
			else {
				$filters['meta_key'][] = $arg;
			}
		}

		// Note: Use case where the date format contains a colon. E.g. "{post_date:jS F Y h:ia}"
		if ( isset( $filters['meta_key'] ) ) {
			$filters['meta_key'] = implode( ':', $filters['meta_key'] );
		}

		return $filters;
	}

	/**
	 * Format the dynamic data value according to the context
	 *
	 * @param string|integer $value
	 * @param string         $tag
	 * @param int            $post_id
	 * @param array          $filters
	 * @param string         $context
	 * @return string|array
	 */
	public function format_value_for_context( $value, $tag, $post_id, $filters, $context = 'text' ) {
		// Return unparsed DD tag (@since 1.6)
		if ( isset( $filters['raw'] ) ) {
			return '{' . $tag . '}';
		}

		$object_type = ! empty( $filters['object_type'] ) ? $filters['object_type'] : '';

		switch ( $context ) {
			case 'text':
				$value = is_array( $value ) ? $value : (array) $value;

				foreach ( $value as $key => $item ) {
					$value[ $key ] = $this->format_value_for_text( $item, $tag, $post_id, $filters );
				}

				$sep = isset( $filters['separator'] ) ? $filters['separator'] : ', ';

				// NOTE: Undocumented.
				$sep = apply_filters( 'bricks/dynamic_data/text_separator', $sep, $tag, $post_id, $filters );

				$value = implode( $sep, $value );

				// Skip sanitize if set in filters or if object_type is 'media' (@see #31wetpu)
				$skip_sanitize = isset( $filters['skip_sanitize'] ) || in_array( $object_type, [ 'media' ] );

				// Sanitize
				if ( ! $skip_sanitize ) {
					add_filter( 'wp_kses_allowed_html', [ $this, 'expand_allowed_html' ], 10, 2 );

					$value = wp_kses_post( $value );

					remove_filter( 'wp_kses_allowed_html', [ $this, 'expand_allowed_html' ], 10, 2 );
				}
				break;

			case 'link':
				// This is a single link field. If field returns multiple, choose the first one.
				if ( is_array( $value ) ) {
					$value = current( $value );
				}

				$filter_meta_key = isset( $filters['meta_key'] ) ? $filters['meta_key'] : '';

				// Retrieve the image URL
				if ( ! empty( $filters['image'] ) || $tag === 'featured_image' || $tag === 'featured_image_tag' ) {
					$image_size = $filter_meta_key ? $filter_meta_key : 'full';

					$value = wp_get_attachment_image_url( $value, $image_size );
				} elseif ( $object_type === 'media' ) {
					$value = wp_get_attachment_url( $value );
				}

				// Link to email (mailto: prefix if 'text' filter not provided)
				elseif ( is_email( $value ) ) {
					$value = $filter_meta_key === 'text' ? trim( $value ) : 'mailto:' . trim( $value );
				}

				// Create a callable link
				elseif ( ! empty( $filters['tel'] ) ) {
					$value = 'tel:' . trim( $value );
				}

				// Link to author / user archive page
				elseif ( strpos( $value, 'http' ) !== 0 && ( strpos( $tag, 'author_' ) === 0 || strpos( $tag, 'wp_user_' ) === 0 ) ) {
					if ( $tag !== 'author_avatar' && $tag !== 'wp_user_picture' ) {
						$user_id = strpos( $tag, 'wp_user_' ) === 0 ? get_current_user_id() : ( isset( $post->post_author ) ? $post->post_author : 0 );

						$value = get_author_posts_url( $user_id );
					}
				} elseif ( $object_type === 'post' ) {
					$value = get_permalink( $value );
				} elseif ( $object_type === 'user' ) {
					$value = get_author_posts_url( $value );
				} elseif ( $object_type === 'term' ) {
					$taxonomy = isset( $filters['taxonomy'] ) ? $filters['taxonomy'] : '';
					$value    = get_term_link( (int) $value, $taxonomy );
				}

				break;

			case 'image':
				$value = array_filter( (array) $value );
				$value = is_array( $value ) ? $value : [ $value ];
				break;

			case 'media':
				$value = is_array( $value ) ? $value : (array) $value;

				foreach ( $value as $key => $media_id ) {
					if ( is_numeric( $media_id ) ) {
						$value[ $key ] = [
							'id'  => $media_id,
							'url' => wp_get_attachment_url( $media_id ),
						];
					} else {
						$value[ $key ] = [
							'url' => $media_id,
						];
					}
				}
				break;
		}

		// Stripped tags if :plain filter is set (@since 1.7.2)
		if ( isset( $filters['plain'] ) ) {
			$value = wp_strip_all_tags( $value );
		}

		// NOTE: Undocumented
		$value = apply_filters( 'bricks/dynamic_data/format_value', $value, $tag, $post_id, $filters, $context );

		return $value;
	}

	/**
	 * Format value for the text context
	 *
	 * @param string|integer $value
	 * @param string         $tag
	 * @param int            $post_id
	 * @param array          $filters
	 *
	 * @return string
	 */
	public function format_value_for_text( $value, $tag, $post_id, $filters ) {
		$object_type = ! empty( $filters['object_type'] ) ? $filters['object_type'] : '';
		$object      = false;

		/**
		 * Plain URL for "file" field type, etc. (@since 1.6)
		 *
		 * Woo Phase 3 - If $filters['url'] use on different tag like {woo_my_account_endpoint}, it will be force went into this condition.
		 * Check $value is numeric or a post object wp_get_attachment_url accept only numeric value, get_permalink accept numeric or post object
		 */
		if ( ! empty( $filters['url'] ) && ( is_numeric( $value ) || is_a( $value, 'WP_Post' ) ) ) {
			return ( $object_type === 'media' ) ? wp_get_attachment_url( $value ) : get_permalink( $value );
		}

		switch ( $object_type ) {
			case 'media':
			case 'post':
				$object = get_post( $value );
				$value  = get_the_title( $value );
				break;

			case 'term':
				if ( ! empty( $filters['object'] ) ) {
					$object = $filters['object'];
				} elseif ( isset( $filters['taxonomy'] ) ) {
					$object = get_term_by( 'id', $value, $filters['taxonomy'] );
					$value  = is_a( $object, 'WP_Term' ) ? $object->name : $value;
				}
				break;

			case 'user':
				$object = get_user_by( 'id', $value );
				$value  = $object ? $object->display_name : $value;
				break;
		}

		// Trim number of words
		// @since 1.6.2 - Check for "trimmed" filter to avoid double trimming from {post_excerpt} or {woo_product_excerpt}
		if ( ! empty( $filters['num_words'] ) && ! in_array( $tag, [ 'author_avatar', 'wp_user_picture' ] ) && empty( $filters['trimmed'] ) ) {
			// Support keeping HTML tags :format @since 1.9.2
			$keep_html = isset( $filters['format'] );
			$value     = Helpers::trim_words( $value, $filters['num_words'], '', $keep_html );
		}

		// Transform image into anchor tag
		if ( ! empty( $filters['image'] ) ) {
			$image_size = ! empty( $filters['meta_key'] ) ? $filters['meta_key'] : 'thumbnail';

			$value = ! empty( $object->ID ) ? wp_get_attachment_image( $object->ID, $image_size, false, [] ) : '';
		}

		if ( in_array( $object_type, [ 'date', 'datetime' ] ) ) {
			// Skip formatting if filter :timestamp (@since 1.9.3)
			if ( isset( $filters['meta_key'] ) && $filters['meta_key'] === 'timestamp' ) {
				return $value;
			}

			if ( isset( $filters['meta_key'] ) ) {
				if ( $filters['meta_key'] == 'human_time_diff' ) {
					/**
					 * human_time_diff
					 *
					 * @see https://developer.wordpress.org/reference/functions/human_time_diff/
					 *
					 * @since 1.9.3: Use time zone as set in WordPress settings
					 */
					$value = human_time_diff( $value, current_time( 'timestamp' ) );
				} else {
					$date_format = $filters['meta_key'];
				}
			} else {
				$date_format = 'datetime' == $object_type ? get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) : get_option( 'date_format' );
			}

			$value = isset( $date_format ) ? date_i18n( $date_format, $value ) : $value;

			// Apply core hooks so plugins could tweak date and time, if date_format is not provided in the filters
			if ( ! isset( $filters['meta_key'] ) && in_array( $tag, [ 'post_date', 'post_modified' ] ) ) {
				$value = apply_filters( 'get_the_date', $value, $date_format, get_post( $post_id ) );
			} elseif ( ! isset( $filters['meta_key'] ) && in_array( $tag, [ 'post_time' ] ) ) {
				$value = apply_filters( 'get_the_time', $value, $date_format, get_post( $post_id ) );
			}
		}

		// Render full HTML anchor tag if "link" filter is set and value is not empty
		if ( ! empty( $filters['link'] ) && ! empty( $value ) ) {
			// New tab (target="_blank")
			$target = ! empty( $filters['newTab'] ) ? ' target="_blank"' : '';

			// Email link
			if ( is_email( $value ) ) {
				// translators: %s: email address
				$aria_label = sprintf( __( 'Send email to %s', 'bricks' ), $value );

				$value = '<a href="mailto:' . trim( $value ) . '" aria-label="' . esc_attr( $aria_label ) . '">' . esc_html( $value ) . '</a>';
			}

			// Website URL
			elseif ( strpos( $value, 'http' ) === 0 ) {
				// translators: %s: website URL
				$aria_label = sprintf( __( 'Visit the website %s', 'bricks' ), $value );

				$value = '<a href="' . esc_url( $value ) . '" aria-label="' . esc_attr( $aria_label ) . '"' . $target . '>' . esc_html( $value ) . '</a>';
			}

			// User/author link
			elseif ( strpos( $tag, 'author_' ) === 0 || strpos( $tag, 'wp_user_' ) === 0 || $object_type === 'user' ) {
				if ( strpos( $tag, 'wp_user_' ) === 0 ) {
					$user_id = get_current_user_id();
				} elseif ( strpos( $tag, 'author_' ) === 0 ) {
					$post    = get_post( $post_id );
					$user_id = isset( $post->post_author ) ? $post->post_author : 0;
				} elseif ( $object_type === 'user' ) {
					$user_id = isset( $object->ID ) ? $object->ID : 0;
				}

				// translators: %s: author name
				$aria_label = sprintf( __( 'Read more about %s', 'bricks' ), get_the_author_meta( 'display_name', $user_id ) );

				$label = in_array( $tag, [ 'wp_user_picture', 'author_avatar' ] ) ? wp_kses_post( $value ) : esc_html( $value );

				$value = '<a href="' . esc_url( get_author_posts_url( $user_id ) ) . '" aria-label="' . esc_attr( $aria_label ) . '"' . $target . '>' . $label . '</a>';
			}

			// Link to an image or attachment
			elseif ( $object && $object_type === 'media' ) {
				// When using featured_image or featured_image_tag. aria-label should point to the post title and the url will be the post permalink
				if ( in_array( $tag, [ 'featured_image', 'featured_image_tag' ] ) ) {
					// translators: %s: post title
					$aria_label = sprintf( __( 'View %s', 'bricks' ), get_the_title( $post_id ) );

					$url = get_permalink( $post_id );
				}

				// Otherwise, assume this link is for downloading the attachment. aria-label should point to the attachment title and the url will be the attachment permalink
				else {
					$filename = get_the_title( $object->ID );

					// translators: %s: attachment title
					$aria_label = sprintf( __( 'Download %s', 'bricks' ), $filename );

					$url = wp_get_attachment_url( $object->ID );
				}

				$value = '<a href="' . esc_url( $url ) . '" aria-label="' . esc_attr( $aria_label ) . '"' . $target . '>' . $value . '</a>';
			}

			// Object is a WP_Term
			elseif ( $object_type === 'term' ) {
				$link = is_a( $object, 'WP_Term' ) ? get_term_link( $object ) : '';

				$value = '<a href="' . esc_url( $link ) . '" rel="tag"' . $target . '>' . $value . '</a>';
			}

			// {post_title:link} or {read_more} or 'post' == $object_type
			else {
				$post_id = $object_type === 'post' ? $object->ID : $post_id;

				// translators: %s: post title
				$aria_label = sprintf( __( 'Read more about %s', 'bricks' ), get_the_title( $post_id ) );

				$value = '<a href="' . get_permalink( $post_id ) . '" aria-label="' . esc_attr( $aria_label ) . '"' . $target . '>' . $value . '</a>';
			}
		}

		return $value;
	}

	/**
	 * Expand the wp_kses_post sanitization function to allow iframe HTML tags
	 *
	 * @param array  $tags
	 * @param string $context
	 * @return array
	 */
	public function expand_allowed_html( $tags, $context ) {
		if ( ! isset( $tags['iframe'] ) ) {
			$tags['iframe'] = [
				'src'             => true,
				'height'          => true,
				'width'           => true,
				'frameborder'     => true,
				'allowfullscreen' => true,
				'title'           => true,
			];
		}

		return $tags;
	}

	/**
	 * Adds the loop fields to the Query Loop builder
	 *
	 * @param array $control_options
	 * @return array
	 */
	public function add_control_options( $control_options ) {
		if ( empty( $this->loop_tags ) ) {
			return $control_options;
		}

		foreach ( $this->loop_tags as $name => $tag ) {
			$control_options['queryTypes'][ $name ] = $tag['label'];
		}

		return $control_options;
	}

	/**
	 * Should be overridden by the provider if needed
	 *
	 * @param array $results
	 * @param Query $query
	 * @return array
	 */
	public function set_loop_query( $results, $query ) {
		return $results;
	}

	/**
	 * Should be overridden by the provider if needed
	 *
	 * @param array  $loop_object
	 * @param string $loop_key
	 * @param Query  $query
	 * @return array
	 */
	public function set_loop_object( $loop_object, $loop_key, $query ) {
		return $loop_object;
	}

	/**
	 * Returns the value of a specific array key
	 *
	 * @param any   $value
	 * @param array $filters
	 *
	 * @return string
	 *
	 * @since 1.8
	 */
	public function return_array_value( $value, $filters ) {
		if ( ! is_array( $filters ) || ! isset( $filters['array_value'] ) ) {
			return '';
		}

		$key = $filters['array_value'];

		$value = isset( $value[ $key ] ) ? $value[ $key ] : '';

		// If the value is not a string, could be an object, array, etc. Return as json string so it can be output properly
		return ! is_string( $value ) ? wp_json_encode( $value ) : $value;
	}

}
