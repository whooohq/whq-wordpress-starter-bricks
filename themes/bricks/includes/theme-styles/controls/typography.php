<?php
$controls = [];

$controls['typographyHtml'] = [
	'type'        => 'number',
	'units'       => true,
	'label'       => 'HTML: font-size',
	'css'         => [
		[
			'property' => 'font-size',
			'selector' => 'html',
		],
	],
	'placeholder' => '62.5%',
	'info'        => "62.5% html font-size: 1rem = 10px\n100% html font-size: 1rem = 16px",
];

$controls['typographyBody'] = [
	'type'        => 'typography',
	'label'       => esc_html__( 'Body', 'bricks' ),
	'css'         => [
		[
			'property' => 'font',
			'selector' => 'body',
		],
	],
	'placeholder' => [
		'font-size'   => '15px',
		'line-height' => '-',
	],
];

$controls['typographyHeadings'] = [
	'type'  => 'typography',
	'label' => esc_html__( 'All headings', 'bricks' ),
	'css'   => [
		[
			'property' => 'font',
			'selector' => 'h1, h2, h3, h4, h5, h6',
		],
	],
];

// Heading: H1

$controls['headingH1Separator'] = [
	'type'  => 'separator',
	'label' => esc_html__( 'Heading', 'bricks' ) . ' H1',
];

$controls['typographyHeadingH1'] = [
	'label' => esc_html__( 'Typography', 'bricks' ),
	'type'  => 'typography',
	'css'   => [
		[
			'property' => 'font',
			'selector' => 'h1',
		],
	],
];

$controls['h1Margin'] = [
	'label'       => esc_html__( 'Margin', 'bricks' ),
	'type'        => 'spacing',
	'css'         => [
		[
			'property' => 'margin',
			'selector' => 'h1',
		],
	],
	'placeholder' => [
		'top'    => 0,
		'right'  => 0,
		'bottom' => 0,
		'left'   => 0,
	],
];

// Heading: H2

$controls['headingH2Separator'] = [
	'type'  => 'separator',
	'label' => esc_html__( 'Heading', 'bricks' ) . ' H2',
];

$controls['typographyHeadingH2'] = [
	'label' => esc_html__( 'Typography', 'bricks' ),
	'type'  => 'typography',
	'css'   => [
		[
			'property' => 'font',
			'selector' => 'h2',
		],
	],
];

$controls['h2Margin'] = [
	'label'       => esc_html__( 'Margin', 'bricks' ),
	'type'        => 'spacing',
	'css'         => [
		[
			'property' => 'margin',
			'selector' => 'h2',
		],
	],
	'placeholder' => [
		'top'    => 0,
		'right'  => 0,
		'bottom' => 0,
		'left'   => 0,
	],
];

// Heading: H3

$controls['headingH3Separator'] = [
	'type'  => 'separator',
	'label' => esc_html__( 'Heading', 'bricks' ) . ' H3',
];

$controls['typographyHeadingH3'] = [
	'label' => esc_html__( 'Typography', 'bricks' ),
	'type'  => 'typography',
	'css'   => [
		[
			'property' => 'font',
			'selector' => 'h3',
		],
	],
];

$controls['h3Margin'] = [
	'label'       => esc_html__( 'Margin', 'bricks' ),
	'type'        => 'spacing',
	'css'         => [
		[
			'property' => 'margin',
			'selector' => 'h3',
		],
	],
	'placeholder' => [
		'top'    => 0,
		'right'  => 0,
		'bottom' => 0,
		'left'   => 0,
	],
];

// Heading: H4

$controls['headingH4Separator'] = [
	'type'  => 'separator',
	'label' => esc_html__( 'Heading', 'bricks' ) . ' H4',
];

$controls['typographyHeadingH4'] = [
	'label' => esc_html__( 'Typography', 'bricks' ),
	'type'  => 'typography',
	'css'   => [
		[
			'property' => 'font',
			'selector' => 'h4',
		],
	],
];

