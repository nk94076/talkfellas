<?php
get_header();
$used = [];
?>
<!-- ===== HERO ===== -->
<section class="hero">
  <div class="container hero-grid">
    <div class="hero-copy">
      <span class="eyebrow"><?php echo esc_html(tf_mod('hero_eyebrow')); ?></span>
      <h1><?php echo esc_html(tf_mod('hero_title')); ?> <em><?php echo esc_html(tf_mod('hero_highlight')); ?></em></h1>
      <p><?php echo esc_html(tf_mod('hero_text')); ?></p>
      <div class="hero-btns">
        <?php if (tf_mod('hero_btn1')) : ?><a class="btn" href="<?php echo esc_url(tf_mod('hero_btn1_url')); ?>"><?php echo esc_html(tf_mod('hero_btn1')) . tf_icon('arrow', 16); ?></a><?php endif; ?>
        <?php if (tf_mod('hero_btn2')) : ?><a class="btn btn-outline" href="<?php echo esc_url(tf_mod('hero_btn2_url')); ?>"><?php echo esc_html(tf_mod('hero_btn2')); ?></a><?php endif; ?>
      </div>
      <?php if (tf_mod('hero_trust')) : ?>
        <div class="trust"><span class="avs"><?php foreach (get_users(['number' => 3, 'role__in' => ['administrator', 'editor', 'author']]) as $u) echo get_avatar($u->ID, 34); ?></span><span><?php echo wp_kses_post(tf_mod('hero_trust')); ?></span></div>
      <?php endif; ?>
    </div>
    <?php $slides = tf_flagged('tf_hero', max(1, (int)tf_mod('hero_slides'))); ?>
    <div class="slider" data-slider>
      <?php foreach ($slides as $i => $p) : $used[] = $p->ID; ?>
        <article class="slide<?php echo $i ? '' : ' is-active'; ?>">
          <?php echo has_post_thumbnail($p) ? get_the_post_thumbnail($p, 'tf-hero', ['loading' => $i ? 'lazy' : 'eager', 'fetchpriority' => $i ? 'auto' : 'high']) : '<span class="ph"></span>'; ?>
          <div class="slide-body">
            <?php echo tf_cat_label($p, 'pill'); ?>
            <h2><a href="<?php echo esc_url(get_permalink($p)); ?>"><?php echo esc_html(get_the_title($p)); ?></a></h2>
            <?php echo tf_meta($p, true); ?>
          </div>
        </article>
      <?php endforeach; ?>
      <?php if (count($slides) > 1) : ?>
        <div class="slider-nav"><button class="sl-btn" data-prev aria-label="Previous"><?php echo tf_icon('chev-l', 18); ?></button><button class="sl-btn" data-next aria-label="Next"><?php echo tf_icon('chev-r', 18); ?></button></div>
        <div class="slider-dots"><?php foreach ($slides as $i => $p) echo '<button data-dot="' . $i . '" class="' . ($i ? '' : 'on') . '" aria-label="Slide ' . ($i + 1) . '"></button>'; ?></div>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php $cats = tf_home_cats(7); if (tf_mod('show_catbar') && $cats) : ?>
<!-- ===== CATEGORY BAR ===== -->
<section class="container">
  <nav class="catbar" aria-label="Categories">
    <?php foreach ($cats as $c) : ?><a href="<?php echo esc_url(get_category_link($c)); ?>"><?php echo tf_icon(tf_cat_icon($c), 28); ?><span><?php echo esc_html($c->name); ?></span></a><?php endforeach; ?>
    <a href="<?php echo esc_url(tf_mod('cats_link') ?: home_url('/?s=')); ?>"><?php echo tf_icon('grid', 28); ?><span>More</span></a>
  </nav>
</section>
<?php endif; ?>

<?php if (tf_mod('show_trending') && has_nav_menu('trending')) : ?>
<!-- ===== TRENDING ===== -->
<section class="container">
  <div class="trending">
    <strong><?php echo tf_icon('bolt', 20) . esc_html(tf_mod('trending_title')); ?></strong>
    <?php wp_nav_menu(['theme_location' => 'trending', 'container' => false, 'menu_class' => 'chips', 'depth' => 1]); ?>
    <?php if (tf_mod('trending_link')) : ?><a class="more" href="<?php echo esc_url(tf_mod('trending_link')); ?>"><?php echo esc_html(tf_mod('trending_link_text')) . tf_icon('arrow', 16); ?></a><?php endif; ?>
  </div>
</section>
<?php endif; ?>

