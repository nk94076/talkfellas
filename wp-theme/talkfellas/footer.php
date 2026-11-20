</main>
<footer class="site-footer">
  <div class="container foot-grid">
    <div class="foot-about">
      <a class="brand light" href="<?php echo esc_url(home_url('/')); ?>">
        <span class="brand-text"><b><?php echo esc_html(get_bloginfo('name')); ?></b><small><?php echo esc_html(tf_mod('logo_tagline')); ?></small></span>
      </a>
      <p><?php echo wp_kses_post(tf_mod('footer_about')); ?></p>
      <div class="social">
        <?php foreach (['social_x' => 'x-social', 'social_facebook' => 'facebook', 'social_instagram' => 'instagram', 'social_youtube' => 'youtube', 'social_linkedin' => 'linkedin'] as $k => $i) if ($u = tf_mod($k)) echo '<a href="' . esc_url($u) . '" target="_blank" rel="noopener" aria-label="' . esc_attr($i) . '">' . tf_icon($i, 18) . '</a>'; ?>
      </div>
    </div>
    <?php for ($i = 1; $i <= 4; $i++) : $loc = "footer-$i"; if (!has_nav_menu($loc)) continue;
      $menu = wp_get_nav_menu_object(get_nav_menu_locations()[$loc]); ?>
      <div class="foot-col"><h4><?php echo esc_html($menu ? $menu->name : ''); ?></h4><?php wp_nav_menu(['theme_location' => $loc, 'container' => false, 'menu_class' => 'foot-menu', 'depth' => 1]); ?></div>
    <?php endfor; ?>
  </div>
  <div class="container foot-copy"><?php echo wp_kses_post(str_replace('{year}', date('Y'), tf_mod('footer_copy'))); ?></div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
