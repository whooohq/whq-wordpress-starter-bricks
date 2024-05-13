<?php
$controls = [];

$controls['padding'] = [
	'label' => esc_html__( 'Padding', 'bricks' ),
	'type'  => 'spacing',
	'css'   => [
		[
			'property' => 'padding',
		],
	],
];

$controls['typography'] = [
	'label' => esc_html__( 'Typography', 'bricks' ),
	'type'  => 'typography',
	'css'   => [
		[
			'property' => 'font',
			'selector' => '',
		],
	],
];

// Info

$controls['infoSeparator'] = [
	'type'  => 'separator',
	'label' => esc_html__( 'Info', 'bricks' ),
];

$controls['infoColor'] = [
	'label' => esc_html__( 'Text color', 'bricks' ),
	'type'  => 'color',
	'css'   => [
		[
			'property' => 'color',
			'selector' => '&.info',
		],
	],
];

$controls['infoBackground'] = [
	'label' => esc_html__( 'Background color', 'bricks' ),
	'type'  => 'color',
	'css'   => [
		[
			'property' => 'background-color',
			'selector' => '&.info',
		],
	],
];

$controls['infoBorder'] = [
	'label' => esc_html__( 'Border', 'bricks' ),
	'type'  => 'border',
	'css'   => [
		[
			'property' => 'border',
			'selector' => '&.info',
		],
	],
];

// Success

$controls['successSeparator'] = [
	'type'  => 'separator',
	'label' => esc_html__( 'Success', 'bricks' ),
];

$controls['successColor'] = [
	'label' => esc_html__( 'Text color', 'bricks' ),
	'type'  => 'color',
	'css'   => [
		[
			'property' => 'color',
			'selector' => '&.success',
		],
	],
];

$controls['successBackground'] = [
	'label' => esc_html__( 'Background color', 'bricks' ),
	'type'  => 'color',
	'css'   => [
		[
			'property' => 'background-color',
			'selector' => '&.success',
		],
	],
];

$controls['successBorder'] = [
	'label' => esc_html__( 'Border', 'bricks' ),
	'type'  => 'border',
	'css'   => [
		[
			'property' => 'border',
			'selector' => '&.success',
		],
	],
];

// Warning

$controls['warningSeparator'] = [
	'type'  => 'separator',
	'label' => esc_html__( 'Warning', 'bricks' ),
];

$controls['warningColor'] = [
	'label' => esc_html__( 'Text color', 'bricks' ),
	'type'  => 'color',
	'css'   => [
		[
			'property' => 'color',
			'selector' => '&.warning',
		],
	],
];

$controls['warningBackground'] = [
	'label' => esc_html__( 'Background color', 'bricks' ),
	'type'  => 'color',
	'css'   => [
		[
			'property' => 'background-color',
			'selector' => '&.warning',
		],
	],
];

$controls['warningBorder'] = [
	'label' => esc_html__( 'Border', 'bricks' ),
	'type'  => 'border',
	'css'   => [
		[
			'property' => 'border',
			'selector' => '&.warning',
		],
	],
];

// Danger

$controls['dangerSeparator'] = [
	'type'  => 'separator',
	'label' => esc_html__( 'Danger', 'bricks' ),
];

$controls['dangerColor'] = [
	'label' => esc_html__( 'Text color', 'bricks' ),
	'type'  => 'color',
	'css'   => [
		[
			'property' => 'color',
			'selector' => '&.danger',
		],
	],
];

$controls['dangerBackground'] = [
	'label' => esc_html__( 'Background color', 'bricks' ),
	'type'  => 'color',
	'css'   => [
		[
			'property' => 'background-color',
			'selector' => '&.danger',
		],
	],
];

$controls['dangerBorder'] = [
	'label' => esc_html__( 'Border', 'bricks' ),
	'type'  => 'border',
	'css'   => [
		[
			'property' => 'border',
			'selector' => '&.danger',
		],
	],
];

// Muted

$controls['mutedSeparator'] = [
	'type'  => 'separator',
	'label' => esc_html__( 'Muted', 'bricks' ),
];

$controls['mutedColor'] = [
	'label' => esc_html__( 'Text color', 'bricks' ),
	'type'  => 'color',
	'css'   => [
		[
			'property' => 'color',
			'selector' => '&.muted',
		],
	],
];

$controls['mutedBackground'] = [
	'label' => esc_html__( 'Background color', 'bricks' ),
	'type'  => 'color',
	'css'   => [
		[
			'property' => 'background-color',
			'selector' => '&.muted',
		],
	],
];

return [
	'name'        => 'alert',
	'controls'    => $controls,
	'cssSelector' => '.brxe-alert',
];
