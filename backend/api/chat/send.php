<?php
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/_access.php';
require_method('POST');
$u = Auth::requireLogin();
Auth::verifyCsrf();

$d = require_fields(['room_id', 'message']);
$roomId = (int)$d['room_id'];
$text   = trim($d['message']);

if (!can_access_room($roomId)) fail('You cannot send messages in this group.', 403);
if (mb_strlen($text) > 1000) fail('Message bohat lamba hai (max 1000).', 422);

// Simple rate limit — 1 second mein 1 message
$last = DB::val("SELECT created_at FROM chat_messages WHERE room_id=? AND user_id=? ORDER BY id DESC LIMIT 1",
                [$roomId, $u['id']]);
if ($last && (time() - strtotime($last)) < 1) fail('Slow down! Please try again.', 429);

$id = DB::insert('chat_messages', ['room_id' => $roomId, 'user_id' => $u['id'], 'message' => $text]);
ok(['id' => $id], 'Sent');
