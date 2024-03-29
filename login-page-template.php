<?php
/*
Template Name: Login page
*/
$options = ltp_options::get_options();
// redirect to https
if ( ! isset($_SERVER["HTTPS"] ) && ( isset( $options["debug_ssl"] ) && intval( $options["debug_ssl"] ) > 0 ) ) {
	ltp_redirect_to("login");
}
// redirect if the user is logged in
if ( is_user_logged_in() ) {
	if ( ! ltp_is_admin() ) {
		if ( ! ltp_is_student() && ! ltp_is_wpp() ) {
			ltp_redirect_to("invalid_role");
		} elseif ( ltp_is_student() ) {
			ltp_redirect_to("builder");
		} elseif ( ltp_is_wpp() ) {
			ltp_redirect_to("viewer");
		}
	}
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