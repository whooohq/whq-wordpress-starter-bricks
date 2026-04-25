<?php
$controls = [];

// Title

$controls['titleSeparator'] = [
	'label' => esc_html__( 'Title', 'bricks' ),
	'type'  => 'separator',
];

$controls['titleMargin'] = [
	'label' => esc_html__( 'Title margin', 'bricks' ),
	'type'  => 'spacing',
	'css'   => [
		[
			'property' => 'margin',
			'selector' => '.title',
		],
	],
];

$controls['titleTypography'] = [
	'label' => esc_html__( 'Title typography', 'bricks' ),
	'type'  => 'typography',
	'css'   => [
		[
			'property' => 'font',
			'selector' => '.title',
		],
	],
];

// Content

$controls['contentSeparator'] = [
	'label' => esc_html__( 'Content', 'bricks' ),
	'type'  => 'separator',
];

$controls['contentWidth'] = [
	'label' => esc_html__( 'Content width', 'bricks' ),
	'type'  => 'number',
	'units' => true,
	'css'   => [
		[
			'property' => 'width',
			'selector' => '.slider-content',
		]
	],
];

$controls['contentBackgroundColor'] = [
	'label' => esc_html__( 'Content background', 'bricks' ),
	'type'  => 'color',
	'css'   => [
		[
			'property' => 'background-color',
			'selector' => '.slider-content',
		]
	],
];

$controls['contentMargin'] = [
	'label' => esc_html__( 'Content margin', 'bricks' ),
	'type'  => 'spacing',
	'css'   => [
		[
			'property' => 'margin',
			'selector' => '.slider-content',
		],
	],
];

$controls['contentPadding'] = [
	'label' => esc_html__( 'Content padding', 'bricks' ),
	'type'  => 'spacing',
	'css'   => [
		[
			'property' => 'padding',
			'selector' => '.slider-content',
		],
	],
];

$controls['contentAlignHorizontal'] = [
	'label'   => esc_html__( 'Content align horizontal', 'bricks' ),
	'type'    => 'select',
	'type'    => 'justify-content',
	'exclude' => 'space',
	'css'     => [
		[
			'property' => 'justify-content',
			'selector' => '.swiper-slide',
		],
	],
	'default' => 'center',
];

$controls['contentAlignVertical'] = [
	'label'   => esc_html__( 'Content align vertical', 'bricks' ),
	'type'    => 'align-items',
	'exclude' => 'stretch',
	'css'     => [
		[
			'property' => 'align-items',
			'selector' => '.swiper-slide',
		],
	],
	'default' => 'center',
];

$controls['contentTextAlign'] = [
	'type'    => 'text-align',
	'label'   => esc_html__( 'Content text align', 'bricks' ),
	'css'     => [
		[
			'property' => 'text-align',
			'selector' => '.slider-content',
		],
	],
	'default' => 'center',
];

$controls['contentTypography'] = [
	'label' => esc_html__( 'Content typography', 'bricks' ),
	'type'  => 'typography',
	'css'   => [
		[
			'property' => 'font',
			'selector' => '.content',
		],
	],
];

// Button

$controls['buttonSeparator'] = [
	'label' => esc_html__( 'Button', 'bricks' ),
	'type'  => 'separator',
];

$controls['buttonStyle'] = [
	'label'   => esc_html__( 'Style', 'bricks' ),
	'type'    => 'select',
	'options' => self::$control_options['styles'],
];

$controls['buttonSize'] = [
	'label'   => esc_html__( 'Size', 'bricks' ),
	'type'    => 'select',
	'options' => self::$control_options['buttonSizes'],
];

$controls['buttonBackground'] = [
	'label' => esc_html__( 'Background', 'bricks' ),
	'type'  => 'color',
	'css'   => [
		[
			'property' => 'background-color',
			'selector' => '.bricks-button',
		],
	],
];

$controls['buttonBorder'] = [
	'label' => esc_html__( 'Border', 'bricks' ),
	'type'  => 'border',
	'css'   => [
		[
			'property' => 'border',
			'selector' => '.bricks-button',
		],
	],
];

$controls['buttonBoxshadow'] = [
	'label' => esc_html__( 'Box shadow', 'bricks' ),
	'type'  => 'box-shadow',
	'css'   => [
		[
			'property' => 'box-shadow',
			'selector' => '.bricks-button',
		],
	],
];

$controls['buttonTypography'] = [
	'label' => esc_html__( 'Button typography', 'bricks' ),
	'type'  => 'typography',
	'css'   => [
		[
			'property' => 'color',
			'selector' => '.bricks-button',
		],
	],
];

