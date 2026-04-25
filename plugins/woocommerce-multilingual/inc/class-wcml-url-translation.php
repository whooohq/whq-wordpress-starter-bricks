<?php

use WCML\Permalinks\Strings;
use WCML\Utilities\WCTaxonomies;

class WCML_Url_Translation {
	const WC_STRING_CONTEXT = 'WooCommerce Endpoints';
	

	/** @var woocommerce_wpml */
	private $woocommerce_wpml;
	/** @var SitePress */
	private $sitepress;
	/** @var wpdb */
	private $wpdb;

	public $default_product_base;
	public $default_product_category_base;
	public $default_product_category_gettext_base;
	public $default_product_tag_base;
	public $default_product_tag_gettext_base;
	public $wc_permalinks;

	/**
	 * WCML_Url_Translation constructor.
	 *
	 * @param woocommerce_wpml $woocommerce_wpml
	 * @param SitePress        $sitepress
	 * @param wpdb             $wpdb
	 */
	public function __construct( woocommerce_wpml $woocommerce_wpml, \WPML\Core\ISitePress $sitepress, wpdb $wpdb ) {
		$this->woocommerce_wpml = $woocommerce_wpml;
		$this->sitepress        = $sitepress;
		$this->wpdb             = $wpdb;

		$this->default_product_base                  = Strings::DEFAULT_PRODUCT_BASE;
		$this->default_product_category_base         = Strings::DEFAULT_PRODUCT_CATEGORY_BASE;
		$this->default_product_tag_base              = Strings::DEFAULT_PRODUCT_TAG_BASE;
		$this->default_product_category_gettext_base = _x( Strings::DEFAULT_PRODUCT_CATEGORY_BASE, 'slug', 'woocommerce' );
		$this->default_product_tag_gettext_base      = _x( Strings::DEFAULT_PRODUCT_TAG_BASE, 'slug', 'woocommerce' );
	}

	public function set_up() {

		$this->wc_permalinks = get_option( 'woocommerce_permalinks' );

		if ( ! is_admin() ) {
			add_filter(
				'option_woocommerce_permalinks',
				[
					$this,
					'use_untranslated_default_url_bases',
				],
				1,
				1
			); // avoid using the _x translations.
			add_action(
				'init',
				[
					$this,
					'fix_post_object_rewrite_slug',
				],
				6
			); // handle the particular case of the default product base: wpmlst-540.
		}

		add_filter(
			'pre_update_option_rewrite_rules',
			[
				$this,
				'force_bases_in_strings_languages',
			],
			1,
			1
		); // high priority.
		add_filter( 'option_rewrite_rules', [ $this, 'translate_bases_in_rewrite_rules' ], 0, 1 ); // high priority
		add_filter( 'term_link', [ $this, 'translate_taxonomy_base' ], 0, 3 ); // high priority
		add_filter( 'woocommerce_taxonomy_archive_description_raw', [ $this, 'process_taxonomy_description_links' ], 10, 2 );

		add_action( 'wp_ajax_wcml_update_base_translation', [ $this, 'wcml_update_base_translation' ] );
		add_filter( 'redirect_canonical', [ $this, 'check_wc_tax_url_on_redirect' ], 10, 2 );
		add_filter( 'query_vars', [ $this, 'translate_query_var_for_product' ] );
		add_filter( 'wp_redirect', [ $this, 'encode_shop_slug' ] );
		add_action( 'switch_blog', [ $this, 'maybe_remove_query_vars_filter' ] );

		add_filter( 'post_type_link', [ $this, 'translate_product_post_type_link' ], 10, 2 );

		if ( empty( $this->woocommerce_wpml->settings['url_translation_set_up'] ) ) {

			$this->clean_up_product_and_taxonomy_bases();

			// set translate product by default
			$this->translate_product_base();

			$this->register_product_and_taxonomy_bases();

			$this->woocommerce_wpml->settings['url_translation_set_up'] = 1;
			$this->woocommerce_wpml->update_settings();
		}

		add_action( 'init', [ $this, 'add_hooks_after_init' ], PHP_INT_MAX );
	}

	/**
	 * Only after 'init' we can check if the product is set as "Display as Translated".
	 * Thanks to this, instead of checking every time the filter is called, we can check it at the registration level once.
	 */
	public function add_hooks_after_init() {
		if ( ! is_admin() ) {
			if ( $this->isProductDisplayAsTranslatedDocument() ) {
				add_filter( 'wpml_st_post_type_link_filter_language_details', [ $this, 'translate_product_slug_when_product_is_display_as_translated_document' ] );
			}
		}

		if ( $this->woocommerce_wpml->products->is_product_display_as_translated_post_type() ) {
			if ( false !== strpos( $this->get_woocommerce_product_base(), '%product_cat%' ) ) {
				add_filter( 'wc_product_post_type_link_product_cat', [ $this, 'translate_product_post_type_link_product_cat_when_display_as_translated' ], 10, 3 );
			}
		}

		add_filter( 'wpml_absolute_links_permalink_query_vars', [ $this, 'adjustQueryVarsOfShopAbsoluteLink' ], 10, 3 );
	}

