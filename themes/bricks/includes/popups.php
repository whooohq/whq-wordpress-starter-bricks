<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Popups
 *
 * @since 1.6
 */
class Popups {
	public static $controls                                   = [];
	public static $generated_template_settings_inline_css_ids = [];
	public static $looping_popup_html                         = '';
	public static $ajax_popup_contents                        = [];
	public static $looping_ajax_popup_ids                     = [];
	public static $enqueue_ajax_loader                        = false;
	private static $breakpoints                               = null;

	public function __construct() {
		// Add popups HTML to frontend
		if ( ! bricks_is_builder() ) {
			add_action( 'wp_footer', [ $this, 'render_popups' ], 10 );
		}

		self::set_controls();
	}

	public static function get_controls() {
		return self::$controls;
	}

	/**
	 * Set popup controls once initially
	 *
	 * For builder theme style & template settings panel.
	 *
	 * No need to run on hook as it does not contain any db data.
	 */
	public static function set_controls() {
		self::$controls['popupPadding'] = [
			'group' => 'popup',
			'label' => esc_html__( 'Padding', 'bricks' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'padding',
					'selector' => '&.brx-popup',
				],
			],
		];

		self::$controls['popupJustifyConent'] = [
			'group'   => 'popup',
			'label'   => esc_html__( 'Align main axis', 'bricks' ),
			'tooltip' => [
				'content'  => 'justify-content',
				'position' => 'top-left',
			],
			'type'    => 'justify-content',
			'inline'  => true,
			'exclude' => [
				'space',
			],
			'css'     => [
				[
					'property' => 'justify-content',
					'selector' => '&.brx-popup',
				],
			],
		];

		self::$controls['popupAlignItems'] = [
			'group'   => 'popup',
			'label'   => esc_html__( 'Align cross axis', 'bricks' ),
			'tooltip' => [
				'content'  => 'align-items',
				'position' => 'top-left',
			],
			'type'    => 'align-items',
			'inline'  => true,
			'exclude' => [
				'stretch',
			],
			'css'     => [
				[
					'property' => 'align-items',
					'selector' => '&.brx-popup',
				],
			],
		];

		self::$controls['popupCloseOn'] = [
			'group'       => 'popup',
			'label'       => esc_html__( 'Close on', 'bricks' ),
			'type'        => 'select',
			'inline'      => true,
			'options'     => [
				'backdrop' => esc_html__( 'Backdrop', 'bricks' ) . ' (' . esc_html__( 'Click', 'bricks' ) . ')',
				'esc'      => 'ESC (' . esc_html__( 'Key', 'bricks' ) . ')',
				'none'     => esc_html__( 'None', 'bricks' ),
			],
			'placeholder' => esc_html__( 'Backdrop', 'bricks' ) . ' & ESC',
		];

		self::$controls['popupZindex'] = [
			'group'       => 'popup',
			'label'       => 'Z-index',
			'type'        => 'number',
			'large'       => true,
			'css'         => [
				[
					'property' => 'z-index',
					'selector' => '&.brx-popup',
				],
			],
			'placeholder' => 10000,
		];

		self::$controls['popupBodyScroll'] = [
			'group' => 'popup',
			'label' => esc_html__( 'Scroll', 'bricks' ) . ' (body)',
			'type'  => 'checkbox',
		];

		/**
		 * Scroll to the top of popup when popup opens
		 *
		 * If popupDisableAutoFocus is checked.
		 *
		 * @since 1.8.4
		 */
		self::$controls['popupScrollToTop'] = [
			'group'       => 'popup',
			'label'       => esc_html__( 'Scroll to top', 'bricks' ),
			'type'        => 'checkbox',
			'description' => esc_html__( 'Scroll to top of popup when popup opens.', 'bricks' ),
		];

