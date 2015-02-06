<?php
/**
 * <head> for University of Leeds Wordpress theme
 *
 * @author Peter Edwards <p.l.edwards@leeds.ac.uk>
 * @version 1.2.1
 * @package UoL_theme
 */
?><!doctype html>
<html dir="ltr" lang="en">

<head>
    <meta charset="utf-8" />
    
	<title><?php wp_title(); ?></title>


    <link rel="Shortcut Icon" type="image/ico" href="http://www.leeds.ac.uk/site/favicon.ico" />
    
	<?php uol_meta(); ?>

    <!-- site styles -->
    <link href='http://fonts.googleapis.com/css?family=Open+Sans:400,700' rel='stylesheet' type='text/css'>
    
    <!-- dynamic styles -->
    <?php //echo uol_get_dynamic_styles(); ?>

    <!-- dynamic scripts -->
    <?php //echo uol_get_dynamic_scripts(); ?>

    <?php wp_head(); ?>
    <link rel="stylesheet" href="<?php echo get_stylesheet_uri(); ?>" />

</head>