<?php
/** Owner ek notification bhejta hai — sab active users ko, ya sirf selected users ko */
require_once __DIR__ . '/../_bootstrap.php';
require_method('POST');
$owner = Auth::requireOwner();
Auth::verifyCsrf();

$d = require_fields(['title', 'body']);
$title = $d['title'];
$body  = $d['body'];
$type  = input('type', 'system');
$allowedTypes = ['booking', 'payment', 'scrim', 'result', 'team', 'support', 'system'];
if (!in_array($type, $allowedTypes, true)) $type = 'system';

if (mb_strlen($title) > 150) fail('Title cannot be more than 150 characters.', 422);
if (mb_strlen($body) > 255) fail('Message cannot be more than 255 characters.', 422);

$userIds = input('user_ids'); // array = specific players; null/empty = sab active users

if (is_array($userIds) && count($userIds) > 0) {
    $ids = array_values(array_unique(array_map('intval', $userIds)));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = DB::run(
        "INSERT INTO notifications (user_id, type, title, body)
         SELECT id, ?, ?, ? FROM users WHERE role='user' AND status='active' AND id IN ($placeholders)",
        array_merge([$type, $title, $body], $ids));
} else {
    $stmt = DB::run(
        "INSERT INTO notifications (user_id, type, title, body)
         SELECT id, ?, ?, ? FROM users WHERE role='user' AND status='active'",
        [$type, $title, $body]);
}

$sentCount = $stmt->rowCount();
if ($sentCount === 0) fail('No active user matched.', 404);

ok(['sent_count' => $sentCount], $sentCount . ' users notified.');