		/**
		 * Disable auto focus
		 *
		 * @since 1.8.4
		 */
		self::$controls['popupDisableAutoFocus'] = [
			'group'       => 'popup',
			'label'       => esc_html__( 'Disable auto focus', 'bricks' ),
			'type'        => 'checkbox',
			'description' => esc_html__( 'Don\'t focus on first focusable element inside popup when popup opens.', 'bricks' ),
		];

		/**
		 * Fetch popup content via AJAX
		 *
		 * @since 1.9.4
		 */
		self::$controls['popupAjax'] = [
			'group'       => 'popup',
			'label'       => esc_html__( 'Fetch content via AJAX', 'bricks' ),
			'type'        => 'checkbox',
			'description' => esc_html__( 'Only supports Post, Term, and User context. More context are available inside the "Interaction" settings that open this popup.', 'bricks' ),
		];

		// Popup AJAX loader
		self::$controls['popupAjaxLoaderSep'] = [
			'group'       => 'popup',
			'label'       => esc_html__( 'AJAX loader', 'bricks' ),
			'type'        => 'separator',
			'description' => esc_html__( 'Shows when popup content is fetched via AJAX.', 'bricks' ),
			'required'    => [ 'popupAjax', '=', true ],
		];

		self::$controls['popupAjaxLoaderAnimation'] = [
			'group'       => 'popup',
			'label'       => esc_html__( 'Animation', 'bricks' ),
			'type'        => 'select',
			'inline'      => true,
			'options'     => Setup::get_control_options( 'ajaxLoaderAnimations' ),
			'placeholder' => esc_html__( 'None', 'bricks' ),
			'required'    => [ 'popupAjax', '=', true ],
		];

		self::$controls['popupAjaxLoaderColor'] = [
			'group'    => 'popup',
			'label'    => esc_html__( 'Color', 'bricks' ),
			'type'     => 'color',
			'required' => [
				[ 'popupAjax', '=', true ],
				[ 'popupAjaxLoaderAnimation', '!=', '' ],
			],
		];

		self::$controls['popupAjaxLoaderScale'] = [
			'group'       => 'popup',
			'label'       => esc_html__( 'Scale', 'bricks' ),
			'type'        => 'number',
			'placeholder' => 1,
			'required'    => [
				[ 'popupAjax', '=', true ],
				[ 'popupAjaxLoaderAnimation', '!=', '' ],
			],
		];

		self::$controls['popupAjaxLoaderSelector'] = [
			'group'       => 'popup',
			'label'       => esc_html__( 'CSS Selector', 'bricks' ),
			'type'        => 'text',
			'description' => esc_html__( 'CSS selector of the element to insert the AJAX loader into.', 'bricks' ),
			'placeholder' => '.brx-popup-content',
			'required'    => [
				[ 'popupAjax', '=', true ],
				[ 'popupAjaxLoaderAnimation', '!=', '' ],
			],
		];

		// BREAKPOINTS (@since 1.9)
		self::$controls['popupBreakpointSep'] = [
			'group'       => 'popup',
			'type'        => 'separator',
			'label'       => esc_html__( 'Breakpoints', 'bricks' ),
			'description' => esc_html__( 'Choose at which breakpoint do you want to start showing this popup or on which specific breakpoints.', 'bricks' ),
		];

		$breakpoints         = self::get_breakpoints();
		$breakpoints_options = array_column( $breakpoints, 'label', 'key' );

		// Popup breakpoint mode (start show at OR show on)
		self::$controls['popupBreakpointMode'] = [
			'group'       => 'popup',
			'type'        => 'select',
			'options'     => [
				'at' => esc_html__( 'Start display at breakpoint', 'bricks' ),
				'on' => esc_html__( 'Display on breakpoints', 'bricks' ),
			],
			'placeholder' => esc_html__( 'Start display at breakpoint', 'bricks' ),
		];

		self::$controls['popupShowAt'] = [
			'group'       => 'popup',
			'type'        => 'select',
			'options'     => $breakpoints_options,
			'placeholder' => esc_html__( 'Any breakpoint', 'bricks' ),
			'required'    => [ 'popupBreakpointMode', '!=', 'on' ],
		];

