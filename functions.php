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

			// require the class to change the login function
			require_once( dirname(__FILE__) . '/lib/login.php' );

			// require the class to provide filters for profile form
			require_once( dirname(__FILE__) . '/lib/filters.php' );

			// require the class to provide sidebars
			require_once( dirname(__FILE__) . '/lib/sidebars.php' );

			// hide admin bar from front end
			add_filter('show_admin_bar', '__return_false');

			// theme installation and updates
			add_action( 'init', array( __CLASS__, 'install' ) );

			// theme uninstallation
			add_action( 'switch_theme', array( __CLASS__, 'uninstall' ) );

			// make dasicons available to theme
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
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
		 * returns the sticky toolbar for student users
		 */
		public static function profile_toolbar( $has_page, $is_published )
		{
			$toolbar = '<div class="section sticky"><h3>Profile Completion</h3><div class="completion-meter"><span></span></div>';
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
			$toolbar .= '</div>';
			return $toolbar;
		}


	}
	leeds_talent_pool::register();
}