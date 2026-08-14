<?php
/** Owner: ban or unban a single user's account */
require_once __DIR__ . '/../_bootstrap.php';
require_method('POST');
Auth::requireOwner();
Auth::verifyCsrf();

$d = require_fields(['user_id', 'action']); // action: ban | unban
$userId = (int) $d['user_id'];
$action = $d['action'] === 'unban' ? 'unban' : 'ban';

$user = DB::one("SELECT id, role FROM users WHERE id=?", [$userId]);
if (!$user) fail('Account not found.', 404);
if ($user['role'] === 'owner') fail('You cannot ban an owner account.', 403);

DB::update('users', ['status' => $action === 'ban' ? 'banned' : 'active'], 'id = :id', ['id' => $userId]);

ok([], $action === 'ban' ? 'User banned.' : 'User unbanned.');