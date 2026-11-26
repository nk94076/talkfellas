<?php
require dirname(__DIR__) . '/inc/helpers.php';
start_session();
header('X-Frame-Options: DENY'); header('Cache-Control: no-store'); header('X-Robots-Tag: noindex');
$a = $_GET['a'] ?? 'dashboard';
$post = $_SERVER['REQUEST_METHOD'] === 'POST';
$flash = $_SESSION['flash'] ?? ''; unset($_SESSION['flash']);
function go(string $q, string $msg = ''): void { if ($msg) $_SESSION['flash'] = $msg; header('Location: /admin/' . ($q ? "?$q" : '')); exit; }
function field(string $name, string $label, string $type = 'text', string $val = '', string $help = ''): string {
  $h = $help ? ' <small>' . e($help) . '</small>' : ''; $n = e($name); $v = e($val);
  return match ($type) {
    'textarea' => "<label>$label$h<textarea name=\"$n\" rows=\"3\">$v</textarea></label>",
    'lines' => "<label>$label$h<textarea name=\"$n\" rows=\"5\" class=\"mono\">$v</textarea></label>",
    'code' => "<label>$label$h<textarea name=\"$n\" rows=\"4\" class=\"mono\">$v</textarea></label>",
    'check' => "<label class=\"check\"><input type=\"hidden\" name=\"$n\" value=\"0\"><input type=\"checkbox\" name=\"$n\" value=\"1\" " . ($val === '1' ? 'checked' : '') . "> $label</label>",
    'image' => "<label>$label$h</label><div class=\"imgf\"><input name=\"$n\" value=\"$v\" placeholder=\"Image URL\"><button type=\"button\" class=\"button\" onclick=\"pick(this)\">Upload</button><img class=\"prev\" src=\"$v\" alt=\"\"></div>",
    'color' => "<label>$label<input type=\"color\" name=\"$n\" value=\"$v\" style=\"height:42px;padding:4px\"></label>",
    default => "<label>$label$h<input type=\"$type\" name=\"$n\" value=\"$v\"></label>",
  };
}
$ICONS = ['laptop' => 'Technology', 'plane' => 'Travel', 'heart' => 'Health', 'leaf' => 'Lifestyle', 'star' => 'Reviews', 'briefcase' => 'Business', 'cap' => 'Education',
  'grid' => 'Grid', 'home' => 'Home', 'cart' => 'Shopping', 'phone' => 'Mobile', 'tv' => 'TV', 'camera' => 'Camera', 'coffee' => 'Food', 'book' => 'Books',
  'dumbbell' => 'Fitness', 'baby' => 'Baby', 'paw' => 'Pets', 'gear' => 'How-to / Tools', 'bulb' => 'Ideas', 'globe' => 'World', 'chart' => 'Finance', 'trophy' => 'Top 10'];

/* ---------- Setup / login ---------- */
$hasUser = (int)db()->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0;
if (!$hasUser) {
  $err = '';
  if ($post) {
    check_csrf(); $u = trim($_POST['username'] ?? ''); $p = $_POST['password'] ?? '';
    if (strlen($u) < 3 || strlen($p) < 10) $err = 'Username min 3 chars, password min 10 chars.';
    else { db()->prepare("INSERT INTO users (username, pass_hash, name, role) VALUES (?,?,?,'admin')")->execute([$u, password_hash($p, PASSWORD_DEFAULT), $u]); seed(); session_regenerate_id(true); $_SESSION['uid'] = 1; set_staff_cookie(1); go('', 'Welcome! Your site is ready — start with Settings, then add posts.'); }
  }
  auth_page('Create admin account', 'Create Account', $err); exit;
}
if ($a === 'logout') { $_SESSION = []; session_destroy(); go(''); } // staff cookie is kept so your own visits stay excluded from stats
if (empty($_SESSION['uid'])) {
  $err = '';
  if ($post) {
    check_csrf(); $_SESSION['tries'] = ($_SESSION['tries'] ?? 0) + 1; if ($_SESSION['tries'] > 5) sleep(min(10, $_SESSION['tries']));
    $st = db()->prepare('SELECT * FROM users WHERE username=?'); $st->execute([trim($_POST['username'] ?? '')]); $u = $st->fetch();
    if ($u && password_verify($_POST['password'] ?? '', $u['pass_hash'])) { session_regenerate_id(true); $_SESSION['uid'] = $u['id']; $_SESSION['tries'] = 0; set_staff_cookie((int)$u['id']); go(''); }
    $err = 'Wrong username or password.';
  }
  auth_page('Admin Login', 'Log In', $err); exit;
}
$uid = (int)$_SESSION['uid'];
$ME = db()->query('SELECT * FROM users WHERE id=' . $uid)->fetch();
if (!$ME) { $_SESSION = []; go(''); }
$ROLE = $ME['role'] ?: 'admin';
$ADMIN_ONLY = ['settings', 'users', 'user-save', 'user-del', 'redirects', 'subs', 'export', 'sub-del'];
if ($ROLE !== 'admin' && in_array($a, $ADMIN_ONLY, true)) go('', 'Only administrators can open that page.');

