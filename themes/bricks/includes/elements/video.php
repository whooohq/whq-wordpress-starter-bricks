<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Video extends Element {
	public $block     = [ 'core/video', 'core-embed/youtube', 'core-embed/vimeo' ];
	public $category  = 'basic';
	public $name      = 'video';
	public $icon      = 'ti-video-clapper';
	public $scripts   = [ 'bricksVideo' ];
	public $draggable = false;

	public function get_label() {
		return esc_html__( 'Video', 'bricks' );
	}

	public function enqueue_scripts() {
		if ( isset( $this->theme_styles['customPlayer'] ) ) {
			wp_enqueue_style( 'video-plyr', BRICKS_URL_ASSETS . 'css/libs/plyr.min.css', [], '3.6.3' );
			wp_enqueue_script( 'video-plyr', BRICKS_URL_ASSETS . 'js/libs/plyr.min.js', [ 'bricks-scripts' ], '3.6.3', true );
		}
	}

	public function set_control_groups() {
		$this->control_groups['icon'] = [
			'title' => esc_html__( 'Overlay', 'bricks' ) . ' / ' . esc_html__( 'Icon', 'bricks' ),
			'tab'   => 'content',
		];
	}

	public function set_controls() {
		$this->controls['videoType'] = [
			'tab'       => 'content',
			'label'     => esc_html__( 'Source', 'bricks' ),
			'type'      => 'select',
			'options'   => [
				'youtube' => 'YouTube',
				'vimeo'   => 'Vimeo',
				'media'   => esc_html__( 'Media', 'bricks' ),
				'file'    => esc_html__( 'File URL', 'bricks' ),
				'meta'    => esc_html__( 'Dynamic Data', 'bricks' ),
			],
			'default'   => 'youtube',
			'inline'    => true,
			'clearable' => false,
		];

		// @since 1.6.1
		$this->controls['iframeTitle'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Iframe title', 'bricks' ),
			'type'     => 'text',
			'inline'   => true,
			'required' => [ 'videoType', '=', [ 'youtube', 'vimeo' ] ],
		];

		/**
		 * Type: YouTube
		 */

		$this->controls['youTubeId'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'YouTube video ID', 'bricks' ),
			'type'     => 'text',
			'inline'   => true,
			'required' => [ 'videoType', '=', 'youtube' ],
			'default'  => '5DGo0AYOJ7s',
		];

		// Cannot be used if using preview image
		$this->controls['youtubeAutoplay'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Autoplay', 'bricks' ),
			'type'     => 'checkbox',
			'info'     => 'YouTube: ' . esc_html__( 'Not supported on mobile devices', 'bricks' ),
			'required' => [
				[ 'videoType', '=', 'youtube' ],
				[ 'previewImage' , '!=', true ],
			],
		];

		$this->controls['youtubeControls'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Controls', 'bricks' ),
			'type'     => 'checkbox',
			'default'  => true,
			'required' => [ 'videoType', '=', 'youtube' ],
		];

		$this->controls['youtubeLoop'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Loop', 'bricks' ),
			'type'     => 'checkbox',
			'required' => [ 'videoType', '=', 'youtube' ],
		];

		$this->controls['youtubeMute'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Mute', 'bricks' ),
			'type'     => 'checkbox',
			'required' => [ 'videoType', '=', 'youtube' ],
		];

		$this->controls['youtubeShowinfo'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Show info', 'bricks' ),
			'type'     => 'checkbox',
			'default'  => true,
			'required' => [ 'videoType', '=', 'youtube' ],
		];

		$this->controls['youtubeRel'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Related videos from other channels', 'bricks' ),
			'type'     => 'checkbox',
			'required' => [ 'videoType', '=', 'youtube' ],
		];

		$this->controls['youtubeDoNotTrack'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Do not track', 'bricks' ),
			'type'     => 'checkbox',
			'required' => [ 'videoType', '=', 'youtube' ],
		];

		/**
		 * Type: Vimeo
		 */

		$this->controls['vimeoId'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Vimeo video ID', 'bricks' ),
			'type'     => 'text',
			'inline'   => true,
			'required' => [ 'videoType', '=', 'vimeo' ],
		];

		// Support unlisted vimeo videos.
		$this->controls['vimeoHash'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Vimeo privacy hash', 'bricks' ),
			'type'     => 'text',
			'inline'   => true,
			'info'     => esc_html__( 'If the video is unlisted, you will need to enter the video privacy hash.', 'bricks' ),
			'required' => [ 'videoType', '=', 'vimeo' ],
		];

		// Cannot be used if using preview image
		$this->controls['vimeoAutoplay'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Autoplay', 'bricks' ),
			'type'     => 'checkbox',
			'required' => [
				[ 'videoType', '=', 'vimeo' ],
				[ 'previewImage' , '!=', true ],
			],
		];

		$this->controls['vimeoLoop'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Loop', 'bricks' ),
			'type'     => 'checkbox',
			'required' => [ 'videoType', '=', 'vimeo' ],
		];

		$this->controls['vimeoMute'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Mute', 'bricks' ),
			'type'     => 'checkbox',
			'required' => [ 'videoType', '=', 'vimeo' ],
		];

		$this->controls['vimeoByline'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Byline', 'bricks' ),
			'type'     => 'checkbox',
			'default'  => true,
			'required' => [ 'videoType', '=', 'vimeo' ],
		];

		$this->controls['vimeoTitle'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Title', 'bricks' ),
			'type'     => 'checkbox',
			'default'  => true,
			'required' => [ 'videoType', '=', 'vimeo' ],
		];

		$this->controls['vimeoPortrait'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'User portrait', 'bricks' ),
			'type'     => 'checkbox',
			'default'  => true,
			'required' => [ 'videoType', '=', 'vimeo' ],
		];

		$this->controls['vimeoDoNotTrack'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Do not track', 'bricks' ),
			'type'     => 'checkbox',
			'required' => [ 'videoType', '=', 'vimeo' ],
		];

		$this->controls['vimeoColor'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Color', 'bricks' ),
			'type'     => 'color',
			'required' => [ 'videoType', '=', 'vimeo' ],
		];

		/**
		 * Preview image
		 *
		 * Load video YouTube/Vimeo iframe after preview image click.
		 *
		 * Cannot be used with autoplay.
		 *
		 * @since 1.7.2
		 */
		$this->controls['previewImageSeparator'] = [
			'tab'         => 'content',
			'type'        => 'separator',
			'label'       => esc_html__( 'Preview image', 'bricks' ),
			'description' => esc_html__( 'The video <iframe> is lazy loaded after clicking the preview image.', 'bricks' ),
			'required'    => [
				[ 'videoType', '=', [ 'vimeo', 'youtube' ] ],
			],
		];

		$this->controls['previewImage'] = [
			'tab'         => 'content',
			'type'        => 'select',
			'options'     => [
				'default' => esc_html__( 'Default', 'bricks' ) . ' (API)',
				'custom'  => esc_html__( 'Custom', 'bricks' ),
			],
			'placeholder' => esc_html__( 'None', 'bricks' ),
			'description' => sprintf(
				'%s :<br> %s > %s > %s',
				esc_html__( 'Fallback preview image', 'bricks' ),
				esc_html__( 'Settings', 'bricks' ),
				esc_html__( 'Theme Styles', 'bricks' ),
				esc_html__( 'Element - Video', 'bricks' )
			),
			'required'    => [
				[ 'videoType', '=', [ 'vimeo', 'youtube' ] ],
			],
		];

		$this->controls['previewImageCustom'] = [
			'tab'      => 'content',
			'type'     => 'image',
			'required' => [
				[ 'videoType', '=', [ 'vimeo', 'youtube' ] ],
				[ 'previewImage', '=', 'custom' ],
			],
		];

		$this->controls['previewImageIconInfo'] = [
			'tab'      => 'content',
			'type'     => 'info',
			'content'  => esc_html__( 'Set "Icon" as video play button for a better user experience.', 'bricks' ),
			'required' => [
				[ 'previewImage', '!=', '' ],
				[ 'overlayIcon', '=', '' ],
			],
		];

		$this->controls['previewImageYoutubeAutoplayInfo'] = [
			'tab'      => 'content',
			'type'     => 'info',
			'content'  => esc_html__( 'Autoplay is not supported when using preview image.', 'bricks' ),
			'required' => [
				[ 'previewImage', '!=', '' ],
				[ 'youtubeAutoplay', '!=', '' ],
			],
		];

		$this->controls['previewImageVimeoAutoplay'] = [
			'tab'      => 'content',
			'type'     => 'info',
			'content'  => esc_html__( 'Autoplay is not supported when using preview image.', 'bricks' ),
			'required' => [
				[ 'previewImage', '!=', '' ],
				[ 'vimeoAutoplay', '!=', '' ],
			],
		];

		/**
		 * Type: Media
		 */

		$this->controls['media'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Media', 'bricks' ),
			'type'     => 'video',
			'required' => [ 'videoType', '=', 'media' ],
		];

		/**
		 * Type: File
		 */

		$this->controls['fileUrl'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Video file URL', 'bricks' ),
			'type'     => 'text',
			'required' => [ 'videoType', '=', 'file' ],
		];

		/**
		 * Type: Meta
		 */

		$this->controls['useDynamicData'] = [
			'tab'            => 'content',
			'label'          => '',
			'type'           => 'text',
			'placeholder'    => esc_html__( 'Select dynamic data', 'bricks' ),
			'hasDynamicData' => 'link',
			'required'       => [ 'videoType', '=', 'meta' ],
		];

		/**
		 * Type: Media & File
		 */

		$this->controls['filePreload'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Preload', 'bricks' ),
			'type'        => 'select',
			'options'     => [
				'metadata' => esc_html__( 'Metadata', 'bricks' ),
				'auto'     => esc_html__( 'Auto', 'bricks' ),
			],
			'placeholder' => esc_html__( 'None', 'bricks' ),
			'inline'      => true,
			'required'    => [ 'videoType', '=', [ 'media', 'file', 'meta' ] ],
		];

		$this->controls['fileAutoplay'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Autoplay', 'bricks' ),
			'type'     => 'checkbox',
			'required' => [ 'videoType', '=', [ 'media', 'file', 'meta' ] ],
		];

		$this->controls['fileLoop'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Loop', 'bricks' ),
			'type'     => 'checkbox',
			'required' => [ 'videoType', '=', [ 'media', 'file', 'meta' ] ],
		];

		$this->controls['fileMute'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Mute', 'bricks' ),
			'type'     => 'checkbox',
			'required' => [ 'videoType', '=', [ 'media', 'file', 'meta' ] ],
		];

		$this->controls['fileInline'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Play inline', 'bricks' ),
			'type'     => 'checkbox',
			'required' => [ 'videoType', '=', [ 'media', 'file', 'meta' ] ],
		];

		$this->controls['fileControls'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Controls', 'bricks' ),
			'type'     => 'checkbox',
			'default'  => true,
			'required' => [ 'videoType', '=', [ 'media', 'file', 'meta' ] ],
		];

		$this->controls['infoControls'] = [
			'tab'      => 'content',
			'content'  => esc_html__( 'Set individual video player controls under: Settings > Theme Styles > Element - Video', 'bricks' ),
			'type'     => 'info',
			'required' => [ 'videoType', '=', [ 'media', 'file', 'meta' ] ],
		];

		$this->controls['videoPoster'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Poster', 'bricks' ),
			'type'        => 'image',
			'description' => esc_html__( 'Set for video SEO best practices.', 'bricks' ),
			'required'    => [ 'videoType', '=', [ 'media', 'file', 'meta' ] ],
		];

		// OVERLAY / ICON

		$this->controls['overlay'] = [
			'tab'      => 'content',
			'group'    => 'icon',
			'type'     => 'background',
			'label'    => esc_html__( 'Overlay', 'bricks' ),
			'exclude'  => 'video',
			'rerender' => true,
			'css'      => [
				[
					'property' => 'background',
					'selector' => '.bricks-video-overlay',
				],
			],
		];

		$this->controls['overlayIcon'] = [
			'tab'      => 'content',
			'group'    => 'icon',
			'label'    => esc_html__( 'Icon', 'bricks' ),
			'type'     => 'icon',
			'rerender' => true,
		];

		$this->controls['overlayIconTypography'] = [
			'tab'      => 'content',
			'group'    => 'icon',
			'label'    => esc_html__( 'Icon typography', 'bricks' ),
			'type'     => 'typography',
			'css'      => [
				[
					'property' => 'font',
					'selector' => '.bricks-video-overlay-icon',
				],
			],
			'exclude'  => [
				'font-family',
				'font-weight',
				'font-style',
				'text-align',
				'text-decoration',
				'text-transform',
				'line-height',
				'letter-spacing',
			],
			'required' => [ 'overlayIcon.icon', '!=', '' ],
		];

		$this->controls['overlayIconPadding'] = [
			'tab'      => 'content',
			'group'    => 'icon',
			'label'    => esc_html__( 'Icon padding', 'bricks' ),
			'type'     => 'spacing',
			'css'      => [
				[
					'property' => 'padding',
					'selector' => '.bricks-video-overlay-icon',
				],
			],
			'required' => [ 'overlayIcon', '!=', '' ],
		];

		$this->controls['overlayIconBackgroundColor'] = [
			'tab'      => 'content',
			'group'    => 'icon',
			'label'    => esc_html__( 'Icon background color', 'bricks' ),
			'type'     => 'color',
			'css'      => [
				[
					'property' => 'background-color',
					'selector' => '.bricks-video-overlay-icon',
				],
			],
			'required' => [ 'overlayIcon', '!=', '' ],
		];

		$this->controls['overlayIconBorder'] = [
			'tab'      => 'content',
			'group'    => 'icon',
			'label'    => esc_html__( 'Icon border', 'bricks' ),
			'type'     => 'border',
			'css'      => [
				[
					'property' => 'border',
					'selector' => '.bricks-video-overlay-icon',
				],
			],
			'required' => [ 'overlayIcon', '!=', '' ],
		];

		$this->controls['overlayIconBoxShadow'] = [
			'tab'      => 'content',
			'group'    => 'icon',
			'label'    => esc_html__( 'Icon box shadow', 'bricks' ),
			'type'     => 'box-shadow',
			'css'      => [
				[
					'property' => 'box-shadow',
					'selector' => '.bricks-video-overlay-icon',
				],
			],
			'required' => [ 'overlayIcon', '!=', '' ],
		];
	}

	public function render() {
		$settings = $this->settings;

		// Return: No video type selected
		if ( empty( $settings['videoType'] ) ) {
			return $this->render_element_placeholder(
				[
					'title' => esc_html__( 'No video selected.', 'bricks' ),
				]
			);
		}

		// Parse settings if videoType = 'meta' try fitting content into the other 'videoType' flows
		$settings = $this->get_normalized_video_settings( $settings );
		$source   = $settings['videoType'] ?? false;

		if ( $source === 'youtube' && empty( $settings['youTubeId'] ) ) {
			return $this->render_element_placeholder(
				[
					'title' => esc_html__( 'No YouTube ID provided.', 'bricks' ),
				]
			);
		}

		if ( $source === 'vimeo' && empty( $settings['vimeoId'] ) ) {
			return $this->render_element_placeholder(
				[
					'title' => esc_html__( 'No Vimeo ID provided.', 'bricks' ),
				]
			);
		}

		if ( $source === 'media' && empty( $settings['media'] ) ) {
			return $this->render_element_placeholder(
				[
					'title' => esc_html__( 'No video selected.', 'bricks' ),
				]
			);
		}

		if ( $source === 'file' && empty( $settings['fileUrl'] ) ) {
			return $this->render_element_placeholder(
				[
					'title' => esc_html__( 'No file URL provided.', 'bricks' ),
				]
			);
		}

		// If meta is still set, then something failed
		if ( $source === 'meta' ) {
			if ( empty( $settings['useDynamicData'] ) ) {
				$message = esc_html__( 'No dynamic data set.', 'bricks' );
			} else {
				$message = esc_html__( 'Dynamic data is empty.', 'bricks' );
			}

			if ( ! empty( $message ) ) {
				return $this->render_element_placeholder(
					[
						'title' => $message
					]
				);
			}
		}

		// Build video URL
		$video_url        = '';
		$video_parameters = [];

		// Use custom HTML5 video player: https://plyr.io (if controls are enabled)
		$use_custom_player = isset( $this->theme_styles['customPlayer'] ) && isset( $settings['fileControls'] );

		switch ( $source ) {
			case 'youtube':
				$video_url = "https://www.youtube.com/embed/{$settings['youTubeId']}?";

				if ( isset( $settings['youtubeDoNotTrack'] ) ) { // @since 1.9.2
					$video_url = "https://www.youtube-nocookie.com/embed/{$settings['youTubeId']}?";
				}

				// https://developers.google.com/youtube/player_parameters
				$video_parameters[] = 'wmode=opaque';

				if ( isset( $settings['youtubeAutoplay'] ) ) {
					$video_parameters[] = 'autoplay=1';
				}

				if ( ! isset( $settings['youtubeControls'] ) ) {
					$video_parameters[] = 'controls=0';
				}

				if ( isset( $settings['youtubeLoop'] ) ) {
					// Loop in iframe requires 'playlist' parameter.
					$video_parameters[] = "loop=1&playlist={$settings['youTubeId']}";
				}

				if ( isset( $settings['youtubeMute'] ) ) {
					$video_parameters[] = 'mute=1';
				}

				if ( ! isset( $settings['youtubeShowinfo'] ) ) {
					$video_parameters[] = 'showinfo=0';
				}

				if ( ! isset( $settings['youtubeRel'] ) ) {
					$video_parameters[] = 'rel=0';
				}

				// Add enablejsapi to autopause on bricks/popup/close (@since 1.8)
				$video_parameters[] = 'enablejsapi=1';

				break;

			case 'vimeo':
				$video_url = "https://player.vimeo.com/video/{$settings['vimeoId']}?";

				// https://developer.vimeo.com/apis/oembed#arguments
				if ( isset( $settings['vimeoAutoplay'] ) ) {
					$video_parameters[] = 'autoplay=1';
				}

				if ( isset( $settings['vimeoHash'] ) ) {
					$video_parameters[] = 'h=' . $settings['vimeoHash'];
				}

				if ( isset( $settings['vimeoLoop'] ) ) {
					$video_parameters[] = 'loop=1';
				}

				if ( isset( $settings['vimeoMute'] ) ) {
					$video_parameters[] = 'muted=1';
				}

				if ( ! isset( $settings['vimeoByline'] ) ) {
					$video_parameters[] = 'byline=0';
				}

				if ( ! isset( $settings['vimeoTitle'] ) ) {
					$video_parameters[] = 'title=0';
				}

				if ( ! isset( $settings['vimeoPortrait'] ) ) {
					$video_parameters[] = 'portrait=0';
				}

				if ( isset( $settings['vimeoDoNotTrack'] ) ) {
					$video_parameters[] = 'dnt=1';
				}

				if ( ! empty( $settings['vimeoColor']['hex'] ) ) {
					$vimeo_color = str_replace( '#', '', $settings['vimeoColor']['hex'] );

					$video_parameters[] = "color={$vimeo_color}";
				}

				break;

			case 'media':
			case 'file':
				if ( $source === 'media' && ! empty( $settings['media']['url'] ) ) {
					$video_url = esc_url( $settings['media']['url'] );
				} elseif ( $source === 'file' && ! empty( $settings['fileUrl'] ) ) {
					$video_url = esc_url( bricks_render_dynamic_data( $settings['fileUrl'] ) );
				}

				$video_classes = [];

				if ( $this->lazy_load() ) {
					$video_classes = [ 'bricks-lazy-hidden' ];
					$this->set_attribute( 'video', 'data-src', $video_url );
				} else {
					$this->set_attribute( 'video', 'src', $video_url );
				}

				// Load custom video player if enabled
				if ( $use_custom_player ) {
					$video_classes[] = 'bricks-plyr';
				}

				$this->set_attribute( 'video', 'class', $video_classes );

				if ( isset( $settings['filePreload'] ) ) {
					$this->set_attribute( 'video', 'preload', $settings['filePreload'] );
				}

				if ( isset( $settings['fileAutoplay'] ) ) {
					$this->set_attribute( 'video', 'autoplay' );

					// Necessary for autoplaying in iOS (https://webkit.org/blog/6784/new-video-policies-for-ios/)
					$this->set_attribute( 'video', 'playsinline' );
				} elseif ( isset( $settings['fileInline'] ) ) {
					$this->set_attribute( 'video', 'playsinline' );
				}

				if ( isset( $settings['fileControls'] ) ) {
					$this->set_attribute( 'video', 'controls' );
				} elseif ( ! $use_custom_player ) {
					$this->set_attribute( 'video', 'onclick', 'this.paused ? this.play() : this.pause()' );
				}

				if ( isset( $settings['fileLoop'] ) ) {
					$this->set_attribute( 'video', 'loop' );
				}

				if ( isset( $settings['fileMute'] ) ) {
					$this->set_attribute( 'video', 'muted' );
				}

				// Video poster (@since 1.8.5)
				$video_poster_image = $this->get_video_image_by_key( 'videoPoster' );

				if ( ! empty( $video_poster_image['url'] ) ) {
					$this->set_attribute( 'video', 'poster', $video_poster_image['url'] );
				}

				break;
		}

		// Set data-id so we could track the plyr instances
		$this->set_attribute( 'wrapper', 'data-id', Helpers::generate_random_id( false ) );

		// Add parameters to final video URL
		if ( ! empty( $video_parameters ) ) {
			$video_url .= join( '&', $video_parameters );
		}

		// STEP: Render

		// Video HTML wrapper with iframe / video element for popup and non-popup settings
		$output = "<div {$this->render_attributes( '_root' )}>";

		$overlay_icon = ! empty( $settings['overlayIcon'] ) ? $settings['overlayIcon'] : false;

		// Check: Theme style for video 'overlayIcon' setting (@since 1.7)
		if ( ! $overlay_icon && ! empty( $this->theme_styles['overlayIcon'] ) ) {
			$overlay_icon = $this->theme_styles['overlayIcon'];
		}

		$icon = $overlay_icon ? self::render_icon( $overlay_icon, [ 'bricks-video-overlay-icon' ] ) : false;

		if ( $use_custom_player ) {
			$video_config_plyr = [];

			// https://github.com/sampotts/plyr/blob/master/controls.md
			if ( isset( $settings['fileControls'] ) ) {
				$video_config_plyr['controls'] = [ 'play' ];

				// Play button (if no custom icon is set)
				if ( ! $icon ) {
					$video_config_plyr['controls'][] = 'play-large';
				}

				if ( isset( $this->theme_styles['fileRestart'] ) ) {
					$video_config_plyr['controls'][] = 'restart';
				}

				if ( isset( $this->theme_styles['fileRewind'] ) ) {
					$video_config_plyr['controls'][] = 'rewind';
				}

				if ( isset( $this->theme_styles['fileFastForward'] ) ) {
					$video_config_plyr['controls'][] = 'fast-forward';
				}

				$video_config_plyr['controls'][] = 'current-time';
				$video_config_plyr['controls'][] = 'duration';
				$video_config_plyr['controls'][] = 'progress';
				$video_config_plyr['controls'][] = 'mute';
				$video_config_plyr['controls'][] = 'volume';

				if ( isset( $this->theme_styles['fileSpeed'] ) ) {
					$video_config_plyr['controls'][] = 'settings';
				}

				if ( isset( $this->theme_styles['filePip'] ) ) {
					$video_config_plyr['controls'][] = 'pip';
				}

				$video_config_plyr['controls'][] = 'fullscreen';
			}

			if ( isset( $settings['fileMute'] ) ) {
				$video_config_plyr['muted'] = true;

				// Store false required for muted to take effect
				$video_config_plyr['storage'] = false;
			}

			$this->set_attribute( 'video', 'data-plyr-config', wp_json_encode( $video_config_plyr ) );
		}

		if ( $source === 'media' || $source === 'file' || $source === 'meta' ) {
			$output .= '<video ' . $this->render_attributes( 'video' ) . '>';
			$output .= '<p>' . esc_html__( 'Your browser does not support the video tag.', 'bricks' ) . '</p>';
			$output .= '</video>';
		}

		if ( $source === 'youtube' || $source === 'vimeo' ) {
			$this->set_attribute( 'iframe', 'allowfullscreen' );
			$this->set_attribute( 'iframe', 'allow', 'autoplay' );

			if ( ! empty( $settings['iframeTitle'] ) ) {
				$this->set_attribute( 'iframe', 'title', wp_strip_all_tags( $this->render_dynamic_data( $settings['iframeTitle'] ) ) );
			}

			// STEP: Render YouTube/Vimeo iframe or div with background image
			$preview_image_url = $this->get_preview_image_url( $settings );
			if ( $preview_image_url ) {
				// STEP: Render div with background image when video lazy load is enabled and autoplay is disabled
				$this->set_attribute( 'iframe', 'data-iframe-src', $video_url );
				$this->set_attribute( 'iframe', 'class', 'bricks-video-preview-image' );

				$background_style = "background-image: url($preview_image_url);";

				// STEP: Add background image to div
				if ( $background_style ) {
					if ( $this->lazy_load() ) {
						// STEP: Global lazy load is enabled, background image added as data-style attribute
						$this->set_attribute( 'iframe', 'data-style', $background_style );
						$this->set_attribute( 'iframe', 'class', 'bricks-lazy-hidden' );
					} else {
						// STEP: Global lazy load is enabled, background image added as data-style attribute
						$this->set_attribute( 'iframe', 'style', $background_style );
					}
				}

				// Render as div
				$output .= '<div ' . $this->render_attributes( 'iframe' ) . '></div>';
			}

			// STEP: Render iframe (when video lazy load is disabled or autoplay is enabled)
			else {
				if ( $this->lazy_load() ) {
					// STEP: Global lazy load is enabled, iframe src added as data-src attribute
					$this->set_attribute( 'iframe', 'data-src', $video_url );
					$this->set_attribute( 'iframe', 'class', 'bricks-lazy-hidden' );
				} else {
					$this->set_attribute( 'iframe', 'src', $video_url );
				}

				// Render as iframe
				$output .= '<iframe ' . $this->render_attributes( 'iframe' ) . '></iframe>';
			}
		}

		// Check: Element & theme style for 'overlay' setting (@since 1.7)
		// Use new helper function to check for 'overlay' setting from different breakpoints (@since 1.8)
		$has_overlay = Helpers::element_setting_has_value( 'overlay', $settings ) || Helpers::element_setting_has_value( 'overlay', $this->theme_styles );

		// Check: Element classes for 'overlay' setting (@since 1.7.1)
		$element_class_has_overlay = $this->element_classes_have( 'overlay' );

		if ( $element_class_has_overlay ) {
			$has_overlay = true;
		}

		if ( $has_overlay ) {
			$output .= $this->lazy_load() ? '<div class="bricks-lazy-hidden bricks-video-overlay"></div>' : '<div class="bricks-video-overlay"></div>';
		}

		if ( $icon ) {
			$output .= $icon;
		}

		$output .= '</div>';

		echo $output;
	}

	public function convert_element_settings_to_block( $settings ) {
		$settings = $this->get_normalized_video_settings( $settings );
		$source   = ! empty( $settings['videoType'] ) ? $settings['videoType'] : false;
		$attrs    = [];
		$output   = '';

		// Video Type: Media file / File URL
		if ( $source === 'media' || $source === 'file' ) {
			$block['blockName'] = 'core/video';

			if ( isset( $settings['media']['id'] ) ) {
				$attrs['id'] = $settings['media']['id'];
			}

			$output = '<figure class="wp-block-video"><video ';

			if ( isset( $settings['fileAutoplay'] ) ) {
				$output .= 'autoplay ';
			}

			if ( isset( $settings['fileControls'] ) ) {
				$output .= 'controls ';
			}

			if ( isset( $settings['fileLoop'] ) ) {
				$output .= 'loop ';
			}

			if ( isset( $settings['fileMute'] ) ) {
				$output .= 'muted ';
			}

			if ( isset( $settings['filePreload'] ) ) {
				$output .= 'preload="' . $settings['filePreload'] . '"';
			}

			if ( $source === 'media' ) {
				$output .= 'src="' . wp_get_attachment_url( intval( $settings['media']['id'] ) ) . '"';
			}

			if ( $source === 'file' ) {
				$output .= 'src="' . esc_url( $settings['fileUrl'] ) . '"';
			}

			if ( isset( $settings['fileInline'] ) ) {
				$output .= ' playsinline';
			}

			$output .= '></video></figure>';
		}

		// Video Type: YouTube
		if ( $source === 'youtube' && isset( $settings['youTubeId'] ) ) {
			$block        = [ 'blockName' => 'core-embed/youtube' ];
			$attrs['url'] = "https://www.youtube.com/watch?v={$settings['youTubeId']}";

			if ( isset( $settings['youtubeDoNotTrack'] ) ) { // @since 1.9.2
				$attrs['url'] = "https://www.youtube-nocookie.com/watch?v={$settings['youTubeId']}";
			}

			$attrs['providerNameSlug'] = 'youtube';
			$attrs['type']             = 'video';
			$output                    = '<figure class="wp-block-embed-youtube wp-block-embed is-type-video is-provider-youtube"><div class="wp-block-embed__wrapper">' . $attrs['url'] . '</div></figure>';
		}

		// Video Type: Vimeo
		if ( $source === 'vimeo' && isset( $settings['vimeoId'] ) ) {
			$block                     = [ 'blockName' => 'core-embed/vimeo' ];
			$attrs['url']              = 'https://www.vimeo.com/' . $settings['vimeoId'];
			$attrs['providerNameSlug'] = 'vimeo';
			$attrs['type']             = 'video';
			$output                    = '<figure class="wp-block-embed-vimeo wp-block-embed is-type-video is-provider-vimeo"><div class="wp-block-embed__wrapper">' . $attrs['url'] . '</div></figure>';
		}

		$block['attrs']        = $attrs;
		$block['innerContent'] = [ $output ];

		return $block;
	}

	public function convert_block_to_element_settings( $block, $attributes ) {
		$video_provider = isset( $attributes['providerNameSlug'] ) ? $attributes['providerNameSlug'] : false;

		// Type: YouTube
		if ( $video_provider === 'youtube' ) {
			// Get YouTube video ID
			parse_str( parse_url( $attributes['url'], PHP_URL_QUERY ), $url_params );

			return [
				'videoType'       => 'youtube',
				'youTubeId'       => $url_params['v'],
				'youtubeControls' => true,
			];
		}

		// Type: Vimeo
		if ( $video_provider === 'vimeo' ) {
			// Get Vimeo video ID
			$url_parts = explode( '/', $attributes['url'] );

			$video_url = '';

			foreach ( $url_parts as $url_part ) {
				if ( is_numeric( $url_part ) ) {
					$video_url = $url_part;
				}
			}

			return [
				'videoType'     => 'vimeo',
				'vimeoId'       => $video_url,
				'vimeoControls' => true,
			];
		}

		$output = $block['innerHTML'];

		// Type: Media file
		$media_video_id = isset( $attributes['id'] ) ? intval( $attributes['id'] ) : 0;

		if ( $media_video_id ) {
			$media = [
				'id'       => $media_video_id,
				'filename' => basename( get_attached_file( $media_video_id ) ),
				'url'      => wp_get_attachment_url( $media_video_id ),
				// 'mime'     => '',
			];

			$element_settings = [
				'videoType'    => 'media',
				'media'        => $media,
				'fileAutoplay' => strpos( $output, ' autoplay' ) !== false,
				'fileControls' => strpos( $output, ' controls' ) !== false,
				'fileLoop'     => strpos( $output, ' loop' ) !== false,
				'fileMute'     => strpos( $output, ' muted' ) !== false,
				'fileInline'   => strpos( $output, ' playsinline' ) !== false,
			];

			if ( strpos( $output, ' preload="auto"' ) !== false ) {
				$element_settings['filePreload'] = 'auto';
			}

			return $element_settings;
		}

		// Type: File URL
		$video_url_parts = explode( '"', $output );
		$video_url       = '';

		foreach ( $video_url_parts as $video_url_part ) {
			if ( filter_var( $video_url_part, FILTER_VALIDATE_URL ) ) {
				$video_url = $video_url_part;
			}
		}

		if ( $video_url ) {
			$element_settings = [
				'videoType'    => 'file',
				'fileUrl'      => $video_url,
				'fileAutoplay' => strpos( $output, ' autoplay' ) !== false,
				'fileControls' => strpos( $output, ' controls' ) !== false,
				'fileLoop'     => strpos( $output, ' loop' ) !== false,
				'fileMute'     => strpos( $output, ' muted' ) !== false,
				'fileInline'   => strpos( $output, ' playsinline' ) !== false,
			];

			if ( strpos( $output, ' preload="auto"' ) !== false ) {
				$element_settings['filePreload'] = 'auto';
			}

			return $element_settings;
		}
	}

	/**
	 * Helper function to parse the settings when videoType = meta
	 *
	 * @return array
	 */
	public function get_normalized_video_settings( $settings = [] ) {
		if ( empty( $settings['videoType'] ) ) {
			return $settings;
		}

		if ( $settings['videoType'] === 'youtube' ) {

			if ( ! empty( $settings['youTubeId'] ) ) {
				$settings['youTubeId'] = $this->render_dynamic_data( $settings['youTubeId'] );
			}

			if ( ! empty( $settings['iframeTitle'] ) ) {
				$settings['iframeTitle'] = $this->render_dynamic_data( $settings['iframeTitle'] );
			}

			return $settings;
		}

		if ( $settings['videoType'] === 'vimeo' ) {

			if ( ! empty( $settings['vimeoId'] ) ) {
				$settings['vimeoId'] = $this->render_dynamic_data( $settings['vimeoId'] );
			}

			if ( ! empty( $settings['iframeTitle'] ) ) {
				$settings['iframeTitle'] = $this->render_dynamic_data( $settings['iframeTitle'] );
			}

			if ( ! empty( $settings['vimeoHash'] ) ) {
				$settings['vimeoHash'] = $this->render_dynamic_data( $settings['vimeoHash'] );
			}

			return $settings;
		}

		// Check 'file' and 'meta' videoType for dynamic data
		$dynamic_data = false;

		if ( $settings['videoType'] === 'file' && ! empty( $settings['fileUrl'] ) && strpos( $settings['fileUrl'], '{' ) === 0 ) {
			$dynamic_data = $settings['fileUrl'];
		}

		if ( $settings['videoType'] === 'meta' && ! empty( $settings['useDynamicData'] ) ) {
			$dynamic_data = $settings['useDynamicData'];
		}

		if ( ! $dynamic_data ) {
			return $settings;
		}

		$meta_video_url = $this->render_dynamic_data_tag( $dynamic_data, 'link' );

		if ( empty( $meta_video_url ) ) {
			return $settings;
		}

		// Is YouTube video
		if ( preg_match( '%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $meta_video_url, $matches ) ) {
			// Regex from @see: https://gist.github.com/ghalusa/6c7f3a00fd2383e5ef33
			$settings['youTubeId'] = $matches[1];
			$settings['videoType'] = 'youtube';

			if ( isset( $settings['fileAutoplay'] ) ) {
				$settings['youtubeAutoplay'] = $settings['fileAutoplay'];
			} else {
				unset( $settings['youtubeAutoplay'] );
			}

			if ( isset( $settings['fileControls'] ) ) {
				$settings['youtubeControls'] = $settings['fileControls'];
			} else {
				unset( $settings['youtubeControls'] );
			}

			if ( isset( $settings['fileLoop'] ) ) {
				$settings['youtubeLoop'] = $settings['fileLoop'];
			} else {
				unset( $settings['youtubeLoop'] );
			}

			if ( isset( $settings['fileMute'] ) ) {
				$settings['youtubeMute'] = $settings['fileMute'];
			} else {
				unset( $settings['youtubeMute'] );
			}
		}

		// Is Vimeo video
		elseif ( preg_match( '%^https?:\/\/(?:www\.|player\.)?vimeo.com\/(?:channels\/(?:\w+\/)?|groups\/([^\/]*)\/videos\/|album\/(\d+)\/video\/|video\/|)(\d+)(?:$|\/|\?)(?:[?]?.*)$%im', $meta_video_url, $matches ) ) {
			// Regex from @see: https://gist.github.com/anjan011/1fcecdc236594e6d700f
			$settings['vimeoId']   = $matches[3];
			$settings['videoType'] = 'vimeo';

			if ( isset( $settings['fileAutoplay'] ) ) {
				$settings['vimeoAutoplay'] = $settings['fileAutoplay'];
			} else {
				unset( $settings['vimeoAutoplay'] );
			}

			if ( isset( $settings['fileLoop'] ) ) {
				$settings['vimeoLoop'] = $settings['fileLoop'];
			} else {
				unset( $settings['vimeoLoop'] );
			}

			if ( isset( $settings['fileMute'] ) ) {
				$settings['vimeoMute'] = $settings['fileMute'];
			} else {
				unset( $settings['vimeoMute'] );
			}

		} else {
			// Url of a video file (either hosted or external)
			$settings['fileUrl']   = $meta_video_url;
			$settings['videoType'] = 'file';
		}

		// Later the settings are used to control the video and the custom field should not be present
		unset( $settings['useDynamicData'] );

		return $settings;
	}

	/**
	 * Get the video image image URL
	 *
	 * @param array $settings
	 *
	 * @since 1.7.2
	 */
	public function get_preview_image_url( $settings = [] ) {
		// Get source from settings as parse settings already processed the dynamic data
		$source = $this->settings['videoType'] ?? false;

		// Return: Video type is not YouTube or Vimeo
		if ( ! in_array( $source, [ 'youtube', 'vimeo' ] ) ) {
			return false;
		}

		// Return: Autoplay enabled
		if ( ( $source === 'youtube' && isset( $settings['youtubeAutoplay'] ) || ( $source === 'vimeo' && isset( $settings['vimeoAutoplay'] ) ) ) ) {
			return false;
		}

		// Return: No perview image type set
		$preview_image_type = $this->settings['previewImage'] ?? '';
		if ( ! $preview_image_type ) {
			return false;
		}

		$preview_image = $preview_image_type === 'custom' && ! empty( $this->settings['previewImageCustom'] ) ? $this->get_video_image_by_key( 'previewImageCustom' ) : false;

		// STEP: Preview image
		if ( ! empty( $preview_image['url'] ) ) {
			return $preview_image['url'];
		}

		// Default: Youtube or Vimeo image
		$video_type = $settings['videoType'] ?? false;

		// STEP: Get YouTube video preview image from API
		if ( $video_type === 'youtube' ) {
			return "https://img.youtube.com/vi/{$settings['youTubeId']}/hqdefault.jpg";
		}

		// STEP: Get the Vimeo video preview image from API
		if ( $video_type === 'vimeo' ) {
			$video_data = wp_remote_get( "https://vimeo.com/api/v2/video/{$settings['vimeoId']}.json" );

			// 404 error is returned if the video is not found, so we need to check for that
			if ( ! is_wp_error( $video_data ) && $video_data['response']['code'] !== 404 ) {
				$video_data = json_decode( $video_data['body'] );

				// Ensure that the thumbnail_large exists before using it
				if ( ! empty( $video_data[0]->thumbnail_large ) ) {
					return $video_data[0]->thumbnail_large;
				}
			}
		}

		// Image source empty: Use Theme Style "Preview image fallback image"
		if ( ! empty( $this->theme_styles['previewImageFallback']['url'] ) ) {
			return $this->theme_styles['previewImageFallback']['url'];
		}
	}

	/**
	 * Get the image by control key
	 *
	 * Similar to get_normalized_image_settings() in the image element.
	 *
	 * Might be a fix image, a dynamic data tag or external URL.
	 *
	 * @since 1.8.5
	 *
	 * @return array
	 */
	public function get_video_image_by_key( $control_key = '' ) {
		if ( empty( $control_key ) ) {
			return [];
		}

		$image = isset( $this->settings[ $control_key ] ) ? $this->settings[ $control_key ] : false;

		if ( ! $image ) {
			return [];
		}

		// STEP: Set image size
		$image['size'] = isset( $image['size'] ) && ! empty( $image['size'] ) ? $image['size'] : BRICKS_DEFAULT_IMAGE_SIZE;

		// STEP: Image ID or URL from dynamic data
		if ( ! empty( $image['useDynamicData'] ) ) {
			$dynamic_image = $this->render_dynamic_data_tag( $image['useDynamicData'], 'image', [ 'size' => $image['size'] ] );

			if ( ! empty( $dynamic_image[0] ) ) {
				if ( is_numeric( $dynamic_image[0] ) ) {
					// Use the image ID to populate and set $dynamic_image['url']
					$image['id']  = $dynamic_image[0];
					$image['url'] = wp_get_attachment_image_url( $image['id'], $image['size'] );
				} else {
					$image['url'] = $dynamic_image[0];
				}
			} else {
				return [];
			}
		}

		// Set image ID
		$image['id'] = empty( $image['id'] ) ? 0 : $image['id'];

		// Set image URL
		if ( ! isset( $image['url'] ) ) {
			$image['url'] = ! empty( $image['id'] ) ? wp_get_attachment_image_url( $image['id'], $image['size'] ) : false;
		}

		return $image;
	}
}
