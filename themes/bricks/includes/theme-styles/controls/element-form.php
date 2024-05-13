<?php
$controls = [];

// Field

$controls['fieldSeparator'] = [
	'type'  => 'separator',
	'label' => esc_html__( 'Field', 'bricks' ),
];

$controls['labelTypography'] = [
	'label' => esc_html__( 'Label typography', 'bricks' ),
	'type'  => 'typography',
	'css'   => [
		[
			'property' => 'font',
			'selector' => '.form-group label',
		],
		[
			'property' => 'font',
			'selector' => '.form-group .label',
		],
	],
];

$controls['placeholderTypography'] = [
	'label' => esc_html__( 'Placeholder typography', 'bricks' ),
	'type'  => 'typography',
	'css'   => [
		[
			'property' => 'font',
			'selector' => '::placeholder',
		],
		[
			'property' => 'font',
			'selector' => 'select', // Select placeholder
		],
	],
];

$controls['fieldTypography'] = [
	'label' => esc_html__( 'Typography', 'bricks' ),
	'type'  => 'typography',
	'css'   => [
		[
			'property' => 'font',
			'selector' => '.form-group input',
		],
		[
			'property' => 'font',
			'selector' => 'select',
		],
		[
			'property' => 'font',
			'selector' => 'textarea',
		],
	],
];

$controls['fieldBackgroundColor'] = [
	'label' => esc_html__( 'Background color', 'bricks' ),
	'type'  => 'color',
	'css'   => [
		[
			'property' => 'background-color',
			'selector' => '.form-group input',
		],
		[
			'property' => 'background-color',
			'selector' => '.flatpickr',
		],
		[
			'property' => 'background-color',
			'selector' => 'select',
		],
		[
			'property' => 'background-color',
			'selector' => 'textarea',
		],
	],
];

$controls['fieldBorder'] = [
	'label' => esc_html__( 'Border', 'bricks' ),
	'type'  => 'border',
	'css'   => [
		[
			'property' => 'border',
			'selector' => '.form-group input',
		],
		[
			'property' => 'border',
			'selector' => '.flatpickr',
		],
		[
			'property' => 'border',
			'selector' => 'select',
		],
		[
			'property' => 'border',
			'selector' => 'textarea',
		],
		[
			'property' => 'border',
			'selector' => '.bricks-button',
		],
		[
			'property' => 'border',
			'selector' => '.choose-files',
		],
	],
];

$controls['fieldMargin'] = [
	'label' => esc_html__( 'Spacing', 'bricks' ),
	'type'  => 'spacing',
	'css'   => [
		[
			'property' => 'padding',
			'selector' => '.form-group',
		],
	],
];

$controls['fieldPadding'] = [
	'label' => esc_html__( 'Padding', 'bricks' ),
	'type'  => 'spacing',
	'css'   => [
		[
			'property' => 'padding',
			'selector' => '.form-group input',
		],
		[
			'property' => 'padding',
			'selector' => '.flatpickr',
		],
		[
			'property' => 'padding',
			'selector' => 'select',
		],
		[
			'property' => 'padding',
			'selector' => 'textarea',
		],
	],
];

// Submit Button

$controls['submitButtonSeparator'] = [
	'type'  => 'separator',
	'label' => esc_html__( 'Submit button', 'bricks' ),
];

$controls['submitButtonPadding'] = [
	'label' => esc_html__( 'Padding', 'bricks' ),
	'type'  => 'spacing',
	'css'   => [
		[
			'property' => 'padding',
			'selector' => '.bricks-button',
		]
	],
];

$controls['submitButtonTypography'] = [
	'label' => esc_html__( 'Typography', 'bricks' ),
	'type'  => 'typography',
	'css'   => [
		[
			'property' => 'font',
			'selector' => '.bricks-button',
		]
	],
];

$controls['submitButtonBackgroundColor'] = [
	'label' => esc_html__( 'Background color', 'bricks' ),
	'type'  => 'color',
	'css'   => [
		[
			'property' => 'background-color',
			'selector' => '.bricks-button',
		]
	],
];

$controls['submitButtonBorder'] = [
	'label' => esc_html__( 'Border', 'bricks' ),
	'type'  => 'border',
	'css'   => [
		[
			'property' => 'border',
			'selector' => '.bricks-button',
		],
	],
];

return [
	'name'        => 'form',
	'controls'    => $controls,
	'cssSelector' => '.brxe-form',
];