<?php if (tf_mod('show_latest')) : $latest = get_posts(['numberposts' => 4, 'post__not_in' => $used]); ?>
<!-- ===== LATEST + POPULAR ===== -->
<section class="container latest-grid">
  <div>
    <?php tf_section_head(tf_mod('latest_eyebrow'), tf_mod('latest_title'), tf_mod('latest_link') ?: (get_option('page_for_posts') ? get_permalink(get_option('page_for_posts')) : ''), 'View All Articles'); ?>
    <?php if ($latest) : $big = array_shift($latest); $used[] = $big->ID; ?>
    <div class="latest">
      <article class="card card-big">
        <div class="card-img"><a href="<?php echo esc_url(get_permalink($big)); ?>" tabindex="-1" aria-hidden="true"><?php echo tf_thumb($big, 'tf-hero'); ?></a><?php echo tf_cat_label($big, 'pill on-img'); ?></div>
        <div class="card-body">
          <h3><a href="<?php echo esc_url(get_permalink($big)); ?>"><?php echo esc_html(get_the_title($big)); ?></a></h3>
          <p><?php echo esc_html(get_the_excerpt($big)); ?></p>
          <?php echo tf_meta($big, true); ?>
        </div>
      </article>
      <div class="list">
        <?php foreach ($latest as $p) : $used[] = $p->ID; ?>
          <article class="row">
            <a class="row-img" href="<?php echo esc_url(get_permalink($p)); ?>"><?php echo tf_thumb($p, 'tf-thumb'); ?></a>
            <div><h3><a href="<?php echo esc_url(get_permalink($p)); ?>"><?php echo esc_html(get_the_title($p)); ?></a></h3><?php echo tf_cat_label($p); echo tf_meta($p); ?></div>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
    <?php else : ?><p class="empty">Publish your first post to see it here.</p><?php endif; ?>
  </div>
  <aside class="popular">
    <h2><?php echo tf_icon('trophy', 26) . esc_html(tf_mod('popular_title')); ?></h2>
    <ol>
      <?php foreach (tf_popular(5) as $i => $p) : ?>
        <li><span class="num"><?php echo sprintf('%02d', $i + 1); ?></span><a href="<?php echo esc_url(get_permalink($p)); ?>"><?php echo esc_html(get_the_title($p)); ?></a><span class="pop-img"><?php echo tf_thumb($p, 'tf-thumb'); ?></span></li>
      <?php endforeach; ?>
    </ol>
  </aside>
</section>
<?php endif; ?>

<?php if (tf_mod('show_promo')) : ?>
<!-- ===== REVIEWS BANNER ===== -->
<section class="container">
  <div class="promo">
    <div class="promo-copy">
      <h2><?php echo esc_html(tf_mod('promo_title')); ?></h2>
      <p><?php echo esc_html(tf_mod('promo_text')); ?></p>
      <?php if (tf_mod('promo_btn')) : ?><a class="btn sm" href="<?php echo esc_url(tf_mod('promo_btn_url')); ?>"><?php echo esc_html(tf_mod('promo_btn')) . tf_icon('arrow', 14); ?></a><?php endif; ?>
    </div>
    <div class="promo-img"><?php if (tf_mod('promo_image')) : ?><img src="<?php echo esc_url(tf_mod('promo_image')); ?>" alt="" loading="lazy"><?php else : echo tf_icon('camera', 120); endif; ?></div>
    <ul class="promo-points">
      <?php foreach (['shield', 'bulb', 'chart'] as $n => $ic) if ($t = tf_mod('promo_point' . ($n + 1))) echo '<li>' . tf_icon($ic, 22) . esc_html($t) . '</li>'; ?>
    </ul>
  </div>
</section>
<?php endif; ?>

<?php if (tf_mod('show_cats') && $cats) : ?>
<!-- ===== EXPLORE BY CATEGORY ===== -->
<section class="container">
  <?php tf_section_head('', tf_mod('cats_title'), tf_mod('cats_link'), 'View All Categories'); ?>
  <div class="cat-tiles">
    <?php foreach ($cats as $c) : $img = tf_cat_image($c); ?>
      <a class="cat-tile" href="<?php echo esc_url(get_category_link($c)); ?>">
        <span class="ct-img"><?php echo $img ? '<img src="' . esc_url($img) . '" alt="" loading="lazy">' : tf_icon(tf_cat_icon($c), 40); ?></span>
        <b><?php echo esc_html($c->name); ?></b>
      </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php if (tf_mod('show_picks')) : $picks = tf_flagged('tf_pick', 3); ?>
