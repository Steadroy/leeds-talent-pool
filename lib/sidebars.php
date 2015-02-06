<?php
/** 
 * Connect site sidebars
 * These sidebars will change according to the user role
 */

if ( ! class_exists( 'ltp_sidebars' ) ) {

	class ltp_sidebars
	{
		/* registers with Wordpress API */
		public static function register()
		{
			/* register sidebars */
			add_action( 'init', array(__CLASS__, 'register_sidebars') );
		}

		public static function register_sidebars()
		{

			/* sidebar shown to students */
			register_sidebar(array(
				'name' => 'Student Sidebar',
				'id' => 'student-sidebar',
				'description' => 'Additional sidebar for Student users',
				'before_widget' => '<div class="student-sidebar" id="%1$s">',
				'after_widget' => '</div>',
				'before_title' => '<h4>',
				'after_title' => '</h4>'
			));

			/* sidebar shown to WPP users */
			register_sidebar(array(
				'name' => 'WPP Sidebar',
				'id' => 'wpp-sidebar',
				'description' => 'Additional sidebar for WPP users',
				'before_widget' => '<div class="wpp-sidebar" id="%1$s">',
				'after_widget' => '</div>',
				'before_title' => '<h4>',
				'after_title' => '</h4>',
			));
		}

		/* outputs sidebars based on user type */
		public static function sidebar()
		{
			global $post;
			if ( is_wpp() ) {

				dynamic_sidebar( 'wpp-sidebar' );
			} else {
				$menu = array();
				$options = ltp_options::get_options();
				if ( isset( $options["builder_page_id"] ) ) {
					$builder_url = get_permalink($options["builder_page_id"]);
					if ( $builder_url ) {
						$class = ( $post && $post->ID && $post->ID == $options["builder_page_id"] ) ? ' class="active"': '';
						$menu[] = sprintf( '<li><a href="%s" title="Profile Builder"%s>Profile Builder</a></li>', $builder_url, $class );
					}
				}
				global $current_user;
				$people_page = get_pages( array(
					'number' => 1,
					'meta_key' => 'wp_username',
					'meta_value' => $current_user->user_login
				) );
				if ( count( $people_page ) ) {
					$viewer_url = get_permalink( $people_page[0]->ID );
					if ( $viewer_url ) {
						$class = ( $post && $post->ID && $people_page[0] && $people_page[0]->ID && $people_page[0]->ID == $post->ID ) ? ' class="active"': '';
						$menu[] = sprintf( '<li><a href="%s" title="Profile Viewer"%s>Profile Viewer</a></li>', $viewer_url, $class );
					}
				}
				if ( count( $menu ) ) {
					printf( '<ul class="ltp-menu">%s</ul>', implode( "", $menu ) );
				}
				dynamic_sidebar( 'student-sidebar' );
			}
		}
	}
	ltp_sidebars::register();
}
