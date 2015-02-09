<?php
/*
Template Name: Profile Viewer Page
*/
$options = ltp_options::get_options();
// redirect users with incorrect roles
if ( is_user_logged_in() ) {
	if ( ! is_student() && ! is_wpp() ) {
		if ( isset( $options["invalid_role_page_id"] ) ) {
			wp_redirect( get_permalink( $options["invalid_role_page_id"] ) );
		}
	} elseif ( is_student() ) {
		if ( isset( $options["builder_page_id"] ) ) {
			wp_redirect( get_permalink( $options["builder_page_id"] ) );
		}
	}
}
