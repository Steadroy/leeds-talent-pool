<?php
/*
Template Name: Profile Builder page
*/
$options = ltp_options::get_options();
// redirect to https
if ( ! isset($_SERVER["HTTPS"] ) && LTP_FORCE_SSL ) {
	ltp_redirect_to("builder");
}

// redirect users with incorrect roles
if ( is_user_logged_in() ) {
	if ( ! ltp_is_admin() ) {
		if ( ! ltp_is_student() && ! ltp_is_wpp() ) {
			ltp_redirect_to("invalid_role");
		} elseif ( ltp_is_wpp() ) {
			ltp_redirect_to("viewer");
		}
	}
} else {
	ltp_redirect_to('login');
}

$has_page = false;
$is_published = false;
$user_page = false;
$errors = '';
global $current_user;
$people_pages = get_posts(array(
	'post_type' => 'people',
	'posts_per_page' => 1,
	'meta_query' => array(
		array(
			'key' => 'wp_username',
			'value' => $current_user->user_login,
		)
	),
	'post_status' => array('publish', 'draft')
));
if ( count( $people_pages ) ) {
	$has_page = true;
	$user_page = $people_pages[0];
	if ( $user_page->post_status === "publish" ) {
		$is_published = true;
	}
}
/*
 * If a user has no page, they see the preview, save and publish buttons
 * If a user has a page which is still draft status, they see the preview, update and publish buttons
 * If a user has a page which is published, they see the view, update button and unpublish button
 */
if ( isset( $_REQUEST["save"] ) || isset( $_REQUEST["view"] ) || isset( $_REQUEST["preview"] ) || isset( $_REQUEST["publish"] ) || isset( $_REQUEST["unpublish"] ) || isset( $_REQUEST["update"] ) ) {
	PeoplePostType::save_extra_profile_fields( $current_user->ID );
	// first check the user has a page
	if ( ! $has_page ) {
		// only preview and publish buttons are present when a user doesn't have a page
		$post_status = ( isset( $_REQUEST["publish"] ) ) ? 'publish': 'draft';
		$person_page_id = wp_insert_post( array(
			"post_type" => "people",
			"post_status" => $post_status,
			"post_title" => $_REQUEST["firstname"] . " " . $_REQUEST["surname"],
			"post_author" => $current_user->ID
		), false );
		if ( $person_page_id === 0 ) {
			$errors .= '<p>Failed to create user page</p>';
		} else {
			// set user ID
			update_post_meta( $person_page_id, 'wp_username', $current_user->user_login );
			// get the page
			$user_page = get_post( $person_page_id );
			$has_page = true;
			$is_published = ( $user_page->post_status == "publish" ) ? true: false;
		}
	} else {
		// update or publish
		$args = array(
			"ID" => $user_page->ID,
		);
		if ( isset( $_REQUEST["firstname"] ) && isset( $_REQUEST["surname"] ) ) {
			$args["post_title"] = $_REQUEST["firstname"] . " " . $_REQUEST["surname"];
			wp_update_user( array( 
				'ID' => $current_user->ID, 
				'first_name' => $_REQUEST["firstname"], 
				'last_name' => $_REQUEST["surname"],
				'display_name' => $_REQUEST["firstname"] . " " . $_REQUEST["surname"]
			) );
		}
		if ( isset( $_REQUEST["publish"] ) ) {
			$args["post_status"] = 'publish';
			$is_published = true;
		}
		if ( isset( $_REQUEST["unpublish"] ) ) {
			$args["post_status"] = 'draft';
			$is_published = false;
		}
		wp_update_post( $args );
	}
	if ( isset( $_REQUEST["preview"] ) && $user_page ) {
		$qs = ( $is_published ) ? '?preview=1': '&preview=1';
		if ( LTP_FORCE_SSL ) {
			wp_redirect( str_replace('http:', 'https:', get_permalink( $user_page->ID ) ) . $qs );
		} else {
			wp_redirect( get_permalink( $user_page->ID ) . $qs );
		}
	} elseif (isset( $_REQUEST["view"] ) && $user_page ) {
		if ( LTP_FORCE_SSL ) {
			wp_redirect( str_replace('http:', 'https:', get_permalink( $user_page->ID ) ) );
		} else {
			wp_redirect( get_permalink( $user_page->ID ) );
		}
	}
}

get_header();

if ( have_posts() ) while ( have_posts() ) : the_post();

	if ( class_exists("PeoplePostType") ) {
		printf( '<form action="%s" method="post" class="ltp-profile-builder"><h2>%s</h2>', get_permalink( $post->ID ), get_the_title() );
		echo PeoplePostType::get_profile_field_control( array('field_name' => 'nonce') );

		//printf( '<p class="compulsory">Compulsory field</p>');

		echo leeds_talent_pool::profile_toolbar( $has_page, $is_published );
		
		// student photo
		print('<div class="section top"><h3>1. Upload Photo</h3>');
		echo PeoplePostType::get_profile_field_control( array('field_name' => 'photo') );
		print('</div>');

		// student basic information
		print( '<div class="section"><h3>2. Basic Information</h3>' );
		echo PeoplePostType::get_profile_field_control( array('field_name' => 'firstname') );
		echo PeoplePostType::get_profile_field_control( array('field_name' => 'surname') );
		echo '<br style="clear:both">';
		echo PeoplePostType::get_profile_field_control( array('field_name' => 'gender') );
		echo PeoplePostType::get_profile_field_control( array('field_name' => 'experience') );
		echo PeoplePostType::get_profile_field_control( array('field_name' => 'region') );
		print( '</div>' );

		print( '<div class="section"><h3>3. Regions I would like to work in</h3><div class="checkbox-list">' );
		echo PeoplePostType::get_profile_field_control( array('field_name' => 'desired_region') );
		print( '</div></div>' );

		print( '<div class="section"><h3>4. Expertise</h3><div class="checkbox-list">' );
		echo PeoplePostType::get_profile_field_control( array('field_name' => 'expertise') );
		print( '</div></div>' );

		print( '<div class="section"><h3>5. Achievements</h3>' );
		echo PeoplePostType::get_profile_field_control( array('field_name' => 'achievements') );
		print( '</div>' );

		//personal Statement
		print( '<div class="section"><h3>6. Personal Statement</h3>' );
		echo PeoplePostType::get_profile_field_control( array('field_name' => 'statement') );
		echo PeoplePostType::get_profile_field_control( array('field_name' => 'cv') );
		print( '</div>' );

		for ( $i = 1; $i <= 3; $i++ ) {
			printf( '<div class="section"><h3>%d. Showcase %d</h3>', ($i + 6), $i );
			echo PeoplePostType::get_profile_field_control( array('field_name' => 'showcase' . $i . '_title') );
			echo PeoplePostType::get_profile_field_control( array('field_name' => 'showcase' . $i . '_text') );
			echo PeoplePostType::get_profile_field_control( array('field_name' => 'showcase' . $i . '_image') );
			echo '<p style="margin:0;"><em>Or video&hellip;</em></p>';
			echo PeoplePostType::get_profile_field_control( array('field_name' => 'showcase' . $i . '_video') );
			echo PeoplePostType::get_profile_field_control( array('field_name' => 'showcase' . $i . '_file') );
			print( '</div>' );
		}

		print( '</form>' );
	} else {
		print('<p>This template requires the People Post Type Plugin to be installed and activated.</p>');
	}
endwhile;
get_footer();