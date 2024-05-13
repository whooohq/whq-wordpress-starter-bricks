<?php
$controls = [];

$controls['background'] = [
	'label' => esc_html__( 'Table background', 'bricks' ),
	'type'  => 'background',
	'css'   => [
		[
			'property' => 'background',
			'selector' => '.pricing-table',
		],
	],
];

// Header

$controls['headerSeparator'] = [
	'type'  => 'separator',
	'label' => esc_html__( 'Header', 'bricks' ),
];

$controls['headerPadding'] = [
	'label' => esc_html__( 'Padding', 'bricks' ),
	'type'  => 'spacing',
	'css'   => [
		[
			'property' => 'padding',
			'selector' => '.pricing-table-header',
		],
	],
];

$controls['headerBackgroundColor'] = [
	'label' => esc_html__( 'Background color', 'bricks' ),
	'type'  => 'color',
	'css'   => [
		[
			'property' => 'background-color',
			'selector' => '.pricing-table-header',
		],
	],
];

$controls['headerBorder'] = [
	'label' => esc_html__( 'Border', 'bricks' ),
	'type'  => 'border',
	'css'   => [
		[
			'property' => 'border',
			'selector' => '.pricing-table-header',
		],
	],
];

$controls['headerTitleTypography'] = [
	'label' => esc_html__( 'Title typography', 'bricks' ),
	'type'  => 'typography',
	'css'   => [
		[
			'property' => 'font',
			'selector' => '.pricing-table-title',
		],
	],
];

$controls['headerSubtitleTypography'] = [
	'label' => esc_html__( 'Subtitle typography', 'bricks' ),
	'type'  => 'typography',
	'css'   => [
		[
			'property' => 'font',
			'selector' => '.pricing-table-subtitle',
		],
	],
];

// Pricing

$controls['priceSeparator'] = [
	'type'  => 'separator',
	'label' => esc_html__( 'Pricing', 'bricks' ),
];

$controls['pricePadding'] = [
	'label' => esc_html__( 'Padding', 'bricks' ),
	'type'  => 'spacing',
	'css'   => [
		[
			'property' => 'padding',
			'selector' => '.pricing-table-pricing',
		],
	],
];

$controls['priceBackgroundColor'] = [
	'label' => esc_html__( 'Background color', 'bricks' ),
	'type'  => 'color',
	'css'   => [
		[
			'property' => 'background-color',
			'selector' => '.pricing-table-pricing',
		],
	],
];

$controls['priceBorder'] = [
	'label' => esc_html__( 'Border', 'bricks' ),
	'type'  => 'border',
	'css'   => [
		[
			'property' => 'Border',
			'selector' => '.pricing-table-pricing',
		],
	],
];

$controls['priceTypography'] = [
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
];

$controls['priceMetaTypography'] = [
	'label' => esc_html__( 'Meta typography', 'bricks' ),
	'type'  => 'typography',
	'css'   => [
		[
			'property' => 'font',
			'selector' => '.pricing-table-price-meta',
		],
	],
];

$controls['priceOriginalTypography'] = [
	'label' => esc_html__( 'Original price typography', 'bricks' ),
	'type'  => 'typography',
	'css'   => [
		[
			'property' => 'font',
			'selector' => '.pricing-table-original-price',
		],
	],
];

// Features

$controls['featuresSeparator'] = [
	'type'  => 'separator',
	'label' => esc_html__( 'Features', 'bricks' ),
];

$controls['featuresPadding'] = [
	'label' => esc_html__( 'Padding', 'bricks' ),
	'type'  => 'spacing',
	'css'   => [
		[
			'property' => 'padding',
			'selector' => '.pricing-table-feature',
		],
	],
];

$controls['featuresIconColor'] = [
	'label' => esc_html__( 'Icon color', 'bricks' ),
	'type'  => 'color',
	'css'   => [
		[
			'property' => 'color',
			'selector' => '.pricing-table-feature i',
		],
	],
];

