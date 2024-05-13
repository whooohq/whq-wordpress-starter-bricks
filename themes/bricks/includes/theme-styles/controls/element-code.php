<?php
$controls = [];

// More themes: https://jmblog.github.io/color-themes-for-google-code-prettify/

$controls['prettify'] = [
	'label'       => esc_html__( 'Code Theme', 'bricks' ),
	'type'        => 'select',
	'options'     => [
		'github'         => 'Github (light)',
		'tomorrow'       => 'Tomorrow (light)',
		'tomorrow-night' => 'Tomorrow Night (dark)',
		'tranquil-heart' => 'Tranquil Heart (dark)',
		// 'custom' => esc_html__( 'Custom', 'bricks' ),
	],
	'placeholder' => esc_html__( 'None', 'bricks' ),
];

return [
	'name'     => 'code',
	'controls' => $controls,
];
