<?php
/**
 * filters used by the theme to modify output
 */

if ( ! class_exists( 'ltp_filters' ) ) {

	class ltp_filters
	{
		/* register filters with Wordpress API */
		public static function register()
		{
			/* add filter to people post type shortcode output */
			add_filter( 'ppt_field', array( __CLASS__, 'form_fields_filter' ), 1, 2 );

			/* add filter to body class to prevent site sidbar appearing there */
			add_filter( 'body_class', array( __CLASS__, 'remove_site_sidebar' ), 10000);

			/* add filter to limit media in media library to only thoise items the current user has uploaded */
			add_filter( 'parse_query', array( __CLASS__, 'parse_query_useronly' ) );

			/* add filter to ltp search results */
			add_filter( 'ltp_results', array( __CLASS__, 'filter_students' ), 1 );

		}

		/* filter for people post type shortcode */
		public static function form_fields_filter( $output, $data )
		{
			switch ( $data["config"]["type"] ) {
				case "image":
				case "file":
					return sprintf('<div class="media-controls">%s</div>', $data["control"]["input"] );
					break;
				case "text":
					return $data["control"]["input"];
					break;
				case "select":
					if ( $data["config"]["control_type"] == "dropdown" && $data["config"]["allow_multiple_select"] == "0" ) {
						return sprintf('<div class="select-wrapper"><div class="select-bg">%s</div></div>', $data["control"]["input"]);
					} else {
						return $data["control"]["input"];
					}
					break;
				default:
					return $data["control"]["input"];
			}
			return $output;
		}

		/* removes the site sidebar class from the body */
		public static function remove_site_sidebar( $classes )
		{
			$newclasses = array( 'sidebars-none' );
			foreach ($classes as $class) {
				if ( $class !== 'sidebars-site' && $class !== 'sidebars-both' && $class !== 'sidebar-corporate' && $class !== 'sidebars-section' ) {
					$newclasses[] = $class;
				}
			}
			return array_unique( $newclasses );
		}
		
		/* adds a query var to the query for media items so only those uploaded by the current user are viewable */
		public static function parse_query_useronly( $wp_query )
		{
			global $current_user, $pagenow;
			
			if ( ! is_a( $current_user, 'WP_User') ) {
				return;
			}
			if ( 'upload.php' == $pagenow || ('admin-ajax.php' == $pagenow && $_REQUEST['action'] == 'query-attachments') ) {
				if ( ltp_is_student() ) {
					$wp_query->set( 'author', $current_user->id );
				}
			}
		}

		/* filters search results */
		public static function filter_students( $students )
		{
			$filtered_students = array();
			foreach ( $students as $student ) {
				// go through applying filters
				// if we get a match, continue
				// student added to output when all filters have been applied
				if ( isset( $_REQUEST["filterby_region"] ) ) {
					$regions = explode( ",", $_REQUEST["filterby_region"] );
					if ( ! in_array( $student["region"], $regions ) ) {
						continue;
					}
				}
				if ( isset( $_REQUEST["filterby_desired_region"] ) ) {
					$regions = explode( ",", $_REQUEST["filterby_desired_region"] );
					if ( ! in_array( $student["desired_region"], $regions ) ) {
						continue;
					}
				}

				$filtered_students[] = $student;
			}
			return $filtered_students;
		}
	}
	ltp_filters::register();
}