		self::$controls['popupShowOn'] = [
			'group'       => 'popup',
			'type'        => 'select',
			'multiple'    => true,
			'options'     => $breakpoints_options,
			'placeholder' => esc_html__( 'Any breakpoint', 'bricks' ),
			'required'    => [ 'popupBreakpointMode', '=', 'on' ],
		];

		// BACKDROP

		self::$controls['popupBackdropSep'] = [
			'group' => 'popup',
			'label' => esc_html__( 'Backdrop', 'bricks' ),
			'type'  => 'separator',
		];

		self::$controls['popupBackground'] = [
			'group'   => 'popup',
			'label'   => esc_html__( 'Background', 'bricks' ),
			'type'    => 'background',
			'css'     => [
				[
					'property' => 'background',
					'selector' => '&.brx-popup .brx-popup-backdrop',
				],
			],
			'exclude' => 'video',
		];

		// Backdrop transition

		self::$controls['popupBackdropTransition'] = [
			'group'  => 'popup',
			'label'  => esc_html__( 'Transition', 'bricks' ),
			'type'   => 'text',
			'inline' => true,
			'css'    => [
				[
					'property' => 'transition',
					'selector' => '&.brx-popup .brx-popup-backdrop',
				],
			],
		];

		// CONTENT

		self::$controls['popupContentSep'] = [
			'group' => 'popup',
			'label' => esc_html__( 'Content', 'bricks' ),
			'type'  => 'separator',
		];

		self::$controls['popupContentPadding'] = [
			'group'       => 'popup',
			'label'       => esc_html__( 'Padding', 'bricks' ),
			'type'        => 'spacing',
			'css'         => [
				[
					'property' => 'padding',
					'selector' => '.brx-popup-content',
				],
			],
			'placeholder' => [
				'top'    => '30px',
				'right'  => '30px',
				'bottom' => '30px',
				'left'   => '30px',

			],
		];

		self::$controls['popupContentWidth'] = [
			'group' => 'popup',
			'label' => esc_html__( 'Width', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'css'   => [
				[
					'property' => 'width',
					'selector' => '.brx-popup-content',
				],
			],
		];

		self::$controls['popupContentHeight'] = [
			'group' => 'popup',
			'label' => esc_html__( 'Height', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'css'   => [
				[
					'property' => 'height',
					'selector' => '.brx-popup-content',
				],
			],
		];

		self::$controls['popupContentBackground'] = [
			'group' => 'popup',
			'label' => esc_html__( 'Background', 'bricks' ),
			'type'  => 'background',
			'css'   => [
				[
					'property' => 'background',
					'selector' => '.brx-popup-content',
				],
			],
		];

		self::$controls['popupContentBorder'] = [
			'group' => 'popup',
			'label' => esc_html__( 'Border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.brx-popup-content',
				],
			],
		];

		self::$controls['popupContentBoxShadow'] = [
			'group' => 'popup',
			'label' => esc_html__( 'Box shadow', 'bricks' ),
			'type'  => 'box-shadow',
			'css'   => [
				[
					'property' => 'box-shadow',
					'selector' => '.brx-popup-content',
				],
			],
		];

		// Popup limits

		self::$controls['popupLimitsSep'] = [
			'group'       => 'popup',
			'type'        => 'separator',
			'label'       => esc_html__( 'Popup limit', 'bricks' ),
			'description' => esc_html__( 'Limit how often this popup appears.', 'bricks' ),
		];

		self::$controls['popupLimitWindow'] = [
			'group'   => 'popup',
			'type'    => 'number',
			'label'   => esc_html__( 'Per page load', 'bricks' ),
			'tooltip' => [
				'content'  => 'window.brx_popup_{id}_total',
				'position' => 'top-left',
			],
		];

		self::$controls['popupLimitSessionStorage'] = [
			'group'   => 'popup',
			'type'    => 'number',
			'label'   => esc_html__( 'Per session', 'bricks' ),
			'tooltip' => [
				'content'  => 'sessionStorage.brx_popup_{id}_total',
				'position' => 'top-left',
			],
		];

		self::$controls['popupLimitLocalStorage'] = [
			'group'   => 'popup',
			'type'    => 'number',
			'label'   => esc_html__( 'Across sessions', 'bricks' ),
			'tooltip' => [
				'content'  => 'localStorage.brx_popup_{id}_total',
				'position' => 'top-left',
			],
		];
	}

