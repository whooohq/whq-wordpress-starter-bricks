<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Settings_Page extends Settings_Base {
	public function set_control_groups() {

		$this->control_groups['general'] = [
			'title'      => esc_html__( 'General', 'bricks' ),
			'fullAccess' => true,
		];

		$this->control_groups['scroll-snap'] = [
			'title'      => esc_html__( 'Scroll snap', 'bricks' ),
			'fullAccess' => true,
		];

		if ( get_post_type( get_the_ID() ) !== BRICKS_DB_TEMPLATE_SLUG || Templates::get_template_type( get_the_ID() ) === 'content' ) {
			$this->control_groups['one-page'] = [
				'title'      => esc_html__( 'One Page Navigation', 'bricks' ),
				'fullAccess' => true,
			];
		}

		if ( empty( Database::$global_settings['disableSeo'] ) ) {
			$this->control_groups['seo'] = [
				'title'      => esc_html__( 'SEO', 'bricks' ),
				'fullAccess' => true,
			];
		}

		if ( empty( Database::$global_settings['disableOpenGraph'] ) ) {
			$this->control_groups['social-media'] = [
				'title'      => esc_html__( 'Social media', 'bricks' ),
				'fullAccess' => true,
			];
		}

		$this->control_groups['custom-code'] = [
			'title'      => esc_html__( 'Custom code', 'bricks' ),
			'fullAccess' => true,
		];
	}

	public function set_controls() {
		$template_type = get_post_meta( get_the_ID(), BRICKS_DB_TEMPLATE_TYPE, true );

		// GENERAL

		$this->controls['bodyClasses'] = [
			'group'       => 'general',
			'type'        => 'text',
			'inline'      => true,
			'label'       => esc_html__( 'CSS classes', 'bricks' ) . ' (body)',
			'description' => esc_html__( 'Space-separated list of CSS classes to add to the <body> tag of this page.', 'bricks' ),
		];

		if ( $template_type !== 'header' && $template_type !== 'footer' ) {
			$this->controls['headerDisabled'] = [
				'group' => 'general',
				'type'  => 'checkbox',
				'label' => esc_html__( 'Disable header', 'bricks' ),
			];

			$this->controls['footerDisabled'] = [
				'group' => 'general',
				'type'  => 'checkbox',
				'label' => esc_html__( 'Disable footer', 'bricks' ),
			];

			$this->controls['disableLazyLoad'] = [
				'group' => 'general',
				'type'  => 'checkbox',
				'label' => esc_html__( 'Disable lazy load', 'bricks' ),
			];
		}

		// Add Theme Styles "General" controls to page settings
		$style_controls = Theme_Styles::$controls;

		if ( count( $style_controls ) === 0 ) {
			Theme_Styles::set_controls();

			$style_controls = Theme_Styles::$controls;
		}

		if ( isset( $style_controls['general'] ) ) {
			$general_controls = $style_controls['general'];

			foreach ( $general_controls as $control_key => $control ) {
				$this->controls[ $control_key ] = $control;

				// Content margin
				if ( $control_key === 'siteBackground' && isset( $style_controls['content']['contentMargin'] ) ) {
					$this->controls['contentMargin']          = $style_controls['content']['contentMargin'];
					$this->controls['contentMargin']['group'] = 'general';
				}
			}
		}

		/**
		 * Scroll snap
		 *
		 * Also used in elements/base.php
		 *
		 * https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_Scroll_Snap
		 *
		 * @since 1.9.3
		 */
		$this->controls['scrollSnapSep'] = [
			'group' => 'scroll-snap',
			'label' => esc_html__( 'Scroll snap', 'bricks' ),
			'desc'  => sprintf(
				// translators: %s: Learn more link, %s: scroll snap
				esc_html__( '%1$s about %2$s in the Bricks Academy.', 'bricks' ),
				Helpers::article_link( 'scroll-snap', esc_html__( 'Learn more', 'bricks' ) ),
				'scroll snap'
			) . ' ' . esc_html__( 'View on frontend', 'bricks' ) . '.',
			'type'  => 'separator',
		];

		$this->controls['scrollSnapType'] = [
			'group'       => 'scroll-snap',
			'label'       => esc_html__( 'Type', 'bricks' ),
			'placeholder' => esc_html__( 'Select', 'bricks' ),
			'tooltip'     => 'scroll-snap-type',
			'type'        => 'select',
			'inline'      => true,
			'options'     => [
				'unset'       => 'Unset',
				'y mandatory' => 'Mandatory (' . esc_html__( 'y-axis', 'bricks' ) . ')',
				'y proximity' => 'Proximity (' . esc_html__( 'y-axis', 'bricks' ) . ')',
			],
			'css'         => [
				[
					'selector' => 'html',
					'property' => 'scroll-snap-type',
				],
				[
					'selector' => '.brxe-section',
					'property' => 'scroll-snap-align',
					'value'    => 'start',
				],
			],
		];

		$this->controls['scrollSnapSelector'] = [
			'group'       => 'scroll-snap',
			'label'       => esc_html__( 'Selector', 'bricks' ),
			'type'        => 'text',
			'inline'      => true,
			'dd'          => false,
			'placeholder' => '.brxe-section',
		];

		$this->controls['scrollSnapAlign'] = [
			'group'       => 'scroll-snap',
			'label'       => esc_html__( 'Align', 'bricks' ),
			'placeholder' => 'start',
			'tooltip'     => 'scroll-snap-align',
			'type'        => 'select',
			'inline'      => true,
			'options'     => [
				'start'  => 'start',
				'center' => 'center',
				'end'    => 'end',
			],
			'css'         => [
				[
					'selector' => '.brxe-section',
					'property' => 'scroll-snap-align',
				],
			],
		];

		$this->controls['scrollMargin'] = [
			'group'   => 'scroll-snap',
			'label'   => esc_html__( 'Margin', 'bricks' ),
			'tooltip' => 'scroll-snap-margin',
			'type'    => 'number',
			'units'   => true,
			'large'   => true,
			'css'     => [
				[
					'selector' => '.brxe-section',
					'property' => 'scroll-margin',
				],
			],
		];

		$this->controls['scrollPadding'] = [
			'group'   => 'scroll-snap',
			'label'   => esc_html__( 'Padding', 'bricks' ),
			'tooltip' => 'scroll-snap-padding',
			'type'    => 'number',
			'units'   => true,
			'large'   => true,
			'css'     => [
				[
					'selector' => 'html',
					'property' => 'scroll-padding',
				],
			],
		];

		$this->controls['scrollSnapStop'] = [
			'group'       => 'scroll-snap',
			'label'       => esc_html__( 'Stop', 'bricks' ),
			'tooltip'     => 'scroll-snap-stop',
			'placeholder' => 'normal',
			'type'        => 'select',
			'inline'      => true,
			'small'       => true,
			'options'     => [
				'normal' => 'normal',
				'always' => 'always',
			],
			'css'         => [
				[
					'selector' => '.brxe-section',
					'property' => 'scroll-snap-stop',
				],
			],
		];

		// SEO

		$this->controls['postName'] = [
			'group'          => 'seo',
			'label'          => esc_html__( 'Permalink', 'bricks' ),
			'type'           => 'text',
			'description'    => esc_html__( 'Displayed in URL. All lowercase. Use dashes instead of spaces.', 'bricks' ),
			'placeholder'    => get_post_field( 'post_name', get_post() ),
			'hasDynamicData' => false,
		];

		$this->controls['postTitle'] = [
			'group'          => 'seo',
			'label'          => esc_html__( 'Title', 'bricks' ) . ' (= ' . esc_html__( 'Post title', 'bricks' ) . ')',
			'type'           => 'text',
			'description'    => esc_html__( 'Displayed in search results, social networks and web browser. Recommended: Max. 60 characters.', 'bricks' ),
			'placeholder'    => bricks_is_builder() ? get_post_field( 'post_title', get_the_ID(), 'raw' ) : '',
			'hasDynamicData' => false,
		];

		$this->controls['apply'] = [
			'group'  => 'seo',
			'type'   => 'apply',
			'reload' => false,
			'label'  => esc_html__( 'Save new title/permalink', 'bricks' ),
		];

		$this->controls['documentTitle'] = [
			'group'       => 'seo',
			'label'       => esc_html__( 'Document title', 'bricks' ),
			'type'        => 'text',
			'description' => esc_html__( 'For frontend SEO purpose only. Not overwriting Post title. Recommended: Max. 60 characters.', 'bricks' ),
		];

		$this->controls['metaDescription'] = [
			'group'       => 'seo',
			'label'       => esc_html__( 'Meta description', 'bricks' ),
			'type'        => 'textarea',
			'description' => esc_html__( 'Descriptive text of this page. Displayed in search engine results. Recommended: 50 - 300 characters.', 'bricks' ),
		];

		$this->controls['metaKeywords'] = [
			'group'       => 'seo',
			'label'       => esc_html__( 'Meta keywords', 'bricks' ),
			'type'        => 'text',
			'description' => esc_html__( 'Separate keywords by comma. Helps search engine to determine topic of a page.', 'bricks' ),
		];

		$this->controls['metaRobots'] = [
			'group'       => 'seo',
			'label'       => esc_html__( 'Meta robots', 'bricks' ),
			'type'        => 'select',
			'options'     => [
				'noindex'      => 'noindex',
				'nofollow'     => 'nofollow',
				'none'         => 'none',
				'noarchive'    => 'noarchive',
				'nocache'      => 'nocache',
				'nosnippet'    => 'nosnippet',
				'notranslate'  => 'notranslate',
				'noimageindex' => 'noimageindex',
			],
			'multiple'    => true,
			'description' => sprintf( '<a href="https://moz.com/learn/seo/robots-meta-directives" target="_blank">%s</a>', esc_html__( 'More about meta robots directives.', 'bricks' ) ),
		];

		/**
		 * Social Media
		 */

		$this->controls['sharingInfo'] = [
			'group'   => 'social-media',
			'type'    => 'info',
			'content' => esc_html__( 'Customize details for sharing this URL on social media.', 'bricks' ),
		];

		$this->controls['sharingTitle'] = [
			'group'       => 'social-media',
			'label'       => esc_html__( 'Title', 'bricks' ),
			'type'        => 'text',
			'placeholder' => get_the_title(),
			'description' => esc_html__( 'Recommended length: 95 characters or less. Default: Post/page title.', 'bricks' ),
		];

		$this->controls['sharingDescription'] = [
			'group'       => 'social-media',
			'label'       => esc_html__( 'Description', 'bricks' ),
			'type'        => 'text',
			'placeholder' => get_the_excerpt(),
			'description' => esc_html__( 'Recommended length: 55 characters. Default: Post/page excerpt.', 'bricks' ),
		];

		$this->controls['sharingImage'] = [
			'group'       => 'social-media',
			'label'       => esc_html__( 'Image', 'bricks' ),
			'type'        => 'image',
			'description' => esc_html__( 'Recommended size: Large. Default: Featured image.', 'bricks' ),
		];

		/**
		 * One Page Navigation
		 */

		$this->controls['onePageNavigation'] = [
			'group' => 'one-page',
			'type'  => 'checkbox',
			'label' => esc_html__( 'Show navigation', 'bricks' ),
		];

		$this->controls['onePageNavigationItemSpacing'] = [
			'group'       => 'one-page',
			'type'        => 'number',
			'units'       => true,
			'label'       => esc_html__( 'Spacing', 'bricks' ),
			'css'         => [
				[
					'property' => 'gap',
					'selector' => '#bricks-one-page-navigation li',
				],
			],
			'placeholder' => 20,
		];

		$this->controls['onePageNavigationItemHeight'] = [
			'group'       => 'one-page',
			'type'        => 'number',
			'units'       => true,
			'label'       => esc_html__( 'Height', 'bricks' ),
			'css'         => [
				[
					'property' => 'height',
					'selector' => '#bricks-one-page-navigation a',
				],
			],
			'placeholder' => 8,
		];

		$this->controls['onePageNavigationItemWidth'] = [
			'group'       => 'one-page',
			'type'        => 'number',
			'units'       => true,
			'label'       => esc_html__( 'Width', 'bricks' ),
			'css'         => [
				[
					'property' => 'width',
					'selector' => '#bricks-one-page-navigation a',
				],
			],
			'placeholder' => 8,
		];

		$this->controls['onePageNavigationItemColor'] = [
			'group' => 'one-page',
			'type'  => 'color',
			'label' => esc_html__( 'Color', 'bricks' ),
			'css'   => [
				[
					'property' => 'color',
					'selector' => '#bricks-one-page-navigation',
				],
			],
		];

		$this->controls['onePageNavigationItemBorder'] = [
			'group' => 'one-page',
			'type'  => 'border',
			'label' => esc_html__( 'Border', 'bricks' ),
			'css'   => [
				[
					'property' => 'border',
					'selector' => '#bricks-one-page-navigation a',
				],
			],
		];

		$this->controls['onePageNavigationItemBoxShadow'] = [
			'group'   => 'one-page',
			'type'    => 'box-shadow',
			'label'   => esc_html__( 'Box shadow', 'bricks' ),
			'css'     => [
				[
					'property' => 'box-shadow',
					'selector' => '#bricks-one-page-navigation a',
				],
			],
			'default' => [
				'top'    => 10,
				'right'  => 10,
				'bottom' => 10,
				'left'   => 10,
			],
		];

		// Active

		$this->controls['onePageNavigationActiveSeparator'] = [
			'group' => 'one-page',
			'label' => esc_html__( 'Active', 'bricks' ),
			'type'  => 'separator',
		];

		$this->controls['onePageNavigationItemHeightActive'] = [
			'group'       => 'one-page',
			'type'        => 'number',
			'units'       => true,
			'label'       => esc_html__( 'Height', 'bricks' ),
			'css'         => [
				[
					'property' => 'height',
					'selector' => '#bricks-one-page-navigation .active',
				],
			],
			'placeholder' => 12,
		];

		$this->controls['onePageNavigationItemWidthActive'] = [
			'group'       => 'one-page',
			'type'        => 'number',
			'units'       => true,
			'label'       => esc_html__( 'Width', 'bricks' ),
			'css'         => [
				[
					'property' => 'width',
					'selector' => '#bricks-one-page-navigation .active',
				],
			],
			'placeholder' => 12,
		];

		$this->controls['onePageNavigationItemColorActive'] = [
			'group' => 'one-page',
			'type'  => 'color',
			'label' => esc_html__( 'Color', 'bricks' ),
			'css'   => [
				[
					'property' => 'color',
					'selector' => '#bricks-one-page-navigation .active',
				],
			],
		];

		$this->controls['onePageNavigationItemBorderActive'] = [
			'group' => 'one-page',
			'type'  => 'border',
			'label' => esc_html__( 'Border', 'bricks' ),
			'css'   => [
				[
					'property' => 'border',
					'selector' => '#bricks-one-page-navigation .active',
				],
			],
		];

		$this->controls['onePageNavigationItemBoxShadowActive'] = [
			'group'   => 'one-page',
			'type'    => 'box-shadow',
			'label'   => esc_html__( 'Box shadow', 'bricks' ),
			'css'     => [
				[
					'property' => 'box-shadow',
					'selector' => '#bricks-one-page-navigation .active',
				],
			],
			'default' => [
				'top'    => 10,
				'right'  => 10,
				'bottom' => 10,
				'left'   => 10,
			],
		];

		/**
		 * Custom Code
		 */

		$this->controls['customCss'] = [
			'group'       => 'custom-code',
			'type'        => 'code',
			'mode'        => 'css',
			'label'       => esc_html__( 'Custom CSS', 'bricks' ),
			// translators: %s: <head>
			'description' => sprintf( esc_html__( 'Adds inline CSS to %s tag.', 'bricks' ), htmlspecialchars( '<head>' ) ),
		];

		$this->controls['customScriptsHeader'] = [
			'group'       => 'custom-code',
			'type'        => 'code',
			'label'       => esc_html__( 'Header scripts', 'bricks' ),
			// translators: %s: </head>
			'description' => sprintf( esc_html__( 'Adds scripts right before closing %s tag.', 'bricks' ), htmlspecialchars( '</head>' ) ),
		];

		$this->controls['customScriptsBodyHeader'] = [
			'group'       => 'custom-code',
			'type'        => 'code',
			'label'       => esc_html__( 'Body (header) scripts', 'bricks' ),
			// translators: %s: <body>
			'description' => sprintf( esc_html__( 'Adds scripts right after opening %s tag.', 'bricks' ), htmlspecialchars( '<body>' ) ),
		];

		$this->controls['customScriptsBodyFooter'] = [
			'group'       => 'custom-code',
			'type'        => 'code',
			'label'       => esc_html__( 'Body (footer) scripts', 'bricks' ),
			// translators: %s: </body>
			'description' => sprintf( esc_html__( 'Adds scripts right before closing %s tag.', 'bricks' ), htmlspecialchars( '</body>' ) ),
		];
	}
}
