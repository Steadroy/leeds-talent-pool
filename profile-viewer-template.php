<?php
/*
Template Name: Profile Viewer Page
*/
if ( ! isset($_SERVER["HTTPS"] ) ) {
	ltp_redirect_to("viewer");
}

$options = ltp_options::get_options();
// redirect users with incorrect roles
if ( is_user_logged_in() ) {
	if ( ! ltp_is_student() && ! ltp_is_wpp() && ! ltp_is_admin() ) {
		ltp_redirect_to("invalid_role");
	} elseif ( ltp_is_student() ) {
		ltp_redirect_to("builder");
	}
} else {
	ltp_redirect_to('login');
}
