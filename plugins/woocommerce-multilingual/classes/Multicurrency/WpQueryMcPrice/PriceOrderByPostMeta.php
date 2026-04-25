<?php

namespace WCML\Multicurrency\WpQueryMcPrice;

class PriceOrderByPostMeta extends AbstractPriceByPostMeta implements \IWPML_Backend_Action {

	const ORDER_BY_VALUE = [ 'price-desc', 'price' ];

	public function add_hooks() {
		if ( $this->detectProductOrderByPriceInMultiCurrency() ) {
			add_filter( 'pre_get_posts', [ $this, 'pre_get_posts' ] );
			add_filter( 'posts_clauses', [ $this, 'posts_clauses_wcml_order_by_price_post_meta' ], 11, 2 );
		}
	}

	/**
	 * These Hooks have a huge impact on the performance of the application, we only want to use them when necessary
	 */
	private function detectProductOrderByPriceInMultiCurrency(): bool {
		if ( ! isset( $_GET['orderby'] ) ) {
			return false;
		}

		if ( ! in_array( $_GET['orderby'], self::ORDER_BY_VALUE ) ) {
			return false;
		}

		return $this->default_currency !== $this->client_currency;
	}

	/**
	 * We find, move and remove the parameter from meta_query - so that they
	 * do not add incorrect SQL - we will build this based on this data later
	 *
	 * @param \WP_Query $wp_query The WP_Query instance (passed by reference).
	 *
	 * @return \WP_Query
	 */
	public function pre_get_posts( $wp_query ) {
		if ( isset( $wp_query->query['orderby'] ) && in_array( $wp_query->query['orderby'], self::ORDER_BY_VALUE ) && isset( $wp_query->query_vars['wc_query'] ) && $wp_query->query_vars['wc_query'] === 'product_query' ) {
			/* @phpstan-ignore property.notFound */
			$wp_query->wcml_orderby_price = $wp_query->query['orderby'];

			unset( $wp_query->query['orderby'] );
			unset( $wp_query->query_vars['orderby'] );
		}

		return $wp_query;
	}

	/**
	 * based on meta query data we build our own SQL
	 *
	 * @param array $clauses
	 * @param \WP_Query $wp_query
	 *
	 * @return array
	 */
	public function posts_clauses_wcml_order_by_price_post_meta( $clauses, $wp_query ) {
		if ( empty( $wp_query->wcml_orderby_price ) ) {
			return $clauses;
		}

		$exchange_rates = $this->woocommerce_wpml->multi_currency->get_exchange_rates();
		if ( ! isset( $exchange_rates[ $this->client_currency ] ) ) {
			return $clauses;
		}
		$exchange_rate = $exchange_rates[ $this->client_currency ];

		$clauses['join'] = $this->buildWCMLMultiCurrencyQueryJoin( $clauses['join'] );

		$orderBy = "CASE " . self::WCML_CUSTOM_PRICES_STATUS_ALIAS . ".meta_value ";
		$orderBy .= "WHEN '1' THEN CAST(" . self::WCML_MC_PRICE_ALIAS . ".meta_value AS decimal(19,4)) "; // No conversion
		$orderBy .= "ELSE ( CAST(" . self::WCML_PRICE_ALIAS . ".meta_value AS decimal(19,4)) * " . $exchange_rate . " ) END "; // Need recalculation currency exchange_rates
		$orderBy .= sprintf( ' %s ', $wp_query->wcml_orderby_price == 'price' ? 'ASC' : 'DESC' );
		$orderBy .= empty( $clauses['orderby'] ) ? ' ' : ', ' . $clauses['orderby'];

		$clauses['orderby'] = $orderBy;

		return $clauses;
	}
}
