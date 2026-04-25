<?php
$controls = [];

$controls['titleTypography'] = [
	'label' => esc_html__( 'Title typography', 'bricks' ),
	'type'  => 'typography',
	'css'   => [
		[
			'property' => 'font',
			'selector' => '.title',
		],
	],
];

$controls['labelTypography'] = [
	'label' => esc_html__( 'Label typography', 'bricks' ),
	'type'  => 'typography',
	'css'   => [
		[
			'property' => 'font',
			'selector' => 'label',
		],
	],
];

$controls['imageBorder'] = [
	'label' => esc_html__( 'Image border', 'bricks' ),
	'type'  => 'border',
	'css'   => [
		[
			'property' => 'border',
			'selector' => '.image',
		],
	],
];

return [
	'name'        => 'post-navigation',
	'controls'    => $controls,
	'cssSelector' => '.brxe-post-navigation',
];
