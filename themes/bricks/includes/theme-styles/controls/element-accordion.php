<?php
$controls = [];

$controls['accordionIcon'] = [
	'group' => 'accordion',
	'type'  => 'icon',
	'label' => esc_html__( 'Icon', 'bricks' ),
];

$controls['accordionIconExpanded'] = [
	'group' => 'accordion',
	'type'  => 'icon',
	'label' => esc_html__( 'Icon expanded', 'bricks' ),
];

$controls['titleTypography'] = [
	'label' => esc_html__( 'Title typography', 'bricks' ),
	'type'  => 'typography',
	'css'   => [
		[
			'property' => 'font',
			'selector' => '.accordion-title .title',
		],
	],
];

$controls['subtitleTypography'] = [
	'label' => esc_html__( 'Subtitle typography', 'bricks' ),
	'type'  => 'typography',
	'css'   => [
		[
			'property' => 'font',
			'selector' => '.accordion-subtitle',
		],
	],
];

$controls['contentTypography'] = [
	'label' => esc_html__( 'Content typography', 'bricks' ),
	'type'  => 'typography',
	'css'   => [
		[
			'property' => 'font',
			'selector' => '.accordion-content-wrapper',
		],
	],
];

return [
	'name'        => 'accordion',
	'controls'    => $controls,
	'cssSelector' => '.brxe-accordion',
];
