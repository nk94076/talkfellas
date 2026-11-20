<?php
require_once __DIR__ . '/config.php';
function db(): PDO {
  static $pdo;
  if ($pdo) return $pdo;
  if (!is_dir(DATA_DIR)) mkdir(DATA_DIR, 0750, true);
  $pdo = new PDO('sqlite:' . DATA_DIR . '/site.db', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
  $pdo->exec('PRAGMA foreign_keys = ON');
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY, username TEXT UNIQUE NOT NULL, pass_hash TEXT NOT NULL);
    CREATE TABLE IF NOT EXISTS categories (id INTEGER PRIMARY KEY, name TEXT NOT NULL, slug TEXT UNIQUE NOT NULL, sort INTEGER DEFAULT 0);
    CREATE TABLE IF NOT EXISTS posts (
      id INTEGER PRIMARY KEY, type TEXT NOT NULL DEFAULT 'post', title TEXT NOT NULL, slug TEXT UNIQUE NOT NULL,
      excerpt TEXT DEFAULT '', intro TEXT DEFAULT '', content TEXT DEFAULT '', image TEXT DEFAULT '',
      category_id INTEGER REFERENCES categories(id) ON DELETE SET NULL, status TEXT NOT NULL DEFAULT 'draft',
      featured INTEGER DEFAULT 0, meta_title TEXT DEFAULT '', meta_desc TEXT DEFAULT '',
      created_at TEXT NOT NULL, updated_at TEXT NOT NULL);
    CREATE TABLE IF NOT EXISTS products (
      id INTEGER PRIMARY KEY, post_id INTEGER NOT NULL REFERENCES posts(id) ON DELETE CASCADE, rank INTEGER NOT NULL,
      name TEXT NOT NULL, image TEXT DEFAULT '', score TEXT DEFAULT '', badge TEXT DEFAULT '', features TEXT DEFAULT '',
      link TEXT DEFAULT '', store TEXT DEFAULT 'Amazon', button TEXT DEFAULT 'Check Price');
  ");
  return $pdo;
}
