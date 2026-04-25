<?php
$controls = [];

$controls['margin'] = [
	'label' => esc_html__( 'Widget margin', 'bricks' ),
	'type'  => 'spacing',
	'css'   => [
		[
			'property' => 'margin',
			'selector' => '.bricks-widget-wrapper',
		],
	],
];

$controls['titleTypography'] = [
	'label' => esc_html__( 'Widget title', 'bricks' ),
	'type'  => 'typography',
	'css'   => [
		[
			'property' => 'font',
			'selector' => '.bricks-widget-title', // No longer working with new GB widgets
		],
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

$controls['contentTypography'] = [
	'tab'   => 'content',
	'label' => esc_html__( 'Content typography', 'bricks' ),
	'type'  => 'typography',
	'css'   => [
		[
			'property' => 'font',
		],
	],
];

$controls['searchBackground'] = [
	'label' => esc_html__( 'Search background color', 'bricks' ),
	'type'  => 'color',
	'css'   => [
		[
			'property' => 'background-color',
			'selector' => 'input[type=search]',
		],
	],
];

return [
	'name'        => 'sidebar',
	'controls'    => $controls,
	'cssSelector' => '.brxe-sidebar',
];
