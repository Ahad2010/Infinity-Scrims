<?php
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/_access.php';
Auth::requireLogin();

$roomId = (int) input('room_id');
$after  = (int) input('after', 0);   // polling ke liye: last message id

if (!can_access_room($roomId)) fail('You are not part of this group.', 403);

$msgs = DB::all(
    "SELECT m.id, m.message, m.is_system, m.created_at, u.id AS user_id, u.username, u.avatar
     FROM chat_messages m JOIN users u ON u.id=m.user_id
     WHERE m.room_id=? AND m.id > ?
     ORDER BY m.id ASC LIMIT 100", [$roomId, $after]);

$me = Auth::id();
foreach ($msgs as &$m) {
    $m['is_mine'] = ((int)$m['user_id'] === $me);
    $m['time']    = date('h:i A', strtotime($m['created_at']));
}
ok(['messages' => $msgs, 'last_id' => $msgs ? end($msgs)['id'] : $after]);