	/**
	 * @param array  $permalink_query_vars
	 * @param string $query
	 * @param string $language
	 *
	 * @return array
	 */
	public function adjustQueryVarsOfShopAbsoluteLink( $permalink_query_vars, $query, $language ) {
		if ( 'post_type=product' === $query ) {
			$default_language = $this->sitepress->get_default_language();

			$pagename = 'shop';
			if ( 'en' !== $default_language && $default_language === $language ) {
				$shop_id  = apply_filters( 'wpml_object_id', wc_get_page_id( 'shop' ), 'page', false, $language );
				$pagename = get_page_uri( $shop_id );
			}

			return [
				'pagename' => $pagename,
				'page'     => '',
			];
		}

		return $permalink_query_vars;
	}

	/**
	 * @param \stdClass $elementLanguageDetails
	 *
	 * @return \stdClass
	 */
	public function translate_product_slug_when_product_is_display_as_translated_document( $elementLanguageDetails ) {
		if ( $elementLanguageDetails->source_language_code ) {
			return $elementLanguageDetails;
		}

		if ( 'product' !== get_post_type( $elementLanguageDetails->element_id ) ) {
			return $elementLanguageDetails;
		}

		$elementLanguageDetails->source_language_code = $elementLanguageDetails->language_code;
		$elementLanguageDetails->language_code        = $this->sitepress->get_current_language();

		return $elementLanguageDetails;
	}

	/**
	 * This method depends on the registration of all CPTs [`register_post_type()`]
	 * which should not happen before init
	 */
	private function isProductDisplayAsTranslatedDocument(): bool {
		return in_array( 'product', \WPML\API\PostTypes::getDisplayAsTranslated(), true );
	}

	/**
	 * Refreshes the CoCommerce permalink settings from its stored option.
	 */
	public function flushWcSettings() {
		$this->wc_permalinks = get_option( 'woocommerce_permalinks' );
	}

	public function clean_up_product_and_taxonomy_bases() {

		$base = $this->get_woocommerce_product_base();

		// delete other old product bases
		$this->wpdb->query( 
			$this->wpdb->prepare(
				"DELETE FROM {$this->wpdb->prefix}icl_strings WHERE context = %s AND value != %s AND name LIKE 'URL slug:%' ",
				Strings::TRANSLATION_DOMAIN,
				trim( $base, '/' )
			)
		);

		// update name for current base
		$this->wpdb->update(
			$this->wpdb->prefix . 'icl_strings',
			[
				'context' => Strings::TRANSLATION_DOMAIN,
				'name'    => 'URL slug: product',
			],
			[
				'context' => Strings::TRANSLATION_DOMAIN,
				'name'    => sprintf( 'Url slug: %s', trim( $base, '/' ) ),
			]
		);

		$woocommerce_permalinks = maybe_unserialize( get_option( 'woocommerce_permalinks' ) );

		foreach ( (array) $woocommerce_permalinks as $base_key => $base ) {

			$base_key = trim( $base_key, '/' );

			$taxonomy = false;

			switch ( $base_key ) {
				case 'category_base':
					$taxonomy = 'product_cat';
					break;
				case 'tag_base':
					$taxonomy = 'product_tag';
					break;
				case 'attribute_base':
					$taxonomy = 'attribute';
					break;
			}

			if ( $taxonomy ) {
				$this->wpdb->query( "DELETE FROM {$this->wpdb->prefix}icl_strings WHERE context LIKE '" . sprintf( 'URL %s slugs - ', $taxonomy ) . "%'" );
			}
		}

	}

	public function fix_post_object_rewrite_slug() {
		global $wp_post_types, $wp_rewrite;

		if ( empty( $this->wc_permalinks['product_base'] ) ) {
			$wp_post_types['product']->rewrite['slug'] = 'product';
			if ( empty( $wp_rewrite->extra_permastructs['product']['struct'] ) ) {
				$wp_rewrite->extra_permastructs['product']['struct'] = '/product/%product%';
			}
		}
	}

	/**
	 * @return string
	 *
	 * @deprecated Use Strings::TRANSLATION_DOMAIN.
	 */
	public function url_strings_context() {
		return Strings::TRANSLATION_DOMAIN;
	}

	/**
	 * @param string $type
	 * @param string $value
	 *
	 * @return string
	 *
	 * @deprecated Use Strings::getStringName().
	 */
	public function url_string_name( $type, $value = '' ) {
		return Strings::getStringName( $type, $value );
	}

	public function translate_product_base() {
		if ( ! defined( 'WOOCOMMERCE_VERSION' ) || ( ! isset( $GLOBALS['ICL_Pro_Translation'] ) ) ) {
			return;
		}

		$slug = $this->get_woocommerce_product_base();
		do_action( 'wpml_activate_slug_translation', 'product', $slug );
	}

	public function get_woocommerce_product_base() {

		if ( isset( $this->wc_permalinks['product_base'] ) && ! empty( $this->wc_permalinks['product_base'] ) ) {
			return trim( $this->wc_permalinks['product_base'], '/' );
		} elseif ( get_option( 'woocommerce_product_slug' ) != false ) {
			return trim( get_option( 'woocommerce_product_slug' ), '/' );
		} else {
			return $this->default_product_base; // the default WooCommerce value. Before permalinks options are saved
		}

	}

