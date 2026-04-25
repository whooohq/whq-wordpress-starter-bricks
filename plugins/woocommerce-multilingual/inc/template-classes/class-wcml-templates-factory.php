<?php

use WPML\Core\Twig_Environment;
use WPML\Core\Twig_Error_Syntax;
use WPML\Core\Twig_Error_Runtime;
use WPML\Core\Twig_Error_Loader;
use WPML\Core\Twig_Loader_Filesystem;

abstract class WCML_Templates_Factory extends WPML_Templates_Factory {

	/**
	 * @param string $template
	 * @param array  $model
	 *
	 * @return string
	 * @throws Twig_Error_Syntax
	 * @throws Twig_Error_Runtime
	 * @throws Twig_Error_Loader
	 */
	public function get_view( $template = null, $model = null ) {
		$output = '';
		$this->maybe_init_twig();

		if ( null === $model ) {
			$model = $this->get_model();
		}
		if ( null === $template ) {
			$template = $this->get_template();
		}

		$this->before_render();

		try {
			/* @phpstan-ignore class.notFound */
			$output = $this->twig->render( $template, $model );
		} catch ( RuntimeException $e ) {
			if ( $this->is_caching_enabled() ) {
				$this->disable_twig_cache();
				unset( $this->twig );
				$this->maybe_init_twig();
				$output = $this->get_view( $template, $model );
			} else {
				$this->add_exception_notice( $e );
			}
		} catch ( \WPML\Core\Twig\Error\SyntaxError $e ) {
			$message = 'Invalid Twig template string: ' . $e->getRawMessage() . "\n" . $template;
			$this->get_wp_api()->error_log( $message );
		}

		return $output;
	}

	protected function before_render() {

	}

	/**
	 * Maybe init twig for WCML
	 */
	protected function maybe_init_twig() {
		if ( $this->twig instanceof Twig_Environment ) {
			return;
		}
			$loader = $this->get_twig_loader();

			$environment_args = [];

			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				$environment_args['debug'] = true;
			}

			if ( $this->is_caching_enabled() ) {
				$wpml_cache_directory  = new WPML_Cache_Directory( $this->get_wp_api() );
				$this->cache_directory = $wpml_cache_directory->get( 'twig' );

				if ( $this->cache_directory ) {
					$environment_args['cache']       = $this->cache_directory;
					$environment_args['auto_reload'] = true;
				} else {
					$this->disable_twig_cache();
				}
			}

			/* @phpstan-ignore assign.propertyType */
			$this->twig = $this->get_twig_environment( $loader, $environment_args );
			if ( is_array( $this->custom_functions ) ) {
				foreach ( $this->custom_functions as $custom_function ) {
					$this->twig->addFunction( $custom_function );
				}
			}
			if ( is_array( $this->custom_filters ) ) {
				foreach ( $this->custom_filters as $custom_filter ) {
					$this->twig->addFilter( $custom_filter );
				}
			}
	}

	/**
	 * @return Twig_Loader_Filesystem
	 */
	protected function get_twig_loader() {
		return new Twig_Loader_Filesystem( $this->template_paths );
	}

	/**
	 * @param Twig_Loader_Filesystem $loader
	 * @param array                  $environment_args
	 */
	private function get_twig_environment( $loader, $environment_args ): Twig_Environment {
		return new Twig_Environment( $loader, $environment_args );
	}
}
