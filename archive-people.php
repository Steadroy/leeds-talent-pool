<?php
$options = ltp_options::get_options();
if ( is_user_logged_in() ) {
	if ( ! ltp_is_student() && ! ltp_is_wpp() && ! ltp_is_admin() ) {
		ltp_redirect_to("invalid_role");
	} elseif ( ltp_is_student() ) {
		ltp_redirect_to("builder");
	} elseif ( ltp_is_wpp() ) {
		ltp_redirect_to("viewer");
	}
} else {
	ltp_redirect_to("login");
}
