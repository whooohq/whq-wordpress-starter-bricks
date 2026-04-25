<?php

/**
 * Class WCML_Tab_Manager
 */
class WCML_Tab_Manager implements \IWPML_Action {

	const POST_TYPE    = 'wc_product_tab';
	const ELEMENT_TYPE = 'post_wc_product_tab';

	const TAB_FIELD_PREFIX           = 'product_tabs:';
	const TAB_FIELD_CORE_INTERFIX    = 'core_tab_';
	const TAB_FIELD_PRODUCT_INTERFIX = 'product_tab:';

	/**
	 * @var WPML_Element_Translation_Package
	 */
	private $tp;

	/**
	 * @var SitePress
	 */
	private $sitepress;

	/**
	 * @var woocommerce_wpml
	 */
	private $woocommerce_wpml;

	/**
	 * @var wpdb
	 */
	private $wpdb;

	/**
	 * @param SitePress                        $sitepress
	 * @param woocommerce_wpml                 $woocommerce_wpml
	 * @param wpdb                             $wpdb
	 * @param WPML_Element_Translation_Package $tp
	 */
	public function __construct( SitePress $sitepress, woocommerce_wpml $woocommerce_wpml, wpdb $wpdb, WPML_Element_Translation_Package $tp ) {
		$this->sitepress        = $sitepress;
		$this->woocommerce_wpml = $woocommerce_wpml;
		$this->wpdb             = $wpdb;
		$this->tp               = $tp;
	}

	public function add_hooks() {
		add_action( 'wcml_update_extra_fields', [ $this, 'sync_tabs' ], 10, 4 );
		add_action( 'wcml_gui_additional_box_html', [ $this, 'custom_box_html' ], 10, 3 );
		add_filter( 'wcml_gui_additional_box_data', [ $this, 'custom_box_html_data' ], 10, 4 );
		add_filter( 'wpml_duplicate_custom_fields_exceptions', [ $this, 'duplicate_custom_fields_exceptions' ] );
		add_action( 'wcml_after_duplicate_product', [ $this, 'duplicate_product_tabs' ], 10, 2 );

		add_filter( 'wc_tab_manager_tab_id', [ $this, 'wc_tab_manager_tab_id' ], 10, 1 );
		add_filter( 'option_wpml_config_files_arr', [ $this, 'make__product_tabs_not_translatable_by_default' ], 0 );

		add_action( 'wpml_translation_job_saved', [ $this, 'save_custom_tabs_translation' ], 10, 3 );
		add_filter( 'wpml_tm_post_md5_content', [ $this, 'adjust_tab_manager_product_signature' ], 10, 2 );

		if ( is_admin() ) {

			add_action( 'save_post', [ $this, 'force_set_language_information_on_product_tabs' ], 10, 2 );
			add_action( 'save_post', [ $this, 'sync_product_tabs' ], 10, 2 );

			add_filter( 'wpml_tm_translation_job_data', [ $this, 'append_custom_tabs_to_translation_package' ], 10, 2 );

			add_filter( 'wcml_do_not_display_custom_fields_for_product', [ $this, 'replace_tm_editor_custom_fields_with_own_sections' ] );

			add_filter( 'wpml_duplicate_custom_fields_exceptions', [ $this, 'duplicate_categories_exception' ] );
			add_action( 'wpml_after_copy_custom_field', [ $this, 'translate_categories' ], 10, 3 );

			add_action( 'woocommerce_product_data_panels', [ $this, 'show_pointer_info' ] );
		} else {
			add_filter( 'option_wc_tab_manager_default_layout', [ $this, 'filter_default_layout' ] );
		}
	}

	/**
	 * @param object $wpml_config_array
	 *
	 * @return object
	 */
	public function make__product_tabs_not_translatable_by_default( $wpml_config_array ) {

		if ( isset( $wpml_config_array->plugins['WooCommerce Tab Manager'] ) ) {
			$wpml_config_array->plugins['WooCommerce Tab Manager'] =
				str_replace(
					'<custom-field action="translate">_product_tabs</custom-field>',
					'<custom-field action="nothing">_product_tabs</custom-field>',
					$wpml_config_array->plugins['WooCommerce Tab Manager']
				);
		}

		return $wpml_config_array;
	}

