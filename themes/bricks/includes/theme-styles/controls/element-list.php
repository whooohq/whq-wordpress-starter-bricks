<?php
$controls = [];

// Item

$controls['itemSeparator'] = [
	'type'  => 'separator',
	'label' => esc_html__( 'List item', 'bricks' ),
];

$controls['itemMargin'] = [
	'label' => esc_html__( 'Margin', 'bricks' ),
	'type'  => 'spacing',
	'css'   => [
		[
			'property' => 'margin',
			'selector' => 'li',
		],
	],
];

$controls['itemPadding'] = [
	'label' => esc_html__( 'Padding', 'bricks' ),
	'type'  => 'spacing',
	'css'   => [
		[
			'property' => 'padding',
			'selector' => 'li',
		],
	],
];

$controls['itemOddBackground'] = [
	'label' => esc_html__( 'Odd background', 'bricks' ),
	'type'  => 'color',
	'css'   => [
		[
			'property' => 'background-color',
			'selector' => 'li:nth-child(odd)',
		],
	],
];

$controls['itemEvenBackground'] = [
	'label' => esc_html__( 'Even background', 'bricks' ),
	'type'  => 'color',
	'css'   => [
		[
			'property' => 'background-color',
			'selector' => 'li:nth-child(even)',
		],
	],
];

$controls['itemBorder'] = [
	'label' => esc_html__( 'Border', 'bricks' ),
	'type'  => 'border',
	'css'   => [
		[
			'property' => 'border',
			'selector' => 'li',
		],
	],
];

$controls['itemAutoWidth'] = [
	'label' => esc_html__( 'Auto width', 'bricks' ),
	'type'  => 'checkbox',
	'css'   => [
		[
			'property' => 'justify-content',
			'selector' => '.content',
			'value'    => 'initial',
		],
		[
			'property' => 'flex-grow',
			'selector' => '.separator',
			'value'    => '0',
		],
	],
];

// Highlight

$controls['highlightSeparator'] = [
	'type'  => 'separator',
	'label' => esc_html__( 'Highlight', 'bricks' ),
];

$controls['highlightBlock'] = [
	'label' => esc_html__( 'Block', 'bricks' ),
	'type'  => 'checkbox',
	'css'   => [
		[
			'property' => 'display',
			'selector' => 'li[data-highlight]::before',
			'value'    => 'block',
		],
	],
];

$controls['highlightLabelPadding'] = [
	'label' => esc_html__( 'Padding', 'bricks' ),
	'type'  => 'spacing',
	'css'   => [
		[
			'property' => 'padding',
			'selector' => 'li[data-highlight]::before',
		],
	],
];

$controls['highlightLabelBackground'] = [
	'label' => esc_html__( 'Background', 'bricks' ),
	'type'  => 'color',
	'css'   => [
		[
			'property' => 'background-color',
			'selector' => 'li[data-highlight]::before',
		],
	],
];

$controls['highlightLabelBorder'] = [
	'label' => esc_html__( 'Border', 'bricks' ),
	'type'  => 'border',
	'css'   => [
		[
			'property' => 'border',
			'selector' => 'li[data-highlight]::before',
		],
	],
];

$controls['highlightLabelTypography'] = [
	'label' => esc_html__( 'Typography', 'bricks' ),
	'type'  => 'typography',
	'css'   => [
		[
			'property' => 'font',
			'selector' => 'li[data-highlight]::before',
		],
	],
];

$controls['separatorHighlightContent'] = [
	'tab'   => 'content',
	'group' => 'highlight',
	'label' => esc_html__( 'Content', 'bricks' ),
	'type'  => 'separator',
];

$controls['highlightContentPadding'] = [
	'label' => esc_html__( 'Padding', 'bricks' ),
	'type'  => 'spacing',
	'css'   => [
		[
			'property' => 'padding',
			'selector' => 'li[data-highlight] + div',
		],
	],
];

$controls['highlightContentBackground'] = [
	'label' => esc_html__( 'Background', 'bricks' ),
	'type'  => 'color',
	'css'   => [
		[
			'property' => 'background-color',
			'selector' => 'li[data-highlight] + div',
		],
	],
];

