<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Instagram_Feed extends \Bricks\Element {
	public $category = 'media';
	public $name     = 'instagram-feed';
	public $icon     = 'ion-logo-instagram';

	public function get_label() {
		return esc_html__( 'Instagram feed', 'bricks' );
	}

	public function get_keywords() {
		return [ 'instagram', 'feed', 'social' ];
	}

	public function set_controls() {
		$this->controls['instagramAccessToken'] = [
			'type'     => 'info',
			'content'  => sprintf(
				// translators: %s: Link to the API Keys settings page
				esc_html__( 'Instagram access token required! Add in WordPress dashboard under %s', 'bricks' ),
				'<a href="' . Helpers::settings_url( '#tab-api-keys' ) . '" target="_blank">Bricks > ' . esc_html__( 'Settings', 'bricks' ) . ' > API keys</a>'
			),
			'required' => [ 'instagramAccessToken', '=', '', 'globalSettings' ],
		];

		// LAYOUT
		$this->controls['layoutSep'] = [
			'type'  => 'separator',
			'label' => esc_html__( 'Layout', 'bricks' ),
		];

		$this->controls['columns'] = [
			'label'       => esc_html__( 'Columns', 'bricks' ),
			'type'        => 'number',
			'css'         => [
				[
					'property' => 'grid-template-columns',
					'selector' => 'ul',
					'value'    => 'repeat(%s, 1fr)',
				],
			],
			'placeholder' => 3,
		];

		$this->controls['numberOfPosts'] = [
			'label'       => esc_html__( 'Posts', 'bricks' ),
			'type'        => 'number',
			'min'         => 1,
			'max'         => 100,
			'placeholder' => 9,
		];

		// IMAGE
		$this->controls['imageSep'] = [
			'type'  => 'separator',
			'label' => esc_html__( 'Image', 'bricks' ),
		];

		$this->controls['imageObjectFit'] = [
			'type'        => 'select',
			'label'       => esc_html__( 'Object fit', 'bricks' ),
			'options'     => $this->control_options['objectFit'],
			'inline'      => true,
			'css'         => [
				[
					'property' => 'object-fit',
					'selector' => 'img',
				],
			],
			'placeholder' => esc_html__( 'Cover', 'bricks' ),
		];

		$this->controls['imageAspectRatio'] = [
			'label'  => esc_html__( 'Aspect ratio', 'bricks' ),
			'type'   => 'text',
			'inline' => true,
			'small'  => true,
			'css'    => [
				[
					'property' => 'aspect-ratio',
					'selector' => 'img',
				],
			],
		];

		$this->controls['imageHeight'] = [
			'type'        => 'number',
			'label'       => esc_html__( 'Height', 'bricks' ),
			'units'       => true,
			'css'         => [
				[
					'property' => 'height',
					'selector' => 'img',
				],
			],
			'placeholder' => '100%',
		];

		$this->controls['imageWidth'] = [
			'type'        => 'number',
			'label'       => esc_html__( 'Width', 'bricks' ),
			'units'       => true,
			'css'         => [
				[
					'property' => 'width',
					'selector' => 'img',
				],
			],
			'placeholder' => '100%',
		];

		$this->controls['imageGap'] = [
			'label' => esc_html__( 'Gap', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'css'   => [
				[
					'property' => 'gap',
					'selector' => 'ul',
				],
			],
		];

		$this->controls['imageBorder'] = [
			'label' => esc_html__( 'Border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => 'img',
				],
			],
		];

		$this->controls['imageBorder'] = [
			'label' => esc_html__( 'Border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => 'img',
				],
			],
		];

		$this->controls['imageLink'] = [
			'type'  => 'checkbox',
			'label' => esc_html__( 'Link', 'bricks' ),
		];

		// CAROUSEL
		$this->controls['carouselSep'] = [
			'type'  => 'separator',
			'label' => esc_html__( 'Carousel', 'bricks' ),
		];

		// $this->controls['skipCarousel'] = [
		// 'label' => esc_html__( 'Skip carousel', 'bricks' ),
		// 'type'  => 'checkbox',
		// ];

		$this->controls['carouselIcon'] = [
			'label'    => esc_html__( 'Icon', 'bricks' ),
			'type'     => 'icon',
			'required' => [ 'skipCarousel', '=', '' ],
		];

		$this->controls['carouselIconColor'] = [
			'label'    => esc_html__( 'Icon color', 'bricks' ),
			'type'     => 'color',
			'css'      => [
				[
					'property' => 'color',
					'selector' => '.brx-icon.carousel',
				],
			],
			'required' => [ 'skipCarousel', '=', '' ],
		];

		$this->controls['carouselIconSize'] = [
			'label'    => esc_html__( 'Icon size', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'height',
					'selector' => '.brx-icon.carousel svg',
				],
				[
					'property' => 'width',
					'selector' => '.brx-icon.carousel svg',
				],
				[
					'property' => 'font-size',
					'selector' => '.brx-icon.carousel',
				],
			],
			'required' => [ 'skipCarousel', '=', '' ],
		];

		$this->controls['carouselIconPosition'] = [
			'type'        => 'dimensions',
			'label'       => esc_html__( 'Icon position', 'bricks' ),
			'css'         => [
				[
					// 'property' => 'position',
					'selector' => '.brx-icon.carousel',
				],
			],
			'placeholder' => [
				'top'   => 10,
				'right' => 10,
			],
			'required'    => [ 'skipCarousel', '=', '' ],
		];

		// VIDEO
		$this->controls['videoSep'] = [
			'type'  => 'separator',
			'label' => esc_html__( 'Video', 'bricks' ),
		];

		// $this->controls['skipVideo'] = [
		// 'label' => esc_html__( 'Skip video', 'bricks' ),
		// 'type'  => 'checkbox',
		// ];

		$this->controls['videoIcon'] = [
			'label'    => esc_html__( 'Icon', 'bricks' ),
			'type'     => 'icon',
			'required' => [ 'skipVideo', '=', '' ],
		];

		$this->controls['videoIconColor'] = [
			'label'    => esc_html__( 'Icon color', 'bricks' ),
			'type'     => 'color',
			'css'      => [
				[
					'property' => 'color',
					'selector' => '.brx-icon.video',
				],
			],
			'required' => [ 'skipVideo', '=', '' ],
		];

		$this->controls['videoIconSize'] = [
			'label'    => esc_html__( 'Icon size', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'height',
					'selector' => '.brx-icon.video svg',
				],
				[
					'property' => 'width',
					'selector' => '.brx-icon.video svg',
				],
				[
					'property' => 'font-size',
					'selector' => '.brx-icon.video',
				],
			],
			'required' => [ 'skipVideo', '=', '' ],
		];

		$this->controls['videoIconPosition'] = [
			'type'        => 'dimensions',
			'label'       => esc_html__( 'Icon position', 'bricks' ),
			'css'         => [
				[
					// 'property' => 'position',
					'selector' => '.brx-icon.video',
				],
			],
			'placeholder' => [
				'top'   => 10,
				'right' => 10,
			],
			'required'    => [ 'skipVideo', '=', '' ],
		];

		// CAPTION
		$this->controls['captionSep'] = [
			'type'  => 'separator',
			'label' => esc_html__( 'Caption', 'bricks' ),
		];

		$this->controls['caption'] = [
			'type'  => 'checkbox',
			'label' => esc_html__( 'Enable', 'bricks' ),
		];

		$this->controls['captionBackground'] = [
			'label'    => esc_html__( 'Background color', 'bricks' ),
			'type'     => 'color',
			'css'      => [
				[
					'property' => 'background-color',
					'selector' => '.caption',
				],
			],
			'required' => [ 'caption', '=', true ],
		];

		$this->controls['captionBorder'] = [
			'label'    => esc_html__( 'Border', 'bricks' ),
			'type'     => 'border',
			'css'      => [
				[
					'property' => 'border',
					'selector' => '.caption',
				],
			],
			'required' => [ 'caption', '=', true ],
		];

		$this->controls['captionTypography'] = [
			'label'    => esc_html__( 'Typography', 'bricks' ),
			'type'     => 'typography',
			'css'      => [
				[
					'property' => 'font',
					'selector' => '.caption',
				],
			],
			'required' => [ 'caption', '=', true ],
		];

		// FOLLOW
		$this->controls['followSep'] = [
			'type'  => 'separator',
			'label' => esc_html__( 'Follow', 'bricks' ),
		];

		$this->controls['followText'] = [
			'label'   => esc_html__( 'Text', 'bricks' ),
			'type'    => 'text',
			'default' => 'Follow us @yourhandle',
		];

		$this->controls['followPosition'] = [
			'label'       => esc_html__( 'Position', 'bricks' ),
			'type'        => 'select',
			'inline'      => true,
			'options'     => [
				'top'    => esc_html__( 'Top', 'bricks' ),
				'bottom' => esc_html__( 'Bottom', 'bricks' ),
			],
			'placeholder' => esc_html__( 'Bottom', 'bricks' ),
		];

		$this->controls['followIcon'] = [
			'label'   => esc_html__( 'Icon', 'bricks' ),
			'type'    => 'icon',
			'default' => [
				'library' => 'ionicons',
				'icon'    => 'ion-logo-instagram',
			],
			'css'     => [
				[
					'selector' => '.follow-icon', // Target SVG file
				],
			],
		];

		$this->controls['followTypography'] = [
			'label' => esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.follow',
				],
			],
		];

		// CACHE
		$this->controls['cacheSep'] = [
			'type'  => 'separator',
			'label' => esc_html__( 'Cache', 'bricks' ),
		];

		$this->controls['cacheDuration'] = [
			'label'       => esc_html__( 'Duration', 'bricks' ),
			'type'        => 'select',
			'options'     => [
				'1800'   => esc_html__( '30 minutes', 'bricks' ),
				'3600'   => esc_html__( '1 hour', 'bricks' ),
				'86400'  => esc_html__( '1 day', 'bricks' ),
				'604800' => esc_html__( '1 week', 'bricks' ),
			],
			'inline'      => true,
			'placeholder' => esc_html__( '1 hour', 'bricks' ),
		];
	}

	public function render() {
		// STEP: Get the access token
		$instagram_access_token = \Bricks\Database::get_setting( 'instagramAccessToken' );

		// Return: No access token set
		if ( ! $instagram_access_token ) {
			return $this->render_element_placeholder(
				[
					'icon-class' => 'ion-md-warning',
					'title'      => esc_html__( 'Please connect your Instagram account.', 'bricks' ),
				]
			);
		}

		// STEP: Get the settings
		$settings = $this->settings;

		// Number of columns
		$columns = is_numeric( $settings['columns'] ?? null ) && $settings['columns'] >= 1 && $settings['columns'] <= 6
			? intval( $settings['columns'] )
			: 3;

		// Number of posts to fetch
		$number_of_posts = is_numeric( $settings['numberOfPosts'] ?? null ) && $settings['numberOfPosts'] >= 0
			? intval( $settings['numberOfPosts'] )
			: 9;

		// Cache duration (default: 1 hour)
		$cache_duration = $settings['cacheDuration'] ?? 3600;

		// Follow link position
		$follow_position = $settings['followPosition'] ?? 'bottom';

		// STEP: Cache the data in transient

		// Create a unique key for the transient to ensure each feed is unique and cached independently
		$transient_key = 'instagram_feed_' . md5( $instagram_access_token . $number_of_posts . $cache_duration );

		// Attempt to retrieve cached data
		$data = get_transient( $transient_key );

		// STEP: If no cache exists, we fetch fresh data

		if ( $data === false ) {
			// Construct the API URL for fetching Instagram posts
			$media_api_url = "https://graph.instagram.com/me/media?fields=id,caption,media_type,media_url,thumbnail_url,permalink,timestamp&access_token=$instagram_access_token&limit=$number_of_posts";

			// Construct the API URL for fetching the Instagram account details
			$account_api_url = "https://graph.instagram.com/me?fields=username&access_token=$instagram_access_token";

			// Fetch and decode Instagram posts
			$media_response = wp_remote_get( $media_api_url );

			if ( is_wp_error( $media_response ) || wp_remote_retrieve_response_code( $media_response ) != 200 ) {
				return $this->render_element_placeholder(
					[
						'icon-class' => 'ion-md-warning',
						'title'      => esc_html__( 'Failed to fetch Instagram posts.', 'bricks' ),
					]
				);
			}

			$media_body = json_decode( wp_remote_retrieve_body( $media_response ), true );

			if ( ! isset( $media_body['data'] ) ) {
				return $this->render_element_placeholder(
					[
						'icon-class' => 'ion-md-warning',
						'title'      => esc_html__( 'No Instagram posts found.', 'bricks' ),
					]
				);
			}

			// Fetch and decode Instagram account details
			$response = wp_remote_get( $account_api_url );

			if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) != 200 ) {
				return $this->render_element_placeholder(
					[
						'icon-class' => 'ion-md-warning',
						'title'      => esc_html__( 'Failed to fetch Instagram account details.', 'bricks' ),
					]
				);
			}

			$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( ! isset( $response_body['username'] ) ) {
				return $this->render_element_placeholder(
					[
						'icon-class' => 'ion-md-warning',
						'title'      => esc_html__( 'No Instagram account found.', 'bricks' ),
					]
				);
			}

			$data = [
				'username' => $response_body['username'],
				'media'    => $media_body['data'],
			];

			// Save the data to cache for the specified duration in transient
			set_transient( $transient_key, $data, intval( $cache_duration ) );
		}

		// STEP: Extract the posts and username
		$follow_icon = $settings['followIcon']['icon'] ?? false;
		$follow_text = $settings['followText'] ?? '';
		$follow_html = '';

		if ( $follow_icon || $follow_text ) {
			$username    = $data['username'] ?? '';
			$follow_html = '<a class="follow" href="https://instagram.com/' . $username . '" target="_blank">';

			if ( $follow_icon ) {
				$follow_html .= $this->render_icon( $settings['followIcon'] );
			}

			if ( $follow_text ) {
				$follow_html .= $follow_text;
			}

			$follow_html .= '</a>';
		}

		$output = "<div {$this->render_attributes( '_root' )}>";

		// Render follow section at the top
		if ( $follow_position == 'top' ) {
			$output .= $follow_html;
		}

		$output .= '<ul>';

		$posts = $data['media'] ?? [];

		foreach ( $posts as $post ) {
			$caption    = $post['caption'] ?? '';
			$media_type = $post['media_type'] ?? '';

			// Skip rendering video (setting or no thumbnail URL)
			if ( $media_type == 'VIDEO' && ( isset( $settings['skipVideo'] ) || ! isset( $post['thumbnail_url'] ) ) ) {
				continue;
			}

			// Skip rendering carousel (setting)
			if ( $media_type == 'CAROUSEL_ALBUM' && isset( $settings['skipcarousel'] ) ) {
				continue;
			}

			$output .= '<li>';

			if ( isset( $settings['imageLink'] ) ) {
				$output .= '<a href="' . esc_url( $post['permalink'] ) . '" target="_blank">';
			}

			switch ( $media_type ) {
				case 'VIDEO':
					$output .= '<img src="' . esc_url( $post['thumbnail_url'] ) . '" alt="' . esc_attr( $caption ) . '">';

					// Video play icon
					$icon = '<svg aria-label="' . esc_html__( 'Video', 'bricks' ) . '" fill="currentcolor" viewBox="0 0 24 24"><path d="M5.888 22.5a3.46 3.46 0 0 1-1.721-.46l-.003-.002a3.451 3.451 0 0 1-1.72-2.982V4.943a3.445 3.445 0 0 1 5.163-2.987l12.226 7.059a3.444 3.444 0 0 1-.001 5.967l-12.22 7.056a3.462 3.462 0 0 1-1.724.462Z"></path></svg>';

					if ( ! empty( $settings['videoIcon'] ) ) {
						$icon = self::render_icon( $settings['videoIcon'] );
					}

					if ( ! empty( $icon ) ) {
						$output .= '<span class="brx-icon video">' . $icon . '</span>';
					}
					break;

				case 'CAROUSEL_ALBUM':
					if ( isset( $post['media_url'] ) ) {
						$image_url = $post['media_url'];
					}

					// Handle no media_url cases (@since 1.9.3)
					else {
						// Create a unique key for the transient to cache the children
						$transient_key_children = 'instagram_carousel_children_' . $post['id'];

						// Attempt to retrieve cached data
						$children_data = get_transient( $transient_key_children );

						// If no cache exists, fetch the children
						if ( $children_data === false ) {
							$endpoint = 'https://graph.instagram.com/' . $post['id'] . "/children?fields=id,media_type,media_url,thumbnail_url&access_token=$instagram_access_token";
							$response = wp_remote_get( $endpoint );

							if ( ! is_wp_error( $response ) ) {
								$children_data = wp_remote_retrieve_body( $response );

								// Cache the response using the user-configured cache duration
								set_transient( $transient_key_children, $children_data, $cache_duration );
							}
						}

						$data      = json_decode( $children_data, true );
						$image_url = '';

						foreach ( $data['data'] as $child ) {
							// If it's an image, use its media_url
							if ( $child['media_type'] === 'IMAGE' && isset( $child['media_url'] ) ) {
								$image_url = $child['media_url'];
								break;
							}

							// If it's a video, use its thumbnail_url
							if ( $child['media_type'] === 'VIDEO' && isset( $child['thumbnail_url'] ) ) {
								$image_url = $child['thumbnail_url'];
								break;
							}
						}
					}

					if ( empty( $image_url ) ) {
						break;
					}

					$output .= '<img src="' . esc_url( $image_url ) . '" alt="' . esc_attr( $caption ) . '">';

					// Carousel icon
					$icon = '<svg aria-label="' . esc_html__( 'Carousel', 'bricks' ) . '" fill="currentcolor" viewBox="0 0 48 48"><path d="M34.8 29.7V11c0-2.9-2.3-5.2-5.2-5.2H11c-2.9 0-5.2 2.3-5.2 5.2v18.7c0 2.9 2.3 5.2 5.2 5.2h18.7c2.8-.1 5.1-2.4 5.1-5.2zM39.2 15v16.1c0 4.5-3.7 8.2-8.2 8.2H14.9c-.6 0-.9.7-.5 1.1 1 1.1 2.4 1.8 4.1 1.8h13.4c5.7 0 10.3-4.6 10.3-10.3V18.5c0-1.6-.7-3.1-1.8-4.1-.5-.4-1.2 0-1.2.6z"></path></svg>';
					if ( ! empty( $settings['carouselIcon'] ) ) {
						$icon = self::render_icon( $settings['carouselIcon'] );
					}

					if ( ! empty( $icon ) ) {
						$output .= '<span class="brx-icon carousel">' . $icon . '</span>';
					}
					break;

				case 'IMAGE':
				default:
					$output .= '<img src="' . esc_url( $post['media_url'] ) . '" alt="' . esc_attr( $caption ) . '">';
					break;
			}

			if ( $caption && isset( $settings['caption'] ) ) {
				$output .= '<p class="caption">' . esc_html( $caption ) . '</p>';
			}

			if ( isset( $settings['imageLink'] ) ) {
				$output .= '</a>';
			}

			$output .= '</li>';
		}

		$output .= '</ul>';

		// Render follow section at the bottom
		if ( $follow_position == 'bottom' ) {
			$output .= $follow_html;
		}

		$output .= '</div>';

		echo $output;
	}
}
