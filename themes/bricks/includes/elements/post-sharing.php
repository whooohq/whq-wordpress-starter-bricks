<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Post_Sharing extends Element {
	public $category     = 'single';
	public $name         = 'post-sharing';
	public $icon         = 'ti-share';
	public $css_selector = 'a';

	public function get_label() {
		return esc_html__( 'Social Sharing', 'bricks' );
	}

	public function enqueue_scripts() {
		// balloon.css tooltip library
		wp_enqueue_style( 'bricks-tooltips' );
	}

	public function set_controls() {
		$this->controls['_margin']['css'][0]['selector'] = 'li';
		$this->controls['_width']['css'][0]['selector']  = 'a';

		$this->controls['items'] = [
			'tab'           => 'content',
			'label'         => esc_html__( 'Share via', 'bricks' ),
			'titleProperty' => 'service',
			'type'          => 'repeater',
			'selector'      => 'li',
			'fields'        => [
				'service'    => [
					'label'     => esc_html__( 'Service', 'bricks' ),
					'type'      => 'select',
					'clearable' => false,
					'options'   => [
						'facebook'  => 'Facebook',
						'twitter'   => 'X',
						'linkedin'  => 'LinkedIn',
						'whatsapp'  => 'WhatsApp',
						'pinterest' => 'Pinterest',
						'telegram'  => 'Telegram',
						'vkontakte' => 'VKontakte',
						'email'     => esc_html__( 'Email', 'bricks' ),
					],
				],

				'excerpt'    => [
					'label'    => esc_html__( 'Excerpt', 'bricks' ),
					'type'     => 'checkbox',
					'required' => [ 'service', '=', 'whatsapp' ],
				],

				'icon'       => [
					'label' => esc_html__( 'Icon', 'bricks' ),
					'type'  => 'icon',
				],

				'background' => [
					'type'  => 'color',
					'label' => esc_html__( 'Background', 'bricks' ),
					'css'   => [
						[
							'property' => 'background-color',
							'selector' => 'a',
						],
					],
				],

				'color'      => [
					'type'  => 'color',
					'label' => esc_html__( 'Color', 'bricks' ),
					'css'   => [
						[
							'property' => 'color',
							'selector' => 'a',
						],
					],
				],
			],
			'default'       => [
				[ 'service' => 'facebook' ],
				[ 'service' => 'twitter' ],
				[ 'service' => 'linkedin' ],
				[ 'service' => 'whatsapp' ],
				[ 'service' => 'pinterest' ],
				[ 'service' => 'telegram' ],
				[ 'service' => 'vkontakte' ],
				[ 'service' => 'email' ],
			],
		];

		$this->controls['brandColors'] = [
			'tab'     => 'content',
			'label'   => esc_html__( 'Use brand colors', 'bricks' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['direction'] = [
			'tab'    => 'content',
			'label'  => esc_html__( 'Direction', 'bricks' ),
			'type'   => 'direction',
			'css'    => [
				[
					'property' => 'flex-direction',
					'selector' => '',
				],
			],
			'inline' => true,
		];

		// LINKS

		$this->controls['linksSeparator'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Links', 'bricks' ),
			'type'  => 'separator',
		];

		$this->controls['newTab'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Open in new tab', 'bricks' ),
			'type'  => 'checkbox',
		];

		$this->controls['linkRel'] = [
			'tab'            => 'content',
			'label'          => esc_html__( 'Rel attribute', 'bricks' ),
			'type'           => 'text',
			'inline'         => true,
			'hasDynamicData' => false,
			'placeholder'    => 'nofollow',
		];
	}

	public function render() {
		$settings = $this->settings;
		$items    = ! empty( $settings['items'] ) ? $settings['items'] : false;

		if ( ! $items ) {
			return $this->render_element_placeholder(
				[
					'title' => esc_html__( 'No sharing option selected.', 'bricks' ),
				]
			);
		}

		global $post;

		$post = get_post( $this->post_id );

		/**
		 * Get request URI is possible (as permalink could have been altered by a plugin)
		 *
		 * Fallback: permalink
		 *
		 * @since 1.9.3 - Use Request URI only if we aren't looping. Otherwise, use the permalink.
		 *
		 * @since 1.9.2
		 */
		$request_uri = ! Query::is_looping() && ! empty( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : false;
		$url         = $request_uri ? home_url( $request_uri ) : get_the_permalink();
		$url         = rawurlencode( html_entity_decode( $url, ENT_COMPAT, 'UTF-8' ) );
		$image       = rawurlencode( html_entity_decode( wp_get_attachment_url( get_post_thumbnail_id() ), ENT_COMPAT, 'UTF-8' ) );
		$title       = rawurlencode( html_entity_decode( get_the_title(), ENT_COMPAT, 'UTF-8' ) );

		// Ignore in builder MutationObserver
		if ( isset( $settings['brandColors'] ) ) {
			$this->set_attribute( '_root', 'class', 'brand-colors' );
		}

		// Link rel attribute (since 1.5)
		$rel_attribute = ! empty( $settings['linkRel'] ) ? trim( $settings['linkRel'] ) : 'nofollow';

		echo "<ul {$this->render_attributes( '_root' )}>";

		foreach ( $items as $index => $item ) {
			$service    = ! empty( $item['service'] ) ? $item['service'] : false;
			$aria_label = '';

			if ( ! $service ) {
				continue;
			}

			$icon = ! empty( $item['icon'] ) ? self::render_icon( $item['icon'] ) : false;

			$data = false;

			switch ( $service ) {
				case 'facebook':
					$aria_label = 'Facebook';

					$data = [
						'url'     => wp_is_mobile() ? 'https://m.facebook.com/sharer.php?u=' . $url : "https://www.facebook.com/sharer.php?u=$url&amp;picture=$image&amp;title=$title",
						// translators: %s: Service name
						'tooltip' => sprintf( esc_html__( 'Share on %s', 'bricks' ), $aria_label ),
						'class'   => 'facebook',
						'icon'    => $icon ? $icon : Helpers::file_get_contents( BRICKS_PATH_ASSETS . 'svg/frontend/facebook.svg' ),
					];
					break;

				case 'twitter':
					$aria_label = 'X';

					$data = [
						'url'     => "https://x.com/share?text=$title&amp;url=$url",
						// translators: %s: Service name
						'tooltip' => sprintf( esc_html__( 'Share on %s', 'bricks' ), $aria_label ),
						'class'   => 'twitter',
						'icon'    => $icon ? $icon : Helpers::file_get_contents( BRICKS_PATH_ASSETS . 'svg/frontend/x.svg' ),
					];
					break;

				case 'linkedin':
					$aria_label = 'LinkedIn';

					$data = [
						'url'     => "https://www.linkedin.com/shareArticle?mini=true&amp;url=$url&amp;title=$title",
						// translators: %s: Service name
						'tooltip' => sprintf( esc_html__( 'Share on %s', 'bricks' ), $aria_label ),
						'class'   => 'linkedin',
						'icon'    => $icon ? $icon : Helpers::file_get_contents( BRICKS_PATH_ASSETS . 'svg/frontend/linkedin.svg' ),
					];
					break;

				case 'whatsapp':
					$aria_label = 'WhatsApp';

					$text = isset( $item['excerpt'] ) ? get_the_excerpt( $post ) : '';

					$data = [
						'url'     => "https://api.whatsapp.com/send?text=*{$title}*+%0A{$text}%0A{$url}",
						// translators: %s: Service name
						'tooltip' => sprintf( esc_html__( 'Share on %s', 'bricks' ), $aria_label ),
						'class'   => 'whatsapp',
						'icon'    => $icon ? $icon : Helpers::file_get_contents( BRICKS_PATH_ASSETS . 'svg/frontend/whatsapp.svg' ),
					];
					break;

				case 'pinterest':
					$aria_label = 'Pinterest';

					$data = [
						'url'     => "https://pinterest.com/pin/create/button/?url=$url&amp;media=$image",
						// translators: %s: Service name
						'tooltip' => sprintf( esc_html__( 'Share on %s', 'bricks' ), $aria_label ),
						'class'   => 'pinterest',
						'icon'    => $icon ? $icon : Helpers::file_get_contents( BRICKS_PATH_ASSETS . 'svg/frontend/pinterest.svg' ),
					];
					break;

				case 'telegram':
					$aria_label = 'Telegram';

					$data = [
						'url'     => "https://t.me/share/url?url={$url}&text={$title}",
						// translators: %s: Service name
						'tooltip' => sprintf( esc_html__( 'Share on %s', 'bricks' ), $aria_label ),
						'class'   => 'telegram',
						'icon'    => $icon ? $icon : Helpers::file_get_contents( BRICKS_PATH_ASSETS . 'svg/frontend/telegram.svg' ),
					];
					break;

				case 'vkontakte':
					$aria_label = 'VKontakte';

					$data = [
						'url'     => "https://vk.com/share.php?url={$url}&title={$title}&image=$image",
						// translators: %s: Service name
						'tooltip' => sprintf( esc_html__( 'Share on %s', 'bricks' ), $aria_label ),
						'class'   => 'vkontakte',
						'icon'    => $icon ? $icon : Helpers::file_get_contents( BRICKS_PATH_ASSETS . 'svg/frontend/vkontakte.svg' ),
					];
					break;

				case 'email':
					$aria_label = esc_html__( 'Email', 'bricks' );

					$data = [
						'url'     => "mailto:?subject=$title&amp;body=$url",
						'tooltip' => esc_html__( 'Share via email', 'bricks' ),
						'class'   => 'email',
						'icon'    => $icon ? $icon : Helpers::file_get_contents( BRICKS_PATH_ASSETS . 'svg/frontend/email.svg' ),
					];
					break;
			}

			if ( $data ) {
				echo "<li data-balloon=\"{$data['tooltip']}\" data-balloon-pos=\"top\">";

				$this->set_attribute( "link-{$index}", 'class', $data['class'] );
				$this->set_attribute( "link-{$index}", 'href', $data['url'] );
				$this->set_attribute( "link-{$index}", 'rel', $rel_attribute );
				$this->set_attribute( "link-{$index}", 'aria-label', $aria_label );

				if ( isset( $settings['newTab'] ) ) {
					$this->set_attribute( "link-{$index}", 'target', '_blank' );
				}

				echo "<a {$this->render_attributes( "link-{$index}" )}>" . self::render_svg( $data['icon'] ) . '</a>';

				echo '</li>';
			}
		}

		echo '</ul>';
	}
}
