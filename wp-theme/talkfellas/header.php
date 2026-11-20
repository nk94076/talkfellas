<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip" href="#main">Skip to content</a>
<header class="site-header">
  <div class="container hdr">
    <a class="brand" href="<?php echo esc_url(home_url('/')); ?>" rel="home">
      <?php if (has_custom_logo()) : echo wp_get_attachment_image(get_theme_mod('custom_logo'), 'full', false, ['class' => 'brand-logo', 'alt' => get_bloginfo('name')]); else : ?>
        <span class="brand-mark"><?php echo tf_icon('users', 20); ?></span>
        <span class="brand-text"><b><?php echo esc_html(get_bloginfo('name')); ?></b><small><?php echo esc_html(tf_mod('logo_tagline')); ?></small></span>
      <?php endif; ?>
    </a>
    <nav class="main-nav" id="main-nav" aria-label="Main">
      <?php wp_nav_menu(['theme_location' => 'primary', 'container' => false, 'menu_class' => 'menu', 'depth' => 2, 'fallback_cb' => function () {
        echo '<ul class="menu"><li class="current-menu-item"><a href="' . esc_url(home_url('/')) . '">Home</a></li>';
        foreach (tf_home_cats(5) as $c) echo '<li><a href="' . esc_url(get_category_link($c)) . '">' . esc_html($c->name) . '</a></li>';
        echo '</ul>';
      }]); ?>
    </nav>
    <div class="hdr-actions">
      <button class="icon-btn" id="theme-toggle" aria-label="Toggle dark mode"><?php echo tf_icon('moon', 20, 'i-moon') . tf_icon('sun', 20, 'i-sun'); ?></button>
      <button class="icon-btn" id="search-toggle" aria-label="Search" aria-expanded="false"><?php echo tf_icon('search', 20); ?></button>
      <button class="icon-btn menu-toggle" id="menu-toggle" aria-label="Menu" aria-controls="main-nav" aria-expanded="false"><?php echo tf_icon('menu', 22); ?></button>
    </div>
  </div>
  <div class="search-panel" id="search-panel" hidden>
    <div class="container"><?php get_search_form(); ?></div>
  </div>
</header>
<main id="main">