	/**
	 * Get breakpoints helper function
	 *
	 * @since 1.9.2
	 */
	private static function get_breakpoints() {
		if ( self::$breakpoints === null ) {
			self::$breakpoints = Breakpoints::get_breakpoints();
		}
		return self::$breakpoints;
	}

	/**
	 * Build query loop popup HTML and store under self::$looping_popup_html
	 *
	 * Render in footer when executing render_popups()
	 *
	 * Included inline styles.
	 *
	 * @param int $popup_id
	 *
	 * @return void
	 *
	 * @since 1.7.1
	 */
	public static function build_looping_popup_html( $popup_id ) {
		$html = self::generate_popup_html( $popup_id );

		/**
		 * Inside query loop: Get popup template settings
		 *
		 * To generate inline CSS for the popup template located inside a query loop.
		 */
		if ( Query::is_looping() && ! in_array( $popup_id, self::$generated_template_settings_inline_css_ids ) ) {
			$popup_template_settings = Helpers::get_template_settings( $popup_id );

			if ( $popup_template_settings ) {
				$template_settings_controls = Settings::get_controls_data( 'template' );

				if ( ! empty( $template_settings_controls['controls'] ) ) {
					$template_settings_inline_css = Assets::generate_inline_css_from_element(
						[
							'settings'             => $popup_template_settings,
							'_templateCssSelector' => ".brxe-popup-{$popup_id}"
						],
						$template_settings_controls['controls'],
						'popup'
					);

					if ( $template_settings_inline_css ) {
						$html .= "<style>$template_settings_inline_css</style>";

						self::$generated_template_settings_inline_css_ids[] = $popup_id;
					}
				}
			}
		}

		self::$looping_popup_html .= $html;
	}