<!-- ===== EDITOR'S PICKS ===== -->
<section class="container picks">
  <div class="picks-intro">
    <h2 class="sec-title"><?php echo esc_html(tf_mod('picks_title')); ?></h2>
    <p><?php echo esc_html(tf_mod('picks_text')); ?></p>
    <?php if (tf_mod('picks_btn') && tf_mod('picks_btn_url')) : ?><a class="btn dark sm" href="<?php echo esc_url(tf_mod('picks_btn_url')); ?>"><?php echo esc_html(tf_mod('picks_btn')) . tf_icon('arrow', 14); ?></a><?php endif; ?>
  </div>
  <?php foreach ($picks as $p) : ?>
    <article class="card">
      <div class="card-img"><a href="<?php echo esc_url(get_permalink($p)); ?>" tabindex="-1" aria-hidden="true"><?php echo tf_thumb($p); ?></a><?php echo tf_cat_label($p, 'pill on-img'); ?></div>
      <div class="card-body"><h3><a href="<?php echo esc_url(get_permalink($p)); ?>"><?php echo esc_html(get_the_title($p)); ?></a></h3><?php echo tf_meta($p); ?></div>
    </article>
  <?php endforeach; ?>
</section>
<?php endif; ?>

<?php if (tf_mod('show_howto')) : $hc = (int)tf_mod('howto_cat'); $how = $hc ? get_posts(['numberposts' => 3, 'category' => $hc]) : []; if ($how) : ?>
<!-- ===== HOW-TO ===== -->
<section class="container">
  <div class="howto">
    <div class="howto-intro"><?php echo tf_icon('gear', 30); ?><div><h2><a href="<?php echo esc_url(get_category_link($hc)); ?>"><?php echo esc_html(tf_mod('howto_title')); ?></a></h2><p><?php echo esc_html(tf_mod('howto_text')); ?></p></div></div>
    <?php foreach ($how as $i => $p) : ?>
      <a class="howto-item" href="<?php echo esc_url(get_permalink($p)); ?>"><span class="hi-ic"><?php echo has_post_thumbnail($p) ? get_the_post_thumbnail($p, 'thumbnail') : tf_icon(['laptop', 'globe', 'bulb'][$i], 34); ?></span><b><?php echo esc_html(get_the_title($p)); ?> <?php echo tf_icon('arrow', 14); ?></b></a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; endif; ?>

<?php if (tf_mod('show_news')) : $ext = tf_mod('news_action'); ?>
<!-- ===== NEWSLETTER ===== -->
<section class="container" id="newsletter">
  <div class="newsletter">
    <span class="nl-ic"><?php echo tf_icon('send', 44); ?></span>
    <div class="nl-copy"><h2><?php echo esc_html(tf_mod('news_title')); ?></h2><p><?php echo esc_html(tf_mod('news_text')); ?></p></div>
    <form class="nl-form" method="post" action="<?php echo esc_url($ext ?: admin_url('admin-post.php')); ?>">
      <?php if (!$ext) : ?><input type="hidden" name="action" value="tf_subscribe"><?php wp_nonce_field('tf_sub', '_tfn'); ?><input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true"><?php endif; ?>
      <label class="nl-field"><?php echo tf_icon('mail', 18); ?><input type="email" name="<?php echo $ext ? 'EMAIL' : 'email'; ?>" required placeholder="Enter your email address" aria-label="Email address"></label>
      <button class="btn dark"><?php echo esc_html(tf_mod('news_btn')) . tf_icon('arrow', 16); ?></button>
      <?php if (isset($_GET['sub'])) echo '<p class="nl-msg">' . ($_GET['sub'] === 'ok' ? 'Thanks for subscribing! 🎉' : 'Please enter a valid email.') . '</p>'; ?>
    </form>
  </div>
</section>
<?php endif; ?>

<?php if (tf_mod('show_stats')) : ?>
<!-- ===== STATS ===== -->
<section class="container stats">
  <?php foreach (['users', 'doc', 'star', 'globe'] as $n => $ic) : $k = 'stat' . ($n + 1); if (!tf_mod($k . '_num')) continue; ?>
    <div class="stat"><?php echo tf_icon($ic, 34); ?><div><b><?php echo esc_html(tf_mod($k . '_num')); ?></b><span><?php echo esc_html(tf_mod($k . '_label')); ?></span></div></div>
  <?php endforeach; ?>
</section>
<?php endif; ?>
<?php get_footer();
