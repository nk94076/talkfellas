<?php
require dirname(__DIR__) . '/inc/helpers.php';
start_session();
header('X-Frame-Options: DENY'); header('Cache-Control: no-store');
$a = $_GET['a'] ?? 'posts';
$hasUser = (int)db()->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0;
$flash = $_SESSION['flash'] ?? ''; unset($_SESSION['flash']);
function go(string $q, string $msg = ''): void { if ($msg) $_SESSION['flash'] = $msg; header('Location: /admin/' . ($q ? "?$q" : '')); exit; }

// ---------- First-run setup ----------
if (!$hasUser) {
  $err = '';
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf(); $u = trim($_POST['username'] ?? ''); $p = $_POST['password'] ?? '';
    if (strlen($u) < 3 || strlen($p) < 10) $err = 'Username min 3 chars, password min 10 chars.';
    else {
      db()->prepare('INSERT INTO users (username, pass_hash) VALUES (?,?)')->execute([$u, password_hash($p, PASSWORD_DEFAULT)]);
      seed_defaults(); session_regenerate_id(true); $_SESSION['uid'] = 1; go('', 'Admin account created. Welcome!');
    }
  }
  auth_page('Create admin account', 'Create Account', $err); exit;
}
// ---------- Login ----------
if ($a === 'logout') { $_SESSION = []; session_destroy(); go(''); }
if (empty($_SESSION['uid'])) {
  $err = '';
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $_SESSION['tries'] = ($_SESSION['tries'] ?? 0) + 1;
    if ($_SESSION['tries'] > 8) { sleep(3); }
    $st = db()->prepare('SELECT * FROM users WHERE username=?'); $st->execute([trim($_POST['username'] ?? '')]); $u = $st->fetch();
    if ($u && password_verify($_POST['password'] ?? '', $u['pass_hash'])) { session_regenerate_id(true); $_SESSION['uid'] = $u['id']; $_SESSION['tries'] = 0; go(''); }
    $err = 'Wrong username or password.';
  }
  auth_page('Admin Login', 'Log In', $err); exit;
}

// ---------- Image upload (AJAX) ----------
if ($a === 'upload' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  check_csrf(); header('Content-Type: application/json');
  $f = $_FILES['file'] ?? null;
  if (!$f || $f['error'] !== UPLOAD_ERR_OK) exit(json_encode(['error' => 'Upload failed']));
  if ($f['size'] > 8 * 1024 * 1024) exit(json_encode(['error' => 'Max 8 MB']));
  $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'][(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name'])] ?? null;
  if (!$ext || !getimagesize($f['tmp_name'])) exit(json_encode(['error' => 'Only JPG, PNG, WEBP, GIF images']));
  $dir = '/uploads/' . date('Y/m'); @mkdir(dirname(__DIR__) . $dir, 0755, true);
  $name = $dir . '/' . bin2hex(random_bytes(8)) . '.' . $ext;
  move_uploaded_file($f['tmp_name'], dirname(__DIR__) . $name);
  exit(json_encode(['url' => $name]));
}

