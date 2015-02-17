<?php
$options = ltp_options::get_options();
// redirect to https
if ( ! isset($_SERVER["HTTPS"] ) && ( isset( $options["debug_ssl"] ) && intval( $options["debug_ssl"] ) > 0 ) ) {
	ltp_redirect_to("invalid-role");
}
if ( is_user_logged_in() ) {
	if ( ! ltp_is_admin() ) {
		if ( ! ltp_is_student() && ! ltp_is_wpp() ) {
			ltp_redirect_to("invalid_role");
		} elseif ( ltp_is_student() ) {
			ltp_redirect_to("builder");
		} elseif ( ltp_is_wpp() ) {
			ltp_redirect_to("viewer");
		}
	} else {
		ltp_redirect_to("viewer");
	}
} else {
	ltp_redirect_to("login");
}
