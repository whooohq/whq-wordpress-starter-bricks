<?php
$controls = [];

// Use :where pseudo-class so it does not unchained global classes (@since 1.7.1)
$link_css_selectors = [
	':where(.brxe-accordion .accordion-content-wrapper) a',
	':where(.brxe-icon-box .content) a',
	':where(.brxe-list) a',
	':where(.brxe-post-content) a:not(.bricks-button)', // @since 1.5 (see: #2hjn8md)
	':where(.brxe-posts .dynamic p) a',
	':where(.brxe-shortcode) a',
	':where(.brxe-tabs .tab-content) a',
	':where(.brxe-team-members) .description a',
	':where(.brxe-testimonials) .testimonial-content-wrapper a',

	':where(.brxe-text) a',
	':where(a.brxe-text)',

	':where(.brxe-text-basic) a',
	':where(a.brxe-text-basic)',

	':where(.brxe-post-comments) .comment-content a',
];

// https://academy.bricksbuilder.io/article/filter-bricks-link_css_selectors/
$link_css_selectors = apply_filters( 'bricks/link_css_selectors', $link_css_selectors );

$link_css_selectors = join( ', ', $link_css_selectors );

$controls['typography'] = [
	'type'    => 'typography',
	'label'   => esc_html__( 'Typography', 'bricks' ),
	'css'     => [
		[
			'property' => 'font',
			'selector' => $link_css_selectors,
		],
	],
	'exclude' => [
		'text-align',
		'line-height',
	],
];

$controls['background'] = [
	'label'   => esc_html__( 'Background', 'bricks' ),
	'type'    => 'background',
	'css'     => [
		[
			'property' => 'background',
			'selector' => $link_css_selectors,
		],
	],
	'exclude' => 'video',
];

$controls['border'] = [
	'label' => esc_html__( 'Border', 'bricks' ),
	'type'  => 'border',
	'css'   => [
		[
			'property' => 'border',
			'selector' => $link_css_selectors,
		],
	],
];

$controls['padding'] = [
	'label' => esc_html__( 'Padding', 'bricks' ),
	'type'  => 'spacing',
	'css'   => [
		[
			'property' => 'padding',
			'selector' => $link_css_selectors,
		],
	],
];

$controls['textDecoration'] = [
	'deprecated' => '1.8',
	'label'      => esc_html__( 'Text decoration', 'bricks' ),
	'type'       => 'text-decoration',
	'css'        => [
		[
			'property' => 'text-decoration',
			'selector' => $link_css_selectors,
		],
	],
];

$controls['transition'] = [
	'label'       => esc_html__( 'Transition', 'bricks' ),
	'css'         => [
		[
			'property' => 'transition',
			'selector' => $link_css_selectors,
		],
	],
	'type'        => 'text',
	'description' => sprintf( '<a href="https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_Transitions/Using_CSS_transitions" target="_blank">%s</a>', esc_html__( 'Learn more about CSS transitions', 'bricks' ) ),
];

return [
	'name'     => 'links',
	'controls' => $controls,
];