	/**
	 * @param int    $original_product_id
	 * @param int    $trnsl_product_id
	 * @param array  $data
	 * @param string $lang
	 */
	public function sync_tabs( $original_product_id, $trnsl_product_id, $data, $lang ) {
		// Check if "duplicate" product.
		// phpcs:disable WordPress.VIP.SuperGlobalInputUsage.AccessDetected
		if ( ( isset( $_POST['icl_ajx_action'] ) && ( 'make_duplicates' === sanitize_text_field( $_POST['icl_ajx_action'] ) ) ) || ( get_post_meta( $trnsl_product_id, '_icl_lang_duplicate_of', true ) ) ) {
			$this->duplicate_tabs( $original_product_id, $trnsl_product_id, $lang );
		}

		$orig_prod_tabs = $this->get_product_tabs( $original_product_id );

		if ( $orig_prod_tabs ) {
			$trnsl_product_tabs = [];
			$i                  = 0;
			foreach ( $orig_prod_tabs as $key => $orig_prod_tab ) {
				switch ( $orig_prod_tab['type'] ) {
					case 'core':
						$default_language           = $this->woocommerce_wpml->products->get_original_product_language( $original_product_id );
						$current_language           = $this->sitepress->get_current_language();
						$trnsl_product_tabs[ $key ] = $orig_prod_tabs[ $key ];
						$title                      = isset( $data[ md5( 'coretab_' . $orig_prod_tab['id'] . '_title' ) ] ) ? $data[ md5( 'coretab_' . $orig_prod_tab['id'] . '_title' ) ] : '';
						$heading                    = isset( $data[ md5( 'coretab_' . $orig_prod_tab['id'] . '_heading' ) ] ) ? $data[ md5( 'coretab_' . $orig_prod_tab['id'] . '_heading' ) ] : '';

						if ( $default_language !== $lang ) {

							$this->refresh_text_domain( $lang );

							if ( ! $title ) {
								$title = isset( $_POST['product_tab_title'][ $orig_prod_tab['position'] ] ) ? $_POST['product_tab_title'][ $orig_prod_tab['position'] ] : $orig_prod_tabs[ $key ]['title'];
								$title = __( $title, 'woocommerce' );
							}

							if ( ! $heading && ( isset( $orig_prod_tabs[ $key ]['heading'] ) || isset( $_POST['product_tab_heading'][ $orig_prod_tab['position'] ] ) ) ) {
								$heading = isset( $_POST['product_tab_heading'][ $orig_prod_tab['position'] ] ) ? $_POST['product_tab_heading'][ $orig_prod_tab['position'] ] : $orig_prod_tabs[ $key ]['heading'];
								$heading = __( $heading, 'woocommerce' );
							}

							$this->refresh_text_domain( $current_language );
						}

						$trnsl_product_tabs[ $key ]['title']   = $title;
						$trnsl_product_tabs[ $key ]['heading'] = $heading;
						break;
					case 'global':
						$trnsl_product_tabs = $this->set_global_tab( $orig_prod_tab, $trnsl_product_tabs, $lang );
						break;
					case 'product':
						$tab_id      = false;
						$title_key   = md5( 'tab_' . $orig_prod_tab['position'] . '_title' );
						$heading_key = md5( 'tab_' . $orig_prod_tab['position'] . '_heading' );
						$title       = isset( $data[ $title_key ] ) ? sanitize_text_field( $data[ $title_key ] ) : '';
						$content     = isset( $data[ $heading_key ] ) ? wp_kses_post( $data[ $heading_key ] ) : '';

						$trnsl_product_tabs = $this->set_product_tab( $orig_prod_tab, $trnsl_product_tabs, $lang, $trnsl_product_id, $tab_id, $title, $content );

						$i++;
						break;
				}
			}
			update_post_meta( $trnsl_product_id, '_product_tabs', $trnsl_product_tabs );
		}
	}

