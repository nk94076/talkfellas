<?php get_header(); the_post(); ?>
<div class="wrap layout">
  <article class="post">
    <?php $c = get_the_category(); if ($c) : ?><a class="cat-label" href="<?php echo esc_url(get_category_link($c[0])); ?>"><?php echo esc_html($c[0]->name); ?></a><?php endif; ?>
    <h1><?php the_title(); ?></h1>
    <div class="meta">By <?php the_author(); ?> · Updated <?php echo get_the_modified_date(); ?></div>
    <div class="disclosure">We may earn a commission when you buy through links on this page, at no extra cost to you.</div>
    <?php if (has_post_thumbnail()) the_post_thumbnail('large'); ?>
    <?php the_content(); ?>
  </article>
  <aside class="sidebar">
    <?php if (is_active_sidebar('sidebar')) : dynamic_sidebar('sidebar'); else : ?>
      <div class="widget"><h3>Popular Lists</h3><ul><?php wp_get_archives(['type'=>'postbypost','limit'=>8]); ?></ul></div>
      <div class="widget"><h3>Categories</h3><ul><?php wp_list_categories(['title_li'=>'']); ?></ul></div>
    <?php endif; ?>
  </aside>
</div>
<?php $rel = new WP_Query(['category__in'=>wp_get_post_categories(get_the_ID()),'post__not_in'=>[get_the_ID()],'posts_per_page'=>3]);
if ($rel->have_posts()) : ?><div class="wrap"><h2 class="section-title">You May Also Like</h2><div class="grid"><?php while ($rel->have_posts()) { $rel->the_post(); tf_card('h3'); } wp_reset_postdata(); ?></div></div><?php endif; ?>
<?php get_footer();
