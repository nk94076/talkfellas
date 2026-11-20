<?php get_header(); ?>
<section class="page-head"><div class="container">
  <?php if (is_search()) : ?><span class="eyebrow">Search results</span><h1>“<?php echo esc_html(get_search_query()); ?>”</h1>
  <?php elseif (is_archive()) : ?><span class="eyebrow"><?php echo is_category() ? 'Category' : (is_tag() ? 'Topic' : 'Archive'); ?></span><h1><?php echo esc_html(single_term_title('', false) ?: wp_strip_all_tags(get_the_archive_title())); ?></h1><?php the_archive_description('<div class="desc">', '</div>'); ?>
  <?php else : ?><span class="eyebrow">Blog</span><h1><?php echo esc_html(get_the_title(get_option('page_for_posts')) ?: 'Latest Articles'); ?></h1><?php endif; ?>
  <?php if (is_search()) get_search_form(); ?>
</div></section>
<div class="container">
  <?php if (have_posts()) : ?>
    <div class="cards"><?php while (have_posts()) { the_post(); get_template_part('template-parts/card'); } ?></div>
    <?php the_posts_pagination(['mid_size' => 1, 'prev_text' => '←', 'next_text' => '→']); ?>
  <?php else : ?><p class="empty">Nothing found. Try a different search.</p><?php endif; ?>
</div>
<?php get_footer();
