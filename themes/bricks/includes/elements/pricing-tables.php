<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Pricing_Tables extends Element {
	public $category     = 'general';
	public $name         = 'pricing-tables';
	public $icon         = 'ti-money';
	public $css_selector = '.pricing-table';

	public function get_label() {
		return esc_html__( 'Pricing Tables', 'bricks' );
	}

	public function set_control_groups() {
		$this->control_groups['tabs'] = [
			'title' => esc_html__( 'Tabs', 'bricks' ),
			'tab'   => 'content',
		];
	}

	public function set_controls() {
		$this->controls['pricingTables'] = [
			'tab'           => 'content',
			'type'          => 'repeater',
			'titleProperty' => 'title',
			'placeholder'   => esc_html__( 'Pricing table', 'bricks' ),
			'fields'        => [
				'showUnder'                => [
					'label'       => esc_html__( 'Show under', 'bricks' ),
					'type'        => 'select',
					'options'     => [
						'tab-1' => esc_html__( 'Tab 1', 'bricks' ),
						'tab-2' => esc_html__( 'Tab 2', 'bricks' ),
					],
					'inline'      => true,
					'placeholder' => esc_html__( 'Tab 1', 'bricks' ),
				],

				'tableBackground'          => [
					'label' => esc_html__( 'Table background', 'bricks' ),
					'type'  => 'background',
					'css'   => [
						[
							'property' => 'background',
							'selector' => '.pricing-table-bg',
						],
					],
				],

				'tableBorder'              => [
					'label' => esc_html__( 'Table border', 'bricks' ),
					'type'  => 'border',
					'css'   => [
						[
							'property' => 'border',
							'selector' => '.pricing-table-bg',
						],
					],
				],

				'tableBoxShadow'           => [
					'label' => esc_html__( 'Table box shadow', 'bricks' ),
					'type'  => 'box-shadow',
					'css'   => [
						[
							'property' => 'box-shadow',
							'selector' => '.pricing-table-bg',
						],
					],
				],

				// Group: Header

				'headerSeparator'          => [
					'label' => esc_html__( 'Header', 'bricks' ),
					'type'  => 'separator',
				],

				'title'                    => [
					'type'        => 'text',
					'placeholder' => esc_html__( 'Title', 'bricks' ),
				],

				'subtitle'                 => [
					'type'        => 'text',
					'placeholder' => esc_html__( 'Subtitle', 'bricks' ),
				],

				'headerPadding'            => [
					'label' => esc_html__( 'Padding', 'bricks' ),
					'type'  => 'spacing',
					'css'   => [
						[
							'property' => 'padding',
							'selector' => '.pricing-table-header',
						],
					],
				],

				'headerBackgroundColor'    => [
					'label' => esc_html__( 'Background color', 'bricks' ),
					'type'  => 'color',
					'css'   => [
						[
							'property' => 'background-color',
							'selector' => '.pricing-table-header',
						],
					],
				],

				'headerBorder'             => [
					'label' => esc_html__( 'Border', 'bricks' ),
					'type'  => 'border',
					'css'   => [
						[
							'property' => 'border',
							'selector' => '.pricing-table-header',
						],
					],
				],

				'headerTitleTypography'    => [
					'label' => esc_html__( 'Title typography', 'bricks' ),
					'type'  => 'typography',
					'css'   => [
						[
							'property' => 'font',
							'selector' => '.pricing-table-title',
						],
					],
				],

				'headerSubtitleTypography' => [
					'label' => esc_html__( 'Subtitle typography', 'bricks' ),
					'type'  => 'typography',
					'css'   => [
						[
							'property' => 'font',
							'selector' => '.pricing-table-subtitle',
						],
					],
				],

				// Group: Pricing

				'priceSeparator'           => [
					'label' => esc_html__( 'Pricing', 'bricks' ),
					'type'  => 'separator',
				],

				'pricePadding'             => [
					'label' => esc_html__( 'Padding', 'bricks' ),
					'type'  => 'spacing',
					'css'   => [
						[
							'property' => 'padding',
							'selector' => '.pricing-table-pricing',
						],
					],
				],

				'pricePrefix'              => [
					'label'  => esc_html__( 'Price prefix', 'bricks' ),
					'type'   => 'text',
					'inline' => true
				],

				'price'                    => [
					'label'  => esc_html__( 'Price', 'bricks' ),
					'type'   => 'text',
					'inline' => true
				],

				'priceSuffix'              => [
					'label'  => esc_html__( 'Price suffix', 'bricks' ),
					'type'   => 'text',
					'inline' => true
				],

				'priceMeta'                => [
					'label'  => esc_html__( 'Price meta', 'bricks' ),
					'type'   => 'text',
					'inline' => true
				],

				'priceMetaTypography'      => [
					'label'    => esc_html__( 'Meta typography', 'bricks' ),
					'type'     => 'typography',
					'css'      => [
						[
							'property' => 'font',
							'selector' => '.pricing-table-price-meta',
						],
					],
					'required' => [ 'priceMeta', '!=', '' ],
				],

				'priceTypography'          => [
					'label' => esc_html__( 'Price typography', 'bricks' ),
					'type'  => 'typography',
					'css'   => [
						[
							'property' => 'font',
							'selector' => '.pricing-table-price-prefix',
						],
						[
							'property' => 'font',
							'selector' => '.pricing-table-price',
						],
						[
							'property' => 'font',
							'selector' => '.pricing-table-price-suffix',
						],
					],
				],

				'priceBackgroundColor'     => [
					'label' => esc_html__( 'Background color', 'bricks' ),
					'type'  => 'color',
					'css'   => [
						[
							'property' => 'background-color',
							'selector' => '.pricing-table-pricing',
						],
					],
				],

				'priceBorder'              => [
					'label' => esc_html__( 'Border', 'bricks' ),
					'type'  => 'border',
					'css'   => [
						[
							'property' => 'Border',
							'selector' => '.pricing-table-pricing',
						],
					],
				],

				'priceOriginal'            => [
					'label'  => esc_html__( 'Original price', 'bricks' ),
					'type'   => 'text',
					'inline' => true
				],

				'priceOriginalTypography'  => [
					'label'    => esc_html__( 'Original price typography', 'bricks' ),
					'type'     => 'typography',
					'css'      => [
						[
							'property' => 'font',
							'selector' => '.pricing-table-original-price',
						],
					],
					'required' => [ 'priceOriginal', '!=', '' ],
				],

				// Group: Features

				'featuresSeparator'        => [
					'label' => esc_html__( 'Features', 'bricks' ),
					'type'  => 'separator',
				],

				'featuresPadding'          => [
					'label'       => esc_html__( 'Padding', 'bricks' ),
					'type'        => 'spacing',
					'css'         => [
						[
							'property' => 'padding',
							'selector' => '.pricing-table-feature',
						],
					],
					'placeholder' => [
						'top'    => 10,
						'right'  => 30,
						'bottom' => 10,
						'left'   => 30,
					],
				],

				'features'                 => [
					'label'       => esc_html__( 'Features', 'bricks' ),
					'type'        => 'textarea',
					'description' => esc_html__( 'One feature per line', 'bricks' ),
				],

				'featuresAlignment'        => [
					'label'       => esc_html__( 'Alignment', 'bricks' ),
					'type'        => 'justify-content',
					'exclude'     => 'space',
					'inline'      => true,
					'css'         => [
						[
							'property' => 'justify-content',
							'selector' => '.pricing-table-feature',
						],
					],
					'placeholder' => esc_html__( 'Center', 'bricks' ),
				],

				'featuresIcon'             => [
					'label' => esc_html__( 'Icon', 'bricks' ),
					'type'  => 'icon',
				],

				'featuresIconColor'        => [
					'label'    => esc_html__( 'Icon color', 'bricks' ),
					'type'     => 'color',
					'css'      => [
						[
							'property' => 'color',
							'selector' => '.pricing-table-feature i',
						],
					],
					'required' => [ 'featuresIcon.icon', '!=', '' ],
				],

				'featuresIconSize'         => [
					'label'    => esc_html__( 'Icon size', 'bricks' ),
					'type'     => 'number',
					'units'    => true,
					'css'      => [
						[
							'property' => 'font-size',
							'selector' => '.pricing-table-feature i',
						],
					],
					'required' => [ 'featuresIcon.icon', '!=', '' ],
				],

				'featuresIconPosition'     => [
					'label'       => esc_html__( 'Icon position', 'bricks' ),
					'type'        => 'select',
					'options'     => [
						'right' => esc_html__( 'Right', 'bricks' ),
						'left'  => esc_html__( 'Left', 'bricks' ),
					],
					'inline'      => true,
					'placeholder' => esc_html__( 'Left', 'bricks' ),
					'required'    => [ 'featuresIcon', '!=', '' ],
				],

				'featuresBackgroundColor'  => [
					'label' => esc_html__( 'Background color', 'bricks' ),
					'type'  => 'color',
					'css'   => [
						[
							'property' => 'background-color',
							'selector' => '.pricing-table-feature',
						],
					],
				],

				'featuresBorder'           => [
					'label' => esc_html__( 'Border', 'bricks' ),
					'type'  => 'border',
					'css'   => [
						[
							'property' => 'border',
							'selector' => '.pricing-table-feature',
						],
					],
				],

				'featuresTypography'       => [
					'label' => esc_html__( 'Typography', 'bricks' ),
					'type'  => 'typography',
					'css'   => [
						[
							'property' => 'font',
							'selector' => '.pricing-table-feature',
						],
					],
				],

				// Group: Footer

				'footerSeparator'          => [
					'type'  => 'separator',
					'label' => esc_html__( 'Footer', 'bricks' ),
				],

				'footerPadding'            => [
					'label' => esc_html__( 'Padding', 'bricks' ),
					'type'  => 'spacing',
					'css'   => [
						[
							'property' => 'padding',
							'selector' => '.pricing-table-footer',
						],
					],
				],

				'footerBackgroundColor'    => [
					'label' => esc_html__( 'Background color', 'bricks' ),
					'type'  => 'color',
					'css'   => [
						[
							'property' => 'background-color',
							'selector' => '.pricing-table-footer',
						],
					],
				],

				'footerBorder'             => [
					'label' => esc_html__( 'Border', 'bricks' ),
					'type'  => 'border',
					'css'   => [
						[
							'property' => 'border',
							'selector' => '.pricing-table-footer',
						],
					],
				],

				// Group: Button

				'buttonSeparator'          => [
					'type'  => 'separator',
					'label' => esc_html__( 'Button', 'bricks' ),
				],

				'buttonText'               => [
					'type'        => 'text',
					'placeholder' => esc_html__( 'Button text', 'bricks' ),
				],

				'buttonLink'               => [
					'label'       => esc_html__( 'Link', 'bricks' ),
					'type'        => 'link',
					'popup'       => false, // NOTE: Undocumented
					'placeholder' => 'https://yoursite.com',
					'required'    => [ 'buttonText', '!=', '' ],
				],

				'buttonWidth'              => [
					'label'    => esc_html__( 'Width', 'bricks' ),
					'type'     => 'number',
					'units'    => true,
					'css'      => [
						[
							'property' => 'width',
							'selector' => '.bricks-button',
						],
					],
					'required' => [ 'buttonText', '!=', '' ],
				],

				'buttonSize'               => [
					'label'    => esc_html__( 'Size', 'bricks' ),
					'type'     => 'select',
					'inline'   => true,
					'options'  => $this->control_options['buttonSizes'],
					'required' => [ 'buttonText', '!=', '' ],
				],

				'buttonStyle'              => [
					'label'       => esc_html__( 'Style', 'bricks' ),
					'type'        => 'select',
					'options'     => $this->control_options['styles'],
					'inline'      => true,
					'placeholder' => esc_html__( 'None', 'bricks' ),
					'required'    => [ 'buttonText', '!=', '' ],
				],

				'buttonBackgroundColor'    => [
					'label'    => esc_html__( 'Background color', 'bricks' ),
					'type'     => 'color',
					'css'      => [
						[
							'property' => 'background-color',
							'selector' => '.bricks-button',
						],
					],
					'required' => [ 'buttonText', '!=', '' ],
				],

				'buttonBorder'             => [
					'label'    => esc_html__( 'Border', 'bricks' ),
					'type'     => 'border',
					'css'      => [
						[
							'property' => 'border',
							'selector' => '.bricks-button',
						],
					],
					'required' => [ 'buttonText', '!=', '' ],
				],

				'buttonTypography'         => [
					'label'    => esc_html__( 'Typography', 'bricks' ),
					'type'     => 'typography',
					'css'      => [
						[
							'property' => 'font',
							'selector' => '.bricks-button',
						],
					],
					'required' => [ 'buttonText', '!=', '' ],
				],

				// Group: Additional Info

				'additionalInfoSeparator'  => [
					'type'  => 'separator',
					'label' => esc_html__( 'Additional info', 'bricks' ),
				],

				'additionalInfo'           => [
					'type' => 'textarea', // NOTE: type 'editor' Slows down repeater editing
				],

				'additionalInfoTypography' => [
					'label'    => esc_html__( 'Typography', 'bricks' ),
					'type'     => 'typography',
					'css'      => [
						[
							'property' => 'font',
							'selector' => '.pricing-table-additional-info',
						],
					],
					'required' => [ 'additionalInfo', '!=', '' ],
				],

				// Group: Ribbon

				'ribbonSeparator'          => [
					'type'  => 'separator',
					'label' => esc_html__( 'Ribbon', 'bricks' ),
				],

				'ribbonText'               => [
					'label'  => esc_html__( 'Text', 'bricks' ),
					'type'   => 'text',
					'inline' => true,
				],

				'ribbonPosition'           => [
					'label'       => esc_html__( 'Position', 'bricks' ),
					'type'        => 'select',
					'options'     => [
						'left'  => esc_html__( 'Left', 'bricks' ),
						'right' => esc_html__( 'Right', 'bricks' ),
					],
					'inline'      => true,
					'placeholder' => esc_html__( 'Right', 'bricks' ),
				],

				'ribbonBackgroundColor'    => [
					'label' => esc_html__( 'Background color', 'bricks' ),
					'type'  => 'color',
					'css'   => [
						[
							'property' => 'background-color',
							'selector' => '.pricing-table-ribbon-title',
						],
					],
				],

				'ribbonTypography'         => [
					'label' => esc_html__( 'Typography', 'bricks' ),
					'type'  => 'typography',
					'css'   => [
						[
							'property' => 'font',
							'selector' => '.pricing-table-ribbon-title',
						],
					],
				],

			],

			'default'       => [
				[
					'title'        => 'BUSINESS',
					'subtitle'     => esc_html__( 'Subtitle goes here', 'bricks' ),
					'pricePrefix'  => '$',
					'price'        => '29',
					'priceSuffix'  => '90',
					'priceMeta'    => esc_html__( 'per month', 'bricks' ),
					'features'     =>
						esc_html__( 'Unlimited websites', 'bricks' ) . "\n" .
						esc_html__( '20GB web space', 'bricks' ) . "\n" .
						esc_html__( 'SSL certificate', 'bricks' ),
					'featuresIcon' => [
						'library' => 'ionicons',
						'icon'    => 'ion-ios-checkmark-circle-outline',
					],
					'buttonText'   => 'GET STARTED',
					'buttonStyle'  => 'primary',
				],
			],
		];

		$this->controls['columns'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Columns', 'bricks' ),
			'type'  => 'number',
			'min'   => 1,
			'css'   => [
				[
					'property' => 'grid-template-columns',
					'selector' => '.pricing-tables',
					'value'    => 'repeat(%s, 1fr)', // NOTE: Undocumented (@since 1.3)
				],
				[
					'property' => 'grid-auto-flow',
					'selector' => '.pricing-tables',
					'value'    => 'unset',
				],
			],
		];

		$this->controls['gutter'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Spacing', 'bricks' ),
			'type'        => 'number',
			'units'       => true,
			'css'         => [
				[
					'property' => 'gap',
					'selector' => '.pricing-tables',
				],
			],
			'placeholder' => 30,
		];

		$this->controls['horizontalAlign'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Align tables', 'bricks' ),
			'type'        => 'align-items',
			'css'         => [
				[
					'property' => 'align-items',
					'selector' => '.pricing-tables',
				],
			],
			'placeholder' => esc_html__( 'Stretch', 'bricks' ),
		];

		// Group: Tabs

		$this->controls['tabs'] = [
			'tab'   => 'content',
			'group' => 'tabs',
			'label' => esc_html__( 'Show tabs', 'bricks' ),
			'type'  => 'checkbox',
		];

		$this->controls['tab1Label'] = [
			'tab'         => 'content',
			'group'       => 'tabs',
			'label'       => esc_html__( 'Tab 1 label', 'bricks' ),
			'type'        => 'text',
			'inline'      => true,
			'placeholder' => esc_html__( 'Monthly', 'bricks' ),
			'required'    => [ 'tabs', '!=', '' ],
		];

		$this->controls['tab2Label'] = [
			'tab'         => 'content',
			'group'       => 'tabs',
			'label'       => esc_html__( 'Tab 2 label', 'bricks' ),
			'type'        => 'text',
			'inline'      => true,
			'placeholder' => esc_html__( 'Yearly', 'bricks' ),
			'required'    => [ 'tabs', '!=', '' ],
		];

		$this->controls['defaultTab'] = [
			'tab'         => 'content',
			'group'       => 'tabs',
			'label'       => esc_html__( 'Default tab', 'bricks' ),
			'type'        => 'select',
			'options'     => [
				'tab-1' => esc_html__( 'Tab 1', 'bricks' ),
				'tab-2' => esc_html__( 'Tab 2', 'bricks' ),
			],
			'inline'      => true,
			'placeholder' => esc_html__( 'Tab 1', 'bricks' ),
			'required'    => [ 'tabs', '!=', '' ],
		];

		$this->controls['tabsJustifyContent'] = [
			'tab'         => 'content',
			'group'       => 'tabs',
			'label'       => esc_html__( 'Alignment', 'bricks' ),
			'type'        => 'justify-content',
			'css'         => [
				[
					'property' => 'justify-content',
					'selector' => '.tabs',
				],
			],
			'placeholder' => esc_html__( 'Center', 'bricks' ),
			'required'    => [ 'tabs', '!=', '' ],
		];

		$this->controls['tabsMargin'] = [
			'tab'      => 'content',
			'group'    => 'tabs',
			'label'    => esc_html__( 'Margin', 'bricks' ),
			'type'     => 'spacing',
			'css'      => [
				[
					'property' => 'margin',
					'selector' => '.tabs',
				],
			],
			'required' => [ 'tabs', '!=', '' ],
		];

		$this->controls['tabsBackgroundColor'] = [
			'tab'      => 'content',
			'group'    => 'tabs',
			'label'    => esc_html__( 'Background', 'bricks' ),
			'type'     => 'color',
			'css'      => [
				[
					'property' => 'background-color',
					'selector' => '.tabs',
				],
			],
			'required' => [ 'tabs', '!=', '' ],
		];

		$this->controls['tabsBorder'] = [
			'tab'      => 'content',
			'group'    => 'tabs',
			'label'    => esc_html__( 'Border', 'bricks' ),
			'type'     => 'border',
			'css'      => [
				[
					'property' => 'border',
					'selector' => '.tabs',
				],
			],
			'required' => [ 'tabs', '!=', '' ],
		];

		$this->controls['tabsBoxShadow'] = [
			'tab'      => 'content',
			'group'    => 'tabs',
			'label'    => esc_html__( 'Box shadow', 'bricks' ),
			'type'     => 'box-shadow',
			'css'      => [
				[
					'property' => 'box-shadow',
					'selector' => '.tabs',
				],
			],
			'required' => [ 'tabs', '!=', '' ],
		];

		// Tab

		$this->controls['tabSeparator'] = [
			'tab'      => 'content',
			'group'    => 'tabs',
			'label'    => esc_html__( 'Tab', 'bricks' ),
			'type'     => 'separator',
			'required' => [ 'tabs', '!=', '' ],
		];

		$this->controls['tabWidth'] = [
			'tab'      => 'content',
			'group'    => 'tabs',
			'label'    => esc_html__( 'Width', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'width',
					'selector' => '.tab',
				],
			],
			'required' => [ 'tabs', '!=', '' ],
		];

		$this->controls['tabMargin'] = [
			'tab'      => 'content',
			'group'    => 'tabs',
			'label'    => esc_html__( 'Margin', 'bricks' ),
			'type'     => 'spacing',
			'css'      => [
				[
					'property' => 'margin',
					'selector' => '.tab',
				],
			],
			'required' => [ 'tabs', '!=', '' ],
		];

		$this->controls['tabPadding'] = [
			'tab'      => 'content',
			'group'    => 'tabs',
			'label'    => esc_html__( 'Padding', 'bricks' ),
			'type'     => 'spacing',
			'css'      => [
				[
					'property' => 'padding',
					'selector' => '.tab',
				],
			],
			'required' => [ 'tabs', '!=', '' ],
		];

		$this->controls['tabBackgroundColor'] = [
			'tab'      => 'content',
			'group'    => 'tabs',
			'label'    => esc_html__( 'Background', 'bricks' ),
			'type'     => 'color',
			'css'      => [
				[
					'property' => 'background-color',
					'selector' => '.tab',
				],
			],
			'required' => [ 'tabs', '!=', '' ],
		];

		$this->controls['tabBorder'] = [
			'tab'      => 'content',
			'group'    => 'tabs',
			'label'    => esc_html__( 'Border', 'bricks' ),
			'type'     => 'border',
			'css'      => [
				[
					'property' => 'border',
					'selector' => '.tab',
				],
			],
			'required' => [ 'tabs', '!=', '' ],
		];

		$this->controls['tabBoxShadow'] = [
			'tab'      => 'content',
			'group'    => 'tabs',
			'label'    => esc_html__( 'Box shadow', 'bricks' ),
			'type'     => 'box-shadow',
			'css'      => [
				[
					'property' => 'box-shadow',
					'selector' => '.tab',
				],
			],
			'required' => [ 'tabs', '!=', '' ],
		];

		$this->controls['tabTitleTypography'] = [
			'tab'      => 'content',
			'group'    => 'tabs',
			'label'    => esc_html__( 'Typography', 'bricks' ),
			'type'     => 'typography',
			'css'      => [
				[
					'property' => 'font',
					'selector' => '.tab',
				],
			],
			'required' => [ 'tabs', '!=', '' ],
		];

		$this->controls['tabActiveBackgroundColor'] = [
			'tab'      => 'content',
			'group'    => 'tabs',
			'label'    => esc_html__( 'Active background', 'bricks' ),
			'type'     => 'color',
			'css'      => [
				[
					'property' => 'background-color',
					'selector' => '.tab.active',
				],
			],
			'required' => [ 'tabs', '!=', '' ],
		];

		$this->controls['tabActiveBorder'] = [
			'tab'      => 'content',
			'group'    => 'tabs',
			'label'    => esc_html__( 'Active border', 'bricks' ),
			'type'     => 'border',
			'css'      => [
				[
					'property' => 'border',
					'selector' => '.tab.active',
				],
			],
			'required' => [ 'tabs', '!=', '' ],
		];

		$this->controls['tabActiveBoxShadow'] = [
			'tab'      => 'content',
			'group'    => 'tabs',
			'label'    => esc_html__( 'Active box shadow', 'bricks' ),
			'type'     => 'box-shadow',
			'css'      => [
				[
					'property' => 'box-shadow',
					'selector' => '.tab.active',
				],
			],
			'required' => [ 'tabs', '!=', '' ],
		];

		$this->controls['tabActiveTitleTypography'] = [
			'tab'      => 'content',
			'group'    => 'tabs',
			'label'    => esc_html__( 'Active typography', 'bricks' ),
			'type'     => 'typography',
			'css'      => [
				[
					'property' => 'font',
					'selector' => '.tab.active',
				],
			],
			'required' => [ 'tabs', '!=', '' ],
		];

		// Defaults
		$this->controls['_boxShadow']['default'] = [
			'values' => [
				'offsetX' => 5,
				'offsetY' => 10,
				'blur'    => 30,
				'spread'  => 0,
			],
			'color'  => [
				'hex' => Setup::get_default_color( 'heading' ),
				'hsl' => 'hsla(0, 0, 13%, 0.1)',
				'rgb' => 'rgba(33, 33, 33, 0.1)',
			],
		];
	}

	public function render() {
		$settings = $this->settings;

		if ( empty( $settings['pricingTables'] ) ) {
			return $this->render_element_placeholder(
				[
					'title' => esc_html__( 'No pricing table defined.', 'bricks' ),
				]
			);
		}

		// Render
		echo "<div {$this->render_attributes( '_root' )}>";

		// Tabs
		$active_tab = 'tab-1';

		if ( isset( $settings['tabs'] ) ) {
			$active_tab = 'tab-1';

			$tab_1_classes = [ 'tab', 'tab-1' ];
			$tab_2_classes = [ 'tab', 'tab-2' ];

			// Set default tab
			if ( ! isset( $settings['defaultTab'] ) || ( isset( $settings['defaultTab'] ) && $settings['defaultTab'] === 'tab-1' ) ) {
				$tab_1_classes[] = 'active';
				$active_tab      = 'tab-1';
			}

			if ( isset( $settings['defaultTab'] ) && $settings['defaultTab'] === 'tab-2' ) {
				$tab_2_classes[] = 'active';
				$active_tab      = 'tab-2';
			}

			$tabs_html  = '<ul class="tabs">';
			$tabs_html .= isset( $settings['tab1Label'] ) ? '<li class="' . join( ' ', $tab_1_classes ) . '">' . $settings['tab1Label'] . '</li>' : '';
			$tabs_html .= isset( $settings['tab2Label'] ) ? '<li class="' . join( ' ', $tab_2_classes ) . '">' . $settings['tab2Label'] . '</li>' : '';
			$tabs_html .= '</ul>';

			echo $tabs_html;
		}

		echo '<ul class="pricing-tables">';

		foreach ( $settings['pricingTables'] as $index => $table ) {
			$table_tab     = isset( $table['showUnder'] ) ? $table['showUnder'] : 'tab-1';
			$table_classes = [ 'pricing-table', 'repeater-item', $table_tab ];

			if ( $active_tab === 'tab-1' && $table_tab === 'tab-1' ) {
				$table_classes[] = 'active';
			}

			if ( $active_tab === 'tab-2' && $table_tab === 'tab-2' ) {
				$table_classes[] = 'active';
			}

			echo '<li class="' . join( ' ', $table_classes ) . '">';

			echo '<div class="pricing-table-bg css-filter"></div>';

			// Ribbon
			if ( isset( $table['ribbonText'] ) ) {
				$ribbon_wrapper_classes = [ 'pricing-table-ribbon' ];

				if ( isset( $table['ribbonPosition'] ) ) {
					$ribbon_wrapper_classes[] = $table['ribbonPosition'];
				} else {
					$ribbon_wrapper_classes[] = 'right';
				}

				$this->set_attribute( "ribbon-wrapper-$index", 'class', $ribbon_wrapper_classes );
				$this->set_attribute( "ribbon-text-$index", 'class', 'pricing-table-ribbon-title' );

				echo "<div {$this->render_attributes( "ribbon-wrapper-$index" )}>";
				echo "<div {$this->render_attributes( "ribbon-text-$index" )}>{$this->render_dynamic_data( $table['ribbonText'] )}</div>";
				echo '</div>';
			}

			// Header
			if ( ! empty( $table['title'] ) || ! empty( $table['subtitle'] ) ) {
				echo '<div class="pricing-table-header">';

				if ( isset( $table['title'] ) ) {
					$this->set_attribute( "title-$index", 'class', 'pricing-table-title' );

					echo "<div {$this->render_attributes( "title-$index" )}>{$this->render_dynamic_data( $table['title'] )}</div>";
				}

				if ( isset( $table['subtitle'] ) ) {
					if ( isset( $table['subtitle'] ) ) {
						$this->set_attribute( "subtitle-$index", 'class', 'pricing-table-subtitle' );

						echo "<span {$this->render_attributes( "subtitle-$index" )}>{$this->render_dynamic_data( $table['subtitle'] )}</span>";
					}
				}

				echo '</div>';
			}

			if ( isset( $table['price'] ) ) {
				echo '<div class="pricing-table-pricing">';

				echo '<div class="pricing-table-price-wrapper">';

				if ( isset( $table['priceOriginal'] ) ) {
					$this->set_attribute( "original-price-$index", 'class', 'pricing-table-original-price' );

					echo "<span {$this->render_attributes( "original-price-$index" )}>{$this->render_dynamic_data( $table['priceOriginal'] )}</span>";
				}

				if ( isset( $table['pricePrefix'] ) ) {
					$this->set_attribute( "price-prefix-$index", 'class', 'pricing-table-price-prefix' );

					echo "<span {$this->render_attributes( "price-prefix-$index" )}>{$this->render_dynamic_data( $table['pricePrefix'] )}</span>";
				}

				if ( isset( $table['price'] ) ) {
					$this->set_attribute( "price-$index", 'class', 'pricing-table-price' );

					echo "<span {$this->render_attributes( "price-$index" )}>{$this->render_dynamic_data( $table['price'] )}</span>";
				}

				if ( isset( $table['priceSuffix'] ) ) {
					$this->set_attribute( "price-suffix-$index", 'class', 'pricing-table-price-suffix' );

					echo "<span {$this->render_attributes( "price-suffix-$index" )}>{$this->render_dynamic_data( $table['priceSuffix'] )}</span>";
				}

				echo '</div>';

				if ( isset( $table['priceMeta'] ) ) {
					$this->set_attribute( "price-meta-$index", 'class', 'pricing-table-price-meta' );

					echo "<span {$this->render_attributes( "price-meta-$index" )}>{$this->render_dynamic_data( $table['priceMeta'] )}</span>";
				}

				echo '</div>';
			}

			// Features
			if ( ! empty( $table['features'] ) ) {
				echo '<ul class="pricing-table-features">';
					$features               = Helpers::parse_textarea_options( $table['features'] );
					$features_icon          = isset( $table['featuresIcon'] ) ? self::render_icon( $table['featuresIcon'] ) : false;
					$features_icon_position = isset( $table['featuresIconPosition'] ) ? $table['featuresIconPosition'] : 'left';

				foreach ( $features as $feature ) {
					echo '<li class="pricing-table-feature">';

					if ( $features_icon && $features_icon_position === 'left' ) {
						echo $features_icon;
					}

						echo '<span class="pricing-table-feature-title">' . $feature . '</span>';

					if ( $features_icon && $features_icon_position === 'right' ) {
						echo $features_icon;
					}

						echo '</li>';
				}

				echo '</ul>';
			}

			// Footer
			if ( ! empty( $table['buttonText'] ) || ! empty( $table['additionalInfo'] ) ) {
				echo '<div class="pricing-table-footer">';

				if ( isset( $table['buttonText'] ) ) {
					// Button
					$button_classes = [ 'bricks-button' ];

					if ( isset( $table['buttonStyle'] ) ) {
						$button_classes[] = 'bricks-background-' . $table['buttonStyle'];
					}

					if ( isset( $table['buttonSize'] ) ) {
						$button_classes[] = $table['buttonSize'];
					}

					if ( isset( $table['buttonCircle'] ) ) {
						$button_classes[] = 'circle';
					}

					// Link
					if ( isset( $table['buttonLink'] ) ) {
						$this->set_link_attributes( "button-$index", $table['buttonLink'] );
					}

					$this->set_attribute( "button-$index", 'class', $button_classes );

					echo '<div class="pricing-table-button-text">';
					echo "<a {$this->render_attributes( "button-$index" )}>{$this->render_dynamic_data( $table['buttonText'] )}</a>";
					echo '</div>';
				}

				if ( isset( $table['additionalInfo'] ) && ! empty( $table['additionalInfo'] ) ) {
					$this->set_attribute( "additional-info-$index", 'class', [ 'pricing-table-additional-info' ] );

					echo "<div {$this->render_attributes( "additional-info-$index" )}>{$this->render_dynamic_data( $table['additionalInfo'] )}</div>";
				}

				echo '</div>';
			}

			echo '</li>';
		}

		echo '</ul>';

		echo '</div>';
	}
}
