<?php

class WPPB_Content_Restriction_End extends ET_Builder_Module {

	public $slug       = 'wppb_content_restriction_end';
	public $vb_support = 'on';

	protected $module_credits = array(
		'module_uri' => 'https://wordpress.org/plugins/profile-builder/',
		'author'     => 'Cozmoslabs',
		'author_uri' => 'https://www.cozmoslabs.com/',
	);

	public function init() {
        $this->name = esc_html__( 'PB Content Restriction End', 'profile-builder' );

        $this->advanced_fields = array(
            'link_options' => false,
            'background'   => false,
            'admin_label'  => false,
        );
	}

    public function render( $attrs, $content, $render_slug ) {
        return;
    }
}

global $content_restriction_activated;
global $wp_version;
if ( isset( $content_restriction_activated ) && $content_restriction_activated == 'yes' && version_compare( $wp_version, "5.0.0", ">=" ) ) {
    new WPPB_Content_Restriction_End;
}

