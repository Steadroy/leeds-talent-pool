<?php
/**
 * action logging class for the Leeds Talent Pool theme
 * This class logs any access to user profile pages by users with the wppusers role
 * and enables users with the wppusers role to save profiles in a basket
 */

if ( ! class_exists( 'ltp_actions' ) ) {

	class ltp_actions
	{
		/**
		 * registers all actions with the wordpress API
		 * installation and uninstallation are delegated to the theme class
		 */
		public static function register()
		{
			
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
		private static function drop_data_table()
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
		 * inserts an entry into the logger
		 * @var array An array containing the following members:
		 *  - user_id (optional, will defaulkt to current user if not supplied)
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

				// derive username from page_id
				$profile_username = get_post_meta( $db_data["profile_page_id"], 'wp_username', true );
				
				// make sure we have an entry type
				$entry_type = ( ! isset( $data["entry_type"] ) ) ? 'log': trim( $data["entry_type"] );

				// insert a row in the database
				global $wpdb;
				$tablename = self::get_data_tablename();
				$wpdb->insert(
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
				return $wpdb->insert_id;
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
		 * saves a profile to the users "basket"
		 * @var integer user id
		 * @var integer profile page ID	 
		 */
		public static function save_profile( $user_id, $profile_page_id )
		{
			self::log( array(
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
		public static function remove_profile( $user_id, $profile_page_id )
		{
			global $wpdb;
			$tablename = self::get_data_tablename();
			$wpdb->update(
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
		 * logs a CV download for a single profile
		 * @var integer user id
		 * @var integer profile page ID	 
		 */
		public static function log_cv_download( $user_id, $profile_page_id )
		{
			self::log( array(
				"user_id" => $user_id,
				"profile_page_id" => $profile_page_id,
				"entry_type" => "cv_download"
			) );
		}
		

		/**
		 * gets the number of views of a profile
		 * @var integer profile page ID
		 */
		public static function get_views( $profile_page_id )
		{
			global $wpdb;
			$tablename = self::get_data_tablename();
			return $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM $tablename WHERE `profile_page_id` = %d AND `entry_type` = 'view'", $profile_page_id ) );
		}

		/**
		 * gets the number of views of a profile by username
		 * @var integer profile page ID
		 */
		public static function get_views_by_username( $profile_username )
		{
			global $wpdb;
			$tablename = self::get_data_tablename();
			return $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM $tablename WHERE `profile_username` = %s AND `entry_type` = 'view'", $profile_username ) );
		}

		/**
		 * gets the number of times a profile has been saved
		 */

	}
	ltp_actions::register();
}