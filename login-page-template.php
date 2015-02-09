<?php
/*
Template Name: Login page
*/

$options = ltp_options::get_options();
// redirect if the user is logged in
if ( is_user_logged_in() ) {
	if ( ! is_student() && ! is_wpp() ) {
		if ( isset( $options["invalid_role_page_id"] ) ) {
			wp_redirect( get_permalink( $options["invalid_role_page_id"] ) );
		}
	} elseif ( is_student() ) {
		if ( isset( $options["builder_page_id"] ) ) {
			wp_redirect( get_permalink( $options["builder_page_id"] ) );
		}
	} elseif ( is_wpp() ) {
		if ( isset( $options["viewer_page_id"] ) ) {
			wp_redirect( get_permalink( $options["viewer_page_id"] ) );
		}
	}
	wp_redirect( site_url() );
}
get_header();

if ( have_posts() ) while ( have_posts() ) : the_post();

	print('<div class="ltp-login-page">');

	// display failure message for login
	if (isset($_GET['login']) && $_GET['login'] == 'failed') {
		print('<p class="login-error">Login failed: You have entered an incorrect Username or password, please try again.</p>');
	}

	printf('<h2>%s</h2><div class="ltp-login-form">', get_the_title());
	wp_login_form( array(
		'echo' => true,
		'redirect' => site_url( $_SERVER['REQUEST_URI'] ), 
		'remember' => false,
	) );
	print('</div>');
	the_content();

endwhile;

get_footer();