<?php
/**
 * Theme footer
 *
 * @author Peter Edwards <p.l.edwards@leeds.ac.uk>
 * @version 1.2.1
 * @package UoL_theme
 */
?>
        </div><!-- #.content-main -->
    </div><!-- #.content -->

    <div class="content-info">
        <ul class="nav meta">
            <?php if ( is_user_logged_in() ) : ?>
			<li><a href="<?php echo wp_logout_url(ltp_login::login_page_url()); ?>" title="Logout">Logout</a></li>
			<?php endif; ?>
        </ul>
        <p>&copy; Copyright Leeds <?php echo date("Y"); ?></p>
    </div>

<?php
$options = get_uol_theme_options();
if (isset($options["google_analytics_key"]) && trim($options["google_analytics_key"]) != "") :
	if (!isset($options["universal_analytics"]) || $options["universal_analytics"] == false) :
?>
<script type="text/javascript">
  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', '<?php echo $options["google_analytics_key"]; ?>']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();
</script>
<?php
	else :
?>
<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

  ga('create', '<?php echo $options["google_analytics_key"]; ?>', 'auto');
  ga('send', 'pageview');

</script>
<?php
	endif;
endif;
wp_footer();
?>
</body>
</html>
