<?php
require_once __DIR__ . '/db.php';
function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function esc_attr($s): string { return e($s); }
function slugify(string $s): string { $s = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $s), '-')); return $s ?: 'item-' . time(); }
function now(): string { return date('Y-m-d H:i:s'); }
function nice_date($d, $f = 'M j, Y'): string { return date($f, strtotime($d)); }
function safe_url($u): string { $u = trim((string)$u); return preg_match('#^(https?://|/|\#|mailto:)#i', $u) ? $u : '#'; }
function abs_url($u): string { return $u === '' ? '' : (preg_match('#^https?://#', $u) ? $u : SITE_URL . '/' . ltrim($u, '/')); }
require_once __DIR__ . '/icons.php';

/* ---------- Settings (all editable in Admin → Settings) ---------- */
function defaults(): array {
  return [
    'site_name' => 'Talk Fellas', 'logo_tagline' => 'IDEAS • GUIDES • REVIEWS', 'logo_image' => '', 'favicon' => '',
    'brand_color' => '#f26522', 'dark_color' => '#13213c',
    'header_menu' => "Home | /\nReviews | /category/reviews\nHow To | /category/how-to\nTop 10 | /category/top-10\nAbout | /about\nContact | /contact",
    'hero_eyebrow' => 'DISCOVER • LEARN • DECIDE', 'hero_title' => 'Honest Reviews, Practical Guides &', 'hero_highlight' => 'Real-Life Ideas',
    'hero_text' => 'In-depth articles, unbiased reviews and easy guides on tech, travel, lifestyle, wellness and more.',
    'hero_btn1' => 'Explore Articles', 'hero_btn1_url' => '/blog', 'hero_btn2' => 'Read Reviews', 'hero_btn2_url' => '/category/reviews',
    'hero_trust' => 'Trusted by <b>50K+</b> readers', 'hero_avatars' => '', 'hero_slides' => '3',
    'show_catbar' => '1', 'show_trending' => '1', 'trending_title' => 'Trending Topics:',
    'trending_links' => "AI Tools in 2026 | /search?q=ai+tools\nBudget Travel | /search?q=budget+travel\nProductivity Hacks | /search?q=productivity\nHealthy Habits | /search?q=habits\nBest Gadgets | /search?q=gadgets",
    'trending_more' => 'View All Trends', 'trending_more_url' => '/blog',
    'show_latest' => '1', 'latest_eyebrow' => 'THE LATEST', 'latest_title' => 'Latest Stories', 'popular_title' => 'Popular This Week',
    'show_promo' => '1', 'promo_title' => 'In-Depth Reviews You Can Trust', 'promo_text' => 'Real testing, honest opinions and clear recommendations.',
    'promo_btn' => 'Read Reviews', 'promo_btn_url' => '/category/reviews', 'promo_image' => '',
    'promo_point1' => 'Unbiased & Honest', 'promo_point2' => 'Expert Insights', 'promo_point3' => 'Real-World Testing',
    'show_cats' => '1', 'cats_title' => 'Explore by Category',
    'show_picks' => '1', 'picks_title' => "Editor's Picks", 'picks_text' => 'Handpicked articles, guides and recommendations from our team.', 'picks_btn' => 'Explore Picks', 'picks_btn_url' => '/blog',
    'show_howto' => '1', 'howto_title' => 'How-To Guides', 'howto_text' => 'Step-by-step tutorials to solve everyday problems and get things done easily.', 'howto_cat' => '',
    'show_news' => '1', 'news_title' => 'Join the Talk Fellas Community', 'news_text' => 'Get the latest articles, reviews and practical guides delivered to your inbox every week.', 'news_btn' => 'Subscribe Now', 'news_action' => '',
    'show_stats' => '1', 'stat1_num' => '50K+', 'stat1_label' => 'Happy Readers', 'stat2_num' => '1,000+', 'stat2_label' => 'Published Articles',
    'stat3_num' => '4.8/5', 'stat3_label' => 'Reader Rating', 'stat4_num' => '25+', 'stat4_label' => 'Topic Categories',
    'footer_about' => 'A modern blog for practical guides, honest reviews and insightful articles on technology, travel, lifestyle and more.',
    'footer1_title' => 'Explore', 'footer1_links' => "Home | /\nLatest Articles | /blog\nReviews | /category/reviews",
    'footer2_title' => 'Categories', 'footer2_links' => '{categories}',
    'footer3_title' => 'Company', 'footer3_links' => "About Us | /about\nContact Us | /contact\nAffiliate Disclosure | /affiliate-disclosure",
    'footer4_title' => 'Legal', 'footer4_links' => "Privacy Policy | /privacy-policy\nTerms of Use | /terms\nSitemap | /sitemap.xml",
    'footer_copy' => '© {year} Talk Fellas. All rights reserved. As an Amazon Associate we earn from qualifying purchases.',
    'social_x' => '', 'social_facebook' => '', 'social_instagram' => '', 'social_youtube' => '', 'social_linkedin' => '',
    'disclosure' => 'Some links in this article are affiliate links. We may earn a commission at no extra cost to you.',
    // SEO
    'seo_home_title' => 'Talk Fellas – Honest Reviews, Practical Guides & Real-Life Ideas',
    'seo_home_desc' => 'In-depth articles, unbiased reviews and easy guides on tech, travel, lifestyle, wellness and more.',
    'seo_title_sep' => '|', 'seo_og_image' => '', 'seo_twitter' => '', 'seo_noindex_site' => '0',
    'seo_google_verify' => '', 'seo_bing_verify' => '', 'seo_ga_id' => '', 'head_code' => '', 'footer_code' => '',
    'seo_org_name' => 'Talk Fellas', 'seo_org_logo' => '',
    'robots_extra' => '',
    'ai_api_key' => '', 'ai_model' => 'claude-sonnet-5-5', 'ai_words' => '1500', 'ai_language' => 'English', 'ai_tone' => 'friendly, helpful and trustworthy',
    'cta_eyebrow' => 'READY TO EXPLORE?', 'track_clicks' => '1',
  ];
}
function s(string $k): string {
  static $cache;
  if ($cache === null) $cache = db()->query('SELECT k, v FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
  return (string)($cache[$k] ?? defaults()[$k] ?? '');
}
function links(string $text): array {
  if (trim($text) === '{categories}') return array_map(fn($c) => [$c['name'], '/category/' . $c['slug']], array_slice(home_cats(), 0, 6));
  $out = [];
  foreach (preg_split('/\R/', $text) as $line) if (str_contains($line, '|')) { [$l, $u] = array_map('trim', explode('|', $line, 2)); if ($l !== '') $out[] = [$l, $u]; }
  return $out;
}

/* ---------- Content queries ---------- */
const POST_SELECT = "SELECT p.*, c.name cat_name, c.slug cat_slug, u.name author_name, u.avatar author_avatar FROM posts p LEFT JOIN categories c ON c.id=p.category_id LEFT JOIN users u ON u.id=p.author_id";
function posts(string $where = '1', array $args = [], int $limit = 12, int $offset = 0, string $order = 'p.published_at DESC'): array {
  $st = db()->prepare(POST_SELECT . " WHERE p.status='published' AND p.type='post' AND p.published_at <= ? AND ($where) ORDER BY $order LIMIT $limit OFFSET $offset");
  $st->execute(array_merge([now()], $args)); return $st->fetchAll();
}
function count_posts(string $where = '1', array $args = []): int {
  $st = db()->prepare("SELECT COUNT(*) FROM posts p WHERE p.status='published' AND p.type='post' AND p.published_at <= ? AND ($where)"); $st->execute(array_merge([now()], $args)); return (int)$st->fetchColumn();
}
function exclude(array $ids): string { $ids = array_map('intval', $ids) ?: [0]; return 'p.id NOT IN (' . implode(',', $ids) . ')'; }
function categories(): array {
  static $c; return $c ??= db()->query("SELECT c.*, (SELECT COUNT(*) FROM posts p WHERE p.category_id=c.id AND p.status='published' AND p.type='post') n FROM categories c ORDER BY sort, name")->fetchAll();
}
function home_cats(int $limit = 7): array { return array_slice(array_values(array_filter(categories(), fn($c) => $c['show_home'])), 0, $limit); }
function cat_image(array $c): string {
  if ($c['image']) return $c['image'];
  $st = db()->prepare("SELECT image FROM posts WHERE category_id=? AND status='published' AND image<>'' ORDER BY published_at DESC LIMIT 1"); $st->execute([$c['id']]);
  return (string)$st->fetchColumn();
}
function read_time(array $p): int { return max(1, (int)ceil(str_word_count(strip_tags($p['intro'] . ' ' . $p['content'])) / 220)); }
function post_url(array $p): string { return '/' . $p['slug']; }

/* ---------- Small view helpers ---------- */
function thumb(array $p, string $class = ''): string {
  return $p['image'] ? '<img src="' . e($p['image']) . '" alt="' . e($p['image_alt'] ?: $p['title']) . '" loading="lazy" class="' . e($class) . '">' : '<span class="ph ' . e($class) . '">' . tf_icon('doc', 34) . '</span>';
}
function cat_label(array $p, string $class = 'tag'): string { return $p['cat_slug'] ? '<a class="' . e($class) . '" href="/category/' . e($p['cat_slug']) . '">' . e($p['cat_name']) . '</a>' : ''; }
function avatar(string $url, int $size = 24): string {
  return $url ? '<img class="av" src="' . e($url) . '" width="' . $size . '" height="' . $size . '" alt="">' : '<span class="av av-ph" style="width:' . $size . 'px;height:' . $size . 'px">' . tf_icon('user', (int)($size * .6)) . '</span>';
}
function meta(array $p, bool $author = false): string {
  $o = '<div class="meta">';
  if ($author) $o .= '<span>' . avatar($p['author_avatar'] ?? '', 22) . e($p['author_name'] ?: 'Editorial Team') . '</span>';
  $o .= '<span>' . tf_icon('calendar', 14) . e(nice_date($p['published_at'])) . '</span><span>' . tf_icon('clock', 14) . read_time($p) . ' min read</span>';
  return $o . '</div>';
}
function sec_head(string $eyebrow, string $title, string $link = '', string $text = 'View All'): void {
  echo '<div class="sec-head"><div>' . ($eyebrow ? '<span class="eyebrow">' . e($eyebrow) . '</span>' : '') . '<h2 class="sec-title">' . e($title) . '</h2></div>';
  if ($link) echo '<a class="more" href="' . e(safe_url($link)) . '">' . e($text) . tf_icon('arrow', 16) . '</a>';
  echo '</div>';
}
function card(array $p, bool $excerpt = true): void { ?>
  <article class="card">
    <div class="card-img"><a href="<?= e(post_url($p)) ?>" tabindex="-1" aria-hidden="true"><?= thumb($p) ?></a><?= cat_label($p, 'pill on-img') ?></div>
    <div class="card-body"><h3><a href="<?= e(post_url($p)) ?>"><?= e($p['title']) ?></a></h3><?php if ($excerpt && $p['excerpt']): ?><p><?= e($p['excerpt']) ?></p><?php endif; ?><?= meta($p) ?></div>
  </article>
<?php }

// Admin HTML is trusted but still stripped of scripts/handlers as a safety net.
function clean_html($html): string {
  $html = preg_replace('#<(script|style|iframe(?![^>]*src="https://(www\.)?(youtube\.com|youtube-nocookie\.com|player\.vimeo\.com)/)|object|embed)[^>]*>.*?</\1>#is', '', (string)$html);
  $html = preg_replace('#\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $html);
  return preg_replace('#(href|src)\s*=\s*(["\'])\s*javascript:[^"\']*\2#i', '$1="#"', $html);
}
// Add id anchors to H2/H3 and return [html, toc]
function with_toc(string $html): array {
  $toc = []; $used = [];
  $html = preg_replace_callback('#<(h[23])([^>]*)>(.*?)</\1>#is', function ($m) use (&$toc, &$used) {
    $text = trim(strip_tags($m[3])); if ($text === '') return $m[0];
    $id = slugify($text); $base = $id; $i = 2; while (isset($used[$id])) $id = $base . '-' . $i++; $used[$id] = 1;
    $toc[] = [$m[1], $text, $id];
    return '<' . $m[1] . ' id="' . $id . '"' . preg_replace('#\sid="[^"]*"#', '', $m[2]) . '>' . $m[3] . '</' . $m[1] . '>';
  }, $html);
  return [$html, $toc];
}

/* ---------- Sessions / CSRF ---------- */
function start_session(): void {
  if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS'])]);
    session_name('tfadmin'); session_start();
  }
}
function csrf(): string { start_session(); return $_SESSION['csrf'] ??= bin2hex(random_bytes(16)); }
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . csrf() . '">'; }
function check_csrf(): void { start_session(); if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { http_response_code(400); exit('Invalid request. Go back and try again.'); } }

