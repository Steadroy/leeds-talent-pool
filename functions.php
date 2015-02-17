<?php
/**
 * Leeds Talent pool functions
 */
function ltp_is_wpp()
{
	if ( is_user_logged_in() ) {	
		$current_user = wp_get_current_user();
		if ( $current_user->has_cap( 'wppuser' ) ) {
			return true;
		}
	}
	return false;
}
function ltp_is_student()
{
	if ( is_user_logged_in() ) {	
		$current_user = wp_get_current_user();
		if ( $current_user->has_cap( 'student' ) ) {
			return true;
		}
	}
	return false;
}
function ltp_is_admin()
{
	if ( is_user_logged_in() ) {	
		$current_user = wp_get_current_user();
		if ( $current_user->has_cap( 'manage_options' ) ) {
			return true;
		}
	}
	return false;
}
function ltp_redirect_to( $pagename )
{
	$options = ltp_options::get_options();
	$page_url = ltp_get_page_url( $pagename );
	if ( $page_url && isset( $options["debug_redirect"] ) && $options["debug_redirect"] ) {
		wp_redirect( $page_url );
	}
}
function ltp_get_page_url( $pagename )
{
	$options = ltp_options::get_options();
	if ( isset( $options[$pagename . "_page_id"] ) ) {
		$page_url = get_permalink( $options[$pagename . "_page_id"] );
		if ( $page_url ) {
			if ( isset( $options["debug_redirect"] ) && $options["debug_redirect"] ) {
				return str_replace(array('connect.leeds.ac.uk', 'http:'), array('pvac-webhost.leeds.ac.uk/connect', 'https:'), $page_url);
			} else {
				return $page_url;
			}
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
			// get the theme options
			$options = ltp_options::get_options();

			// require the class to log user actions
			require_once( dirname(__FILE__) . '/lib/data.php' );

			// require the class to change the login function
			require_once( dirname(__FILE__) . '/lib/login.php' );

			// require the class to provide filters for profile form
			require_once( dirname(__FILE__) . '/lib/filters.php' );

			// require the class to provide template related stuff
			require_once( dirname(__FILE__) . '/lib/template.php' );

			// hide admin bar from front end
			add_filter( 'show_admin_bar', '__return_false');

			// theme installation and updates
			add_action( 'init', array( __CLASS__, 'install' ) );

			// theme uninstallation
			add_action( 'switch_theme', array( __CLASS__, 'uninstall' ) );

			// make dasicons available to theme
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );

			// force ssl for site
			if ( isset( $options["debug_ssl"] ) && $options["debug_ssl"] ) {
				add_action( 'plugins_loaded', array( __CLASS__, 'force_ssl' ) );
			}
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
						ltp_data::create_data_table();

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
		 * enqueue scripts and add dashicons
		 */
		public static function enqueue_scripts()
		{
			// queue dashicons
			wp_enqueue_style( 'dashicons' );

			// queue media
			wp_enqueue_media();

			wp_dequeue_script( 'uol' );

			// register script
			wp_register_script(
				'ltp-script',
				get_stylesheet_directory_uri() . '/scripts.min.js',
				array('jquery'),
				self::$version,
				true
			);
			wp_localize_script(
				'ltp-script',
				'ppt',
				array(
					'ajaxurl' => admin_url('admin-ajax.php'),
					'ajaxnonce' => wp_create_nonce( 'ajax_ppt_fields_action' ),
					'img_empty_single' => __('No image selected', 'ppt'),
					'img_empty_multiple' => __('No images selected', 'ppt'),
					'img_select_single' => __('Select Image', 'ppt'),
					'img_select_multiple' => __('Select Images', 'ppt'),
					'img_remove' => __('remove image', 'ppt'),
					'file_select' => __('Select file', 'ppt'),
					'file_remove' => __('remove file', 'ppt'),
					'file_empty' => __('No files selected', 'ppt')
				)
			);
			wp_enqueue_script( 'ltp-script' );
		}

		/**
		 * forces SSL connection on all pages
		 */
		public static function force_ssl()
		{
			if ( ! isset( $_SERVER["HTTPS"] ) ) {
				$newurl = "https://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
				wp_redirect( $newurl );
				exit();
			}
		}





	}
	leeds_talent_pool::register();
}