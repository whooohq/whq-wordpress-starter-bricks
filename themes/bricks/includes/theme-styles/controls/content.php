<?php
$controls = [];

$controls['contentMargin'] = [
	'type'        => 'spacing',
	'label'       => esc_html__( 'Margin', 'bricks' ),
	'css'         => [
		[
			'property' => 'margin',
			'selector' => '#brx-content',
		],
		// WooCommerce
		[
			'property' => 'margin',
			'selector' => '.content-area',
		],
	],
	'description' => esc_html__( 'Space between header and footer.', 'bricks' ),
];

// @since 1.4 deprecated
$controls['contentBlockquoteSeparator'] = [
	'type'       => 'separator',
	'label'      => esc_html__( 'Blockquote', 'bricks' ),
	'deprecated' => true,
];

$controls['contentBlockquoteMargin'] = [
	'type'       => 'spacing',
	'label'      => esc_html__( 'Margin', 'bricks' ),
	'css'        => [
		[
			'property' => 'margin',
			'selector' => 'blockquote',
		],
	],
	'deprecated' => true,
];

$controls['contentBlockquotePadding'] = [
	'type'       => 'spacing',
	'label'      => esc_html__( 'Padding', 'bricks' ),
	'css'        => [
		[
			'property' => 'padding',
			'selector' => 'blockquote',
		],
	],
	'deprecated' => true,
];

$controls['contentBlockquoteBorder'] = [
	'type'       => 'border',
	'label'      => esc_html__( 'Border', 'bricks' ),
	'css'        => [
		[
			'property' => 'border',
			'selector' => 'blockquote',
		],
	],
	'deprecated' => true,
];

$controls['contentBlockquoteTypography'] = [
	'type'       => 'typography',
	'label'      => esc_html__( 'Typography', 'bricks' ),
	'css'        => [
		[
			'property' => 'font',
			'selector' => 'blockquote',
		],
	],
	'deprecated' => true,
];

return [
	'name'     => 'content',
	'controls' => $controls,
];
