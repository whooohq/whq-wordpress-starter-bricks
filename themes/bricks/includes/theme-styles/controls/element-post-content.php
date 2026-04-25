<?php
$controls = [];

$controls['typography'] = [
	'label' => esc_html__( 'Typography', 'bricks' ),
	'type'  => 'typography',
	'css'   => [
		[
			'property' => 'font',
			'selector' => '.brxe-post-content',
		],
	],
];

return [
	'name'     => 'post-content',
	'controls' => $controls,
];
