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
			// hide admin bar from front end
			add_filter('show_admin_bar', '__return_false');

			// WPP authentication routine
			// add_filter( 'authenticate', array( __CLASS__, 'authenticate_WPP_user' ), 10, 3 );

			// force login
			add_action( 'wp', array(__CLASS__, 'force_members_login_init') );

			// installation of tracking table and any updates
			add_action( 'init', array( __CLASS__, 'install' ) );

			// deinstallation of the theme
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


		/**
		 * function which forces all users to log in
		 */
		public static function force_members_login_init() 
		{
			/* If the user is logged in, then abort */
			if ( current_user_can('read') ) return;

			/* This is an array of pages that will be EXCLUDED from being blocked */
			$exclusions = array(
				'wp-login.php',
				'wp-cron.php', // Just incase
				'wp-trackback.php',
				'xmlrpc.php'
			);

			/* If the current script name is in the exclusion list, abort */
			if ( in_array( basename($_SERVER['PHP_SELF']), $exclusions) ) return;

			/* Still here? Okay, then redirect to the login form */
			auth_redirect();
		}


		/**
		 * authentication of WPP users via the WPP external user validation web service
		 */
		public static function authenticate_WPP_user( $user, $username, $password )
		{
			// Make sure a username and password are present for us to work with
			if ($username == '' || $password == '') {
				return;
			}
			$authuser = 'authuser';
			$authpass = 'authpass';
			// validate against WPP user validation service
			$url = "https://inside.wpp.com/WebService/Secured/ExternalUserValidation.asmx/ValidateUser";
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_FAILONERROR, 1);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_TIMEOUT, 3);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, "username=$username&password=$password");
			curl_setopt($ch, CURLOPT_USERPWD, "$authuser:$authpass");
			$resultxml = curl_exec($ch);
			if ( $resultxml !== false ) {
				$result = simplexml_load_string($resultxml);
				if ( $result !== false ) {
					// valid result - check status code
					if ( $result->Status->Code == 0 ) {
						// successful login
						$email = $result->EmailAddress;
						$first_name = $result->FirstName;
						$last_name = $result->LastName;
						$display_name = $result->FirstName . ' ' . $result->LastName;
						$company = $result->Company;
						// try to load Wordpress user
						$userobj = new WP_User();
						$user = $userobj->get_data_by( 'email', $email );
						// if user doesn't exist, create one
						if ( $user->ID == 0 ) {
							// set up user data
							$userdata = array(
								'user_login'    => $username,
								'user_pass'     => $password,
								'user_email'    => $email,
								'first_name'    => $first_name,
								'last_name'     => $last_name,
								'display_name'  => $display_name,
								'role'          => 'wppuser',
								'description'   => $company
							);
							$new_user_id = wp_insert_user( $userdata ); 
							$user = new WP_User($user->ID); // Attempt to load up the user with that ID
							return $user;
						}
					}
				}
			}
		}
	}
	leeds_talent_pool::register();
}