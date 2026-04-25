<?php

use function WCML\functions\getSetting;
use function WCML\functions\isStandAlone;
use WCML\AdminNotices\WizardNotice;
use WCML\StandAlone\UI\AdminMenu;
use WCML\Utilities\AdminPages;
use WCML\Utilities\AdminUrl;
use WPML\FP\Fns;
use WPML\FP\Str;

/**
 * Class WCML_Admin_Menus
 */
class WCML_Admin_Menus {

	const SLUG = 'wpml-wcml';

	/** @var woocommerce_wpml */
	private static $woocommerce_wpml;

	/** @var SitePress|null */
	private static $sitepress;

	/** @var wpdb */
	private static $wpdb;

	/**
	 * Set up menus
	 *
	 * @param woocommerce_wpml $woocommerce_wpml WCML instance.
	 * @param SitePress|null   $sitepress        WPML Core instance.
	 * @param wpdb             $wpdb             wpdb instance.
	 */
	public static function set_up_menus( $woocommerce_wpml, $sitepress, $wpdb ) {
		self::$woocommerce_wpml = $woocommerce_wpml;
		self::$sitepress        = $sitepress;
		self::$wpdb             = $wpdb;

		if (
			\WPML\Container\make( WCML_Dependencies::class )->check() &&
			! current_user_can( 'wpml_manage_woocommerce_multilingual' ) &&
			current_user_can( 'wpml_operate_woocommerce_multilingual' ) &&
			! current_user_can( 'translate' )
		) {
			add_filter( 'wpml_menu_page', [ __CLASS__, 'wpml_menu_page' ] );
		}

		add_action( 'admin_menu', [ __CLASS__, 'register_menus' ], 80 );

		if ( AdminPages::isWcmlSettings() ) {
			add_action( 'admin_body_class', Str::concat( Fns::__, ' ' . self::SLUG . '-' . AdminPages::getTabToDisplay() ) );
		}

		if ( self::is_page_without_admin_language_switcher() ) {
			self::remove_wpml_admin_language_switcher();
		}

		if ( is_admin() && ! is_null( $sitepress ) && \WPML\Container\make( WCML_Dependencies::class )->check() && WCML_Capabilities::canManageWcml() ) {
			add_action( 'admin_head', [ __CLASS__, 'hide_multilingual_content_setup_box' ] );
			if ( ! isStandAlone() ) {
				add_action( 'admin_footer', [ __CLASS__, 'documentation_links' ] );
				add_action( 'admin_init', [ __CLASS__, 'restrict_admin_with_redirect' ] );
			}
			add_filter( 'plugin_action_links_woocommerce-multilingual/wpml-woocommerce.php', [ __CLASS__, 'add_settings_links_to_plugin_actions' ] );
		}

		add_filter( 'woocommerce_prevent_admin_access', [ __CLASS__, 'check_user_admin_access' ] );

		if ( ! isStandAlone() ) {
			add_action( 'admin_head', [ __CLASS__, 'add_menu_warning' ] );
		}
	}

	/**
	 * @return bool
	 */
	private static function is_page_without_admin_language_switcher() {
		$get_page = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : false;

		$is_page_wpml_wcml        = self::SLUG === $get_page;
		$is_shipping_zones        = 'shipping_zones' === $get_page;
		$is_attributes_page       = apply_filters( 'wcml_is_attributes_page', 'product_attributes' === $get_page );
		$is_order_create_or_edit  = \WCML\Orders\Helper::isOrderCreateAdminScreen() || \WCML\Orders\Helper::isOrderEditAdminScreen();
		$is_coupon_create_or_edit = \WCML\Coupons\Helper::isCouponCreateAdminScreen() || \WCML\Coupons\Helper::isCouponEditAdminScreen();

		return is_admin() && (
				$is_page_wpml_wcml ||
				$is_order_create_or_edit ||
				$is_coupon_create_or_edit ||
				$is_shipping_zones ||
				$is_attributes_page
			);
	}

	public static function remove_wpml_admin_language_switcher() {
		remove_action( 'wp_before_admin_bar_render', [ self::$sitepress, 'admin_language_switcher' ] );
	}

	/**
	 * @param array $menu
	 *
	 * @return array
	 */
	public static function wpml_menu_page( $menu ) {
		if ( isset( $menu['menu_slug'] ) && WPML_TM_FOLDER . '/menu/translations-queue.php' === $menu['menu_slug'] ) {
			$menu['capability'] = 'wpml_operate_woocommerce_multilingual';
		}

		return $menu;
	}

