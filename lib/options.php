<?php
/**
 * action logging class for the Leeds Talent Pool theme
 * This class logs any access to user profile pages by users with the wppusers role
 * and enables users with the wppusers role to save profiles in a basket
 */

if ( ! class_exists( 'ltp_options' ) ) {

	class ltp_options
	{
		private static $wpp_page;
		private static $student_page;
		/**
		 * registers all actions with the wordpress API
		 * installation and uninstallation are delegated to the theme class
		 */
		public static function register()
		{
			/* add options to Appearance menu */
			add_action( 'admin_menu', array( __CLASS__, 'add_ltp_theme_admin_menu' ) );

			/* register options with wordpress Settings API */
			add_action( 'admin_init', array( __CLASS__, 'register_ltp_theme_options' ) );

			/* make sure screen options are saved */
			add_filter( 'set-screen-option', array( __CLASS__, 'set_screen_options' ), 10, 3 );

			add_filter( 'user_search_columns', array( __CLASS__, 'add_columns_to_user_search' ), 10, 3 );

		}

		/**
		 * add a submenu to the theme admin menu to access the theme settings page
		 */
		public static function add_ltp_theme_admin_menu()
		{
			/* main item */
			add_menu_page("Connect", "Connect", "manage_options", "connect_wpp", array(__CLASS__, "ltp_wpp_admin_page"), 'dashicons-migrate', 61 );
			/* WPP users page */
			$wpp_page = add_submenu_page("connect_wpp", "WPP users", "WPP users", "manage_options", "connect_wpp", array(__CLASS__, "ltp_wpp_admin_page") );
			/* Student Users page */
			$student_page = add_submenu_page("connect_wpp", "Students", "Students", "manage_options", "connect_students", array(__CLASS__, "ltp_students_admin_page") );
			/* Options */
			add_submenu_page( "connect_wpp", "Connect Options", "Connect Options", "manage_options", "connect_options", array(__CLASS__, "ltp_theme_options_page") );

			add_action( 'load-' . $wpp_page, array( __CLASS__, 'wpp_admin_screen_options' ) );
			add_action( 'load-' . $student_page, array( __CLASS__, 'student_admin_screen_options' ) );
		}

		/**
		 * adds screen option for number of users to display in table
		 */
		public static function wpp_admin_screen_options()
		{
			add_screen_option( 'per_page', array(
				'label' => __('Users', 'healthylunch'),
				'default' => 30,
				'option' => 'ltp_wpp_users_pp'
			) );
		}

		/**
		 * adds screen option for number of users to display in table
		 */
		public static function student_admin_screen_options()
		{
			add_screen_option( 'per_page', array(
				'label' => __('Users', 'healthylunch'),
				'default' => 30,
				'option' => 'ltp_student_users_pp'
			) );
		}

		/**
		 * sets the option when the Apply buton is clicked 
		 */
		public static function set_screen_options( $status , $option, $value) 
		{
			if ( 'ltp_wpp_users_pp' == $option || 'ltp_student_users_pp' == $option ) {
				return $value;
			}
			return $status;
		}

		/**
		 * gets screen option for page
		 */
		public static function get_admin_per_page( $option )
		{
			// get the current user ID
			$user = get_current_user_id();
			// get the current admin screen
			$screen = get_current_screen();
			// retrieve the "per_page" option
			$screen_option = $screen->get_option('per_page', 'option');
			// retrieve the value of the option stored for the current user
			$per_page = get_user_meta($user, $screen_option, true);
			if ( empty ( $per_page) || $per_page < 1 ) {
				// get the default value if none is set
				$per_page = $screen->get_option('per_page', 'default' );
			}
			return $per_page;
		}

		public static function add_columns_to_user_search( $search_columns, $search, $this )
		{
			return array('first_name', 'last_name', 'user_email', 'user_login');
		}
		/**
		 * Creates the WPP user admin page
		 */
		public static function ltp_wpp_admin_page()
		{
			print('<div class="wrap"><h2>WPP users</h2>');
			if ( ! current_user_can( 'list_users' ) ) {
				wp_die('You do not have sufficient permissions to access this page.');
			}
			
			print('<form method="post">');
			wp_nonce_field('disable_form', 'disable_form_nonce', true, true);
			$table = new ltp_WPP_List_Table();
			$table->prepare_items(); 
			$table->search_box( 'search', 'search_wpp' );
			$table->display(); 
			print('</form></div>');
		}

		public static function ltp_students_admin_page()
		{

   			/* get people pages */
			$people_pages = get_posts(array(
				'post_type' => 'people',
				'numberposts' => -1,
				'nopaging' => true,
				'status' => 'any'
			));

			/* map people page IDs to usernames */
			$pages_map = array();
			foreach ($people_pages as $pp) {
				$wp_username = get_post_meta($pp->ID, 'wp_username', true);
				if ($wp_username) {
					$pages_map[$wp_username] = $pp;
				}
			}

			$wpp_users = array();
			$student_users = array();

			foreach ($users as $user) {
				if ( ! $user->has_cap( 'administrator' ) ) {
					if ( $user->has_cap( 'wppuser' ) ) {
						$wpp_users[] = $user;
					}
					if ( $user->has_cap( 'student' ) ) {
						$student_users[] = $user;
					}
				}
			}
			$summary = ltp_data::get_summary_data();


			if ( count( $student_users ) ) {
				print('<h2>Students</h2><table class="widefat">');
				print('<thead><tr><th>Name</th><th>Logged in?</th><th>Page published?</th><th>CV</th><th>Showcases</th><th>Views</th></tr></thead><tbody>');
				foreach ($student_users as $user) {
					// get the user data
					$userdata = ltp_template::get_user_data($user);

					print('<tr class="person-row">');

					// link name to page if available
					$userID = $user->data->ID;
					if ( ! isset($pages_map[$user->data->user_login])) {
						printf('<td>%s</td>', $user->data->display_name);
						$logged_in = false;
					} else {
						printf('<td><a href="%s">%s</a></td>', get_permalink($pages_map[$user->data->user_login]->ID), $user->data->display_name);
						$logged_in = true;
					}
					// has the user logged in?
					if ( ! $logged_in ) {
						$class = ' class="empty"';
						$text = "No";
					} else {
						$class = '';
						$text = "Yes";
					}
					printf('<td%s>%s</td>', $class, $text );

					// is the user profile page published?
					if ( $logged_in && isset( $pages_map[$user->data->user_login]) && $pages_map[$user->data->user_login]->post_status == 'publish' ) {
						$class = '';
						$text = "Yes";
						$has_page = true;
					} else {
						$class = ' class="empty"';
						$text = "No";
						$has_page = true;
					}
					printf('<td%s>%s</td>', $class, $text );

					// has a cv been uploaded?
					if ( $logged_in && ! empty( $userdata["cv"] ) ) {
						$class = '';
						$text = "Yes";
						if ( isset( $pages_map[$user->data->user_login]) ) {
							$downloads = ltp_data::get_downloads($pages_map[$user->data->user_login]->ID);
							if ( $downloads ) {
								$text .= ( $downloads > 1 )? "(downloaded " . $downloads . " times)": "(downloaded once)";
							}
						}
					} else {
						$class = ' class="empty"';
						$text = "No";
					}
					printf('<td%s>%s</td>', $class, $text );

					// how many showcases have been added?
					if ( $logged_in ) {
						$showcase_count = 0;
						for ( $i = 1; $i < 4; $i++ ) {
							if ( trim( $userdata["showcase" . $i . "_title"] ) !== '' || trim( $userdata["showcase" . $i . "_text"] ) !== '' || trim( $userdata["showcase" . $i . "_image"] ) !== '' || trim( $userdata["showcase" . $i . "_file"] ) !== '' || trim( $userdata["showcase" . $i . "_video"] ) !== '' ) {
								$showcase_count++;
							}
						}
						if ( $showcase_count > 0 ) {
							$class = '';
							$text = "Yes (" . $showcase_count . ")";
						} else {
							$class = ' class="empty"';
							$text = "None";
						}
					} else {
						$class = ' class="empty"';
						$text = "None";
					}
					printf('<td%s>%s</td>', $class, $text );

					// how many times has the profile been viewed?
					if ( $logged_in ) {
						$class = '';
						$text = "Yes";
						$views = ltp_data::get_views($pages_map[$user->data->user_login]->ID);
						if ( $views ) {
							$text = ( $views > 0 )? $views: "";
						}
					} else {
						$class = ' class="empty"';
						$text = "None";
					}
					printf('<td%s>%s</td>', $class, $text );
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

if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * class to extend WP_List_Table to display WPP users
 */
class ltp_WPP_List_Table extends WP_List_Table {

    function __construct()
    {
        parent::__construct( array(
            'singular'  => 'WPP User',
            'plural'    => 'WPP Users',
            'ajax'      => false
	    ) );
	}

	function no_items()
	{
		echo 'No WPP users found.';
	}

	function column_default( $item, $column_name )
	{
    	switch( $column_name ) { 
    	    case 'last_name':
        	case 'first_name':
        	case 'user_login':
        	case 'user_email';
        	case 'last_login':
        	case 'saved_profiles':
        	case 'view_count':
            	return $item[ $column_name ];
        	default:
            	return '';
		}
	}

	function get_sortable_columns() {
		$sortable_columns = array(
			'last_name'    => array( 'last_name', true ),
			'first_name' => array( 'first_name', false ),
			'user_login'   => array( 'user_login', false ),
			'user_email'      => array( 'user_email', false )
		);
		return $sortable_columns;
	}

	function get_columns(){
        $columns = array(
            'cb'        => '<input type="checkbox" />',
			'user_login'   => 'username',
			'last_name'  => 'Surname',
			'first_name' => 'First name',
			'user_email'  => 'email',
			'saved_profiles' => 'Saved profiles',
			'view_count' => 'Views',
			'last_login' => 'Last login'
        );
         return $columns;
    }

	function column_username($item)
	{
  		$actions = array(
            'edit' => sprintf('<a href="%s?user_id=%d" title="Edit Profile">Edit</a>', admin_url('user-edit.php'), $item["ID"])
        );
		return sprintf('%s %s', $item['user_login'], $this->row_actions($actions) );
	}

	function get_bulk_actions()
	{
		$actions = array(
			'disable_users' => 'Disable'
		);
		return $actions;
	}

	function column_cb($item)
	{
        return sprintf( '<input type="checkbox" name="selection[]" value="%s" />', $item['ID'] );    
    }

	function prepare_items()
	{
		/* set up the columns */
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		/* set paging parameters */
		$per_page = ltp_options::get_admin_per_page( 'ltp_wpp_users_pp' );
		$current_page = $this->get_pagenum();
		$offset = ( ( $current_page - 1 ) * $per_page );

		/* set ordering parameters */
		$poss_orderby = array( 'first_name', 'last_name', 'user_login', 'user_email' );
		$orderby = ( isset( $_REQUEST["orderby"] ) && in_array( $_REQUEST["orderby"], $poss_orderby ) ) ? $_REQUEST["orderby"]: 'last_name';
		$order = ( isset( $_REQUEST["order"] ) && in_array( strtoupper($_REQUEST["order"]), array( 'ASC', 'DESC' ) ) ) ? strtoupper($_REQUEST["order"]): 'ASC';

		$searchterm = ( isset( $_REQUEST["s"] ) && trim( $_REQUEST["s"] ) !== '' ) ? trim( $_REQUEST["s"] ): '';

		/* build arguments to user query */
		$args = array(
			'role' => 'wppuser',
			'number' => $per_page,
			'offset' => $offset,
			'fields' => 'all_with_meta',
			'count_total' => true
		);
		if ( $searchterm !== '' ) {
			$args['meta_query'] = array(
        		'relation' => 'OR',
        		array(
            		'key'     => 'first_name',
            		'value'   => $searchterm,
            		'compare' => 'LIKE'
        		),
        		array(
            		'key'     => 'last_name',
            		'value'   => $searchterm,
            		'compare' => 'LIKE'
        		)
    		);
    	}

		if ( $orderby ) {
			switch ( $orderby ) {
				case 'first_name':
				case 'last_name':
					$args['orderby'] = 'meta_value';
					$args['meta_key'] = $orderby;
					break;
				default:
					$args['orderby'] = $orderby;
			}
			$args['order'] = $order;
		}
		//print('<pre>' . print_r( $args, true ) . '</pre>');

		/* query users */
		$user_query = new WP_User_Query( $args );
		/* get total users */
		$total_items = $user_query->get_total();
		/* get results */
		$wpp_users = $user_query->get_results();
		/* get additional data */
		$summary = ltp_data::get_summary_data();
		/* build items for table */
		$this->items = array();
		if ( ! empty( $user_query->results ) ) {
			foreach ($user_query->results as $user) {
				//print('<pre>' . print_r($user, true) . '</pre>');
				$wpp_user = array();
				$wpp_user["ID"] = $user->ID;
				$wpp_user["first_name"] = $user->first_name;
				$wpp_user["last_name"] = $user->last_name;
				$wpp_user["user_login"] = $user->user_login;
				$wpp_user["user_email"] = $user->user_email;
				$wpp_user["view_count"] = ( isset( $summary[$user->ID] ) && isset( $summary[$user->ID]["view"] ) )? $summary[$user->ID]["view"]: 0;
				$wpp_user["saved_profiles"] = ( isset( $summary[$user->ID] ) && isset( $summary[$user->ID]["saved"] ) )? $summary[$user->ID]["saved"]: 0;
				$last_login = ltp_data::get_last_login( $user->ID );
				if ( ! $last_login ) {
					$wpp_user["last_login"] = '-';
				} else {
					$wpp_user["last_login"] = date( 'l jS \of F Y', $last_login );
				}
				if ( $wpp_user["saved_profiles"] > 0 ) {
					$saved_profiles = ltp_data::get_saved_profiles($user->ID);
					if ( count( $saved_profiles ) ) {
						$list = array();
						foreach ( $saved_profiles as $profile_page ) {
							array_push( $list, sprintf('<a href="%s" title="%s">%s</a>', get_permalink( $profile_page->ID ), esc_attr( $profile_page->post_title ), $profile_page->post_title ) );
						}
						$wpp_user["saved_profiles"] = implode( ', ', $list );
					}
				}
				//print('<pre>' . print_r($wpp_user, true) . '</pre>');
				array_push( $this->items, $wpp_user );
			}
		}

		$this->set_pagination_args( array(
			'total_items' => $total_items, 
			'per_page'    => $per_page
		) );

	}
}

