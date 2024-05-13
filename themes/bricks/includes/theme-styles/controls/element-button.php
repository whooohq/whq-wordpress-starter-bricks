<?php
$controls = [];

// Default

$controls['defaultSeparator'] = [
	'type'  => 'separator',
	'label' => esc_html__( 'Style - Default', 'bricks' ),
];

$controls['typography'] = [
	'type'  => 'typography',
	'label' => esc_html__( 'Typography', 'bricks' ),
	'css'   => [
		[
			'property' => 'font',
			'selector' => '.bricks-button',
		],
	],
];

$controls['background'] = [
	'type'  => 'color',
	'label' => esc_html__( 'Background color', 'bricks' ),
	'css'   => [
		[
			'property' => 'background-color',
			'selector' => '.bricks-button:not([class*="bricks-background-"]):not([class*="bricks-color-"])',
		],
	],
];

$controls['border'] = [
	'type'  => 'border',
	'label' => esc_html__( 'Border', 'bricks' ),
	'css'   => [
		[
			'property' => 'border',
			'selector' => '.bricks-button',
		],
	],
];

$controls['boxShadow'] = [
	'type'  => 'box-shadow',
	'label' => esc_html__( 'Box shadow', 'bricks' ),
	'css'   => [
		[
			'property' => 'box-shadow',
			'selector' => '.bricks-button',
		],
	],
];

$controls['transition'] = [
	'label'       => esc_html__( 'Transition', 'bricks' ),
	'css'         => [
		[
			'property' => 'transition',
			'selector' => '.bricks-button',
		],
	],
	'type'        => 'text',
	'description' => sprintf( '<a href="https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_Transitions/Using_CSS_transitions" target="_blank">%s</a>', esc_html__( 'Learn more about CSS transitions', 'bricks' ) ),
];

// Primary

$controls['primarySeparator'] = [
	'type'  => 'separator',
	'label' => esc_html__( 'Style - Primary', 'bricks' ),
];

$controls['primaryTypography'] = [
	'type'  => 'typography',
	'label' => esc_html__( 'Typography', 'bricks' ),
	'css'   => [
		[
			'property' => 'font',
			'selector' => ':root .bricks-button[class*="primary"]',
		],
	],
];

$controls['primaryBackground'] = [
	'type'  => 'color',
	'label' => esc_html__( 'Background color', 'bricks' ),
	'css'   => [
		[
			'property' => 'background-color',
			'selector' => ':root .bricks-button[class*="primary"]:not(.outline)',
		],
	],
];

$controls['primaryBorder'] = [
	'type'  => 'border',
	'label' => esc_html__( 'Border', 'bricks' ),
	'css'   => [
		[
			'property' => 'border',
			'selector' => ':root .bricks-button[class*="primary"]',
		],
	],
];

$controls['primaryBoxShadow'] = [
	'type'  => 'box-shadow',
	'label' => esc_html__( 'Box shadow', 'bricks' ),
	'css'   => [
		[
			'property' => 'box-shadow',
			'selector' => ':root .bricks-button[class*="primary"]',
		],
	],
];

// Secondary

$controls['secondarySeparator'] = [
	'type'  => 'separator',
	'label' => esc_html__( 'Style - Secondary', 'bricks' ),
];

$controls['secondaryTypography'] = [
	'type'  => 'typography',
	'label' => esc_html__( 'Typography', 'bricks' ),
	'css'   => [
		[
			'property' => 'font',
			'selector' => ':root .bricks-button[class*="secondary"]',
		],
	],
];

$controls['secondaryBackground'] = [
	'type'  => 'color',
	'label' => esc_html__( 'Background color', 'bricks' ),
	'css'   => [
		[
			'property' => 'background-color',
			'selector' => ':root .bricks-button[class*="secondary"]:not(.outline)',
		],
	],
];

$controls['secondaryBorder'] = [
	'type'  => 'border',
	'label' => esc_html__( 'Border', 'bricks' ),
	'css'   => [
		[
			'property' => 'border',
			'selector' => ':root .bricks-button[class*="secondary"]',
		],
	],
];

$controls['secondaryBoxShadow'] = [
	'type'  => 'box-shadow',
	'label' => esc_html__( 'Box shadow', 'bricks' ),
	'css'   => [
		[
			'property' => 'box-shadow',
			'selector' => ':root .bricks-button[class*="secondary"]',
		],
	],
];

// Light

$controls['lightSeparator'] = [
	'type'  => 'separator',
	'label' => esc_html__( 'Style - Light', 'bricks' ),
];

$controls['lightTypography'] = [
	'type'  => 'typography',
	'label' => esc_html__( 'Typography', 'bricks' ),
	'css'   => [
		[
			'property' => 'font',
			'selector' => ':root .bricks-button[class*="light"]',
		],
	],
];

$controls['lightBackground'] = [
	'type'  => 'color',
	'label' => esc_html__( 'Background color', 'bricks' ),
	'css'   => [
		[
			'property' => 'background-color',
			'selector' => ':root .bricks-button[class*="light"]:not(.outline):not(.bricks-lightbox)',
		],
	],
];

$controls['lightBorder'] = [
	'type'  => 'border',
	'label' => esc_html__( 'Border', 'bricks' ),
	'css'   => [
		[
			'property' => 'border',
			'selector' => ':root .bricks-button[class*="light"]',
		],
	],
];