	public static function register_menus() {
		$pageTitle = $menuTitle = self::getWcmlShortLabel();

		if ( \WPML\Container\make( WCML_Dependencies::class )->check() || class_exists( 'WooCommerce' ) ) {
			add_submenu_page(
				'woocommerce',
				$pageTitle,
				$menuTitle,
				'wpml_operate_woocommerce_multilingual',
				self::SLUG,
				[ __CLASS__, 'render_menus' ]
			);
		} else {
			add_menu_page(
				$pageTitle,
				$menuTitle,
				'wpml_manage_woocommerce_multilingual',
				self::SLUG,
				[ __CLASS__, 'render_menus' ],
				WCML_PLUGIN_URL . '/res/images/icon16.png'
			);

		}
	}

	public static function render_menus() {
		global $sitepress_settings;

		if ( \WPML\Container\make( WCML_Dependencies::class )->check() ) {
			if ( isStandAlone() ) {
				$plugins_wrap = new AdminMenu( self::$sitepress, self::$woocommerce_wpml );
				$plugins_wrap->show();
			} elseif ( getSetting( 'set_up_wizard_run' ) ) {
				$menus_wrap = new WCML_Menus_Wrap( self::$woocommerce_wpml, self::$sitepress, $sitepress_settings );
				$menus_wrap->show();
			} else {
				$wizard_wrap = new WizardNotice( self::$woocommerce_wpml );
				$wizard_wrap->show();
			}
		}
	}

