<?php
$controls = [];

$controls['contentBackground'] = [
	'label' => esc_html__( 'Content background', 'bricks' ),
	'type'  => 'color',
	'css'   => [
		[
			'property' => 'background-color',
			'selector' => '.post-content',
		],
	],
];

$controls['contentPadding'] = [
	'label' => esc_html__( 'Content padding', 'bricks' ),
	'type'  => 'spacing',
	'css'   => [
		[
			'property' => 'padding',
			'selector' => '.post-content',
		],
	],
];

return [
	'name'        => 'related-posts',
	'controls'    => $controls,
	'cssSelector' => '.brxe-related-posts',
];