	/**
	 * @param int    $original_product_id
	 * @param int    $trnsl_product_id
	 * @param string $lang
	 */
	public function duplicate_tabs( $original_product_id, $trnsl_product_id, $lang ) {
		$orig_prod_tabs = maybe_unserialize( get_post_meta( $original_product_id, '_product_tabs', true ) );
		$prod_tabs      = [];
		foreach ( $orig_prod_tabs as $key => $orig_prod_tab ) {
			switch ( $orig_prod_tab['type'] ) {
				case 'core':
					$prod_tabs[ $key ] = $orig_prod_tab;
					$this->refresh_text_domain( $lang );
					$prod_tabs[ $key ]['title'] = __( $orig_prod_tab['title'], 'woocommerce' );
					if ( isset( $orig_prod_tab['heading'] ) ) {
						$prod_tabs[ $key ]['heading'] = __( $orig_prod_tab['heading'], 'woocommerce' );
					}
					$orig_lang = $this->sitepress->get_language_for_element( $original_product_id, 'post_product' );
					$this->refresh_text_domain( $orig_lang );
					break;
				case 'global':
					$prod_tabs = $this->set_global_tab( $orig_prod_tab, $prod_tabs, $lang );
					break;
				case 'product':
					$original_tab = get_post( $orig_prod_tab['id'] );
					$prod_tabs    = $this->set_product_tab( $orig_prod_tab, $prod_tabs, $lang, $trnsl_product_id, false, $original_tab->post_title, $original_tab->post_content );
					break;
			}
		}

		update_post_meta( $trnsl_product_id, '_product_tabs', $prod_tabs );
	}

	/**
	 * @param string $lang
	 */
	public function refresh_text_domain( $lang ) {
		$this->sitepress->switch_lang( $lang );
	}

	/**
	 * @param array  $orig_prod_tab
	 * @param array  $trnsl_product_tabs
	 * @param string $lang
	 *
	 * @return array
	 */
	public function set_global_tab( $orig_prod_tab, $trnsl_product_tabs, $lang ) {
		$tr_tab_id = apply_filters( 'wpml_object_id', $orig_prod_tab['id'], self::POST_TYPE, true, $lang );
		$trnsl_product_tabs[ $orig_prod_tab['type'] . '_tab_' . $tr_tab_id ] = [
			'position' => $orig_prod_tab['position'],
			'type'     => $orig_prod_tab['type'],
			'id'       => $tr_tab_id,
			'name'     => get_post( $tr_tab_id )->post_name,
		];
		return $trnsl_product_tabs;
	}

	/**
	 * @param array  $orig_prod_tab
	 * @param array  $trnsl_product_tabs
	 * @param string $lang
	 * @param int    $trnsl_product_id
	 * @param int    $tab_id
	 * @param string $title
	 * @param string $content
	 *
	 * @return mixed
	 */
	public function set_product_tab( $orig_prod_tab, $trnsl_product_tabs, $lang, $trnsl_product_id, $tab_id, $title, $content ) {
		if ( ! $tab_id ) {
			$tr_tab_id = apply_filters( 'wpml_object_id', $orig_prod_tab['id'], self::POST_TYPE, false, $lang );

			if ( ! is_null( $tr_tab_id ) ) {
				$tab_id = $tr_tab_id;
			}
		}

		if ( $tab_id ) {
			// update existing tab
			$args                 = [];
			$args['post_title']   = $title;
			$args['post_content'] = $content;
			$this->wpdb->update( $this->wpdb->posts, $args, [ 'ID' => $tab_id ] );
		} else {
			// tab not exist creating new
			$args                 = [];
			$args['post_title']   = $title;
			$args['post_content'] = $content;
			$args['post_author']  = get_current_user_id();
			$args['post_name']    = sanitize_title( $title );
			$args['post_type']    = self::POST_TYPE;
			$args['post_parent']  = $trnsl_product_id;
			$args['post_status']  = 'publish';
			$this->wpdb->insert( $this->wpdb->posts, $args );

			$tab_id   = $this->wpdb->insert_id;
			$tab_trid = $this->sitepress->get_element_trid( $orig_prod_tab['id'], self::ELEMENT_TYPE );
			if ( ! $tab_trid ) {
				$this->sitepress->set_element_language_details( $orig_prod_tab['id'], self::ELEMENT_TYPE, false, $this->sitepress->get_default_language() );
				$tab_trid = $this->sitepress->get_element_trid( $orig_prod_tab['id'], self::ELEMENT_TYPE );
			}
			$this->sitepress->set_element_language_details( $tab_id, self::ELEMENT_TYPE, $tab_trid, $lang );
		}

		if ( empty( $title ) || strlen( $title ) != strlen( utf8_encode( $title ) ) ) {
			$tab_name = 'product-tab-' . $tab_id;
		} else {
			$tab_name = sanitize_title( $title );
		}

		$trnsl_product_tabs[ $orig_prod_tab['type'] . '_tab_' . $tab_id ] = [
			'position' => $orig_prod_tab['position'],
			'type'     => $orig_prod_tab['type'],
			'id'       => $tab_id,
			'name'     => $tab_name,
		];

		return $trnsl_product_tabs;
	}