	/**
	 * Generate popup HTML
	 *
	 * @param int $popup_id
	 *
	 * @return string
	 *
	 * @since 1.7.1
	 */
	public static function generate_popup_html( $popup_id ) {
		$elements = Database::get_data( $popup_id );

		if ( empty( $elements ) ) {
			return;
		}

		$popup_content = Frontend::render_data( $elements, 'popup' );

		// Skip adding popup HTML if empty (e.g. popup outermost element condition not fulfilled)
		if ( empty( $popup_content ) ) {
			return;
		}

		$is_popup_preview = Templates::get_template_type() === 'popup';

		$popup_template_settings = Helpers::get_template_settings( $popup_id );

		$attributes = [
			'data-popup-id' => $popup_id,
			'class'         => [ 'brx-popup', "brxe-popup-{$popup_id}" ],
		];

		// Popup breakpoint mode (start show at OR show on) @since 1.9.4
		$popup_breakpoint_mode = $popup_template_settings['popupBreakpointMode'] ?? 'at';

		/**
		 * STEP: Set the show at 'width' according to the selected breakpoint
		 *
		 * To hide popup on certain breakpoints.
		 *
		 * @since 1.9
		 */
		if ( $popup_breakpoint_mode === 'at' && isset( $popup_template_settings['popupShowAt'] ) ) {
			$breakpoint = Breakpoints::get_breakpoint_by( 'key', $popup_template_settings['popupShowAt'] );

			if ( $breakpoint ) {
				$width = $breakpoint['width'] ?? null;

				// Is base breakpoint
				if ( isset( $breakpoint['base'] ) ) {
					$breakpoints = Breakpoints::$breakpoints;

					foreach ( $breakpoints as $index => $bp ) {
						if ( $bp['key'] === $breakpoint['key'] && $index === 0 ) {
							$next_breakpoint = isset( $breakpoints[ $index + 1 ] ) ? $breakpoints[ $index + 1 ] : null;

							if ( isset( $next_breakpoint['width'] ) ) {
								$width = Breakpoints::$is_mobile_first ? null : $next_breakpoint['width'] + 1;
							}
						}
					}
				}

				if ( $width ) {
					$attributes['data-popup-show-at'] = $width;
				}
			}
		}

		/**
		 * STEP: Handle multi-select breakpoints
		 *
		 * To hide popup on certain breakpoints.
		 *
		 * @since 1.9.4
		 */
		if ( $popup_breakpoint_mode === 'on' && isset( $popup_template_settings['popupShowOn'] ) ) {
			$breakpoints = bricks_is_frontend() ? self::get_breakpoints() : [];

			// Get all 'width' values from breakpoints
			$all_widths = array_column( $breakpoints, 'width' );

			// Determine the minimum and maximum widths from the available breakpoints
			$min_available_width = min( $all_widths );
			$max_available_width = max( $all_widths );

			// This will store the range of widths for each breakpoint
			$width_ranges = [];

			// STEP: Loop through each breakpoint selected by the user
			foreach ( $popup_template_settings['popupShowOn'] as $selected_breakpoint_key ) {
				// Retrieve the details of the selected breakpoint
				$selected_breakpoint_details = Breakpoints::get_breakpoint_by( 'key', $selected_breakpoint_key );

				// If no matching breakpoint is found, continue to the next breakpoint
				if ( ! $selected_breakpoint_details ) {
					continue;
				}

				$current_width = $selected_breakpoint_details['width'];

				$min_range = 0;
				$max_range = '9999';

				// Mobile first logic
				if ( Breakpoints::$is_mobile_first ) {
					// Adjust the minimum range if this is not the smallest breakpoint
					if ( $current_width !== $min_available_width ) {
						$min_range = $current_width;
					}

					// Find the next largest width from all breakpoints
					$larger_widths = array_filter(
						$all_widths,
						function ( $width ) use ( $current_width ) {
							return $width > $current_width;
						}
					);

					if ( ! empty( $larger_widths ) ) {
						$next_larger_width = min( $larger_widths );
						$max_range         = $next_larger_width - 1;
					}
				}

				// Non-mobile-first logic
				else {
					// STEP: Find all breakpoints that have a width lesser than the current breakpoint's width
					$lesser_breakpoints = array_filter(
						$breakpoints,
						function ( $breakpoint ) use ( $current_width ) {
							return $breakpoint['width'] < $current_width;
						}
					);

					// Extract all the widths of these lesser breakpoints
					$lesser_breakpoint_widths = array_column( $lesser_breakpoints, 'width' );
					$max_lesser_width         = ( ! empty( $lesser_breakpoint_widths ) ) ? max( $lesser_breakpoint_widths ) : null;

					// STEP: Determine the range

					// Adjust the minimum range if this is not the smallest breakpoint
					if ( $current_width !== $min_available_width ) {
						$min_range = $max_lesser_width + 1;
					}

					// Adjust the max range if this is not the largest breakpoint
					if ( $current_width !== $max_available_width ) {
						$max_range = $current_width;
					}
				}

				// Store the width range
				$width_ranges[] = "$min_range-$max_range";
			}

			// Set the attribute with the combined width ranges
			$attributes['data-popup-show-on-widths'] = implode( ',', $width_ranges );
		}

		// @since 1.9.4
		$ajax_popup_key = $popup_id;

		// Add popup loop attributes for JavaScript logic (@since 1.7.1)
		$looping_query_id = Query::is_any_looping();

		if ( $looping_query_id ) {
			// Unique identifier for popup inside query loop (@since 1.8.4)
			$unique_loop_id = [
				Query::get_query_element_id( $looping_query_id ),
				Query::get_loop_index(),
				Query::get_loop_object_type( $looping_query_id ),
				Query::get_loop_object_id( $looping_query_id ),
			];

			$attributes['data-popup-loop']       = Query::get_query_element_id( $looping_query_id ); // Needed when AJAX pagination
			$attributes['data-popup-loop-index'] = Query::get_loop_index(); // Not in use but in academy
			$attributes['data-popup-loop-id']    = implode( ':', $unique_loop_id );
			$ajax_popup_key                      = implode( ':', $unique_loop_id ); // For AJAX popup content array key @since 1.9.4

			// Add loop element ID as popup class (e.g. brxe-{loop_container_element_id}) to target correct popup selectors (@since 1.7.1)
			$attributes['class'][] = "brxe-{$attributes['data-popup-loop']}";
		}

		// Allow body scroll when popup is open (@since 1.7.1)
		if ( isset( $popup_template_settings['popupBodyScroll'] ) ) {
			$attributes['data-popup-body-scroll'] = esc_attr( 'true' );
		}

		// Close popup on
		if ( isset( $popup_template_settings['popupCloseOn'] ) ) {
			$attributes['data-popup-close-on'] = esc_attr( $popup_template_settings['popupCloseOn'] );
		}

		// Auto focus and Scroll to top (@since 1.8.4)
		if ( isset( $popup_template_settings['popupDisableAutoFocus'] ) ) {
			$attributes['data-popup-disable-auto-focus'] = 1;
		}

		if ( isset( $popup_template_settings['popupScrollToTop'] ) ) {
			$attributes['data-popup-scroll-to-top'] = 1;
		}

		// Ajax popup (@since 1.9.4)
		if ( isset( $popup_template_settings['popupAjax'] ) ) {
			$attributes['data-popup-ajax'] = 1;

			// Add to array of popup contents that are fetched via AJAX
			self::$ajax_popup_contents[ $ajax_popup_key ][ $popup_id ] = [
				'html'       => $popup_content,
				'attributes' => $attributes,
			];

			if ( $looping_query_id ) {
				// Add popup ID to array of popup IDs that are fetched via AJAX, no duplicate IDs
				self::$looping_ajax_popup_ids = array_unique( array_merge( self::$looping_ajax_popup_ids, [ $popup_id ] ) );
			}
		}

		// Ajax popup loader (@since 1.9.4)
		if ( ! empty( $popup_template_settings['popupAjaxLoaderAnimation'] ) ) {
			$ajax_loader_data = [
				'animation' => $popup_template_settings['popupAjaxLoaderAnimation'],
				'selector'  => isset( $popup_template_settings['popupAjaxLoaderSelector'] ) ? $popup_template_settings['popupAjaxLoaderSelector'] : '',
				'color'     => isset( $popup_template_settings['popupAjaxLoaderColor'] ) ? Assets::generate_css_color( $popup_template_settings['popupAjaxLoaderColor'] ) : '',
				'scale'     => isset( $popup_template_settings['popupAjaxLoaderScale'] ) ? $popup_template_settings['popupAjaxLoaderScale'] : '',
			];

			$attributes['data-brx-ajax-loader'] = wp_json_encode( $ajax_loader_data );

			// Indicate that AJAX loader style should be enqueued
			self::$enqueue_ajax_loader = true;
		}

		if ( ! $is_popup_preview ) {
			// Not previewing popup template: Hide it
			$attributes['class'][] = 'hide';

			// STEP: Add popup show limits
			$limits = [];

			$limit_options = [
				'popupLimitWindow'         => 'windowStorage',
				'popupLimitSessionStorage' => 'sessionStorage',
				'popupLimitLocalStorage'   => 'localStorage',
			];

			foreach ( $limit_options as $limit => $storage ) {
				if ( empty( $popup_template_settings[ $limit ] ) ) {
					continue;
				}

				$limits[ $storage ] = intval( $popup_template_settings[ $limit ] );
			}

			if ( ! empty( $limits ) ) {
				$attributes['data-popup-limits'] = htmlspecialchars( wp_json_encode( $limits ) );
			}

			// NOTE: Undocumented
			$attributes = apply_filters( 'bricks/popup/attributes', $attributes, $popup_id );
		}

		/**
		 * Add AJAX popup class as it is not being outputted for each loop.
		 * Now rendered as a single popup so we must add the class here or the looping styles will not work. (wrong selector)
		 *
		 * @since 1.9.4
		 */
		if ( in_array( $popup_id, self::$looping_ajax_popup_ids ) ) {
			// Find popup content in self::$ajax_popup_contents where popup_id matches
			foreach ( self::$ajax_popup_contents as $loop_id => $ajax_popup_content ) {
				foreach ( $ajax_popup_content as $pid => $content ) {
					if ( $pid !== $popup_id ) {
						continue;
					}

					// Only get the class attribute
					$attributes['class'] = array_unique( array_merge( $attributes['class'], $ajax_popup_content[ $popup_id ]['attributes']['class'] ) );

					// Exit loop - only match once
					break;
				}
			}
		}

		$attributes = Helpers::stringify_html_attributes( $attributes );

		$popup_content_classes = 'brx-popup-content';

		// Default popup width = Container width
		if ( ! isset( $popup_template_settings['popupContentWidth'] ) ) {
			$popup_content_classes .= ' brxe-container';
		}

		// Return empty html if AJAX popup in loop and not Api load_popup_content (@since 1.9.4)
		if ( isset( $popup_template_settings['popupAjax'] ) && ! Api::is_current_endpoint( 'load_popup_content' ) && $looping_query_id ) {
			return '';
		}

		$html  = "<div {$attributes}>";
		$html .= "<div class=\"$popup_content_classes\">";
		// Render popup content only if not popupAjax (@since 1.9.4)
		if ( ! isset( $popup_template_settings['popupAjax'] ) ) {
			$html .= $popup_content;
		}
		$html .= '</div>';
		$html .= '<div class="brx-popup-backdrop"></div>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Check if there is any popup to render and adds popup HTML to the footer
	 *
	 * @since 1.6
	 *
	 * @return void
	 */
	public static function render_popups() {
		$popup_ids = Database::$active_templates['popup'];

		$is_popup_preview = Templates::get_template_type() === 'popup';

		// Is popup preview: Add popup ID
		if ( $is_popup_preview ) {
			$popup_ids = [ get_the_ID() ];
		}

		// Output query looping popup HTML (@since 1.7.1)
		if ( ! empty( self::$looping_popup_html ) ) {
			echo self::$looping_popup_html;
		}

		/**
		 * Add looping AJAX popup ids to eliminate too much duplicated popup HTML
		 *
		 * @since 1.9.4
		 */
		if ( ! empty( self::$looping_ajax_popup_ids ) ) {
			// Only add AJAX popup ids if not exists in popup ids
			$popup_ids = array_unique( array_merge( $popup_ids, array_diff( array_values( self::$looping_ajax_popup_ids ), $popup_ids ) ) );
		}

		if ( empty( $popup_ids ) ) {
			return;
		}

		// Enqueue AJAX loader style (@since 1.9.4)
		if ( self::$enqueue_ajax_loader ) {
			wp_enqueue_style( 'bricks-ajax-loader' );
		}

		foreach ( $popup_ids as $popup_id ) {
			// Refactor HTML generation (@since 1.7.1)
			$html = self::generate_popup_html( $popup_id );

			if ( empty( $html ) ) {
				continue;
			}

			echo $html;
		}

		/**
		 * Template settings "Popup" load as inline CSS
		 *
		 * NOTE: Not optimal, but needed as template settings are not part of popup CSS file
		 */
		if ( Database::get_setting( 'cssLoading' ) === 'file' && ! empty( Assets::$inline_css['popup'] ) ) {
			echo '<style>' . Assets::$inline_css['popup'] . '</style>';
		}
	}
}
