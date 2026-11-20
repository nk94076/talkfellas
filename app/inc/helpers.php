<?php
require_once __DIR__ . '/db.php';
function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function slugify(string $s): string { $s = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $s), '-')); return $s ?: 'item-' . time(); }
function now(): string { return gmdate('Y-m-d H:i:s'); }
function nice_date($d): string { return date('F j, Y', strtotime($d)); }
function url(string $path = ''): string { return '/' . ltrim($path, '/'); }
function safe_url($u): string { $u = trim((string)$u); return preg_match('#^(https?://|/)#i', $u) ? $u : '#'; }
function categories(): array { return db()->query('SELECT c.*, (SELECT COUNT(*) FROM posts p WHERE p.category_id=c.id AND p.status="published" AND p.type="post") n FROM categories c ORDER BY sort, name')->fetchAll(); }
function posts(string $where = '1', array $args = [], int $limit = 12, int $offset = 0): array {
  $st = db()->prepare("SELECT p.*, c.name cat_name, c.slug cat_slug FROM posts p LEFT JOIN categories c ON c.id=p.category_id
    WHERE p.status='published' AND p.type='post' AND ($where) ORDER BY p.featured DESC, p.updated_at DESC LIMIT $limit OFFSET $offset");
  $st->execute($args); return $st->fetchAll();
}
function count_posts(string $where = '1', array $args = []): int {
  $st = db()->prepare("SELECT COUNT(*) FROM posts p WHERE p.status='published' AND p.type='post' AND ($where)"); $st->execute($args); return (int)$st->fetchColumn();
}
// Admin-authored HTML: strip scripts/event handlers as a safety net.
function clean_html($html): string {
  $html = preg_replace('#<(script|style|iframe|object|embed)[^>]*>.*?</\1>#is', '', (string)$html);
  $html = preg_replace('#\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $html);
  return preg_replace('#(href|src)\s*=\s*(["\'])\s*javascript:[^"\']*\2#i', '$1="#"', $html);
}
function start_session(): void {
  if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS'])]);
    session_name('tfadmin'); session_start();
  }
}
function csrf(): string { start_session(); return $_SESSION['csrf'] ??= bin2hex(random_bytes(16)); }
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . csrf() . '">'; }
function check_csrf(): void { start_session(); if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { http_response_code(400); exit('Invalid request. Go back and try again.'); } }
