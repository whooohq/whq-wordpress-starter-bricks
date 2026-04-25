<?php

class WPPB_RecoverPassword extends ET_Builder_Module {

	public $slug       = 'wppb_recover_password';
	public $vb_support = 'on';

	protected $module_credits = array(
		'module_uri' => 'https://wordpress.org/plugins/profile-builder/',
		'author'     => 'Cozmoslabs',
		'author_uri' => 'https://www.cozmoslabs.com/',
	);

	public function init() {
        $this->name = esc_html__( 'PB Recover Password', 'profile-builder' );

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
        $fields = array();

        if( defined( 'WPPB_PAID_PLUGIN_DIR' ) ) {
            $fields['toggle_ajax_validation'] = array(
                'label'              => esc_html__( 'AJAX Validation', 'profile-builder' ),
                'type'               => 'yes_no_button',
                'options'            => array(
                    'on'             => esc_html__( 'Yes', 'profile-builder'),
                    'off'            => esc_html__( 'No', 'profile-builder'),
                ),
                'option_category'    => 'basic_option',
                'description'        => esc_html__( 'Use AJAX to Validate the Recover Password Form without reloading the page.', 'profile-builder' ),
                'toggle_slug'        => 'main_content',
            );
        }

        return $fields;
	}

    public function render( $attrs, $content, $render_slug ) {

        include_once( WPPB_PLUGIN_DIR.'/front-end/recover.php' );

        $atts = [
            'ajax' => ( is_array( $attrs ) && array_key_exists( 'toggle_ajax_validation', $attrs ) ) && $attrs['toggle_ajax_validation'] === 'on'  ? 'true' : false,
        ];

        return '<div class="wppb-divi-front-end-container">' . wppb_front_end_password_recovery( $atts ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}

new WPPB_RecoverPassword;
