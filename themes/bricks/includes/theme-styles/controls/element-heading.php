<?php
$controls = [];

// Heading: Default <hx> tag

$controls['tag'] = [
	'tab'         => 'content',
	'label'       => esc_html__( 'HTML tag', 'bricks' ) . ' (' . esc_html__( 'Default', 'bricks' ) . ')',
	'type'        => 'select',
	'inline'      => true,
	'options'     => [
		'h1' => 'h1',
		'h2' => 'h2',
		'h3' => 'h3',
		'h4' => 'h4',
		'h5' => 'h5',
		'h6' => 'h6',
	],
	'placeholder' => 'h3',
];

// Heading: Separator

$controls['separatorSeparator'] = [
	'label' => esc_html__( 'Separator', 'bricks' ),
	'type'  => 'separator',
];

$controls['separator'] = [
	'tab'         => 'content',
	'label'       => esc_html__( 'Separator', 'bricks' ),
	'type'        => 'select',
	'options'     => [
		'right' => esc_html__( 'Right', 'bricks' ),
		'left'  => esc_html__( 'Left', 'bricks' ),
		'both'  => esc_html__( 'Both', 'bricks' ),
	],
	'inline'      => true,
	'placeholder' => esc_html__( 'None', 'bricks' ),
];

$controls['separatorWidth'] = [
	'label'    => esc_html__( 'Width', 'bricks' ),
	'type'     => 'number',
	'units'    => true,
	'css'      => [
		[
			'property' => 'width',
			'selector' => '.brxe-heading .separator',
		],
		[
			'property' => 'flex-grow',
			'selector' => '.brxe-heading .separator',
			'value'    => 0,
		],
	],
	'required' => [ 'separator', '!=', '' ],
];

$controls['separatorHeight'] = [
	'label'    => esc_html__( 'Height', 'bricks' ),
	'type'     => 'number',
	'units'    => true,
	'css'      => [
		[
			'property' => 'border-top-width',
			'selector' => '.brxe-heading .separator',
		],
	],
	'required' => [ 'separator', '!=', '' ],
];

$controls['separatorSpacing'] = [
	'tab'         => 'content',
	'label'       => esc_html__( 'Spacing', 'bricks' ),
	'type'        => 'number',
	'units'       => true,
	'css'         => [
		[
			'property' => 'gap',
			'selector' => '.brxe-heading.has-separator',
		],
	],
	'placeholder' => 20,
	'required'    => [ 'separator', '!=', '' ],
];

$controls['separatorAlignItems'] = [
	'label'    => esc_html__( 'Align', 'bricks' ),
	'type'     => 'align-items',
	'exclude'  => 'stretch',
	'css'      => [
		[
			'property' => 'align-items',
			'selector' => '.brxe-heading.has-separator',
		],
	],
	'inline'   => true,
	'required' => [ 'separator', '!=', '' ],
];

$controls['separatorStyle'] = [
	'label'    => esc_html__( 'Style', 'bricks' ),
	'type'     => 'select',
	'options'  => self::$control_options['borderStyle'],
	'css'      => [
		[
			'property' => 'border-top-style',
			'selector' => '.brxe-heading .separator',
		],
	],
	'inline'   => true,
	'required' => [ 'separator', '!=', '' ],
];

$controls['separatorColor'] = [
	'label'    => esc_html__( 'Color', 'bricks' ),
	'type'     => 'color',
	'css'      => [
		[
			'property' => 'border-top-color',
			'selector' => '.brxe-heading .separator',
		],
	],
	'required' => [ 'separator', '!=', '' ],
];

$controls['separatorMargin'] = [
	'label'    => esc_html__( 'Margin', 'bricks' ),
	'type'     => 'spacing',
	'css'      => [
		[
			'property' => 'margin',
			'selector' => '.brxe-heading .separator',
		],
	],
	'required' => [ 'separator', '!=', '' ],
];

return [
	'name'        => 'heading',
	'controls'    => $controls,
	'cssSelector' => '',
];
