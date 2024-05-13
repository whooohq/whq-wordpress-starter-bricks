<?php


namespace WPML\BlockEditor;

use WP_Mock\Hook;
use WPML\BlockEditor\Blocks\LanguageSwitcher;
use WPML\LIB\WP\Hooks;
use WPML\Core\WP\App\Resources;
use function WPML\Container\make;
use function WPML\FP\spreadArgs;

class Loader implements \IWPML_Backend_Action, \IWPML_REST_Action {

	const SCRIPT_NAME = 'wpml-blocks';

	/** @var array Contains the script data that needs to be localized for the registered blocks. */
	private $localizedScriptData = [];

	public function add_hooks() {

		Hooks::onAction( 'init' )
			->then( [ $this, 'registerBlocks' ] );

		Hooks::onAction( 'wp_enqueue_scripts' )
		     ->then( [ $this, 'enqueueBlockStyles' ] );

		Hooks::onAction( 'enqueue_block_editor_assets' )
			->then( [ $this, 'enqueueBlockAssets' ] );

		Hooks::onFilter( 'block_categories_all', 10, 2 )
			->then( spreadArgs( [ $this, 'registerCategory' ] ) );
	}

	/**
	 * @param array[] $block_categories
	 * @param \WP_Block_Editor_Context $editor_context
	 * @return mixed
	 */
	public function registerCategory( $block_categories, $editor_context ) {
		if ( ! empty( $editor_context->post ) ) {
			array_push(
				$block_categories,
				[
					'slug'  => 'wpml',
					'title' => __( 'WPML', 'sitepress-multilingual-cms' ),
					'icon'  => null,
				]
			);
		}
		return $block_categories;
	}

	/**
	 * Register blocks that need server side render.
	 */
	public function registerBlocks() {
		$LSLocalizedScriptData = make( LanguageSwitcher::class )->register();
		$this->localizedScriptData = array_merge( $this->localizedScriptData, $LSLocalizedScriptData );
	}

	/**
	 * @return void
	 */
	public function enqueueBlockAssets() {
		$dependencies = [
			'wp-blocks',
			'wp-i18n',
			'wp-element',
			'wp-editor',
		];
		$localizedScriptData = [ 'name' => 'WPMLBlocks', 'data' => $this->localizedScriptData ];
		$enqueuedApp = Resources::enqueueApp( 'blocks' );
		$enqueuedApp( $localizedScriptData, $dependencies );
	}

	public function enqueueBlockStyles() {
		wp_enqueue_style(
			self::SCRIPT_NAME,
			ICL_PLUGIN_URL . '/dist/css/blocks/styles.css',
			[],
			ICL_SITEPRESS_VERSION
		);
	}
}
