<?php
require __DIR__ . '/inc/helpers.php';
require __DIR__ . '/inc/layout.php';

$path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$page = max(1, (int)($_GET['page'] ?? 1)); $per = 12;

function not_found(): void { http_response_code(404); head('Page not found'); echo '<div class="wrap pad"><h1>Page not found</h1><p><a href="/">Go to homepage →</a></p></div>'; foot(); exit; }

// ---------- Home ----------
if ($path === '') {
  $featured = posts('p.featured=1', [], 1);
  $latest = posts('1', [], 9);
  head();
  $cats = array_filter(categories(), fn($c) => $c['n'] > 0); ?>
  <section class="hero"><div class="wrap">
    <h1><?= e(SITE_TAGLINE) ?></h1><p>We research thousands of reviews so you can pick the best — fast.</p>
    <form class="hero-search" action="/search"><input type="search" name="q" placeholder="What are you shopping for?" aria-label="Search"><button>Search</button></form>
    <div class="chips"><?php foreach ($cats as $c): ?><a href="/category/<?= e($c['slug']) ?>"><?= e($c['name']) ?></a><?php endforeach; ?></div>
  </div></section>
  <div class="wrap">
    <?php if ($featured): $f = $featured[0]; ?>
      <a class="feature" href="/<?= e($f['slug']) ?>">
        <div class="feature-img"><?php if ($f['image']): ?><img src="<?= e($f['image']) ?>" alt="<?= e($f['title']) ?>"><?php endif; ?></div>
        <div class="feature-body"><span class="cat-label">★ Editor's Pick</span><h2><?= e($f['title']) ?></h2><p><?= e($f['excerpt']) ?></p><span class="btn">Read the list →</span></div>
      </a>
    <?php endif; ?>
    <h2 class="section-title">Latest Top 10 Lists</h2>
    <?php if ($latest) grid($latest); else echo '<p class="muted">No posts yet. Log in at <a href="/admin/">/admin</a> to add your first list.</p>'; ?>
    <?php foreach (array_slice($cats, 0, 4) as $c): $ps = posts('p.category_id=?', [$c['id']], 4); if (!$ps) continue; ?>
      <div class="section-head"><h2 class="section-title"><?= e($c['name']) ?></h2><a href="/category/<?= e($c['slug']) ?>">View all →</a></div>
      <?php grid($ps); ?>
    <?php endforeach; ?>
  </div>
  <?php foot(); exit;
}

// ---------- Category ----------
if (preg_match('#^category/([a-z0-9-]+)$#', $path, $m)) {
  $st = db()->prepare('SELECT * FROM categories WHERE slug=?'); $st->execute([$m[1]]); $cat = $st->fetch() ?: not_found();
  $total = count_posts('p.category_id=?', [$cat['id']]);
  head('Best ' . $cat['name'], 'Top 10 lists and buying guides for ' . $cat['name'] . '.'); ?>
  <section class="page-hero"><div class="wrap"><span class="cat-label light">Category</span><h1><?= e($cat['name']) ?></h1><p><?= $total ?> buying guides</p></div></section>
  <div class="wrap pad"><?php grid(posts('p.category_id=?', [$cat['id']], $per, ($page - 1) * $per)); pager($page, $total, $per, '/category/' . $cat['slug']); ?></div>
  <?php foot(); exit;
}

// ---------- Search ----------
if ($path === 'search') {
  $q = trim($_GET['q'] ?? ''); $like = '%' . $q . '%';
  $w = '(p.title LIKE ? OR p.excerpt LIKE ?)';
  $total = $q === '' ? 0 : count_posts($w, [$like, $like]);
  head('Search: ' . $q); ?>
  <section class="page-hero"><div class="wrap"><h1>Results for “<?= e($q) ?>”</h1><p><?= $total ?> found</p></div></section>
  <div class="wrap pad"><?php if ($total) { grid(posts($w, [$like, $like], $per, ($page - 1) * $per)); pager($page, $total, $per, '/search?q=' . urlencode($q)); } else echo '<p>No results. Try another keyword.</p>'; ?></div>
  <?php foot(); exit;
}

// ---------- Sitemap ----------
if ($path === 'sitemap.xml') {
  header('Content-Type: application/xml; charset=utf-8');
  echo '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
  echo '<url><loc>' . SITE_URL . '/</loc></url>';
  foreach (db()->query("SELECT slug, updated_at FROM posts WHERE status='published'") as $r) echo '<url><loc>' . e(SITE_URL . '/' . $r['slug']) . '</loc><lastmod>' . substr($r['updated_at'], 0, 10) . '</lastmod></url>';
  foreach (categories() as $c) if ($c['n']) echo '<url><loc>' . e(SITE_URL . '/category/' . $c['slug']) . '</loc></url>';
  echo '</urlset>'; exit;
}

