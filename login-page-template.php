<?php
/*
Template Name: Login page
*/

// redirect to home page if the user is logged in
if ( is_user_logged_in() ) {
	wp_redirect( site_url() );
}

get_header();

// display failure message for login
if (isset($_GET['login']) && $_GET['login'] == 'failed') {
	?>
		<div id="login-error">
			<p>Login failed: You have entered an incorrect Username or password, please try again.</p>
		</div>
	<?php
}
wp_login_form( array(
	'echo' => true,
	'redirect' => site_url( $_SERVER['REQUEST_URI'] ), 
	'remember' => false,
) );

get_footer();