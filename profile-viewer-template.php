<?php
/*
Template Name: Profile Viewer Page
*/
$options = ltp_options::get_options();
if ( ! isset($_SERVER["HTTPS"] ) && (isset( $options["debug_ssl"] ) && intval( $options["debug_ssl"] ) > 0 ) {
	ltp_redirect_to("viewer");
}

// redirect users with incorrect roles
if ( is_user_logged_in() ) {
	if ( ! ltp_is_admin() ) {
		if ( ! ltp_is_student() && ! ltp_is_wpp() ) {
			ltp_redirect_to("invalid_role");
		} elseif ( ltp_is_student() ) {
			ltp_redirect_to("builder");
		}
	}
} else {
	ltp_redirect_to('login');
}

// get the people pages - admins see drafts as well
$post_status = ( ltp_is_admin() ) ? array('publish', 'draft'): 'publish'; 
$people_pages = get_posts(array(
	'post_type' => 'people',
	'nopaging' => true,
	'post_status' => $post_status
));

get_header();

if ( count( $people_pages ) ) {

	// get all students
	$students = array();
	$users = get_users( array(
		'role' => 'student',
		'fields' => 'ID'
	) );
	if ( count( $users ) ) {
		foreach ( $users as $userid ) {
			$students[$user->user_login] = leeds_talent_pool::get_user_data( $userid );
		}
	}
	print('<pre>' . print_r($students, true) . '</pre>');

	// apply filters on $students to see which pages are to be displayed
	$to_display = apply_filters( 'ltp_results', $students );
	if ( ! isset( $_REQUEST["filter"] ) ) {
		$to_display = $students;
	} else {
		switch ( $_REQUEST["filter"] ) {
			case "region":

				break;
			case "desired_region":

				break;
			case "expertise":

		}
	}

	// loop through people pages displaying users
	foreach ( $people_pages as $post ) {
		$username = get_post_meta($post->ID, 'wp_username', true);
		if ( in_array( $username, array_keys( $to_display ) ) ) {
			// display user data
			$userdata = $to_display[$username];
		}
	}
} else {
	print('<p>No profiles have been added to the system yet - please try again later&hellip;</p>');
}

get_footer();