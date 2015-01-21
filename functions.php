<?php
/**
 * Leeds Talent pool functions
 */
function is_wpp()
{
	if ( is_user_logged_in() ) {	
		$current_user = wp_get_current_user();
		$roles = $current_user->roles;
		$role = array_shift($roles);
		if ( $role === 'wppuser' ) {
			return true;
		}
	}
	return false;
}
function is_student()
{
	if ( is_user_logged_in() ) {	
		$current_user = wp_get_current_user();
		$roles = $current_user->roles;
		$role = array_shift($roles);
		if ( $role === 'student' ) {
			return true;
		}
	}
	return false;
}

if ( ! class_exists( 'leeds_talent_pool' ) ) {
	class leeds_talent_pool
	{
		/* theme version */
		static $version = '0.0.1';

		/* registers with wordpress API */
		public static function register()
		{
			// require the class for the theme options
			require_once( dirname(__FILE__) . '/lib/options.php' );

			// require the class to log user actions
			require_once( dirname(__FILE__) . '/lib/actions.php' );

			// require the class change the login function
			require_once( dirname(__FILE__) . '/lib/login.php' );

			// hide admin bar from front end
			add_filter('show_admin_bar', '__return_false');

			// theme installation and updates
			add_action( 'init', array( __CLASS__, 'install' ) );

			// theme uninstallation
			add_action( 'switch_theme', array( __CLASS__, 'uninstall' ) );
		}

		/**
		 * installation routine
		 */
		public static function install()
		{
			$current_version = get_option('ltp_theme_version');
			if ($current_version != self::$version) {
				switch ($current_version) {
					case false:
						/* first installation */
						ltp_actions::create_data_table();

					case '0.0.1':
						/* upgrade from 0.0.1 */
				}
				/* update the version option */
				//update_option('ltp_theme_version', self::$version);
			}
		}

		/**
		 * uninstallation routine
		 */
		public static function uninstall()
		{
			delete_option('ltp_theme_version');
		}






	}
	leeds_talent_pool::register();
}