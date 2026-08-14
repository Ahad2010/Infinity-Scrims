<?php
require_once __DIR__ . '/_bootstrap.php';
$u = Auth::requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf();
    $id = (int) input('id', 0);
    if ($id) DB::run("UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?", [$id, $u['id']]);
    else     DB::run("UPDATE notifications SET is_read=1 WHERE user_id=?", [$u['id']]);
    ok([], 'Marked as read.');
}

$rows = DB::all("SELECT * FROM notifications WHERE user_id=? ORDER BY id DESC LIMIT 50", [$u['id']]);
foreach ($rows as &$r) $r['ago'] = time_ago($r['created_at']);

ok([
    'notifications' => $rows,
    'unread' => (int) DB::val("SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0", [$u['id']]),
]);