/* ---------- Actions ---------- */
if ($a === 'upload' && $post) {
  check_csrf(); header('Content-Type: application/json');
  $f = $_FILES['file'] ?? null;
  if (!$f || $f['error'] !== UPLOAD_ERR_OK) exit(json_encode(['error' => 'Upload failed (max ' . ini_get('upload_max_filesize') . ')']));
  if ($f['size'] > 10 * 1024 * 1024) exit(json_encode(['error' => 'Max 10 MB']));
  $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif', 'image/svg+xml' => null][(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name'])] ?? null;
  if (!$ext || !getimagesize($f['tmp_name'])) exit(json_encode(['error' => 'Only JPG, PNG, WEBP or GIF images']));
  $base = slugify(pathinfo($f['name'], PATHINFO_FILENAME)) ?: 'image';
  $dir = '/uploads/' . date('Y/m'); @mkdir(dirname(__DIR__) . $dir, 0755, true);
  $name = $dir . '/' . substr($base, 0, 60) . '-' . bin2hex(random_bytes(3)) . '.' . $ext;
  move_uploaded_file($f['tmp_name'], dirname(__DIR__) . $name);
  exit(json_encode(['url' => $name]));
}
if ($a === 'save' && $post) {
  check_csrf(); $id = (int)($_POST['id'] ?? 0);
  $title = trim($_POST['title'] ?? ''); if ($title === '') go('a=edit&id=' . $id, 'Title is required.');
  $slug = slugify(($_POST['slug'] ?? '') ?: $title);
  $st = db()->prepare('SELECT COUNT(*) FROM posts WHERE slug=? AND id<>?'); $st->execute([$slug, $id]);
  if ($st->fetchColumn() || in_array($slug, ['admin', 'category', 'tag', 'search', 'blog', 'uploads', 'assets', 'subscribe', 'sitemap-xml', 'feed-xml', 'robots-txt'])) $slug .= '-' . bin2hex(random_bytes(2));
  $pub = strtotime($_POST['published_at'] ?? '') ?: time();
  $f = ['type' => ($_POST['type'] ?? '') === 'page' ? 'page' : 'post', 'title' => $title, 'slug' => $slug, 'excerpt' => trim($_POST['excerpt'] ?? ''),
    'intro' => $_POST['intro'] ?? '', 'content' => $_POST['content'] ?? '', 'image' => trim($_POST['image'] ?? ''), 'image_alt' => trim($_POST['image_alt'] ?? ''),
    'category_id' => ((int)($_POST['category_id'] ?? 0)) ?: null, 'author_id' => $uid, 'status' => ($_POST['status'] ?? '') === 'published' ? 'published' : 'draft',
    'in_hero' => (int)!empty($_POST['in_hero']), 'is_pick' => (int)!empty($_POST['is_pick']), 'tags' => implode(', ', array_filter(array_map('trim', explode(',', $_POST['tags'] ?? '')))),
    'meta_title' => trim($_POST['meta_title'] ?? ''), 'meta_desc' => trim($_POST['meta_desc'] ?? ''), 'focus_kw' => trim($_POST['focus_kw'] ?? ''),
    'canonical' => trim($_POST['canonical'] ?? ''), 'noindex' => (int)!empty($_POST['noindex']), 'og_image' => trim($_POST['og_image'] ?? ''),
    'aff_name' => trim($_POST['aff_name'] ?? ''), 'aff_url' => trim($_POST['aff_url'] ?? ''), 'aff_desc' => trim($_POST['aff_desc'] ?? ''), 'aff_btn' => trim($_POST['aff_btn'] ?? ''), 'numbered' => (int)!empty($_POST['numbered']),
    'published_at' => date('Y-m-d H:i:s', $pub), 'updated_at' => now()];
  $pdo = db(); $pdo->beginTransaction();
  if ($id) {
    $old = $pdo->prepare('SELECT slug, status FROM posts WHERE id=?'); $old->execute([$id]); $old = $old->fetch();
    unset($f['author_id']); $set = implode(',', array_map(fn($k) => "$k=:$k", array_keys($f))); $f['id'] = $id; $pdo->prepare("UPDATE posts SET $set WHERE id=:id")->execute($f);
    if ($old && $old['status'] === 'published' && $old['slug'] !== $slug) $pdo->prepare('INSERT OR REPLACE INTO redirects (src, dest) VALUES (?,?)')->execute(['/' . $old['slug'], '/' . $slug]);
  } else { $pdo->prepare('INSERT INTO posts (' . implode(',', array_keys($f)) . ') VALUES (:' . implode(',:', array_keys($f)) . ')')->execute($f); $id = (int)$pdo->lastInsertId(); }
  $pdo->prepare('DELETE FROM redirects WHERE src=?')->execute(['/' . $slug]);
  $pdo->prepare('DELETE FROM products WHERE post_id=?')->execute([$id]);
  $ins = $pdo->prepare('INSERT INTO products (post_id, rank, name, image, score, badge, features, link, store, button) VALUES (?,?,?,?,?,?,?,?,?,?)'); $r = 0;
  foreach (($_POST['pr'] ?? []) as $x) { if (trim($x['name'] ?? '') === '') continue;
    $ins->execute([$id, ++$r, trim($x['name']), trim($x['image'] ?? ''), trim($x['score'] ?? ''), trim($x['badge'] ?? ''), trim($x['features'] ?? ''), trim($x['link'] ?? ''), trim($x['store'] ?? '') ?: 'Amazon', trim($x['button'] ?? '') ?: 'Check Price']); }
  $pdo->commit();
  go('a=edit&id=' . $id, 'Saved ✓ ' . ($f['status'] === 'published' ? '<a href="/' . e($slug) . '" target="_blank">View live →</a>' : '(Draft — not public yet)'));
}
if ($a === 'delete' && $post) { check_csrf(); db()->prepare('DELETE FROM posts WHERE id=?')->execute([(int)$_POST['id']]); go('a=' . (($_POST['back'] ?? '') === 'pages' ? 'pages' : 'posts'), 'Deleted.'); }
if ($a === 'cat-save' && $post) {
  check_csrf(); $id = (int)($_POST['id'] ?? 0); $name = trim($_POST['name'] ?? ''); if ($name === '') go('a=cats', 'Name required.');
  $f = ['name' => $name, 'slug' => slugify(($_POST['slug'] ?? '') ?: $name), 'description' => trim($_POST['description'] ?? ''), 'icon' => array_key_exists($_POST['icon'] ?? '', $ICONS) ? $_POST['icon'] : 'grid',
    'image' => trim($_POST['image'] ?? ''), 'show_home' => (int)!empty($_POST['show_home']), 'sort' => (int)($_POST['sort'] ?? 0), 'meta_title' => trim($_POST['meta_title'] ?? ''), 'meta_desc' => trim($_POST['meta_desc'] ?? '')];
  try {
    if ($id) { $set = implode(',', array_map(fn($k) => "$k=:$k", array_keys($f))); $f['id'] = $id; db()->prepare("UPDATE categories SET $set WHERE id=:id")->execute($f); }
    else db()->prepare('INSERT INTO categories (' . implode(',', array_keys($f)) . ') VALUES (:' . implode(',:', array_keys($f)) . ')')->execute($f);
  } catch (PDOException) { go('a=cats', 'A category with that URL already exists.'); }
  go('a=cats', 'Category saved ✓');
}
if ($a === 'cat-del' && $post) { check_csrf(); db()->prepare('DELETE FROM categories WHERE id=?')->execute([(int)$_POST['id']]); go('a=cats', 'Category deleted.'); }
if ($a === 'settings' && $post) {
  check_csrf(); $ins = db()->prepare('INSERT OR REPLACE INTO settings (k, v) VALUES (?,?)'); $d = defaults();
  foreach ($_POST['s'] ?? [] as $k => $v) { if (!array_key_exists($k, $d) || ($k === 'ai_api_key' && trim($v) === '')) continue; $ins->execute([$k, is_array($v) ? end($v) : trim($v)]); }
  go('a=settings&tab=' . urlencode($_POST['tab'] ?? ''), 'Settings saved ✓');
}
if ($a === 'profile' && $post) {
  check_csrf(); db()->prepare('UPDATE users SET name=?, bio=?, avatar=? WHERE id=?')->execute([trim($_POST['name'] ?? ''), trim($_POST['bio'] ?? ''), trim($_POST['avatar'] ?? ''), $uid]);
  if (($_POST['new'] ?? '') !== '') {
    $u = db()->query('SELECT pass_hash FROM users WHERE id=' . $uid)->fetchColumn();
    if (!password_verify($_POST['old'] ?? '', $u)) go('a=profile', 'Profile saved, but current password was wrong — password not changed.');
    if (strlen($_POST['new']) < 10) go('a=profile', 'New password must be at least 10 characters.');
    db()->prepare('UPDATE users SET pass_hash=? WHERE id=?')->execute([password_hash($_POST['new'], PASSWORD_DEFAULT), $uid]);
  }
  go('a=profile', 'Profile saved ✓');
}
if ($a === 'redirects' && $post) {
  check_csrf();
  if (!empty($_POST['del'])) db()->prepare('DELETE FROM redirects WHERE src=?')->execute([$_POST['del']]);
  elseif (($src = '/' . trim(parse_url(trim($_POST['src'] ?? ''), PHP_URL_PATH) ?? '', '/')) !== '/' && trim($_POST['dest'] ?? '') !== '') db()->prepare('INSERT OR REPLACE INTO redirects (src, dest) VALUES (?,?)')->execute([$src, trim($_POST['dest'])]);
  go('a=redirects', 'Redirects updated.');
}
if ($a === 'export') {
  header('Content-Type: text/csv'); header('Content-Disposition: attachment; filename=subscribers.csv');
  $o = fopen('php://output', 'w'); fputcsv($o, ['email', 'date']); foreach (db()->query('SELECT * FROM subscribers ORDER BY created_at DESC') as $r) fputcsv($o, $r); exit;
}
if ($a === 'sub-del' && $post) { check_csrf(); db()->prepare('DELETE FROM subscribers WHERE email=?')->execute([$_POST['email'] ?? '']); go('a=subs', 'Removed.'); }

if ($a === 'quickdraft' && $post) {
  check_csrf(); $t = trim($_POST['title'] ?? ''); if ($t === '') go('', 'Add a title for your draft.');
  $slug = slugify($t) . '-' . bin2hex(random_bytes(2));
  db()->prepare("INSERT INTO posts (type,title,slug,content,status,author_id,published_at,updated_at) VALUES ('post',?,?,?,'draft',?,?,?)")->execute([$t, $slug, '<p>' . nl2br(e(trim($_POST['content'] ?? ''))) . '</p>', $uid, now(), now()]);
  go('', 'Draft saved. <a href="?a=edit&id=' . db()->lastInsertId() . '">Edit draft →</a>');
}

