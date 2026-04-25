<?php

use WCML\COT\Helper as COTHelper;

class WCML_Multi_Currency_Reports {

	const TOP_SELLER_QUERY_USE_SELECT_ORDERS_FROM = '8.8.0';

	/** @var woocommerce_wpml */
	private $woocommerce_wpml;
	/** @var wpdb */
	private $wpdb;

	/** @var string $reports_currency */
	protected $reports_currency;

	/**
	 * @param woocommerce_wpml $woocommerce_wpml
	 * @param wpdb $wpdb
	 */
	public function __construct( woocommerce_wpml $woocommerce_wpml, wpdb $wpdb ) {
		$this->woocommerce_wpml = $woocommerce_wpml;
		$this->wpdb             = $wpdb;
	}

	public function add_hooks() {

		add_action( 'init', [ $this, 'reports_init' ] );

		if ( is_admin() ) {

			add_action( 'wp_ajax_wcml_reports_set_currency', [ $this, 'set_reports_currency' ] );

			add_action( 'wc_reports_tabs', [ $this, 'reports_currency_selector' ] );

			if ( current_user_can( 'view_woocommerce_reports' ) ||
			     current_user_can( 'manage_woocommerce' ) ||
			     current_user_can( 'publish_shop_orders' )
			) {
				add_filter( 'woocommerce_dashboard_status_widget_top_seller_query', [
					$this,
					'filterDashboardstatusWidgetTopSellerQuery'
				] );
			}

			add_action( 'current_screen', [ $this, 'admin_screen_loaded' ], 10, 1 );
		}
	}

	public function admin_screen_loaded( $screen ) {

		if ( $screen->id === 'dashboard' ) {
			// Note that this filter only runs when the WooCommerce Admin is disabled.
			// See https://developer.woocommerce.com/2021/05/18/request-for-comments-removing-the-filter-to-turn-off-woocommerce-admin/
			add_filter( 'woocommerce_reports_get_order_report_query', [
				$this,
				'filterOrdersAsPostsByCurrencyPostmeta'
			] );
		}

	}

	public function reports_init() {

		$isReportsPage = isset( $_GET['page'] ) && 'wc-reports' === $_GET['page'];

		if( $isReportsPage || \WCML\Rest\Functions::isRestApiRequest() ){
			add_filter( 'woocommerce_reports_get_order_report_query', [ $this, 'admin_reports_query_filter' ] );
		}

		if ( $isReportsPage ) { //wc-reports - 2.1.x, woocommerce_reports 2.0.x

			$wcml_reports_set_currency_nonce  = esc_js( wp_create_nonce( 'reports_set_currency' ) );
			$wcml_reports_set_currency_script = <<<JS
                jQuery('#dropdown_shop_report_currency').on('change', function(){
                    jQuery.ajax({
                        url: ajaxurl,
                        type: 'post',
                        data: {
                            action: 'wcml_reports_set_currency',
                            currency: jQuery('#dropdown_shop_report_currency').val(),
                            wcml_nonce: '$wcml_reports_set_currency_nonce'
                            },
                        success: function( response ){
                            if(typeof response.error !== 'undefined'){
                                alert(response.error);
                            }else{
                               window.location = window.location.href;
                            }
                        }
                    })
                });
JS;

			$handle = 'wcml_reports_set_currency_dropdown';
			wp_register_script( $handle, '', [ 'jquery' ], WCML_VERSION, true );
			wp_enqueue_script( $handle );
			wp_add_inline_script( $handle, $wcml_reports_set_currency_script );

			$this->reports_currency = $_COOKIE['_wcml_reports_currency'] ?? wcml_get_woocommerce_currency_option();

			add_filter( 'woocommerce_currency_symbol', [ $this, '_set_reports_currency_symbol' ] );
		}
	}

	public function admin_reports_query_filter( $query ) {

		if( \WCML\Rest\Functions::isRestApiRequest() ) {
			$this->reports_currency = $this->woocommerce_wpml->multi_currency->get_rest_currency();
		}

		if( !$this->reports_currency ){
			return $query;
		}

		$query['join']  .= " LEFT JOIN {$this->wpdb->postmeta} AS meta_order_currency ON meta_order_currency.post_id = posts.ID ";
		$query['where'] .= sprintf( " AND meta_order_currency.meta_key='_order_currency' AND meta_order_currency.meta_value = '%s' ",
			$this->reports_currency );

		return $query;
	}

