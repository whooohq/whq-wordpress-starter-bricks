<?php
$controls = [];

$controls['_display'] = [
	'label'       => esc_html__( 'Display', 'bricks' ),
	'type'        => 'select',
	'options'     => [
		'block' => 'block',
		'flex'  => 'flex',
	],
	'css'         => [
		[
			'property' => 'display',
			'selector' => '.brxe-block',
		],
	],
	'inline'      => true,
	'placeholder' => 'flex',
];

$controls['_direction'] = [
	'label'       => esc_html__( 'Direction', 'bricks' ),
	'tooltip'     => [
		'content'  => 'flex-direction',
		'position' => 'top-left',
	],
	'type'        => 'direction',
	'css'         => [
		[
			'property' => 'flex-direction',
			'selector' => '.brxe-block',
		],
	],
	'inline'      => true,
	'rerender'    => true,
	'placeholder' => 'column',
	'required'    => [ '_display', '=', [ '', 'flex' ] ],
];

$controls['_justifyContent'] = [
	'label'    => esc_html__( 'Align main axis', 'bricks' ),
	'tooltip'  => [
		'content'  => 'justify-content',
		'position' => 'top-left',
	],
	'type'     => 'justify-content',
	'css'      => [
		[
			'property' => 'justify-content',
			'selector' => '.brxe-block',
		],
	],
	'required' => [ '_display', '=', [ '', 'flex' ] ],
];

$controls['_alignItems'] = [
	'label'    => esc_html__( 'Align cross axis', 'bricks' ),
	'tooltip'  => [
		'content'  => 'align-items',
		'position' => 'top-left',
	],
	'type'     => 'align-items',
	'css'      => [
		[
			'property' => 'align-items',
			'selector' => '.brxe-block',
		],
	],
	'required' => [ '_display', '=', [ '', 'flex' ] ],
];

$controls['width'] = [
	'label'       => esc_html__( 'Width', 'bricks' ),
	'type'        => 'number',
	'units'       => true,
	'css'         => [
		[
			'property' => 'width',
			'selector' => '.brxe-block',
		],
	],
	'placeholder' => '100%',
];

$controls['widthMin'] = [
	'tab'   => 'style',
	'group' => '_layout',
	'label' => esc_html__( 'Min. width', 'bricks' ),
	'type'  => 'number',
	'units' => true,
	'css'   => [
		[
			'property' => 'min-width',
			'selector' => '.brxe-block',
		],
	],
];

$controls['widthMax'] = [
	'tab'   => 'style',
	'group' => '_layout',
	'label' => esc_html__( 'Max. width', 'bricks' ),
	'type'  => 'number',
	'units' => true,
	'css'   => [
		[
			'property' => 'max-width',
			'selector' => '.brxe-block',
		],
	],
];

$controls['_columnGap'] = [
	'label'    => esc_html__( 'Column gap', 'bricks' ),
	'type'     => 'number',
	'units'    => true,
	'css'      => [
		[
			'property' => 'column-gap',
			'selector' => '.brxe-block',
		],
	],
	'required' => [ '_display', '=', [ '', 'flex' ] ],
];

$controls['_rowGap'] = [
	'label'    => esc_html__( 'Row gap', 'bricks' ),
	'type'     => 'number',
	'units'    => true,
	'css'      => [
		[
			'property' => 'row-gap',
			'selector' => '.brxe-block',
		],
	],
	'required' => [ '_display', '=', [ '', 'flex' ] ],
];

$controls['margin'] = [
	'label' => esc_html__( 'Margin', 'bricks' ),
	'type'  => 'spacing',
	'css'   => [
		[
			'property' => 'margin',
			'selector' => '.brxe-block',
		],
	],
];

$controls['padding'] = [
	'label' => esc_html__( 'Padding', 'bricks' ),
	'type'  => 'spacing',
	'css'   => [
		[
			'property' => 'padding',
			'selector' => '.brxe-block',
		],
	],
];

return [
	'name'     => 'block',
	'controls' => $controls,
	// 'cssSelector' => '.brxe-block',
];
