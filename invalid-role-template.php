<?php
/*
Template Name: Invalid Role page
*/
if ( ! isset($_SERVER["HTTPS"] ) ) {
	ltp_redirect_to("invalid-role");
}

$options = ltp_options::get_options();
// redirect if the user is logged in
if ( is_user_logged_in() ) {
	if ( ltp_is_student() ) {
		ltp_redirect_to( "builder" );
	} elseif ( ltp_is_wpp() ) {
		ltp_redirect_to( "viewer" );
	}
} else {
	ltp_redirect_to('login');
}

get_header();
if ( have_posts() ) while ( have_posts() ) : the_post();

	print('<div class="invalid-role-page">');
	printf('<h2>%s</h2>', get_the_title() );
	the_content();
	print('</div>');

endwhile;
get_footer(); ?>