	/**
	 * Registers some permalink bases for translation.
	 *
	 * This also:
	 * - Maybe sets the string languages for those bases, when saving them from the permalinks settings page.
	 *   See $_POST['{item}_base_language'].
	 *   See \WCML\Permalinks\Settings\TranslationControls::registerStringsOnSave().
	 * - Maybe sets missing translations for default bases, if provided by WooCommerce itself.
	 *
	 * This method is called in multiple places, and also used to be a callback for the pre_update_option_woocommerce_permalinks filter.
	 * For backward compatibility, it still takes two optional parameters and returns the first, untouched one.
	 *
	 * @see https://onthegosystems.myjetbrains.com/youtrack/issue/wcml-4739
	 *
	 * @param array|false $wcPermalinks The WooCommerce permalink settings to use, defaults to the stored ones.
	 * @param array|false $deprecated   Not used, never used.
	 *
	 * @return array|false
	 */
	public function register_product_and_taxonomy_bases( $wcPermalinks = false, $deprecated = false ) {

		if ( empty( $wcPermalinks ) ) {
			$permalink_options = $this->wc_permalinks;
		} else {
			$permalink_options = $wcPermalinks;
		}

		// products
		$product_base = ! empty( $permalink_options['product_base'] ) ? trim( $permalink_options['product_base'], '/' ) : $this->default_product_base;
		$name         = Strings::getStringName( 'product' );

		$string_language = $this->woocommerce_wpml->strings->get_string_language( $product_base, Strings::TRANSLATION_DOMAIN, $name );
		if ( is_null( $string_language ) ) {
			$string_language = '';
		}
		do_action( 'wpml_register_single_string', Strings::TRANSLATION_DOMAIN, $name, $product_base, false, $string_language );

		if ( isset( $_POST['product_base_language'] ) ) {
			$this->woocommerce_wpml->strings->set_string_language( $product_base, Strings::TRANSLATION_DOMAIN, $name, $_POST['product_base_language'] );
		}

		if ( $product_base == $this->default_product_base ) {
			$this->add_default_slug_translations( $product_base, $name );
		}

		// categories
		$category_base = ! empty( $permalink_options['category_base'] ) ? $permalink_options['category_base'] : $this->default_product_category_base;
		$name          = Strings::getStringName( 'product_cat' );

		$string_language = $this->woocommerce_wpml->strings->get_string_language( $category_base, Strings::TRANSLATION_DOMAIN, $name );
		if ( is_null( $string_language ) ) {
			$string_language = '';
		}
		do_action( 'wpml_register_single_string', Strings::TRANSLATION_DOMAIN, $name, $category_base, false, $string_language );

		if ( isset( $_POST['category_base_language'] ) ) {
			$this->woocommerce_wpml->strings->set_string_language( $category_base, Strings::TRANSLATION_DOMAIN, $name, $_POST['category_base_language'] );
		}

		if ( $category_base == $this->default_product_category_base ) {
			$this->add_default_slug_translations( $category_base, $name );
		}

		// tags
		$tag_base = ! empty( $permalink_options['tag_base'] ) ? $permalink_options['tag_base'] : $this->default_product_tag_base;
		$name     = Strings::getStringName( 'product_tag' );

		$string_language = $this->woocommerce_wpml->strings->get_string_language( $tag_base, Strings::TRANSLATION_DOMAIN, $name );
		if ( is_null( $string_language ) ) {
			$string_language = '';
		}
		do_action( 'wpml_register_single_string', Strings::TRANSLATION_DOMAIN, $name, $tag_base, false, $string_language );

		if ( isset( $_POST['tag_base_language'] ) ) {
			$this->woocommerce_wpml->strings->set_string_language( $tag_base, Strings::TRANSLATION_DOMAIN, $name, $_POST['tag_base_language'] );
		}

		if ( $tag_base == $this->default_product_tag_base ) {
			$this->add_default_slug_translations( $tag_base, $name );
		}

		if ( isset( $permalink_options['attribute_base'] ) && $permalink_options['attribute_base'] ) {
			$attr_base = trim( $permalink_options['attribute_base'], '/' );
			$attr_string_name = Strings::getStringName( 'attribute' );

			$string_language = $this->woocommerce_wpml->strings->get_string_language( $attr_base, Strings::TRANSLATION_DOMAIN, $attr_string_name );
			if ( is_null( $string_language ) ) {
				$string_language = '';
			}
			do_action( 'wpml_register_single_string', Strings::TRANSLATION_DOMAIN, $attr_string_name, $attr_base, false, $string_language );

			if ( isset( $_POST['attribute_base_language'] ) ) {
				$this->woocommerce_wpml->strings->set_string_language( $attr_base, Strings::TRANSLATION_DOMAIN, $attr_string_name, $_POST['attribute_base_language'] );
			}
		}

		return $wcPermalinks;
	}

	/**
	 * @param mixed $permalinks
	 *
	 * @return mixed
	 */
	public function use_untranslated_default_url_bases( $permalinks ) {

		// exception (index.php in WP permalink structure) #wcml-1939
		if ( preg_match( '#^/?index\.php/#', get_option( 'permalink_structure' ) ) ) {
			return $permalinks;
		}

		if ( empty( $permalinks['product_base'] ) ) {
			$permalinks['product_base'] = $this->default_product_base;
		}
		if ( empty( $permalinks['category_base'] ) ) {
			$permalinks['category_base'] = $this->default_product_category_base;
		}
		if ( empty( $permalinks['tag_base'] ) ) {
			$permalinks['tag_base'] = $this->default_product_tag_base;
		}

		return $permalinks;
	}

