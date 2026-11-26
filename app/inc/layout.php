<?php
/** $seo: title, desc, image, canonical, type, noindex, schema(array), full_title(bool) */
function head(array $seo = []): void {
  $name = s('site_name'); $sep = s('seo_title_sep');
  $title = !empty($seo['full_title']) ? $seo['title'] : (($seo['title'] ?? '') ? $seo['title'] . " $sep $name" : s('seo_home_title'));
  $desc = $seo['desc'] ?? '' ?: s('seo_home_desc');
  $path = strtok($_SERVER['REQUEST_URI'], '?');
  $canonical = ($seo['canonical'] ?? '') ?: SITE_URL . ($path === '/' ? '/' : rtrim($path, '/'));
  $img = abs_url(($seo['image'] ?? '') ?: s('seo_og_image'));
  $noindex = s('seo_noindex_site') === '1' || !empty($seo['noindex']);
  $schema = $seo['schema'] ?? [];
  $schema[] = ['@context' => 'https://schema.org', '@type' => 'WebSite', 'name' => $name, 'url' => SITE_URL . '/',
    'potentialAction' => ['@type' => 'SearchAction', 'target' => SITE_URL . '/search?q={q}', 'query-input' => 'required name=q']];
  $schema[] = ['@context' => 'https://schema.org', '@type' => 'Organization', 'name' => s('seo_org_name') ?: $name, 'url' => SITE_URL . '/',
    'logo' => abs_url(s('seo_org_logo') ?: s('logo_image')) ?: null,
    'sameAs' => array_values(array_filter([s('social_x'), s('social_facebook'), s('social_instagram'), s('social_youtube'), s('social_linkedin')]))];
  ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?></title>
<meta name="description" content="<?= e($desc) ?>">
<link rel="canonical" href="<?= e($canonical) ?>">
<meta name="robots" content="<?= $noindex ? 'noindex, nofollow' : 'index, follow, max-image-preview:large, max-snippet:-1' ?>">
<meta property="og:site_name" content="<?= e($name) ?>"><meta property="og:type" content="<?= e($seo['type'] ?? 'website') ?>">
<meta property="og:title" content="<?= e($title) ?>"><meta property="og:description" content="<?= e($desc) ?>"><meta property="og:url" content="<?= e($canonical) ?>">
<?php if ($img): ?><meta property="og:image" content="<?= e($img) ?>"><meta name="twitter:image" content="<?= e($img) ?>"><?php endif; ?>
<meta name="twitter:card" content="summary_large_image"><?php if (s('seo_twitter')): ?><meta name="twitter:site" content="<?= e(s('seo_twitter')) ?>"><?php endif; ?>
<?php if (!empty($seo['published'])): ?><meta property="article:published_time" content="<?= e($seo['published']) ?>"><meta property="article:modified_time" content="<?= e($seo['modified']) ?>"><?php endif; ?>
<?php if (s('seo_google_verify')): ?><meta name="google-site-verification" content="<?= e(s('seo_google_verify')) ?>"><?php endif; ?>
<?php if (s('seo_bing_verify')): ?><meta name="msvalidate.01" content="<?= e(s('seo_bing_verify')) ?>"><?php endif; ?>
<link rel="icon" href="<?= e(s('favicon') ?: '/assets/favicon.svg') ?>">
<link rel="alternate" type="application/rss+xml" title="<?= e($name) ?>" href="/feed.xml">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700;9..144,800&family=Inter:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="/assets/main.css?v=4">
<style>:root{--brand:<?= e(preg_match('/^#[0-9a-f]{3,8}$/i', s('brand_color')) ? s('brand_color') : '#f26522') ?>;--navy:<?= e(preg_match('/^#[0-9a-f]{3,8}$/i', s('dark_color')) ? s('dark_color') : '#13213c') ?>}</style>
<script>try{var t=localStorage.getItem('tf-theme');if(t)document.documentElement.dataset.theme=t;}catch(e){}</script>
<?php foreach ($schema as $sc): ?><script type="application/ld+json"><?= json_encode(array_filter($sc, fn($v) => $v !== null && $v !== []), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
<?php endforeach; ?>
<script>window.TF=<?= json_encode(['ga' => s('seo_ga_id') && !is_staff() ? s('seo_ga_id') : '', 'staff' => is_staff(), 'track' => s('track_clicks') === '1', 'post' => (int)($seo['post_id'] ?? 0), 'title' => $seo['title'] ?? ''], JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) ?>;</script>
<?php if (s('seo_ga_id') && !is_staff()): $ga = e(s('seo_ga_id')); ?><script async src="https://www.googletagmanager.com/gtag/js?id=<?= $ga ?>"></script><script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments)}gtag('js',new Date());gtag('config','<?= $ga ?>');</script><?php endif; ?>
<?= is_staff() ? '' : s('head_code') ?>
</head>
<body>
<a class="skip" href="#main">Skip to content</a>
<header class="site-header">
  <div class="container hdr">
    <a class="brand" href="/" rel="home">
      <?php if (s('logo_image')): ?><img class="brand-logo" src="<?= e(s('logo_image')) ?>" alt="<?= e($name) ?>"><?php else: ?>
      <span class="brand-mark"><?= tf_icon('users', 20) ?></span><span class="brand-text"><b><?= e($name) ?></b><small><?= e(s('logo_tagline')) ?></small></span><?php endif; ?>
    </a>
    <nav class="main-nav" id="main-nav" aria-label="Main"><ul class="menu">
      <?php foreach (links(s('header_menu')) as [$l, $u]): $on = rtrim($u, '/') === rtrim($path, '/') || ($u !== '/' && str_starts_with($path, rtrim($u, '/') . '/')); ?>
        <li class="<?= $on ? 'current-menu-item' : '' ?>"><a href="<?= e(safe_url($u)) ?>"><?= e($l) ?></a></li>
      <?php endforeach; ?>
      <?php if ($hc = home_cats(12)): ?><li class="menu-item-has-children"><a href="/blog">Categories</a><ul class="sub-menu"><?php foreach ($hc as $c): ?><li><a href="/category/<?= e($c['slug']) ?>"><?= e($c['name']) ?></a></li><?php endforeach; ?></ul></li><?php endif; ?>
    </ul></nav>
    <div class="hdr-actions">
      <button class="icon-btn" id="theme-toggle" aria-label="Toggle dark mode"><?= tf_icon('moon', 20, 'i-moon') . tf_icon('sun', 20, 'i-sun') ?></button>
      <button class="icon-btn" id="search-toggle" aria-label="Search" aria-expanded="false"><?= tf_icon('search', 20) ?></button>
      <button class="icon-btn menu-toggle" id="menu-toggle" aria-label="Menu" aria-controls="main-nav" aria-expanded="false"><?= tf_icon('menu', 22) ?></button>
    </div>
  </div>
  <div class="search-panel" id="search-panel" hidden><div class="container"><?php search_form(); ?></div></div>
