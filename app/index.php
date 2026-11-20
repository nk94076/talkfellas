<?php
require __DIR__ . '/inc/helpers.php';
require __DIR__ . '/inc/layout.php';

$path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$page = max(1, (int)($_GET['page'] ?? 1)); $per = 12;

// Admin-managed 301 redirects
$st = db()->prepare('SELECT dest FROM redirects WHERE src=?'); $st->execute(['/' . $path]);
if ($to = $st->fetchColumn()) { header('Location: ' . $to, true, 301); exit; }

function not_found(): void {
  http_response_code(404); head(['title' => 'Page not found', 'noindex' => true]); ?>
  <div class="container narrow center"><div class="article"><span class="eyebrow">Error 404</span><h1 class="article-title">Page not found</h1><p>The page you’re looking for doesn’t exist. Try searching:</p><?php search_form(); ?><p style="margin-top:20px"><a class="btn" href="/">Back to Home</a></p></div></div>
  <?php foot(); exit;
}
function listing(string $eyebrow, string $title, string $where, array $args, string $base, array $seo, string $desc = ''): void {
  global $page, $per;
  $total = count_posts($where, $args); $pages = max(1, (int)ceil($total / $per));
  if ($page > $pages) not_found();
  if ($page > 1) { $seo['title'] = ($seo['title'] ?? $title) . " – Page $page"; }
  head($seo); ?>
  <section class="page-head"><div class="container"><span class="eyebrow"><?= e($eyebrow) ?></span><h1><?= e($title) ?></h1><?php if ($desc): ?><div class="desc"><p><?= e($desc) ?></p></div><?php endif; ?><?php if ($eyebrow === 'Search results') search_form(); ?></div></section>
  <div class="container">
    <?php if ($total): ?><div class="cards"><?php foreach (posts($where, $args, $per, ($page - 1) * $per) as $p) card($p); ?></div>
      <?php if ($pages > 1): $sep = str_contains($base, '?') ? '&' : '?'; ?><nav class="pagination"><div class="nav-links">
        <?php for ($i = 1; $i <= $pages; $i++) echo $i === $page ? "<span class=\"page-numbers current\">$i</span>" : '<a class="page-numbers" href="' . e($i === 1 ? $base : $base . $sep . 'page=' . $i) . "\">$i</a>"; ?>
      </div></nav><?php endif; ?>
    <?php else: ?><p class="empty">Nothing found yet.</p><?php endif; ?>
  </div>
  <?php foot(); exit;
}

