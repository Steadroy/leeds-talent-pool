<?php 
/**
 * Top of page for University of Leeds Wordpress theme
 * this contains the menus and sidebars
 * 
 * @author Peter Edwards <p.l.edwards@leeds.ac.uk>
 * @version 1.2.1
 * @package UoL_theme
 */

 
$theme_options = get_uol_theme_options();
?>
<body <?php body_class(); ?>>

    <div class="header"> 
        <a id="logo" href="http://www.leeds.ac.uk/" title="University of Leeds Homepage"><img src="<?php echo get_template_directory_uri(); ?>/img/logo/logo_black.png" class="hidden" alt="University of Leeds" /></a>
        <?php if ((isset($theme_options["header_link"]) && trim($theme_options["header_link"]) != "") && (isset($theme_options["header_title"]) && trim($theme_options["header_title"]) != "")) : ?>
                
                <h2><a href="<?php echo $theme_options["header_link"]; ?>" title="<?php echo esc_attr($theme_options["header_title"]); ?>"><?php echo $theme_options["header_title"]; ?></a></h2>
                
        <?php endif; ?>
    </div>

    <div class="content">

        <h1><a href="<?php echo home_url( '/' ); ?>" title="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>" rel="home"><span><?php bloginfo( 'name' ); ?></span></a></h1>
      
        <?php wp_nav_menu( array(
            'theme_location' => 'tabs',
            'menu_class' => 'nav-main',
            'depth' => 1
        ) ); ?>

        <div class="section-sidebar nav">
            <div class="section-sidebar-content">
            <?php ltp_sidebars::sidebar(); ?>
            </div>
        </div>

        <div class="content-main">