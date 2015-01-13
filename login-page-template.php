<?php
/*
Template Name: Login page
*/
if ( is_user_logged_in() ) {
	wp_redirect( site_url() );
}

get_header();

wp_login_form( array(
	'echo' => true,
	'redirect' => site_url( $_SERVER['REQUEST_URI'] ), 
	'remember' => false,
) );

get_footer();