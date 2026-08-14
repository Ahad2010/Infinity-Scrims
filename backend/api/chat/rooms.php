<?php
require_once __DIR__ . '/../_bootstrap.php';
$u = Auth::requireLogin();

// User ke team rooms + un scrims ke rooms jahan confirmed booking hai
$rooms = DB::all(
    "SELECT r.id, r.type, r.name, r.scrim_id, r.team_id,
            (SELECT message FROM chat_messages WHERE room_id=r.id ORDER BY id DESC LIMIT 1) AS last_message,
            (SELECT created_at FROM chat_messages WHERE room_id=r.id ORDER BY id DESC LIMIT 1) AS last_at
     FROM chat_rooms r
     WHERE (r.type='team' AND r.team_id IN (SELECT team_id FROM team_members WHERE user_id=?))
        OR (r.type='scrim' AND r.scrim_id IN (
              SELECT scrim_id FROM bookings WHERE booked_by=? AND status='confirmed'))
     ORDER BY last_at DESC", [$u['id'], $u['id']]);

foreach ($rooms as &$r) $r['last_at_txt'] = $r['last_at'] ? time_ago($r['last_at']) : null;
ok(['rooms' => $rooms]);
