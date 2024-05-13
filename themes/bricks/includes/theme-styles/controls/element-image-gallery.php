<?php
$controls = [];

$controls['layout'] = [
	'label'       => esc_html__( 'Layout', 'bricks' ),
	'type'        => 'select',
	'options'     => [
		'grid'    => esc_html__( 'Grid', 'bricks' ),
		'masonry' => esc_html__( 'Masonry', 'bricks' ),
		'metro'   => esc_html__( 'Metro', 'bricks' ),
	],
	'placeholder' => esc_html__( 'Grid', 'bricks' ),
	'inline'      => true,
];

$controls['imageRatio'] = [
	'label'       => esc_html__( 'Image ratio', 'bricks' ),
	'type'        => 'select',
	'options'     => self::$control_options['imageRatio'],
	'inline'      => true,
	'description' => esc_html__( 'Precedes image height setting.', 'bricks' ),
	'placeholder' => esc_html__( 'Square', 'bricks' ),
	'required'    => [ 'layout', '!=', [ 'masonry', 'metro' ] ],
];

$controls['columns'] = [
	'label'       => esc_html__( 'Columns', 'bricks' ),
	'type'        => 'number',
	'min'         => 1,
	'placeholder' => 3,
	'required'    => [ 'layout', '!=', [ 'metro' ] ],
];

$controls['imageHeight'] = [
	'label'       => esc_html__( 'Image height', 'bricks' ),
	'type'        => 'number',
	'units'       => true,
	'css'         => [
		[
			'property'  => 'padding-top',
			'selector'  => '.image',
			'important' => true,
		],
	],
	'placeholder' => '',
	'required'    => [ 'layout', '!=', [ 'masonry', 'metro' ] ],
];

$controls['gutter'] = [
	'label'       => esc_html__( 'Spacing', 'bricks' ),
	'type'        => 'number',
	'units'       => true,
	'css'         => [
		[
			'property' => '--gutter',
			'selector' => '',
		],
	],
	'placeholder' => 0,
];

return [
	'name'        => 'image-gallery',
	'controls'    => $controls,
	'cssSelector' => '.brxe-image-gallery',
];
