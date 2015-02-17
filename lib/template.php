<?php
/**
 * Templating class for the Leeds Talent Pool theme
 * This class provides template and display related functions for the theme
 */

if ( ! class_exists( 'ltp_template' ) ) {

	class ltp_template
	{
		/**
		 * gets a user's profile data
		 * @param integer User ID
		 */
		public static function get_user_data( $user )
		{
			$meta = get_user_meta( $user->ID );
			// put simple string data into $userdata
			$userdata = array(
				"user" => $user
			);
			$string_fields = array(
				"firstname",
				"surname",
				"photo",
				"achievements",
				"statement",
				"cv",
				"showcase1_title",
				"showcase1_text",
				"showcase1_image",
				"showcase1_file",
				"showcase1_video",
				"showcase2_title",
				"showcase2_text",
				"showcase2_image",
				"showcase2_file",
				"showcase2_video",
				"showcase3_title",
				"showcase3_text",
				"showcase3_image",
				"showcase3_file",
				"showcase3_video"
			);
			$array_fields = array(
				"gender",
				"experience",
				"region",
				"desired_region",
				"expertise"
			);
			foreach ( $string_fields as $f ) {
				if ( isset( $meta[$f] ) ) {
					$userdata[$f] = $meta[$f][0];
				} else {
					$userdata[$f] = '';
				}
			}
			foreach ( $array_fields as $a ) {
				if ( isset( $meta[$a] ) ) {
					$userdata[$a] = unserialize($meta[$a][0]);
				} else {
					$userdata[$a] = array();
				}
			}
			return $userdata;
		}

		public static function get_vcard( $student, $page_id, $left = false )
		{
			$filter_attr = array(
				"experience",
				"region",
				"desired_region",
				"expertise"
			);
			//$vcard = '<pre>' . print_r($student, true) . '</pre>';
			$classes = $left ? "left ": "right ";
			foreach ( $filter_attr as $att ) {
				if ( isset( $student[$att] ) && is_array( $student[$att] ) && count( $student[$att] ) ) {
					foreach ( $student[$att]  as $val ) {
						$classes .= $att . '-' . str_replace(' ', '-', $val) . ' ';
					}
				}
			}
			$vcard .= sprintf( '<div class="ltp-profile-wrap %s"><div class="vcard">', trim( $classes ) );
			// get full name 
			$fullname = $student["firstname"] . " " . $student["surname"];
			if ( isset( $student['photo'] ) && intval( $student['photo'] ) > 0 ) {
				$photo_thumb = wp_get_attachment_image_src( $student['photo'], 'thumbnail' );
				$vcard .= sprintf('<div class="photo"><img title="%s" src="%s"></div>', esc_attr($fullname), $photo_thumb[0] );
			}
			$vcard .= sprintf('<h2 class="full-name">%s</h2>', $fullname);
			if ( isset( $student['qualifications'] ) && $student['qualifications'] !== '' ) {
				$vcard .= sprintf('<p><strong>Qualifications:</strong> %s</p>', $student['qualifications'] );
			}
			if ( is_array($student['region']) && count($student['region']) && $student['region'][0] !== 'null' ) {
				$vcard .= sprintf('<p><strong>Current location:</strong> %s</p>', $student['region'][0] );
			}
			if ( is_array($student['desired_region']) && count($student['desired_region']) ) {
				$vcard .= sprintf('<p><strong>Willing to work in:</strong> %s</p>', implode(", ", $student['desired_region'] ) );
			}
			if ( is_array($student['experience']) && count($student['experience']) && $student['experience'][0] !== 'null' ) {
				$vcard .= sprintf('<p><strong>Experience (years):</strong> %s</p>',  $student['experience'][0]);
			}
			if ( is_array($student['expertise']) && count($student['expertise']) ) {
				$vcard .= sprintf('<p><strong>Expertise:</strong> %s</p>', implode(", ", $student['expertise'] ) );
			}
			$cv_url = false;
			if ( $student['cv'] !== '' ) {
				$cv_url = wp_get_attachment_url( $student['cv'] );
			}
			if ( ltp_is_wpp() ) {
				global $current_user;
				$vcard .= self::wpp_toolbar( $current_user->ID, $page_id, $cv_url, true );
			}
			$vcard .= '</div></div>';
			return $vcard;
		}
		
		/**
		 * returns the sticky toolbar for student users
		 */
		public static function profile_toolbar( $has_page, $is_published )
		{
			$toolbar = '<div class="section sticky"><h3>Profile Completion</h3><div class="completion-meter"><span></span></div>';
			if ( ! $is_published ) {
				$toolbar .= '<button name="preview" class="ppt-button ppt-preview-button">Preview</button>';
			} else {
				$toolbar .= '<button name="view" class="ppt-button ppt-view-button">View</button>';
			}
			if ( ! $has_page ) {
				$toolbar .= '<button name="save" class="ppt-button ppt-save-button">Save</button>';
			} else {
				$toolbar .= '<button name="update" class="ppt-button ppt-update-button">Update</button>';
			}
			if ( ! $has_page || ( $has_page && ! $is_published ) ) {
				$toolbar .= '<button name="publish" class="ppt-button ppt-publish-button">Publish</button>';
			}
			if ( $has_page && $is_published ) {
				$toolbar .= '<button name="unpublish" class="ppt-button ppt-unpublish-button">Un-publish</button>';
			}
			$toolbar .= '</div>';
			return $toolbar;
		}

		/**
		 * returns a toolbar for wpp users
		 * present at the top of single profile pages in sticky toolbar, and embedded into vcards on viewer page
		 */
		public static function wpp_toolbar( $user_id, $profile_page_id, $cv_URL = false, $link_to_page = false )
		{
			$toolbar = sprintf('<form action="%s" method="post">', $_SERVER["REQUEST_URI"] );
			$toolbar .= sprintf('<input type="hidden" name="user_id" value="%s">', $user_id );
			$toolbar .= sprintf('<input type="hidden" name="profile_page_id" value="%s">', $profile_page_id );
			if ( $link_to_page ) {
				$toolbar .= sprintf('<a class="profile-button" href="%s">View Profile</a>', get_permalink( $profile_page_id ) );
			}
			if ( $cv_URL ) {
				$toolbar .= sprintf('<input type="hidden" name="cv_url" value="%s">', esc_attr( $cv_URL ) );
				$toolbar .= '<button type="submit" name="action" value="cv_download" class="ppt-button">Download CV</button>';
			}
			if ( ltp_data::is_saved( $user_id, $profile_page_id ) ) {
				$toolbar .= sprintf('<button name="action" value="remove" class="ppt-button">Remove</button>');
			} else {
				$toolbar .= sprintf('<button name="action" value="save" class="ppt-button">Save</button>');
			}
			$toolbar .= '</form>';
			return $toolbar;
		}
	}
}