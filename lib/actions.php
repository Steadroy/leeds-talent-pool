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
					page_id int(11) NOT NULL,
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
		 */
		private static function log( $data )
		{
			// check data passed
			if ( ! is_array( $data ) ) {
				return false;
			} else {
				$db_data = array();
				// sanitise user_id
				if ( ! isset( $data["user_id"] ) ) {
					$db_data["user_id"] = get_current_user_id();
					if ( $db_data["user_id"] === 0 ) {
						return false;
					}
				} else {
					$db_data["user_id"] = intVal( $data["user_id"] );
				}

				// sanitise page_id
				if ( ! isset( $data["page_id"] ) ) {
					$db_data["page_id"] = get_queried_object_id();
					if ( $data["page_id"] === 0 ) {
						return false;
					}
				} else {
					$db_data["page_id"] = intVal( $data["page_id"] );
				}

				// derive username from page_id
				$db_data["profile_username"] = get_post_meta( $db_data["page_id"], 'wp_username', true );
				
				// set timestamp
				$db_data["access_time"] = time();

				// make sure we have an entry type
				if ( ! isset( $data["entry_type"] ) ) {
					$db_data["entry_type"] = 'log';
				} else {
					$db_data["entry_type"] = trim( $data["entry_type"] );
				}

				// insert a row in the database
				global $wpdb;
				$tablename = self::get_data_tablename();
				$wpdb->insert(
					$tablename,
					$db_data,
					array( '%d', '%d', '%s', '%d', '%s')
				);
				return $wpdb->insert_id;
			}
		}

		/**
		 * updates the entry type of an existing log entry
		 */
		private static function update_entry_type( $id, $type )
		{
			global $wpdb;
			$tablename = self::get_data_tablename();
			$wpdb->update(
				$tablename,
				array( 
					'entry_type' => trim( stripslashes( $type ) )
				),
				array(
					'entry_id' => intval( $id )
				)
			);
		}
	}
	ltp_actions::register();
}