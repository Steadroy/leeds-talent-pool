<?php
/*
Template Name: Profile Builder page
*/
$has_page = false;
$is_published = false;
$user_page = false;
$errors = '';
$current_user = wp_get_current_user();
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
 * If a user has a page which is published, they see the update button and unpublish button
 */
if ( isset( $_REQUEST["save"] ) || isset( $_REQUEST["preview"] ) || isset( $_REQUEST["publish"] ) || isset( $_REQUEST["unpublish"] ) || isset( $_REQUEST["update"] ) ) {
	global $current_user;
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
		// update or publish - only field in page which needs updating is post title
		$args = array(
			"ID" => $user_page->ID,
			"post_title" => $_REQUEST["firstname"] . " " . $_REQUEST["surname"],
		);
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
		wp_safe_redirect( get_permalink( $user_page->ID ) . $qs );
	}
}

get_header();
if ( have_posts() ) while ( have_posts() ) : the_post();

	if ( class_exists("PeoplePostType") ) {
		printf( '<h2>%s</h2><form action="%s" method="post" class="ltp-profile-builder">', get_the_title(), get_permalink( $post->ID ) );
		echo PeoplePostType::get_profile_field_control( array('field_name' => 'nonce') );

		//printf( '<p class="compulsory">Compulsory field</p>');

		printf( '<div class="section sticky"><h3>Profile Completion</h3><div class="completion-meter"><span></span></div>' );
		if ( ! $is_published ) {
			print( '<button name="preview" class="ppt-button ppt-preview-button">Preview</button>' );
		}
		if ( ! $has_page ) {
			print( '<button name="save" class="ppt-button ppt-save-button">Save</button>' );
		} else {
			print( '<button name="update" class="ppt-button ppt-save-button">Update</button>' );
		}
		if ( ! $has_page || ( $has_page && ! $is_published ) ) {
			print( '<button name="publish" class="ppt-button ppt-publish-button">Publish</button>' );
		}
		if ( $has_page && $is_published ) {
			print( '<button name="unpublish" class="ppt-button ppt-publish-button">Un-publish</button>' );
		}
		print( '</div>' );
		
		// student photo
		print('<div class="section"><h3>1. Upload Photo</h3>');
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
		print( '</div>' );

		for ( $i = 1; $i <= 3; $i++ ) {
			printf( '<div class="section"><h3>%d. Showcase %d</h3>', ($i + 6), $i );
			echo PeoplePostType::get_profile_field_control( array('field_name' => 'showcase' . $i . '_title') );
			echo PeoplePostType::get_profile_field_control( array('field_name' => 'showcase' . $i . '_text') );
			echo PeoplePostType::get_profile_field_control( array('field_name' => 'showcase' . $i . '_image') );
			echo PeoplePostType::get_profile_field_control( array('field_name' => 'showcase' . $i . '_file') );
			print( '</div>' );
		}

		print( '</form>' );
	} else {
		print('<p>This template requires the People Post Type Plugin to be installed and activated.</p>');
	}
endwhile;
get_footer();