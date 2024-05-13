<?php
$controls = [];

// NOTE: Not working with "External files"
// $controls['direction'] = [
// 'tab'      => 'content',
// 'label'    => esc_html__( 'Direction', 'bricks' ),
// 'type'     => 'direction',
// 'css'      => [
// [
// 'property' => 'flex-direction',
// ],
// ],
// 'inline'   => true,
// 'rerender' => true,
// ];

$controls['verticalAlign'] = [
	'tab'          => 'content',
	'group'        => 'icon',
	'label'        => esc_html__( 'Icon align', 'bricks' ),
	'type'         => 'align-items',
	'exclude'      => 'stretch',
	'css'          => [
		[
			'property' => 'align-self',
			'selector' => '.icon',
		],
	],
	'inline'       => true,
	'isHorizontal' => false,
];

// Icon

$controls['iconMargin'] = [
	'label' => esc_html__( 'Icon margin', 'bricks' ),
	'type'  => 'spacing',
	'css'   => [
		[
			'property' => 'margin',
			'selector' => '.icon',
		],
	],
];

$controls['iconPadding'] = [
	'label' => esc_html__( 'Icon padding', 'bricks' ),
	'type'  => 'spacing',
	'css'   => [
		[
			'property' => 'padding',
			'selector' => '.icon',
		],
	],
];

$controls['textAlign'] = [
	'deprecated' => true, // No longer needed with 'direction' setting
	'type'       => 'text-align',
	'label'      => esc_html__( 'Text align', 'bricks' ),
	'css'        => [
		[
			'property' => 'text-align',
		],
		[
			'property' => 'align-self',
			'selector' => '.icon',
		],
	],
	'inline'     => true,
];

$controls['iconSize'] = [
	'label' => esc_html__( 'Icon size', 'bricks' ),
	'type'  => 'number',
	'units' => true,
	'css'   => [
		[
			'property' => 'font-size',
			'selector' => '.icon i',
		],
	],
];

$controls['iconHeight'] = [
	'label' => esc_html__( 'Icon height', 'bricks' ),
	'type'  => 'number',
	'units' => true,
	'css'   => [
		[
			'property' => 'height',
			'selector' => '.icon',
		],
		[
			'property' => 'line-height',
			'selector' => '.icon',
		],
	],
];

$controls['iconWidth'] = [
	'label' => esc_html__( 'Icon width', 'bricks' ),
	'type'  => 'number',
	'units' => true,
	'css'   => [
		[
			'property' => 'min-width',
			'selector' => '.icon',
		],
	],
];

$controls['iconColor'] = [
	'label' => esc_html__( 'Icon color', 'bricks' ),
	'type'  => 'color',
	'css'   => [
		[
			'property' => 'color',
			'selector' => '.icon',
		],
		[
			'property' => 'color',
			'selector' => '.icon a',
		],
	],
];

$controls['iconBackgroundColor'] = [
	'label' => esc_html__( 'Icon background', 'bricks' ),
	'type'  => 'color',
	'css'   => [
		[
			'property' => 'background-color',
			'selector' => '.icon',
		],
	],
];

$controls['iconBorder'] = [
	'label' => esc_html__( 'Icon border', 'bricks' ),
	'type'  => 'border',
	'css'   => [
		[
			'property' => 'border',
			'selector' => '.icon',
		],
	],
];

$controls['iconBoxShadow'] = [
	'label' => esc_html__( 'Icon box shadow', 'bricks' ),
	'type'  => 'box-shadow',
	'css'   => [
		[
			'property' => 'box-shadow',
			'selector' => '.icon',
		],
	],
];

// Content

$controls['contentSeparator'] = [
	'type'  => 'separator',
	'label' => esc_html__( 'Content', 'bricks' ),
];

$controls['typographyHeading'] = [
	'type'  => 'typography',
	'label' => esc_html__( 'Heading typography', 'bricks' ),
	'css'   => [
		[
			'property' => 'font',
			'selector' => 'h1',
		],
		[
			'property' => 'font',
			'selector' => 'h2',
		],
		[
			'property' => 'font',
			'selector' => 'h3',
		],
		[
			'property' => 'font',
			'selector' => 'h4',
		],
		[
			'property' => 'font',
			'selector' => 'h5',
		],
		[
			'property' => 'font',
			'selector' => 'h6',
		],
	],
];

$controls['typographyBody'] = [
	'type'  => 'typography',
	'label' => esc_html__( 'Body typography', 'bricks' ),
	'css'   => [
		[
			'property' => 'font',
			'selector' => '.content',
		],
	],
];

$controls['contentBackgroundColor'] = [
	'tab'   => 'style',
	'group' => 'content',
	'type'  => 'color',
	'label' => esc_html__( 'Content background', 'bricks' ),
	'css'   => [
		[
			'property' => 'background-color',
			'selector' => '.content',
		],
	],
];

$controls['contentBorder'] = [
	'type'  => 'border',
	'label' => esc_html__( 'Content border', 'bricks' ),
	'css'   => [
		[
			'property' => 'border',
			'selector' => '.content',
		],
	],
];

$controls['contentBoxShadow'] = [
	'type'  => 'box-shadow',
	'label' => esc_html__( 'Content box shadow', 'bricks' ),
	'css'   => [
		[
			'property' => 'box-shadow',
			'selector' => '.content',
		],
	],
];

$controls['contentMargin'] = [
	'type'  => 'spacing',
	'label' => esc_html__( 'Content margin', 'bricks' ),
	'css'   => [
		[
			'property' => 'margin',
			'selector' => '.content',
		],
	],
];

$controls['contentPadding'] = [
	'type'  => 'spacing',
	'label' => esc_html__( 'Content padding', 'bricks' ),
	'css'   => [
		[
			'property' => 'padding',
			'selector' => '.content',
		],
	],
];

return [
	'name'        => 'icon-box',
	'controls'    => $controls,
	'cssSelector' => '.brxe-icon-box',
];