	public function add_default_slug_translations( $slug, $name ) {

		$string_id       = icl_get_string_id( $slug, Strings::TRANSLATION_DOMAIN, $name );
		$string_language = $this->woocommerce_wpml->strings->get_string_language( $slug, Strings::TRANSLATION_DOMAIN, $name );

		// will use a filter in the future wpmlst-529
		$string_object               = new WPML_ST_String( $string_id, $this->wpdb );
		$string_translation_statuses = $string_object->get_translation_statuses();

		foreach ( $string_translation_statuses as $s ) {
			$string_translations[ $s->language ] = $s->status;
		}

		$languages = $this->sitepress->get_active_languages();

		foreach ( $languages as $language => $language_info ) {

			if ( $language != $string_language ) {

				// check if there's an existing translation
				if ( ! isset( $string_translations[ $language ] ) ) {

					$slug_translation = $this->woocommerce_wpml->strings->get_translation_from_woocommerce_mo_file( $slug, $language, false );

					if ( $slug_translation ) {
						// add string translation
						icl_add_string_translation( $string_id, $language, $slug_translation, ICL_STRING_TRANSLATION_COMPLETE );
					}
				}
			}
		}

	}

	public function force_bases_in_strings_languages( $value ) {

		if ( $value && $this->sitepress->get_current_language() !== 'en' ) {

			remove_filter(
				'gettext_with_context',
				[
					$this->woocommerce_wpml->strings,
					'category_base_in_strings_language',
				],
				99
			);
			$taxonomies = [
				'product_cat' => [
					'base'            => 'category_base',
					'base_translated' => apply_filters(
						'wpml_translate_single_string',
						'product-category',
						Strings::TRANSLATION_DOMAIN,
						Strings::getStringName( 'product_cat' )
					),
					'default'         => $this->default_product_category_base,
				],
				'product_tag' => [
					'base'            => 'tag_base',
					'base_translated' => apply_filters(
						'wpml_translate_single_string',
						'product-tag',
						Strings::TRANSLATION_DOMAIN,
						Strings::getStringName( 'product_tag' )
					),
					'default'         => $this->default_product_tag_base,
				],
			];
			add_filter(
				'gettext_with_context',
				[
					$this->woocommerce_wpml->strings,
					'category_base_in_strings_language',
				],
				99,
				3
			);
			foreach ( $taxonomies as $taxonomy_details ) {

				if ( empty( $this->wc_permalinks[ $taxonomy_details['base'] ] ) && $value ) {

					$new_value = [];
					foreach ( $value as $k => $v ) {
						$k               = preg_replace( '#' . $taxonomy_details['base_translated'] . '/#', $taxonomy_details['default'] . '/', $k );
						$new_value[ $k ] = $v;
					}
					$value = $new_value;
					unset( $new_value );

				}
			}
		}

		return $value;

	}

	/**
	 * @param array $value
	 *
	 * @return array
	 */
	public function translate_bases_in_rewrite_rules( $value ) {

		if ( ! empty( $value ) ) {
			$value = $this->translate_wc_default_taxonomies_bases_in_rewrite_rules( $value );
			$value = $this->translate_attributes_bases_in_rewrite_rules( $value );
			$value = $this->translate_shop_page_base_in_rewrite_rules( $value );
		}

		return $value;
	}

	/**
	 * @param array $value
	 *
	 * @return array
	 */
	public function translate_wc_default_taxonomies_bases_in_rewrite_rules( $value ) {
		$taxonomies = [ WCTaxonomies::TAXONOMY_PRODUCT_CATEGORY, WCTaxonomies::TAXONOMY_PRODUCT_TAG ];

		foreach ( $taxonomies as $taxonomy ) {
			$slug_details = $this->get_translated_tax_slug( $taxonomy );

			$string_language = $this->woocommerce_wpml->strings->get_string_language( $slug_details['slug'], Strings::TRANSLATION_DOMAIN, Strings::getStringName( $taxonomy ) );
			if ( $this->sitepress->get_current_language() == $string_language ) {
				continue;
			}

			if ( $slug_details ) {

				$slug_match             = addslashes( ltrim( $slug_details['slug'], '/' ) );
				$slug_translation_match = ltrim( $slug_details['translated_slug'], '/' );

				$buff_value = [];

				foreach ( (array) $value as $k => $v ) {

					if ( $slug_details['slug'] != $slug_details['translated_slug'] && preg_match( '#(^|^/)' . $slug_match . '/#', $k ) ) {
						$k = preg_replace( '#(^|^/)' . $slug_match . '/#', '$1' . $slug_translation_match . '/', $k );
					}

					$buff_value[ $k ] = $v;
				}
				$value = $buff_value;
				unset( $buff_value );
			}
		}

		return $value;
	}

