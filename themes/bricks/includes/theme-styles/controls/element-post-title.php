<?php
$controls = [];

$controls['typography'] = [
	'label' => esc_html__( 'Typography', 'bricks' ),
	'type'  => 'typography',
	'css'   => [
		[
			'property' => 'font',
		],
	],
];

return [
	'name'        => 'post-title',
	'controls'    => $controls,
	'cssSelector' => '.brxe-post-title',
];
