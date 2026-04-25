<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Convert Gutenberg blocks to Bricks elements and vice versa
 */
class Blocks {
	// Bricks elements objects that are able to be converted
	private $elements = [];

	// Converted Bricks data
	private $output = [];

	/**
	 * In order to convert Gutenberg content into a flat Bricks data, this object needs to be instantiated
	 */
	public function __construct() {}

	/**
	 * Load post gutenberg blocks
	 *
	 * @param int $post_id
	 */
	public static function load_blocks( $post_id ) {
		$post = get_post( $post_id );

		if ( ! has_blocks( $post ) ) {
			return false;
		}

		return parse_blocks( $post->post_content );
	}

	/**
	 * Prepare Bricks elements instances that are possible to be converted
	 */
	public static function load_elements() {
		$elements = [];

		foreach ( Elements::$elements as $element ) {
			$element_class_name = $element['class'];

			$element_instance = new $element_class_name( $element );

			if ( isset( $element_instance->block ) && method_exists( $element_instance, 'convert_block_to_element_settings' ) ) {
				foreach ( (array) $element_instance->block as $block ) {
					$elements[ $block ] = $element_instance;
				}
			}
		}

		return $elements;
	}

	/**
	 * Convert gutenberg post content into bricks data
	 *
	 * @param int $post_id
	 */
	public function convert_blocks_to_bricks( $post_id ) {
		$this->output = [];

		// Loads Gutenberg data
		$blocks = self::load_blocks( $post_id );

		if ( empty( $blocks ) ) {
			return [];
		}

		$this->elements = self::load_elements();

		foreach ( $blocks as $block ) {
			$this->convert_block_to_element( $block );
		}

		return $this->output;
	}

	/**
	 * Convert Gutenberg block to Bricks element
	 *
	 * To populate Bricks with existing Gutenberg blocks.
	 *
	 * Supported blocks (Gutenberg blockName > Bricks element['name']):
	 * - core/columns, core/buttons, core/group > container
	 * - core/heading       > heading
	 * - core/paragraph     > text
	 * - core/list          > text
	 * - core/buttons       > button
	 * - core/image         > image
	 * - core/html          > html
	 * - core/code          > code
	 * - core/preformatted  > code
	 * - core/video         > video
	 * - core-embed/youtube > video
	 * - core-embed/vimeo   > video
	 * - core/audio         > audio
	 * - core/shortcode     > shortcode
	 * - core/search        > search
	 */
	public function convert_block_to_element( $block, $parent_id = 0 ) {
		// Skip block without blockName (e.g. Classic Editor generated post_content, etc.)
		if ( empty( $block['blockName'] ) ) {
			return;
		}

		// Block is core/columns, core/buttons or core/group
		if ( ! empty( $block['innerBlocks'] ) ) {

			$row_id = Helpers::generate_random_id( false );

			$row_settings = $this->add_common_block_settings( [], $block['attrs'] );

			$row = [
				'name'     => 'container',
				'id'       => $row_id,
				'parent'   => $parent_id,
				'settings' => $row_settings,
				'children' => []
			];

			// Iterate through all the inner blocks
			foreach ( $block['innerBlocks'] as $child_block ) {

				if ( empty( $child_block['blockName'] ) ) {
					continue;
				}

				// Child block is a column
				if ( $child_block['blockName'] === 'core/column' ) {

					$column_id = Helpers::generate_random_id( false );

					$row['children'][] = $column_id;

					$column_settings = $this->add_common_block_settings( [], $child_block['attrs'] );

					$column = [
						'name'     => 'container',
						'id'       => $column_id,
						'parent'   => $row_id,
						'settings' => $column_settings,
						'children' => []
					];

					if ( ! empty( $child_block['innerBlocks'] ) && is_array( $child_block['innerBlocks'] ) ) {
						foreach ( $child_block['innerBlocks'] as $inner_child_block ) {
							$element = $this->convert_block_to_element( $inner_child_block, $column_id );

							if ( ! empty( $element['id'] ) ) {
								$column['children'][] = $element['id'];
							}
						}
					}

					// Add column to the flat list
					$this->output[] = $column;

				}
				// Not a column, maybe a core/button
				else {
					$element = $this->convert_block_to_element( $child_block, $row_id );

					if ( ! empty( $element['id'] ) ) {
						$row['children'][] = $element['id'];
					}
				}
			}

			// Add row to the flat list
			$this->output[] = $row;

			// Leave, this block is added.
			return $row;
		}

		// Regular block, check if we can convert it
		if ( ! array_key_exists( $block['blockName'], $this->elements ) ) {
			return false;
		}

		$bricks_element_instance = $this->elements[ $block['blockName'] ];
		$element_settings        = $bricks_element_instance->convert_block_to_element_settings( $block, $block['attrs'] );

		if ( empty( $element_settings ) || ! is_array( $element_settings ) ) {
			return false;
		}

		$element_settings = $this->add_common_block_settings( $element_settings, $block['attrs'] );

		$element = [
			'name'     => $bricks_element_instance->name,
			'id'       => Helpers::generate_random_id( false ),
			'parent'   => $parent_id,
			'settings' => $element_settings,
			'children' => []
		];

		// Add element to the flat list
		$this->output[] = $element;

		return $element;
	}

	/**
	 * Add common block settings to Bricks data
	 *
	 * @param array $settings Bricks element settings.
	 * @param array $attributes GT block attributes.
	 *
	 * @return array
	 */
	public function add_common_block_settings( $settings, $attributes ) {
		if ( empty( $attributes ) ) {
			return $settings;
		}

		if ( isset( $attributes['className'] ) ) {
			$settings['_cssClasses'] = trim( $attributes['className'] );
		}

		return $settings;
	}

	/**
	 * Generate blocks HTML string from Bricks content elements (to store as post_content)
	 *
	 * @param array $elements Array of all Bricks elements on a section.
	 * @param int   $post_id The post ID.
	 *
	 * @return string
	 *
	 * @since 1.0
	 */
	public static function serialize_bricks_to_blocks( $elements, $post_id ) {
		$blocks = [];

		foreach ( $elements as $element ) {
			$element_class_name = isset( Elements::$elements[ $element['name'] ]['class'] ) ? Elements::$elements[ $element['name'] ]['class'] : $element['name'];
			$element_instance   = new $element_class_name( $element );
			$element_instance->set_post_id( $post_id );
			$element_settings = isset( $element['settings'] ) && is_array( $element['settings'] ) ? $element['settings'] : [];

			// Skip: Element has no settings / $block defined / convert_element_settings_to_block() function
			if ( ! count( $element_settings ) || ! $element_instance->block || ! method_exists( $element_instance, 'convert_element_settings_to_block' ) ) {
				continue;
			}

			// Get block HTML string plus comment attributes
			$block = $element_instance->convert_element_settings_to_block( $element['settings'] );

			$blocks[] = serialize_block( $block );
		}

		if ( count( $blocks ) ) {
			return join( "\n\n", $blocks );
		}
	}
}