	/**
	 * @param array $value
	 *
	 * @return array
	 */
	public function translate_attributes_bases_in_rewrite_rules( $value ) {

		// handle attributes
		$wc_taxonomies           = wc_get_attribute_taxonomies();
		$wc_taxonomies_wc_format = [];
		foreach ( $wc_taxonomies as $v ) {
			$wc_taxonomies_wc_format[] = WCTaxonomies::TAXONOMY_PREFIX_ATTRIBUTE . $v->attribute_name;
		}

		foreach ( $wc_taxonomies_wc_format as $taxonomy ) {
			$taxonomy_obj = get_taxonomy( $taxonomy );

			if ( isset( $taxonomy_obj->rewrite['slug'] ) ) {
				$exp            = explode( '/', trim( $taxonomy_obj->rewrite['slug'], '/' ) );
				$slug           = join( '/', array_slice( $exp, 0, count( $exp ) - 1 ) );
				$attribute_slug = preg_replace( "#^$slug/#", '', $taxonomy_obj->rewrite['slug'] );

				$current_language        = $this->sitepress->get_current_language();
				$slug_language           = $this->woocommerce_wpml->strings->get_string_language( $slug, Strings::TRANSLATION_DOMAIN, Strings::getStringName( 'attribute' ) );
				$attribute_slug_language = $this->woocommerce_wpml->strings->get_string_language( $attribute_slug, Strings::TRANSLATION_DOMAIN, Strings::getStringName( 'attribute_slug', $attribute_slug ) );

				if ( $current_language !== $attribute_slug_language || $current_language !== $slug_language ) {

					$slug_translation = apply_filters( 'wpml_translate_single_string', $slug, Strings::TRANSLATION_DOMAIN, Strings::getStringName( 'attribute' ) );

					$attribute_slug_translation = apply_filters( 'wpml_translate_single_string', $attribute_slug, Strings::TRANSLATION_DOMAIN, Strings::getStringName( 'attribute_slug', $attribute_slug ) );

					if ( $slug && $slug_translation && $slug !== $slug_translation ) {

						$slug_match             = addslashes( ltrim( $slug, '/' ) );
						$slug_translation_match = ltrim( $slug_translation, '/' );

						$pattern             = '#^' . $slug_match . '/(.*)#';
						$replacement_pattern = '#^' . $slug_match . '/(' . $attribute_slug . ')/(.*)#';
						$replacement         = $slug_translation_match . '/' . $attribute_slug_translation . '/$2';

						$value = $this->replace_bases_in_rewrite_rules( $value, $pattern, $replacement_pattern, $replacement );

					} elseif ( $attribute_slug_translation && $attribute_slug !== $attribute_slug_translation ) {

						$slug_match  = addslashes( ltrim( $attribute_slug, '/' ) );
						$pattern     = '#(^|\/)' . $slug_match . '/(.*)#';
						$replacement = '$1' . $attribute_slug_translation . '/$2';

						$value = $this->replace_bases_in_rewrite_rules( $value, $pattern, $pattern, $replacement );

					}
				}
			}
		}

		return $value;

	}

	/**
	 * @param array $value
	 *
	 * @return array
	 */
	public function translate_shop_page_base_in_rewrite_rules( $value ) {
		// filter shop page rewrite slug
		$current_shop_id = wc_get_page_id( 'shop' );
		$default_shop_id = apply_filters( 'wpml_object_id', $current_shop_id, 'page', true, $this->sitepress->get_default_language() );

		if ( is_null( get_post( $current_shop_id ) ) || is_null( get_post( $default_shop_id ) ) ) {
			return $value;
		}

		$current_slug = urldecode( get_page_uri( $current_shop_id ) );
		$default_slug = urldecode( get_page_uri( $default_shop_id ) );

		if ( $current_slug != $default_slug ) {
			$buff_value = [];
			foreach ( (array) $value as $k => $v ) {

				if ( preg_match( '#^' . $default_slug . '/\?\$$#', $k ) ||
					 preg_match( '#^' . $default_slug . '/\(?feed#', $k ) ||
					 preg_match( '#^' . $default_slug . '/page#', $k )
				) {

					$k = preg_replace( '#^' . $default_slug . '/#', $current_slug . '/', $k );
				}

				$buff_value[ $k ] = $v;
			}

			$value = $buff_value;
			unset( $buff_value );
		}

		return $value;
	}

	/**
	 * @param array  $value
	 * @param string $pattern
	 * @param string $replacement_pattern
	 * @param string $replacement
	 *
	 * @return array
	 */
	public function replace_bases_in_rewrite_rules( $value, $pattern, $replacement_pattern, $replacement ) {

		$buff_value = [];
		foreach ( (array) $value as $k => $v ) {
			if ( preg_match( $pattern, $k ) ) {
				$k = preg_replace( $replacement_pattern, $replacement, $k );
			}
			$buff_value[ $k ] = $v;
		}

		$value = $buff_value;
		unset( $buff_value );

		return $value;
	}

