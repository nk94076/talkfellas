<?php
require_once __DIR__ . '/config.php';
function db(): PDO {
  static $pdo;
  if ($pdo) return $pdo;
  if (!is_dir(DATA_DIR)) mkdir(DATA_DIR, 0750, true);
  $pdo = new PDO('sqlite:' . DATA_DIR . '/site.db', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
  $pdo->exec('PRAGMA foreign_keys = ON; PRAGMA journal_mode = WAL;');
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY, username TEXT UNIQUE NOT NULL, pass_hash TEXT NOT NULL, name TEXT DEFAULT '', bio TEXT DEFAULT '', avatar TEXT DEFAULT '');
    CREATE TABLE IF NOT EXISTS settings (k TEXT PRIMARY KEY, v TEXT);
    CREATE TABLE IF NOT EXISTS categories (id INTEGER PRIMARY KEY, name TEXT NOT NULL, slug TEXT UNIQUE NOT NULL, description TEXT DEFAULT '',
      icon TEXT DEFAULT 'grid', image TEXT DEFAULT '', show_home INTEGER DEFAULT 1, sort INTEGER DEFAULT 0, meta_title TEXT DEFAULT '', meta_desc TEXT DEFAULT '');
    CREATE TABLE IF NOT EXISTS posts (
      id INTEGER PRIMARY KEY, type TEXT NOT NULL DEFAULT 'post', title TEXT NOT NULL, slug TEXT UNIQUE NOT NULL,
      excerpt TEXT DEFAULT '', intro TEXT DEFAULT '', content TEXT DEFAULT '', image TEXT DEFAULT '', image_alt TEXT DEFAULT '',
      category_id INTEGER REFERENCES categories(id) ON DELETE SET NULL, author_id INTEGER DEFAULT 1, status TEXT NOT NULL DEFAULT 'draft',
      in_hero INTEGER DEFAULT 0, is_pick INTEGER DEFAULT 0, tags TEXT DEFAULT '',
      meta_title TEXT DEFAULT '', meta_desc TEXT DEFAULT '', focus_kw TEXT DEFAULT '', canonical TEXT DEFAULT '', noindex INTEGER DEFAULT 0, og_image TEXT DEFAULT '',
      views INTEGER DEFAULT 0, views_week INTEGER DEFAULT 0, views_wk TEXT DEFAULT '',
      published_at TEXT NOT NULL, updated_at TEXT NOT NULL);
    CREATE INDEX IF NOT EXISTS ix_posts_pub ON posts(status, type, published_at);
    CREATE TABLE IF NOT EXISTS products (
      id INTEGER PRIMARY KEY, post_id INTEGER NOT NULL REFERENCES posts(id) ON DELETE CASCADE, rank INTEGER NOT NULL,
      name TEXT NOT NULL, image TEXT DEFAULT '', score TEXT DEFAULT '', badge TEXT DEFAULT '', features TEXT DEFAULT '',
      link TEXT DEFAULT '', store TEXT DEFAULT 'Amazon', button TEXT DEFAULT 'Check Price');
    CREATE TABLE IF NOT EXISTS subscribers (email TEXT PRIMARY KEY, created_at TEXT NOT NULL);
    CREATE TABLE IF NOT EXISTS redirects (src TEXT PRIMARY KEY, dest TEXT NOT NULL);
    CREATE TABLE IF NOT EXISTS clicks (id INTEGER PRIMARY KEY, post_id INTEGER, url TEXT, label TEXT, created_at TEXT NOT NULL);
    CREATE INDEX IF NOT EXISTS ix_clicks ON clicks(post_id, created_at);
  ");
  // add columns to older databases
  $add = ['users' => ['role' => "TEXT DEFAULT 'admin'", 'email' => "TEXT DEFAULT ''"],
          'posts' => ['aff_name' => "TEXT DEFAULT ''", 'aff_url' => "TEXT DEFAULT ''", 'aff_desc' => "TEXT DEFAULT ''", 'aff_btn' => "TEXT DEFAULT ''", 'numbered' => 'INTEGER DEFAULT 0', 'clicks' => 'INTEGER DEFAULT 0']];
  foreach ($add as $t => $cols) {
    $have = array_column($pdo->query("PRAGMA table_info($t)")->fetchAll(), 'name');
    foreach ($cols as $c => $def) if (!in_array($c, $have, true)) $pdo->exec("ALTER TABLE $t ADD COLUMN $c $def");
  }
  return $pdo;
}
