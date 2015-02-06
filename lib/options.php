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
			add_action( 'admin_menu', array(__CLASS__, 'add_ltp_theme_options_menu') );

			/* register options with wordpress Settings API */
			add_action( 'admin_init', array(__CLASS__, 'register_ltp_theme_options') );
		}

		/**
		 * add a submenu to the theme admin menu to access the theme settings page
		 */
		public static function add_ltp_theme_options_menu()
		{
			/* Theme Options */
			$options_page = add_theme_page("Connect Options", "Connect Options", "manage_options", "connect_options", array(__CLASS__, "ltp_theme_options_page") );
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
				'ltp_general-options', 
				'Connect theme settings', 
				array(__CLASS__, 'ltp_section_text'), 
				'ltp_options'
			);
			add_settings_field(
				'login_page_id', 
				'Login page', 
				array(__CLASS__, 'ltp_setting_page_select'), 
				'ltp_options', 
				'ltp_general-options',
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
				'ltp_general-options',
				array(
					'fieldname' => 'builder_page_id',
					'desc' => 'Select a page to use as the Profile Builder page (should use the profile builder page template)'
				)
			);
			add_settings_field(
				'wpp_url',
				'WPP Authentication API URL',
				array(__CLASS__, 'ltp_setting_text'),
				'ltp_options',
				'ltp_general-options',
				array(
					'fieldname' => 'wpp_url'
				)
			);
			add_settings_field(
				'wpp_user',
				'WPP Auth user',
				array(__CLASS__, 'ltp_setting_text'),
				'ltp_options',
				'ltp_general-options',
				array(
					'fieldname' => 'wpp_user'
				)
			);
			add_settings_field(
				'wpp_pass',
				'WPP Auth password',
				array(__CLASS__, 'ltp_setting_text'),
				'ltp_options',
				'ltp_general-options',
				array(
					'fieldname' => 'wpp_pass'
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