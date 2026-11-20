<?php get_header(); while (have_posts()) : the_post(); $p = get_post(); ?>
<div class="container single-wrap">
  <article <?php post_class('article'); ?>>
    <?php if (function_exists('rank_math_the_breadcrumbs')) rank_math_the_breadcrumbs();
      elseif (function_exists('yoast_breadcrumb')) yoast_breadcrumb('<nav class="crumbs">', '</nav>');
      else { $c = tf_primary_cat(); echo '<nav class="crumbs"><a href="' . esc_url(home_url('/')) . '">Home</a>' . ($c ? ' / <a href="' . esc_url(get_category_link($c)) . '">' . esc_html($c->name) . '</a>' : '') . '</nav>'; } ?>
    <?php echo tf_cat_label($p, 'pill'); ?>
    <h1 class="article-title"><?php the_title(); ?></h1>
    <?php if (has_excerpt()) : ?><p class="lead"><?php echo esc_html(get_the_excerpt()); ?></p><?php endif; ?>
    <?php echo tf_meta($p, true); ?>
    <?php if (has_post_thumbnail()) : ?><figure class="article-img"><?php the_post_thumbnail('large', ['fetchpriority' => 'high']); ?></figure><?php endif; ?>
    <div class="disclosure"><?php echo tf_icon('shield', 16); ?> Some links in this article are affiliate links. We may earn a commission at no extra cost to you.</div>
    <div class="entry"><?php the_content(); wp_link_pages(); ?></div>
    <?php the_tags('<div class="post-tags">', '', '</div>'); ?>
    <div class="author-box"><?php echo get_avatar(get_the_author_meta('ID'), 64); ?><div><b><?php the_author(); ?></b><p><?php echo esc_html(get_the_author_meta('description') ?: 'Writer at ' . get_bloginfo('name') . '.'); ?></p></div></div>
    <?php if (comments_open() || get_comments_number()) comments_template(); ?>
  </article>
  <aside class="sidebar">
    <?php if (is_active_sidebar('sidebar')) : dynamic_sidebar('sidebar'); else : ?>
      <div class="widget popular"><h2><?php echo tf_icon('trophy', 22) . esc_html(tf_mod('popular_title')); ?></h2><ol>
        <?php foreach (tf_popular(5) as $i => $q) : ?><li><span class="num"><?php printf('%02d', $i + 1); ?></span><a href="<?php echo esc_url(get_permalink($q)); ?>"><?php echo esc_html(get_the_title($q)); ?></a><span class="pop-img"><?php echo tf_thumb($q, 'tf-thumb'); ?></span></li><?php endforeach; ?>
      </ol></div>
      <div class="widget"><h3 class="widget-title">Categories</h3><ul class="cat-list"><?php wp_list_categories(['title_li' => '', 'show_count' => true]); ?></ul></div>
    <?php endif; ?>
  </aside>
</div>
<?php $c = tf_primary_cat(); $rel = $c ? get_posts(['numberposts' => 3, 'category' => $c->term_id, 'exclude' => [get_the_ID()]]) : [];
if ($rel) : ?>
<section class="container"><?php tf_section_head('', 'You May Also Like'); ?><div class="cards"><?php foreach ($rel as $q) get_template_part('template-parts/card', null, ['post' => $q]); ?></div></section>
<?php endif; endwhile; get_footer();
