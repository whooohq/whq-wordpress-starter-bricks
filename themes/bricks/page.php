<?php
get_header();

$bricks_data = Bricks\Helpers::get_bricks_data( get_the_ID(), 'content' );

if ( $bricks_data ) {
	Bricks\Frontend::render_content( $bricks_data );
} else {
	if ( have_posts() ) :
		while ( have_posts() ) :
			the_post();
			get_template_part( 'template-parts/page' );
		endwhile;
	endif;
}

get_footer();
