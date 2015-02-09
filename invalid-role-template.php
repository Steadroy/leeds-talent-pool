<?php
/*
Template Name: Invalid Role page
*/
get_header();
if ( have_posts() ) while ( have_posts() ) : the_post();

	print('<div class="invalid-role-page">');
	printf('<h2>%s</h2>', get_the_title() );
	the_content();
	print('</div>');

endwhile;
get_footer(); ?>