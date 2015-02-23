<?php
/**
 * Single post template
 * 
 * @author Peter Edwards <p.l.edwards@leeds.ac.uk>
 * @version 1.1
 * @package Wordpress
 * @subpackage UoL_theme
 */
$options = ltp_options::get_options();
// redirect users with incorrect roles
if ( is_user_logged_in() ) {
	if ( ! ltp_is_admin() ) {
		if ( ! ltp_is_student() && ! ltp_is_wpp() ) {
			ltp_redirect_to("invalid_role");
		}
	}
} else {
	ltp_redirect_to('login');
}	
ltp_data::save_actions();

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

	// log view if wpp user
	if ( ltp_is_wpp() ) {
		ltp_data::log_view($current_user->ID, $post->ID);
	}

	/* start profile output */
	print('<div class="ltp-profile-viewer">');

	// se if a CV has been uploaded
	$cv_URL = '';
	$cv_ID = get_user_meta( $user->ID, 'cv', true );
	if ( $cv_ID !== '' ) {
		$cv_URL = wp_get_attachment_url( $cv_ID );
	}
	// start toolbar output
	print('<div class="section sticky toolbar">');
	if ( ltp_is_wpp() ) {
		// WPP users toolbar
		print( ltp_template::wpp_profile_toolbar( $current_user->ID, $post->ID ) );
	} elseif ( $current_user->ID == $user->ID ) {
		// student toolbar
		printf( '<form action="%s" method="post">', get_permalink( $options["builder_page_id"] ) );
		print( '<button name="return" class="ppt-button ppt-return-button">Return to profile builder</button>' );
		if ( $post->post_status !== 'publish' ) {
			print( '<button name="publish" class="ppt-button ppt-publish-button">Publish</button>' );
		} else {
			print( '<button name="unpublish" class="ppt-button ppt-unpublish-button">Un-publish</button>' );
		}
		print('<p><em>This is how recruiters will see your profile</em></p>');
		print('</form>');
	}
	print('</div>');

	print('<div class="ltp-profile-wrap"><div class="vcard">');

	/* photo */
	$photo_ID = get_user_meta( $user->ID, 'photo', true );
	if ( intval( $photo_ID) > 0 ) {
		$photo_thumb = wp_get_attachment_image_src( $photo_ID, 'thumbnail' );
		$photo_large = wp_get_attachment_image_src( $photo_ID, 'large' );
		printf('<div class="photo"><a href="%s" title="%s"><img src="%s"></a></div>', $photo_large[0], esc_attr($post->post_title), $photo_thumb[0] );
	}
	printf('<h2 class="full-name">%s</h2>', $post->post_title);
	printf('<p><strong>Qualifications:</strong> %s</p>', get_user_meta( $user->ID, 'qualifications', true) );
	$loc = get_user_meta( $current_user->ID, 'region', true );
	if ( is_array($loc) && count($loc) && $loc[0] !== 'null' ) {
		printf('<p><strong>Current location:</strong> %s</p>', $loc[0] );
	}
	printf('<p><strong>Willing to work in:</strong> %s</p>', implode(", ", get_user_meta( $user->ID, 'desired_region', true) ) );
	$exp = get_user_meta( $user->ID, 'experience', true );
	if ( is_array($exp) && count($exp) && $exp[0] !== 'null' ) {
		printf('<p><strong>Experience (years):</strong> %s</p>',  $exp[0]);
	}
	printf('<p><strong>Expertise:</strong> %s</p>', implode(", ", get_user_meta( $user->ID, 'expertise', true) ) );
	if ( ! ltp_is_wpp() && ! empty( $cv_URL ) ) {
		printf('<p><a href="%s" class="profile-button">Download CV</a></p>', $cv_URL );
	}
	print('</div>');
	print( apply_filters('the_content', get_user_meta( $user->ID, 'statement', true ) ) );
	print('<h2 class="showcase-title">Student Creative/Analytical Showcase</h2>');
	print('<div class="showcase-thumbs">');


	$full_text = array();
	for ( $i = 1; $i <= 3; $i++ ) {
		$full_text["sc" . $i] = sprintf( '<div class="showcase" id="showcase-%d"><h3>%s</h3>', $i, get_user_meta( $current_user->ID, 'showcase' . $i . '_title', true ) );
		$image_ID = get_user_meta( $user->ID, 'showcase' . $i . '_image', true );
		$showcase_thumb_url = $showcase_embed = false;
		if ( intval( $image_ID ) > 0 ) {
			$showcase_thumb = wp_get_attachment_image_src( $image_ID, 'thumbnail' );
			if ( $showcase_thumb ) {
				$showcase_thumb_url = $showcase_thumb[0];
				$showcase_large = wp_get_attachment_image_src( $image_ID, 'large' );
				$showcase_embed = sprintf('<img class="showcase-large" src="%s">', $showcase_large[0]);
			}
		}
		$video_url = get_user_meta( $user->ID, 'showcase' . $i . '_video', true );
		if ( $video_url !== '' ) {
			if ( preg_match( '%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $video_url, $match ) ) {
				$video_id = $match[1];
				$showcase_thumb_url = sprintf('http://img.youtube.com/vi/%s/hqdefault.jpg', $video_id);
				$padding = ((360/640)*100) . '%';
				$showcase_embed = sprintf('<div class="video-wrap"><div class="video-container" style="padding-bottom:%s"><iframe width="640" height="360" src="https://www.youtube.com/embed/%s" frameborder="0" allowfullscreen></iframe></div></div>', $padding, $video_id);
			} elseif ( preg_match('/vimeo\.com/', $video_url) ) {
				$oembed_url = 'http://vimeo.com/api/oembed.json?url=' . urlencode($video_url);
				$curl = curl_init();
				curl_setopt($curl, CURLOPT_URL, $oembed_url);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
				curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
				if ( isset($_SERVER["SERVER_ADDR"]) && $_SERVER["SERVER_ADDR"] == "127.0.0.1" )
				{
					curl_setopt($curl, CURLOPT_PROXY, "http://www-cache.leeds.ac.uk:3128");
				}
				$json = curl_exec($curl);
				curl_close($curl);
				$video_data = json_decode($json);
				if ( $video_data ) {
					$video_id = $video_data->video_id;
					$showcase_thumb_url = $video_data->thumbnail_url;
					$padding = (($video_data->height/$video_data->width)*100) . '%';
					$showcase_embed = sprintf('<div class="video-wrap"><div class="video-container" style="padding-bottom:%s"><iframe src="//player.vimeo.com/video/%s?color=fe7500" width="%d" height="%d" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe></div></div>', $padding, $video_id, $video_data->width, $video_data->height);
				}
			}
		}
		if ( $showcase_embed ) {
			$full_text["sc" . $i] .= $showcase_embed;
		}
		$full_text["sc" . $i] .= apply_filters( 'the_content', get_user_meta( $user->ID, 'showcase' . $i . '_text', true ) );
		$file_ID = get_user_meta( $user->ID, 'showcase' . $i . '_file', true );
		if ( intval( $file_ID ) > 0 ) {
			$file_url = wp_get_attachment_url( $file_ID );
			if ( $file_url ) {
				$file_title = get_the_title( $file_ID );
				if ( trim($file_title) === '') {
					$file_title = $file_url;
				}
				$full_text["sc" . $i] .= sprintf('<p><a class="profile-button" href="%s">%s</a></p>', $file_url, $file_title);
			}
		}
		$full_text["sc" . $i] .= '</div>';
		if ($showcase_thumb_url) {
			printf('<div class="showcase-thumb" id="showcase_thumb%d"><img src="%s"><a class="profile-button showcase-button" href="#showcase-%d" rel="showcase">More</a></div>', $i, $showcase_thumb_url, $i);
		}
	}
	print('</div></div>');
	foreach ( $full_text as $sc ) {
		print($sc);
	}
	print( '</div>' );
endwhile;
endif;
get_footer();