/* ========== HOME ========== */
if ($path === '') {
  head(['full_title' => true, 'title' => s('seo_home_title')]);
  $used = [];
  $slides = posts('p.in_hero=1', [], max(1, (int)s('hero_slides'))) ?: posts('1', [], max(1, (int)s('hero_slides')));
  $cats = home_cats(7); ?>
<section class="hero"><div class="container hero-grid">
  <div class="hero-copy">
    <span class="eyebrow"><?= e(s('hero_eyebrow')) ?></span>
    <h1><?= e(s('hero_title')) ?> <em><?= e(s('hero_highlight')) ?></em></h1>
    <p><?= e(s('hero_text')) ?></p>
    <div class="hero-btns">
      <?php if (s('hero_btn1')): ?><a class="btn" href="<?= e(safe_url(s('hero_btn1_url'))) ?>"><?= e(s('hero_btn1')) . tf_icon('arrow', 16) ?></a><?php endif; ?>
      <?php if (s('hero_btn2')): ?><a class="btn btn-outline" href="<?= e(safe_url(s('hero_btn2_url'))) ?>"><?= e(s('hero_btn2')) ?></a><?php endif; ?>
    </div>
    <?php if (s('hero_trust')): $avs = array_filter(array_map('trim', preg_split('/\R/', s('hero_avatars')))) ?: array_filter(db()->query("SELECT avatar FROM users WHERE avatar<>'' LIMIT 3")->fetchAll(PDO::FETCH_COLUMN)); ?>
      <div class="trust"><?php if ($avs): ?><span class="avs"><?php foreach (array_slice($avs, 0, 3) as $a) echo '<img src="' . e($a) . '" width="34" height="34" alt="">'; ?></span><?php endif; ?><span><?= strip_tags(s('hero_trust'), '<b><strong><em>') ?></span></div>
    <?php endif; ?>
  </div>
  <?php if ($slides): ?>
  <div class="slider" data-slider>
    <?php foreach ($slides as $i => $p): $used[] = $p['id']; ?>
      <article class="slide<?= $i ? '' : ' is-active' ?>">
        <?= $p['image'] ? '<img src="' . e($p['image']) . '" alt="' . e($p['image_alt'] ?: $p['title']) . '"' . ($i ? ' loading="lazy"' : ' fetchpriority="high"') . '>' : '<span class="ph"></span>' ?>
        <div class="slide-body"><?= cat_label($p, 'pill') ?><h2><a href="<?= e(post_url($p)) ?>"><?= e($p['title']) ?></a></h2><?= meta($p, true) ?></div>
      </article>
    <?php endforeach; ?>
    <?php if (count($slides) > 1): ?>
      <div class="slider-nav"><button class="sl-btn" data-prev aria-label="Previous"><?= tf_icon('chev-l', 18) ?></button><button class="sl-btn" data-next aria-label="Next"><?= tf_icon('chev-r', 18) ?></button></div>
      <div class="slider-dots"><?php foreach ($slides as $i => $p) echo '<button data-dot="' . $i . '" class="' . ($i ? '' : 'on') . '" aria-label="Slide ' . ($i + 1) . '"></button>'; ?></div>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div></section>

<?php if (s('show_catbar') && $cats): ?>
<section class="container"><nav class="catbar" aria-label="Categories">
  <?php foreach ($cats as $c): ?><a href="/category/<?= e($c['slug']) ?>"><?= tf_icon($c['icon'], 28) ?><span><?= e($c['name']) ?></span></a><?php endforeach; ?>
  <a href="/blog"><?= tf_icon('grid', 28) ?><span>More</span></a>
</nav></section>
<?php endif; ?>

<?php $tl = links(s('trending_links')); if (s('show_trending') && $tl): ?>
<section class="container"><div class="trending">
  <strong><?= tf_icon('bolt', 20) . e(s('trending_title')) ?></strong>
  <ul class="chips"><?php foreach ($tl as [$l, $u]): ?><li><a href="<?= e(safe_url($u)) ?>"><?= e($l) ?></a></li><?php endforeach; ?></ul>
  <?php if (s('trending_more')): ?><a class="more" href="<?= e(safe_url(s('trending_more_url'))) ?>"><?= e(s('trending_more')) . tf_icon('arrow', 16) ?></a><?php endif; ?>
</div></section>
<?php endif; ?>

<?php if (s('show_latest')): $latest = posts(exclude($used), [], 4); ?>
<section class="container latest-grid">
  <div>
    <?php sec_head(s('latest_eyebrow'), s('latest_title'), '/blog', 'View All Articles'); ?>
    <?php if ($latest): $big = array_shift($latest); $used[] = $big['id']; ?>
    <div class="latest">
      <article class="card card-big">
        <div class="card-img"><a href="<?= e(post_url($big)) ?>" tabindex="-1" aria-hidden="true"><?= thumb($big) ?></a><?= cat_label($big, 'pill on-img') ?></div>
        <div class="card-body"><h3><a href="<?= e(post_url($big)) ?>"><?= e($big['title']) ?></a></h3><p><?= e($big['excerpt']) ?></p><?= meta($big, true) ?></div>
      </article>
      <div class="list">
        <?php foreach ($latest as $p): $used[] = $p['id']; ?>
          <article class="row"><a class="row-img" href="<?= e(post_url($p)) ?>" tabindex="-1" aria-hidden="true"><?= thumb($p) ?></a>
            <div><h3><a href="<?= e(post_url($p)) ?>"><?= e($p['title']) ?></a></h3><?= cat_label($p) . meta($p) ?></div></article>
        <?php endforeach; ?>
      </div>
    </div>
    <?php else: ?><p class="empty">No posts yet. Log in at <a href="/admin/">/admin</a> to publish your first article.</p><?php endif; ?>
  </div>
  <?php popular_box(); ?>
</section>
<?php endif; ?>

<?php if (s('show_promo')): ?>
<section class="container"><div class="promo">
  <div class="promo-copy"><h2><?= e(s('promo_title')) ?></h2><p><?= e(s('promo_text')) ?></p>
    <?php if (s('promo_btn')): ?><a class="btn sm" href="<?= e(safe_url(s('promo_btn_url'))) ?>"><?= e(s('promo_btn')) . tf_icon('arrow', 14) ?></a><?php endif; ?></div>
  <div class="promo-img"><?= s('promo_image') ? '<img src="' . e(s('promo_image')) . '" alt="" loading="lazy">' : tf_icon('camera', 120) ?></div>
  <ul class="promo-points"><?php foreach (['shield', 'bulb', 'chart'] as $n => $ic) if ($t = s('promo_point' . ($n + 1))) echo '<li>' . tf_icon($ic, 22) . e($t) . '</li>'; ?></ul>
</div></section>
<?php endif; ?>

<?php if (s('show_cats') && $cats): ?>
<section class="container">
  <?php sec_head('', s('cats_title'), '/blog', 'View All'); ?>
  <div class="cat-tiles"><?php foreach ($cats as $c): $img = cat_image($c); ?>
    <a class="cat-tile" href="/category/<?= e($c['slug']) ?>"><span class="ct-img"><?= $img ? '<img src="' . e($img) . '" alt="" loading="lazy">' : tf_icon($c['icon'], 40) ?></span><b><?= e($c['name']) ?></b></a>
  <?php endforeach; ?></div>
</section>
<?php endif; ?>

<?php if (s('show_picks')): $picks = posts('p.is_pick=1', [], 3) ?: posts(exclude($used), [], 3); if ($picks): ?>
<section class="container picks">
  <div class="picks-intro"><h2 class="sec-title"><?= e(s('picks_title')) ?></h2><p><?= e(s('picks_text')) ?></p>
    <?php if (s('picks_btn')): ?><a class="btn dark sm" href="<?= e(safe_url(s('picks_btn_url'))) ?>"><?= e(s('picks_btn')) . tf_icon('arrow', 14) ?></a><?php endif; ?></div>
  <?php foreach ($picks as $p) card($p, false); ?>
</section>
<?php endif; endif; ?>

<?php $hc = (int)s('howto_cat'); $how = $hc ? posts('p.category_id=?', [$hc], 3) : []; if (s('show_howto') && $how): $hcat = array_values(array_filter(categories(), fn($c) => $c['id'] == $hc))[0] ?? null; ?>
<section class="container"><div class="howto">
  <div class="howto-intro"><?= tf_icon('gear', 30) ?><div><h2><a href="/category/<?= e($hcat['slug'] ?? '') ?>"><?= e(s('howto_title')) ?></a></h2><p><?= e(s('howto_text')) ?></p></div></div>
  <?php foreach ($how as $i => $p): ?><a class="howto-item" href="<?= e(post_url($p)) ?>"><span class="hi-ic"><?= $p['image'] ? '<img src="' . e($p['image']) . '" alt="" loading="lazy">' : tf_icon(['laptop', 'globe', 'bulb'][$i], 34) ?></span><b><?= e($p['title']) ?> <?= tf_icon('arrow', 14) ?></b></a><?php endforeach; ?>
</div></section>
<?php endif; ?>

<?php if (s('show_news')): newsletter(); endif; ?>

<?php if (s('show_stats')): ?>
<section class="container stats"><?php foreach (['users', 'doc', 'star', 'globe'] as $n => $ic): $k = 'stat' . ($n + 1); if (!s($k . '_num')) continue; ?>
  <div class="stat"><?= tf_icon($ic, 34) ?><div><b><?= e(s($k . '_num')) ?></b><span><?= e(s($k . '_label')) ?></span></div></div>
<?php endforeach; ?></section>
<?php endif;
  foot(); exit;
}

