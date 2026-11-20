<?php
/**
 * TalkFellas theme — every homepage section is editable from
 * Appearance → Customize → "TalkFellas Homepage", Posts (TalkFellas Options box),
 * Posts → Categories (icon / image / show on homepage) and Appearance → Menus.
 */
define('TF_VER', '1.0.0');
require get_template_directory() . '/inc/icons.php';
require get_template_directory() . '/inc/customizer.php';
require get_template_directory() . '/inc/meta.php';

add_action('after_setup_theme', function () {
  load_theme_textdomain('talkfellas');
  add_theme_support('title-tag');
  add_theme_support('post-thumbnails');
  add_theme_support('custom-logo', ['height' => 60, 'width' => 220, 'flex-width' => true, 'flex-height' => true]);
  add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script']);
  add_theme_support('responsive-embeds');
  add_theme_support('align-wide');
  add_theme_support('editor-styles');
  add_theme_support('automatic-feed-links');
  add_image_size('tf-card', 640, 400, true);
  add_image_size('tf-hero', 1200, 720, true);
  add_image_size('tf-thumb', 240, 180, true);
  register_nav_menus([
    'primary'  => 'Header Menu',
    'trending' => 'Trending Topics bar',
    'footer-1' => 'Footer column 1',
    'footer-2' => 'Footer column 2',
    'footer-3' => 'Footer column 3',
    'footer-4' => 'Footer column 4',
  ]);
});

add_action('wp_enqueue_scripts', function () {
  wp_enqueue_style('tf-fonts', 'https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700;9..144,800&family=Inter:wght@400;500;600;700&display=swap', [], null);
  wp_enqueue_style('tf-main', get_template_directory_uri() . '/assets/css/main.css', [], TF_VER);
  wp_enqueue_script('tf-main', get_template_directory_uri() . '/assets/js/main.js', [], TF_VER, ['in_footer' => true, 'strategy' => 'defer']);
  if (is_singular() && comments_open() && get_option('thread_comments')) wp_enqueue_script('comment-reply');
});
add_filter('wp_resource_hints', function ($urls, $rel) {
  if ($rel === 'preconnect') { $urls[] = 'https://fonts.googleapis.com'; $urls[] = ['href' => 'https://fonts.gstatic.com', 'crossorigin']; }
  return $urls;
}, 10, 2);

// Set the saved colour theme before paint to avoid a flash.
add_action('wp_head', function () {
  echo "<script>try{var t=localStorage.getItem('tf-theme');if(t)document.documentElement.dataset.theme=t;}catch(e){}</script>\n";
}, 1);

add_action('widgets_init', function () {
  register_sidebar(['name' => 'Post Sidebar', 'id' => 'sidebar', 'before_widget' => '<div class="widget %2$s">', 'after_widget' => '</div>', 'before_title' => '<h3 class="widget-title">', 'after_title' => '</h3>']);
});

/* ---------- Helpers ---------- */
function tf_mod($key) { return get_theme_mod($key, tf_defaults()[$key] ?? ''); }