	/**
	 * @param array $exceptions
	 *
	 * @return array
	 */
	public function duplicate_custom_fields_exceptions( $exceptions ) {
		$exceptions[] = '_product_tabs';
		return $exceptions;
	}

	/**
	 * @param object $obj
	 * @param int    $product_id
	 * @param array  $data
	 */
	public function custom_box_html( $obj, $product_id, $data ) {

		if ( 'yes' !== get_post_meta( $product_id, '_override_tab_layout', true ) ) {
			return;
		}

		$orig_prod_tabs = $this->get_product_tabs( $product_id );
		if ( ! $orig_prod_tabs ) {
			return;
		}

		$tabs_section = new WPML_Editor_UI_Field_Section( __( 'Product tabs', 'woocommerce-multilingual' ) );

		$keys     = array_keys( $orig_prod_tabs );
		$last_key = end( $keys );
		$divider  = true;

		foreach ( $orig_prod_tabs as $key => $prod_tab ) {
			if ( $key === $last_key ) {
				$divider = false;
			}

			if ( in_array( $prod_tab['type'], [ 'product', 'core' ] ) ) {
				if ( 'core' === $prod_tab['type'] ) {
					$group     = new WPML_Editor_UI_Field_Group( $prod_tab['title'], $divider );
					$tab_field = new WPML_Editor_UI_Single_Line_Field( 'coretab_' . $prod_tab['id'] . '_title', __( 'Title', 'woocommerce-multilingual' ), $data, false );
					$group->add_field( $tab_field );
					$tab_field = new WPML_Editor_UI_Single_Line_Field( 'coretab_' . $prod_tab['id'] . '_heading', __( 'Heading', 'woocommerce-multilingual' ), $data, false );
					$group->add_field( $tab_field );
					$tabs_section->add_field( $group );
				} else {
					$group     = new WPML_Editor_UI_Field_Group( ucfirst( str_replace( '-', ' ', $prod_tab['name'] ) ), $divider );
					$tab_field = new WPML_Editor_UI_Single_Line_Field( 'tab_' . $prod_tab['position'] . '_title', __( 'Title', 'woocommerce-multilingual' ), $data, false );
					$group->add_field( $tab_field );
					$tab_field = new WCML_Editor_UI_WYSIWYG_Field( 'tab_' . $prod_tab['position'] . '_heading', null, $data, false );
					$group->add_field( $tab_field );
					$tabs_section->add_field( $group );
				}
			}
		}
		$obj->add_field( $tabs_section );
	}

