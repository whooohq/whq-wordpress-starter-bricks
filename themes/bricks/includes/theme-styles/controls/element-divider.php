<?php
$controls = [];

$controls['height'] = [
	'label'       => esc_html__( 'Height', 'bricks' ),
	'type'        => 'number',
	'units'       => true,
	'css'         => [
		[
			'property' => 'border-top-width',
			'selector' => '.line',
		],
	],
	'placeholder' => 1,
];

$controls['color'] = [
	'label' => esc_html__( 'Color', 'bricks' ),
	'type'  => 'color',
	'css'   => [
		[
			'property' => 'border-top-color',
			'selector' => '.line',
		],
		[
			'property' => 'color',
			'selector' => '.icon i',
		],
	],
];

return [
	'name'        => 'divider',
	'controls'    => $controls,
	'cssSelector' => '.brxe-divider',
];
