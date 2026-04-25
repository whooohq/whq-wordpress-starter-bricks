<?php
$controls = [];

$controls['titleBorder'] = [
	'label' => esc_html__( 'Widget title border', 'bricks' ),
	'type'  => 'border',
	'css'   => [
		[
			'property' => 'border',
			'selector' => '.bricks-widget-title',
		],
	],
];

$controls['titleTypography'] = [
	'label' => esc_html__( 'Widget title typography', 'bricks' ),
	'type'  => 'typography',
	'css'   => [
		[
			'property' => 'font',
			'selector' => '.bricks-widget-title',
		],
	],
];

$controls['contentTypography'] = [
	'label' => esc_html__( 'Content typography', 'bricks' ),
	'type'  => 'typography',
	'css'   => [
		[
			'property' => 'font',
			'selector' => 'ul',
		],
	],
];

$controls['postsTitleTypography'] = [
	'label' => esc_html__( 'Post title typography', 'bricks' ),
	'type'  => 'typography',
	'css'   => [
		[
			'property' => 'font',
			'selector' => '.post-title',
		],
	],
];

$controls['postsMetaTypography'] = [
	'label' => esc_html__( 'Post meta typography', 'bricks' ),
	'type'  => 'typography',
	'css'   => [
		[
			'property' => 'font',
			'selector' => '.post-meta',
		],
	],
];

return [
	'name'        => 'wordpress',
	'controls'    => $controls,
	'cssSelector' => '.brxe-wordpress',
];
