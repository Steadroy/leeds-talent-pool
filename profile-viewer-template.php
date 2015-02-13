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
		'fields' => 'all_with_meta'
	) );
	if ( count( $users ) ) {
		foreach ( $users as $user ) {
			$students[$user->user_login] = leeds_talent_pool::get_user_data( $user );
		}
	}
	//print('<pre>' . print_r($students, true) . '</pre>');

	// apply filters on $stiudents to see which pages are to be displayed
	$to_display = array();
	if ( ! isset( $_REQUEST["filter"] ) ) {
		$to_display = $students;
	} else {

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