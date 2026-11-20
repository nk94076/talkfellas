<?php
add_action('after_setup_theme', function () {
  add_theme_support('title-tag');
  add_theme_support('post-thumbnails');
  add_theme_support('html5', ['search-form','gallery','caption']);
  add_theme_support('responsive-embeds');
  add_theme_support('align-wide');
  register_nav_menus(['primary' => 'Header Menu', 'footer' => 'Footer Menu']);
});
add_action('wp_enqueue_scripts', function () {
  wp_enqueue_style('talkfellas', get_stylesheet_uri(), [], '1.0');
});
add_action('widgets_init', function () {
  register_sidebar(['name'=>'Sidebar','id'=>'sidebar','before_widget'=>'<div class="widget">','after_widget'=>'</div>','before_title'=>'<h3>','after_title'=>'</h3>']);
});

/**
 * Ranked product card for Top-10 posts.
 * [product rank="1" name="Product" image="URL" score="9.8" link="AFFILIATE URL" badge="Best Overall" store="Amazon"]
 * Feature one | Feature two | Feature three
 * [/product]
 */
add_shortcode('product', function ($a, $content = '') {
  $a = shortcode_atts(['rank'=>'','name'=>'','image'=>'','score'=>'','link'=>'#','badge'=>'','store'=>'Amazon','button'=>'Check Price'], $a);
  $features = array_filter(array_map('trim', explode('|', wp_strip_all_tags($content))));
  ob_start(); ?>
  <div class="tf-product<?php echo $a['rank']==='1' ? ' is-top' : ''; ?>">
    <?php if ($a['rank']) : ?><div class="tf-rank"><?php echo esc_html($a['rank']); ?></div><?php endif; ?>
    <div class="tf-img"><?php if ($a['image']) : ?><a href="<?php echo esc_url($a['link']); ?>" rel="nofollow sponsored noopener" target="_blank"><img src="<?php echo esc_url($a['image']); ?>" alt="<?php echo esc_attr($a['name']); ?>" loading="lazy"></a><?php endif; ?></div>
    <div class="tf-info">
      <?php if ($a['badge']) : ?><span class="tf-badge"><?php echo esc_html($a['badge']); ?></span><?php endif; ?>
      <h3><?php echo esc_html($a['name']); ?></h3>
      <?php if ($features) : ?><ul><?php foreach ($features as $f) echo '<li>'.esc_html($f).'</li>'; ?></ul><?php endif; ?>
    </div>
    <div class="tf-cta">
      <?php if ($a['score']) : ?><div class="tf-score"><?php echo esc_html($a['score']); ?><small>Our Score</small></div><?php endif; ?>
      <a class="tf-btn" href="<?php echo esc_url($a['link']); ?>" rel="nofollow sponsored noopener" target="_blank"><?php echo esc_html($a['button']); ?> →</a>
      <div class="tf-store">on <?php echo esc_html($a['store']); ?></div>
    </div>
  </div>
  <?php return ob_get_clean();
});

function tf_card($heading = 'h2') { ?>
  <article class="card">
    <a class="thumb" href="<?php the_permalink(); ?>"><?php if (has_post_thumbnail()) the_post_thumbnail('medium_large'); ?></a>
    <div class="body">
      <?php $c = get_the_category(); if ($c) : ?><a class="cat-label" href="<?php echo esc_url(get_category_link($c[0])); ?>"><?php echo esc_html($c[0]->name); ?></a><?php endif; ?>
      <<?php echo $heading; ?>><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></<?php echo $heading; ?>>
      <div class="meta">Updated <?php echo get_the_modified_date(); ?></div>
    </div>
  </article>
<?php }