</header>
<main id="main">
<?php }

function search_form(): void { ?>
  <form role="search" method="get" class="search-form" action="/search"><label class="screen-reader-text" for="q<?= $u = random_int(1, 99999) ?>">Search</label>
  <input type="search" id="q<?= $u ?>" name="q" placeholder="Search articles, reviews, guides…" value="<?= e($_GET['q'] ?? '') ?>"><button class="btn"><?= tf_icon('search', 18) ?><span>Search</span></button></form>
<?php }

function foot(): void { ?>
</main>
<footer class="site-footer">
  <div class="container foot-grid">
    <div class="foot-about">
      <a class="brand light" href="/"><span class="brand-text"><b><?= e(s('site_name')) ?></b><small><?= e(s('logo_tagline')) ?></small></span></a>
      <p><?= e(s('footer_about')) ?></p>
      <div class="social"><?php foreach (['social_x' => 'x-social', 'social_facebook' => 'facebook', 'social_instagram' => 'instagram', 'social_youtube' => 'youtube', 'social_linkedin' => 'linkedin'] as $k => $i) if (s($k)) echo '<a href="' . e(safe_url(s($k))) . '" target="_blank" rel="noopener" aria-label="' . e($i) . '">' . tf_icon($i, 18) . '</a>'; ?></div>
    </div>
    <?php for ($i = 1; $i <= 4; $i++): $ls = links(s("footer{$i}_links")); if (!$ls) continue; ?>
      <div class="foot-col"><h4><?= e(s("footer{$i}_title")) ?></h4><ul><?php foreach ($ls as [$l, $u]): ?><li><a href="<?= e(safe_url($u)) ?>"><?= e($l) ?></a></li><?php endforeach; ?></ul></div>
    <?php endfor; ?>
  </div>
  <div class="container foot-copy"><?= e(str_replace('{year}', date('Y'), s('footer_copy'))) ?></div>
</footer>
<script src="/assets/main.js?v=4" defer></script>
<?= is_staff() ? '' : s('footer_code') ?>
</body></html>
<?php }