$controls['highlightContentBorder'] = [
	'label' => esc_html__( 'Border', 'bricks' ),
	'type'  => 'border',
	'css'   => [
		[
			'property' => 'border',
			'selector' => 'li[data-highlight] + div',
		],
	],
];

$controls['highlightContentColor'] = [
	'label' => esc_html__( 'Text color', 'bricks' ),
	'type'  => 'color',
	'css'   => [
		[
			'property' => 'color',
			'selector' => 'li[data-highlight] + div .title',
		],
		[
			'property' => 'color',
			'selector' => 'li[data-highlight] + div .meta',
		],
		[
			'property' => 'color',
			'selector' => 'li[data-highlight] + div .description',
		],
	],
];

// Title

$controls['titleSeparator'] = [
	'type'  => 'separator',
	'label' => esc_html__( 'Title', 'bricks' ),
];

$controls['titleMargin'] = [
	'label' => esc_html__( 'Margin', 'bricks' ),
	'type'  => 'spacing',
	'css'   => [
		[
			'property' => 'margin',
			'selector' => '.title',
		],
	],
];

$controls['titleTypography'] = [
	'label' => esc_html__( 'Typography', 'bricks' ),
	'type'  => 'typography',
	'css'   => [
		[
			'property' => 'font',
			'selector' => '.title',
		],
	],
];

// Meta

$controls['metaSeparator'] = [
	'type'  => 'separator',
	'label' => esc_html__( 'Meta', 'bricks' ),
];

$controls['metaMargin'] = [
	'label' => esc_html__( 'Margin', 'bricks' ),
	'type'  => 'spacing',
	'css'   => [
		[
			'property' => 'margin',
			'selector' => '.meta',
		],
	],
];

$controls['metaTypography'] = [
	'label' => esc_html__( 'Typography', 'bricks' ),
	'type'  => 'typography',
	'css'   => [
		[
			'property' => 'font',
			'selector' => '.meta',
		],
	],
];

// Description

$controls['descriptionSeparator'] = [
	'type'  => 'separator',
	'label' => esc_html__( 'Description', 'bricks' ),
];

$controls['descriptionTypography'] = [
	'label' => esc_html__( 'Typography', 'bricks' ),
	'type'  => 'typography',
	'css'   => [
		[
			'property' => 'font',
			'selector' => '.description',
		],
	],
];

// Separator

$controls['separatorSeparator'] = [
	'type'  => 'separator',
	'label' => esc_html__( 'Separator', 'bricks' ),
];

$controls['separatorDisable'] = [
	'label' => esc_html__( 'Disable', 'bricks' ),
	'type'  => 'checkbox',
	'css'   => [
		[
			'property' => 'display',
			'selector' => '.separator',
			'value'    => 'none',
		],
	],
];

$controls['separatorStyle'] = [
	'label'   => esc_html__( 'Style', 'bricks' ),
	'type'    => 'select',
	'options' => self::$control_options['borderStyle'],
	'css'     => [
		[
			'property' => 'border-top-style',
			'selector' => '.separator',
		],
	],
	'inline'  => true,
];

$controls['separatorWidth'] = [
	'label' => esc_html__( 'Width', 'bricks' ),
	'type'  => 'number',
	'units' => true,
	'css'   => [
		[
			'property' => 'flex-basis',
			'selector' => '.separator',
		],
	],
];

$controls['separatorHeight'] = [
	'label' => esc_html__( 'Height', 'bricks' ),
	'type'  => 'number',
	'units' => true,
	'css'   => [
		[
			'property' => 'border-top-width',
			'selector' => '.separator',
		],
	],
];

$controls['separatorColor'] = [
	'label' => esc_html__( 'Color', 'bricks' ),
	'type'  => 'color',
	'css'   => [
		[
			'property' => 'border-top-color',
			'selector' => '.separator',
		],
	],
];

return [
	'name'        => 'list',
	'controls'    => $controls,
	'cssSelector' => '.brxe-list',
];