function popular_box(): void {
  $pop = posts("p.published_at >= date('now','-120 days')", [], 5, 0, 'p.views_week DESC, p.views DESC') ?: posts('1', [], 5, 0, 'p.views DESC'); if (!$pop) return; ?>
  <aside class="popular"><h2><?= tf_icon('trophy', 26) . e(s('popular_title')) ?></h2><ol>
    <?php foreach ($pop as $i => $p): ?><li><span class="num"><?= sprintf('%02d', $i + 1) ?></span><a href="<?= e(post_url($p)) ?>"><?= e($p['title']) ?></a><span class="pop-img"><?= thumb($p) ?></span></li><?php endforeach; ?>
  </ol></aside>
<?php }
function newsletter(): void { $ext = s('news_action'); ?>
<section class="container" id="newsletter"><div class="newsletter">
  <span class="nl-ic"><?= tf_icon('send', 44) ?></span>
  <div class="nl-copy"><h2><?= e(s('news_title')) ?></h2><p><?= e(s('news_text')) ?></p></div>
  <form class="nl-form" method="post" action="<?= e($ext ? safe_url($ext) : '/subscribe') ?>">
    <?php if (!$ext): ?><input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true"><?php endif; ?>
    <label class="nl-field"><?= tf_icon('mail', 18) ?><input type="email" name="<?= $ext ? 'EMAIL' : 'email' ?>" required placeholder="Enter your email address" aria-label="Email address"></label>
    <button class="btn dark"><?= e(s('news_btn')) . tf_icon('arrow', 16) ?></button>
    <?php if (isset($_GET['sub'])) echo '<p class="nl-msg">' . ($_GET['sub'] === 'ok' ? 'Thanks for subscribing! 🎉' : 'Please enter a valid email.') . '</p>'; ?>
  </form>
</div></section>
<?php }

