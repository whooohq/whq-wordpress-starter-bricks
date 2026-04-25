<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Search extends Element {
	public $block        = 'core/search';
	public $category     = 'wordpress';
	public $name         = 'search';
	public $icon         = 'ti-search';
	public $css_selector = 'form';

	public function get_label() {
		return esc_html__( 'Search', 'bricks' );
	}

	public function set_control_groups() {
		$this->control_groups['input'] = [
			'title' => esc_html__( 'Input', 'bricks' ),
		];

		$this->control_groups['button'] = [
			'title' => esc_html__( 'Button', 'bricks' ) . ' / ' . esc_html__( 'Icon', 'bricks' ),
		];

		$this->control_groups['overlay'] = [
			'title' => esc_html__( 'Overlay', 'bricks' ),
		];
	}

	public function set_controls() {
		$this->controls['searchType'] = [
			'label'       => esc_html__( 'Type', 'bricks' ),
			'type'        => 'select',
			'inline'      => true,
			'options'     => [
				'input'   => esc_html__( 'Input', 'bricks' ),
				'overlay' => esc_html__( 'Icon', 'bricks' ) . ' & ' . esc_html__( 'Overlay', 'bricks' ),
			],
			'placeholder' => esc_html__( 'Input', 'bricks' ),
		];

		$this->controls['ariaLabel'] = [
			'label'       => 'aria-label',
			'type'        => 'text',
			'inline'      => true,
			'placeholder' => esc_html__( 'Toggle search', 'bricks' ),
			'required'    => [ 'searchType', '=', 'overlay' ],
		];

		$this->controls['actionURL'] = [
			'label'       => esc_html__( 'Action URL', 'bricks' ),
			'type'        => 'text',
			'placeholder' => home_url( '/' ),
			'description' => esc_html__( 'Leave empty to use the default WordPress home URL.', 'bricks' ),
		];

		$this->controls['additionalParams'] = [
			'label'         => esc_html__( 'Additional parameters', 'bricks' ),
			'type'          => 'repeater',
			'titleProperty' => 'paramKey',
			'fields'        => [
				'paramKey'   => [
					'label' => esc_html__( 'Key', 'bricks' ),
					'type'  => 'text',
				],
				'paramValue' => [
					'label' => esc_html__( 'Value', 'bricks' ),
					'type'  => 'text',
				],
			],
			'description'   => esc_html__( 'Added to the search form as hidden input fields.', 'bricks' ),
		];

		// INPUT

		$this->controls['inputHeight'] = [
			'group' => 'input',
			'label' => esc_html__( 'Height', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'css'   => [
				[
					'property' => 'height',
					'selector' => 'input[type=search]',
				],
			],
		];

		$this->controls['inputWidth'] = [
			'group' => 'input',
			'label' => esc_html__( 'Width', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'css'   => [
				[
					'property' => 'width',
					'selector' => 'input[type=search]',
				],
			],
		];

		$this->controls['placeholder'] = [
			'group'       => 'input',
			'label'       => esc_html__( 'Placeholder', 'bricks' ),
			'type'        => 'text',
			'inline'      => true,
			'placeholder' => esc_html__( 'Search ...', 'bricks' ),
		];

		$this->controls['placeholderColor'] = [
			'group' => 'input',
			'label' => esc_html__( 'Placeholder color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'color',
					'selector' => 'input[type=search]::placeholder',
				],
			],
		];

		$this->controls['inputBackgroundColor'] = [
			'group' => 'input',
			'label' => esc_html__( 'Background color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => 'input[type=search]',
				],
			],
		];

		$this->controls['inputBorder'] = [
			'group' => 'input',
			'label' => esc_html__( 'Border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => 'input[type=search]',
				],
			],
		];

		$this->controls['inputBoxShadow'] = [
			'group' => 'input',
			'label' => esc_html__( 'Box shadow', 'bricks' ),
			'type'  => 'box-shadow',
			'css'   => [
				[
					'property' => 'box-shadow',
					'selector' => 'input[type=search]',
				],
			],
		];

		// BUTTON

		$this->controls['buttonAriaLabel'] = [
			'group'  => 'button',
			'label'  => 'aria-label',
			'type'   => 'text',
			'inline' => true,
		];

		$this->controls['buttonAriaLabelInfo'] = [
			'group'    => 'button',
			'content'  => esc_html__( 'You have set an icon, but no text. Please provide the "aria-label" for accessibility.', 'bricks' ),
			'type'     => 'info',
			'required' => [
				[ 'buttonAriaLabel', '=', '' ],
				[ 'buttonText', '=', '' ],
				[ 'icon', '!=', '' ],
			],
		];

		$this->controls['buttonText'] = [
			'group'  => 'button',
			'label'  => esc_html__( 'Text', 'bricks' ),
			'type'   => 'text',
			'inline' => true,
		];

		$this->controls['icon'] = [
			'group' => 'button',
			'label' => esc_html__( 'Icon', 'bricks' ),
			'type'  => 'icon',
		];

		$this->controls['buttonPadding'] = [
			'group' => 'button',
			'label' => esc_html__( 'Padding', 'bricks' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'padding',
					'selector' => 'button',
				],
			],
		];

		$this->controls['iconHeight'] = [
			'group' => 'button',
			'label' => esc_html__( 'Height', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'css'   => [
				[
					'property' => 'height',
					'selector' => 'button',
				],
			],
		];

		$this->controls['iconWidth'] = [
			'group' => 'button',
			'label' => esc_html__( 'Width', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'css'   => [
				[
					'property' => 'width',
					'selector' => 'button',
				],
			],
		];

		$this->controls['iconBackgroundColor'] = [
			'group' => 'button',
			'label' => esc_html__( 'Background color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => 'button',
				],
			],
		];

		$this->controls['iconTypography'] = [
			'group'   => 'button',
			'label'   => esc_html__( 'Typography', 'bricks' ),
			'type'    => 'typography',
			'css'     => [
				[
					'property' => 'font',
					'selector' => 'button',
				],
			],
			'exclude' => [ 'none' ],
		];

		// SEARCH OVERLAY

		$this->controls['searchOverlayTitle'] = [
			'group'   => 'overlay',
			'label'   => esc_html__( 'Title', 'bricks' ),
			'type'    => 'text',
			'inline'  => true,
			'default' => esc_html__( 'Search site', 'bricks' ),
		];

		$this->controls['searchOverlayTitleTag'] = [
			'group'       => 'overlay',
			'label'       => esc_html__( 'Title tag', 'bricks' ),
			'type'        => 'text',
			'inline'      => true,
			'placeholder' => 'h4',
		];

		$this->controls['searchOverlayTitleTypography'] = [
			'group' => 'overlay',
			'label' => esc_html__( 'Title typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.title',
				],
			],
		];

		$this->controls['searchOverlayBackground'] = [
			'group' => 'contoverlaynt',
			'label' => esc_html__( 'Background', 'bricks' ),
			'type'  => 'background',
			'css'   => [
				[
					'property' => 'background',
					'selector' => '.bricks-search-overlay',
				],
			],
		];

		$this->controls['searchOverlayBackgroundOverlay'] = [
			'group' => 'overlay',
			'label' => esc_html__( 'Background', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.bricks-search-overlay:after',
				],
			],
		];
	}

	public function render() {
		$settings   = $this->settings;
		$element_id = $this->id;

		$search_title     = isset( $settings['searchOverlayTitle'] ) ? esc_html( $settings['searchOverlayTitle'] ) : esc_html__( 'Search site', 'bricks' );
		$search_title_tag = isset( $settings['searchOverlayTitleTag'] ) ? esc_html( $settings['searchOverlayTitleTag'] ) : 'h4';
		$search_type      = isset( $settings['searchType'] ) ? $settings['searchType'] : 'input';
		$icon             = isset( $settings['icon'] ) ? self::render_icon( $settings['icon'] ) : false;
		$aria_label       = isset( $settings['ariaLabel'] ) ? $settings['ariaLabel'] : esc_html__( 'Toggle search', 'bricks' );
		$pre_search_value = ''; // Will be using in searchform.php @since 1.9.5

		// Action URL: Parse dynamic data (@since 1.9.5)
		if ( ! empty( $settings['actionURL'] ) ) {
			$settings['actionURL'] = bricks_render_dynamic_data( $settings['actionURL'] );
		}

		// Parse additionalParams (@since 1.9.5)
		if ( ! empty( $settings['additionalParams'] ) ) {
			$additional_params = [];

			foreach ( $settings['additionalParams'] as $param ) {
				$key   = bricks_render_dynamic_data( sanitize_text_field( $param['paramKey'] ?? '' ) );
				$value = bricks_render_dynamic_data( sanitize_text_field( $param['paramValue'] ?? '' ) );

				if ( empty( $key ) || empty( $value ) ) {
					continue;
				}

				// If user predefined search value, store it for later use
				if ( $key === 's' ) {
					$pre_search_value = $value;
					continue;
				}

				$additional_params[ $key ] = $value;
			}

			// Overwrite additionalParams
			$settings['additionalParams'] = $additional_params;
		}

		echo "<div {$this->render_attributes( '_root' )}>";

		if ( $search_type === 'input' ) {
			// Use include to pass $settings
			include locate_template( 'searchform.php' );
		} else {
			// Return: No icon set
			if ( ! $icon ) {
				return $this->render_element_placeholder(
					[
						'title' => esc_html__( 'No icon selected.', 'bricks' )
					]
				);
			}

			echo '<button aria-expanded="false" aria-label="' . esc_attr( $aria_label ) . '" class="toggle">' . $icon . '</button>';

			unset( $settings['icon'] );
			?>
			<div class="bricks-search-overlay">
				<div class="bricks-search-inner">
					<?php
					echo "<$search_title_tag class=\"title\">$search_title</$search_title_tag>";

					// Use include to pass $settings
					include locate_template( 'searchform.php' );
					?>
				</div>

				<?php echo '<button aria-label="' . esc_html__( 'Close search', 'bricks' ) . '" class="close">×</button>'; ?>
			</div>
			<?php
		}

		echo '</div>';
	}

	public function convert_element_settings_to_block( $settings ) {
		$attributes = [];

		if ( isset( $settings['inputWidth'] ) ) {
			$attributes['width'] = $settings['inputWidth'];
		}

		if ( isset( $settings['placeholder'] ) ) {
			$attributes['placeholder'] = $settings['placeholder'];
		}

		if ( isset( $settings['icon'] ) ) {
			$attributes['buttonUseIcon'] = true;
		}

		if ( isset( $settings['_cssClasses'] ) ) {
			$attributes['className'] = $settings['_cssClasses'];
		}

		$block = [
			'blockName'    => $this->block,
			'attrs'        => $attributes,
			'innerContent' => [],
		];

		return $block;
	}

	public function convert_block_to_element_settings( $block, $attributes ) {
		$element_settings = [];

		if ( isset( $attributes['width'] ) ) {
			$element_settings['inputWidth'] = $attributes['width'] . 'px';
		}

		if ( isset( $attributes['placeholder'] ) ) {
			$element_settings['placeholder'] = $attributes['placeholder'];
		}

		if ( isset( $attributes['buttonUseIcon'] ) ) {
			$element_settings['icon'] = [
				'library' => 'themify',
				'icon'    => 'ti-search',
			];
		}

		if ( isset( $attributes['className'] ) ) {
			$element_settings['_cssClasses'] = $attributes['className'];
		}

		return $element_settings;
	}
}
