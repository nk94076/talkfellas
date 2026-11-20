<form role="search" method="get" class="search-form" action="<?php echo esc_url(home_url('/')); ?>">
  <label class="screen-reader-text" for="s-<?php echo $u = wp_unique_id(); ?>">Search for:</label>
  <input type="search" id="s-<?php echo $u; ?>" name="s" placeholder="Search articles, reviews, guides…" value="<?php echo esc_attr(get_search_query()); ?>">
  <button type="submit" class="btn"><?php echo tf_icon('search', 18); ?><span>Search</span></button>
</form>
