<?php
/** Owner ke apne banaye hue scrims — "Scrims" sidebar page ke liye */
require_once __DIR__ . '/../_bootstrap.php';
$owner = Auth::requireOwner();

$status = input('status', 'all');
$where = ["s.created_by = ?"];
$params = [$owner['id']];
if ($status !== 'all') { $where[] = "s.status = ?"; $params[] = $status; }
$w = implode(' AND ', $where);

$rows = DB::all(
    "SELECT s.id, s.title, s.banner, s.match_at, s.total_slots, s.price_per_slot, s.status, s.visibility,
            g.name AS game_name,
            (SELECT COUNT(*) FROM slots WHERE scrim_id=s.id AND status IN ('booked','held')) AS booked_slots,
            (SELECT COALESCE(SUM(p.amount),0) FROM payments p JOIN bookings b ON b.id=p.booking_id
             WHERE b.scrim_id=s.id AND p.status='approved') AS revenue,
            (SELECT COUNT(*) FROM results WHERE scrim_id=s.id AND status='published') AS has_published_result
     FROM scrims s JOIN games g ON g.id=s.game_id
     WHERE $w
     ORDER BY s.match_at DESC", $params);

foreach ($rows as &$r) {
    $r['revenue_txt'] = money($r['revenue']);
    $r['price_txt']   = money($r['price_per_slot']);
    $r['has_published_result'] = (int) $r['has_published_result'] > 0;
    if ($r['banner']) $r['banner'] = img_url($r['banner']);
}

ok(['scrims' => $rows]);