	public function translate_taxonomy_base( $termlink, $term, $taxonomy ) {
		global $wp_rewrite, $wpml_term_translations;
		static $no_recursion_flag;

		// handles product categories, product tags and attributes
		$wc_taxonomies = wc_get_attribute_taxonomies();
		foreach ( $wc_taxonomies as $v ) {
			$wc_taxonomies_wc_format[] = WCTaxonomies::TAXONOMY_PREFIX_ATTRIBUTE . $v->attribute_name;
		}

		if ( ( WCTaxonomies::isProductAttribute( $taxonomy ) || WCTaxonomies::isProductTag( $taxonomy ) || ( ! empty( $wc_taxonomies_wc_format ) && in_array( $taxonomy, $wc_taxonomies_wc_format ) ) ) && ! $no_recursion_flag ) {

			$cache_key = 'termlink#' . $taxonomy . '#' . $term->term_id;
			if ( $link = wp_cache_get( $cache_key, 'terms' ) ) {
				$termlink = $link;
			} else {

				$no_recursion_flag = false;

				if ( ! is_null( $wpml_term_translations ) ) {
					$term_language = $term->term_id ? $wpml_term_translations->get_element_lang_code( $term->term_taxonomy_id ) : false;
				} else {
					$term_language = $term->term_id ? $this->sitepress->get_language_for_element( $term->term_taxonomy_id, 'tax_' . $taxonomy ) : false;
				}

				if ( $term_language ) {

					$slug_details = $this->get_translated_tax_slug( $taxonomy, $term_language );

					$base            = $slug_details['slug'];
					$base_translated = $slug_details['translated_slug'];

					if ( isset( $wp_rewrite->extra_permastructs[ $taxonomy ] ) ) {

						$buff = $wp_rewrite->extra_permastructs[ $taxonomy ]['struct'];

						if ( $base_translated !== $base ) {
							// translate the attribute base
							$wp_rewrite->extra_permastructs[ $taxonomy ]['struct'] = preg_replace( '#^' . $base . '/(.*)#', $base_translated . '/$1', $wp_rewrite->extra_permastructs[ $taxonomy ]['struct'] );
						}

						// translate the attribute slug
						$attribute_slug             = preg_replace( '#^' . $base . '/([^/]+)/.+$#', '$1', $wp_rewrite->extra_permastructs[ $taxonomy ]['struct'] );
						$attribute_slug_default     = preg_replace( '#^pa_#', '', $taxonomy );
						$attribute_slug_translation = apply_filters(
							'wpml_translate_single_string',
							$attribute_slug,
							Strings::TRANSLATION_DOMAIN,
							Strings::getStringName( 'attribute_slug', $attribute_slug_default ),
							$term_language
						);

						if ( $attribute_slug_translation != $attribute_slug ) {

							if ( $base ) {
								$wp_rewrite->extra_permastructs[ $taxonomy ]['struct'] = preg_replace(
									'#^' . $base_translated . '/([^/]+)/(.+)$#',
									$base_translated . '/' . $attribute_slug_translation . '/$2',
									$wp_rewrite->extra_permastructs[ $taxonomy ]['struct']
								);
							} else {
								$wp_rewrite->extra_permastructs[ $taxonomy ]['struct'] = preg_replace(
									'#' . $attribute_slug_default . '/(.+)$#',
									$attribute_slug_translation . '/$1',
									$wp_rewrite->extra_permastructs[ $taxonomy ]['struct']
								);
							}
						}

						$no_recursion_flag = true;
						$termlink          = get_term_link( $term, $taxonomy );

						$wp_rewrite->extra_permastructs[ $taxonomy ]['struct'] = $buff;

					}
				}

				$no_recursion_flag = false;

				wp_cache_add( $cache_key, $termlink, 'terms', 0 );
			}
		}

		return $termlink;
	}

	/**
	 * Currently, html links placed in the product category description are not always translated
	 *
	 * @param string  $term_description Raw description text.
	 * @param WP_Term $term Term object for this taxonomy archive.
	 *
	 * @return string
	 */
	public function process_taxonomy_description_links( $term_description, $term ) {
		if ( ! is_string( $term_description ) ) {
			return $term_description;
		}

		return (string) apply_filters( 'wpml_translate_link_targets', $term_description );
	}

	public function get_translated_tax_slug( $taxonomy, $language = false ) {

		switch ( $taxonomy ) {
			case 'product_tag':
				if ( ! empty( $this->wc_permalinks['tag_base'] ) ) {
					$slug = trim( $this->wc_permalinks['tag_base'], '/' );
				} else {
					$slug = 'product-tag';
				}

				$string_language = $this->woocommerce_wpml->strings->get_string_language( $slug, Strings::TRANSLATION_DOMAIN, Strings::getStringName( $taxonomy ) );

				break;

			case 'product_cat':
				if ( ! empty( $this->wc_permalinks['category_base'] ) ) {
					$slug = trim( $this->wc_permalinks['category_base'], '/' );
				} else {
					$slug = 'product-category';
				}

				$string_language = $this->woocommerce_wpml->strings->get_string_language( $slug, Strings::TRANSLATION_DOMAIN, Strings::getStringName( $taxonomy ) );

				break;

			default:
				$slug = trim( $this->wc_permalinks['attribute_base'], '/' );

				$string_language = $this->woocommerce_wpml->strings->get_string_language( $slug, Strings::TRANSLATION_DOMAIN, Strings::getStringName( 'attribute' ) );

				$taxonomy = 'attribute';

				break;
		}

		if ( ! $language ) {
			$language = $this->sitepress->get_current_language();
		}

		if ( $slug && $language !== 'all' && $language !== $string_language ) {

			$slug_translation = apply_filters( 'wpml_translate_single_string', $slug, Strings::TRANSLATION_DOMAIN, Strings::getStringName( $taxonomy ), $language, false );

			return [
				'slug'            => $slug,
				'translated_slug' => $slug_translation,
			];
		}

		return [
			'slug'            => $slug,
			'translated_slug' => $slug,
		];

	}

