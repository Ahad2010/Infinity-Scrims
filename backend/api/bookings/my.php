<?php
/**
 * Team-membership-based — shows a booking to EVERY member of the team
 * that booked (not just the captain who clicked "Book"), and includes
 * who on the team actually made the booking.
 */
require_once __DIR__ . '/../_bootstrap.php';
$u = Auth::requireLogin();

$filter = input('filter', 'all');   // all | pending | confirmed | past
$where  = ["tm.user_id = ?"];
$params = [$u['id']];

if ($filter === 'pending')   { $where[] = "b.status='pending'"; }
if ($filter === 'confirmed') { $where[] = "b.status='confirmed' AND s.match_at >= NOW()"; }
if ($filter === 'past')      { $where[] = "s.match_at < NOW()"; }
$w = implode(' AND ', $where);

$rows = DB::all(
    "SELECT b.id, b.status, b.amount, b.created_at, b.booked_by,
sl.slot_number, s.id AS scrim_id, s.title, s.banner, s.mode, s.map, s.match_at, s.group_link, s.live_stream_link,
            g.name AS game_name, t.name AS team_name,
            bu.username AS booked_by_username,
            p.status AS payment_status
     FROM bookings b
     JOIN team_members tm ON tm.team_id = b.team_id
     JOIN slots sl ON sl.id = b.slot_id
     JOIN scrims s ON s.id = b.scrim_id
     JOIN games  g ON g.id = s.game_id
     JOIN teams  t ON t.id = b.team_id
     JOIN users  bu ON bu.id = b.booked_by
     LEFT JOIN payments p ON p.booking_id = b.id
     WHERE $w
     ORDER BY s.match_at DESC", $params);

foreach ($rows as &$r) {
    $r['amount_txt'] = money($r['amount']);
    $r['needs_payment'] = ($r['status'] === 'pending' && $r['payment_status'] === null);
    // Frontend can show "Booked by you" vs "Booked by <name>" using this.
    $r['booked_by_me'] = ((int)$r['booked_by'] === (int)$u['id']);
    if ($r['banner']) $r['banner'] = img_url($r['banner']);
// Group link / stream link only make sense once the team is actually confirmed in.
    if ($r['status'] !== 'confirmed') { $r['group_link'] = null; $r['live_stream_link'] = null; }
}
ok(['bookings' => $rows]);