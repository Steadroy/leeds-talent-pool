<?php
/**
 * Single post template
 * 
 * @author Peter Edwards <p.l.edwards@leeds.ac.uk>
 * @version 1.1
 * @package Wordpress
 * @subpackage UoL_theme
 */

get_header(); 


// start Wordpress loop
if (have_posts()) : while (have_posts()) : the_post();

	$current_user = wp_get_current_user();
	$username = get_post_meta($post->ID, 'wp_username', true);
	$user = false;

	if ( ! empty( $username ) ) {
		$user = get_user_by( 'login', $username );
	}
	$options = ltp_options::get_options();

	/* start profile output */
	print('<div class="ltp-profile-viewer">');

	if ( $current_user->ID == $user->ID ) {
		printf( '<div class="sticky toolbar"><form action="%s" method="post">', get_permalink( $options["builder_page_id"] ) );
		print( '<button name="return" class="ppt-button ppt-return-button">Return to profile builder</button>' );
		if ( $post->post_status !== 'publish' ) {
			print( '<button name="publish" class="ppt-button ppt-publish-button">Publish</button>' );
		} else {
			print( '<button name="unpublish" class="ppt-button ppt-unpublish-button">Un-publish</button>' );
		}
		print('<p><em>This is how recruiters will see your profile</em></p>');
		print('</form></div>');
	}

	print('<div class="vcard">');

	/* photo */
	$photo_ID = get_user_meta( $current_user->ID, 'photo', true );
	if ( intval( $photo_ID) > 0 ) {
		$photo_thumb = wp_get_attachment_image_src( $photo_ID, 'thumbnail' );
		$photo_large = wp_get_attachment_image_src( $photo_ID, 'large' );
		printf('<div class="photo"><a href="%s" title="%s"<img src="%s"></a></div>', $photo_large[0], esc_attr($post->post_title), $photo_thumb[0] );
	}
	printf('<h2 class="full-name">%s</h2>', $post->post_title);
	printf('<p><strong>Qualifications:</strong> %s</p>', get_user_meta( $current_user->ID, 'qualifications', true) );
	printf('<p><strong>Current location:</strong> %s</p>', implode(", ", get_user_meta( $current_user->ID, 'region', true) ) );
	printf('<p><strong>Willing to work in:</strong> %s</p>', implode(", ", get_user_meta( $current_user->ID, 'desired_region', true) ) );
	printf('<p><strong>Experience (years):</strong> %s</p>', get_user_meta( $current_user->ID, 'experience', true)[0] );
	printf('<p><strong>Expertise:</strong> %s</p>', implode(", ", get_user_meta( $current_user->ID, 'expertise', true) ) );
	$cv_ID = get_user_meta( $current_user->ID, 'cv', true );
	if ( $cv_ID !== '' ) {
		$cv_url = get_attachment_link( $cv_ID );
		printf('<p><a href="%s" class="profile-button">Download CV</a></p>', $cv_url );
	}
	print('</div>');
	print( wptexturize( get_user_meta( $current_user->ID, 'statement', true ) ) );
	print('<div class="showcase-thumbs">');


	$full_text = array();
	for ( $i = 1; $i <= 3; $i++ ) {
		$full_text["sc" . $i] = sprintf( '<div class="showcase" id="showcase-%d"><h3>%s</h3>', $i, get_user_meta( $current_user->ID, 'showcase' . $i . '_title', true ) );
		$full_text["sc" . $i] .= wptexturize( get_user_meta( $current_user->ID, 'showcase' . $i . '_text', true ) );
		$image_ID = get_user_meta( $current_user->ID, 'showcase' . $i . '_image', true );
		$showcase_thumb = $showcase_large = false;
		if ( intval( $image_ID ) > 0 ) {
			$showcase_thumb = wp_get_attachment_image_src( $image_ID, 'thumbnail' );
			$showcase_large = wp_get_attachment_image_src( $image_ID, 'large' );
		}
		$file_ID = get_user_meta( $current_user->ID, 'showcase' . $i . '_file', true );
		$file_url = get_attachment_link( $file_ID );
		$file_title = get_the_title( $file_ID );
		$video_url = get_user_meta( $current_user->ID, 'showcase' . $i . '_video', true );
		$full_text["sc" . $i] .= '</div>';
		if ($showcase_thumb) {
			printf('<div class="showcase_thumb" id="showcase_thumb%d"><img src="%s"><button class="profile-button" data-showcaseid="%d">More</button></div>', $i, $showcase_thumb[0], $i);
		}
	}
	print('</div>');
	foreach ( $full_text as $sc ) {
		print($sc);
	}
	print( '</div>' );
endwhile;
endif;
get_footer();