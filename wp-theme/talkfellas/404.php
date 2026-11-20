<?php get_header(); ?>
<div class="container narrow center"><div class="article"><span class="eyebrow">Error 404</span><h1 class="article-title">Page not found</h1><p>The page you’re looking for doesn’t exist. Try searching:</p><?php get_search_form(); ?><p><a class="btn" href="<?php echo esc_url(home_url('/')); ?>">Back to Home</a></p></div></div>
<?php get_footer();
