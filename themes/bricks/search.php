<?php
namespace Bricks;

get_header();

$bricks_data = Database::get_template_data( 'search' );

if ( is_array( $bricks_data ) ) {
	Frontend::render_content( $bricks_data );
} else {
	echo '<div class="bricks-archive-title-wrapper brxe-container">';
	echo '<h1 class="title">' . esc_html__( 'Search results for:', 'bricks' ) . ' ' . get_search_query() . '</h1>';
	echo '</div>';

	include locate_template( 'template-parts/content.php' );
}

get_footer();
