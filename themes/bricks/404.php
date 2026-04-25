<?php
get_header();

$bricks_data = Bricks\Helpers::get_bricks_data( get_the_ID(), 'error' );

if ( $bricks_data ) {
	Bricks\Frontend::render_content( $bricks_data );
} else {
	echo '<main id="brx-content" class="bricks-404-wrapper brxe-container">';
	echo '<h1 class="title">' . esc_html__( 'Whoops, that page is gone', 'bricks' ) . '</h1>';
	get_search_form( true );
	echo '</main>';
}

get_footer();
