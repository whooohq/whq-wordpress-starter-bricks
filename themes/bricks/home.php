<?php
get_header();

$show_on_front = get_option( 'show_on_front' );

if ( $show_on_front === 'page' ) {
	$post_id     = get_option( 'page_for_posts' );
	$bricks_data = $post_id ? Bricks\Helpers::get_bricks_data( $post_id, 'content' ) : false;
} else {
	$bricks_data = Bricks\Database::get_template_data( 'content' );
}

if ( $bricks_data ) {
	Bricks\Frontend::render_content( $bricks_data );
} else {
	include locate_template( 'template-parts/content.php' );
}

get_footer();
