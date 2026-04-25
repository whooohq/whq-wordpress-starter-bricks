<?php

class WPPB_Content_Restriction_Start extends ET_Builder_Module {

	public $slug       = 'wppb_content_restriction_start';
	public $vb_support = 'on';

	protected $module_credits = array(
		'module_uri' => 'https://wordpress.org/plugins/profile-builder/',
		'author'     => 'Cozmoslabs',
		'author_uri' => 'https://www.cozmoslabs.com/',
	);

	public function init() {
        $this->name = esc_html__( 'PB Content Restriction Start', 'profile-builder' );

        $this->settings_modal_toggles = array(
            'general' => array(
                'toggles' => array(
                    'main_content' => esc_html__( 'Module Settings', 'profile-builder' ),
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
        if (!function_exists('get_editable_roles')) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
        }
        $user_roles ['default'] = esc_html__( 'All' , 'profile-builder' );
        $editable_roles = get_editable_roles();
        foreach ($editable_roles as $key => $role) {
            $user_roles [$key] = $role['name'];
        }

        $fields_list['wppb_display_to'] = array(
            'label'              => esc_html__( 'Show content to', 'profile-builder' ),
            'description'        => esc_html__( 'The users you wish to see the content.', 'profile-builder' ),
            'type'               => 'select',
            'options'            => array(
                'all'            => esc_html__( 'All', 'profile-builder' ),
                'logged_in'      => esc_html__( 'Logged in', 'profile-builder' ),
                'not_logged_in'  => esc_html__( 'Not logged in', 'profile-builder' ),
            ),
            'default'            => 'all',
            'option_category'    => 'basic_option',
            'toggle_slug'        => 'main_content',
        );
        $fields_list['wppb_user_roles'] = array(
            'label'              => esc_html__( 'User Roles', 'profile-builder' ),
            'description'        => esc_html__( 'The desired valid user roles. Select none for all roles to be valid.', 'profile-builder' ),
            'type'               => 'select',
            'options'            => $user_roles,
            'default'            => 'default',
            'option_category'    => 'basic_option',
            'toggle_slug'        => 'main_content',
            'show_if'            => array(
                'wppb_display_to'     => 'logged_in',
            ),
        );
        $fields_list['wppb_users_ids'] = array(
            'label'              => esc_html__( 'User IDs', 'profile-builder' ),
            'description'        => esc_html__( 'A comma-separated list of user IDs.', 'profile-builder' ),
            'type'               => 'text',
            'option_category'    => 'basic_option',
            'toggle_slug'        => 'main_content',
            'show_if'            => array(
                'wppb_display_to'     => 'logged_in',
            ),
        );
        $fields_list['wppb_toggle_message'] = array(
            'label'              => esc_html__( 'Enable Message', 'profile-builder' ),
            'description'        => esc_html__( 'Show the Message defined in the Profile Builder Settings.', 'profile-builder' ),
            'type'               => 'yes_no_button',
            'options'            => array(
                'on'             => esc_html__( 'Yes', 'profile-builder'),
                'off'            => esc_html__( 'No', 'profile-builder'),
            ),
            'option_category'    => 'basic_option',
            'toggle_slug'        => 'main_content',
            'show_if_not'        => array(
                'wppb_display_to'     => 'all',
            ),
        );
        $fields_list['wppb_toggle_custom_message'] = array(
            'label'              => esc_html__( 'Custom Message', 'profile-builder' ),
            'description'        => esc_html__( 'Enable Custom Message.', 'profile-builder' ),
            'type'               => 'yes_no_button',
            'options'            => array(
                'on'             => esc_html__( 'Yes', 'profile-builder'),
                'off'            => esc_html__( 'No', 'profile-builder'),
            ),
            'option_category'    => 'basic_option',
            'toggle_slug'        => 'main_content',
            'show_if_not'        => array(
                'wppb_display_to'     => 'all',
            ),
            'show_if'            => array(
                'wppb_toggle_message' => 'on',
            ),
        );
        $fields_list['wppb_message_logged_in'] = array(
            'label'              => esc_html__( 'Custom message', 'profile-builder' ),
            'description'        => esc_html__( 'Enter the custom message you wish the restricted users to see.', 'profile-builder' ),
            'type'               => 'text',
            'option_category'    => 'basic_option',
            'toggle_slug'        => 'main_content',
            'show_if'            => array(
                'wppb_toggle_message'        => 'on',
                'wppb_toggle_custom_message' => 'on',
                'wppb_display_to'            => 'logged_in',
            ),
        );
        $fields_list['wppb_message_logged_out'] = array(
            'label'              => esc_html__( 'Custom message', 'profile-builder' ),
            'description'        => esc_html__( 'Custom message for logged-out users.', 'profile-builder' ),
            'type'               => 'text',
            'option_category'    => 'basic_option',
            'toggle_slug'        => 'main_content',
            'show_if'            => array(
                'wppb_toggle_message'        => 'on',
                'wppb_toggle_custom_message' => 'on',
                'wppb_display_to'            => 'not_logged_in',
            ),
        );
        return $fields_list;
	}

    public function render( $attrs, $content, $render_slug ) {
        return;
    }
}

global $content_restriction_activated;
global $wp_version;
if ( isset( $content_restriction_activated ) && $content_restriction_activated == 'yes' && version_compare( $wp_version, "5.0.0", ">=" ) ) {
    new WPPB_Content_Restriction_Start;
}
