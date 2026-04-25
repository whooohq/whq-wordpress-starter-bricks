<?php
get_header();

$bricks_data = Bricks\Database::get_template_data( 'archive' );

if ( $bricks_data ) {
	Bricks\Frontend::render_content( $bricks_data );
} else {
	echo '<div class="bricks-archive-title-wrapper brxe-container">';
	the_archive_title( '<h1 class="title">', '</h1>' );
	echo '</div>';

	include locate_template( 'template-parts/content.php' );
}

get_footer();
