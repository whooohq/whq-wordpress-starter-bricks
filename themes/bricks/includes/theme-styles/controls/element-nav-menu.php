<?php
$controls = [];

// Top Level Menu

$controls['menuSeparator'] = [
	'type'  => 'separator',
	'label' => esc_html__( 'Top level', 'bricks' ),
];

$controls['menuMargin'] = [
	'label' => esc_html__( 'Margin', 'bricks' ),
	'type'  => 'spacing',
	'css'   => [
		[
			'property' => 'margin',
			'selector' => '.bricks-nav-menu > li',
		]
	],
];

$controls['menuPadding'] = [
	'label' => esc_html__( 'Padding', 'bricks' ),
	'type'  => 'spacing',
	'css'   => [
		[
			'property' => 'padding',
			'selector' => '.bricks-nav-menu > li > a',
		],
		[
			'property' => 'padding',
			'selector' => '.bricks-nav-menu > li > .brx-submenu-toggle > *',
		],
	],
];

$controls['menuAlignment'] = [
	'label'       => esc_html__( 'Alignment', 'bricks' ),
	'type'        => 'direction',
	'css'         => [
		[
			'property' => 'flex-direction',
			'selector' => '.bricks-nav-menu',
		],
	],
	'inline'      => true,
	'placeholder' => 'row',
];

$controls['menuTypography'] = [
	'type'  => 'typography',
	'label' => esc_html__( 'Typography', 'bricks' ),
	'css'   => [
		[
			'property' => 'font',
			'selector' => '.bricks-nav-menu > li > a',
		],
		[
			'property' => 'font',
			'selector' => '.bricks-nav-menu > li > .brx-submenu-toggle',
		],
	],
];

$controls['menuActiveTypography'] = [
	'label' => esc_html__( 'Active typography', 'bricks' ),
	'type'  => 'typography',
	'css'   => [
		[
			'property' => 'font',
			'selector' => '.bricks-nav-menu .current-menu-item > a',
		],
		[
			'property' => 'font',
			'selector' => '.bricks-nav-menu .current-menu-item > .brx-submenu-toggle',
		],
	],
];

$controls['menuActiveBorder'] = [
	'label' => esc_html__( 'Active border', 'bricks' ),
	'type'  => 'border',
	'css'   => [
		[
			'property' => 'border',
			'selector' => '.bricks-nav-menu .current-menu-item > a',
		],
		[
			'property' => 'border',
			'selector' => '.bricks-nav-menu .current-menu-item > .brx-submenu-toggle',
		],
	],
];

// Sub Menu

$controls['subMenuSeparator'] = [
	'type'  => 'separator',
	'label' => esc_html__( 'Sub menu', 'bricks' ),
];

$controls['subMenuPadding'] = [
	'type'  => 'spacing',
	'label' => esc_html__( 'Padding', 'bricks' ),
	'css'   => [
		[
			'property' => 'padding',
			'selector' => '.bricks-nav-menu .sub-menu a',
		],
		[
			'property' => 'padding',
			'selector' => '.bricks-nav-menu .sub-menu button',
		],
	],
];

$controls['subMenuTypography'] = [
	'type'  => 'typography',
	'label' => esc_html__( 'Typography', 'bricks' ),
	'css'   => [
		[
			'property' => 'font',
			'selector' => '.bricks-nav-menu .sub-menu > li',
		],
	],
];

$controls['subMenuActiveTypography'] = [
	'label' => esc_html__( 'Active typography', 'bricks' ),
	'type'  => 'typography',
	'css'   => [
		[
			'property' => 'font',
			'selector' => '.bricks-nav-menu .sub-menu > .current-menu-item > a',
		],
		[
			'property' => 'font',
			'selector' => '.bricks-nav-menu .sub-menu > .current-menu-item > .brx-submenu-toggle',
		],
	],
];

$controls['subMenuBackground'] = [
	'type'  => 'background',
	'label' => esc_html__( 'Background', 'bricks' ),
	'css'   => [
		[
			'property' => 'background',
			'selector' => '.bricks-nav-menu .sub-menu .menu-item',
		]
	],
];

$controls['subMenuBorder'] = [
	'label' => esc_html__( 'Border', 'bricks' ),
	'type'  => 'border',
	'css'   => [
		[
			'property' => 'border',
			'selector' => '.bricks-nav-menu .sub-menu',
		],
	],
];

$controls['subMenuBoxShadow'] = [
	'label' => esc_html__( 'Box shadow', 'bricks' ),
	'type'  => 'box-shadow',
	'css'   => [
		[
			'property' => 'box-shadow',
			'selector' => '.bricks-nav-menu .sub-menu',
		],
	],
];

return [
	'name'        => 'nav-menu',
	'controls'    => $controls,
	'cssSelector' => '.brxe-nav-menu',
];