function tf_read_time($post = null) {
  $post = get_post($post);
  $words = str_word_count(wp_strip_all_tags($post ? $post->post_content : ''));
  return max(1, (int)ceil($words / 220));
}
function tf_primary_cat($post = null) {
  $post = get_post($post);
  if (function_exists('rank_math') || class_exists('RankMath')) {
    $id = (int)get_post_meta($post->ID, 'rank_math_primary_category', true);
    if ($id && ($t = get_term($id)) && !is_wp_error($t)) return $t;
  }
  $id = (int)get_post_meta($post->ID, '_yoast_wpseo_primary_category', true);
  if ($id && ($t = get_term($id)) && !is_wp_error($t)) return $t;
  $c = get_the_category($post->ID);
  return $c ? $c[0] : null;
}
function tf_cat_label($post = null, $class = 'tag') {
  $c = tf_primary_cat($post); if (!$c) return '';
  return '<a class="' . esc_attr($class) . '" href="' . esc_url(get_category_link($c)) . '">' . esc_html($c->name) . '</a>';
}
function tf_thumb($post, $size = 'tf-card', $class = '') {
  if (has_post_thumbnail($post)) return get_the_post_thumbnail($post, $size, ['class' => $class, 'loading' => 'lazy', 'alt' => esc_attr(get_the_title($post))]);
  return '<span class="ph ' . esc_attr($class) . '">' . tf_icon('doc', 34) . '</span>';
}
function tf_meta($post, $author = false) {
  $out = '<div class="meta">';
  if ($author) $out .= '<span>' . get_avatar(get_post_field('post_author', $post), 24, '', '', ['class' => 'av']) . esc_html(get_the_author_meta('display_name', get_post_field('post_author', $post))) . '</span>';
  $out .= '<span>' . tf_icon('calendar', 14) . esc_html(get_the_date('M j, Y', $post)) . '</span>';
  $out .= '<span>' . tf_icon('clock', 14) . tf_read_time($post) . ' min read</span>';
  return $out . '</div>';
}
function tf_cat_icon($term) { return get_term_meta($term->term_id, 'tf_icon', true) ?: 'grid'; }
function tf_cat_image($term, $size = 'tf-card') {
  $id = (int)get_term_meta($term->term_id, 'tf_image', true);
  if ($id) return wp_get_attachment_image_url($id, $size);
  $p = get_posts(['category' => $term->term_id, 'numberposts' => 1, 'meta_key' => '_thumbnail_id']);
  return $p ? get_the_post_thumbnail_url($p[0], $size) : '';
}
function tf_home_cats($limit = 7) {
  $cats = get_terms(['taxonomy' => 'category', 'hide_empty' => false, 'meta_key' => 'tf_show', 'meta_value' => '1']);
  if (is_wp_error($cats) || !$cats) $cats = get_categories(['orderby' => 'count', 'order' => 'DESC', 'number' => $limit, 'exclude' => [get_option('default_category')]]);
  usort($cats, fn($a, $b) => (int)get_term_meta($a->term_id, 'tf_order', true) <=> (int)get_term_meta($b->term_id, 'tf_order', true));
  return array_slice($cats, 0, $limit);
}
function tf_flagged($flag, $n) {
  $p = get_posts(['numberposts' => $n, 'meta_key' => $flag, 'meta_value' => '1', 'ignore_sticky_posts' => true]);
  return $p ?: get_posts(['numberposts' => $n]);
}
function tf_popular($n = 5, $days = 7) {
  $p = get_posts(['numberposts' => $n, 'meta_key' => 'tf_views_week', 'orderby' => 'meta_value_num', 'order' => 'DESC',
    'date_query' => [['after' => '90 days ago']]]);
  return $p ?: get_posts(['numberposts' => $n, 'orderby' => 'comment_count']);
}
function tf_section_head($eyebrow, $title, $link = '', $link_text = 'View All') {
  echo '<div class="sec-head"><div>';
  if ($eyebrow) echo '<span class="eyebrow">' . esc_html($eyebrow) . '</span>';
  echo '<h2 class="sec-title">' . esc_html($title) . '</h2></div>';
  if ($link) echo '<a class="more" href="' . esc_url($link) . '">' . esc_html($link_text) . tf_icon('arrow', 16) . '</a>';
  echo '</div>';
}

/* ---------- View counter (powers "Popular This Week") ---------- */
add_action('wp', function () {
  if (!is_singular('post') || is_user_logged_in() || is_preview()) return;
  $id = get_queried_object_id(); $week = gmdate('oW');
  if (get_post_meta($id, 'tf_views_wk', true) !== $week) { update_post_meta($id, 'tf_views_wk', $week); update_post_meta($id, 'tf_views_week', 0); }
  update_post_meta($id, 'tf_views_week', (int)get_post_meta($id, 'tf_views_week', true) + 1);
  update_post_meta($id, 'tf_views', (int)get_post_meta($id, 'tf_views', true) + 1);
});

