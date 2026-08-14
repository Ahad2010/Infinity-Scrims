<?php
/** Owner ke liye: notification bhejte waqt "Specific Players" list — search ke sath */
require_once __DIR__ . '/../_bootstrap.php';
$owner = Auth::requireOwner();

$search = trim(input('search', ''));
if ($search !== '') {
    $like = '%' . $search . '%';
    $users = DB::all(
        "SELECT id, username, email FROM users
         WHERE role='user' AND status='active' AND (username LIKE ? OR email LIKE ?)
         ORDER BY username ASC LIMIT 200", [$like, $like]);
} else {
    $users = DB::all(
        "SELECT id, username, email FROM users WHERE role='user' AND status='active'
         ORDER BY username ASC LIMIT 200");
}

ok(['users' => $users]);