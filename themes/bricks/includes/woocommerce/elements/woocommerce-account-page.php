<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Woocommerce_Account_Page extends Woo_Element {
	public $category = 'woocommerce';
	public $name     = 'woocommerce-account-page';
	public $icon     = 'ti-user';

	public function get_label() {
		return esc_html__( 'Account', 'bricks' ) . ' - ' . esc_html__( 'Page', 'bricks' );
	}

	public function set_control_groups() {
		$this->control_groups['navigation'] = [
			'title' => esc_html__( 'Navigation', 'bricks' ),
		];

		$this->control_groups['content'] = [
			'title' => esc_html__( 'Content', 'bricks' ),
		];
	}

	public function set_controls() {
		// WRAPPER
		$this->controls['direction'] = [
			'label'    => esc_html__( 'Direction', 'bricks' ),
			'type'     => 'direction',
			'inline'   => true,
			'rerender' => false,
			'css'      => [
				[
					'selector' => '.woocommerce:not(#brx-content)',
					'property' => 'flex-direction',
				],
			],
		];

		$this->controls['gap'] = [
			'label' => esc_html__( 'Gap', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'css'   => [
				[
					'selector' => '.woocommerce:not(#brx-content)',
					'property' => 'gap',
				],
			],
		];

		// NAVIGATION
		$this->controls['navDirection'] = [
			'group'    => 'navigation',
			'label'    => esc_html__( 'Direction', 'bricks' ),
			'type'     => 'direction',
			'inline'   => true,
			'rerender' => false,
			'css'      => [
				[
					'selector' => '.woocommerce-MyAccount-navigation ul',
					'property' => 'flex-direction',
				],
			],
		];

		$this->controls['navAlignItems'] = [
			'group'   => 'navigation',
			'label'   => esc_html__( 'Align items', 'bricks' ),
			'type'    => 'align-items',
			'inline'  => true,
			'exclude' => [ 'stretch' ],
			'css'     => [
				[
					'selector' => '.woocommerce-MyAccount-navigation ul',
					'property' => 'align-items',
				],
			],
		];

		$this->controls['navJustifyContent'] = [
			'group' => 'navigation',
			'label' => esc_html__( 'Justify content', 'bricks' ),
			'type'  => 'justify-content',
			'css'   => [
				[
					'selector' => '.woocommerce-MyAccount-navigation ul',
					'property' => 'justify-content',
				],
			],
		];

		$this->controls['navGap'] = [
			'group' => 'navigation',
			'label' => esc_html__( 'Gap', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'css'   => [
				[
					'selector' => '.woocommerce-MyAccount-navigation ul',
					'property' => 'gap',
				],
			],
		];

		$this->controls['navBackground'] = [
			'group' => 'navigation',
			'label' => esc_html__( 'Background', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'selector' => '.woocommerce-MyAccount-navigation',
					'property' => 'background-color',
				],
			],
		];

		$this->controls['navBorder'] = [
			'group' => 'navigation',
			'label' => esc_html__( 'Border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'selector' => '.woocommerce-MyAccount-navigation',
					'property' => 'border',
				],
			],
		];

		$this->controls['navBoxShadow'] = [
			'group' => 'navigation',
			'label' => esc_html__( 'Box shadow', 'bricks' ),
			'type'  => 'box-shadow',
			'css'   => [
				[
					'selector' => '.woocommerce-MyAccount-navigation',
					'property' => 'box-shadow',
				],
			],
		];

		// NAV ITEM

		$this->controls['navItemSep'] = [
			'group' => 'navigation',
			'label' => esc_html__( 'Item', 'bricks' ),
			'type'  => 'separator',
		];

		$this->controls['navItemPadding'] = [
			'group' => 'navigation',
			'label' => esc_html__( 'Padding', 'bricks' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'selector' => '.woocommerce-MyAccount-navigation a',
					'property' => 'padding',
				],
			],
		];

		$this->controls['navItemBackground'] = [
			'group' => 'navigation',
			'label' => esc_html__( 'Background', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'selector' => '.woocommerce-MyAccount-navigation a',
					'property' => 'background-color',
				],
			],
		];

		$this->controls['navItemBorder'] = [
			'group' => 'navigation',
			'label' => esc_html__( 'Border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'selector' => '.woocommerce-MyAccount-navigation a',
					'property' => 'border',
				],
			],
		];

		$this->controls['navItemBoxShadow'] = [
			'group' => 'navigation',
			'label' => esc_html__( 'Box shadow', 'bricks' ),
			'type'  => 'box-shadow',
			'css'   => [
				[
					'selector' => '.woocommerce-MyAccount-navigation a',
					'property' => 'box-shadow',
				],
			],
		];

		$this->controls['navItemTypogaphy'] = [
			'group' => 'navigation',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'selector' => '.woocommerce-MyAccount-navigation a',
					'property' => 'font',
				],
			],
		];

		// ACTIVE

		$this->controls['navItemActiveSep'] = [
			'group' => 'navigation',
			'label' => esc_html__( 'Active', 'bricks' ),
			'type'  => 'separator',
		];

		$this->controls['navItemBackgroundActive'] = [
			'group' => 'navigation',
			'label' => esc_html__( 'Background', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'selector' => '.woocommerce-MyAccount-navigation .is-active a',
					'property' => 'background-color',
				],
			],
		];

		$this->controls['navItemBorderActive'] = [
			'group' => 'navigation',
			'label' => esc_html__( 'Border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'selector' => '.woocommerce-MyAccount-navigation .is-active a',
					'property' => 'border',
				],
			],
		];

		$this->controls['navItemBoxShadowActive'] = [
			'group' => 'navigation',
			'label' => esc_html__( 'Box shadow', 'bricks' ),
			'type'  => 'box-shadow',
			'css'   => [
				[
					'selector' => '.woocommerce-MyAccount-navigation .is-active a',
					'property' => 'box-shadow',
				],
			],
		];

		$this->controls['navItemTypogaphyActive'] = [
			'group' => 'navigation',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'selector' => '.woocommerce-MyAccount-navigation .is-active a',
					'property' => 'font',
				],
			],
		];

		// CONTENT

		$this->controls['contentPadding'] = [
			'group' => 'content',
			'label' => esc_html__( 'Padding', 'bricks' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'selector' => '.woocommerce-MyAccount-content',
					'property' => 'padding',
				],
			],
		];

		$this->controls['contentBackground'] = [
			'group' => 'content',
			'label' => esc_html__( 'Background', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'selector' => '.woocommerce-MyAccount-content',
					'property' => 'background-color',
				],
			],
		];

		$this->controls['contentBorder'] = [
			'group' => 'content',
			'label' => esc_html__( 'Border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'selector' => '.woocommerce-MyAccount-content',
					'property' => 'border',
				],
			],
		];

		$this->controls['contentBoxShadow'] = [
			'group' => 'content',
			'label' => esc_html__( 'Box shadow', 'bricks' ),
			'type'  => 'box-shadow',
			'css'   => [
				[
					'selector' => '.woocommerce-MyAccount-content',
					'property' => 'box-shadow',
				],
			],
		];

		$this->controls['contentTypogaphy'] = [
			'group' => 'content',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'selector' => '.woocommerce-MyAccount-content',
					'property' => 'font',
				],
			],
		];
	}

	public function render() {
		global $wp;

		// STEP: Lost/reset password form (Bricks template)
		if ( isset( $wp->query_vars['lost-password'] ) ) {
			// Reset password (same /lost-password/ URL, but with a reset key & login params)
			if (
				isset( $_GET['show-reset-form'] ) ||
				( isset( $_GET['key'] ) && isset( $_GET['login'] ) )
			) {
				$bricks_template = WooCommerce::get_template_data_by_type( 'wc_account_reset_password' );
				if ( $bricks_template ) {
					echo "<div {$this->render_attributes( '_root' )}>{$bricks_template}</div>";
					return;
				}

				// Fallback: Get 'wc_account_form_lost_password' Woo template
				else {
					wc_get_template( 'myaccount/form-reset-password.php', [ 'args' => Woocommerce::get_reset_password_args() ] );
					return;
				}
			}

			// Lost password confirmation
			if (
				isset( $_GET['reset-link-sent'] ) ||
				( isset( $_GET['wc-reset-password'] ) && $_GET['wc-reset-password'] === 'reset-link-sent' )
			) {
				$bricks_template = WooCommerce::get_template_data_by_type( 'wc_account_form_lost_password_confirmation' );
				if ( $bricks_template ) {
					echo "<div {$this->render_attributes( '_root' )}>{$bricks_template}</div>";
					return;
				}
			}

			// Lost password form
			$bricks_template = WooCommerce::get_template_data_by_type( 'wc_account_form_lost_password' );
			if ( $bricks_template ) {
				echo "<div {$this->render_attributes( '_root' )}>{$bricks_template}</div>";
				return;
			}

			// Fallback: Get 'wc_account_form_lost_password' Woo template
			else {
				wc_get_template( 'myaccount/form-lost-password.php' );
				return;
			}
		}

		// STEP: Logged-in user: Show my account page
		if ( is_user_logged_in() ) {
			echo "<div {$this->render_attributes( '_root' )}>";

			// Builder & template preview: Add 'wc_account_dashboard' template CSS
			if ( bricks_is_builder() ) {
				$accont_dashboard_template_ids = Templates::get_templates_by_type( 'wc_account_dashboard' );
				$accont_dashboard_template_id  = $accont_dashboard_template_ids[0] ?? null;

				if ( $accont_dashboard_template_id ) {
					$elements = get_post_meta( $accont_dashboard_template_id, BRICKS_DB_PAGE_CONTENT, true );
					$css      = Templates::generate_inline_css( $accont_dashboard_template_id, $elements );
					$css     .= Assets::$inline_css_dynamic_data;

					if ( $css ) {
						echo "<style type=\"text/css\" data-template-id=\"{$accont_dashboard_template_id}\">{$css}</style>";
					}
				}
			}

			echo do_shortcode( '[woocommerce_my_account]' );

			echo '</div>';
			return;
		}

		// STEP: Non-logged-in user: Show login/register form

		// STEP: Login/register form (Bricks template)
		$bricks_template = WooCommerce::get_template_data_by_type( 'wc_account_form_login' );
		if ( $bricks_template ) {
			echo "<div {$this->render_attributes( '_root' )}>{$bricks_template}</div>";
			return;
		}

		// STEP: Fallback: Get 'wc_account_form_login' Woo template
		wc_get_template( 'myaccount/form-login.php' );
		return;
	}
}
