<?php

class WCML_Endpoints {

	/** @var woocommerce_wpml */
	private $woocommerce_wpml;
	/**
	 * @var SitePress
	 */
	private $sitepress;
	/**
	 * @var wpdb
	 */
	private $wpdb;

	/** @var array */
	private $originalEndpoints = [];
	/** @var array */
	private $translatedEndpoints = [];
	/** @var array */
	private $endpointsToTranslations = [];
	/** @var array */
	private $translatedEndpointsInDatabase = [];
	/** @var array */
	private $editAddressSlugs = [];

	/** @var array<string,string> */
	private $endpointKeysToOptions = [
		'order-pay'                  => 'woocommerce_checkout_pay_endpoint',
		'order-received'             => 'woocommerce_checkout_order_received_endpoint',
		'orders'                     => 'woocommerce_myaccount_orders_endpoint',
		'view-order'                 => 'woocommerce_myaccount_view_order_endpoint',
		'downloads'                  => 'woocommerce_myaccount_downloads_endpoint',
		'edit-account'               => 'woocommerce_myaccount_edit_account_endpoint',
		'edit-address'               => 'woocommerce_myaccount_edit_address_endpoint',
		'payment-methods'            => 'woocommerce_myaccount_payment_methods_endpoint',
		'lost-password'              => 'woocommerce_myaccount_lost_password_endpoint',
		'customer-logout'            => 'woocommerce_logout_endpoint',
		'add-payment-method'         => 'woocommerce_myaccount_add_payment_method_endpoint',
		'delete-payment-method'      => 'woocommerce_myaccount_delete_payment_method_endpoint',
		'set-default-payment-method' => 'woocommerce_myaccount_set_default_payment_method_endpoint',
	];

	/**
	 * @see WPML_Endpoints_Support::STRING_CONTEXT
	 */
	const STRING_CONTEXT = 'WP Endpoints';

	public function __construct( woocommerce_wpml $woocommerce_wpml, SitePress $sitepress, wpdb $wpdb ) {
		$this->woocommerce_wpml = $woocommerce_wpml;
		$this->sitepress        = $sitepress;
		$this->wpdb             = $wpdb;
	}

	public function add_hooks() {
		add_action( 'init', [ $this, 'migrate_ones_string_translations' ], 8 );

		add_filter( 'wpml_registered_endpoints', [ $this, 'unregisterWcEndpointsFromWpml' ] );

		if ( ! is_admin() ) {
			add_action( 'init', [ $this, 'initQueryVars' ], 9 );
			add_action( 'init', [ $this, 'translateOptions' ] );
			add_filter( 'woocommerce_get_query_vars', [ $this, 'registerAndTranslate' ], 99 );
			add_filter( 'option_rewrite_rules', [ $this, 'adjustRewriteRules' ], 0 );
		}

		add_filter( 'wpml_endpoint_url_value', [ $this, 'filter_endpoint_url_value' ], 10, 2 );
		add_filter( 'wpml_current_ls_language_url_endpoint', [ $this, 'add_endpoint_to_current_ls_language_url' ], 10, 4 );

		add_filter( 'wpml_sl_blacklist_requests', [ $this, 'reserved_requests' ] );
		add_filter( 'woocommerce_get_endpoint_url', [ $this, 'filter_get_endpoint_url' ], 10, 4 );
	}