$controls['featuresBackgroundColor'] = [
	'label' => esc_html__( 'Background color', 'bricks' ),
	'type'  => 'color',
	'css'   => [
		[
			'property' => 'background-color',
			'selector' => '.pricing-table-feature',
		],
	],
];

$controls['featuresBorder'] = [
	'label' => esc_html__( 'Border', 'bricks' ),
	'type'  => 'border',
	'css'   => [
		[
			'property' => 'border',
			'selector' => '.pricing-table-feature',
		],
	],
];

$controls['featuresTypography'] = [
	'label' => esc_html__( 'Features typography', 'bricks' ),
	'type'  => 'typography',
	'css'   => [
		[
			'property' => 'font',
			'selector' => '.pricing-table-feature',
		],
	],
];

// Footer

$controls['footerSeparator'] = [
	'type'  => 'separator',
	'label' => esc_html__( 'Footer', 'bricks' ),
];

$controls['footerPadding'] = [
	'label' => esc_html__( 'Padding', 'bricks' ),
	'type'  => 'spacing',
	'css'   => [
		[
			'property' => 'padding',
			'selector' => '.pricing-table-footer',
		],
	],
];

$controls['footerBackgroundColor'] = [
	'label' => esc_html__( 'Background color', 'bricks' ),
	'type'  => 'color',
	'css'   => [
		[
			'property' => 'background-color',
			'selector' => '.pricing-table-footer',
		],
	],
];

$controls['footerBorder'] = [
	'label' => esc_html__( 'Border', 'bricks' ),
	'type'  => 'border',
	'css'   => [
		[
			'property' => 'border',
			'selector' => '.pricing-table-footer',
		],
	],
];

// Button

$controls['buttonSeparator'] = [
	'type'  => 'separator',
	'label' => esc_html__( 'Button', 'bricks' ),
];

$controls['buttonBackgroundColor'] = [
	'label' => esc_html__( 'Background color', 'bricks' ),
	'type'  => 'color',
	'css'   => [
		[
			'property' => 'background-color',
			'selector' => '.bricks-button',
		],
	],
];

$controls['buttonBorder'] = [
	'label' => esc_html__( 'Border', 'bricks' ),
	'type'  => 'border',
	'css'   => [
		[
			'property' => 'border',
			'selector' => '.bricks-button',
		],
	],
];

$controls['buttonTypography'] = [
	'label' => esc_html__( 'Typography', 'bricks' ),
	'type'  => 'typography',
	'css'   => [
		[
			'property' => 'font',
			'selector' => '.bricks-button',
		],
	],
];

// Additional Info

$controls['additionalInfoSeparator'] = [
	'type'  => 'separator',
	'label' => esc_html__( 'Additional info', 'bricks' ),
];

$controls['additionalInfoTypography'] = [
	'label' => esc_html__( 'Typography', 'bricks' ),
	'type'  => 'typography',
	'css'   => [
		[
			'property' => 'font',
			'selector' => '.pricing-table-additional-info',
		],
	],
];

// Ribbon

$controls['ribbonSeparator'] = [
	'type'  => 'separator',
	'label' => esc_html__( 'Ribbon', 'bricks' ),
];

$controls['ribbonTextColor'] = [
	'label' => esc_html__( 'Text color', 'bricks' ),
	'type'  => 'color',
	'css'   => [
		[
			'property' => 'color',
			'selector' => '.pricing-table-ribbon-title',
		],
	],
];

$controls['ribbonBackgroundColor'] = [
	'label' => esc_html__( 'Background color', 'bricks' ),
	'type'  => 'color',
	'css'   => [
		[
			'property' => 'background-color',
			'selector' => '.pricing-table-ribbon-title',
		],
	],
];

return [
	'name'        => 'pricing-tables',
	'controls'    => $controls,
	'cssSelector' => '.brxe-pricing-tables',
];
