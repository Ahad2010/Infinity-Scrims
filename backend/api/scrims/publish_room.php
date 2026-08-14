<?php
require_once __DIR__ . '/../_bootstrap.php';
require_method('POST');
Auth::requireOwner();
Auth::verifyCsrf();

$d = require_fields(['scrim_id', 'room_id', 'room_password']);
$scrimId = (int)$d['scrim_id'];

$scrim = DB::one("SELECT id, title FROM scrims WHERE id=?", [$scrimId]);
if (!$scrim) fail('Scrim not found.', 404);

DB::update('scrims', [
    'room_id'       => $d['room_id'],
    'room_password' => $d['room_password'],
    'status'        => 'live',
], 'id = :id', ['id' => $scrimId]);

// Confirmed teams ko notify + group chat mein system message
$teams = DB::all("SELECT DISTINCT team_id FROM bookings WHERE scrim_id=? AND status='confirmed'", [$scrimId]);
foreach ($teams as $t) {
    notify_team((int)$t['team_id'], 'scrim', 'Room ID publish ho gayi',
        $scrim['title'] . ' — group chat mein Room ID aur password check karein.',
        'scrim.php?id=' . $scrimId);
}

$room = DB::one("SELECT id FROM chat_rooms WHERE type='scrim' AND scrim_id=?", [$scrimId]);
if ($room) system_message((int)$room['id'], Auth::id(),
    "Room ID: {$d['room_id']}  |  Password: {$d['room_password']}");

ok([], 'Room details published.');
