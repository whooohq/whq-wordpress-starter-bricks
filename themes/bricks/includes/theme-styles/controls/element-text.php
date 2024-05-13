<?php
$controls = [];

$controls['typography'] = [
	'type'  => 'typography',
	'label' => esc_html__( 'Typography', 'bricks' ),
	'css'   => [
		[
			'property' => 'font',
			'selector' => '.brxe-text',
		],
		[
			'property' => 'font',
			'selector' => '.brxe-text-basic',
		],
	],
];

return [
	'name'     => 'text',
	'controls' => $controls,
];
