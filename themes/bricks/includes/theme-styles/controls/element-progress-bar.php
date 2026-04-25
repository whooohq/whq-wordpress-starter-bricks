<?php
$controls = [];

$controls['height'] = [
	'label'       => esc_html__( 'Height', 'bricks' ),
	'type'        => 'number',
	'units'       => true,
	'css'         => [
		[
			'property' => 'height',
			'selector' => '.bar',
		]
	],
	'placeholder' => 8,
];

$controls['barColor'] = [
	'label' => esc_html__( 'Bar color', 'bricks' ),
	'type'  => 'color',
	'css'   => [
		[
			'property' => 'background-color',
			'selector' => '.bar span',
		],
	],
];

$controls['barBackgroundColor'] = [
	'label' => esc_html__( 'Bar background color', 'bricks' ),
	'type'  => 'color',
	'css'   => [
		[
			'property' => 'background-color',
			'selector' => '.bar',
		],
	],
];

$controls['barBorder'] = [
	'label' => esc_html__( 'Bar border', 'bricks' ),
	'type'  => 'border',
	'css'   => [
		[
			'property' => 'border',
			'selector' => '.bar',
		],
	],
];

$controls['labelTypography'] = [
	'label' => esc_html__( 'Label typography', 'bricks' ),
	'type'  => 'typography',
	'css'   => [
		[
			'property' => 'font',
			'selector' => '.label',
		],
	],
];

$controls['percentageTypography'] = [
	'label' => esc_html__( 'Percentage typography', 'bricks' ),
	'type'  => 'typography',
	'css'   => [
		[
			'property' => 'font',
			'selector' => '.percentage',
		],
	],
];

return [
	'name'        => 'progress-bar',
	'controls'    => $controls,
	'cssSelector' => '.brxe-progress-bar',
];
