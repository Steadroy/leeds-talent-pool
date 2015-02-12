<?php
/**
 * login functions for the Leeds Connect website
 * forces login via a custom login page, and adds filters to ensure
 * traffic is redirected to the custom login page on failure.
 */

if ( ! class_exists( 'ltp_login' ) ) {
	class ltp_login
	{
		/**
		 * registers all actions with the wordpress API
		 * installation and uninstallation are delegated to the theme class
		 */
		public static function register()
		{
			// WPP authentication routine
			// add_filter( 'authenticate', array( __CLASS__, 'authenticate_WPP_user' ), 10, 3 );

			// force login
			add_action( 'wp', array( __CLASS__, 'force_members_login_init' ) );

			// intercept failed logins
			add_action( 'wp_login_failed', array( __CLASS__, 'login_failed' ) );

			// intercept blank logins
			add_action( 'authenticate', array( __CLASS__, 'blank_login' ) );

			// redirect after login
			add_filter( 'login_redirect', array( __CLASS__, 'redirect_after_login' ), 10, 3 );

		}

		/**
		 * function which forces all users to log in
		 */
		public static function force_members_login_init() 
		{
			/* If the user is logged in, then abort */
			if ( current_user_can('read') ) return;

			/* get options to determine login page ID */
			$options = ltp_options::get_options();

			/* This is an array of pages that will be EXCLUDED from being blocked */
			$exclusions = array(
				//'wp-login.php',
				'wp-cron.php', // Just incase
				'wp-trackback.php',
				'xmlrpc.php',
				//'login'
			);

			/* If the current script name is in the exclusion list, abort */
			if ( in_array( basename($_SERVER['PHP_SELF']), $exclusions) ) return;

			/* if this is the login page, abort */
			if ( isset( $options["login_page_id"] ) ) {
				
				if ( get_queried_object_id() == $options["login_page_id"] ) {
					return;
				}

				/* Still here? Okay, then redirect to the login form */
				self::redirect();
			} else {
				return;
			}
		}

		/**
		 * when a login fails, redirect to custom login page
		 */
		public static function login_failed( $user )
		{
			// check what page the login attempt is coming from
			if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
  				$referrer = $_SERVER['HTTP_REFERER'];
				// check that we are not on the default login page
				if ( ! empty( $referrer ) && ! strstr( $referrer, 'wp-login' ) && ! strstr( $referrer,'wp-admin' ) && $user != null ) {
					// make sure we don't already have a failed login attempt
					if ( ! strstr( $referrer, '?login=failed' ) ) {
						// Redirect to the login page and append a querystring of login failed
						self::redirect( '?login=failed' );
					} else {
						self::redirect();
					}
				    exit;
				}
			} else {
				self::redirect();
			}
		}

		/**
		 * when a login is blank, redirect to custom login page
		 */
		public static function blank_login( $user )
		{
			// check what page the login attempt is coming from
			if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
				$referrer = $_SERVER['HTTP_REFERER'];

				$error = ( ( isset( $_POST['log']) && $_POST['log'] == '' ) || ( isset( $_POST['pwd'] ) && $_POST['pwd'] == '' ) );

				// check that were not on the default login page
				if ( ! empty( $referrer ) && ! strstr( $referrer, 'wp-login' ) && ! strstr( $referrer, 'wp-admin' ) && $error ) {

					// make sure we don't already have a failed login attempt
					if ( !strstr($referrer, '?login=failed') ) {
						// Redirect to the login page and append a querystring of login failed
						self::redirect( '?login=failed' );
					} else {
						self::redirect();
					}
					exit;
				}
			}
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
			// Get options for WPP authentication
			$options = ltp_options::get_options();
			$authuser = $options["wpp_user"];
			$authpass = $options["wpp_pass"];
			$url = $options["wpp_url"];
			//$url = "https://inside.wpp.com/WebService/Secured/ExternalUserValidation.asmx/ValidateUser";
			if ( ! empty( $authuser ) && ! empty( $authpass ) && ! empty( $url ) ) {
				// validate against WPP user validation service
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

		public static function redirect_after_login( $redirect, $redirect_to, $user)
		{
			$options = ltp_options::get_options();
			// make sure we have a valid user
			if ( $user && is_object( $user ) && is_a( $user, 'WP_User' ) ) {
				if ( $user->has_cap( 'administrator' ) ) {
					return admin_url();
				}
				if ( ! ltp_is_student() && ! ltp_is_wpp() ) {
					return ltp_get_page_url( "invalid_role" );
				} elseif ( ltp_is_student() ) {
					return ltp_get_page_url( "builder" );
				} elseif ( ltp_is_wpp() ) {
					return ltp_get_page_url( "viewer" );
				}
			} else {
				$login_url = self::login_page_url();
				if ( $login_url ) {
					return $login_url;
				}
			}
			return $redirect;
		}

		/**
		 * gets the login page URL
		 */
		public static function login_page_url()
		{
			$options = ltp_options::get_options();
			if ( isset( $options["login_page_id"] ) && ! empty( $options["login_page_id"] ) ) {
				return get_permalink( $options["login_page_id"] );
			} else {
				return false;
			}
		}

		/**
		 * redirects to custom login page
		 */
		public static function redirect( $qs = '' )
		{
			$login_url = self::login_page_url();
			if ( $login_url ) {
				wp_redirect( $login_url . $qs );
			} else {
				auth_redirect();
			}
		}
	}
	ltp_login::register();
}