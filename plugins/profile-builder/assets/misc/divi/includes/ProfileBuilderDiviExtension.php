<?php

class WPPB_ProfileBuilderDiviExtension extends DiviExtension {

	/**
	 * The gettext domain for the extension's translations.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $gettext_domain = 'wppb-profile-builder-divi-extension';

	/**
	 * The extension's WP Plugin name.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $name = 'profile-builder-divi-extension';

	/**
	 * The extension's version
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $version = '1.0.0';

	/**
	 * WPPB_ProfileBuilderDiviExtension constructor.
	 *
	 * @param string $name
	 * @param array  $args
	 */
	public function __construct( $name = 'profile-builder-divi-extension', $args = array() ) {
		$this->plugin_dir     = plugin_dir_path( __FILE__ );
		$this->plugin_dir_url = plugin_dir_url( $this->plugin_dir );

        $this->_builder_js_data = array(
            'nonces' => array(
                'pb_divi_render_form_nonce' => wp_create_nonce( 'pb_divi_render_form' ),
            )
        );

		parent::__construct( $name, $args );
	}
}

new WPPB_ProfileBuilderDiviExtension;
