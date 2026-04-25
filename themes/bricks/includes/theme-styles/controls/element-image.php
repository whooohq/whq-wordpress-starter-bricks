<?php
$controls = [];

// Icon

$controls['popupIcon'] = [
	'label' => esc_html__( 'Icon', 'bricks' ),
	'type'  => 'icon',
];

// NOTE: Set popup CSS control outside of control 'link' (CSS is not applied to nested controls)
$controls['popupIconBackgroundColor'] = [
	'label' => esc_html__( 'Icon background color', 'bricks' ),
	'type'  => 'color',
	'css'   => [
		[
			'property' => 'background-color',
			'selector' => '.icon',
		],
	],
];

$controls['popupIconBorder'] = [
	'label' => esc_html__( 'Icon border', 'bricks' ),
	'type'  => 'border',
	'css'   => [
		[
			'property' => 'border',
			'selector' => '.icon',
		],
	],
];

$controls['popupIconBoxShadow'] = [
	'label' => esc_html__( 'Icon box shadow', 'bricks' ),
	'type'  => 'box-shadow',
	'css'   => [
		[
			'property' => 'box-shadow',
			'selector' => '.icon',
		],
	],
];

$controls['popupIconHeight'] = [
	'label' => esc_html__( 'Icon height', 'bricks' ),
	'type'  => 'number',
	'units' => true,
	'css'   => [
		[
			'property' => 'line-height',
			'selector' => '.icon',
		],
	],
];

$controls['popupIconWidth'] = [
	'label' => esc_html__( 'Icon width', 'bricks' ),
	'type'  => 'number',
	'units' => true,
	'css'   => [
		[
			'property' => 'width',
			'selector' => '.icon',
		],
	],
];

$controls['popupIconTypography'] = [
	'label'    => esc_html__( 'Icon typography', 'bricks' ),
	'type'     => 'typography',
	'css'      => [
		[
			'property' => 'font',
			'selector' => '.icon',
		],
	],
	'exclude'  => [
		'font-family',
		'font-weight',
		'font-style',
		'text-align',
		'text-decoration',
		'text-transform',
		'line-height',
		'letter-spacing',
	],
	'required' => [ 'popupIcon.icon', '!=', '' ],
];

$controls['caption'] = [
	'label'       => esc_html__( 'Caption', 'bricks' ),
	'type'        => 'select',
	'options'     => [
		'none'       => esc_html__( 'No caption', 'bricks' ),
		'attachment' => esc_html__( 'Attachment', 'bricks' ),
	],
	'inline'      => true,
	'placeholder' => esc_html__( 'Attachment', 'bricks' ),
];

return [
	'name'        => 'image',
	'controls'    => $controls,
	'cssSelector' => '.brxe-image',
];