$controls['h4Margin'] = [
	'label'       => esc_html__( 'Margin', 'bricks' ),
	'type'        => 'spacing',
	'css'         => [
		[
			'property' => 'margin',
			'selector' => 'h4',
		],
	],
	'placeholder' => [
		'top'    => 0,
		'right'  => 0,
		'bottom' => 0,
		'left'   => 0,
	],
];

// Heading: H5

$controls['headingH5Separator'] = [
	'type'  => 'separator',
	'label' => esc_html__( 'Heading', 'bricks' ) . ' H5',
];

$controls['typographyHeadingH5'] = [
	'label' => esc_html__( 'Typography', 'bricks' ),
	'type'  => 'typography',
	'css'   => [
		[
			'property' => 'font',
			'selector' => 'h5',
		],
	],
];

$controls['h5Margin'] = [
	'label'       => esc_html__( 'Margin', 'bricks' ),
	'type'        => 'spacing',
	'css'         => [
		[
			'property' => 'margin',
			'selector' => 'h5',
		],
	],
	'placeholder' => [
		'top'    => 0,
		'right'  => 0,
		'bottom' => 0,
		'left'   => 0,
	],
];

// Heading: H6

$controls['headingH6Separator'] = [
	'type'  => 'separator',
	'label' => esc_html__( 'Heading', 'bricks' ) . ' H6',
];

$controls['typographyHeadingH6'] = [
	'label' => esc_html__( 'Typography', 'bricks' ),
	'type'  => 'typography',
	'css'   => [
		[
			'property' => 'font',
			'selector' => 'h6',
		],
	],
];

$controls['h6Margin'] = [
	'label'       => esc_html__( 'Margin', 'bricks' ),
	'type'        => 'spacing',
	'css'         => [
		[
			'property' => 'margin',
			'selector' => 'h6',
		],
	],
	'placeholder' => [
		'top'    => 0,
		'right'  => 0,
		'bottom' => 0,
		'left'   => 0,
	],
];

// MISC

$controls['miscSeparator'] = [
	'type'  => 'separator',
	'label' => esc_html__( 'Miscellaneous', 'bricks' ),
];

$controls['typographyHero'] = [
	'type'  => 'typography',
	'label' => esc_html__( 'Hero', 'bricks' ),
	'css'   => [
		[
			'property' => 'font',
			'selector' => '.bricks-type-hero',
		]
	],
];

$controls['typographyLead'] = [
	'type'  => 'typography',
	'label' => esc_html__( 'Lead', 'bricks' ),
	'css'   => [
		[
			'property' => 'font',
			'selector' => '.bricks-type-lead',
		],
	],
];

$controls['focusOutline'] = [
	'label'          => esc_html__( 'Focus outline', 'bricks' ),
	'type'           => 'text',
	'css'            => [
		[
			'property' => 'outline',
			'selector' => 'body.bricks-is-frontend :focus',
		],
	],
	'placeholder'    => 'thin dotted currentColor',
	'inline'         => true,
	'hasDynamicData' => false,
];

// BLOCKQUOTE

$controls['blockquoteSeparator'] = [
	'type'  => 'separator',
	'label' => esc_html__( 'Blockquote', 'bricks' ),
];

$controls['blockquoteMargin'] = [
	'type'  => 'spacing',
	'label' => esc_html__( 'Blockquote margin', 'bricks' ),
	'css'   => [
		[
			'property' => 'margin',
			'selector' => 'blockquote',
		],
	],
];

$controls['blockquotePadding'] = [
	'type'  => 'spacing',
	'label' => esc_html__( 'Blockquote padding', 'bricks' ),
	'css'   => [
		[
			'property' => 'padding',
			'selector' => 'blockquote',
		],
	],
];

$controls['blockquoteBorder'] = [
	'type'  => 'border',
	'label' => esc_html__( 'Blockquote border', 'bricks' ),
	'css'   => [
		[
			'property' => 'border',
			'selector' => 'blockquote',
		],
	],
];

$controls['blockquoteTypography'] = [
	'type'  => 'typography',
	'label' => esc_html__( 'Blockquote typography', 'bricks' ),
	'css'   => [
		[
			'property' => 'font',
			'selector' => 'blockquote',
		],
	],
];

return [
	'name'     => 'typography',
	'controls' => $controls,
];
