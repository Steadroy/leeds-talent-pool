<?php
/**
 * data logging class for the Leeds Talent Pool theme
 * This class logs any access to user profile pages by users with the wppuser role
 * and enables users with the wppuser role to save profiles in a basket
 */

if ( ! class_exists( 'ltp_data' ) ) {

	class ltp_data
	{
		/**
		 * registers all actions with the wordpress API
		 * installation and uninstallation are delegated to the theme class
		 */
		public static function register()
		{
			/* ajax handler for updates */
			add_action( 'wp_ajax_ltp_data', array( __CLASS__, 'ajax_actions' ) );

			/* login handler */
			add_action( 'wp_login', array( __CLASS__, 'log_login' ), 10, 2 );
		}

		/**
		 * creates the database table where the analytics data is stored
		 * for profile views, etc
		 */
		public static function create_data_table()
		{
			global $wpdb;
			$data_table_name = self::get_data_tablename();
			if ( $wpdb->get_var("show tables like '$data_table_name'") != $data_table_name) {
				$query = "CREATE TABLE " . $data_table_name . " (
					entry_id int(11) NOT NULL AUTO_INCREMENT,
					user_id int(11) NOT NULL,
					profile_page_id int(11) NOT NULL,
					profile_username VARCHAR(255) NOT NULL DEFAULT '',
					access_time int(11) NOT NULL,
					entry_type VARCHAR(255) NOT NULL DEFAULT '',
					PRIMARY KEY  (entry_id)
				);";
				require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
				dbDelta($query);
			}
		}

		/**
		 * removes the database table
		 */
		public static function drop_data_table()
		{
			global $wpdb;
			$data_table_name = self::get_data_tablename();
			if ( $wpdb->get_var("show tables like '$data_table_name'") == $data_table_name) {
				$query = "DROP TABLE " . $data_table_name . ";";
				require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
				dbDelta($query);
			}
		}

		/**
		 * gets the data table name
		 */
		private static function get_data_tablename()
		{
			global $wpdb;
			return $wpdb->prefix . "ltp_data";
		}

		/**
		 * saves any actions from the UI
		 */
		public static function save_actions()
		{
			if ( isset( $_REQUEST["action"] ) && isset( $_REQUEST["profile_page_id"] ) ) {
				global $current_user;
				$user_id = ( isset( $_REQUEST["user_id"] ) ) ? $_REQUEST["user_id"] : $current_user->ID;
				$profile_page_id = $_REQUEST["profile_page_id"];
				switch ( $_REQUEST["action"] ) {
					case 'cv_download':
						self::log_cv_download( $user_id, $profile_page_id );
						wp_redirect( $_REQUEST["cv_url"] );
						break;
					case 'save':
						self::save_profile( $user_id, $profile_page_id );
						break;
					case 'remove':
						self::remove_profile( $user_id, $profile_page_id );
						break;
				}
			}
		}

		/**
		 * saves actions via AJAX
		 */
		public static function ajax_actions()
		{
    		if ( wp_verify_nonce( $_REQUEST['datanonce'], 'ltp_data_nonce' ) ) {
				global $current_user;
				$ret = array();
				$ret["user_id"] = ( isset( $_REQUEST["user_id"] ) ) ? $_REQUEST["user_id"] : $current_user->ID;
				$ret["profile_page_id"] = isset($_REQUEST["profile_page_id"])? $_REQUEST["profile_page_id"]: 0;
				$ret["ajax_action"] = $_REQUEST["ajax_action"];
				switch ( $ret["ajax_action"] ) {
					case 'save':
						$ret["result"] = self::save_profile( $ret["user_id"], $ret["profile_page_id"] );
						break;
					case 'remove':
						$ret["result"] = self::remove_profile( $ret["user_id"], $ret["profile_page_id"] );
						break;
					case 'history':
						$start = isset( $_REQUEST["start"] )? $_REQUEST["start"]: 0;
						$num = isset( $_REQUEST["num"] )? $_REQUEST["num"]: 20;
						$ret["result"] = self::get_history_html( $ret["user_id"], $start, $num );
						break;
					case 'view':
						$ret["result"] = self::log_view( $ret["user_id"], $ret["profile_page_id"] );
						break;
					case 'cv_download':
						$ret["result"] = self::log_cv_download( $ret["user_id"], $ret["profile_page_id"] );
						break;
				}
				print(json_encode($ret));
				exit();
			}
		}

		/**
		 * inserts an entry into the logger
		 * @var array An array containing the following members:
		 *  - user_id (optional, will default to current user if not supplied)
		 *  - profile_page_id (mandatory)
		 *  - entry_type (optional, will default to 'log')
		 */
		private static function log( $data )
		{
			// check data passed
			if ( ! is_array( $data ) ) {
				return false;
			} else {
				// sanitise user_id
				if ( ! isset( $data["user_id"] ) ) {
					$user_id = get_current_user_id();
					if ( $user_id === 0 ) {
						return false;
					}
				} else {
					$user_id = intVal( $data["user_id"] );
				}

				// sanitise page_id
				if ( ! isset( $data["profile_page_id"] ) ) {
					return false;
				} else {
					$profile_page_id = intVal( $data["profile_page_id"] );
				}

				if ( $profile_page_id ) {
					// derive username from page_id
					$profile_username = get_post_meta( $profile_page_id, 'wp_username', true );
				} else {
					$profile_username = '';
				}
				
				// make sure we have an entry type
				$entry_type = ( ! isset( $data["entry_type"] ) ) ? 'log': trim( $data["entry_type"] );

				// insert a row in the database
				global $wpdb;
				$tablename = self::get_data_tablename();
				$result = $wpdb->insert(
					$tablename,
					array(
						'user_id' => $user_id,
						'profile_page_id' => $profile_page_id,
						'profile_username' => $profile_username,
						'access_time' => time(),
						'entry_type' => $entry_type
					),
					array(
						'%d',
						'%d',
						'%s',
						'%d',
						'%s'
					)
				);
				if ( $result !== false ) {
					return $wpdb->insert_id;
				} else {
					return false;
				}
			}
		}

		/**
		 * gets all log entries for a user
		 * @var integer user id
		 */
		public static function get_user_data( $user_id = false )
		{
			if ( $user_id == false ) {
				return array();
			} else {
				global $wpdb;
				$tablename = self::get_data_tablename();
				$results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `$tablename` WHERE `user_id` = %d;", $user_id ) );
				if ( $results ) {
					return $results;
				} else {
					return array();
				}
			}
		}

		/**
		 * checks to see whether any profiles have been saved by a user
		 */
		public static function has_saved( $user_id )
		{
			global $wpdb;
			$tablename = self::get_data_tablename();
			return $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM $tablename WHERE `user_id` = %d AND `entry_type` = 'saved';", $user_id ) );
		}

		/**
		 * checks to see whether a profile has been saved by a user
		 */
		public static function is_saved( $user_id, $profile_page_id )
		{
			global $wpdb;
			$tablename = self::get_data_tablename();
			return $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM $tablename WHERE `user_id` = %d AND `profile_page_id` = %d AND `entry_type` = 'saved';", $user_id, $profile_page_id ) );
		}

		/**
		 * saves a profile to the users "basket"
		 * @var integer user id
		 * @var integer profile page ID	 
		 */
		private static function save_profile( $user_id, $profile_page_id )
		{
			return self::log( array(
				"user_id" => $user_id,
				"profile_page_id" => $profile_page_id,
				"entry_type" => "saved"
			) );
		}

		/**
		 * removes a profile from the users "basket"
		 * @var integer user id
		 * @var integer profile page ID	 
		 */
		private static function remove_profile( $user_id, $profile_page_id )
		{
			global $wpdb;
			$tablename = self::get_data_tablename();
			$result = $wpdb->update(
				$tablename,
				array( 
					'entry_type' => 'removed'
				),
				array(
					'user_id' => intval( $user_id ),
					'profile_page_id' => intval( $profile_page_id ),
					'entry_type' => 'saved'
				)
			);
			return $result;
		}

		/**
		 * logs a CV download for a single profile
		 * @var integer user id
		 * @var integer profile page ID	 
		 */
		private static function log_cv_download( $user_id, $profile_page_id )
		{
			self::log( array(
				"user_id" => $user_id,
				"profile_page_id" => $profile_page_id,
				"entry_type" => "cv_download"
			) );
		}
		
		/**
		 * logs a view for a single profile
		 * @var integer user id
		 * @var integer profile page ID	 
		 */
		public static function log_view( $user_id, $profile_page_id )
		{
			self::log( array(
				"user_id" => $user_id,
				"profile_page_id" => $profile_page_id,
				"entry_type" => "view"
			) );
		}

		/**
		 * logs a user login
		 * @var string user_login
		 * @var object WP_User object
		 */
		public static function log_login( $username, $user )
		{
			self::log( array(
				"user_id" => $user->ID,
				"profile_page_id" => 0,
				"entry_type" => "login"
			) );
		}

		/********************************************************
		 * Methods to get data for student profile pages        *
		 ********************************************************/

		/**
		 * gets the number of views of a profile
		 * @var integer profile page ID
		 */
		public static function get_views( $profile_page_id )
		{
			global $wpdb;
			$tablename = self::get_data_tablename();
			$row = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) as views FROM $tablename WHERE `profile_page_id` = %d AND `entry_type` = 'view'", $profile_page_id ) );
			if ( $row ) {
				return $row;
			}
			return false;
		}

		/**
		 * gets the number of views of a profile by username
		 * @var integer profile page ID
		 */
		public static function get_views_by_username( $profile_username )
		{
			global $wpdb;
			$tablename = self::get_data_tablename();
			$row = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) as views FROM $tablename WHERE `profile_username` = %s AND `entry_type` = 'view'", $profile_username ) );
			if ( $row ) {
				return $row;
			}
			return false;
		}

		/**
		 * gets the number of saves of a profile
		 * @var integer profile page ID
		 */
		public static function get_saves( $profile_page_id )
		{
			global $wpdb;
			$tablename = self::get_data_tablename();
			$row =  $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) as saves FROM $tablename WHERE `profile_page_id` = %d AND `entry_type` = 'saved'", $profile_page_id ) );
			if ( $row ) {
				return $row;
			}
			return false;
		}

		/**
		 * gets the number of saves of a profile by username
		 * @var integer profile page ID
		 */
		public static function get_saves_by_username( $profile_username )
		{
			global $wpdb;
			$tablename = self::get_data_tablename();
			$row = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) as saves FROM $tablename WHERE `profile_username` = %s AND `entry_type` = 'saved'", $profile_username ) );
			if ( $row ) {
				return $row;
			}
			return false;
		}

		/**
		 * gets the number of downloads of a cv
		 * @var integer profile page ID
		 */
		public static function get_downloads( $profile_page_id )
		{
			global $wpdb;
			$tablename = self::get_data_tablename();
			$row = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) as downloads FROM $tablename WHERE `profile_page_id` = %d AND `entry_type` = 'cv_download'", $profile_page_id ) );
			if ( $row ) {
				return $row;
			}
			return false;
		}

		/**
		 * gets the number of downloads of a cv by username
		 * @var integer profile page ID
		 */
		public static function get_downloads_by_username( $profile_username )
		{
			global $wpdb;
			$tablename = self::get_data_tablename();
			$row = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) as downloads FROM $tablename WHERE `profile_username` = %s AND `entry_type` = 'cv_download'", $profile_username ) );
			if ( $row ) {
				return $row;
			}
			return false;
		}

		/********************************************************
		 * Methods to get data for WPP page footer              *
		 ********************************************************/

		/**
		 * gets the date of the previous login (or date of last login if only one)
		 * @var integer ID of WPP user
		 */
		public static function get_previous_login( $user_id = false )
		{
			if ( $user_id ) {
				global $wpdb;
				$tablename = self::get_data_tablename();
				$logins = $wpdb->get_results( $wpdb->prepare("SELECT `access_time` FROM $tablename WHERE `entry_type` = 'login' AND `user_id` = %d ORDER BY `access_time` DESC LIMIT 2;", $user_id ) );
				if ( count( $logins ) === 2 ) {
					return $logins[1]->access_time;
				} elseif ( count( $logins ) === 1 ) {
					return $logins[0]->access_time;
				} 
			}
			return false;
		}

		/**
		 * gets the date of the last recorded login
		 * @var integer ID of WPP user
		 */
		public static function get_last_login( $user_id = false )
		{
			if ( $user_id ) {
				global $wpdb;
				$tablename = self::get_data_tablename();
				$login = $wpdb->get_row( $wpdb->prepare("SELECT `access_time` FROM $tablename WHERE `entry_type` = 'login' AND `user_id` = %d ORDER BY `access_time` DESC LIMIT 1;", $user_id ) );
				if ( $login ) {
					return $login->access_time;
				} else {
					return false;
				}
			}
			return false;
		}


		/**
		 * gets the profiles added since a given date
		 */
		public static function get_profiles_modified_since( $timestamp = false )
		{
			if ( ! $timestamp ) {
				$timestamp = time();
			}
			$year = date('Y', $timestamp);
			$month = date('n', $timestamp);
			$day = date('j', $timestamp);
			$people_pages = get_posts(array(
				'post_type' => 'people',
				'numberposts' => -1,
				'nopaging' => true,
				'status' => 'publish',
				'date_query' => array(
					array(
						'column' => 'post_date',
						'after' => array(
							'year' => $year,
							'month' => $month,
							'day' => $day
						),
						'inclusive' => true
					)
				)
			));
			return $people_pages;
		}

		/**
		 * checks whether a WPP user has any history
		 */
		public static function user_has_history( $user_id = false )
		{
			if ( $user_id ) {
				global $wpdb;
				$tablename = self::get_data_tablename();
				return $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM $tablename WHERE `user_id` = %d and `entry_type` IN('view','saved','removed','cv-download');", $user_id ) );
			}
		}


		/**
		 * gets the view history of a WPP user
		 */
		private static function get_user_history( $user_id = false, $start = 0, $num = 20 )
		{
			if ( $user_id ) {
				global $wpdb;
				$tablename = self::get_data_tablename();
				$start = abs(intval($start));
				$num = intval($num) === 0? 20: abs(intval($num));
				$history = $wpdb->get_results( $wpdb->prepare("SELECT * FROM $tablename WHERE `user_id` = %d and `entry_type` IN('view','saved','removed','cv-download') ORDER BY `access_time` DESC LIMIT $start, $num;", $user_id ) );
				return $history;
			}
			return array();
		}

		/**
		 * processes history entries and returns HTML for ajax requests
		 */
		private static function get_history_html( $user_id = false, $start = 0, $num = 20 )
		{
			$total = self::user_has_history($user_id);
			$out = '';
			if ( $total ) {
				$history = self::get_user_history( $user_id, $start, $num );
				$people_pages = self::get_people_pages_for_results( $history );
				if ( count( $history ) ) {
					$nav = '';
					//$out .= "<p>Start: " . $start . "<br>Num: " . $num . "</p><pre>" . print_r($history, true) . '</pre>';
					//$out .= '<pre>' . print_r($people_pages, true) . '</pre>';
					if ( $start > 0 ) {
						$nav .= sprintf( '<a href="#" class="history previous" data-start="%s" data-num="%s" data-user_id="%s">&laquo; previous</a>', ( $start - $num ), $num, $user_id );
					}
					if ( ($start + $num) < $total ) {
						$nav .= sprintf( '<a href="#" class="history next" data-start="%s" data-num="%s" data-user_id="%s">next &raquo;</a>', ( $start + $num ), $num, $user_id );
					}
					$actions = array();
					foreach( $history as $action ) {
						foreach ( $people_pages as $page ) {
							if ( $page->ID == $action->profile_page_id ) {
								$action->profile_url = get_permalink( $page->ID );
								$action->profile_title = $page->post_title;
								break;
							}
						}
						$datestr = date('Ymd', $action->access_time);
						if ( !isset( $actions[$datestr] ) ) {
							$actions[$datestr] = array();
						}
						$actions[$datestr][] = $action;
					}
					$out .= $nav;
					foreach ( $actions as $datestr => $entries ) {
						$out .= sprintf('<h3>%s</h3><table class="historytable"><thead><tr><th>Profile</th><th>Action</th><th>Time</th></tr></thead><tbody>', date( 'l jS F, Y', $entries[0]->access_time ) );
						foreach ( $entries as $entry ) {
							$out .= sprintf('<tr><td><a href="%s">%s</a></td><td>%s</td><td>%s</td></tr>', $entry->profile_url, $entry->profile_title, $entry->entry_type, date( 'g:i:sa', $entry->access_time ) );
						}
						$out .= '</tbody></table>';
					}
					$out .= $nav;
				}
			}
			return $out;
		}

		/**
		 * gets a summary of data saved for all WPP users
		 */
		public static function get_summary_data()
		{
			global $wpdb;
			$tablename = self::get_data_tablename();
			$results = $wpdb->get_results( "SELECT COUNT(*) as `count`, `entry_type`, `user_id` FROM $tablename GROUP BY `entry_type`, `user_id`;" );
			$users = array();
			if ( count( $results ) ) {
				foreach ( $results as $row ) {
					if ( ! isset( $users[$row->user_id] ) ) {
						$users[$row->user_id] = array(
							"login" => 0,
							"view" => 0,
							"saved" => 0
						);
					}
					$users[$row->user_id][$row->entry_type] = $row->count;
				}
			}
			return $users;
		}

		/**
		 * gets saved profiles for a WPP user
		 * @param integer user ID of WPP user
		 */
		public static function get_saved_profiles( $user_id = false )
		{
			if ( $user_id !== false ) {
				global $wpdb;
				$tablename = self::get_data_tablename();
				$saved = $wpdb->get_results( $wpdb->prepare("SELECT * FROM $tablename WHERE `entry_type` = 'saved'  AND `user_id` = %d;", $user_id ) );
				return self::get_people_pages_for_results( $saved );
			}
		}

		/**
		 * returns pages from an array of post IDs (shortcut)
		 */
		private static function get_people_pages_for_results( $results = array() )
		{
			$people_pages = array();
			if ( $results && count( $results ) ) {
				$posts_in = array();
				foreach ( $results as $row ) {
					if ( $row->profile_page_id > 0 ) {
						array_push( $posts_in, $row->profile_page_id );
					}
				}
				if ( count( $posts_in ) ) {
					$people_pages = get_posts(array(
						'post_type' => 'people',
						'numberposts' => -1,
						'nopaging' => true,
						'post_status' => 'any',
						'post__in' => $posts_in
					));
					/* now merge back the info from the original query */
					for ( $i = 0; $i < count($people_pages); $i++ ) {
						foreach ( $results as $row ) {
							if ( $people_pages[$i]->ID == $row->profile_page_id ) {
								$people_pages[$i]->access_time = $row->access_time;
								$people_pages[$i]->entry_type = $row->entry_type;
								$people_pages[$i]->profile_username = $row->profile_username;
								break;
							}
						}
					}
				}
			}
			return $people_pages;
		}
	}
	ltp_data::register();
}