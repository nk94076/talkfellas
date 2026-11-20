<?php get_header(); ?>
<div class="wrap">
  <h1 class="section-title"><?php if (is_search()) printf('Results for “%s”', esc_html(get_search_query())); elseif (is_archive()) the_archive_title(); else echo 'Latest Articles'; ?></h1>
  <?php if (have_posts()) : ?>
    <div class="grid"><?php while (have_posts()) { the_post(); tf_card(); } ?></div>
    <div class="pagination"><?php echo paginate_links(); ?></div>
  <?php else : ?><p>Nothing found.</p><?php endif; ?>
</div>
<?php get_footer();
