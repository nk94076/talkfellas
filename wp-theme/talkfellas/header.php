<!doctype html>
<html <?php language_attributes(); ?>>
<head><meta charset="<?php bloginfo('charset'); ?>"><meta name="viewport" content="width=device-width,initial-scale=1"><?php wp_head(); ?></head>
<body <?php body_class(); ?>><?php wp_body_open(); ?>
<div class="topbar">We independently research & review products. We may earn a commission from links on this page.</div>
<header class="site-header"><div class="wrap">
  <a class="logo" href="<?php echo esc_url(home_url('/')); ?>">Talk<span>Fellas</span></a>
  <nav class="main-nav"><?php wp_nav_menu(['theme_location'=>'primary','container'=>false,'fallback_cb'=>function(){ echo '<ul>'; wp_list_categories(['title_li'=>'','number'=>6,'orderby'=>'count','order'=>'DESC']); echo '</ul>'; }]); ?></nav>
  <form class="search-form" role="search" action="<?php echo esc_url(home_url('/')); ?>"><input type="search" name="s" placeholder="Search reviews…" value="<?php echo esc_attr(get_search_query()); ?>"><button aria-label="Search">⌕</button></form>
</div></header>
