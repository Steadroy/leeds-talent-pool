<?php
/*
Template Name: Profile Viewer Page
*/
$options = ltp_options::get_options();
if ( ! isset($_SERVER["HTTPS"] ) && ( isset( $options["debug_ssl"] ) && intval( $options["debug_ssl"] ) > 0 ) ) {
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
ltp_data::save_actions();

get_header();

print('<div class="section sticky toolbar">');
print( ltp_template::wpp_toolbar() );
print('</div>');

print('<div class="ltp-profiles">');

if ( count( $people_pages ) ) {

	// get all students
	$students = array();
	$users = get_users( array(
		'role' => 'student',
		'fields' => 'all'
	) );

	if ( count( $users ) ) {
		foreach ( $users as $user ) {
			$students[$user->user_login] = ltp_template::get_user_data( $user );
		}
	}

	// apply filters on $students to see which pages are to be displayed
	//$to_display = apply_filters( 'ltp_results', $students );
	$count = 0;
	// loop through people pages displaying users
	foreach ( $people_pages as $post ) {
		$username = get_post_meta($post->ID, 'wp_username', true);
		if ( in_array( $username, array_keys( $students ) ) ) {
			// display user data
			if ( trim( $students[$username]["firstname"] ) !== '' && trim( $students[$username]["surname"] ) !== '' ) {
				$left = ( $count % 2 === 0 );
				print( ltp_template::get_vcard( $students[$username], $post->ID, $left ) );
				$count++;
			}
		}
	}
} else {
	print('<p>No profiles have been added to the system yet - please try again later&hellip;</p>');
}
print('</div>');
get_footer();