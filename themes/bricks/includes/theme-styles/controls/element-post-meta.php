<?php
$controls = [];

$controls['padding'] = [
	'label' => esc_html__( 'Padding', 'bricks' ),
	'type'  => 'spacing',
	'css'   => [
		[
			'property' => 'padding',
			'selector' => '.item',
		],
	],
];

$controls['gutter'] = [
	'label'       => esc_html__( 'Gap', 'bricks' ),
	'type'        => 'number',
	'units'       => true,
	'css'         => [
		[
			'property' => 'width',
			'selector' => '.separator',
		],
	],
	'inline'      => true,
	'small'       => true,
	'placeholder' => 20,
];

$controls['background'] = [
	'label' => esc_html__( 'Background', 'bricks' ),
	'type'  => 'color',
	'css'   => [
		[
			'property' => 'background-color',
			'selector' => '.item',
		],
	],
];

$controls['border'] = [
	'label' => esc_html__( 'Border', 'bricks' ),
	'type'  => 'border',
	'css'   => [
		[
			'property' => 'border',
			'selector' => '.item',
		],
	],
];

$controls['typography'] = [
	'label' => esc_html__( 'Typography', 'bricks' ),
	'type'  => 'typography',
	'css'   => [
		[
			'property' => 'font',
			'selector' => '.item',
		],
	],
];

return [
	'name'        => 'post-meta',
	'controls'    => $controls,
	'cssSelector' => '.brxe-post-meta',
];