	public function get_base_translation( $base, $language ) {
		$original_base = $base;

		// case of attribute slugs
		if ( strpos( $base, 'attribute_slug-' ) === 0 ) {
			$base = 'attribute_slug';
		}

		switch ( $base ) {
			case 'product':
				$slug           = $this->get_woocommerce_product_base();
				$return['name'] = __( 'Product Base', 'woocommerce-multilingual' );
				break;

			case 'product_tag':
				$slug           = ! empty( $this->wc_permalinks['tag_base'] ) ? trim( $this->wc_permalinks['tag_base'], '/' ) : 'product-tag';
				$return['name'] = __( 'Product Tag Base', 'woocommerce-multilingual' );
				break;

			case 'product_cat':
				$slug           = ! empty( $this->wc_permalinks['category_base'] ) ? trim( $this->wc_permalinks['category_base'], '/' ) : 'product-category';
				$return['name'] = __( 'Product Category Base', 'woocommerce-multilingual' );
				break;

			case 'attribute':
				$slug           = trim( $this->wc_permalinks['attribute_base'], '/' );
				$return['name'] = __( 'Product Attribute Base', 'woocommerce-multilingual' );
				break;

			case 'attribute_slug':
				$slug           = preg_replace( '#^attribute_slug-#', '', $original_base );
				$return['name'] = __( 'Attribute Slug', 'woocommerce-multilingual' );
				$string_id      = icl_get_string_id( $slug, Strings::TRANSLATION_DOMAIN, Strings::getStringName( $base, $slug ) );
				break;

			default:
				$endpoints = WC()->query->get_query_vars();
				$slug      = isset( $endpoints[ $base ] ) ? $endpoints[ $base ] : $base;

				/* translators: %s is a slug */
				$return['name'] = sprintf( __( 'Endpoint: %s', 'woocommerce-multilingual' ), $base );
				$string_id      = icl_get_string_id( $slug, $this->get_endpoint_string_context(), $base );
				break;
		}

		$return['original_value'] = $slug;
		if ( ! isset( $string_id ) ) {
			$string_id = icl_get_string_id( $slug, Strings::TRANSLATION_DOMAIN, Strings::getStringName( $base ) );
		}
		$base_translations = icl_get_string_translations_by_id( $string_id );

		$return['translated_base'] = '';
		if ( isset( $base_translations[ $language ] ) ) {
			if ( $base_translations[ $language ]['status'] == ICL_TM_COMPLETE ) {
				$return['translated_base'] = $base_translations[ $language ]['value'];
			} elseif ( $base_translations[ $language ]['status'] == ICL_TM_NEEDS_UPDATE ) {
				$return['translated_base'] = $base_translations[ $language ]['value'];
				$return['needs_update ']   = true;
			}
		}

		return $return;

	}

	private function get_endpoint_string_context() {
		return self::get_endpoints_string_context();
	}

	public static function get_endpoints_string_context(): string {
		return class_exists( WPML_Endpoints_Support::class ) ? WPML_Endpoints_Support::STRING_CONTEXT : self::WC_STRING_CONTEXT;
	}

	/**
	 * @param string $base
	 *
	 * @return string
	 */
	public function get_source_slug_language( $base ) {

		if ( $base == 'shop' ) {
			$source_language = $this->sitepress->get_language_for_element( wc_get_page_id( 'shop' ), 'post_page' );
		} elseif ( in_array( $base, [ 'product', WCTaxonomies::TAXONOMY_PRODUCT_CATEGORY, WCTaxonomies::TAXONOMY_PRODUCT_TAG, 'attribute' ] ) ) {
			$source_language = $this->woocommerce_wpml->strings->get_string_language( $base, Strings::TRANSLATION_DOMAIN, Strings::getStringName( $base ) );
		} elseif ( strpos( $base, 'attribute_slug-' ) === 0 ) {
			$slug            = preg_replace( '#^attribute_slug-#', '', $base );
			$source_language = $this->woocommerce_wpml->strings->get_string_language( $base, Strings::TRANSLATION_DOMAIN, Strings::getStringName( 'attribute_slug', $slug ) );
		} else {
			$source_language = $this->woocommerce_wpml->strings->get_string_language( $base, $this->get_endpoint_string_context(), $base );
		}

		return $source_language;
	}

	public function wcml_update_base_translation() {

		$nonce = filter_input( INPUT_POST, 'wcml_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wcml_update_base_translation' ) ) {
			die( 'Invalid nonce' );
		}

		$original_base       = $_POST['base'];
		$original_base_value = $_POST['base_value'];
		$base_translation    = wc_sanitize_permalink( trim( $_POST['base_translation'], '/' ) );
		$language            = $_POST['language'];

		if ( $original_base == 'shop' ) {
			$original_shop_id   = wc_get_page_id( 'shop' );
			$translated_shop_id = apply_filters( 'wpml_object_id', $original_shop_id, 'page', false, $language );

			if ( ! is_null( $translated_shop_id ) ) {

				$trnsl_shop_obj = get_post( $translated_shop_id );
				$new_slug       = wp_unique_post_slug( $base_translation, $translated_shop_id, $trnsl_shop_obj->post_status, $trnsl_shop_obj->post_type, $trnsl_shop_obj->post_parent );
				$this->wpdb->update( $this->wpdb->posts, [ 'post_name' => $new_slug ], [ 'ID' => $translated_shop_id ] );

			}
		} else {
			if ( in_array( $original_base, [ 'product', WCTaxonomies::TAXONOMY_PRODUCT_CATEGORY, WCTaxonomies::TAXONOMY_PRODUCT_TAG, 'attribute' ] ) ) {
				$string_id = icl_get_string_id( $original_base_value, Strings::TRANSLATION_DOMAIN, Strings::getStringName( $original_base ) );
			} elseif ( strpos( $original_base, 'attribute_slug-' ) === 0 ) {
				$slug = preg_replace( '#^attribute_slug-#', '', $original_base );
				do_action( 'wpml_register_single_string', Strings::TRANSLATION_DOMAIN, Strings::getStringName( 'attribute_slug', $slug ), $slug );
				$string_id = icl_get_string_id( $original_base_value, Strings::TRANSLATION_DOMAIN, Strings::getStringName( 'attribute_slug', $slug ) );
			} else {
				$string_id = icl_get_string_id( $original_base_value, $this->get_endpoint_string_context(), $original_base );
				if ( ! $string_id && function_exists( 'icl_register_string' ) ) {
					$string_id = icl_register_string( $this->get_endpoint_string_context(), $original_base, $original_base_value );
				}
			}

			icl_add_string_translation( $string_id, $language, $base_translation, ICL_STRING_TRANSLATION_COMPLETE );
		}

