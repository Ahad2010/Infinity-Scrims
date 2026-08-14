<?php
require_once __DIR__ . '/../_bootstrap.php';

$id = (int) input('id');
if (!$id) fail('Scrim id is required.', 422);

$s = DB::one(
    "SELECT s.*, g.name AS game_name, g.slug AS game_slug, u.username AS host
     FROM scrims s JOIN games g ON g.id=s.game_id JOIN users u ON u.id=s.created_by
     WHERE s.id = ?", [$id]);
if (!$s) fail('Scrim not found.', 404);

$slots = DB::all(
    "SELECT sl.slot_number, sl.status, t.name AS team_name
     FROM slots sl LEFT JOIN teams t ON t.id = sl.team_id
     WHERE sl.scrim_id = ? ORDER BY sl.slot_number", [$id]);

$booked = 0;
foreach ($slots as &$sl) {
    if (in_array($sl['status'], ['booked','held'])) $booked++;
    // held ko user "booked" hi dekhega
    if ($sl['status'] === 'held') $sl['status'] = 'booked';
}

$s['booked_slots'] = $booked;
$s['remaining']    = (int)$s['total_slots'] - $booked;
$s['total_amount'] = (float)$s['price_per_slot'] + (float)$s['platform_fee'];
if ($s['banner']) $s['banner'] = UPLOAD_URL . '/' . $s['banner'];

// Room ID sirf confirmed booking wale ko
$s['room_id'] = $s['room_password'] = null;
$s['my_team_booking'] = null;
if (Auth::check()) {
    $hasBooking = DB::val(
        "SELECT b.id FROM bookings b WHERE b.scrim_id=? AND b.booked_by=? AND b.status='confirmed'",
        [$id, Auth::id()]);
    if (!$hasBooking && !Auth::isOwner()) { /* hidden */ }
    else {
        $row = DB::one("SELECT room_id, room_password FROM scrims WHERE id=?", [$id]);
        $s['room_id'] = $row['room_id'];
        $s['room_password'] = $row['room_password'];
    }

    // Team-membership-based check — har team member (sirf captain nahi) ko
    // "Already Booked" dikhna chahiye agar unki koi bhi team (jiska wo member
    // ya captain ho) is scrim mein pehle se slot book kar chuki hai.
    $s['my_team_booking'] = DB::one(
        "SELECT b.id AS booking_id, b.team_id, t.name AS team_name, b.status,
                sl.slot_number
         FROM bookings b
         JOIN teams t ON t.id = b.team_id
         JOIN team_members tm ON tm.team_id = b.team_id
         JOIN slots sl ON sl.id = b.slot_id
         WHERE b.scrim_id = ? AND tm.user_id = ? AND b.status IN ('pending','confirmed')
         ORDER BY b.id DESC LIMIT 1", [$id, Auth::id()]);
}

$accounts = DB::all("SELECT method, account_title, account_number FROM payout_accounts WHERE is_active=1");

ok(['scrim' => $s, 'slots' => $slots, 'payout_accounts' => $accounts]);