/* ---------- Newsletter (stores emails; export from Tools → Subscribers) ---------- */
add_action('admin_post_nopriv_tf_subscribe', 'tf_subscribe'); add_action('admin_post_tf_subscribe', 'tf_subscribe');
function tf_subscribe() {
  $back = wp_get_referer() ?: home_url('/');
  if (!wp_verify_nonce($_POST['_tfn'] ?? '', 'tf_sub') || !empty($_POST['website'])) { wp_safe_redirect(add_query_arg('sub', 'err', $back)); exit; }
  $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
  if (!is_email($email)) { wp_safe_redirect(add_query_arg('sub', 'err', $back) . '#newsletter'); exit; }
  $list = get_option('tf_subscribers', []); $list[strtolower($email)] = current_time('mysql');
  update_option('tf_subscribers', $list, false);
  wp_safe_redirect(add_query_arg('sub', 'ok', $back) . '#newsletter'); exit;
}
add_action('admin_menu', function () {
  add_management_page('Subscribers', 'Subscribers', 'manage_options', 'tf-subscribers', function () {
    $list = get_option('tf_subscribers', []);
    echo '<div class="wrap"><h1>Newsletter Subscribers (' . count($list) . ')</h1><p><a class="button" href="' . esc_url(wp_nonce_url(admin_url('admin-post.php?action=tf_export'), 'tf_export')) . '">Download CSV</a></p><table class="widefat striped"><tr><th>Email</th><th>Date</th></tr>';
    foreach (array_reverse($list, true) as $e => $d) echo '<tr><td>' . esc_html($e) . '</td><td>' . esc_html($d) . '</td></tr>';
    echo '</table></div>';
  });
});
add_action('admin_post_tf_export', function () {
  if (!current_user_can('manage_options') || !check_admin_referer('tf_export')) wp_die('No');
  header('Content-Type: text/csv'); header('Content-Disposition: attachment; filename=subscribers.csv');
  $o = fopen('php://output', 'w'); fputcsv($o, ['email', 'date']);
  foreach (get_option('tf_subscribers', []) as $e => $d) fputcsv($o, [$e, $d]); exit;
});

/* ---------- Affiliate product box: [product rank="1" name="" image="" score="9.5" badge="" link=""]Feature | Feature[/product] ---------- */
add_shortcode('product', function ($a, $content = '') {
  $a = shortcode_atts(['rank' => '', 'name' => '', 'image' => '', 'score' => '', 'link' => '#', 'badge' => '', 'store' => 'Amazon', 'button' => 'Check Price'], $a);
  $f = array_filter(array_map('trim', preg_split('/\||\n/', wp_strip_all_tags($content))));
  $rel = 'nofollow sponsored noopener';
  $h = '<div class="pbox' . ($a['rank'] === '1' ? ' top' : '') . '">';
  if ($a['rank']) $h .= '<span class="prank">' . esc_html($a['rank']) . '</span>';
  $h .= '<a class="pimg" href="' . esc_url($a['link']) . '" rel="' . $rel . '" target="_blank">' . ($a['image'] ? '<img src="' . esc_url($a['image']) . '" alt="' . esc_attr($a['name']) . '" loading="lazy">' : '') . '</a>';
  $h .= '<div class="pinfo">' . ($a['badge'] ? '<span class="pbadge">' . esc_html($a['badge']) . '</span>' : '') . '<h3>' . esc_html($a['name']) . '</h3>';
  if ($f) { $h .= '<ul>'; foreach ($f as $x) $h .= '<li>' . esc_html($x) . '</li>'; $h .= '</ul>'; }
  $h .= '</div><div class="pcta">' . ($a['score'] !== '' ? '<div class="pscore">' . esc_html($a['score']) . '<small>Our Score</small></div>' : '');
  $h .= '<a class="btn" href="' . esc_url($a['link']) . '" rel="' . $rel . '" target="_blank">' . esc_html($a['button']) . '</a><small class="pstore">on ' . esc_html($a['store']) . '</small></div></div>';
  return $h;
});

// Excerpt tweaks
add_filter('excerpt_length', fn() => 22);
add_filter('excerpt_more', fn() => '…');