/* ---------- Staff (logged-in admins/editors) are excluded from analytics ---------- */
function staff_secret(): string {
  $k = s('staff_secret'); if ($k !== '') return $k;
  $k = bin2hex(random_bytes(16)); db()->prepare("INSERT OR REPLACE INTO settings (k, v) VALUES ('staff_secret', ?)")->execute([$k]); return $k;
}
function set_staff_cookie(int $uid): void { setcookie('tf_staff', $uid . '.' . hash_hmac('sha256', (string)$uid, staff_secret()), ['expires' => time() + 86400 * 365, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS'])]); }
function is_staff(): bool {
  static $r; if ($r !== null) return $r;
  $c = $_COOKIE['tf_staff'] ?? ''; if (!str_contains($c, '.')) return $r = false;
  [$id, $sig] = explode('.', $c, 2);
  return $r = hash_equals(hash_hmac('sha256', $id, staff_secret()), $sig);
}
/* ---------- Affiliate CTA box ---------- */
function cta_box(array $p): string {
  if (!$p['aff_url']) return '';
  $name = $p['aff_name'] ?: 'the official site';
  return '<div class="cta-box"><div><span class="eyebrow">' . e(s('cta_eyebrow')) . '</span><h3>' . e($p['aff_name'] ?: $p['title']) . '</h3>' . ($p['aff_desc'] ? '<p>' . e($p['aff_desc']) . '</p>' : '') . '</div>'
    . '<a class="btn aff" href="' . e(safe_url($p['aff_url'])) . '" rel="nofollow sponsored noopener" target="_blank" data-aff="' . e($p['aff_name']) . '">' . e($p['aff_btn'] ?: 'Visit ' . $name) . ' ' . tf_icon('arrow', 16) . '</a></div>';
}
// Replace {{aff}}text{{/aff}} markers written by the AI with the real affiliate link.
function aff_links(string $html, array $p): string {
  if (!$p['aff_url']) return preg_replace('#\{\{aff\}\}(.*?)\{\{/aff\}\}#s', '$1', $html);
  $a = '<a class="aff" href="' . e(safe_url($p['aff_url'])) . '" rel="nofollow sponsored noopener" target="_blank" data-aff="' . e($p['aff_name']) . '">';
  $html = preg_replace('#\{\{aff\}\}(.*?)\{\{/aff\}\}#s', $a . '$1</a>', $html);
  return str_replace('{{cta}}', cta_box($p), $html);
}