if ($a === 'user-save' && $post) {
  check_csrf(); $id = (int)($_POST['id'] ?? 0); $un = trim($_POST['username'] ?? ''); $pw = $_POST['password'] ?? '';
  $role = in_array($_POST['role'] ?? '', ['admin', 'editor'], true) ? $_POST['role'] : 'editor';
  if (!preg_match('/^[A-Za-z0-9_.@-]{3,40}$/', $un)) go('a=users' . ($id ? "&id=$id" : ''), 'Username: 3–40 letters, numbers or . _ - @');
  if (!$id && strlen($pw) < 10) go('a=users', 'Password must be at least 10 characters.');
  if ($id === $uid && $role !== 'admin') go('a=users', 'You cannot remove your own admin role.');
  try {
    if ($id) {
      db()->prepare('UPDATE users SET username=?, name=?, email=?, role=? WHERE id=?')->execute([$un, trim($_POST['name'] ?? ''), trim($_POST['email'] ?? ''), $role, $id]);
      if ($pw !== '') { if (strlen($pw) < 10) go("a=users&id=$id", 'Password must be at least 10 characters.'); db()->prepare('UPDATE users SET pass_hash=? WHERE id=?')->execute([password_hash($pw, PASSWORD_DEFAULT), $id]); }
    } else db()->prepare('INSERT INTO users (username, pass_hash, name, email, role) VALUES (?,?,?,?,?)')->execute([$un, password_hash($pw, PASSWORD_DEFAULT), trim($_POST['name'] ?? ''), trim($_POST['email'] ?? ''), $role]);
  } catch (PDOException) { go('a=users', 'That username is already taken.'); }
  go('a=users', 'User saved ✓');
}
if ($a === 'user-del' && $post) {
  check_csrf(); $id = (int)($_POST['id'] ?? 0); if ($id === $uid) go('a=users', 'You cannot delete yourself.');
  db()->prepare('UPDATE posts SET author_id=? WHERE author_id=?')->execute([$uid, $id]); db()->prepare('DELETE FROM users WHERE id=?')->execute([$id]); go('a=users', 'User deleted. Their posts were moved to you.');
}
if ($a === 'ai-gen' && $post) {
  check_csrf(); @set_time_limit(300);
  $in = ['title' => trim($_POST['title'] ?? ''), 'aff_url' => trim($_POST['aff_url'] ?? ''), 'aff_name' => trim($_POST['aff_name'] ?? ''), 'notes' => trim($_POST['notes'] ?? ''),
    'keyword' => trim($_POST['keyword'] ?? ''), 'words' => max(500, min(4000, (int)($_POST['words'] ?? s('ai_words')))), 'category_id' => (int)($_POST['category_id'] ?? 0)];
  $_SESSION['ai_form'] = $in;
  if ($in['title'] === '') go('a=ai', 'Title is required.');
  if ($in['aff_url'] !== '' && !preg_match('#^https?://#i', $in['aff_url'])) go('a=ai', 'Affiliate link must start with http:// or https://');
  [$out, $err] = ai_write($in);
  if ($err) go('a=ai', '<b>AI error:</b> ' . e($err));
  $slug = slugify(slugify((string)($out['slug'] ?? '')) !== '' && !empty($out['slug']) ? $out['slug'] : $in['title']); $chk = db()->prepare('SELECT COUNT(*) FROM posts WHERE slug=?'); $chk->execute([$slug]); if ($chk->fetchColumn()) $slug .= '-' . bin2hex(random_bytes(2));
  $f = ['type' => 'post', 'title' => $in['title'], 'slug' => $slug, 'excerpt' => mb_substr(trim($out['excerpt'] ?? ''), 0, 300), 'intro' => '', 'content' => $out['content_html'] ?? '',
    'category_id' => $in['category_id'] ?: null, 'author_id' => $uid, 'status' => 'draft', 'tags' => implode(', ', array_slice((array)($out['tags'] ?? []), 0, 8)),
    'meta_title' => mb_substr(trim($out['meta_title'] ?? ''), 0, 70), 'meta_desc' => mb_substr(trim($out['meta_description'] ?? ''), 0, 170), 'focus_kw' => $in['keyword'] ?: trim($out['focus_keyword'] ?? ''),
    'aff_name' => $in['aff_name'], 'aff_url' => $in['aff_url'], 'aff_desc' => mb_substr(trim($out['cta_description'] ?? ''), 0, 220), 'aff_btn' => '', 'numbered' => 1, 'published_at' => now(), 'updated_at' => now()];
  db()->prepare('INSERT INTO posts (' . implode(',', array_keys($f)) . ') VALUES (:' . implode(',:', array_keys($f)) . ')')->execute($f);
  unset($_SESSION['ai_form']);
  go('a=edit&id=' . db()->lastInsertId(), 'AI draft ready ✓ — review it, add a featured image, then set Status → Published.');
}

