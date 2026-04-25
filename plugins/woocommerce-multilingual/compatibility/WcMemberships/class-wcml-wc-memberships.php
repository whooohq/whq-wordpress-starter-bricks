<?php

use PHPUnit\Framework\ExpectationFailedException;

class WCML_WC_Memberships implements \IWPML_Action {

	const SAVED_POST_PARENT = 'wcml_memberships_post_parent';
	/**
	 * @var WPML_WP_API
	 */
	private $wp_api;

	/**
	 * @param WPML_WP_API $wp_api
	 */
	public function __construct( WPML_WP_API $wp_api ) {
		$this->wp_api = $wp_api;
	}

	public function add_hooks() {
		add_filter( 'wcml_endpoint_keys_to_options', [ $this, 'endpoint_keys_to_options' ] );
		add_filter( 'wcml_register_endpoints_store_urls', [ $this, 'register_endpoints_store_urls' ] );
		add_filter( 'wcml_endpoints_translation_controls', [ $this, 'register_translation_controls' ] );
		add_filter( 'wc_memberships_members_area_my-memberships_actions', [ $this, 'filter_actions_links' ] );
		add_filter( 'wpml_pre_parse_query', [ $this, 'save_post_parent' ] );
		add_filter( 'wpml_post_parse_query', [ $this, 'restore_post_parent' ] );
		add_filter( 'wc_memberships_rule_object_ids', [ $this, 'add_translated_object_ids' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'load_assets' ] );

		add_filter( 'woocommerce_order_get__wc_memberships_access_granted', [ $this, 'orderMemberships' ] );
	}

	/**
	 * @param array $actions
	 * @return array
	 */
	public function filter_actions_links( $actions ) {
		foreach ( $actions as $key => $action ) {
			if ( 'view' === $key ) {
				$membership_endpoints   = $this->get_members_area_endpoint();
				$actions[ $key ]['url'] = str_replace( $membership_endpoints['original'], $membership_endpoints['translated'], $action['url'] );
			}
		}

		return $actions;
	}

	/**
	 * @param WP_Query $q
	 *
	 * @return WP_Query
	 */
	public function save_post_parent( $q ) {
		if ( isset( $q->query_vars['post_type'] )
			&& in_array( 'wc_user_membership', (array) $q->query_vars['post_type'], true )
			&& ! empty( $q->query_vars['post_parent'] ) ) {
			$q->query_vars[ self::SAVED_POST_PARENT ] = $q->query_vars['post_parent'];
		}

		return $q;
	}

	/**
	 * @param WP_Query $q
	 *
	 * @return WP_Query
	 */
	public function restore_post_parent( $q ) {
		if ( isset( $q->query_vars[ self::SAVED_POST_PARENT ] ) ) {
			$q->query_vars['post_parent'] = $q->query_vars[ self::SAVED_POST_PARENT ];
			unset( $q->query_vars[ self::SAVED_POST_PARENT ] );
		}

		return $q;
	}

	/**
	 * @param int[] $object_ids
	 *
	 * @return int[]
	 */
	public function add_translated_object_ids( $object_ids ) {
		$result = [];
		foreach ( $object_ids as $object_id ) {
			$type         = apply_filters( 'wpml_element_type', get_post_type( $object_id ) );
			$trid         = apply_filters( 'wpml_element_trid', null, $object_id, $type );
			$translations = array_values( wp_list_pluck(
				apply_filters( 'wpml_get_element_translations', [], $trid, $type ),
				'element_id'
			) );

			$result = array_merge( $result, [ $object_id ], $translations );
		}

		return array_values( array_unique( array_map( 'intval', $result ) ) );
	}

	public function load_assets() {
		if ( wc_get_page_id( 'myaccount' ) === get_the_ID() ) {
			$wcml_plugin_url = $this->wp_api->constant( 'WCML_PLUGIN_URL' );
			$wcml_version    = $this->wp_api->constant( 'WCML_VERSION' );
			wp_register_script( 'wcml-members-js', $wcml_plugin_url . '/compatibility/res/js/wcml-members.js', [ 'jquery' ], $wcml_version, true );
			wp_enqueue_script( 'wcml-members-js' );
			wp_localize_script( 'wcml-members-js', 'wc_memberships_memebers_area_endpoint', $this->get_members_area_endpoint() );
		}
	}

	/**
	 * @return array
	 */
	private function get_members_area_endpoint() {
		$endpoint            = get_option( 'woocommerce_myaccount_members_area_endpoint', 'members-area' );
		$string_context      = WCML_Url_Translation::get_endpoints_string_context();
		$translated_endpoint = apply_filters( 'wpml_translate_single_string', $endpoint, $string_context, 'members_area' );

		return [
			'original'   => $endpoint,
			'translated' => $translated_endpoint,
		];
	}

	/**
	 * @param array $memberships
	 *
	 * @return array
	 */
	public function orderMemberships( $memberships ) {
		if ( ! doing_action( 'woocommerce_thankyou' ) ) {
			return $memberships;
		}

		$relevantMemberships = [];
		$wpmlCurrentLanguage = apply_filters( 'wpml_current_language', null );
		foreach ( $memberships as $membershipId => $membershipData ) {
			$userMembership = wc_memberships_get_user_membership( (int) $membershipId );
			$membershipPlan = $userMembership ? $userMembership->get_plan() : null;

			if ( null === $membershipPlan ) {
				continue;
			}

			$membershipPlanLanguageDetails = apply_filters( 'wpml_element_language_details', null, [
				'element_id'   => $membershipPlan->id,
				'element_type' => 'wc_membership_plan',
			] );

			if (
				null === $membershipPlanLanguageDetails
				|| $wpmlCurrentLanguage === $membershipPlanLanguageDetails->language_code
			) {
				$relevantMemberships[ $membershipId ] = $membershipData;
			}
		}

		return $relevantMemberships;
	}

	/**
	 * @param array<string,string> $endpoint_keys_to_options
	 *
	 * @return array<string,string>
	 */
	public function endpoint_keys_to_options( $endpoint_keys_to_options ) {
		$endpoint_keys_to_options['members_area']        = 'woocommerce_myaccount_members_area_endpoint';
		$endpoint_keys_to_options['profile_fields_area'] = 'woocommerce_myaccount_profile_fields_area_endpoint';
		return $endpoint_keys_to_options;
	}

	/**
	 * @param array<string,string> $store_urls
	 *
	 * @return array<string,string>
	 */
	public function register_endpoints_store_urls( $store_urls ) {
		$store_urls['members_area']        = get_option( 'woocommerce_myaccount_members_area_endpoint', 'members-area' );
		$store_urls['profile_fields_area'] = get_option( 'woocommerce_myaccount_profile_fields_area_endpoint', 'my-profile' );
		return $store_urls;
	}

	/**
	 * @param array<string,string> $translation_controls
	 *
	 * @return array<string,string>
	 */
	public function register_translation_controls( $translation_controls ) {
		$translation_controls['members_area']        = 'woocommerce_myaccount_members_area_endpoint';
		$translation_controls['profile_fields_area'] = 'woocommerce_myaccount_profile_fields_area_endpoint';
		return $translation_controls;
	}

}
