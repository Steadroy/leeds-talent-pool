<?php
$options = ltp_options::get_options();
if ( is_user_logged_in() ) {
	if ( ! is_student() && ! is_wpp() ) {
		if ( isset( $options["invalid_role_page_id"]))
			wp_redirect( get_permalink( $options["invalid_role_page_id"] ) );
		}
	} elseif ( is_student() ) {
		if ( isset( $options["builder_page_id"]))
			wp_redirect( get_permalink( $options["builder_page_id"] ) );
		}
	} elseif ( is_wpp() ) {
		if ( isset( $options["viewer_page_id"]))
			wp_redirect( get_permalink( $options["viewer_page_id"] ) );
		}
	}
	wp_redirect( site_url() );
} else {
	wp_redirect( site_url() );
}
