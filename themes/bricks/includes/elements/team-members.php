<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Team_Members extends Element {
	public $category = 'general';
	public $name     = 'team-members';
	public $icon     = 'ti-id-badge';
	public $tag      = 'ul';

	public function get_label() {
		return esc_html__( 'Team Members', 'bricks' );
	}

	public function set_control_groups() {
		$this->control_groups['team-members'] = [
			'title' => esc_html__( 'Team members', 'bricks' ),
			'tab'   => 'content',
		];

		$this->control_groups['layout'] = [
			'title' => esc_html__( 'Layout', 'bricks' ),
			'tab'   => 'content',
		];

		$this->control_groups['image'] = [
			'title' => esc_html__( 'Image', 'bricks' ),
			'tab'   => 'content',
		];

		$this->control_groups['content'] = [
			'title' => esc_html__( 'Content', 'bricks' ),
			'tab'   => 'content',
		];
	}

	public function set_controls() {
		// TEAM MEMBERS

		$this->controls['items'] = [
			'tab'           => 'content',
			'group'         => 'team-members',
			'placeholder'   => esc_html__( 'Team member', 'bricks' ),
			'type'          => 'repeater',
			'titleProperty' => 'title',
			'fields'        => [
				'image'       => [
					'tab'   => 'content',
					'label' => esc_html__( 'Image', 'bricks' ),
					'type'  => 'image',
				],

				'title'       => [
					'tab'   => 'content',
					'label' => esc_html__( 'Title', 'bricks' ),
					'type'  => 'text',
				],

				'subtitle'    => [
					'tab'   => 'content',
					'label' => esc_html__( 'Subtitle', 'bricks' ),
					'type'  => 'text',
				],

				'description' => [
					'tab'   => 'content',
					'label' => esc_html__( 'Content', 'bricks' ),
					'type'  => 'textarea',
				],
			],
			'default'       => [
				[
					'image'       => [
						'full' => 'https://source.unsplash.com/random/600x600?woman',
						'url'  => 'https://source.unsplash.com/random/600x600?woman',
					],
					'title'       => 'Bianca Gosh',
					'subtitle'    => 'CEO',
					'description' => 'Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus.',
				],
				[
					'image'       => [
						'full' => 'https://source.unsplash.com/random/700x700?man',
						'url'  => 'https://source.unsplash.com/random/700x700?man',
					],
					'title'       => 'Linus Slim',
					'subtitle'    => 'CFO',
					'description' => 'Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus.',
				],
				[
					'image'       => [
						'full' => 'https://source.unsplash.com/random/800x800?woman',
						'url'  => 'https://source.unsplash.com/random/800x800?woman',
					],
					'title'       => 'Ilaria Cue',
					'subtitle'    => 'CMO',
					'description' => 'Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus.',
				],
				[
					'image'       => [
						'full' => 'https://source.unsplash.com/random/800x800?man',
						'url'  => 'https://source.unsplash.com/random/800x800?man',
					],
					'title'       => 'Brian Masset',
					'subtitle'    => 'CTO',
					'description' => 'Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus.',
				],
			],
		];

		// LAYOUT

		$this->controls['membersPerRow'] = [
			'tab'   => 'content',
			'group' => 'layout',
			'label' => esc_html__( 'Columns', 'bricks' ),
			'type'  => 'number',
			'min'   => 1,
			'max'   => 6,
			'css'   => [
				[
					'property' => 'grid-template-columns',
					'value'    => 'repeat(%s, 1fr)', // NOTE: Undocumented (@since 1.3)
				],
				[
					'property' => 'grid-auto-flow',
					'value'    => 'unset',
				],
			],
		];

		$this->controls['memberGutter'] = [
			'tab'         => 'content',
			'group'       => 'layout',
			'label'       => esc_html__( 'Gap', 'bricks' ),
			'type'        => 'number',
			'units'       => true,
			'css'         => [
				[
					'property' => 'gap',
				],
			],
			'placeholder' => 20,
		];

		$this->controls['contentBackgroundColor'] = [
			'tab'   => 'content',
			'group' => 'layout',
			'label' => esc_html__( 'Background', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.member',
				],
			],
		];

		$this->controls['contentBorder'] = [
			'tab'   => 'content',
			'group' => 'layout',
			'label' => esc_html__( 'Border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.member',
				],
			],
		];

		$this->controls['contentBoxShadow'] = [
			'tab'   => 'content',
			'group' => 'layout',
			'label' => esc_html__( 'Box shadow', 'bricks' ),
			'type'  => 'box-shadow',
			'css'   => [
				[
					'property' => 'box-shadow',
					'selector' => '.member',
				],
			],
		];

		// IMAGE

		$this->controls['imagePosition'] = [
			'tab'         => 'content',
			'group'       => 'image',
			'label'       => esc_html__( 'Image position', 'bricks' ),
			'type'        => 'select',
			'options'     => [
				'top'    => esc_html__( 'Top', 'bricks' ),
				'right'  => esc_html__( 'Right', 'bricks' ),
				'left'   => esc_html__( 'Left', 'bricks' ),
				'bottom' => esc_html__( 'Bottom', 'bricks' ),
			],
			'placeholder' => esc_html__( 'Top', 'bricks' ),
			'inline'      => true,
		];

		$swiper_controls = self::get_swiper_controls();

		$this->controls['imageRatio']                = $swiper_controls['imageRatio'];
		$this->controls['imageRatio']['group']       = 'image';
		$this->controls['imageRatio']['clearable']   = true;
		$this->controls['imageRatio']['reset']       = true;
		$this->controls['imageRatio']['placeholder'] = esc_html__( 'Square', 'bricks' );
		unset( $this->controls['imageRatio']['default'] );

		$this->controls['imageWidth'] = [
			'tab'   => 'content',
			'group' => 'image',
			'label' => esc_html__( 'Width', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'css'   => [
				[
					'selector' => '.image',
					'property' => 'width',
				],
			],
		];

		$this->controls['imageMargin'] = [
			'tab'   => 'content',
			'group' => 'image',
			'label' => esc_html__( 'Margin', 'bricks' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'margin',
					'selector' => '.image',
				],
			],
		];

		$this->controls['imageBorder'] = [
			'tab'   => 'content',
			'group' => 'image',
			'label' => esc_html__( 'Border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.image',
				],
			],
		];

		// CONTENT

		$this->controls['contentPadding'] = [
			'tab'         => 'content',
			'group'       => 'content',
			'label'       => esc_html__( 'Padding', 'bricks' ),
			'type'        => 'spacing',
			'css'         => [
				[
					'property' => 'padding',
					'selector' => '.content',
				],
			],
			'placeholder' => [
				'top'    => 15,
				'right'  => 0,
				'bottom' => 0,
				'left'   => 0,
			],
		];

		$this->controls['contentAlign'] = [
			'tab'    => 'content',
			'group'  => 'content',
			'label'  => esc_html__( 'Text align', 'bricks' ),
			'type'   => 'text-align',
			'css'    => [
				[
					'property' => 'text-align',
					'selector' => '.content',
				],
			],
			'inline' => true,
		];

		$this->controls['memberTitleTag'] = [
			'tab'         => 'content',
			'group'       => 'member',
			'label'       => esc_html__( 'Title tag', 'bricks' ),
			'type'        => 'select',
			'options'     => [
				'h2'  => 'h2',
				'h3'  => 'h3',
				'h4'  => 'h4',
				'h5'  => 'h5',
				'h6'  => 'h6',
				'p'   => 'p',
				'div' => 'div',
			],
			'inline'      => true,
			'placeholder' => 'h4',
		];

		$this->controls['memberTitleTypography'] = [
			'tab'   => 'content',
			'group' => 'content',
			'label' => esc_html__( 'Title typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.title',
				]
			],
		];

		$this->controls['memberSubtitleTypography'] = [
			'tab'   => 'content',
			'group' => 'content',
			'label' => esc_html__( 'Subtitle typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.subtitle',
				]
			],
		];

		$this->controls['memberDescriptionTypography'] = [
			'tab'   => 'content',
			'group' => 'content',
			'label' => esc_html__( 'Description typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.description',
				]
			],
		];
	}

	public function render() {
		$settings = $this->settings;

		$members   = ! empty( $settings['items'] ) ? $settings['items'] : false;
		$title_tag = ! empty( $settings['memberTitleTag'] ) ? esc_attr( $settings['memberTitleTag'] ) : 'h4';

		if ( ! $members ) {
			return $this->render_element_placeholder(
				[
					'title' => esc_html__( 'No team members added.', 'bricks' ),
				]
			);
		}

		if ( ! empty( $settings['imagePosition'] ) ) {
			$this->set_attribute( '_root', 'class', "image-{$settings['imagePosition']}" );
		}

		echo "<ul {$this->render_attributes( '_root' )}>";

		foreach ( $members as $index => $member ) {
			echo '<li class="member">';

			if ( isset( $member['image'] ) ) {
				// Image
				$team_member_image_classes[] = 'image';
				$team_member_image_classes[] = 'css-filter';

				if ( isset( $settings['imageRatio'] ) ) {
					$team_member_image_classes[] = $settings['imageRatio'];
				}

				$this->set_attribute( "image-{$index}", 'class', $team_member_image_classes );

				if ( ! empty( $member['image']['useDynamicData'] ) ) {
					$images = $this->render_dynamic_data_tag( $member['image']['useDynamicData'], 'image' );

					$size = isset( $member['image']['size'] ) ? $member['image']['size'] : BRICKS_DEFAULT_IMAGE_SIZE;

					if ( ! empty( $images[0] ) ) {
						$url = is_numeric( $images[0] ) ? wp_get_attachment_image_url( $images[0], $size ) : $images[0];
					}
				} elseif ( isset( $member['image']['url'] ) ) {
					$url = $member['image']['url'];
				} else {
					$url = '';
				}

				$this->set_attribute( "image-{$index}", 'style', 'background-image: url(' . $url . ')' );

				echo "<div {$this->render_attributes( "image-{$index}" )}></div>";
			}

			echo '<div class="content">';

			if ( ! empty( $member['title'] ) ) {
				$this->set_attribute( "title-$index", esc_attr( $title_tag ) );
				$this->set_attribute( "title-$index", 'class', [ 'title' ] );

				echo "<{$this->render_attributes( "title-$index" )}>{$member['title']}</{$title_tag}>";
			}

			if ( ! empty( $member['subtitle'] ) ) {
				$this->set_attribute( "subtitle-$index", 'class', [ 'subtitle' ] );

				echo "<div {$this->render_attributes( "subtitle-$index" )}>{$member['subtitle']}</div>";
			}

			if ( ! empty( $member['description'] ) ) {
				$this->set_attribute( "description-$index", 'class', [ 'description' ] );

				echo "<div {$this->render_attributes( "description-$index" )}>{$member['description']}</div>";
			}

			echo '</div>';

			echo '</li>';
		}

		echo '</ul>';
	}
}
