<?php
$controls = [];

$controls['typography'] = [
	'label' => esc_html__( 'Typography', 'bricks' ),
	'type'  => 'typography',
	'css'   => [
		[
			'property' => 'font',
			'selector' => '',
		],
	],
];

return [
	'name'        => 'counter',
	'controls'    => $controls,
	'cssSelector' => '.brxe-counter',
];