/* ================= Views ================= */
admin_head($a, $flash);
if ($a === 'dashboard') {
  $cnt = fn($sql) => (int)db()->query($sql)->fetchColumn();
  $glance = [['doc', $cnt("SELECT COUNT(*) FROM posts WHERE type='post' AND status='published'"), 'Published posts', '?a=posts&status=published'],
    ['book', $cnt("SELECT COUNT(*) FROM posts WHERE type='post' AND status='draft'"), 'Drafts', '?a=posts&status=draft'],
    ['grid', $cnt("SELECT COUNT(*) FROM posts WHERE type='page'"), 'Pages', '?a=pages'], ['briefcase', $cnt('SELECT COUNT(*) FROM categories'), 'Categories', '?a=cats'],
    ['mail', $cnt('SELECT COUNT(*) FROM subscribers'), 'Subscribers', '?a=subs'], ['chart', $cnt("SELECT COALESCE(SUM(views),0) FROM posts"), 'Total views', '?a=posts'], ['cart', $cnt("SELECT COUNT(*) FROM clicks WHERE created_at >= datetime('now','-30 days')"), 'Affiliate clicks (30d)', '?a=posts']];
  $recent = db()->query("SELECT id,title,slug,published_at,status FROM posts WHERE type='post' ORDER BY updated_at DESC LIMIT 6")->fetchAll();
  $top = db()->query("SELECT id,title,slug,views_week,views FROM posts WHERE type='post' AND status='published' ORDER BY views_week DESC, views DESC LIMIT 5")->fetchAll();
  $me = db()->query('SELECT name, username, avatar FROM users WHERE id=' . $uid)->fetch();
  $todo = ['Add your logo (Settings → General)' => (bool)s('logo_image'), 'Set homepage SEO title & description (Settings → SEO)' => s('seo_home_title') !== defaults()['seo_home_title'],
    'Add Google Search Console code (Settings → SEO)' => (bool)s('seo_google_verify'), 'Upload category images (Categories)' => !$cnt("SELECT COUNT(*) FROM categories WHERE image='' AND show_home=1"),
    'Add your author photo & bio (Profile)' => (bool)$me['avatar'], 'Publish your first post' => (bool)$glance[0][1]];
  $done = count(array_filter($todo)); $colors = ['violet', 'blue', 'amber', 'rose', 'green', 'indigo', 'amber']; ?>
  <div class="page-head"><div><h1>Dashboard</h1><p>Welcome back, <?= e($me['name'] ?: $me['username']) ?> 👋 Here’s what’s happening with your site.</p></div><span class="date-chip"><?= tf_icon('calendar', 16) . date('M j, Y') ?></span></div>
  <div class="stats"><?php foreach ($glance as $i => [$ic, $n, $l, $u]): ?><a class="stat <?= $colors[$i] ?>" href="<?= $u ?>"><span class="st-ic"><?= tf_icon($ic, 22) ?></span><span><b><?= number_format($n) ?></b><small><?= $l ?></small></span></a><?php endforeach; ?></div>
  <div class="dash">
    <div class="card"><div class="card-h"><?= tf_icon('doc', 22) ?><div><h2>Quick Draft</h2></div></div>
      <form method="post" action="?a=quickdraft"><?= csrf_field() ?><label>Title<input name="title" placeholder="Enter post title…"></label><label>Content<textarea name="content" rows="4" placeholder="What’s on your mind?"></textarea></label>
      <div class="btns"><button class="button button-primary"><?= tf_icon('doc', 16) ?> Save Draft</button><button type="reset" class="button">↻ Clear</button></div></form></div>
    <div class="card"><div class="card-h"><?= tf_icon('chart', 22) ?><div><h2>At a Glance</h2><small>A quick overview of your content.</small></div></div>
      <ul class="glance"><?php foreach ($glance as $i => [$ic, $n, $l, $u]): ?><li><a href="<?= $u ?>"><span class="g-ic <?= $colors[$i] ?>"><?= tf_icon($ic, 15) ?></span><?= $l ?><b><?= number_format($n) ?></b></a></li><?php endforeach; ?></ul></div>
    <div class="card"><div class="card-h"><?= tf_icon('clock', 22) ?><div><h2>Activity — Recently Edited</h2></div><a class="more" href="?a=posts">View All ›</a></div>
      <?php if ($recent): ?><ul class="activity"><?php foreach ($recent as $r): ?><li><a href="?a=edit&id=<?= $r['id'] ?>"><?= e($r['title']) ?></a><span><?= $r['status'] === 'draft' ? '<em class="tag-draft">Draft</em>' : '' ?><?= e(nice_date($r['published_at'], 'M j')) ?></span></li><?php endforeach; ?></ul>
      <?php else: ?><div class="empty"><span class="em-ic"><?= tf_icon('doc', 40) ?></span><b>No posts yet</b><p>Once you create or edit a post, it will appear here.</p><a class="button button-primary" href="?a=edit&type=post">+ Create Your First Post</a></div><?php endif; ?></div>
  </div>
  <div class="dash two">
    <div class="card"><div class="card-h"><span class="fire">🔥</span><div><h2>Popular This Week</h2><small>Your most viewed posts from the last 7 days.</small></div></div>
      <?php if ($top && $top[0]['views_week']): ?><ol class="pop"><?php foreach ($top as $i => $r): ?><li><span class="n"><?= $i + 1 ?></span><a href="/<?= e($r['slug']) ?>" target="_blank"><?= e($r['title']) ?></a><b><?= (int)$r['views_week'] ?> views</b></li><?php endforeach; ?></ol>
      <?php else: ?><div class="empty sm"><span class="em-ic"><?= tf_icon('chart', 30) ?></span><b>No views yet</b><p>Content views will appear here once your posts start getting traffic.</p></div><?php endif; ?></div>
    <div class="card"><div class="card-h"><?= tf_icon('shield', 22) ?><div><h2>Site Setup Checklist</h2><small>Get your site ready for launch.</small></div><div class="prog"><small><?= $done ?> of <?= count($todo) ?> completed</small><span><i style="width:<?= round($done / count($todo) * 100) ?>%"></i></span></div></div>
      <ul class="todo"><?php foreach ($todo as $t => $ok): ?><li class="<?= $ok ? 'ok' : '' ?>"><span class="cb"><?= $ok ? '✓' : '' ?></span><?= e($t) ?></li><?php endforeach; ?></ul></div>
  </div>
<?php } elseif ($a === 'posts' || $a === 'pages') {
  $type = $a === 'pages' ? 'page' : 'post'; $q = trim($_GET['q'] ?? ''); $stf = in_array($_GET['status'] ?? '', ['published', 'draft']) ? $_GET['status'] : '';
  $counts = db()->prepare("SELECT status, COUNT(*) FROM posts WHERE type=? GROUP BY status"); $counts->execute([$type]); $counts = $counts->fetchAll(PDO::FETCH_KEY_PAIR);
  $st = db()->prepare("SELECT p.*, c.name cat, u.name author FROM posts p LEFT JOIN categories c ON c.id=p.category_id LEFT JOIN users u ON u.id=p.author_id WHERE p.type=? AND p.title LIKE ?" . ($stf ? " AND p.status='$stf'" : '') . " ORDER BY p.published_at DESC"); $st->execute([$type, "%$q%"]); $rows = $st->fetchAll(); ?>
  <h1 class="wp-heading"><?= $type === 'page' ? 'Pages' : 'Posts' ?> <a class="page-title-action" href="?a=edit&type=<?= $type ?>">Add New <?= $type === 'page' ? 'Page' : 'Post' ?></a></h1>
  <div class="tablenav"><ul class="subsubsub"><li><a class="<?= $stf ? '' : 'current' ?>" href="?a=<?= $a ?>">All <span>(<?= array_sum($counts) ?>)</span></a> |</li><li><a class="<?= $stf === 'published' ? 'current' : '' ?>" href="?a=<?= $a ?>&status=published">Published <span>(<?= $counts['published'] ?? 0 ?>)</span></a> |</li><li><a class="<?= $stf === 'draft' ? 'current' : '' ?>" href="?a=<?= $a ?>&status=draft">Drafts <span>(<?= $counts['draft'] ?? 0 ?>)</span></a></li></ul>
    <form class="search-box"><input type="hidden" name="a" value="<?= $a ?>"><input type="search" name="q" value="<?= e($q) ?>"><button class="button">Search <?= $type === 'page' ? 'Pages' : 'Posts' ?></button></form></div>
  <table class="wp-list-table"><thead><tr><th class="col-title">Title</th><th>Author</th><?php if ($type === 'post'): ?><th>Category</th><th>Tags</th><th class="num">Views</th><th class="num">Clicks</th><?php endif; ?><th>Date</th></tr></thead><tbody>
  <?php foreach ($rows as $r): $sched = $r['status'] === 'published' && $r['published_at'] > now(); ?><tr>
    <td class="col-title"><strong><a class="row-title" href="?a=edit&id=<?= $r['id'] ?>"><?= e($r['title']) ?></a><?= $r['status'] === 'draft' ? ' <span class="post-state">— Draft</span>' : ($sched ? ' <span class="post-state">— Scheduled</span>' : '') ?><?= $r['in_hero'] ? ' <span class="post-state">— Hero</span>' : '' ?><?= $r['is_pick'] ? ' <span class="post-state">— Pick</span>' : '' ?></strong>
      <div class="row-actions"><a href="?a=edit&id=<?= $r['id'] ?>">Edit</a> | <a href="/<?= e($r['slug']) ?>" target="_blank"><?= $r['status'] === 'draft' ? 'Preview' : 'View' ?></a> | <form method="post" action="?a=delete" onsubmit="return confirm('Move to trash? This cannot be undone.')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>"><input type="hidden" name="back" value="<?= $a ?>"><button class="link trash">Trash</button></form></div></td>
    <td><?= e($r['author'] ?: '—') ?></td><?php if ($type === 'post'): ?><td><?= $r['cat'] ? e($r['cat']) : '—' ?></td><td><?= $r['tags'] ? e($r['tags']) : '—' ?></td><td class="num"><?= (int)$r['views'] ?></td><td class="num"><?= (int)$r['clicks'] ?></td><?php endif; ?>
    <td class="date"><?= $r['status'] === 'draft' ? 'Last Modified' : ($sched ? 'Scheduled' : 'Published') ?><br><?= e(nice_date($r['status'] === 'draft' ? $r['updated_at'] : $r['published_at'], 'Y/m/d \a\t g:i a')) ?></td>
  </tr><?php endforeach; if (!$rows) echo '<tr><td colspan="6">No ' . ($type === 'page' ? 'pages' : 'posts') . ' found.</td></tr>'; ?></tbody></table>
<?php } elseif ($a === 'edit') {
  $id = (int)($_GET['id'] ?? 0);
  $p = ['id' => 0, 'type' => ($_GET['type'] ?? '') === 'page' ? 'page' : 'post', 'title' => '', 'slug' => '', 'excerpt' => '', 'intro' => '', 'content' => '', 'image' => '', 'image_alt' => '', 'category_id' => null, 'status' => 'draft',
    'in_hero' => 0, 'is_pick' => 0, 'tags' => '', 'meta_title' => '', 'meta_desc' => '', 'focus_kw' => '', 'canonical' => '', 'noindex' => 0, 'og_image' => '', 'published_at' => now()]; $prods = [];
  if ($id) { $st = db()->prepare('SELECT * FROM posts WHERE id=?'); $st->execute([$id]); $p = $st->fetch() ?: go('', 'Not found.'); $st = db()->prepare('SELECT * FROM products WHERE post_id=? ORDER BY rank'); $st->execute([$id]); $prods = $st->fetchAll(); }
  $isPost = $p['type'] === 'post'; ?>
  <form method="post" action="?a=save" id="editor"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $p['id'] ?>"><input type="hidden" name="type" value="<?= e($p['type']) ?>">
  <h1 class="wp-heading"><?= $id ? 'Edit' : 'Add New' ?> <?= $isPost ? 'Post' : 'Page' ?><?php if ($id): ?> <a class="page-title-action" href="?a=edit&type=<?= e($p['type']) ?>">Add New</a><?php endif; ?></h1>
  <div class="cols"><div class="main-col">
    <input name="title" id="f-title" class="title-input" required value="<?= e($p['title']) ?>" placeholder="Add title" aria-label="Title">
    <label>URL <small><?= e(SITE_URL) ?>/<b id="slug-prev"><?= e($p['slug']) ?></b> — blank = from title. Changing a live URL auto-creates a 301 redirect.</small><input name="slug" id="f-slug" value="<?= e($p['slug']) ?>"></label>
    <?php if ($isPost): ?>
      <label>Excerpt / short description <small>(cards, under title, and default Google description)</small><textarea name="excerpt" id="f-excerpt" rows="2"><?= e($p['excerpt']) ?></textarea></label>
      <label>Intro <small>(appears before the product list)</small></label><div class="rte" data-name="intro"><?= clean_html($p['intro']) ?></div><input type="hidden" name="intro">
      <h2 class="sub">Ranked Products <small>— optional, for Top-10 / review posts. Drag ↕ to reorder.</small></h2>
      <div id="products"></div><button type="button" class="button" onclick="addProduct()">+ Add Product</button>
      <label style="margin-top:24px">Article content <small>(use H2/H3 headings — a table of contents is built automatically)</small></label>
    <?php else: ?><label>Content</label><?php endif; ?>
    <div class="rte" data-name="content"><?= clean_html($p['content']) ?></div><input type="hidden" name="content">

    <?php if ($isPost): ?><div class="seo-box"><h2 class="sub">Affiliate Box <small>— shown at the end of the post (or where you type {{cta}}). Wrap words in {{aff}}…{{/aff}} to link them.</small></h2>
      <div class="g2"><?= field('aff_name', 'Brand / product name', 'text', $p['aff_name'] ?? '') ?><?= field('aff_url', 'Affiliate link', 'url', $p['aff_url'] ?? '', 'https://…') ?></div>
      <?= field('aff_desc', 'Short description', 'textarea', $p['aff_desc'] ?? '') ?><div class="g2"><?= field('aff_btn', 'Button text', 'text', $p['aff_btn'] ?? '', 'default: Visit {brand}') ?>
      <label class="check" style="margin-top:38px"><input type="checkbox" name="numbered" value="1" <?= !empty($p['numbered']) ? 'checked' : '' ?>> Numbered headings (1, 2, 3…)</label></div></div><?php endif; ?>
    <div class="seo-box"><h2 class="sub">SEO</h2>
      <div class="serp"><span class="serp-url"><?= e(parse_url(SITE_URL, PHP_URL_HOST)) ?> › <span id="serp-slug"><?= e($p['slug']) ?></span></span><span class="serp-title" id="serp-title"></span><span class="serp-desc" id="serp-desc"></span></div>
      <div class="g2"><?= field('focus_kw', 'Focus keyword', 'text', $p['focus_kw'], 'main search phrase') ?><?= $isPost ? field('tags', 'Tags', 'text', $p['tags'], 'comma separated') : '' ?></div>
      <label>SEO title <small id="mt-count"></small><input name="meta_title" id="f-mt" value="<?= e($p['meta_title']) ?>" placeholder="Defaults to post title"></label>
      <label>Meta description <small id="md-count"></small><textarea name="meta_desc" id="f-md" rows="2" placeholder="Defaults to excerpt"><?= e($p['meta_desc']) ?></textarea></label>
      <ul class="checks" id="seo-checks"></ul>
      <details><summary>Advanced</summary>
        <?= field('canonical', 'Canonical URL', 'url', $p['canonical'], 'leave blank unless this is a copy of another page') ?>
        <?= field('og_image', 'Social share image', 'image', $p['og_image'], 'defaults to featured image') ?>
        <label class="check"><input type="checkbox" name="noindex" value="1" <?= $p['noindex'] ? 'checked' : '' ?>> Hide from Google (noindex)</label>
      </details>
    </div>
  </div>
  <div class="side-col">
    <div class="box"><h3>Publish</h3><?php if ($id && $p['status'] === 'published'): ?><a class="button" href="/<?= e($p['slug']) ?>" target="_blank" style="margin-bottom:8px">View Post</a><?php endif; ?>
      <label>Status<select name="status"><option value="draft">Draft</option><option value="published" <?= $p['status'] === 'published' ? 'selected' : '' ?>>Published</option></select></label>
      <label>Publish date <small>(future = scheduled)</small><input type="datetime-local" name="published_at" value="<?= e(date('Y-m-d\TH:i', strtotime($p['published_at']))) ?>"></label>
      <?php if ($isPost): ?><label class="check"><input type="checkbox" name="in_hero" value="1" <?= $p['in_hero'] ? 'checked' : '' ?>> Show in homepage hero slider</label>
      <label class="check"><input type="checkbox" name="is_pick" value="1" <?= $p['is_pick'] ? 'checked' : '' ?>> Editor's Pick</label><?php endif; ?>
      <div class="major-publishing"><?php if ($id): ?><span class="muted">Last saved <?= e(nice_date($p['updated_at'] ?? now(), 'M j, g:i a')) ?></span><?php endif; ?><button class="button button-primary button-large"><?= $p['status'] === 'published' ? 'Update' : 'Save' ?></button></div></div>
    <?php if ($isPost): ?><div class="box"><h3>Category</h3><select name="category_id"><option value="">— None —</option><?php foreach (categories() as $c): ?><option value="<?= $c['id'] ?>" <?= $c['id'] == $p['category_id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select><small><a href="?a=cats">Manage categories</a></small></div><?php endif; ?>
    <div class="box"><h3>Featured Image</h3><?= field('image', '', 'image', $p['image']) ?><?= field('image_alt', 'Alt text (SEO)', 'text', $p['image_alt']) ?></div>
  </div></div></form>
  <template id="ptpl"><div class="prod" draggable="true">
    <div class="prod-h"><span class="drag">↕</span><b class="num"></b><button type="button" class="link danger" onclick="this.closest('.prod').remove();renum()">Remove</button></div>
    <div class="g2"><label>Product name<input data-k="name"></label><label>Affiliate link<input data-k="link" placeholder="https://amzn.to/..."></label>
    <label>Badge<input data-k="badge" placeholder="Best Overall"></label><label>Score<input data-k="score" placeholder="9.8"></label>
    <label>Store<input data-k="store" placeholder="Amazon"></label><label>Button text<input data-k="button" placeholder="Check Price"></label></div>
    <label>Image</label><div class="imgf"><input data-k="image" placeholder="Image URL"><button type="button" class="button" onclick="pick(this)">Upload</button><img class="prev" alt=""></div>
    <label>Key features <small>(one per line)</small><textarea data-k="features" rows="3"></textarea></label>
  </div></template>
  <script>window.PRODUCTS=<?= json_encode($prods, JSON_HEX_TAG | JSON_HEX_AMP) ?>;window.SITE=<?= json_encode(s('site_name')) ?>;window.SEP=<?= json_encode(s('seo_title_sep')) ?>;</script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.snow.min.css" rel="stylesheet"><script src="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.min.js"></script>
  <script src="/admin/editor.js?v=3"></script>
<?php } elseif ($a === 'cats') {
  $edit = null; if (isset($_GET['id'])) foreach (categories() as $c) if ($c['id'] == $_GET['id']) $edit = $c;
  $c = $edit ?? ['id' => 0, 'name' => '', 'slug' => '', 'description' => '', 'icon' => 'grid', 'image' => '', 'show_home' => 1, 'sort' => count(categories()), 'meta_title' => '', 'meta_desc' => '']; ?>
  <h1 class="wp-heading">Categories</h1>
  <div class="cols"><div><table class="wp-list-table"><tr><th></th><th>Name</th><th>Posts</th><th>Homepage</th><th>Order</th><th></th></tr>
    <?php foreach (categories() as $r): ?><tr><td class="ic-cell"><?= tf_icon($r['icon'], 20) ?></td><td><a href="?a=cats&id=<?= $r['id'] ?>"><b><?= e($r['name']) ?></b></a><br><small>/category/<?= e($r['slug']) ?></small></td><td><?= $r['n'] ?></td><td><?= $r['show_home'] ? '✓' : '—' ?></td><td><?= $r['sort'] ?></td>
      <td class="act"><a href="?a=cats&id=<?= $r['id'] ?>">Edit</a><form method="post" action="?a=cat-del" onsubmit="return confirm('Delete category? Posts stay, just uncategorised.')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="link danger">Delete</button></form></td></tr><?php endforeach; ?>
  </table></div>
  <form method="post" action="?a=cat-save" class="box"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $c['id'] ?>"><h3><?= $edit ? 'Edit category' : 'Add category' ?></h3>
    <?= field('name', 'Name', 'text', $c['name']) ?><?= field('slug', 'URL slug', 'text', $c['slug'], 'blank = from name') ?>
    <label>Icon <small>(category bar)</small><select name="icon"><?php foreach ($ICONS as $k => $v): ?><option value="<?= $k ?>" <?= $c['icon'] === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></label>
    <?= field('image', 'Image (Explore by Category)', 'image', $c['image']) ?>
    <label class="check"><input type="checkbox" name="show_home" value="1" <?= $c['show_home'] ? 'checked' : '' ?>> Show on homepage & menu</label>
    <?= field('sort', 'Order', 'number', (string)$c['sort']) ?><?= field('description', 'Description', 'textarea', $c['description']) ?>
    <?= field('meta_title', 'SEO title', 'text', $c['meta_title']) ?><?= field('meta_desc', 'Meta description', 'textarea', $c['meta_desc']) ?>
    <button class="button button-primary block" style="margin-top:12px">Save category</button><?php if ($edit): ?><p><a href="?a=cats">+ Add new instead</a></p><?php endif; ?>
  </form></div>
<?php } elseif ($a === 'settings') {
  $cats = ['' => '— Choose —']; foreach (categories() as $c) $cats[$c['id']] = $c['name'];
  $L = 'One per line:  Label | /link';
  $tabs = [
    'General' => [['site_name', 'Site name'], ['logo_tagline', 'Logo tagline'], ['logo_image', 'Logo image (optional — replaces text logo)', 'image'], ['favicon', 'Favicon', 'image'],
      ['brand_color', 'Brand colour', 'color'], ['dark_color', 'Dark colour', 'color'], ['header_menu', 'Header menu', 'lines', $L . ' (Categories dropdown is added automatically)'], ['disclosure', 'Affiliate disclosure (posts with products)', 'textarea']],
    'Hero' => [['hero_eyebrow', 'Small heading'], ['hero_title', 'Title'], ['hero_highlight', 'Highlighted words (orange)'], ['hero_text', 'Description', 'textarea'],
      ['hero_btn1', 'Button 1 text'], ['hero_btn1_url', 'Button 1 link'], ['hero_btn2', 'Button 2 text'], ['hero_btn2_url', 'Button 2 link'],
      ['hero_trust', 'Trust line', 'text', '<b>bold</b> allowed'], ['hero_avatars', 'Trust avatars (image URLs, one per line)', 'lines'], ['hero_slides', 'Slider posts (tick "hero slider" on posts)', 'number']],
    'Categories & Trending' => [['show_catbar', 'Show category icon bar', 'check'], ['show_trending', 'Show trending topics', 'check'], ['trending_title', 'Trending label'], ['trending_links', 'Trending topics', 'lines', $L], ['trending_more', '"View all" text'], ['trending_more_url', '"View all" link'],
      ['show_cats', 'Show "Explore by Category"', 'check'], ['cats_title', 'Explore section title']],
    'Latest & Popular' => [['show_latest', 'Show section', 'check'], ['latest_eyebrow', 'Small heading'], ['latest_title', 'Title'], ['popular_title', 'Popular box title (ranked by views this week)']],
    'Reviews Banner' => [['show_promo', 'Show section', 'check'], ['promo_title', 'Title'], ['promo_text', 'Text', 'textarea'], ['promo_btn', 'Button text'], ['promo_btn_url', 'Button link'], ['promo_image', 'Image', 'image'], ['promo_point1', 'Point 1'], ['promo_point2', 'Point 2'], ['promo_point3', 'Point 3']],
    "Picks & How-To" => [['show_picks', "Show Editor's Picks (tick on posts)", 'check'], ['picks_title', 'Picks title'], ['picks_text', 'Picks text', 'textarea'], ['picks_btn', 'Picks button'], ['picks_btn_url', 'Picks button link'],
      ['show_howto', 'Show How-To Guides', 'check'], ['howto_title', 'How-To title'], ['howto_text', 'How-To text', 'textarea'], ['howto_cat', 'How-To category', 'select', $cats]],
    'Newsletter & Stats' => [['show_news', 'Show newsletter', 'check'], ['news_title', 'Title'], ['news_text', 'Text', 'textarea'], ['news_btn', 'Button'], ['news_action', 'External form URL (Mailchimp etc.)', 'url', 'blank = save emails here (Subscribers)'],
      ['show_stats', 'Show stats', 'check'], ['stat1_num', 'Stat 1'], ['stat1_label', 'Label 1'], ['stat2_num', 'Stat 2'], ['stat2_label', 'Label 2'], ['stat3_num', 'Stat 3'], ['stat3_label', 'Label 3'], ['stat4_num', 'Stat 4'], ['stat4_label', 'Label 4']],
    'Footer & Social' => [['footer_about', 'About text', 'textarea'], ['footer1_title', 'Column 1 title'], ['footer1_links', 'Column 1 links', 'lines', $L], ['footer2_title', 'Column 2 title'], ['footer2_links', 'Column 2 links', 'lines', '{categories} = auto list'],
      ['footer3_title', 'Column 3 title'], ['footer3_links', 'Column 3 links', 'lines', $L], ['footer4_title', 'Column 4 title'], ['footer4_links', 'Column 4 links', 'lines', $L], ['footer_copy', 'Copyright ({year} = current year)'],
      ['social_x', 'X / Twitter URL', 'url'], ['social_facebook', 'Facebook URL', 'url'], ['social_instagram', 'Instagram URL', 'url'], ['social_youtube', 'YouTube URL', 'url'], ['social_linkedin', 'LinkedIn URL', 'url']],
    'AI & Tracking' => [['ai_api_key', 'Claude API key (from console.anthropic.com)', 'password', s('ai_api_key') ? 'Saved ✓ — leave blank to keep' : 'Required for AI Writer'], ['ai_model', 'AI model'], ['ai_words', 'Default article length (words)', 'number'],
      ['ai_language', 'Article language', 'text', 'e.g. English, Hindi, Hinglish'], ['ai_tone', 'Writing tone'], ['cta_eyebrow', 'Affiliate box small heading'],
      ['seo_ga_id', 'Google Analytics 4 Measurement ID (G-XXXXXXX)', 'text', 'affiliate clicks are sent as event "affiliate_click"'], ['track_clicks', 'Count affiliate clicks in this dashboard', 'check']],
    'SEO' => [['seo_home_title', 'Homepage SEO title'], ['seo_home_desc', 'Homepage meta description', 'textarea'], ['seo_title_sep', 'Title separator'], ['seo_og_image', 'Default social share image', 'image'],
      ['seo_twitter', 'Twitter @handle'], ['seo_org_name', 'Organization name (schema)'], ['seo_org_logo', 'Organization logo (schema)', 'image'],
      ['seo_google_verify', 'Google Search Console verification code'], ['seo_bing_verify', 'Bing verification code'],
      ['robots_extra', 'Extra robots.txt lines', 'code'], ['head_code', 'Code in <head> (AdSense, pixels…)', 'code'], ['footer_code', 'Code before </body>', 'code'], ['seo_noindex_site', 'Hide whole site from Google (staging only!)', 'check']],
  ];
  $tab = isset($tabs[$_GET['tab'] ?? '']) ? $_GET['tab'] : 'General'; ?>
  <h1 class="wp-heading">Settings</h1>
  <nav class="tabs"><?php foreach ($tabs as $t => $_): ?><a class="<?= $t === $tab ? 'on' : '' ?>" href="?a=settings&tab=<?= urlencode($t) ?>"><?= e($t) ?></a><?php endforeach; ?></nav>
  <form method="post" class="box settings"><?= csrf_field() ?><input type="hidden" name="tab" value="<?= e($tab) ?>">
    <?php foreach ($tabs[$tab] as $f): [$k, $label] = $f; $type = $f[2] ?? 'text'; $help = is_string($f[3] ?? null) ? $f[3] : '';
      if ($type === 'select') { echo '<label>' . e($label) . '<select name="s[' . $k . ']">'; foreach ($f[3] as $v => $l) echo '<option value="' . e($v) . '"' . ((string)$v === s($k) ? ' selected' : '') . '>' . e($l) . '</option>'; echo '</select></label>'; continue; }
      echo field("s[$k]", e($label), $type, $k === 'ai_api_key' ? '' : s($k), $help); endforeach; ?>
    <button class="button button-primary" style="margin-top:16px">Save <?= e($tab) ?></button>
  </form>
  <script>window.PRODUCTS=null;</script><script src="/admin/editor.js?v=3"></script>
<?php } elseif ($a === 'users') {
  $edit = isset($_GET['id']) ? db()->query('SELECT * FROM users WHERE id=' . (int)$_GET['id'])->fetch() : null;
  $u = $edit ?: ['id' => 0, 'username' => '', 'name' => '', 'email' => '', 'role' => 'editor']; ?>
  <h1 class="wp-heading">Users</h1>
  <div class="cols"><div><table class="wp-list-table"><thead><tr><th class="col-title">Username</th><th>Name</th><th>Email</th><th>Role</th><th class="num">Posts</th></tr></thead><tbody>
    <?php foreach (db()->query('SELECT u.*, (SELECT COUNT(*) FROM posts p WHERE p.author_id=u.id) n FROM users u ORDER BY u.id') as $r): ?><tr>
      <td class="col-title"><strong><a class="row-title" href="?a=users&id=<?= $r['id'] ?>"><?= e($r['username']) ?></a><?= $r['id'] == $uid ? ' <span class="post-state">— You</span>' : '' ?></strong>
        <div class="row-actions"><a href="?a=users&id=<?= $r['id'] ?>">Edit</a><?php if ($r['id'] != $uid): ?> | <form method="post" action="?a=user-del" onsubmit="return confirm('Delete this user? Their posts move to you.')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="link trash">Delete</button></form><?php endif; ?></div></td>
      <td><?= e($r['name']) ?></td><td><?= e($r['email']) ?></td><td><span class="pill"><?= e(ucfirst($r['role'] ?: 'admin')) ?></span></td><td class="num"><?= $r['n'] ?></td></tr><?php endforeach; ?>
  </tbody></table>
  <p class="muted"><b>Administrator</b>: everything. <b>Editor</b>: posts, pages, categories, AI Writer and own profile — no settings, users, subscribers or redirects.</p></div>
  <form method="post" action="?a=user-save" class="box"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $u['id'] ?>"><h3><?= $edit ? 'Edit user' : 'Add New User' ?></h3>
    <?= field('username', 'Username', 'text', $u['username']) ?><?= field('name', 'Display name', 'text', $u['name']) ?><?= field('email', 'Email', 'email', $u['email']) ?>
    <label>Role<select name="role"><option value="editor">Editor</option><option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>Administrator</option></select></label>
    <?= field('password', $edit ? 'New password (leave blank to keep)' : 'Password (min 10)', 'password') ?>
    <button class="button button-primary block" style="margin-top:16px"><?= $edit ? 'Update User' : 'Add New User' ?></button><?php if ($edit): ?><p><a href="?a=users">+ Add new instead</a></p><?php endif; ?></form></div>
<?php } elseif ($a === 'ai') { $f = $_SESSION['ai_form'] ?? []; $hasKey = s('ai_api_key') !== ''; ?>
  <div class="page-head"><div><h1>AI Writer</h1><p>Give a title and your affiliate link — a full, SEO-ready article is written and the affiliate link is added automatically.</p></div></div>
  <?php if (!$hasKey): ?><div class="notice" style="border-left-color:#f59e0b">Add your Claude API key first: <?= $ROLE === 'admin' ? '<a href="?a=settings&tab=AI+%26+Tracking">Settings → AI &amp; Tracking</a>' : 'ask an administrator to add it in Settings → AI &amp; Tracking' ?>.</div><?php endif; ?>
  <div class="cols"><form method="post" action="?a=ai-gen" class="card" onsubmit="this.querySelector('button').disabled=true;this.querySelector('button').textContent='Writing… this takes about a minute';"><?= csrf_field() ?>
    <?= field('title', 'Blog title *', 'text', $f['title'] ?? '', 'e.g. Lycamobile Review 2026: Plans, Coverage & Is It Worth It?') ?>
    <div class="g2"><?= field('aff_name', 'Brand / product name', 'text', $f['aff_name'] ?? '', 'e.g. Lycamobile') ?><?= field('aff_url', 'Affiliate link', 'url', $f['aff_url'] ?? '', 'https://…') ?></div>
    <div class="g2"><?= field('keyword', 'Focus keyword (SEO)', 'text', $f['keyword'] ?? '') ?><?= field('words', 'Length (words)', 'number', (string)($f['words'] ?? s('ai_words'))) ?></div>
    <label>Category<select name="category_id"><option value="">— None —</option><?php foreach (categories() as $c): ?><option value="<?= $c['id'] ?>" <?= ($f['category_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></label>
    <?= field('notes', 'Extra instructions / facts (optional)', 'textarea', $f['notes'] ?? '', 'prices, features, pros/cons, audience, country…') ?>
    <button class="button button-primary button-large block" style="margin-top:18px" <?= $hasKey ? '' : 'disabled' ?>>✨ Write Article</button></form>
    <div class="box"><h3>What you get</h3><ul class="plain"><li>✓ Full article with numbered H2 sections, intro & FAQ</li><li>✓ Affiliate link placed naturally in the text</li><li>✓ "Ready to explore? → Visit Brand" box at the end</li><li>✓ SEO title, meta description, tags & excerpt</li><li>✓ Saved as a <b>Draft</b> — you review before publishing</li></ul>
      <p class="muted">Tip: add real facts (prices, plans, your experience) in “Extra instructions” — AI can make mistakes, so always check details before publishing.</p></div></div>
<?php } elseif ($a === 'profile') { $u = db()->query('SELECT * FROM users WHERE id=' . $uid)->fetch(); ?>
  <h1 class="wp-heading">Profile</h1>
  <form method="post" class="box narrow-form" id="editor"><?= csrf_field() ?>
    <?= field('name', 'Display name (author)', 'text', $u['name']) ?><?= field('bio', 'Author bio', 'textarea', $u['bio']) ?><?= field('avatar', 'Photo', 'image', $u['avatar']) ?>
    <h3 style="margin-top:20px">Change password</h3><?= field('old', 'Current password', 'password') ?><?= field('new', 'New password (min 10)', 'password') ?>
    <button class="button button-primary" style="margin-top:16px">Save</button></form>
  <script>window.PRODUCTS=null;</script><script src="/admin/editor.js?v=3"></script>
<?php } elseif ($a === 'subs') { $rows = db()->query('SELECT * FROM subscribers ORDER BY created_at DESC')->fetchAll(); ?>
  <h1 class="wp-heading">Subscribers <a class="page-title-action" href="?a=export">Export CSV</a></h1><p class="muted"><?= count($rows) ?> subscribers</p>
  <table class="wp-list-table"><tr><th>Email</th><th>Date</th><th></th></tr><?php foreach ($rows as $r): ?><tr><td><?= e($r['email']) ?></td><td><?= e(nice_date($r['created_at'])) ?></td>
    <td><form method="post" action="?a=sub-del"><?= csrf_field() ?><input type="hidden" name="email" value="<?= e($r['email']) ?>"><button class="link danger">Remove</button></form></td></tr><?php endforeach; ?></table>
<?php } elseif ($a === 'redirects') { ?>
  <h1 class="wp-heading">Redirects</h1><p class="muted">Old URL → new URL. Created automatically when you change a published post's URL.</p>
  <form method="post" class="inline"><?= csrf_field() ?><input name="src" placeholder="/old-url" required><input name="dest" placeholder="/new-url or https://…" required><button class="button button-primary">Add</button></form>
  <table class="wp-list-table"><tr><th>From</th><th>To</th><th></th></tr><?php foreach (db()->query('SELECT * FROM redirects ORDER BY src') as $r): ?><tr><td><?= e($r['src']) ?></td><td><?= e($r['dest']) ?></td>
    <td><form method="post"><?= csrf_field() ?><input type="hidden" name="del" value="<?= e($r['src']) ?>"><button class="link danger">Delete</button></form></td></tr><?php endforeach; ?></table>
<?php }
echo '</main></div><script>window.CSRF=' . json_encode(csrf()) . ';</script></body></html>';

/* ---------- view helpers ---------- */
function admin_head(string $a, string $flash): void {
  global $ROLE; $nav = [['dashboard', 'home', 'Dashboard'], ['posts', 'doc', 'Posts'], ['ai', 'bulb', 'AI Writer'], ['pages', 'book', 'Pages'], ['cats', 'grid', 'Categories'], ['subs', 'mail', 'Subscribers'], '-',
    ['users', 'users', 'Users'], ['settings', 'gear', 'Settings'], ['redirects', 'arrow', 'Redirects'], ['profile', 'user', 'Profile']];
  if ($ROLE !== 'admin') $nav = array_values(array_filter($nav, fn($n) => $n === '-' || !in_array($n[0], ['subs', 'users', 'settings', 'redirects'], true)));
  $cur = $a === 'edit' ? ((($_GET['type'] ?? '') === 'page' || (isset($_GET['id']) && db()->query('SELECT type FROM posts WHERE id=' . (int)$_GET['id'])->fetchColumn() === 'page')) ? 'pages' : 'posts') : ($a === 'quickdraft' ? 'dashboard' : $a);
  $me = db()->query('SELECT name, username, avatar FROM users WHERE id=' . (int)$_SESSION['uid'])->fetch(); $nm = $me['name'] ?: $me['username'];
  $titles = ['dashboard' => 'Dashboard', 'posts' => 'Posts', 'pages' => 'Pages', 'cats' => 'Categories', 'subs' => 'Subscribers', 'settings' => 'Settings', 'redirects' => 'Redirects', 'profile' => 'Profile', 'users' => 'Users', 'ai' => 'AI Writer']; ?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title><?= e($titles[$cur] ?? 'Admin') ?> · <?= e(s('site_name')) ?> CMS</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"><link rel="stylesheet" href="/admin/admin.css?v=5"></head><body class="cms">
<aside id="sidebar">
  <a class="sb-brand" href="?a=dashboard"><span class="sb-logo"><?= tf_icon('users', 22) ?></span><span><?= e(s('site_name')) ?><br>CMS</span></a>
  <nav><?php foreach ($nav as $n): if ($n === '-') { echo '<hr>'; continue; } [$k, $ic, $l] = $n; ?><a class="<?= $cur === $k ? 'on' : '' ?>" href="?a=<?= $k ?>"><?= tf_icon($ic, 19) ?><span><?= $l ?></span></a><?php endforeach; ?>
    <a href="/" target="_blank"><?= tf_icon('globe', 19) ?><span>Visit Site</span><i class="ext">↗</i></a></nav>
  <div class="sb-card"><span class="crown"><?= tf_icon('trophy', 18) ?></span><b><?= e(s('site_name')) ?> CMS v1.0</b><p>Manage your blog, content and audience easily.</p></div>
</aside>
<div id="main">
  <header id="topbar">
    <button class="tb-btn" onclick="document.body.classList.toggle('sb-open')" aria-label="Menu"><?= tf_icon('menu', 22) ?></button>
    <form class="tb-search" action="/admin/"><input type="hidden" name="a" value="posts"><?= tf_icon('search', 18) ?><input type="search" name="q" placeholder="Search posts, pages or categories…" aria-label="Search"></form>
    <a class="tb-btn bell" href="?a=subs" aria-label="Subscribers"><?= tf_icon('mail', 20) ?><?php if ((int)db()->query("SELECT COUNT(*) FROM subscribers WHERE created_at >= datetime('now','-7 days')")->fetchColumn()): ?><i></i><?php endif; ?></a>
    <details class="tb-user"><summary><?= $me['avatar'] ? '<img src="' . e($me['avatar']) . '" alt="">' : '<span class="av">' . e(strtoupper(mb_substr($nm, 0, 1))) . '</span>' ?><span class="who"><b><?= e($nm) ?></b><small><?= $ROLE === 'admin' ? 'Administrator' : 'Editor' ?></small></span><?= tf_icon('chev-d', 16) ?></summary>
      <div class="menu"><a href="?a=profile">Profile</a><?php if ($ROLE === 'admin'): ?><a href="?a=settings">Settings</a><a href="?a=users">Users</a><?php endif; ?><a href="/" target="_blank">Visit Site</a><a href="?a=logout" class="danger">Log Out</a></div></details>
  </header>
  <main id="content"><?php if ($flash): ?><div class="notice"><?= $flash ?></div><?php endif;
}
function auth_page(string $title, string $btn, string $err): void { ?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title><?= e($title) ?></title><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"><link rel="stylesheet" href="/admin/admin.css?v=5"></head>
<body class="login"><div class="login-wrap"><div class="login-logo"><span class="sb-logo"><?= tf_icon('users', 22) ?></span><?= e(s('site_name')) ?> CMS</div><form method="post" class="auth-box"><h1><?= e($title) ?></h1><?php if ($err): ?><div class="flash err"><?= e($err) ?></div><?php endif; ?>
<?= csrf_field() ?><label>Username<input name="username" required autofocus autocomplete="username"></label><label>Password<input type="password" name="password" required autocomplete="current-password"></label><button class="button button-primary button-large block"><?= e($btn) ?></button></form><p class="backto"><a href="/">← Go to <?= e(s('site_name')) ?></a></p></div></body></html>
<?php }
function seed(): void {
  $pdo = db(); $t = now();
  foreach ([['Technology', 'laptop'], ['Travel', 'plane'], ['Health & Wellness', 'heart'], ['Lifestyle', 'leaf'], ['Reviews', 'star'], ['Business', 'briefcase'], ['Education', 'cap'], ['How To', 'gear']] as $i => [$n, $ic])
    $pdo->prepare('INSERT OR IGNORE INTO categories (name, slug, icon, sort, show_home) VALUES (?,?,?,?,?)')->execute([$n, slugify($n), $ic, $i, $n === 'How To' ? 0 : 1]);
  $pdo->prepare("INSERT OR REPLACE INTO settings (k, v) VALUES ('howto_cat', ?)")->execute([(string)$pdo->query("SELECT id FROM categories WHERE slug='how-to'")->fetchColumn()]);
  $name = s('site_name'); $mail = 'contact@' . parse_url(SITE_URL, PHP_URL_HOST);
  $pages = ['About Us' => ['about', "<p>$name is an independent blog sharing honest reviews, practical guides and real-life ideas on technology, travel, lifestyle, wellness and more.</p>"],
    'Contact' => ['contact', "<p>Questions, feedback or partnership enquiries? Email us at <a href=\"mailto:$mail\">$mail</a>.</p>"],
    'Affiliate Disclosure' => ['affiliate-disclosure', "<p>$name participates in affiliate programs, including the Amazon Associates Program. When you buy through links on our site we may earn a commission at no extra cost to you. As an Amazon Associate we earn from qualifying purchases. This never influences our reviews or rankings.</p>"],
    'Privacy Policy' => ['privacy-policy', "<p>We respect your privacy. This site may use cookies and third-party services (analytics, advertising and affiliate partners) that collect anonymous usage data. Newsletter emails are used only to send our newsletter and you can unsubscribe any time. We do not sell personal information. Contact: $mail</p>"],
    'Terms of Use' => ['terms', "<p>All content on $name is for general information only. While we strive for accuracy, we make no guarantees. Prices and availability of products change; check the retailer before buying.</p>"]];
  foreach ($pages as $title => [$slug, $html]) $pdo->prepare("INSERT OR IGNORE INTO posts (type,title,slug,content,status,published_at,updated_at) VALUES ('page',?,?,?,'published',?,?)")->execute([$title, $slug, $html, $t, $t]);
}

/* ---------- AI article writer (Anthropic Claude API) ---------- */
function ai_write(array $in): array {
  $key = s('ai_api_key'); if ($key === '') return [null, 'No API key set (Settings → AI & Tracking).'];
  if (!function_exists('curl_init')) return [null, 'PHP curl extension is missing on the server.'];
  $brand = $in['aff_name'] ?: 'the product/service';
  $aff = $in['aff_url'] ? "Where it helps the reader, link the brand name or a natural call-to-action phrase 2–3 times in the body by wrapping the words like {{aff}}Visit {$brand}{{/aff}} — do NOT write any real URL for it. Do NOT add a separate final call-to-action box; the site adds one automatically." : 'There is no affiliate link; do not add {{aff}} markers.';
  $prompt = "Write a complete, original, helpful blog article.\n\nTitle: {$in['title']}\nBrand/product: {$brand}\nFocus keyword: " . ($in['keyword'] ?: '(choose the best one)') .
    "\nLength: about {$in['words']} words\nLanguage: " . s('ai_language') . "\nTone: " . s('ai_tone') . "\nExtra notes from the editor (treat as facts to use): " . ($in['notes'] ?: 'none') .
    "\n\nRules:\n- HTML only for the body: <p>, <h2>, <h3>, <ul>/<ol>/<li>, <strong>, <em>, <table> with <thead>/<tbody>, <blockquote>. No <h1>, no inline styles, no scripts, no images.\n- Start with a 2–3 paragraph introduction (no heading), then 8–12 <h2> sections (do not number them yourself — the site numbers them), include a pros & cons list, a comparison or quick-facts table where useful, an FAQ section with 4–6 questions as <h3>, and a short conclusion.\n- Use the focus keyword naturally in the first paragraph and in 2–3 headings.\n- Do not invent specific prices, statistics or quotes unless given in the notes; describe them generally instead.\n- {$aff}\n\nReturn ONLY valid JSON (no markdown fences) with these keys: " .
    '{"content_html": "...", "excerpt": "1–2 sentence summary", "meta_title": "max 60 chars", "meta_description": "140–160 chars", "focus_keyword": "...", "tags": ["3-6 short tags"], "cta_description": "one sentence describing the brand for the affiliate box", "slug": "short-url-slug"}';
  $body = json_encode(['model' => s('ai_model') ?: 'claude-sonnet-5-5', 'max_tokens' => 16000, 'system' => 'You are an expert SEO content writer for an affiliate review blog. You write accurate, people-first content and always follow the output format exactly.',
    'messages' => [['role' => 'user', 'content' => $prompt]]]);
  $ch = curl_init('https://api.anthropic.com/v1/messages');
  curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $body, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 280, CURLOPT_CONNECTTIMEOUT => 15,
    CURLOPT_HTTPHEADER => ['content-type: application/json', 'x-api-key: ' . $key, 'anthropic-version: 2023-06-01']]);
  $res = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); $cerr = curl_error($ch); curl_close($ch);
  if ($res === false) return [null, 'Could not reach the AI service: ' . $cerr];
  $j = json_decode($res, true);
  if ($code !== 200) return [null, ($j['error']['message'] ?? 'HTTP ' . $code)];
  $text = ''; foreach ($j['content'] ?? [] as $b) if (($b['type'] ?? '') === 'text') $text .= $b['text'];
  $text = trim(preg_replace('/^```(?:json)?|```$/m', '', trim($text)));
  $a = strpos($text, '{'); $b = strrpos($text, '}');
  $out = ($a !== false && $b !== false) ? json_decode(substr($text, $a, $b - $a + 1), true) : null;
  if (!is_array($out) || empty($out['content_html'])) return [null, ($j['stop_reason'] ?? '') === 'max_tokens' ? 'Article was too long — try fewer words.' : 'AI returned an unexpected format. Please try again.'];
  return [$out, null];
}
