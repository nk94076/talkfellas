<?php get_header(); while (have_posts()) : the_post(); ?>
<div class="container narrow"><article <?php post_class('article'); ?>>
  <h1 class="article-title"><?php the_title(); ?></h1>
  <?php if (has_post_thumbnail()) : ?><figure class="article-img"><?php the_post_thumbnail('large'); ?></figure><?php endif; ?>
  <div class="entry"><?php the_content(); ?></div>
</article></div>
<?php endwhile; get_footer();