$controls['lightBoxShadow'] = [
	'type'  => 'box-shadow',
	'label' => esc_html__( 'Box shadow', 'bricks' ),
	'css'   => [
		[
			'property' => 'box-shadow',
			'selector' => ':root .bricks-button[class*="light"]',
		],
	],
];

// Dark

$controls['darkSeparator'] = [
	'type'  => 'separator',
	'label' => esc_html__( 'Style - Dark', 'bricks' ),
];

$controls['darkTypography'] = [
	'type'  => 'typography',
	'label' => esc_html__( 'Typography', 'bricks' ),
	'css'   => [
		[
			'property' => 'font',
			'selector' => ':root .bricks-button[class*="dark"]',
		],
	],
];

$controls['darkBackground'] = [
	'type'  => 'color',
	'label' => esc_html__( 'Background color', 'bricks' ),
	'css'   => [
		[
			'property' => 'background-color',
			'selector' => ':root .bricks-button[class*="dark"]:not(.outline)',
		],
	],
];

$controls['darkBorder'] = [
	'type'  => 'border',
	'label' => esc_html__( 'Border', 'bricks' ),
	'css'   => [
		[
			'property' => 'border',
			'selector' => ':root .bricks-button[class*="dark"]',
		],
	],
];

$controls['darkBoxShadow'] = [
	'type'  => 'box-shadow',
	'label' => esc_html__( 'Box shadow', 'bricks' ),
	'css'   => [
		[
			'property' => 'box-shadow',
			'selector' => ':root .bricks-button[class*="dark"]',
		],
	],
];

// Size - Default

$controls['sizeDefaultSeparator'] = [
	'type'  => 'separator',
	'label' => esc_html__( 'Size - Default', 'bricks' ),
];

$controls['sizeDefaultPadding'] = [
	'type'        => 'spacing',
	'label'       => esc_html__( 'Padding', 'bricks' ),
	'css'         => [
		[
			'property' => 'padding',
			'selector' => '.bricks-button',
		],
	],
	'placeholder' => [
		'top'    => '0.5em',
		'right'  => '1em',
		'bottom' => '0.5em',
		'left'   => '1em',
	],
];

// Size - Small

$controls['sizeSmSeparator'] = [
	'type'  => 'separator',
	'label' => esc_html__( 'Size - Small', 'bricks' ),
];

$controls['sizeSmPadding'] = [
	'type'        => 'spacing',
	'label'       => esc_html__( 'Padding', 'bricks' ),
	'css'         => [
		[
			'property' => 'padding',
			'selector' => '.bricks-button.sm',
		],
	],
	'placeholder' => [
		'top'    => '0.4em',
		'right'  => '1em',
		'bottom' => '0.4em',
		'left'   => '1em',
	],
];

$controls['sizeSmTypography'] = [
	'type'  => 'typography',
	'label' => esc_html__( 'Typography', 'bricks' ),
	'css'   => [
		[
			'property' => 'font',
			'selector' => '.bricks-button.sm',
		],
	],
];

// Size - Medium

$controls['sizeMdSeparator'] = [
	'type'  => 'separator',
	'label' => esc_html__( 'Size - Medium', 'bricks' ),
];

$controls['sizeMdPadding'] = [
	'type'        => 'spacing',
	'label'       => esc_html__( 'Padding', 'bricks' ),
	'css'         => [
		[
			'property' => 'padding',
			'selector' => '.bricks-button.md',
		],
	],
	'placeholder' => [
		'top'    => '0.5em',
		'right'  => '1em',
		'bottom' => '0.5em',
		'left'   => '1em',
	],
];


$controls['sizeMdTypography'] = [
	'type'  => 'typography',
	'label' => esc_html__( 'Typography', 'bricks' ),
	'css'   => [
		[
			'property' => 'font',
			'selector' => '.bricks-button.md',
		],
	],
];

// Size - Large

$controls['sizeLgSeparator'] = [
	'type'  => 'separator',
	'label' => esc_html__( 'Size - Large', 'bricks' ),
];

$controls['sizeLgPadding'] = [
	'type'        => 'spacing',
	'label'       => esc_html__( 'Padding', 'bricks' ),
	'css'         => [
		[
			'property' => 'padding',
			'selector' => '.bricks-button.lg',
		],
	],
	'placeholder' => [
		'top'    => '0.6em',
		'right'  => '1em',
		'bottom' => '0.6em',
		'left'   => '1em',
	],
];

$controls['sizeLgTypography'] = [
	'type'  => 'typography',
	'label' => esc_html__( 'Typography', 'bricks' ),
	'css'   => [
		[
			'property' => 'font',
			'selector' => '.bricks-button.lg',
		],
	],
];

// Size - Extra Large

$controls['sizeXlSeparator'] = [
	'type'  => 'separator',
	'label' => esc_html__( 'Size - Extra Large', 'bricks' ),
];

$controls['sizeXlPadding'] = [
	'type'        => 'spacing',
	'label'       => esc_html__( 'Padding', 'bricks' ),
	'css'         => [
		[
			'property' => 'padding',
			'selector' => '.bricks-button.xl',
		],
	],
	'placeholder' => [
		'top'    => '0.8em',
		'right'  => '1em',
		'bottom' => '0.8em',
		'left'   => '1em',
	],
];

$controls['sizeXlTypography'] = [
	'type'  => 'typography',
	'label' => esc_html__( 'Typography', 'bricks' ),
	'css'   => [
		[
			'property' => 'font',
			'selector' => '.bricks-button.xl',
		],
	],
];

return [
	'name'     => 'button',
	'controls' => $controls,
];