/* ========== NEWSLETTER SUBMIT ========== */
if ($path === 'subscribe' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = strtolower(trim($_POST['email'] ?? ''));
  $ok = empty($_POST['website']) && filter_var($email, FILTER_VALIDATE_EMAIL);
  if ($ok) db()->prepare('INSERT OR IGNORE INTO subscribers (email, created_at) VALUES (?,?)')->execute([$email, now()]);
  header('Location: /?sub=' . ($ok ? 'ok' : 'err') . '#newsletter', true, 303); exit;
}

/* ========== LISTINGS ========== */
if ($path === 'blog') listing('Blog', 'Latest Articles', '1', [], '/blog', ['title' => 'Latest Articles', 'desc' => s('seo_home_desc')]);
if (preg_match('#^category/([a-z0-9-]+)$#', $path, $m)) {
  $cat = array_values(array_filter(categories(), fn($c) => $c['slug'] === $m[1]))[0] ?? null; if (!$cat) not_found();
  listing('Category', $cat['name'], 'p.category_id=?', [$cat['id']], '/category/' . $cat['slug'],
    ['title' => $cat['meta_title'] ?: $cat['name'], 'desc' => $cat['meta_desc'] ?: ($cat['description'] ?: 'Articles, reviews and guides about ' . $cat['name'] . '.'), 'image' => $cat['image'],
     'schema' => [['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => [['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => SITE_URL . '/'], ['@type' => 'ListItem', 'position' => 2, 'name' => $cat['name']]]]]],
    $cat['description']);
}
if (preg_match('#^tag/([a-z0-9-]+)$#', $path, $m)) {
  $tag = str_replace('-', ' ', $m[1]);
  listing('Topic', ucwords($tag), "(',' || lower(replace(p.tags, ', ', ',')) || ',') LIKE ?", ['%,' . $tag . ',%'], '/tag/' . $m[1], ['title' => ucwords($tag)]);
}
if ($path === 'search') {
  $q = trim(mb_substr($_GET['q'] ?? '', 0, 100)); $like = '%' . $q . '%';
  listing('Search results', '“' . $q . '”', '(p.title LIKE ? OR p.excerpt LIKE ? OR p.tags LIKE ? OR p.content LIKE ?)', [$like, $like, $like, $like], '/search?q=' . urlencode($q), ['title' => 'Search: ' . $q, 'noindex' => true]);
}

