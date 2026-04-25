<?php
$controls = [];

// Member

$controls['memberSeparator'] = [
	'type'  => 'separator',
	'label' => esc_html__( 'Member', 'bricks' ),
];

$controls['memberGutter'] = [
	'tab'   => 'content',
	'group' => 'member',
	'label' => esc_html__( 'Gap', 'bricks' ),
	'type'  => 'number',
	'units' => true,
	'css'   => [
		[
			'property' => 'gap',
		],
	],
];

$controls['memberBorder'] = [
	'tab'   => 'content',
	'group' => 'member',
	'label' => esc_html__( 'Border', 'bricks' ),
	'type'  => 'border',
	'css'   => [
		[
			'property' => 'border',
			'selector' => '.member',
		],
	],
];

$controls['memberBoxShadow'] = [
	'tab'   => 'content',
	'group' => 'member',
	'label' => esc_html__( 'Box shadow', 'bricks' ),
	'type'  => 'box-shadow',
	'css'   => [
		[
			'property' => 'box-shadow',
			'selector' => '.member',
		],
	],
];

$controls['memberTitleTypography'] = [
	'tab'   => 'content',
	'group' => 'member',
	'label' => esc_html__( 'Title typography', 'bricks' ),
	'type'  => 'typography',
	'css'   => [
		[
			'property' => 'font',
			'selector' => '.title',
		]
	],
];

$controls['memberSubtitleTypography'] = [
	'tab'   => 'content',
	'group' => 'member',
	'label' => esc_html__( 'Subtitle typography', 'bricks' ),
	'type'  => 'typography',
	'css'   => [
		[
			'property' => 'font',
			'selector' => '.subtitle',
		]
	],
];

$controls['memberDescriptionTypography'] = [
	'tab'   => 'content',
	'group' => 'member',
	'label' => esc_html__( 'Description typography', 'bricks' ),
	'type'  => 'typography',
	'css'   => [
		[
			'property' => 'font',
			'selector' => '.description',
		]
	],
];

// Image

$controls['imageSeparator'] = [
	'type'  => 'separator',
	'label' => esc_html__( 'Image', 'bricks' ),
];

$controls['imageBorder'] = [
	'tab'   => 'content',
	'group' => 'image',
	'label' => esc_html__( 'Image border', 'bricks' ),
	'type'  => 'border',
	'css'   => [
		[
			'property' => 'border',
			'selector' => '.image',
		],
	],
];

// Content

$controls['contentSeparator'] = [
	'type'  => 'separator',
	'label' => esc_html__( 'Content', 'bricks' ),
];

$controls['contentPadding'] = [
	'label' => esc_html__( 'Padding', 'bricks' ),
	'type'  => 'spacing',
	'css'   => [
		[
			'property' => 'padding',
			'selector' => '.content',
		],
	],
];

$controls['contentAlign'] = [
	'label'   => esc_html__( 'Text align', 'bricks' ),
	'type'    => 'select',
	'options' => [
		'left'   => esc_html__( 'Left', 'bricks' ),
		'center' => esc_html__( 'Center', 'bricks' ),
		'right'  => esc_html__( 'Right', 'bricks' ),
	],
	'css'     => [
		[
			'property' => 'text-align',
			'selector' => '.content',
		],
	],
	'inline'  => true,
];

$controls['contentBackgroundColor'] = [
	'label' => esc_html__( 'Background', 'bricks' ),
	'type'  => 'color',
	'css'   => [
		[
			'property' => 'background-color',
			'selector' => '.member',
		],
	],
];

return [
	'name'        => 'team-members',
	'controls'    => $controls,
	'cssSelector' => '.brxe-team-members',
];
