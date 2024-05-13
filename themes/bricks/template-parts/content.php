<?php
ob_start();

echo '<div class="brxe-container">';

if ( have_posts() ) {
	$settings = [
		'settings' => [
			'layout'          => 'grid',
			'columns'         => 2,
			'gutter'          => 30,
			'imageLink'       => true,
			'fields'          => [
				[
					'dynamicData' => '{post_title:link}',
					'tag'         => 'h3',
				],
				[
					'dynamicData' => '{post_date}',
				],
				[
					'dynamicData' => '{post_excerpt:20}',
				],
			],
			'postsNavigation' => true,
		]
	];

	// Set is_archive_main_query for search page so it will use main query result (@since 1.9.1)
	if ( is_search() ) {
		$settings['settings']['query']['is_archive_main_query'] = true;
	}

	$post_content = new Bricks\Element_Posts( $settings );
	$post_content->load();
	$post_content->init();
}

// No posts
else {
	$no_posts_html = '<div class="bricks-no-posts-wrapper">';

	$no_posts_html .= '<h3 class="title">' . esc_html__( 'Nothing found.', 'bricks' ) . '</h3>';

	if ( current_user_can( 'publish_posts' ) ) {
		$no_posts_html .= '<p>';
		$no_posts_html .= esc_html__( 'Ready to publish your first post?', 'bricks' );
		$no_posts_html .= ' <a href="' . admin_url( 'post-new.php' ) . '">' . esc_html__( 'Get started here', 'bricks' ) . '</a>.';
		$no_posts_html .= '</p>';
	}

	$no_posts_html .= '</div>';

	echo $no_posts_html;
}

echo '</div>';

$attributes = [ 'class' => 'layout-default' ];

$html = ob_get_clean();

Bricks\Frontend::render_content( [], $attributes, $html );