	public static function documentation_links() {
		global $post, $pagenow;

		if ( $post && ! is_object( $post ) ) {
			$post = get_post( $post );
		}

		if ( ! $post ) {
			return;
		}

		$get_post_type = get_post_type( $post->ID );

		if ( 'product' === $get_post_type && 'edit.php' === $pagenow ) {
			$quick_edit_notice  = '<p>';
			$quick_edit_notice .= esc_html__( 'Quick Edit isn\'t available for product translations:', 'woocommerce-multilingual' );
			$quick_edit_notice .= '</p>';

			$quick_edit_notice .= '<ul>';
			$quick_edit_notice .= '<li>';
			$quick_edit_notice .= sprintf(
			/* translators: %1$s and %2$s are opening and closing HTML link tags */
				esc_html__( '- To edit this product\'s translation, use the %1$sTranslation Dashboard%2$s.', 'woocommerce-multilingual' ),
				'<a href="' . esc_url( AdminUrl::getWPMLTMDashboardProducts() ) . '">',
				'</a>'
			);
			$quick_edit_notice .= '</li>';
			$quick_edit_notice .= '<li>';
			$quick_edit_notice .= esc_html__( '- To edit the original product, switch back to the default language.', 'woocommerce-multilingual' );
			$quick_edit_notice .= '</li>';
			$quick_edit_notice .= '</ul>';

			?>
			<script type="text/javascript">
				jQuery( '.subsubsub' ).append( '<div id="quick_edit_notice" style="display:none;"><?php echo wp_filter_post_kses( $quick_edit_notice ); ?></div>' );

				//lock feature for translations
				jQuery( document ).on( 'click', '.featured a', function() {
					if ( jQuery( this ).closest( 'tr' ).find( '.quick_hide' ).size() > 0 ) {
						return false;
					}

				} );
			</script>
			<?php
			// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	public static function hide_multilingual_content_setup_box() {
		remove_meta_box( 'icl_div_config', convert_to_screen( 'shop_order' ), 'normal' );
		remove_meta_box( 'icl_div_config', convert_to_screen( 'shop_coupon' ), 'normal' );
	}

	public static function restrict_admin_with_redirect() {
		global $pagenow;

		if (
			self::$woocommerce_wpml->is_wpml_prior_4_2() &&
			self::$woocommerce_wpml->settings['trnsl_interface'] ) {

			if (
				'post.php' === $pagenow &&
				! wp_doing_ajax() &&
				self::is_post_product_translation_screen() &&
				self::is_post_action_needs_redirect()
			) {
				$prid = (int) $_GET['post'];
				if ( 'auto-draft' !== get_post_status( $prid ) ) {
					wcml_safe_redirect( AdminUrl::getWPMLTMDashboardProducts().'&prid=' . $prid );
				}
			} elseif ( self::is_admin_duplicate_page_action( $pagenow ) && self::is_post_product_translation_screen() ) {
				wcml_safe_redirect( AdminUrl::getWPMLTMDashboardProducts() );
			}
		} elseif ( 'post.php' === $pagenow && self::is_post_product_translation_screen() ) {
			add_action( 'admin_notices', [ __CLASS__, 'inf_editing_product_in_non_default_lang' ] );
		}
	}

	/**
	 * @return bool
	 */
	private static function is_post_product_translation_screen() {
		return isset( $_GET['post'] ) && 'product' === get_post_type( $_GET['post'] ) && ! self::$woocommerce_wpml->products->is_original_product( $_GET['post'] );
	}

	private static function is_post_action_needs_redirect(): bool {
		return ! isset( $_GET['action'] ) ||
			( isset( $_GET['action'] ) &&
				! in_array (
					$_GET['action'],
					[ 'trash', 'delete', 'untrash' ],
					true
				)
			);
	}

	/**
	 * @param string $pagenow
	 *
	 * @return bool
	 */
	private static function is_admin_duplicate_page_action( $pagenow ) {
		return 'admin.php' === $pagenow && isset( $_GET['action'] ) && 'duplicate_product' === $_GET['action'];
	}

	/**
	 * @param array $actions
	 *
	 * @return array
	 */
	public static function add_settings_links_to_plugin_actions( $actions ) {
		// $getLink :: (string, string) -> string
		$getLink = function( $label, $url ) {
			return '<a href="' . esc_url( $url ) . '">' . $label . '</a>';
		};

		return array_merge(
			[
				'settings-ml' => $getLink(
					esc_html__( 'Multilingual Settings', 'woocommerce-multilingual' ),
					AdminUrl::getMultilingualTab()
				),
				'settings-mc' => $getLink(
					esc_html__( 'Multicurrency Settings', 'woocommerce-multilingual' ),
					AdminUrl::getMultiCurrencyTab()
				),
			],
			$actions
		);
	}

	public static function inf_editing_product_in_non_default_lang() {
		if ( ! self::$woocommerce_wpml->settings['dismiss_tm_warning'] ) {
			$url = $_SERVER['REQUEST_URI'];

			$message = '<div class="message error otgs-is-dismissible"><p>';

			$message .= sprintf(
				/* translators: 1: open <a> tag, 2: close <a> tag */
				esc_html__( 'It’s recommended that you translate products from the %1$sTranslation Dashboard%2$s.', 'woocommerce-multilingual' ),
				'<a href="' . esc_url( AdminUrl::getWPMLTMDashboardProducts() ) . '">',
				'</a>'
			);
			$message .= '</p><a class="notice-dismiss" href="' . esc_url( add_query_arg( 'wcml_action', 'dismiss_tm_warning', $url ) ) . '"><span class="screen-reader-text">' . __( 'Dismiss', 'woocommerce-multilingual' ) . '</span></a>';
			$message .= '</div>';

			echo wp_kses_post( $message );
		}
	}

	/**
	 * @param bool $prevent_access
	 *
	 * @return bool
	 */
	public static function check_user_admin_access( $prevent_access ) {
		if ( \WPML\Container\make( WCML_Dependencies::class )->check() ) {
			$user_lang_pairs = get_user_meta( get_current_user_id(), self::$wpdb->prefix . 'language_pairs', true );
			if ( current_user_can( 'wpml_manage_woocommerce_multilingual' ) || ! empty( $user_lang_pairs ) ) {
				return false;
			}
		}

		return $prevent_access;
	}

	public static function add_menu_warning() {
		global $submenu, $menu;

		if (
			class_exists( 'WooCommerce' ) &&
			(
				empty( self::$woocommerce_wpml->settings['set_up_wizard_run'] ) ||
				(
					empty( self::$woocommerce_wpml->settings['set_up_wizard_run'] ) &&
					self::$woocommerce_wpml->settings['set_up_wizard_splash']
				)
			)
		) {
			if ( isset( $submenu['woocommerce'] ) ) {
				$wcml_short_label = self::getWcmlShortLabel();
				foreach ( $submenu['woocommerce'] as $key => $menu_item ) {
					if ( $wcml_short_label === $menu_item[0] ) {
						// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
						$submenu['woocommerce'][ $key ][0] .= '<span class="wcml-menu-warn"><i class="otgs-ico-warning"></i></span>';
						break;
					}
				}
			}

			$woocommerce_label = __( 'WooCommerce', 'woocommerce' );
			foreach ( $menu as $key => $menu_item ) {
				if ( $woocommerce_label === $menu_item[0] ) {
					// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
					$menu[ $key ][0] .= '<span class="wcml-menu-warn"><i class="otgs-ico-warning"></i></span>';
					break;
				}
			}
		}
	}

	/**
	 * @return string
	 */
	public static function getWcmlLabel() {
		return __( 'WPML Multilingual & Multicurrency for WooCommerce', 'woocommerce-multilingual' );
	}

	/**
	 * @return string
	 */
	public static function getWcmlShortLabel() {
		return __( 'WCML', 'woocommerce-multilingual' );
	}
}