		if ( in_array( $original_base, [ WCTaxonomies::TAXONOMY_PRODUCT_CATEGORY, WCTaxonomies::TAXONOMY_PRODUCT_TAG ], true ) ) {
			// Notify WPML that the taxonomy slug was translated.
			do_action( 'wpml_activate_slug_translation', $original_base, $original_base_value, WPML_Slug_Translation_Factory::TAX );
		}

		$edit_base = new WCML_Store_URLs_Edit_Base_UI( $original_base, $language, $this->woocommerce_wpml, $this->sitepress );
		$html      = $edit_base->get_view();

		echo json_encode( $html );
		die();

	}

	// return correct redirect URL for WC standard taxonomies when pretty permalink uses with lang as parameter in WPML
	public function check_wc_tax_url_on_redirect( $redirect_url, $requested_url ) {
		global $wp_query;

		if ( is_tax() ) {
			$original = @parse_url( $requested_url );
			if ( isset( $original['query'] ) ) {
				parse_str( $original['query'], $query_args );
				if ( ( isset( $query_args['product_cat'] ) || isset( $query_args['product_tag'] ) ) && isset( $query_args['lang'] ) ) {
					$obj     = $wp_query->get_queried_object();
					$tax_url = get_term_link( (int) $obj->term_id, $obj->taxonomy );

					return $tax_url;
				}
			}
		}

		return $redirect_url;
	}

	public function translate_query_var_for_product( $public_query_vars ) {

		$product_permalink = $this->woocommerce_wpml->strings->product_permalink_slug();
		$string_language   = $this->woocommerce_wpml->strings->get_string_language( $product_permalink, Strings::TRANSLATION_DOMAIN, Strings::getStringName( 'product' ) );

		if ( $this->sitepress->get_current_language() != $string_language ) {
			$translated_slug = $this->get_translated_product_base_by_lang( false, $product_permalink );

			if ( isset( $_GET[ $translated_slug ] ) ) {
				$buff = $_GET[ $translated_slug ];
				unset( $_GET[ $translated_slug ] );
				$_GET[ $product_permalink ] = $buff;
			}
		}

		return $public_query_vars;
	}

	public function maybe_remove_query_vars_filter() {
		if ( ! is_plugin_active( basename( $this->sitepress->get_wp_api()->constant( 'WPML_ST_PATH' ) ) . '/plugin.php' ) ) {
			remove_filter( 'query_vars', [ $this, 'translate_query_var_for_product' ] );
		}
	}

	public function get_translated_product_base_by_lang( $language = false, $product_slug = false ) {

		if ( ! $language ) {
			$language = $this->sitepress->get_current_language();
		}

		if ( ! $product_slug ) {
			$product_slug = $this->woocommerce_wpml->strings->product_permalink_slug();
		}

		$translated_slug = apply_filters( 'wpml_get_translated_slug', $product_slug, 'product', $language );

		return $translated_slug;
	}

	public function encode_shop_slug( $location ) {
		if ( get_post_type( get_query_var( 'p' ) ) == 'product' ) {
			$language  = $this->sitepress->get_language_for_element( get_query_var( 'p' ), 'post_product' );
			$base_slug = $this->get_translated_product_base_by_lang( $language );

			$location = str_replace( $base_slug, implode( '/', array_map( 'rawurlencode', explode( '/', $base_slug ) ) ), $location );
		}

		return $location;
	}

	public function translate_product_post_type_link( $permalink, $post ) {

		// Abort if post is not a product or permalink don't have 'uncategorized' flag
		if ( 'product' !== $post->post_type || false === strpos( $permalink, '/uncategorized/' ) ) {
			return $permalink;
		}

		$permalinks = wc_get_permalink_structure();

		// Make sure the product permalink have %product_cat% flag.
		if ( preg_match( '`/(.+)(/%product_cat%)`', $permalinks['product_rewrite_slug'] ) ) {
			$find             = 'uncategorized';
			$element_language = $this->sitepress->get_language_for_element( $post->ID, 'post_product' );
			$replace          = $this->woocommerce_wpml->strings->get_translation_from_woocommerce_mo_file( 'slug' . chr( 4 ) . $find, $element_language, false );

			if ( ! is_null( $replace ) ) {
				$permalink = str_replace( $find, $replace, $permalink );
			}
		}

		return $permalink;
	}

	public function translate_product_post_type_link_product_cat_when_display_as_translated( $primary_term, $terms, $post ) {
		if ( 'product' === $post->post_type && WCTaxonomies::isProductCategory( $primary_term->taxonomy ) ) {
			$translated_term_id = apply_filters( 'wpml_object_id', $primary_term->term_id, $primary_term->taxonomy, true );

			return get_term( $translated_term_id, $primary_term->taxonomy );
		}

		return $primary_term;
	}

}
