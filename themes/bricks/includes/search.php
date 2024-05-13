<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Search {
	public function __construct() {
		if ( Database::get_setting( 'searchResultsQueryBricksData', false ) ) {
			// Checking if query contains "s" var @since 1.5.7 (CU #3pxbtcp)
			add_filter( 'posts_join', [ $this, 'search_postmeta_table' ], 10, 2 );
			add_filter( 'posts_where', [ $this, 'modify_search_for_postmeta' ], 10, 2 );
			add_filter( 'posts_distinct', [ $this, 'search_distinct' ], 10, 2 );
		}
	}

	/**
	 * Helper: Check if is_search() OR Bricks infinite scroll REST API search results
	 *
	 * @since 1.5.7
	 */
	public function is_search( $query ) {
		// WordPress search results
		if ( is_search() ) {
			return true;
		}

		// Bricks: Infinite scroll search results
		if ( bricks_is_rest_call() && isset( $query->query_vars['s'] ) && ! empty( $query->query_vars['s'] ) ) {
			return true;
		}
	}

	/**
	 * Search 'posts' and 'postmeta' tables
	 *
	 * https://adambalee.com/search-wordpress-by-custom-fields-without-a-plugin/
	 * http://codex.wordpress.org/Plugin_API/Filter_Reference/posts_join
	 *
	 * @since 1.3.7
	 */
	public function search_postmeta_table( $join, $query ) {
		global $wpdb;

		if ( $this->is_search( $query ) ) {
			$join .= ' LEFT JOIN ' . $wpdb->postmeta . ' bricksdata ON ' . $wpdb->posts . '.ID = bricksdata.post_id ';
		}

		return $join;
	}

	/**
	 * Modify search query
	 *
	 * http://codex.wordpress.org/Plugin_API/Filter_Reference/posts_where
	 *
	 * @since 1.3.7
	 */
	public function modify_search_for_postmeta( $where, $query ) {
		global $pagenow, $wpdb;

		if ( $this->is_search( $query ) ) {
			$where = preg_replace(
				'/\(\s*' . $wpdb->posts . ".post_title\s+LIKE\s*(\'[^\']+\')\s*\)/",
				'(' . $wpdb->posts . '.post_title LIKE $1) OR (bricksdata.meta_value LIKE $1)',
				$where
			);
		}

		return $where;
	}

	/**
	 * Prevent duplicates
	 *
	 * http://codex.wordpress.org/Plugin_API/Filter_Reference/posts_distinct
	 *
	 * @since 1.3.7
	 */
	public function search_distinct( $where, $query ) {
		global $wpdb;

		if ( $this->is_search( $query ) ) {
			return 'DISTINCT';
		}

		return $where;
	}
}