	public function migrate_ones_string_translations() {
		if ( ! get_option( 'wcml_endpoints_context_updated' ) ) {

			$endpoint_keys = [
				'order-pay',
				'order-received',
				'view-order',
				'edit-account',
				'edit-address',
				'lost-password',
				'customer-logout',
				'add-payment-method',
				'set-default-payment-method',
				'delete-payment-method',
				'payment-methods',
				'downloads',
				'orders',
			];

			foreach ( $endpoint_keys as $endpoint_key ) {

				$existing_string_id = $this->wpdb->get_var(
					$this->wpdb->prepare( "SELECT id FROM {$this->wpdb->prefix}icl_strings
											WHERE context = %s AND name = %s",
					WPML_Endpoints_Support::STRING_CONTEXT, $endpoint_key )
				);

				if ( $existing_string_id ) {

					// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$existing_wcml_string_id = $this->wpdb->get_var(
						$this->wpdb->prepare(
							"SELECT id FROM {$this->wpdb->prefix}icl_strings WHERE context = %s AND name = %s",
							WCML_Url_Translation::WC_STRING_CONTEXT,
							$endpoint_key
						)
					);

					if ( $existing_wcml_string_id ) {
						$wcml_string_translations = icl_get_string_translations_by_id( $existing_wcml_string_id );

						foreach ( $wcml_string_translations as $language_code => $translation_data ) {
							icl_add_string_translation( $existing_string_id, $language_code, $translation_data['value'], ICL_STRING_TRANSLATION_COMPLETE );
						}

						wpml_unregister_string_multi( [ $existing_wcml_string_id ] );
					}
				} else {

					// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$this->wpdb->query(
						$this->wpdb->prepare(
							"UPDATE {$this->wpdb->prefix}icl_strings
                                  SET context = %s
                                  WHERE context = %s AND name = %s",
							WPML_Endpoints_Support::STRING_CONTEXT,
							WCML_Url_Translation::WC_STRING_CONTEXT,
							$endpoint_key
						)
					);

					$string_id = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT id FROM {$this->wpdb->prefix}icl_strings WHERE context = %s AND name = %s", WPML_Endpoints_Support::STRING_CONTEXT, $endpoint_key ) );

					if ( $string_id ) {
						$this->wpdb->query(
							$this->wpdb->prepare( "UPDATE {$this->wpdb->prefix}icl_strings
                              SET domain_name_context_md5 = %s
                              WHERE id = %d",
							md5( $endpoint_key . WPML_Endpoints_Support::STRING_CONTEXT ), $string_id )
						);
					}
				}
			}
			update_option( 'wcml_endpoints_context_updated', true );
		}
	}

	/**
	 * Just call WC()->query->get_query_vars() early so the filter woocommerce_get_query_vars is executed.
	 */
	public function initQueryVars() {
		WC()->query->get_query_vars();
	}

	/**
	 * @param array<string,string> $queryVars
	 *
	 * @return array<string,string>
	 */
	public function registerAndTranslate( $queryVars ) {
		if ( empty( $this->originalEndpoints ) ) {
			$this->originalEndpoints = $queryVars;
		}

		$currentLanguage   = $this->sitepress->get_current_language();
		$pendingQueryVars  = [];
		$originalQueryVars = $queryVars;

		foreach ( $queryVars as $key => $endpoint ) {
			if ( in_array( $endpoint, $this->translatedEndpoints, true ) ) {
				continue;
			}
			if ( array_key_exists( $endpoint, $this->endpointsToTranslations ) ) {
				$queryVars[ $key ] = $this->endpointsToTranslations[ $endpoint ];
				continue;
			}
			if ( $key !== $endpoint && $this->isRegisteredEndpointString( $endpoint, $endpoint ) ) {
				$this->migrateEndpointStringName( $key, $endpoint );
			} elseif ( ! $this->isRegisteredEndpointString( $key, $endpoint ) ) {
				$this->registerEndpointString( $key, $endpoint );
			}
			$pendingQueryVars[ $key ] = $endpoint;
		}

		foreach ( $pendingQueryVars as $key => $endpoint ) {
			$queryVars[ $key ]                          = $this->translateEndpoint( $key, $endpoint, $currentLanguage );
			$this->translatedEndpoints[ $key ]          = $queryVars[ $key ];
			$this->endpointsToTranslations[ $endpoint ] = $queryVars[ $key ];
		}

		$additionalQueryVars = apply_filters( 'wcml_register_endpoints_query_vars', $queryVars, $originalQueryVars, $this );

		return array_merge( $queryVars, $additionalQueryVars );
	}

	/**
	 * @param string      $key
	 * @param string|null $endpointOrLanguage
	 * @param string|null $language
	 *
	 * @return string
	 *
	 * @deprecated Keep for backward compatibility: this was used in an example in our documentation.
	 */
	public function get_endpoint_translation( $key, $endpointOrLanguage = null, $language = null ) {
		$endpoint = $key;

		$activeLanguages = array_keys( $this->sitepress->get_active_languages() );

		if ( in_array( $endpointOrLanguage, $activeLanguages, true ) ) {
			$language = $endpointOrLanguage;
		} elseif ( $endpointOrLanguage ) {
			$endpoint = $endpointOrLanguage;
		}

		return $this->translateEndpoint( $key, $endpoint, $language );
	}

	/**
	 * WooCommerce query vars / endpoints should not be managed by WPML directly,
	 * because they can be registered with a name that is different from its value.
	 *
	 * @param array<string,string> $endpoints
	 *
	 * @return array<string,string>
	 */
	public function unregisterWcEndpointsFromWpml( $endpoints ) {
		foreach ( $this->originalEndpoints as $key => $value ) {
			unset( $endpoints[ $key ] );
			unset( $endpoints[ $value ] );
		}
		foreach ( $this->translatedEndpoints as $key => $value ) {
			unset( $endpoints[ $value ] );
		}
		return $endpoints;
	}

	/**
	 * @param string $key
	 * @param string $endpoint
	 *
	 * @return bool
	 */
	private function isRegisteredEndpointString( $key, $endpoint ) {
		$endpoints = wp_cache_get( self::STRING_CONTEXT, __CLASS__ );

		if ( false === $endpoints ) {
			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$endpointsByname = $this->wpdb->get_results(
				$this->wpdb->prepare(
					"
					SELECT name, value FROM {$this->wpdb->prefix}icl_strings WHERE context = %s
					",
					self::STRING_CONTEXT
				)
			);
			// phpcs:enable

			$endpoints = wp_list_pluck( $endpointsByname, 'value', 'name' );

			wp_cache_set( self::STRING_CONTEXT, $endpoints, __CLASS__ );
		}

		return array_key_exists( $key, $endpoints ) && $endpoints[ $key ] === $endpoint;
	}

	/**
	 * Migrate an endpoint registered with its value as its name, pushing the endpoing key as name instead.
	 * If another endpoint with a name matching the endpoint key already exists, update it to use a name {key}-back-RAND, just in case.
	 *
	 * Invalidates the cache for self::STRING_CONTEXT.
	 *
	 * @param string $key
	 * @param string $endpoint
	 */
	private function migrateEndpointStringName( $key, $endpoint ) {
		$keyBackup = $key . '-bak-' . wp_rand( 0, 1000 );
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$this->wpdb->query(
			$this->wpdb->prepare(
				"
				UPDATE {$this->wpdb->prefix}icl_strings
				SET name = %s, domain_name_context_md5 = %s
				WHERE context = %s AND name = %s
				",
				$keyBackup,
				md5( $keyBackup . self::STRING_CONTEXT ),
				self::STRING_CONTEXT,
				$key
			)
		);
		$this->wpdb->query(
			$this->wpdb->prepare(
				"
				UPDATE {$this->wpdb->prefix}icl_strings
				SET name = %s, domain_name_context_md5 = %s
				WHERE context = %s AND name = %s and value = %s
				",
				$key,
				md5( $key . self::STRING_CONTEXT ),
				self::STRING_CONTEXT,
				$endpoint,
				$endpoint
			)
		);
		// phpcs:enable

		do_action( 'wpml_st_string_updated' );
		wp_cache_delete( self::STRING_CONTEXT, __CLASS__ );
	}

	/**
	 * Invalidates the cache for self::STRING_CONTEXT.
	 *
	 * @param string $key
	 * @param string $endpoint
	 */
	private function registerEndpointString( $key, $endpoint ) {
		do_action( 'wpml_register_single_string', self::STRING_CONTEXT, $key, $endpoint );
		wp_cache_delete( self::STRING_CONTEXT, __CLASS__ );
	}

	/**
	 * Make sure that rewrite rules contains entries for endpoint translations.
	 * Instead of replacing the rules for the original endpoints, insert the rules for translations right after the original ones.
	 *
	 * @param array<string,string> $rewriteRules
	 *
	 * @return array<string,string>
	 */
	public function adjustRewriteRules( $rewriteRules ) {
		if ( empty( $rewriteRules ) ) {
			return $rewriteRules;
		}

		foreach ( $this->endpointsToTranslations as $endpoint => $endpointTranslation ) {
			if ( $endpoint === $endpointTranslation ) {
				continue;
			}

			$adjustedRewriteRules = [];
			foreach ( $rewriteRules as $k => $v ) {
				if ( array_key_exists( $k, $adjustedRewriteRules ) ) {
					continue;
				}
				// Keep the current rewrite rule.
				$adjustedRewriteRules[ $k ] = $v;

				// Maybe insert the rewrite rule for the endpoint translation.
				$newKey = false;
				if ( 0 === strpos( $k, $endpoint . '(/(.*))?/?$' ) ) {
					$newKey = str_replace(
						$endpoint . '(/(.*))?/?$',
						$endpointTranslation . '(/(.*))?/?$',
						$k
					);
				} elseif ( false !== strpos( $k, '/' . $endpoint . '(/(.*))?/?$' ) ) {
					$newKey = str_replace(
						'/' . $endpoint . '(/(.*))?/?$',
						'/' . $endpointTranslation . '(/(.*))?/?$',
						$k
					);
				}

				if ( ! $newKey ) {
					continue;
				}

				preg_match(
					'/&' . $endpoint . '=\$matches\[(\d+)\]/',
					$v,
					$matches
				);
				$matchesValue = isset( $matches[1] ) ? '$matches[' . $matches[1] . ']' : '';
				$newValue     = str_replace(
					'&' . $endpoint . '=' . $matchesValue,
					'&' . $endpointTranslation . '=' . $matchesValue . '&' . $endpoint . '=' . $matchesValue,
					$v
				);

				$adjustedRewriteRules[ $newKey ] = $newValue;
			}
			$rewriteRules = $adjustedRewriteRules;
		}

		return $rewriteRules;
	}

	/**
	 * Third parties might get endpoints stored in options directly: serve them with translations.
	 */
	public function translateOptions() {
		/**
		 * Register WooCommerce endpoints stored in options, as a key => option_name pair.
		 *
		 * The key should match the query var used to define the endpoint.
		 * The option_name should match the option name used to store the endpoint value.
		 *
		 * @since 5.5.3
		 *
		 * @param array<string,string> $keysToOptions An array of key => option_name pairs.
		 *
		 * @return array<string,string>
		 */
		$keysToOptions = apply_filters(
			'wcml_endpoint_keys_to_options',
			$this->endpointKeysToOptions
		);

		foreach ( $keysToOptions as $key => $option ) {
			add_filter(
				'option_' . $option,
				function( $originalEndpoint ) use ( $key ) {
					if ( array_key_exists( $key, $this->translatedEndpoints ) ) {
						return $this->translatedEndpoints[ $key ];
					}
					return $originalEndpoint;
				},
				99
			);
		}
	}

	/**
	 * @param string      $key
	 * @param string      $endpoint
	 * @param string|null $language
	 * @param bool        $encode
	 *
	 * @return string
	 */
	public function translateEndpoint( $key, $endpoint, $language = null, $encode = true ) {
		if ( null === $language ) {
			$language = $this->sitepress->get_current_language();
		}
		$translatedEndpointsInDatabase = $this->getTranslatedEndpointsInDatabase( $language );
		$endpointTranslation           = $translatedEndpointsInDatabase[ $key ] ?? $endpoint;

		if ( ! empty( $endpointTranslation ) ) {
			return $encode
				? implode( '/', array_map( 'rawurlencode', explode( '/', $endpointTranslation ) ) )
				: $endpointTranslation;
		} else {
			return $endpoint;
		}
	}

	/**
	 * @param string $language
	 * @param bool   $refreshCache
	 *
	 * @return array<string,string>
	 */
	private function getTranslatedEndpointsInDatabase( $language, $refreshCache = false ) {
		if ( ! $refreshCache && array_key_exists( $language, $this->translatedEndpointsInDatabase ) ) {
			return $this->translatedEndpointsInDatabase[ $language ];
		}

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$translatedEndpointsInDatabase = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"
				SELECT s.name as name, st.value as value
				FROM {$this->wpdb->prefix}icl_strings s
				JOIN {$this->wpdb->prefix}icl_string_translations st
				WHERE s.context = %s
				AND s.id = st.string_id
				AND st.language = %s
				",
				self::STRING_CONTEXT,
				$language
			)
		);
		// phpcs:enable

		$this->translatedEndpointsInDatabase[ $language ] = wp_list_pluck( $translatedEndpointsInDatabase, 'value', 'name' );

		return $this->translatedEndpointsInDatabase[ $language ];
	}

	/**
	 * @param array<int,string> $requests
	 *
	 * @return array<int,string>
	 */
	public function reserved_requests( $requests ) {
		$cache_key   = 'reserved_requests';
		$cache_group = 'wpml-endpoints';

		$found             = null;
		$reserved_requests = wp_cache_get( $cache_key, $cache_group, false, $found );

		if ( ! $found || ! $reserved_requests ) {
			$reserved_requests = [];

			$current_language = $this->sitepress->get_current_language();
			$languages        = $this->sitepress->get_active_languages();
			$languages_codes  = array_keys( $languages );
			foreach ( $languages_codes as $language_code ) {
				$this->sitepress->switch_lang( $language_code );

				$my_account_page_id = wc_get_page_id( 'myaccount' );

				if ( $my_account_page_id ) {

					$account_base = get_page_uri( $my_account_page_id );

					if ( $account_base ) {
						$reserved_requests[]           = $account_base;
						$reserved_requests[]           = '/^' . str_replace( '/', '\/', $account_base ) . '/';
						$is_page_display_as_translated = $this->sitepress->is_display_as_translated_post_type( 'page' );

						if ( ! $is_page_display_as_translated ) {
							$wcQueryVars = WC()->query->get_query_vars();
							foreach ( $wcQueryVars as $key => $endpoint ) {
								$translated_endpoint = $this->translateEndpoint( $key, $endpoint, $language_code );
								$reserved_requests[] = $account_base . '/' . $translated_endpoint;
							}
						}
					}
				}
			}

			$this->sitepress->switch_lang( $current_language );

			$reserved_requests[] = '/' . get_option( 'woocommerce_checkout_pay_endpoint', 'order-pay' ) . '/';

			wp_cache_set( $cache_key, $reserved_requests, $cache_group );

		}

		if ( $reserved_requests ) {
			$requests = array_unique( array_merge( $requests, $reserved_requests ) );
		}

		return $requests;
	}

	/**
	 * @param string      $slug
	 * @param string|bool $language
	 *
	 * @return string
	 */
	private function get_translated_edit_address_slug( $slug, $language = false ) {
		if ( $language && isset( $this->editAddressSlugs[ $language ][ $slug ] ) ) {
			return $this->editAddressSlugs[ $language ][ $slug ];
		}

		/** @var WCML_WC_Strings $strings */
		$strings          = $this->woocommerce_wpml->strings;
		$strings_language = $strings->get_string_language( $slug, 'woocommerce', 'edit-address-slug: ' . $slug );
		if ( $strings_language === $language ) {
			return $slug;
		}

		$translated_slug = apply_filters( 'wpml_translate_single_string', $slug, 'woocommerce', 'edit-address-slug: ' . $slug, $language );
		if ( $translated_slug === $slug ) {
			if ( $language ) {
				$translated_slug = $strings->get_translation_from_woocommerce_mo_file( $strings->get_msgid_for_mo( $slug, 'edit-address-slug' ), $language );
			} else {
				$translated_slug = _x( $slug, 'edit-address-slug', 'woocommerce' );
			}
		}

		if ( $language ) {
			$this->editAddressSlugs[ $language ][ $slug ] = $translated_slug;
		}

		return $translated_slug;
	}

	/**
	 * @param string $url
	 * @param string $endpoint
	 * @param string $value
	 * @param string $permalink
	 *
	 * @return string
	 */
	public function filter_get_endpoint_url( $url, $endpoint, $value, $permalink ) {
		remove_filter( 'woocommerce_get_endpoint_url', [ $this, 'filter_get_endpoint_url' ], 10 );

		$key = array_search( $endpoint, $this->translatedEndpoints, true );
		if ( false === $key ) {
			$key = array_search( $endpoint, $this->originalEndpoints, true );
		}

		if ( false !== $key ) {
			$currentLanguage     = $this->sitepress->get_current_language();
			$translated_endpoint = $this->translateEndpoint( $key, $endpoint, $currentLanguage );
			$url                 = wc_get_endpoint_url( $translated_endpoint, $value, $this->sitepress->convert_url( $permalink ) );
		}

		add_filter( 'woocommerce_get_endpoint_url', [ $this, 'filter_get_endpoint_url' ], 10, 4 );
		return $url;
	}

	/**
	 * @param string      $value
	 * @param string|bool $page_lang
	 *
	 * @return string
	 */
	public function filter_endpoint_url_value( $value, $page_lang ) {
		if ( $page_lang ) {
			$edit_address_shipping = $this->get_translated_edit_address_slug( 'shipping', $page_lang );
			$edit_address_billing  = $this->get_translated_edit_address_slug( 'billing', $page_lang );

			if ( $edit_address_shipping == urldecode( $value ) ) {
				$value = $this->get_translated_edit_address_slug( 'shipping', $this->sitepress->get_current_language() );
			} elseif ( $edit_address_billing == urldecode( $value ) ) {
				$value = $this->get_translated_edit_address_slug( 'billing', $this->sitepress->get_current_language() );
			}
		}

		return $value;
	}

	/**
	 * @param string      $value
	 * @param string      $switcherLanguage
	 * @param string|bool $postLanguage
	 *
	 * @return string
	 */
	private function adjustCurrentLsEndpointValue( $value, $switcherLanguage, $postLanguage ) {
		if ( $postLanguage ) {
			$edit_address_shipping = sanitize_title( $this->get_translated_edit_address_slug( 'shipping', $postLanguage ) );
			$edit_address_billing  = sanitize_title( $this->get_translated_edit_address_slug( 'billing', $postLanguage ) );

			if ( urldecode( $value ) === $edit_address_shipping ) {
				$value = sanitize_title( $this->get_translated_edit_address_slug( 'shipping', $switcherLanguage ) );
			} elseif ( urldecode( $value ) === $edit_address_billing ) {
				$value = sanitize_title( $this->get_translated_edit_address_slug( 'billing', $switcherLanguage ) );
			}
		}

		return $value;
	}

	/**
	 * @param string $url
	 * @param string $postLanguage
	 * @param array  $data
	 * @param array  $current_endpoint
	 */
	public function add_endpoint_to_current_ls_language_url( $url, $postLanguage, $data, $current_endpoint ) {
		$current_endpoint = $this->getCurrentLsEndpoint( $data['code'], $postLanguage );

		if ( $current_endpoint ) {
			$url = apply_filters( 'wpml_get_endpoint_url', $current_endpoint['endpoint'], $current_endpoint['value'], $url );
		}

		return esc_url_raw( $url );
	}

	/**
	 * @param string      $switcherLanguage
	 * @param string|bool $postLanguage
	 *
	 * @return array<string,string>
	 */
	private function getCurrentLsEndpoint( $switcherLanguage, $postLanguage ) {
		global $wp;

		$current_endpoint = [];

		foreach ( $this->translatedEndpoints as $key => $endpointTranslation ) {
			if (
				isset( $wp->query_vars[ $endpointTranslation ] )
				&& array_key_exists( $key, $this->originalEndpoints )
			) {
				$current_endpoint['key']      = $key;
				$current_endpoint['endpoint'] = $this->translateEndpoint( $key, $this->originalEndpoints[ $key ], $switcherLanguage, false );
				$current_endpoint['value']    = $this->adjustCurrentLsEndpointValue( $wp->query_vars[ $endpointTranslation ], $switcherLanguage, $postLanguage );
				break;
			}
		}

		if ( $current_endpoint ) {
			return $current_endpoint;
		}

		foreach ( $this->originalEndpoints as $key => $endpoint ) {
			if ( isset( $wp->query_vars[ $endpoint ] ) ) {
				$current_endpoint['key']      = $key;
				$current_endpoint['endpoint'] = $this->translateEndpoint( $key, $endpoint, $switcherLanguage, false );
				$current_endpoint['value']    = $this->adjustCurrentLsEndpointValue( $wp->query_vars[ $endpoint ], $switcherLanguage, $postLanguage );
				break;
			}
		}

		return $current_endpoint;
	}

}