/* ========== SITEMAP / FEED / ROBOTS ========== */
if ($path === 'sitemap.xml') {
  header('Content-Type: application/xml; charset=utf-8');
  echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';
  echo '<url><loc>' . SITE_URL . '/</loc><changefreq>daily</changefreq><priority>1.0</priority></url><url><loc>' . SITE_URL . '/blog</loc></url>';
  $st = db()->prepare("SELECT slug, image, updated_at, type FROM posts WHERE status='published' AND noindex=0 AND published_at<=? ORDER BY published_at DESC"); $st->execute([now()]);
  foreach ($st as $r) echo '<url><loc>' . e(SITE_URL . '/' . $r['slug']) . '</loc><lastmod>' . date('c', strtotime($r['updated_at'])) . '</lastmod>' . ($r['image'] ? '<image:image><image:loc>' . e(abs_url($r['image'])) . '</image:loc></image:image>' : '') . '</url>';
  foreach (categories() as $c) if ($c['n']) echo '<url><loc>' . e(SITE_URL . '/category/' . $c['slug']) . '</loc></url>';
  echo '</urlset>'; exit;
}
if ($path === 'feed.xml') {
  header('Content-Type: application/rss+xml; charset=utf-8');
  echo '<?xml version="1.0" encoding="UTF-8"?><rss version="2.0"><channel><title>' . e(s('site_name')) . '</title><link>' . SITE_URL . '/</link><description>' . e(s('seo_home_desc')) . '</description>';
  foreach (posts('1', [], 20) as $p) echo '<item><title>' . e($p['title']) . '</title><link>' . e(SITE_URL . post_url($p)) . '</link><guid>' . e(SITE_URL . post_url($p)) . '</guid><pubDate>' . date(DATE_RSS, strtotime($p['published_at'])) . '</pubDate><description>' . e($p['excerpt']) . '</description></item>';
  echo '</channel></rss>'; exit;
}
if ($path === 'robots.txt') {
  header('Content-Type: text/plain');
  echo s('seo_noindex_site') === '1' ? "User-agent: *\nDisallow: /\n" : "User-agent: *\nDisallow: /admin/\nDisallow: /search\n" . s('robots_extra') . "\nSitemap: " . SITE_URL . "/sitemap.xml\n"; exit;
}

