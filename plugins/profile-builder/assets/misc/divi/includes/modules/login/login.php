<?php

class WPPB_Login extends ET_Builder_Module {

	public $slug       = 'wppb_login';
	public $vb_support = 'on';

	protected $module_credits = array(
		'module_uri' => 'https://wordpress.org/plugins/profile-builder/',
		'author'     => 'Cozmoslabs',
		'author_uri' => 'https://www.cozmoslabs.com/',
	);

	public function init() {
        $this->name = esc_html__( 'PB Login', 'profile-builder' );

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
        $args = array(
            'post_type'      => 'page',
            'posts_per_page' => -1
        );

        if( function_exists( 'wc_get_page_id' ) )
            $args['exclude'] = wc_get_page_id( 'shop' );

        $all_pages = get_posts( $args );
        $pages ['default'] = 'None';

        if( !empty( $all_pages ) ){
            foreach ( $all_pages as $page ){
                $pages [ esc_url( get_page_link( $page->ID ) ) ] = esc_html( $page->post_title );
            }
        }

        $wppb_module_settings = get_option( 'wppb_module_settings', 'not_found' );

        $registration_forms ['default'] = esc_html__( 'Default' , 'profile-builder' );

        if ( !( ( $wppb_module_settings !== 'not_found' && (
                    !isset( $wppb_module_settings['wppb_multipleRegistrationForms'] ) ||
                    $wppb_module_settings['wppb_multipleRegistrationForms'] !== 'show'
                ) ) ||
            $wppb_module_settings === 'not_found' ) ){
            $args = array(
                'post_type'      => 'wppb-rf-cpt',
                'posts_per_page' => -1
            );

            $the_query = new WP_Query( $args );

            if ( $the_query->have_posts() ) {
                foreach ( $the_query->posts as $post ) {
                    $registration_forms [esc_attr( Wordpress_Creation_Kit_PB::wck_generate_slug( $post->post_title ) )] = esc_html( $post->post_title );
                }
                wp_reset_postdata();
            }
        }

        if (!function_exists('get_editable_roles')) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
        }
        $user_roles ['default'] = esc_html__( 'Default' , 'profile-builder' );
        $editable_roles = get_editable_roles();
        foreach ($editable_roles as $key => $role) {
            $user_roles [$key] = $role['name'];
        }

        $fields = array(
            'register_url'        => array(
                'label'           => esc_html__( 'Registration Page', 'profile-builder' ),
                'type'            => 'select',
                'options'         => $pages,
                'default'         => 'default',
                'option_category' => 'basic_option',
                'description'     => esc_html__( 'Add a link to a Registration Page.', 'profile-builder' ),
                'toggle_slug'     => 'main_content',
            ),
            'lostpassword_url'        => array(
                'label'           => esc_html__( 'Recover Password Page', 'profile-builder' ),
                'type'            => 'select',
                'options'         => $pages,
                'default'         => 'default',
                'option_category' => 'basic_option',
                'description'     => esc_html__( 'Add a link to a Recover Password Page.', 'profile-builder' ),
                'toggle_slug'     => 'main_content',
            ),
            'redirect_url'        => array(
                'label'           => esc_html__( 'Redirect After Login', 'profile-builder' ),
                'type'            => 'select',
                'options'         => $pages,
                'default'         => 'default',
                'option_category' => 'basic_option',
                'description'     => esc_html__( 'Select a page for an After Login Redirect.', 'profile-builder' ),
                'toggle_slug'     => 'main_content',
            ),
            'logout_redirect_url' => array(
                'label'           => esc_html__( 'Redirect After Logout', 'profile-builder' ),
                'type'            => 'select',
                'options'         => $pages,
                'default'         => 'default',
                'option_category' => 'basic_option',
                'description'     => esc_html__( 'Select a page for an After Logout Redirect.', 'profile-builder' ),
                'toggle_slug'     => 'main_content',
            ),
        );

        if( defined( 'WPPB_PAID_PLUGIN_DIR' ) ) {
            $fields['toggle_ajax_validation'] = array(
                'label'              => esc_html__( 'AJAX Validation', 'profile-builder' ),
                'type'               => 'yes_no_button',
                'options'            => array(
                    'on'             => esc_html__( 'Yes', 'profile-builder'),
                    'off'            => esc_html__( 'No', 'profile-builder'),
                ),
                'option_category'    => 'basic_option',
                'description'        => esc_html__( 'Use AJAX to Validate the Login Form without reloading the page.', 'profile-builder' ),
                'toggle_slug'        => 'main_content',
            );
        }

        $wppb_two_factor_authentication_settings = get_option( 'wppb_two_factor_authentication_settings', 'not_found' );
        if( isset( $wppb_two_factor_authentication_settings['enabled'] ) && $wppb_two_factor_authentication_settings['enabled'] === 'yes' ) {
            $fields ['toggle_auth_field'] = array(
                'label'           => esc_html__( 'Show Authenticator Code Field', 'profile-builder' ),
                'type'            => 'yes_no_button',
                'options'         => array(
                    'on'          => esc_html__( 'Yes', 'profile-builder'),
                    'off'         => esc_html__( 'No', 'profile-builder'),
                ),
                'option_category' => 'basic_option',
                'description'     => esc_html__( 'Select if the form should show the Authenticator Code Field.', 'profile-builder' ),
                'toggle_slug'     => 'main_content',
            );
        }

		return $fields;
	}

    public function render( $attrs, $content, $render_slug ) {

        if ( !is_array( $attrs ) ) {
            return;
        }

        include_once( WPPB_PLUGIN_DIR.'/front-end/login.php' );

        $atts = [
            'register_url'        => array_key_exists( 'register_url', $attrs ) && $attrs['register_url'] !== '' ? pb_divi_parse_url( $attrs['register_url'] ) : '',
            'lostpassword_url'    => array_key_exists( 'lostpassword_url', $attrs ) && $attrs['lostpassword_url'] !== '' ? pb_divi_parse_url( $attrs['lostpassword_url'] ) : '',
            'redirect_url'        => array_key_exists( 'redirect_url', $attrs ) && $attrs['redirect_url'] !== '' ? pb_divi_parse_url( $attrs['redirect_url'] ) : '',
            'logout_redirect_url' => array_key_exists( 'logout_redirect_url', $attrs ) && $attrs['logout_redirect_url'] !== '' ? pb_divi_parse_url( $attrs['logout_redirect_url'] ) : '',
            'ajax'                => array_key_exists( 'toggle_ajax_validation', $attrs ) && $attrs['toggle_ajax_validation'] === 'on'  ? 'true' : false,
            'auth_field'          => array_key_exists( 'toggle_auth_field', $attrs ) && $attrs['toggle_auth_field'] === 'on' ? 'yes' : '',
        ];

        return '<div class="wppb-divi-front-end-container">' . wppb_front_end_login( $atts ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}

new WPPB_Login;