// ---------- Save post ----------
if ($a === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  check_csrf(); $id = (int)($_POST['id'] ?? 0);
  $title = trim($_POST['title'] ?? ''); if ($title === '') go('a=edit&id=' . $id, 'Title is required.');
  $slug = slugify($_POST['slug'] ?: $title);
  $st = db()->prepare('SELECT COUNT(*) FROM posts WHERE slug=? AND id<>?'); $st->execute([$slug, $id]);
  if ($st->fetchColumn() || in_array($slug, ['admin', 'category', 'search', 'uploads', 'assets', 'sitemap-xml'])) $slug .= '-' . substr(bin2hex(random_bytes(2)), 0, 4);
  $f = ['type' => $_POST['type'] === 'page' ? 'page' : 'post', 'title' => $title, 'slug' => $slug, 'excerpt' => trim($_POST['excerpt'] ?? ''),
    'intro' => $_POST['intro'] ?? '', 'content' => $_POST['content'] ?? '', 'image' => trim($_POST['image'] ?? ''),
    'category_id' => ((int)($_POST['category_id'] ?? 0)) ?: null, 'status' => $_POST['status'] === 'published' ? 'published' : 'draft',
    'featured' => isset($_POST['featured']) ? 1 : 0, 'meta_title' => trim($_POST['meta_title'] ?? ''), 'meta_desc' => trim($_POST['meta_desc'] ?? ''), 'updated_at' => now()];
  $pdo = db(); $pdo->beginTransaction();
  if ($id) { $set = implode(',', array_map(fn($k) => "$k=:$k", array_keys($f))); $f['id'] = $id; $pdo->prepare("UPDATE posts SET $set WHERE id=:id")->execute($f); }
  else { $f['created_at'] = now(); $pdo->prepare('INSERT INTO posts (' . implode(',', array_keys($f)) . ') VALUES (:' . implode(',:', array_keys($f)) . ')')->execute($f); $id = (int)$pdo->lastInsertId(); }
  $pdo->prepare('DELETE FROM products WHERE post_id=?')->execute([$id]);
  $ins = $pdo->prepare('INSERT INTO products (post_id, rank, name, image, score, badge, features, link, store, button) VALUES (?,?,?,?,?,?,?,?,?,?)');
  $r = 0;
  foreach (($_POST['pr'] ?? []) as $x) {
    if (trim($x['name'] ?? '') === '') continue;
    $ins->execute([$id, ++$r, trim($x['name']), trim($x['image'] ?? ''), trim($x['score'] ?? ''), trim($x['badge'] ?? ''), trim($x['features'] ?? ''), trim($x['link'] ?? ''), trim($x['store'] ?? '') ?: 'Amazon', trim($x['button'] ?? '') ?: 'Check Price']);
  }
  $pdo->commit();
  go('a=edit&id=' . $id, 'Saved ✓ ' . ($f['status'] === 'published' ? '<a href="/' . e($slug) . '" target="_blank">View live →</a>' : '(Draft)'));
}
if ($a === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') { check_csrf(); db()->prepare('DELETE FROM posts WHERE id=?')->execute([(int)$_POST['id']]); go('a=' . ($_POST['back'] ?? 'posts'), 'Deleted.'); }

// ---------- Categories ----------
if ($a === 'cats' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  check_csrf();
  if (!empty($_POST['del'])) db()->prepare('DELETE FROM categories WHERE id=?')->execute([(int)$_POST['del']]);
  elseif (trim($_POST['name'] ?? '') !== '') {
    try { db()->prepare('INSERT INTO categories (name, slug, sort) VALUES (?,?,?)')->execute([trim($_POST['name']), slugify($_POST['name']), (int)($_POST['sort'] ?? 0)]); }
    catch (PDOException) { go('a=cats', 'Category already exists.'); }
  }
  go('a=cats', 'Categories updated.');
}
// ---------- Password ----------
if ($a === 'password' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  check_csrf(); $u = db()->query('SELECT * FROM users WHERE id=' . (int)$_SESSION['uid'])->fetch();
  if (!password_verify($_POST['old'] ?? '', $u['pass_hash'])) go('a=password', 'Current password is wrong.');
  if (strlen($_POST['new'] ?? '') < 10) go('a=password', 'New password must be at least 10 characters.');
  db()->prepare('UPDATE users SET pass_hash=? WHERE id=?')->execute([password_hash($_POST['new'], PASSWORD_DEFAULT), $u['id']]); go('a=password', 'Password changed ✓');
}

// ================= Views =================
admin_head($a, $flash);
if ($a === 'posts' || $a === 'pages') {
  $type = $a === 'pages' ? 'page' : 'post';
  $rows = db()->prepare("SELECT p.*, c.name cat FROM posts p LEFT JOIN categories c ON c.id=p.category_id WHERE p.type=? ORDER BY p.updated_at DESC"); $rows->execute([$type]); ?>
  <div class="bar"><h1><?= $type === 'page' ? 'Pages' : 'Top 10 Lists / Posts' ?></h1><a class="btn" href="?a=edit&type=<?= $type ?>">+ New <?= $type === 'page' ? 'Page' : 'Post' ?></a></div>
  <table class="list"><tr><th>Title</th><?php if ($type === 'post'): ?><th>Category</th><?php endif; ?><th>Status</th><th>Updated</th><th></th></tr>
  <?php foreach ($rows as $r): ?><tr>
    <td><a href="?a=edit&id=<?= $r['id'] ?>"><b><?= e($r['title']) ?></b></a><?= $r['featured'] ? ' ★' : '' ?><br><small>/<?= e($r['slug']) ?></small></td>
    <?php if ($type === 'post'): ?><td><?= e($r['cat'] ?? '—') ?></td><?php endif; ?>
    <td><span class="pill <?= $r['status'] ?>"><?= $r['status'] ?></span></td><td><small><?= e(nice_date($r['updated_at'])) ?></small></td>
    <td class="act"><a href="/<?= e($r['slug']) ?>" target="_blank">View</a>
      <form method="post" action="?a=delete" onsubmit="return confirm('Delete this permanently?')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>"><input type="hidden" name="back" value="<?= $a ?>"><button class="link danger">Delete</button></form></td>
  </tr><?php endforeach; ?></table>
<?php } elseif ($a === 'edit') {
  $id = (int)($_GET['id'] ?? 0); $p = ['id' => 0, 'type' => $_GET['type'] ?? 'post', 'title' => '', 'slug' => '', 'excerpt' => '', 'intro' => '', 'content' => '', 'image' => '', 'category_id' => null, 'status' => 'draft', 'featured' => 0, 'meta_title' => '', 'meta_desc' => '']; $prods = [];
  if ($id) { $st = db()->prepare('SELECT * FROM posts WHERE id=?'); $st->execute([$id]); $p = $st->fetch() ?: go('', 'Not found.'); $st = db()->prepare('SELECT * FROM products WHERE post_id=? ORDER BY rank'); $st->execute([$id]); $prods = $st->fetchAll(); }
  $isPost = $p['type'] === 'post'; ?>
  <form method="post" action="?a=save" id="editor"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $p['id'] ?>"><input type="hidden" name="type" value="<?= e($p['type']) ?>">
  <div class="bar"><h1><?= $id ? 'Edit' : 'New' ?> <?= $isPost ? 'Post' : 'Page' ?></h1><div><?php if ($id && $p['status'] === 'published'): ?><a class="btn ghost" href="/<?= e($p['slug']) ?>" target="_blank">View</a> <?php endif; ?><button class="btn">Save</button></div></div>
  <div class="cols">
    <div class="main-col">
      <label>Title<input name="title" required value="<?= e($p['title']) ?>" placeholder="10 Best Wireless Earbuds in 2026"></label>
      <label>URL slug <small>(blank = from title)</small><input name="slug" value="<?= e($p['slug']) ?>" placeholder="best-wireless-earbuds"></label>
      <?php if ($isPost): ?>
        <label>Short description <small>(shows on cards & Google)</small><textarea name="excerpt" rows="2"><?= e($p['excerpt']) ?></textarea></label>
        <label>Intro <small>(above the product list)</small></label><div class="rte" data-name="intro"><?= clean_html($p['intro']) ?></div><input type="hidden" name="intro">
        <h2 class="sub">Ranked Products <small>— drag ↕ to reorder; rank follows order</small></h2>
        <div id="products"></div>
        <button type="button" class="btn ghost" onclick="addProduct()">+ Add Product</button>
        <label style="margin-top:24px">Buying guide / content <small>(below the list)</small></label>
      <?php else: ?><label>Content</label><?php endif; ?>
      <div class="rte" data-name="content"><?= clean_html($p['content']) ?></div><input type="hidden" name="content">
    </div>
    <div class="side-col">
      <div class="box"><h3>Publish</h3>
        <label>Status<select name="status"><option value="draft">Draft</option><option value="published" <?= $p['status'] === 'published' ? 'selected' : '' ?>>Published</option></select></label>
        <?php if ($isPost): ?><label class="check"><input type="checkbox" name="featured" <?= $p['featured'] ? 'checked' : '' ?>> Feature on homepage</label><?php endif; ?>
        <button class="btn block">Save</button></div>
      <?php if ($isPost): ?>
      <div class="box"><h3>Category</h3><select name="category_id"><option value="">— None —</option><?php foreach (categories() as $c): ?><option value="<?= $c['id'] ?>" <?= $c['id'] == $p['category_id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select><small><a href="?a=cats">Manage categories</a></small></div>
      <div class="box"><h3>Featured Image</h3><div class="imgf"><input name="image" value="<?= e($p['image']) ?>" placeholder="Image URL"><button type="button" class="btn ghost sm" onclick="pick(this)">Upload</button><img class="prev" src="<?= e($p['image']) ?>" alt=""></div></div>
      <?php endif; ?>
      <div class="box"><h3>SEO</h3><label>Meta title<input name="meta_title" value="<?= e($p['meta_title']) ?>"></label><label>Meta description<textarea name="meta_desc" rows="3"><?= e($p['meta_desc']) ?></textarea></label></div>
    </div>
  </div></form>
  <template id="ptpl"><div class="prod" draggable="true">
    <div class="prod-h"><span class="drag">↕</span><b class="num"></b><button type="button" class="link danger" onclick="this.closest('.prod').remove();renum()">Remove</button></div>
    <div class="g2"><label>Product name<input data-k="name"></label><label>Affiliate link<input data-k="link" placeholder="https://amzn.to/..."></label>
    <label>Badge<input data-k="badge" placeholder="Best Overall"></label><label>Score<input data-k="score" placeholder="9.8"></label>
    <label>Store<input data-k="store" placeholder="Amazon"></label><label>Button text<input data-k="button" placeholder="Check Price"></label></div>
    <label>Image</label><div class="imgf"><input data-k="image" placeholder="Image URL"><button type="button" class="btn ghost sm" onclick="pick(this)">Upload</button><img class="prev" alt=""></div>
    <label>Key features <small>(one per line)</small><textarea data-k="features" rows="3"></textarea></label>
  </div></template>
  <script>window.PRODUCTS = <?= json_encode($prods, JSON_HEX_TAG | JSON_HEX_AMP) ?>; window.CSRF = <?= json_encode(csrf()) ?>;</script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.snow.min.css" rel="stylesheet">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.min.js"></script>
  <script src="/admin/editor.js?v=1"></script>
<?php } elseif ($a === 'cats') { ?>
  <div class="bar"><h1>Categories</h1></div>
  <form method="post" class="inline"><?= csrf_field() ?><input name="name" placeholder="New category, e.g. Electronics" required><input name="sort" type="number" placeholder="Order" style="width:90px"><button class="btn">Add</button></form>
  <table class="list"><tr><th>Name</th><th>URL</th><th>Posts</th><th>Order</th><th></th></tr>
  <?php foreach (categories() as $c): ?><tr><td><b><?= e($c['name']) ?></b></td><td><small>/category/<?= e($c['slug']) ?></small></td><td><?= $c['n'] ?></td><td><?= $c['sort'] ?></td>
    <td><form method="post" onsubmit="return confirm('Delete category? Posts stay, just uncategorised.')"><?= csrf_field() ?><input type="hidden" name="del" value="<?= $c['id'] ?>"><button class="link danger">Delete</button></form></td></tr><?php endforeach; ?></table>
<?php } elseif ($a === 'password') { ?>
  <div class="bar"><h1>Change Password</h1></div>
  <form method="post" class="narrow-form"><?= csrf_field() ?><label>Current password<input type="password" name="old" required></label><label>New password (min 10)<input type="password" name="new" minlength="10" required></label><button class="btn">Update</button></form>
<?php }
echo '</main></body></html>';

// ---------- helpers ----------
function admin_head(string $a, string $flash): void { ?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title>Admin · <?= e(SITE_NAME) ?></title><link rel="stylesheet" href="/admin/admin.css?v=1"></head><body>
<header class="ah"><a class="logo" href="/admin/">Talk<span>Fellas</span> <small>CMS</small></a>
<nav><a class="<?= in_array($a, ['posts', 'edit']) ? 'on' : '' ?>" href="/admin/">Posts</a><a class="<?= $a === 'pages' ? 'on' : '' ?>" href="?a=pages">Pages</a><a class="<?= $a === 'cats' ? 'on' : '' ?>" href="?a=cats">Categories</a><a class="<?= $a === 'password' ? 'on' : '' ?>" href="?a=password">Password</a><a href="/" target="_blank">View Site ↗</a><a href="?a=logout">Logout</a></nav></header>
<main class="am"><?php if ($flash): ?><div class="flash"><?= $flash ?></div><?php endif;
}
function auth_page(string $title, string $btn, string $err): void { ?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title><?= e($title) ?></title><link rel="stylesheet" href="/admin/admin.css?v=1"></head>
<body class="auth"><form method="post" class="auth-box"><div class="logo">Talk<span>Fellas</span></div><h1><?= e($title) ?></h1><?php if ($err): ?><div class="flash err"><?= e($err) ?></div><?php endif; ?>
<?= csrf_field() ?><label>Username<input name="username" required autofocus></label><label>Password<input type="password" name="password" required></label><button class="btn block"><?= e($btn) ?></button></form></body></html>
<?php }
function seed_defaults(): void {
  $pdo = db(); $t = now();
  foreach (['Electronics', 'Home & Kitchen', 'Health & Beauty', 'Sports & Outdoors', 'Baby & Kids', 'Pets'] as $i => $c) $pdo->prepare('INSERT OR IGNORE INTO categories (name, slug, sort) VALUES (?,?,?)')->execute([$c, slugify($c), $i]);
  $pages = [
    'About Us' => '<p>' . SITE_NAME . ' is an independent product research site. Our editors analyse specs, expert reviews and thousands of real customer ratings to rank the best products in every category — so you can buy with confidence.</p>',
    'Contact' => '<p>Questions or partnership enquiries? Email us at <a href="mailto:' . SITE_EMAIL . '">' . SITE_EMAIL . '</a>.</p>',
    'Affiliate Disclosure' => '<p>' . SITE_NAME . ' participates in affiliate programs including the Amazon Associates Program. When you buy through links on our site we may earn a commission at no extra cost to you. As an Amazon Associate we earn from qualifying purchases. Commissions never affect our rankings.</p>',
    'Privacy Policy' => '<p>We respect your privacy. This site may use cookies and third-party services (analytics and affiliate partners) that collect anonymous usage data. Affiliate partners may set cookies to track referrals. We do not sell personal information. Contact: ' . SITE_EMAIL . '</p>',
  ];
  foreach ($pages as $title => $html) $pdo->prepare("INSERT OR IGNORE INTO posts (type,title,slug,content,status,created_at,updated_at) VALUES ('page',?,?,?,'published',?,?)")->execute([$title, $title === 'About Us' ? 'about' : slugify($title), $html, $t, $t]);
}