// Background

$controls['backgroundSeparator'] = [
	'label' => esc_html__( 'Background', 'bricks' ),
	'type'  => 'separator',
];

$controls['backgroundFilters'] = [
	'label'         => esc_html__( 'CSS Filters', 'bricks' ),
	'titleProperty' => 'type',
	'type'          => 'filters',
	'inline'        => true,
	'css'           => [
		[
			'property' => 'filter',
			'selector' => '.css-filter',
		],
	],
	'description'   => sprintf( '<a target="_blank" href="https://developer.mozilla.org/en-US/docs/Web/CSS/filter#Syntax">%s</a>', esc_html__( 'Learn more about CSS filters', 'bricks' ) ),
];

$controls['backgroundPositionTop'] = [
	'label' => esc_html__( 'Top', 'bricks' ),
	'type'  => 'number',
	'units' => true,
	'css'   => [
		[
			'property' => 'top',
			'selector' => '.image',
		]
	],
];

$controls['backgroundPositionRight'] = [
	'label' => esc_html__( 'Right', 'bricks' ),
	'type'  => 'number',
	'units' => true,
	'css'   => [
		[
			'property' => 'right',
			'selector' => '.image',
		]
	],
];

$controls['backgroundPositionBottom'] = [
	'label' => esc_html__( 'Bottom', 'bricks' ),
	'type'  => 'number',
	'units' => true,
	'css'   => [
		[
			'property' => 'bottom',
			'selector' => '.image',
		]
	],
];

$controls['backgroundPositionLeft'] = [
	'label' => esc_html__( 'Left', 'bricks' ),
	'type'  => 'number',
	'units' => true,
	'css'   => [
		[
			'property' => 'left',
			'selector' => '.image',
		]
	],
];


$swiper_controls = \Bricks\Element_Slider::get_swiper_controls();

// Arrows

$controls['arrowsSeparator'] = [
	'label' => esc_html__( 'Arrows', 'bricks' ),
	'type'  => 'separator',
];

$controls['arrows']          = $swiper_controls['arrows'];
$controls['arrowHeight']     = $swiper_controls['arrowHeight'];
$controls['arrowWidth']      = $swiper_controls['arrowWidth'];
$controls['arrowBackground'] = $swiper_controls['arrowBackground'];
$controls['arrowBorder']     = $swiper_controls['arrowBorder'];
$controls['arrowTypography'] = $swiper_controls['arrowTypography'];

$controls['prevArrowSeparator'] = $swiper_controls['prevArrowSeparator'];
$controls['prevArrow']          = $swiper_controls['prevArrow'];
$controls['prevArrowTop']       = $swiper_controls['prevArrowTop'];
$controls['prevArrowRight']     = $swiper_controls['prevArrowRight'];
$controls['prevArrowBottom']    = $swiper_controls['prevArrowBottom'];
$controls['prevArrowLeft']      = $swiper_controls['prevArrowLeft'];

$controls['nextArrowSeparator'] = $swiper_controls['nextArrowSeparator'];
$controls['nextArrow']          = $swiper_controls['nextArrow'];
$controls['nextArrowTop']       = $swiper_controls['nextArrowTop'];
$controls['nextArrowRight']     = $swiper_controls['nextArrowRight'];
$controls['nextArrowBottom']    = $swiper_controls['nextArrowBottom'];
$controls['nextArrowLeft']      = $swiper_controls['nextArrowLeft'];

// Dots

$controls['dotsSeparator'] = [
	'label' => esc_html__( 'Dots', 'bricks' ),
	'type'  => 'separator',
];

$controls['dots']            = $swiper_controls['dots'];
$controls['dotsVertical']    = $swiper_controls['dotsVertical'];
$controls['dotsHeight']      = $swiper_controls['dotsHeight'];
$controls['dotsWidth']       = $swiper_controls['dotsWidth'];
$controls['dotsTop']         = $swiper_controls['dotsTop'];
$controls['dotsRight']       = $swiper_controls['dotsRight'];
$controls['dotsBottom']      = $swiper_controls['dotsBottom'];
$controls['dotsLeft']        = $swiper_controls['dotsLeft'];
$controls['dotsBorder']      = $swiper_controls['dotsBorder'];
$controls['dotsColor']       = $swiper_controls['dotsColor'];
$controls['dotsActiveColor'] = $swiper_controls['dotsActiveColor'];
$controls['dotsSpacing']     = $swiper_controls['dotsSpacing'];

return [
	'name'        => 'slider',
	'controls'    => $controls,
	'cssSelector' => '.brxe-slider',
];
