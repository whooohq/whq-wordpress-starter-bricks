<?php
$controls = [];

$controls['imageAlign'] = [
	'tab'     => 'content',
	'group'   => 'image',
	'label'   => esc_html__( 'Image align', 'bricks' ),
	'type'    => 'select',
	'options' => [
		'flex-start' => esc_html__( 'Top / Start', 'bricks' ) ,
		'center'     => esc_html__( 'Center', 'bricks' ) ,
		'flex-end'   => esc_html__( 'Bottom / End', 'bricks' ) ,
	],
	'css'     => [
		[
			'property' => 'align-items',
			'selector' => '.repeater-item',
		],
	],
	'inline'  => true,
];

$controls['imageSize'] = [
	'tab'         => 'content',
	'group'       => 'image',
	'label'       => esc_html__( 'Image size', 'bricks' ),
	'type'        => 'number',
	'units'       => true,
	'css'         => [
		[
			'property' => 'width',
			'selector' => '.image',
		],
		[
			'property' => 'height',
			'selector' => '.image',
		],
	],
	'placeholder' => 60,
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

$controls['typographyContent'] = [
	'label' => esc_html__( 'Testimonial', 'bricks' ),
	'type'  => 'typography',
	'css'   => [
		[
			'property' => 'font',
			'selector' => '.testimonial-content-wrapper',
		],
	],
];

$controls['typographyName'] = [
	'label' => esc_html__( 'Name', 'bricks' ),
	'type'  => 'typography',
	'css'   => [
		[
			'property' => 'font',
			'selector' => '.testimonial-name',
		],
	],
];

$controls['typographyTitle'] = [
	'label' => esc_html__( 'Title', 'bricks' ),
	'type'  => 'typography',
	'css'   => [
		[
			'property' => 'font',
			'selector' => '.testimonial-title',
		],
	],
];

return [
	'name'        => 'testimonials',
	'controls'    => $controls,
	'cssSelector' => '.brxe-testimonials',
];
