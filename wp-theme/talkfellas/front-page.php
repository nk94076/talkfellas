<?php get_header(); ?>
<section class="hero"><div class="wrap">
  <h1>The Best Products, Ranked & Reviewed</h1>
  <p>Expert top-10 lists to help you buy smarter.</p>
  <div class="cats"><?php foreach (get_categories(['number'=>8,'orderby'=>'count','order'=>'DESC']) as $c) echo '<a href="'.esc_url(get_category_link($c)).'">'.esc_html($c->name).'</a>'; ?></div>
</div></section>
<div class="wrap">
  <h2 class="section-title">Latest Top 10 Lists</h2>
  <div class="grid"><?php $q = new WP_Query(['posts_per_page'=>12]); while ($q->have_posts()) { $q->the_post(); tf_card('h3'); } wp_reset_postdata(); ?></div>
  <?php foreach (get_categories(['number'=>4,'orderby'=>'count','order'=>'DESC']) as $c) :
    $q = new WP_Query(['cat'=>$c->term_id,'posts_per_page'=>4]); if (!$q->have_posts()) continue; ?>
    <h2 class="section-title"><?php echo esc_html($c->name); ?></h2>
    <div class="grid"><?php while ($q->have_posts()) { $q->the_post(); tf_card('h3'); } wp_reset_postdata(); ?></div>
  <?php endforeach; ?>
</div>
<?php get_footer();