	/**
	 * @param array        $data
	 * @param int          $product_id
	 * @param object|mixed $translation
	 * @param string       $lang
	 *
	 * @return mixed
	 */
	public function custom_box_html_data( $data, $product_id, $translation, $lang ) {

		$orig_prod_tabs = $this->get_product_tabs( $product_id );

		if ( empty( $orig_prod_tabs ) ) {
			return $data;
		}

		foreach ( $orig_prod_tabs as $key => $prod_tab ) {
			if ( in_array( $prod_tab['type'], [ 'product', 'core' ] ) ) {
				if ( 'core' === $prod_tab['type'] ) {
					$data[ 'coretab_' . $prod_tab['id'] . '_title' ]   = [ 'original' => $prod_tab['title'] ];
					$data[ 'coretab_' . $prod_tab['id'] . '_heading' ] = [ 'original' => isset( $prod_tab['heading'] ) ? $prod_tab['heading'] : '' ];
				} else {
					$data[ 'tab_' . $prod_tab['position'] . '_title' ]   = [ 'original' => get_the_title( $prod_tab['id'] ) ];
					$data[ 'tab_' . $prod_tab['position'] . '_heading' ] = [ 'original' => get_post( $prod_tab['id'] )->post_content ];
				}
			}
		}

		if ( is_object( $translation ) ) {
			$tr_prod_tabs = $this->get_product_tabs( $translation->ID );

			if ( ! is_array( $tr_prod_tabs ) ) {
				return $data; // __('Please update original product','woocommerce-multilingual');
			}

			foreach ( $tr_prod_tabs as $key => $prod_tab ) {
				if ( in_array( $prod_tab['type'], [ 'product', 'core' ] ) ) {
					if ( 'core' === $prod_tab['type'] ) {
						$data[ 'coretab_' . $prod_tab['id'] . '_title' ]['translation']   = $prod_tab['title'];
						$data[ 'coretab_' . $prod_tab['id'] . '_heading' ]['translation'] = $prod_tab['heading'] ?? '';
					} else {
						$data[ 'tab_' . $prod_tab['position'] . '_title' ]['translation']   = get_the_title( $prod_tab['id'] );
						$data[ 'tab_' . $prod_tab['position'] . '_heading' ]['translation'] = get_post( $prod_tab['id'] )->post_content;
					}
				}
			}
		} else {
			$current_language = $this->sitepress->get_current_language();
			foreach ( $orig_prod_tabs as $key => $prod_tab ) {
				if ( 'core' === $prod_tab['type'] ) {
					$this->sitepress->switch_lang( $lang );
					$title = __( $prod_tab['title'], 'woocommerce' );
					if ( $prod_tab['title'] !== $title ) {
						$data[ 'coretab_' . $prod_tab['id'] . '_title' ]['translation'] = $title;
					}

					if ( ! isset( $prod_tab['heading'] ) ) {
						$data[ 'coretab_' . $prod_tab['id'] . '_heading' ]['translation'] = '';
					} else {
						$heading = __( $prod_tab['heading'], 'woocommerce' );
						if ( $prod_tab['heading'] !== $heading ) {
							$data[ 'coretab_' . $prod_tab['id'] . '_heading' ]['translation'] = $heading;
						}
					}

					$this->sitepress->switch_lang( $current_language );
				}
			}
		}

		return $data;
	}

	/**
	 * @param int     $new_id
	 * @param WP_Post $original_post
	 */
	public function duplicate_product_tabs( $new_id, $original_post ) {
		if ( function_exists( 'wc_tab_manager_duplicate_product' ) ) {
			wc_tab_manager_duplicate_product( $new_id, $original_post );
		}
	}

	/**
	 * @param int     $post_id
	 * @param WP_Post $post
	 */
	public function force_set_language_information_on_product_tabs( $post_id, $post ) {
		if ( self::POST_TYPE === $post->post_type ) {

			$language = $this->sitepress->get_language_for_element( $post_id, self::ELEMENT_TYPE );
			if ( empty( $language ) && $post->post_parent ) {
				$parent_language = $this->sitepress->get_language_for_element( $post->post_parent, 'post_product' );
				if ( $parent_language ) {
					$this->sitepress->set_element_language_details( $post_id, self::ELEMENT_TYPE, null, $parent_language );
				}
			}
		}
	}

