<?php
/**
 * Leeds Talent pool functions
 */

if ( ! class_exists( 'leeds_talent_pool' ) ) {
	class leeds_talent_pool
	{
		public static function register()
		{
			// hide admin bar from front end
			add_filter('show_admin_bar', '__return_false');
		}
	}
	leeds_talent_pool::register();
}