<?php
$controls = [];

$controls['colorsInfo'] = [
	'type'    => 'info',
	'content' => esc_html__( 'Applicable to heading or button "Style" setting only. Create & use global colors through your own custom "Color palette".', 'bricks' ),
];

$controls['colorPrimary'] = [
	'type'  => 'color',
	'label' => esc_html__( 'Primary color', 'bricks' ),
	'css'   => [
		[
			'selector' => '.bricks-color-primary',
			'property' => 'color',
		],

		[
			'selector' => '.bricks-background-primary',
			'property' => 'background-color',
		],
	],
];

$controls['colorSecondary'] = [
	'type'  => 'color',
	'label' => esc_html__( 'Secondary color', 'bricks' ),
	'css'   => [
		[
			'property' => 'color',
			'selector' => '.bricks-color-secondary',
		],
		[
			'property' => 'background-color',
			'selector' => '.bricks-background-secondary',
		],
	],
];

$controls['colorLight'] = [
	'type'  => 'color',
	'label' => esc_html__( 'Light color', 'bricks' ),
	'css'   => [
		[
			'property' => 'color',
			'selector' => '.bricks-color-light',
		],
		[
			'property' => 'background-color',
			'selector' => '.bricks-background-light',
		],
	],
];

$controls['colorDark'] = [
	'type'  => 'color',
	'label' => esc_html__( 'Dark color', 'bricks' ),
	'css'   => [
		[
			'property' => 'color',
			'selector' => '.bricks-color-dark',
		],
		[
			'property' => 'background-color',
			'selector' => '.bricks-background-dark',
		],
	],
];

$controls['colorMuted'] = [
	'type'  => 'color',
	'label' => esc_html__( 'Muted color', 'bricks' ),
	'css'   => [
		[
			'property' => 'color',
			'selector' => '.bricks-color-muted',
		],
		[
			'property' => 'background-color',
			'selector' => '.bricks-background-muted',
		],
	],
];

$controls['colorBorder'] = [
	'type'  => 'color',
	'label' => esc_html__( 'Border color', 'bricks' ),
	'css'   => [
		[
			'property' => 'border-color',
			'selector' => '*',
		],
	],
];

$controls['colorInfo'] = [
	'type'  => 'color',
	'label' => esc_html__( 'Info color', 'bricks' ),
	'css'   => [
		[
			'property' => 'color',
			'selector' => '.bricks-color-info',
		],
		[
			'property' => 'background-color',
			'selector' => '.bricks-background-info',
		],
	],
];

$controls['colorSuccess'] = [
	'type'  => 'color',
	'label' => esc_html__( 'Success color', 'bricks' ),
	'css'   => [
		[
			'property' => 'color',
			'selector' => '.bricks-color-success',
		],
		[
			'property' => 'background-color',
			'selector' => '.bricks-background-success',
		],
	],
];

$controls['colorWarning'] = [
	'type'  => 'color',
	'label' => esc_html__( 'Warning color', 'bricks' ),
	'css'   => [
		[
			'property' => 'color',
			'selector' => '.bricks-color-warning',
		],
		[
			'property' => 'background-color',
			'selector' => '.bricks-background-warning',
		],
	],
];

$controls['colorDanger'] = [
	'type'  => 'color',
	'label' => esc_html__( 'Danger color', 'bricks' ),
	'css'   => [
		[
			'property' => 'color',
			'selector' => '.bricks-color-danger',
		],
		[
			'property' => 'background-color',
			'selector' => '.bricks-background-danger',
		],
	],
];

return [
	'name'        => 'colors',
	'controls'    => $controls,
	'cssSelector' => ':root', // @since 1.3 (see: #mvdca2)
];