	/**
	 * @param array   $package
	 * @param WP_Post $post
	 *
	 * @return array
	 */
	public function append_custom_tabs_to_translation_package( $package, $post ) {

		if ( 'product' === $post->post_type ) {

			$override_tab_layout = get_post_meta( $post->ID, '_override_tab_layout', true );

			if ( 'yes' === $override_tab_layout ) {

				$meta = (array) get_post_meta( $post->ID, '_product_tabs', true );

				foreach ( $meta as $key => $value ) {

					if ( preg_match( '/product_tab_([0-9]+)/', $key, $matches ) ) {

						$wc_product_tab_id = $matches[1];
						$wc_product_tab    = get_post( $wc_product_tab_id );

						$package['contents'][ self::TAB_FIELD_PREFIX . self::TAB_FIELD_PRODUCT_INTERFIX . $wc_product_tab_id . ':title' ] = [
							'translate' => 1,
							'data'      => $this->tp->encode_field_data( $wc_product_tab->post_title ),
							'format'    => 'base64',
						];

						$package['contents'][ self::TAB_FIELD_PREFIX . self::TAB_FIELD_PRODUCT_INTERFIX . $wc_product_tab_id . ':description' ] = [
							'translate' => 1,
							'data'      => $this->tp->encode_field_data( $wc_product_tab->post_content ),
							'format'    => 'base64',
						];

					} elseif ( preg_match( '/^' . self::TAB_FIELD_CORE_INTERFIX . '(.+)$/', $key, $matches ) ) {

						$package['contents'][ self::TAB_FIELD_PREFIX . self::TAB_FIELD_CORE_INTERFIX . 'title:' . $matches[1] ] = [
							'translate' => 1,
							'data'      => $this->tp->encode_field_data( $value['title'] ),
							'format'    => 'base64',
						];

						if ( isset( $value['heading'] ) ) {
							$package['contents'][ self::TAB_FIELD_PREFIX . self::TAB_FIELD_CORE_INTERFIX . 'heading:' . $matches[1] ] = [
								'translate' => 1,
								'data'      => $this->tp->encode_field_data( $value['heading'] ),
								'format'    => 'base64',
							];
						}
					}
				}
			}
		}

		return $package;
	}