// ---------- Post / Page ----------
if (preg_match('#^[a-z0-9-]+$#', $path)) {
  $st = db()->prepare("SELECT p.*, c.name cat_name, c.slug cat_slug FROM posts p LEFT JOIN categories c ON c.id=p.category_id WHERE p.slug=? AND p.status='published'");
  $st->execute([$path]); $p = $st->fetch() ?: not_found();
  head($p['meta_title'] ?: $p['title'], $p['meta_desc'] ?: $p['excerpt'], $p['image']);

  if ($p['type'] === 'page') { ?>
    <div class="wrap narrow pad"><article class="post"><h1><?= e($p['title']) ?></h1><div class="content"><?= clean_html($p['content']) ?></div></article></div>
  <?php foot(); exit; }

  $st = db()->prepare('SELECT * FROM products WHERE post_id=? ORDER BY rank'); $st->execute([$p['id']]); $products = $st->fetchAll();
  $related = $p['category_id'] ? posts('p.category_id=? AND p.id<>?', [$p['category_id'], $p['id']], 3) : [];
  $ld = ['@context' => 'https://schema.org', '@type' => 'Article', 'headline' => $p['title'], 'description' => $p['excerpt'],
    'datePublished' => date('c', strtotime($p['created_at'])), 'dateModified' => date('c', strtotime($p['updated_at'])),
    'author' => ['@type' => 'Organization', 'name' => SITE_NAME], 'image' => $p['image'] ? SITE_URL . $p['image'] : null];
  if ($products) $ld = [$ld, ['@context' => 'https://schema.org', '@type' => 'ItemList', 'itemListElement' => array_map(fn($x) => ['@type' => 'ListItem', 'position' => (int)$x['rank'], 'name' => $x['name']], $products)]]; ?>
  <div class="wrap layout">
    <article class="post">
      <nav class="crumbs"><a href="/">Home</a><?php if ($p['cat_slug']): ?> › <a href="/category/<?= e($p['cat_slug']) ?>"><?= e($p['cat_name']) ?></a><?php endif; ?></nav>
      <h1><?= e($p['title']) ?></h1>
      <div class="meta">By <?= e(SITE_NAME) ?> Editorial Team · Updated <?= e(nice_date($p['updated_at'])) ?></div>
      <div class="disclosure">We may earn a commission when you buy through links on this page, at no extra cost to you. <a href="/affiliate-disclosure">Learn more</a></div>
      <?php if ($p['intro']): ?><div class="content"><?= clean_html($p['intro']) ?></div><?php endif; ?>

      <?php if ($products): ?>
        <div class="quick">
          <h2>Our Top Picks at a Glance</h2>
          <?php foreach ($products as $x): ?>
            <a class="quick-row" href="<?= e(safe_url($x['link'])) ?>" rel="nofollow sponsored noopener" target="_blank">
              <b>#<?= (int)$x['rank'] ?></b><span><?= e($x['name']) ?><?php if ($x['badge']): ?> <em><?= e($x['badge']) ?></em><?php endif; ?></span><strong><?= e($x['score']) ?></strong>
            </a>
          <?php endforeach; ?>
        </div>
        <?php foreach ($products as $x): $feat = array_filter(array_map('trim', explode("\n", $x['features']))); ?>
          <div class="product<?= $x['rank'] == 1 ? ' top' : '' ?>" id="pick-<?= (int)$x['rank'] ?>">
            <div class="rank"><?= (int)$x['rank'] ?></div>
            <a class="p-img" href="<?= e(safe_url($x['link'])) ?>" rel="nofollow sponsored noopener" target="_blank"><?php if ($x['image']): ?><img src="<?= e($x['image']) ?>" alt="<?= e($x['name']) ?>" loading="lazy"><?php endif; ?></a>
            <div class="p-info">
              <?php if ($x['badge']): ?><span class="badge"><?= e($x['badge']) ?></span><?php endif; ?>
              <h3><?= e($x['name']) ?></h3>
              <?php if ($feat): ?><ul><?php foreach ($feat as $f): ?><li><?= e($f) ?></li><?php endforeach; ?></ul><?php endif; ?>
            </div>
            <div class="p-cta">
              <?php if ($x['score'] !== ''): ?><div class="score"><?= e($x['score']) ?><small>Our Score</small></div><?php endif; ?>
              <a class="btn block" href="<?= e(safe_url($x['link'])) ?>" rel="nofollow sponsored noopener" target="_blank"><?= e($x['button'] ?: 'Check Price') ?> →</a>
              <div class="store">on <?= e($x['store']) ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>

      <?php if ($p['content']): ?><div class="content"><?= clean_html($p['content']) ?></div><?php endif; ?>
    </article>
    <aside class="sidebar">
      <?php $trend = posts('p.id<>?', [$p['id']], 6); if ($trend): ?><div class="widget"><h3>Trending Lists</h3><?php foreach ($trend as $r): ?><a class="mini" href="/<?= e($r['slug']) ?>"><?php if ($r['image']): ?><img src="<?= e($r['image']) ?>" alt=""><?php endif; ?><span><?= e($r['title']) ?></span></a><?php endforeach; ?></div><?php endif; ?>
      <div class="widget"><h3>Categories</h3><?php foreach (categories() as $c) if ($c['n']): ?><a class="cat-link" href="/category/<?= e($c['slug']) ?>"><?= e($c['name']) ?> <span><?= $c['n'] ?></span></a><?php endif; ?></div>
    </aside>
  </div>
  <?php if ($related): ?><div class="wrap"><h2 class="section-title">You May Also Like</h2><?php grid($related); ?></div><?php endif; ?>
  <script type="application/ld+json"><?= json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
  <?php foot(); exit;
}
not_found();
