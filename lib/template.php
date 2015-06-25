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

		public static function get_vcard( $student, $page_id, $latest )
		{
			global $current_user;

			$vcard = '';
			$filter_attr = array(
				"experience",
				"region",
				"desired_region",
				"expertise"
			);
			$classes = $latest? "latest ": "";
			foreach ( $filter_attr as $att ) {
				if ( isset( $student[$att] ) && is_array( $student[$att] ) && count( $student[$att] ) ) {
					foreach ( $student[$att]  as $val ) {
						$classes .= $att . '-' . preg_replace('/[^a-zA-Z0-9]+/', '', $val) . ' ';
					}
				}
			}
			if ( ltp_data::is_saved( $current_user->ID, $page_id ) ) {
				$classes .= ' saved';
			}
			$vcard .= sprintf( '<div id="ltp_profile_wrap_%s" class="ltp-profile-wrap %s"><div class="vcard">', $page_id, trim( $classes ) );
			// get full name 
			$fullname = $student["firstname"] . " " . $student["surname"];
			if ( isset( $student['photo'] ) && intval( $student['photo'] ) > 0 ) {
				$photo_thumb = wp_get_attachment_image_src( $student['photo'], 'thumbnail' );
				$vcard .= sprintf('<div class="photo"><img title="%s" src="%s"></div>', esc_attr($fullname), $photo_thumb[0] );
			}
			$vcard .= sprintf('<h2 class="full-name">%s</h2>', $fullname);
			$vcard .= sprintf('<p><strong>Email:</strong> <a href="mailto:%s" title="Email %s">%s</a></p>', $student["user"]->data->user_email, esc_attr( $fullname ), $student["user"]->data->user_email);
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
				$vcard .= self::wpp_profile_buttons( $current_user->ID, $page_id, $cv_url );
			}
			$vcard .= '</div></div>';
			return $vcard;
		}
		
		/**
		 * returns the sticky toolbar for student users
		 */
		public static function profile_toolbar( $has_page, $is_published )
		{
			$toolbar = '<div class="section sticky"><h3>Profile Completion</h3><div class="completion-meter"><span></span></div><div class="toolbar-buttons">';
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
			$toolbar .= '</div></div>';
			return $toolbar;
		}

		/**
		 * returns a toolbar for wpp users when viewing a single profile page, 
		 * or used for buttons on individual profiles in view mode
		 */
		public static function wpp_profile_toolbar( $user_id, $profile_page_id, $cv_URL = false )
		{
			$last_login_date = ltp_data::get_previous_login($user_id);
			$profiles_added = ltp_data::get_profiles_added_since($last_login_date);
			$toolbar = self::get_status_line( $user_id, $last_login_date, $profiles_added );
			$saved_profiles = ltp_data::has_saved( $user_id );
			$toolbar .= sprintf('<form action="%s" method="post" class="toolbar-buttons">', $_SERVER["REQUEST_URI"] );
			$toolbar .= sprintf('<input type="hidden" name="user_id" value="%s">', $user_id );
			$toolbar .= sprintf('<input type="hidden" name="profile_page_id" value="%s">', $profile_page_id );
			$toolbar .= sprintf('<a class="profile-button" href="%s">View all profiles</a>', ltp_get_page_url('viewer'));
			if ( $saved_profiles ) {
				$toolbar .= sprintf('<a class="profile-button" href="%s#saved">View Saved Profiles</a>', ltp_get_page_url('viewer'));
			}
			if ( $cv_URL ) {
				$toolbar .= sprintf('<input type="hidden" name="cv_url" value="%s">', esc_attr( $cv_URL ) );
				$toolbar .= '<button name="action" value="cv_download" class="ppt-button ajax-button">Download CV</button>';
			}
			if ( ltp_data::is_saved( $user_id, $profile_page_id ) ) {
				$toolbar .= sprintf('<button name="action" value="remove" class="ppt-button ajax-button">Remove</button>');
			} else {
				$toolbar .= sprintf('<button name="action" value="save" class="ppt-button ajax-button">Save</button>');
			}
			$toolbar .= '</form>';
			return $toolbar;
		}

		/**
		 * gets a status line for the WPP toolbar
		 */
		public static function get_status_line( $user_id, $last_login_date, $profiles_added )
		{
			$added_profiles = '';
			if ( $last_login_date == false ) {
				$last_login = "never";
			} else {
				$last_login = date('l jS \of F Y, H:i', $last_login_date);
				if ( count( $profiles_added ) ) {
					$added_profiles = sprintf(' | Profiles updated since your last login: <strong>%s</strong>', count( $profiles_added ) );
				} else {
					$added_profiles = ' | No profiles added since you last logged in';
				}
			}
			$history_link = ltp_data::user_has_history( $user_id )? ' | <a href="#" class="history" data-start="0" data-num="20" data-user_id="' . $user_id . '">History</a>': '';
			return sprintf('<div class="status">Last login: %s%s%s</div>', $last_login, $history_link, $added_profiles);
		}

		/**
		 * returns a set of buttons used on individual profiles in list view to enable saving
		 * and removing profiles via ajax
		 */
		public static function wpp_profile_buttons( $user_id, $profile_page_id, $cv_url = false )
		{
			$data_attr = sprintf(' data-user_id="%s" data-profile_page_id="%s"', $user_id, $profile_page_id);
			$buttons = sprintf('<a class="profile-button ajax-button" data-ajax_action="view" href="#" data-linkurl="%s"%s>View Profile</a>', get_permalink( $profile_page_id ), $data_attr );
			if ( ltp_data::is_saved( $user_id, $profile_page_id ) ) {
				$buttons .= sprintf('<a href="#" id="save_%s" data-ajax_action="remove" class="profile-button ajax-button"%s>Remove</a>', $profile_page_id, $data_attr);
			} else {
				$buttons .= sprintf('<a href="#" id="save_%s" data-ajax_action="save" class="profile-button ajax-button"%s>Save</a>', $profile_page_id, $data_attr);
			}
			if ( $cv_url ) {
				$buttons .= sprintf('<a href="#" id="download_%s" data-ajax_action="cv_download" class="profile-button ajax-button" data-linkurl="%s"%s>CV</a>', $profile_page_id, $cv_url, $data_attr);
			}
			return $buttons;
		}

		/**
		 * returns a sticky toolbar for WPP users which will appear at the top of the profile viewer page
		 * includes filters and links to saved profiles, etc.
		 */
		public static function wpp_toolbar( $user_id, $last_login_date, $profiles_added )
		{
			$toolbar = self::get_status_line( $user_id, $last_login_date, $profiles_added );
			$toolbar .= '<div class="toolbar-buttons">';
			
			// View buttons
			
			// view all profiles button
			$toolbar .= '<a href="#all" id="view-all" class="profile-button">View All Profiles</a>';
			// view saved profiles button
			$toolbar .= '<a href="#saved" id="view-saved" class="profile-button">View Saved Profiles</a>';
			// view filtered profiles button
			$toolbar .= '<a href="#filtered" id="view-filtered" class="profile-button">View Filtered Profiles</a>';
			// view latest profiles button
			$toolbar .= '<a href="#latest" id="view-latest" class="profile-button">View Latest Profiles</a>';
			
			// Filters control buttons
			
			// show filters button
			$toolbar .= sprintf('<a href="#" id="show-filters" class="profile-button">Filter Profiles</a>');
			// edit filters button
			$toolbar .= sprintf('<a href="#" id="edit-filters" class="profile-button">Edit filters</a>');
			// apply filters button
			$toolbar .= sprintf('<a href="#" id="apply-filters" class="profile-button">Apply filters</a>');
			
			$toolbar .= '</div>';
			
			// Filters
			$toolbar .= '<div id="profile-filters">';
			$fields = PeoplePostType::get_profile_fields();
			$filters = array( 
				"expertise" => array(
					"label" => "Students with expertise in:",
					"no-selection" => "Anything",
					"options" => array()
				),
				"experience" => array(
					"label" => "Minimum experience (years):",
					"no-selection" => "No minimum",
					"options" => array()
				), 
				"region" => array(
					"label" => "Students who are currently based in:",
					"no-selection" => "Any region",
					"options" => array()
				),
				"desired_region" => array(
					"label" => "Students who wish to work in:",
					"no-selection" => "Any region",
					"options" => array()
				),
			);
			// get options for each filter
			foreach ( $fields as $field ) {
				if ( in_array( $field["name"], array_keys( $filters ) ) ) {
					$filters[$field["name"]]["options"] = $field["options"];
				}
			}
			$filter_list = '<div id="filter-list"><h3>Filter profiles by:</h3><ul>';
			$filter_controls = '<div id="filter-controls">';
			foreach ( $filters as $filter => $data ) {
				$active = ($filter === "expertise") ? ' active': '';;
				if ( count( $data["options"] ) ) {
					$filter_list .= sprintf('<li><a href="#filters-%s" class="show-filter-controls%s">%s</a><span class="current-filters-list" id="current-filters-%s" data-no-selection="%s"></span></li>', $filter, $active, $data["label"], $filter, esc_attr($data["no-selection"]) );
					$filter_controls .= sprintf('<div class="checkbox-list %s" id="filters-%s"><a class="select-filters-button all" href="#" data-selectid="filters-%s" title="Select all">select all</a><a class="select-filters-button none" href="#" data-selectid="filters-%s" title="Select none">select none</a>', $active, $filter, $filter, $filter);
					//$toolbar .= sprintf('<p class="label">%s</p><div class="checkbox-list" id="filters-%s">', $data["label"], $filter );
					foreach ( $data["options"] as $option ) {
						$option_value = $filter . '-' . preg_replace('/[^a-zA-Z0-9]+/', '', $option);
						//$toolbar .= sprintf('<label for="%s"><input type="checkbox" name="%s" id="%s" value="1"> %s</label>', $option_value, $filter, $option_value, $option );
						$filter_controls .= sprintf('<label for="%s"><input type="checkbox" name="%s" id="%s" value="1" data-filter-label="%s"> %s</label>', $option_value, $filter, $option_value, esc_attr($option), $option );
					}
					$filter_controls .= '</div>';
					//$toolbar .= '</div></div>';
				}
			}
			$filter_list .= '</ul><a href="#" id="delete-filters" class="profile-button">Delete filters</a><a href="#" id="cancel-filters" class="profile-button">Cancel</a></div>';
			$filter_controls .= '</div>';
			$toolbar .= $filter_list . $filter_controls . '</div>';
			return $toolbar;
		}


	}
}