	/**
	 * @param int    $post_id
	 * @param array  $data
	 * @param object $job
	 */
	public function save_custom_tabs_translation( $post_id, $data, $job ) {
		$translated_product_tabs_updated = false;

		$original_product_tabs = get_post_meta( $job->original_doc_id, '_product_tabs', true );

		if ( $original_product_tabs ) {

			// custom tabs
			$product_tab_translations = [];

			foreach ( $data as $value ) {

				if ( preg_match( '/' . self::TAB_FIELD_PREFIX . self::TAB_FIELD_PRODUCT_INTERFIX . '([0-9]+):(.+)/', $value['field_type'], $matches ) ) {

					$wc_product_tab_id = $matches[1];
					$field             = $matches[2];

					$product_tab_translations[ $wc_product_tab_id ][ $field ] = $value['data'];
				}
			}

			if ( $product_tab_translations ) {

				$translated_product_tabs = get_post_meta( $post_id, '_product_tabs', true );
				$translated_product_tabs = $translated_product_tabs ?: [];

				foreach ( $product_tab_translations as $wc_product_tab_id => $value ) {

					$translated_wc_product_tab = [
						'ID'           => apply_filters( 'wpml_object_id', $wc_product_tab_id, self::POST_TYPE, false, $job->language_code ),
						'post_type'    => self::POST_TYPE,
						'post_title'   => $value['title'],
						'post_content' => isset( $value['description'] ) ? $value['description'] : '',
						'post_status'  => 'publish',
						'post_parent'  => $post_id,
					];

					$wc_product_tab_id_translated = wp_insert_post( $translated_wc_product_tab );

					if ( $wc_product_tab_id_translated ) {

						$wc_product_tab_trid = $this->sitepress->get_element_trid( $wc_product_tab_id, self::ELEMENT_TYPE );
						$this->sitepress->set_element_language_details( $wc_product_tab_id_translated, self::ELEMENT_TYPE, $wc_product_tab_trid, $job->language_code );

						$wc_product_tab_translated = get_post( $wc_product_tab_id_translated );

						$translated_product_tabs[ 'product_tab_' . $wc_product_tab_id_translated ] = [
							'position' => $original_product_tabs[ 'product_tab_' . $wc_product_tab_id ]['position'],
							'type'     => 'product',
							'id'       => $wc_product_tab_id_translated,
							'name'     => $wc_product_tab_translated->post_name,

						];
					}
				}

				$translated_product_tabs_updated = true;
			}

			// the other tabs
			$product_tab_translations = [];

			foreach ( $data as $value ) {

				if ( preg_match( '/' . self::TAB_FIELD_PREFIX . self::TAB_FIELD_CORE_INTERFIX . '(.+):(.+)/', $value['field_type'], $matches ) ) {

					$tab_field = $matches[1];
					$tab_id    = $matches[2];

					$product_tab_translations[ $tab_id ][ $tab_field ] = $value['data'];
				}
			}

			if ( $product_tab_translations ) {
				foreach ( $product_tab_translations as $id => $tab ) {

					$translated_product_tabs[ self::TAB_FIELD_CORE_INTERFIX . $id ] = [
						'type'     => 'core',
						'position' => $original_product_tabs[ self::TAB_FIELD_CORE_INTERFIX . $id ]['position'],
						'id'       => $id,
						'title'    => $tab['title'],
					];

					$translated_product_tabs[ self::TAB_FIELD_CORE_INTERFIX . $id ]['heading'] = isset( $tab['heading'] ) ? $tab['heading'] : '';
				}

				$translated_product_tabs_updated = true;
			}

			foreach ( $original_product_tabs as $original_product_tab ) {
				if ( isset( $translated_product_tabs ) && 'global' === $original_product_tab['type'] ) {
					$translated_product_tabs         = $this->set_global_tab( $original_product_tab, $translated_product_tabs, $job->language_code );
					$translated_product_tabs_updated = true;
				}
			}

			if ( true === $translated_product_tabs_updated && isset( $translated_product_tabs ) ) {
				update_post_meta( $post_id, '_product_tabs', $translated_product_tabs );
			}
		}
	}

	/**
	 * @param int $product_id
	 *
	 * @return array
	 */
	public function get_product_tabs( $product_id ) {

		$override_tab_layout = get_post_meta( $product_id, '_override_tab_layout', true );

		if ( 'yes' == $override_tab_layout ) {
			// product defines its own tab layout?
			$product_tabs = get_post_meta( $product_id, '_product_tabs', true );
		} else {
			// otherwise, get the default layout if any
			$product_tabs = get_option( 'wc_tab_manager_default_layout', false );
		}

		return is_array( $product_tabs ) ? $product_tabs : [];
	}

	public function sync_product_tabs( $post_id, $post ) {

		$override_tab_layout = get_post_meta( $post_id, '_override_tab_layout', true );

		if ( $override_tab_layout && $this->woocommerce_wpml->products->is_original_product( $post_id ) ) {

			$original_product_tabs = $this->get_product_tabs( $post_id );

			$trid         = $this->sitepress->get_element_trid( $post_id, 'post_' . $post->post_type );
			$translations = $this->sitepress->get_element_translations( $trid, 'post_' . $post->post_type, true );

			foreach ( $translations as $language => $translation ) {

				if ( empty( $translation->original ) ) {

					$translated_product_tabs = $this->get_product_tabs( $translation->element_id );

					// sync tab positions for product tabs
					foreach ( $original_product_tabs as $tab ) {
						if ( $tab['type'] == 'product' ) {
							$translated_tab_product_id = apply_filters( 'wpml_object_id', $tab['id'], self::POST_TYPE, false, $language );
							if ( $translated_tab_product_id && is_array( $translated_product_tabs[ 'product_tab_' . $translated_tab_product_id ] ) ) {
								$translated_product_tabs[ 'product_tab_' . $translated_tab_product_id ]['position'] = $tab['position'];
							}
						}
					}

					// sync translated core tabs with original tabs
					foreach ( $translated_product_tabs as $tab_key => $tab ) {
						if ( $tab['type'] === 'core' && ! isset( $original_product_tabs[ $tab_key ] ) ) {
							unset( $translated_product_tabs[ $tab_key ] );
						}
					}

					update_post_meta( $translation->element_id, '_product_tabs', $translated_product_tabs );

				}
			}
		}
	}