	public function _set_reports_currency_symbol( $currency ) {
		static $no_recur = false;
		if ( ! empty( $this->reports_currency ) && empty( $no_recur ) ) {
			$no_recur = true;
			$currency = get_woocommerce_currency_symbol( $this->reports_currency );
			$no_recur = false;
		}

		return $currency;
	}

	public function set_reports_currency() {

		$nonce = filter_input( INPUT_POST, 'wcml_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'reports_set_currency' ) ) {
			echo json_encode( [ 'error' => __( 'Invalid nonce', 'woocommerce-multilingual' ) ] );
			die();
		}

		$cookie_name = '_wcml_reports_currency';
		// @todo uncomment or delete when #wpmlcore-5796 is resolved
		// do_action( 'wpsc_add_cookie', $cookie_name );
		setcookie( $cookie_name, filter_input( INPUT_POST, 'currency', FILTER_SANITIZE_FULL_SPECIAL_CHARS ),
			time() + 86400, COOKIEPATH, COOKIE_DOMAIN );

		exit;

	}

	public function reports_currency_selector() {
		$currency_codes = $this->woocommerce_wpml->multi_currency->get_currency_codes();
		$currencies     = get_woocommerce_currencies();

		// Remove filter temporary.
		remove_filter( 'woocommerce_currency_symbol', [ $this, '_set_reports_currency_symbol' ] );
		?>
        <select id="dropdown_shop_report_currency" style="margin-left:5px;">
			<?php if ( empty( $currency_codes ) ): ?>
                <option value=""><?php _e( 'Currency - no orders found', 'woocommerce-multilingual' ) ?></option>
			<?php else: ?>
				<?php foreach ( $currency_codes as $currency ): ?>
                    <option value="<?php echo esc_attr( $currency ) ?>" <?php selected( $currency, $this->reports_currency ); ?>>
						<?php printf( "%s (%s)", $currencies[ $currency ], get_woocommerce_currency_symbol( $currency ) ) ?>
                    </option>
				<?php endforeach; ?>
			<?php endif; ?>
        </select>
		<?php

		// Add filter back.
		add_filter( 'woocommerce_currency_symbol', [ $this, '_set_reports_currency_symbol' ] );
	}

	/**
	 * Filter some WC dashboard status queries by currency when products are stored in the posts table.
	 *
	 * @param array  $query
	 * @param string $tableAlias
	 *
	 * @return array
	 */
	public function filterOrdersAsPostsByCurrencyPostmeta( $query, $tableAlias = 'posts' ) {

		$currency = $this->woocommerce_wpml->multi_currency->admin_currency_selector->get_cookie_dashboard_currency();

		$query['join']  .= " INNER JOIN {$this->wpdb->postmeta} AS currency_postmeta ON {$tableAlias}.ID = currency_postmeta.post_id";
		$query['where'] .= $this->wpdb->prepare( " AND currency_postmeta.meta_key = '_order_currency' AND currency_postmeta.meta_value = %s", $currency );

		return $query;
	}

	/**
	 * Filter WC dashboard top seller query.
	 *
	 * @param array $query Query to filter
	 *
	 * @return array
	 *
	 * @see https://onthegosystems.myjetbrains.com/youtrack/issue/wcml-4789
	 * @see https://github.com/woocommerce/woocommerce/commit/c83b030834b52e3f8dbd4e42f0751a4b911479a2
	 */
	public function filterDashboardstatusWidgetTopSellerQuery( $query ) {
		// Before WooCommerce 8.8.0, the table alias was posts.
		/* @phpstan-ignore booleanAnd.rightAlwaysTrue */
		if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, self::TOP_SELLER_QUERY_USE_SELECT_ORDERS_FROM, '<' ) ) {
			return $this->filterOrdersAsPostsByCurrencyPostmeta( $query );
		}

		// After WooCommerce 8.8.0, the table alias was orders.
		// If HPOS is disabled, it still matches the posts table.
		if ( false === COTHelper::isUsageEnabled() ) {
			return $this->filterOrdersAsPostsByCurrencyPostmeta( $query, 'orders' );
		}

		// If HPOS is enabled after WooCommerce 8.8.0, the orders alias matches the orders table, which has a currency column.
		$query['where'] .= $this->wpdb->prepare( " AND orders.currency = %s", $this->woocommerce_wpml->multi_currency->admin_currency_selector->get_cookie_dashboard_currency() );

		return $query;
	}

}
