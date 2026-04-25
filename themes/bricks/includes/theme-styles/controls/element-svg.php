<?php
$controls = [];

$controls['height'] = [
	'label' => esc_html__( 'Height', 'bricks' ),
	'type'  => 'number',
	'units' => true,
	'css'   => [
		[
			'property' => 'height',
		]
	],
];

$controls['width'] = [
	'label' => esc_html__( 'Width', 'bricks' ),
	'type'  => 'number',
	'units' => true,
	'css'   => [
		[
			'property' => 'width',
		]
	],
];

$controls['strokeWidth'] = [
	'label' => esc_html__( 'Stroke width', 'bricks' ),
	'type'  => 'number',
	'min'   => 1,
	'css'   => [
		[
			'property'  => 'stroke-width',
			'selector'  => ' *',
			'important' => true,
		]
	],
];

$controls['stroke'] = [
	'label' => esc_html__( 'Stroke color', 'bricks' ),
	'type'  => 'color',
	'css'   => [
		[
			'property'  => 'stroke',
			'selector'  => ' *',
			'important' => true,
		]
	],
];

$controls['fill'] = [
	'label' => esc_html__( 'Fill', 'bricks' ),
	'type'  => 'color',
	'css'   => [
		[
			'property'  => 'fill',
			'selector'  => ' *',
			'important' => true,
		]
	],
];

return [
	'name'        => 'svg',
	'controls'    => $controls,
	'cssSelector' => '.brxe-svg',
];