	/**
	 * @param int|string $tab_id
	 *
	 * @return int|string
	 */
	public function wc_tab_manager_tab_id( $tab_id ) {
		if ( is_int( $tab_id ) ) {
			return apply_filters( 'wpml_object_id', $tab_id, self::POST_TYPE, true );
		} else {
			return $tab_id;
		}
	}

	public function filter_default_layout( $default_tabs ) {

		if ( is_array( $default_tabs ) ) {
			foreach ( $default_tabs as $tab_key => $default_tab ) {
				if ( substr( $tab_key, 0, 10 ) == 'global_tab' ) {
					$trnsl_tab_id = apply_filters( 'wpml_object_id', $default_tab['id'], self::POST_TYPE, true, $this->sitepress->get_current_language() );

					if ( $trnsl_tab_id != $default_tab['id'] ) {
						$default_tabs[ 'global_tab_' . $trnsl_tab_id ]         = $default_tab;
						$default_tabs[ 'global_tab_' . $trnsl_tab_id ]['id']   = $trnsl_tab_id;
						$default_tabs[ 'global_tab_' . $trnsl_tab_id ]['name'] = get_post( $trnsl_tab_id )->post_name;
						unset( $default_tabs[ $tab_key ] );
					}
				}
			}
		}

		return $default_tabs;
	}

	public function show_pointer_info() {
		$pointerFactory = new WCML\PointerUi\Factory();
		$pointerFactory
			->create( [
				'content'    => sprintf(
					/* translators: %1$s and %2$s are opening and closing HTML link tags */
					esc_html__( 'To translate custom per-product tabs, go to the %1$sTranslation Dashboard%2$s and send the associated product for translation.', 'woocommerce-multilingual' ),
					'<a href="' . esc_url( \WCML\Utilities\AdminUrl::getWPMLTMDashboardProducts() ) . '">',
					'</a>'
				),
				'selectorId' => 'woocommerce_product_tabs>p',
				'docLink'    => WCML_Tracking_Link::getWcmlTabManagerDoc(),
			] )
			->show();
	}

	public function replace_tm_editor_custom_fields_with_own_sections( $fields ) {
		$fields[] = '_product_tabs';

		return $fields;
	}

	public function duplicate_categories_exception( $fields ) {
		$fields[] = '_wc_tab_categories';

		return $fields;
	}

	public function translate_categories( $post_id_from, $post_id_to, $meta_key ) {
		if ( '_wc_tab_categories' === $meta_key ) {
			// Saving has already been processed, remove nonce so that we dont
			// process translations too (which would overwrite _wc_tab_categories.
			unset( $_POST['wc_tab_manager_metabox_nonce'] );

			$args     = [
				'element_id'   => $post_id_to,
				'element_type' => self::POST_TYPE,
			];
			$language = apply_filters( 'wpml_element_language_code', false, $args );

			$categories = [];
			$meta_value = get_post_meta( $post_id_from, $meta_key, true );
			foreach ( $meta_value as $category ) {
				$categories[] = apply_filters( 'wpml_object_id', $category, 'product_cat', true, $language );
			}

			update_post_meta( $post_id_to, $meta_key, $categories, $meta_value );
		}
	}

	/**
	 * @param string        $content
	 * @param \WP_Post|null $post
	 *
	 * @return string
	 */
	public function adjust_tab_manager_product_signature( $content, $post = null ) {
		if ( ! is_a( $post, 'WP_Post') ) {
			return $content;
		}
		$tabs = get_posts( [
			'post_parent' => $post->ID,
			'post_type'   => self::POST_TYPE,
			'post_status' => 'publish',
			'numberposts' => -1,
		] );

		foreach ( $tabs as $tab ) {
			$content .= $tab->post_title . ';' . $tab->post_content . ';';
		}

		return $content;
	}
}
