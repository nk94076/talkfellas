<?php
function head(string $title = '', string $desc = '', string $image = '', string $canonical = ''): void {
  $full = $title ? "$title | " . SITE_NAME : SITE_NAME . ' – ' . SITE_TAGLINE;
  $desc = $desc ?: SITE_TAGLINE; $canonical = SITE_URL . ($canonical ?: strtok($_SERVER['REQUEST_URI'], '?'));
  $cats = array_filter(categories(), fn($c) => $c['n'] > 0); ?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($full) ?></title><meta name="description" content="<?= e($desc) ?>"><link rel="canonical" href="<?= e($canonical) ?>">
<meta property="og:title" content="<?= e($full) ?>"><meta property="og:description" content="<?= e($desc) ?>"><meta property="og:url" content="<?= e($canonical) ?>"><meta property="og:type" content="website">
<?php if ($image): ?><meta property="og:image" content="<?= e(str_starts_with($image, 'http') ? $image : SITE_URL . $image) ?>"><?php endif; ?>
<meta name="twitter:card" content="summary_large_image"><link rel="icon" href="/assets/favicon.svg"><link rel="stylesheet" href="/assets/style.css?v=1">
</head><body>
<div class="topbar">We independently research & rank products. We may earn a commission when you buy through our links.</div>
<header class="site-header"><div class="wrap hdr">
  <a class="logo" href="/">Talk<span>Fellas</span></a>
  <button class="menu-btn" aria-label="Menu" onclick="document.body.classList.toggle('nav-open')">☰</button>
  <nav class="main-nav"><a href="/">Home</a><?php foreach (array_slice($cats, 0, 6) as $c): ?><a href="/category/<?= e($c['slug']) ?>"><?= e($c['name']) ?></a><?php endforeach; ?></nav>
  <form class="search" action="/search"><input type="search" name="q" placeholder="Search top 10 lists…" value="<?= e($_GET['q'] ?? '') ?>" aria-label="Search"><button aria-label="Search">⌕</button></form>
</div></header><main>
<?php }
function foot(): void { ?>
</main><footer class="site-footer"><div class="wrap">
  <div class="foot-grid">
    <div><a class="logo light" href="/">Talk<span>Fellas</span></a><p>Expert-researched top 10 lists and buying guides to help you shop smarter.</p></div>
    <div><h4>Categories</h4><?php foreach (array_slice(array_filter(categories(), fn($c) => $c['n'] > 0), 0, 8) as $c): ?><a href="/category/<?= e($c['slug']) ?>"><?= e($c['name']) ?></a><?php endforeach; ?></div>
    <div><h4>Company</h4><a href="/about">About Us</a><a href="/contact">Contact</a><a href="/affiliate-disclosure">Affiliate Disclosure</a><a href="/privacy-policy">Privacy Policy</a></div>
  </div>
  <p class="copy">© <?= date('Y') ?> <?= e(SITE_NAME) ?>. As an Amazon Associate we earn from qualifying purchases.</p>
</div></footer></body></html>
<?php }
function card(array $p): void { ?>
  <article class="card">
    <a class="thumb" href="/<?= e($p['slug']) ?>"><?php if ($p['image']): ?><img src="<?= e($p['image']) ?>" alt="<?= e($p['title']) ?>" loading="lazy"><?php else: ?><span class="ph">TOP 10</span><?php endif; ?></a>
    <div class="body">
      <?php if ($p['cat_slug']): ?><a class="cat-label" href="/category/<?= e($p['cat_slug']) ?>"><?= e($p['cat_name']) ?></a><?php endif; ?>
      <h3><a href="/<?= e($p['slug']) ?>"><?= e($p['title']) ?></a></h3>
      <div class="meta">Updated <?= e(nice_date($p['updated_at'])) ?></div>
    </div>
  </article>
<?php }
function grid(array $posts): void { echo '<div class="grid">'; foreach ($posts as $p) card($p); echo '</div>'; }
function pager(int $page, int $total, int $per, string $base): void {
  $pages = (int)ceil($total / $per); if ($pages < 2) return;
  $sep = str_contains($base, '?') ? '&' : '?';
  echo '<nav class="pager">';
  for ($i = 1; $i <= $pages; $i++) echo $i === $page ? "<span class=\"cur\">$i</span>" : '<a href="' . e($base . $sep . 'page=' . $i) . "\">$i</a>";
  echo '</nav>';
}
