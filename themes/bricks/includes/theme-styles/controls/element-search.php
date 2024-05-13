<?php
$controls = [];

$controls['inputBackgroundColor'] = [
	'label' => esc_html__( 'Input background', 'bricks' ),
	'type'  => 'color',
	'css'   => [
		[
			'property' => 'background-color',
			'selector' => 'input[type=search]',
		],
	],
];

$controls['inputBorder'] = [
	'label' => esc_html__( 'Input border', 'bricks' ),
	'type'  => 'border',
	'css'   => [
		[
			'property' => 'border',
			'selector' => 'input[type=search]',
		],
	],
];

$controls['iconBackgroundColor'] = [
	'label' => esc_html__( 'Icon background', 'bricks' ),
	'type'  => 'color',
	'css'   => [
		[
			'property' => 'background-color',
			'selector' => 'button',
		],
	],
];

$controls['iconTypography'] = [
	'label'   => esc_html__( 'Icon typography', 'bricks' ),
	'type'    => 'typography',
	'css'     => [
		[
			'property' => 'font',
			'selector' => 'button',
		],
	],
	'exclude' => [
		'font-family',
		'font-weight',
		'font-style',
		'text-align',
		'text-decoration',
		'text-transform',
		'letter-spacing',
	],
];

$controls['iconWidth'] = [
	'label' => esc_html__( 'Icon width', 'bricks' ),
	'type'  => 'number',
	'units' => true,
	'css'   => [
		[
			'property' => 'width',
			'selector' => 'button',
		],
	],
];

return [
	'name'        => 'search',
	'controls'    => $controls,
	'cssSelector' => '.brxe-search',
];
