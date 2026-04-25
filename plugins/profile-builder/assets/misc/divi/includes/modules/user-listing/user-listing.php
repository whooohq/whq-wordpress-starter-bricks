<?php

class WPPB_UserListing extends ET_Builder_Module {

	public $slug       = 'wppb_userlisting';
	public $vb_support = 'on';

	protected $module_credits = array(
		'module_uri' => 'https://wordpress.org/plugins/profile-builder/',
		'author'     => 'Cozmoslabs',
		'author_uri' => 'https://www.cozmoslabs.com/',
	);

	public function init() {
        $this->name = esc_html__( 'PB User Listing', 'profile-builder' );

        $this->settings_modal_toggles = array(
            'general' => array(
                'toggles' => array(
                    'main_content' => esc_html__( 'Form Settings', 'profile-builder' ),
                ),
            ),
        );

        $this->advanced_fields = array(
            'link_options' => false,
            'background'   => false,
            'admin_label'  => false,
        );
	}

	public function get_fields() {

        // get UL names
        $ul_names = wppb_get_userlisting_names();
        $default_key = 'default';
        $ul_names [ $default_key ] = 'Select a User Listing form';
        $ul_default_key = is_null( key($ul_names) ) ? '' : key($ul_names);

        // get fields
        $wppb_manage_fields = get_option( 'wppb_manage_fields', 'not_found' );
        $field_names = array();
        $field_names [ $default_key ] = 'None';
        foreach( $wppb_manage_fields as $value ) {
            if ( $value['meta-name'] !== '' ) {
                $field_names[ $value['meta-name'] ] = $value['field-title'];
            }
        }

		return array(
            'userlisting_name'      => array(
                'label'             => esc_html__( 'Form', 'profile-builder' ),
                'type'              => 'select',
                'options'           => $ul_names,
                'default'           => $default_key,
                'option_category'   => 'basic_option',
                'description'       => esc_html__( 'Select the desired User Listing form.', 'profile-builder' ),
                'toggle_slug'       => 'main_content',
            ),
            'toggle_single'         => array(
                'label'             => esc_html__( 'Single-Userlisting', 'profile-builder' ),
                'type'              => 'yes_no_button',
                'options'           => array(
                    'on'            => esc_html__( 'Yes', 'profile-builder'),
                    'off'           => esc_html__( 'No', 'profile-builder'),
                ),
                'default'           => 'off',
                'option_category'   => 'basic_option',
                'description'       => esc_html__( 'Use the Single-Userlisting template.', 'profile-builder' ),
                'toggle_slug'       => 'main_content',
            ),
            'single_id'             => array(
                'label'             => esc_html__( 'ID', 'profile-builder' ),
                'type'              => 'text',
                'option_category'   => 'basic_option',
                'description'       => esc_html__( 'Input User ID.', 'profile-builder' ),
                'toggle_slug'       => 'main_content',
                'show_if'           => array(
                    'toggle_single' => 'on',
                ),
            ),
            'field_name'              => array(
                'label'             => esc_html__( 'Field Name', 'profile-builder' ),
                'type'              => 'select',
                'options'           => $field_names,
                'default'           => $default_key,
                'option_category'   => 'basic_option',
                'description'       => esc_html__( 'Select the desired Field Name.', 'profile-builder' ),
                'toggle_slug'       => 'main_content',
                'show_if'           => array(
                    'toggle_single' => 'off',
                ),
            ),
            'meta_value'            => array(
                'label'             => esc_html__( 'Field Value', 'profile-builder' ),
                'type'              => 'text',
                'option_category'   => 'basic_option',
                'description'       => esc_html__( 'Input the desired Field Value.', 'profile-builder' ),
                'toggle_slug'       => 'main_content',
                'show_if'           => array(
                    'toggle_single' => 'off',
                ),
            ),
            'include_id'            => array(
                'label'             => esc_html__( 'Include', 'profile-builder' ),
                'type'              => 'text',
                'option_category'   => 'basic_option',
                'description'       => esc_html__( 'Input User IDs for users you wish to display.', 'profile-builder' ),
                'toggle_slug'       => 'main_content',
                'show_if'           => array(
                    'toggle_single' => 'off',
                ),
            ),
            'exclude_id'            => array(
                'label'             => esc_html__( 'Exclude', 'profile-builder' ),
                'type'              => 'text',
                'option_category'   => 'basic_option',
                'description'       => esc_html__( 'Input User IDs for users you wish to hide.', 'profile-builder' ),
                'toggle_slug'       => 'main_content',
                'show_if'           => array(
                    'toggle_single' => 'off',
                ),
            ),
		);
	}

    public function render( $attrs, $content, $render_slug ) {

        if ( !is_array( $attrs ) || !array_key_exists( 'userlisting_name', $attrs ) ) {
            return;
        }

        include_once( WPPB_PAID_PLUGIN_DIR.'/add-ons/user-listing/userlisting.php' );

        $atts = [
            'name'       => sanitize_text_field($attrs['userlisting_name']),
            '0'          => array_key_exists( 'toggle_single', $attrs ) && $attrs['toggle_single'] === 'on' ? 'single'             : '',
            'id'         => array_key_exists( 'single_id', $attrs ) ? absint($attrs['single_id']) : '',
            'meta_key'   => array_key_exists( 'meta_key', $attrs ) && $attrs['meta_key'] !== 'default'  ? sanitize_text_field($attrs['meta_key']) : '',
            'meta_value' => array_key_exists( 'meta_value', $attrs ) && $attrs['meta_value'] !== 'default'  ? sanitize_text_field($attrs['meta_value']) : '',
            'include'    => array_key_exists( 'include_id', $attrs ) ? pb_divi_parse_ids($attrs['include_id']) : '',
            'exclude'    => array_key_exists( 'exclude_id', $attrs ) ? pb_divi_parse_ids($attrs['exclude_id']) : '',
        ];
        return '<div class="wppb-divi-front-end-container">' . wppb_user_listing_shortcode( $atts ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}

new WPPB_UserListing;
