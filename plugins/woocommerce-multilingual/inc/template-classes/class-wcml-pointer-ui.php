<?php

class WCML_Pointer_UI extends WCML_Templates_Factory {

    private $content;
    private $documentationUrl;
    private $selector;
    private $method;
    private $anchor;

    public function __construct(
      $content,
      $documentationUrl = false,
      $selector = false,
      $method = false,
      $anchor = false
    ) {
        parent::__construct();

        $this->anchor           = $anchor ?: __( 'How to translate this?', 'woocommerce-multilingual' );
				$this->selector         = $selector;
        $this->method           = $method;
        $this->content          = $content;
        $this->documentationUrl = $documentationUrl;
    }

    public function get_model(){

        $model = [
            'pointer'       => md5( rand( 0, 100 ) ),
            'selector'      => $this->selector,
            'insert_method' => $this->method,
            'description'   => [
                'content'       => $this->content,
                'trnsl_title'   => $this->anchor,
                'doc_link'      => $this->documentationUrl,
                'doc_link_text' => __( 'Learn more', 'woocommerce-multilingual' ),
            ],
        ];

        return $model;

    }

    protected function init_template_base_dir() {
        $this->template_paths = [
            WCML_PLUGIN_PATH . '/templates/',
        ];
    }

    public function get_template() {
        return 'pointer-ui.twig';
    }


}