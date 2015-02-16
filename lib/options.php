<?php
/**
 * action logging class for the Leeds Talent Pool theme
 * This class logs any access to user profile pages by users with the wppusers role
 * and enables users with the wppusers role to save profiles in a basket
 */

if ( ! class_exists( 'ltp_options' ) ) {

	class ltp_options
	{
		/**
		 * registers all actions with the wordpress API
		 * installation and uninstallation are delegated to the theme class
		 */
		public static function register()
		{
			/* add options to Appearance menu */
			add_action( 'admin_menu', array(__CLASS__, 'add_ltp_theme_admin_menu') );

			/* register options with wordpress Settings API */
			add_action( 'admin_init', array(__CLASS__, 'register_ltp_theme_options') );
		}

		/**
		 * add a submenu to the theme admin menu to access the theme settings page
		 */
		public static function add_ltp_theme_admin_menu()
		{
			/* admin */
			add_menu_page("Connect", "Connect", "manage_options", "connect_admin", array(__CLASS__, "ltp_theme_admin_page"), 'dashicons-migrate', 61 );
			/* Options */
			add_submenu_page( "connect_admin", "Connect Options", "Options", "manage_options", "connect_options", array(__CLASS__, "ltp_theme_options_page") );
		}

		/**
		 * Creates the admin page
		 */
		public static function ltp_theme_admin_page()
		{
			print('<div class="wrap"><h2>Connect Admin</h2>');
			if ( ! current_user_can( 'list_users' ) ) {
				wp_die('You do not have sufficient permissions to access this page.');
			}
		
			/* get users */
   			$users = get_users('orderby=nicename');

   			/* get people pages */
			$people_pages = get_posts(array(
				'post_type' => 'people',
				'numberposts' => -1,
				'nopaging' => true
			));

			/* map people page IDs to usernames */
			$pages_map = array();
			foreach ($people_pages as $pp) {
				$wp_username = get_post_meta($pp->ID, 'wp_username', true);
				if ($wp_username) {
					$pages_map[$wp_username] = $pp->ID;
				}
			}

			$wpp_users = array();
			$student_users = array();

			foreach ($users as $user) {
				if ( $user->has_cap( 'wppuser' ) ) {
					$wpp_users[] = $user;
				}
				if ( $user->has_cap( 'student' ) ) {
					$student_users[] = $user;
				}
			}

			if ( count( $wpp_users ) ) {
				print('<h2>WPP Users</h2><table class="widefat">');
				print('<thead><tr><th>Name</th><th>Views</th><th>Saved</th></tr></thead>');
				foreach ($wpp_users as $user) {
					print('<tr class="person-row">');
					printf('<td>%s</td>', $user->data->display_name);

					print('</tr>');
				}
				print('</tbody></table>');
			}

			if ( count( $student_users ) ) {
				print('<h2>Students</h2><table class="widefat">');
				print('<thead><tr><th>Name</th><th>Views</th><th>Saved</th></tr></thead><tbody>');
				foreach ($student_users as $user) {
					print('<tr class="person-row">');
					// link name to page if available
					$userID = $user->data->ID;
					if ( ! isset($pages_map[$user->data->user_login])) {
						printf('<td>%s</td>', $user->data->display_name);
					} else {
						printf('<td><a href="%s">%s</a></td>', get_permalink($pages_map[$user->data->user_login]), $user->data->display_name);
					}
					print('</tr>');
				}
				print('</tbody></table>');
			}
			print('</div>');
		}

		/**
		 * registers settings and sections
		 */
		public static function register_ltp_theme_options()
		{
			register_setting(
				'ltp_theme_options', 
				'ltp_theme_options', 
				array(__CLASS__, 'validate_ltp_theme_options')
			);
			add_settings_section(
				'ltp_page-options', 
				'Key pages', 
				array(__CLASS__, 'ltp_section_text'), 
				'ltp_options'
			);
			add_settings_field(
				'login_page_id', 
				'Login page', 
				array(__CLASS__, 'ltp_setting_page_select'), 
				'ltp_options', 
				'ltp_page-options',
				array(
					'fieldname' => 'login_page_id',
					'desc' => 'Select a page to use as the login page (should use the login page template)'
				)
			);
			add_settings_field(
				'builder_page_id', 
				'Profile builder page', 
				array(__CLASS__, 'ltp_setting_page_select'), 
				'ltp_options', 
				'ltp_page-options',
				array(
					'fieldname' => 'builder_page_id',
					'desc' => 'Select a page to use as the Profile Builder page (should use the profile builder page template)'
				)
			);
			add_settings_field(
				'viewer_page_id', 
				'Profile viewer page', 
				array(__CLASS__, 'ltp_setting_page_select'), 
				'ltp_options', 
				'ltp_page-options',
				array(
					'fieldname' => 'viewer_page_id',
					'desc' => 'Select a page to use as the Profile Viewer page (should use the profile viewer page template)'
				)
			);
			add_settings_field(
				'invalid_role_page_id', 
				'Invalid Role page', 
				array(__CLASS__, 'ltp_setting_page_select'), 
				'ltp_options', 
				'ltp_page-options',
				array(
					'fieldname' => 'invalid_role_page_id',
					'desc' => 'Select a page to redirect users to if they do not have a valid role on the site'
				)
			);

			add_settings_section(
				'ltp_wpp-options', 
				'WPP Authentication', 
				array(__CLASS__, 'ltp_section_text'), 
				'ltp_options'
			);
			add_settings_field(
				'wpp_url',
				'WPP Authentication API URL',
				array(__CLASS__, 'ltp_setting_text'),
				'ltp_options',
				'ltp_wpp-options',
				array(
					'fieldname' => 'wpp_url'
				)
			);
			add_settings_field(
				'wpp_user',
				'WPP Auth user',
				array(__CLASS__, 'ltp_setting_text'),
				'ltp_options',
				'ltp_wpp-options',
				array(
					'fieldname' => 'wpp_user'
				)
			);
			add_settings_field(
				'wpp_pass',
				'WPP Auth password',
				array(__CLASS__, 'ltp_setting_text'),
				'ltp_options',
				'ltp_wpp-options',
				array(
					'fieldname' => 'wpp_pass'
				)
			);
			add_settings_section(
				'ltp_debug-options', 
				'Debugging', 
				array(__CLASS__, 'ltp_section_text'), 
				'ltp_options'
			);
			add_settings_field(
				'debug_redirect',
				'Enforce redirection',
				array(__CLASS__, 'ltp_setting_checkbox'),
				'ltp_options',
				'ltp_debug-options',
				array(
					'fieldname' => 'debug_redirect',
					'desc' => 'Redirects WPP and Student Users so they can only access specific areas of the site.'
				)
			);
			add_settings_field(
				'debug_ssl',
				'Enforce SSL connection',
				array(__CLASS__, 'ltp_setting_checkbox'),
				'ltp_options',
				'ltp_debug-options',
				array(
					'fieldname' => 'debug_ssl',
					'desc' => 'Enforces an SSL connection for all requests'
				)
			);
		}

		/**
		 * creates the options page
		 */
		public static function ltp_theme_options_page()
		{
			print('<div class="wrap"><h2>Connect Theme Options</h2>');
			settings_errors('ltp_theme_options');
			if (isset($_REQUEST['settings-updated']) && $_REQUEST['settings-updated'] == "true")
			{
				print('<div id="message" class="updated"><p><strong>Settings saved.</strong></p></div>');
			}
			/* output the options page */
			print('<form method="post" action="options.php">');
			settings_fields('ltp_theme_options');
			do_settings_sections('ltp_options');
			print('<p class="submit"><input type="submit" class="button-primary" name="Submit" value="Save Changes" /></p>');
			print("</form></div>");
		}

		/**
		 * settings section text
		 */
		public static function ltp_section_text()
			{ echo ""; }

		/**
		 * text input
		 */
		public static function ltp_setting_text( $args )
		{
			$options = self::get_options();
			if (isset($args["fieldname"])) {
				$option_value = isset($options[$args["fieldname"]])? $options[$args["fieldname"]]: "";
				$length = isset($args["length"])? $args["length"]: "40";
				printf('<p><input id="%1$s" name="ltp_theme_options[%1$s]" size="%2$s" type="text" value="%3$s" />', $args["fieldname"], $length, $option_value);
				if ( isset( $args["desc"] ) && $args["desc"] != "" ) {
					printf( '<br />%s', $args["desc"] );
				}
				print( '</p>');
			}
		}

		/**
		 * checkbox input
		 */
		public static function ltp_setting_checkbox( $args )
		{
			$options = self::get_options();
			if (isset($args["fieldname"])) {
				$chckd = (isset( $options[$args["fieldname"]] ) && intval( $options[$args["fieldname"]] ) > 0 ) ? ' checked': '';
				printf('<p><input id="%1$s" name="ltp_theme_options[%1$s]" type="checkbox" value="1"%2$s />', $args["fieldname"], $chckd);
				if ( isset( $args["desc"] ) && $args["desc"] != "" ) {
					printf( '<br />%s', $args["desc"] );
				}
				print( '</p>');
			}
		}

		/**
		 * page selection
		 */
		public static function ltp_setting_page_select( $args )
		{
			$options = self::get_options();
			if ( isset( $args["fieldname"] ) ) {
				$pages = get_pages();
				if ( count( $pages ) ) {
					printf( '<p><select name="ltp_theme_options[%s]">', $args["fieldname"] );
					foreach ($pages as $page) {
						$sel = ( isset( $options[$args["fieldname"]] ) && $options[$args["fieldname"]] == $page->ID )? ' selected="selected"': '';
						printf( '<option value="%d"%s>%s</option>', $page->ID, $sel, $page->post_title);
					}
					print( '</select>' );
					if ( isset( $args["desc"] ) && $args["desc"] != "" ) {
						printf( '<br />%s', $args["desc"] );
					}
					print( '</p>' );
				}
			}
		}

		/**
		 * input validation callback
		 */
		public static function validate_ltp_theme_options( $theme_options )
		{
			$theme_options["debug_redirect"] = ( isset( $theme_options["debug_redirect"] ) ) ? 1: 0;
			$theme_options["debug_ssl"] = ( isset( $theme_options["debug_ssl"] ) ) ? 1: 0;
			return $theme_options;
		}

		/**
		 * get all options
		 */
		public static function get_options()
		{
			/* get options from wp options */
			return get_option('ltp_theme_options');
		}
	}
	ltp_options::register();
}