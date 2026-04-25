<?php
namespace Bricks;

$controls = [];

// PERFORMANCE: Run WP query to populate control options in builder only
$all_terms             = bricks_is_builder() ? Helpers::get_terms_options( null, null, true ) : [];
$terms                 = bricks_is_builder() ? Helpers::get_terms_options() : [];
$registered_post_types = bricks_is_builder() ? Helpers::get_registered_post_types() : [];

$controls['conditions'] = [
	// 'label' => esc_html__( 'Style conditions', 'bricks' ),
	'type'          => 'repeater',
	'placeholder'   => esc_html__( 'Condition', 'bricks' ),
	'titleProperty' => 'main',
	'fields'        => [
		'main'                        => [
			'type'        => 'select',
			'options'     => [
				'any'         => esc_html__( 'Entire website', 'bricks' ),
				'frontpage'   => esc_html__( 'Front page', 'bricks' ),
				'postType'    => esc_html__( 'Post type', 'bricks' ),
				'archiveType' => esc_html__( 'Archive', 'bricks' ),
				'search'      => esc_html__( 'Search results', 'bricks' ),
				'error'       => esc_html__( 'Error page', 'bricks' ),
				'terms'       => esc_html__( 'Terms', 'bricks' ),
				'ids'         => esc_html__( 'Individual', 'bricks' ),
			],
			'placeholder' => esc_html__( 'Select', 'bricks' ),
		],

		'archiveType'                 => [
			'type'        => 'select',
			'label'       => esc_html__( 'Archive type', 'bricks' ),
			'options'     => [
				'any'    => esc_html__( 'All archives', 'bricks' ),
				'author' => esc_html__( 'Author', 'bricks' ),
				'date'   => esc_html__( 'Date', 'bricks' ),
				'term'   => esc_html__( 'Categories & Tags', 'bricks' ),
			],
			'multiple'    => true,
			'placeholder' => esc_html__( 'Select archive type', 'bricks' ),
			'required'    => [ 'main', '=', 'archiveType' ],
		],

		'archiveTerms'                => [
			'type'        => 'select',
			'label'       => esc_html__( 'Archive terms', 'bricks' ),
			'options'     => $all_terms,
			'multiple'    => true,
			'searchable'  => true,
			'placeholder' => esc_html__( 'Select archive term', 'bricks' ),
			'description' => esc_html__( 'Leave empty to apply template to all archive terms.', 'bricks' ),
			'required'    => [ 'archiveType', '=', 'term' ],
		],

		'archiveTermsIncludeChildren' => [
			'type'     => 'checkbox',
			'label'    => esc_html__( 'Apply to child terms', 'bricks' ),
			'required' => [ 'archiveType', '=', 'term' ],
		],

		'postType'                    => [
			'type'        => 'select',
			'label'       => esc_html__( 'Post type', 'bricks' ),
			'options'     => $registered_post_types,
			'multiple'    => true,
			'placeholder' => esc_html__( 'Select post type', 'bricks' ),
			'required'    => [ 'main', '=', 'postType' ],
		],

		'terms'                       => [
			'type'        => 'select',
			'label'       => esc_html__( 'Terms', 'bricks' ),
			'options'     => $terms,
			'multiple'    => true,
			'searchable'  => true,
			'placeholder' => esc_html__( 'Select terms', 'bricks' ),
			'required'    => [ 'main', '=', 'terms' ],
		],

		'ids'                         => [
			'type'        => 'select',
			'label'       => esc_html__( 'Individual', 'bricks' ),
			'optionsAjax' => [
				'action'                 => 'bricks_get_posts',
				'postType'               => 'any',
				'addLanguageToPostTitle' => true,
			],
			'multiple'    => true,
			'searchable'  => true,
			'placeholder' => esc_html__( 'Select individual', 'bricks' ),
			'required'    => [ 'main', '=', 'ids' ],
		],

		'idsIncludeChildren'          => [
			'type'     => 'checkbox',
			'label'    => esc_html__( 'Apply to child pages', 'bricks' ),
			'required' => [ 'main', '=', 'ids' ],
		],

		'exclude'                     => [
			'type'  => 'checkbox',
			'label' => esc_html__( 'Exclude', 'bricks' ),
		],
	],
	'description'   => esc_html__( 'Set condition(s) to apply selected theme style to your entire website or certain areas.', 'bricks' ),
];

return [
	'name'     => 'conditions',
	'controls' => $controls,
];
