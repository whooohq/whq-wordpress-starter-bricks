<?php
$controls = [];

// Title

$controls['titleSeparator'] = [
	'type'  => 'separator',
	'label' => esc_html__( 'Title', 'bricks' ),
];

$controls['titlePadding'] = [
	'label' => esc_html__( 'Padding', 'bricks' ),
	'type'  => 'spacing',
	'css'   => [
		[
			'property' => 'padding',
			'selector' => '.tab-title',
		],
	],
];

$controls['titleTypography'] = [
	'label' => esc_html__( 'Typography', 'bricks' ),
	'type'  => 'typography',
	'css'   => [
		[
			'property' => 'font',
			'selector' => '.tab-title',
		],
	],
];

$controls['titleBackgroundColor'] = [
	'label' => esc_html__( 'Background', 'bricks' ),
	'type'  => 'color',
	'css'   => [
		[
			'property' => 'background-color',
			'selector' => '.tab-title',
		],
	],
];

$controls['titleBorder'] = [
	'label' => esc_html__( 'Border', 'bricks' ),
	'type'  => 'border',
	'css'   => [
		[
			'property' => 'border',
			'selector' => '.tab-title',
		],
	],
];

$controls['titleActiveTypography'] = [
	'label' => esc_html__( 'Active typography', 'bricks' ),
	'type'  => 'typography',
	'css'   => [
		[
			'property' => 'font',
			'selector' => '.tab-title.brx-open',
		],
	],
];

$controls['titleActiveBackgroundColor'] = [
	'label' => esc_html__( 'Active background', 'bricks' ),
	'type'  => 'color',
	'css'   => [
		[
			'property' => 'background-color',
			'selector' => '.tab-title.brx-open',
		],
	],
];

$controls['titleActiveBorder'] = [
	'label' => esc_html__( 'Active border', 'bricks' ),
	'type'  => 'border',
	'css'   => [
		[
			'property' => 'border',
			'selector' => '.tab-title.brx-open',
		],
	],
];

// Content

$controls['contentSeparator'] = [
	'type'  => 'separator',
	'label' => esc_html__( 'Content', 'bricks' ),
];

$controls['contentPadding'] = [
	'label' => esc_html__( 'Padding', 'bricks' ),
	'type'  => 'spacing',
	'css'   => [
		[
			'property' => 'padding',
			'selector' => '.tab-content',
		],
	],
];

$controls['contentTextAlign'] = [
	'label'   => esc_html__( 'Text align', 'bricks' ),
	'type'    => 'select',
	'options' => [
		'left'    => esc_html__( 'Left', 'bricks' ),
		'center'  => esc_html__( 'Center', 'bricks' ),
		'right'   => esc_html__( 'Right', 'bricks' ),
		'justify' => esc_html__( 'Justify', 'bricks' ),
	],
	'css'     => [
		[
			'property' => 'text-align',
			'selector' => '.tab-content',
		],
	],
	'inline'  => true,
];

$controls['contentColor'] = [
	'label' => esc_html__( 'Text color', 'bricks' ),
	'type'  => 'color',
	'css'   => [
		[
			'property' => 'color',
			'selector' => '.tab-content',
		],
	],
];

$controls['contentBackgroundColor'] = [
	'label' => esc_html__( 'Background color', 'bricks' ),
	'type'  => 'color',
	'css'   => [
		[
			'property' => 'background-color',
			'selector' => '.tab-content',
		],
	],
];

$controls['contentBorder'] = [
	'label' => esc_html__( 'Border', 'bricks' ),
	'type'  => 'border',
	'css'   => [
		[
			'property' => 'border',
			'selector' => '.tab-content',
		],
	],
];

return [
	'name'        => 'tabs',
	'controls'    => $controls,
	'cssSelector' => '.brxe-tabs',
];