/* ========== POST / PAGE ========== */
if (preg_match('#^[a-z0-9-]+$#', $path)) {
  $st = db()->prepare(POST_SELECT . " WHERE p.slug=? AND p.status='published' AND p.published_at<=?"); $st->execute([$path, now()]);
  $p = $st->fetch() ?: not_found();
  $seo = ['title' => $p['meta_title'] ?: $p['title'], 'desc' => $p['meta_desc'] ?: $p['excerpt'], 'image' => $p['og_image'] ?: $p['image'], 'canonical' => $p['canonical'], 'noindex' => $p['noindex']];

  if ($p['type'] === 'page') { head($seo); ?>
    <div class="container narrow"><article class="article"><h1 class="article-title"><?= e($p['title']) ?></h1>
      <?php if ($p['image']): ?><figure class="article-img"><img src="<?= e($p['image']) ?>" alt="<?= e($p['image_alt'] ?: $p['title']) ?>"></figure><?php endif; ?>
      <div class="entry"><?= clean_html($p['content']) ?></div></article></div>
  <?php foot(); exit; }

  // count views (weekly + total), skip bots
  if (!preg_match('/bot|crawl|spider|slurp|preview/i', $_SERVER['HTTP_USER_AGENT'] ?? '')) {
    $wk = date('oW');
    db()->prepare('UPDATE posts SET views=views+1, views_week=CASE WHEN views_wk=? THEN views_week+1 ELSE 1 END, views_wk=? WHERE id=?')->execute([$wk, $wk, $p['id']]);
  }
  $st = db()->prepare('SELECT * FROM products WHERE post_id=? ORDER BY rank'); $st->execute([$p['id']]); $products = $st->fetchAll();
  [$intro] = with_toc(clean_html($p['intro'])); [$content, $toc] = with_toc(clean_html($p['content']));
  $related = $p['category_id'] ? posts('p.category_id=? AND p.id<>?', [$p['category_id'], $p['id']], 3) : [];
  $url = SITE_URL . post_url($p);
  $seo['type'] = 'article'; $seo['published'] = date('c', strtotime($p['published_at'])); $seo['modified'] = date('c', strtotime($p['updated_at']));
  $seo['schema'][] = ['@context' => 'https://schema.org', '@type' => 'BlogPosting', 'headline' => $p['title'], 'description' => $seo['desc'], 'mainEntityOfPage' => $url,
    'image' => $p['image'] ? [abs_url($p['image'])] : null, 'datePublished' => $seo['published'], 'dateModified' => $seo['modified'],
    'author' => ['@type' => 'Person', 'name' => $p['author_name'] ?: 'Editorial Team'], 'publisher' => ['@type' => 'Organization', 'name' => s('site_name'), 'logo' => abs_url(s('seo_org_logo') ?: s('logo_image')) ?: null],
    'keywords' => $p['tags'] ?: null];
  $crumbs = [['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => SITE_URL . '/']];
  if ($p['cat_slug']) $crumbs[] = ['@type' => 'ListItem', 'position' => 2, 'name' => $p['cat_name'], 'item' => SITE_URL . '/category/' . $p['cat_slug']];
  $crumbs[] = ['@type' => 'ListItem', 'position' => count($crumbs) + 1, 'name' => $p['title']];
  $seo['schema'][] = ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $crumbs];
  if ($products) $seo['schema'][] = ['@context' => 'https://schema.org', '@type' => 'ItemList', 'itemListElement' => array_map(fn($x) => ['@type' => 'ListItem', 'position' => (int)$x['rank'], 'name' => $x['name']], $products)];
  head($seo); $rel = 'nofollow sponsored noopener'; ?>
  <div class="container single-wrap">
    <article class="article">
      <nav class="crumbs" aria-label="Breadcrumb"><a href="/">Home</a><?php if ($p['cat_slug']): ?> / <a href="/category/<?= e($p['cat_slug']) ?>"><?= e($p['cat_name']) ?></a><?php endif; ?></nav>
      <?= cat_label($p, 'pill') ?>
      <h1 class="article-title"><?= e($p['title']) ?></h1>
      <?php if ($p['excerpt']): ?><p class="lead"><?= e($p['excerpt']) ?></p><?php endif; ?>
      <?= meta($p, true) ?><?php if ($p['updated_at'] > $p['published_at'] && substr($p['updated_at'], 0, 10) !== substr($p['published_at'], 0, 10)): ?><div class="meta"><span>Updated <?= e(nice_date($p['updated_at'])) ?></span></div><?php endif; ?>
      <?php if ($p['image']): ?><figure class="article-img"><img src="<?= e($p['image']) ?>" alt="<?= e($p['image_alt'] ?: $p['title']) ?>" fetchpriority="high"></figure><?php endif; ?>
      <?php if ($products && s('disclosure')): ?><div class="disclosure"><?= tf_icon('shield', 16) . e(s('disclosure')) ?></div><?php endif; ?>
      <?php if (count($toc) >= 3): ?><details class="toc" open><summary>Table of Contents</summary><ol><?php foreach ($toc as [$h, $t, $id]): ?><li class="<?= $h ?>"><a href="#<?= e($id) ?>"><?= e($t) ?></a></li><?php endforeach; ?></ol></details><?php endif; ?>
      <?php if ($intro): ?><div class="entry"><?= $intro ?></div><?php endif; ?>
      <?php if ($products): ?>
        <div class="quick"><h2>Our Top Picks at a Glance</h2><?php foreach ($products as $x): ?>
          <a class="quick-row" href="#pick-<?= (int)$x['rank'] ?>"><b>#<?= (int)$x['rank'] ?></b><span><?= e($x['name']) ?><?php if ($x['badge']): ?> <em><?= e($x['badge']) ?></em><?php endif; ?></span><?php if ($x['score'] !== ''): ?><strong><?= e($x['score']) ?></strong><?php endif; ?></a>
        <?php endforeach; ?></div>
        <?php foreach ($products as $x): $f = array_filter(array_map('trim', preg_split('/\R/', $x['features']))); ?>
          <div class="pbox<?= $x['rank'] == 1 ? ' top' : '' ?>" id="pick-<?= (int)$x['rank'] ?>">
            <span class="prank"><?= (int)$x['rank'] ?></span>
            <a class="pimg" href="<?= e(safe_url($x['link'])) ?>" rel="<?= $rel ?>" target="_blank"><?php if ($x['image']): ?><img src="<?= e($x['image']) ?>" alt="<?= e($x['name']) ?>" loading="lazy"><?php endif; ?></a>
            <div class="pinfo"><?php if ($x['badge']): ?><span class="pbadge"><?= e($x['badge']) ?></span><?php endif; ?><h3><?= e($x['name']) ?></h3><?php if ($f): ?><ul><?php foreach ($f as $li): ?><li><?= e($li) ?></li><?php endforeach; ?></ul><?php endif; ?></div>
            <div class="pcta"><?php if ($x['score'] !== ''): ?><div class="pscore"><?= e($x['score']) ?><small>Our Score</small></div><?php endif; ?><a class="btn" href="<?= e(safe_url($x['link'])) ?>" rel="<?= $rel ?>" target="_blank"><?= e($x['button'] ?: 'Check Price') ?></a><small class="pstore">on <?= e($x['store']) ?></small></div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
      <?php if ($content): ?><div class="entry"><?= $content ?></div><?php endif; ?>
      <?php if ($p['tags']): ?><div class="post-tags"><?php foreach (array_filter(array_map('trim', explode(',', $p['tags']))) as $t): ?><a href="/tag/<?= e(slugify($t)) ?>">#<?= e($t) ?></a><?php endforeach; ?></div><?php endif; ?>
      <div class="share"><span>Share:</span>
        <a href="https://twitter.com/intent/tweet?url=<?= urlencode($url) ?>&text=<?= urlencode($p['title']) ?>" target="_blank" rel="noopener" aria-label="Share on X"><?= tf_icon('x-social', 16) ?></a>
        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($url) ?>" target="_blank" rel="noopener" aria-label="Share on Facebook"><?= tf_icon('facebook', 16) ?></a>
        <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode($url) ?>" target="_blank" rel="noopener" aria-label="Share on LinkedIn"><?= tf_icon('linkedin', 16) ?></a>
        <a href="https://wa.me/?text=<?= urlencode($p['title'] . ' ' . $url) ?>" target="_blank" rel="noopener" aria-label="Share on WhatsApp"><?= tf_icon('send', 16) ?></a>
      </div>
      <?php $au = db()->prepare('SELECT * FROM users WHERE id=?'); $au->execute([$p['author_id']]); if ($a = $au->fetch()): ?>
        <div class="author-box"><?= avatar($a['avatar'], 64) ?><div><b><?= e($a['name'] ?: 'Editorial Team') ?></b><p><?= e($a['bio'] ?: 'Writer at ' . s('site_name') . '.') ?></p></div></div>
      <?php endif; ?>
    </article>
    <aside class="sidebar"><?php popular_box(); ?>
      <div class="widget"><h3 class="widget-title">Categories</h3><ul class="cat-list"><?php foreach (categories() as $c) if ($c['n']): ?><li><a href="/category/<?= e($c['slug']) ?>"><?= e($c['name']) ?></a><span><?= $c['n'] ?></span></li><?php endif; ?></ul></div>
    </aside>
  </div>
  <?php if ($related): ?><section class="container"><?php sec_head('', 'You May Also Like'); ?><div class="cards"><?php foreach ($related as $r) card($r); ?></div></section><?php endif; ?>
  <?php if (s('show_news')) newsletter(); ?>
  <?php foot(); exit;
}
not_found();
