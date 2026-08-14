<?php
/** Owner: Payment Approval Queue */
require_once __DIR__ . '/../_bootstrap.php';
Auth::requireOwner();

$status = input('status', 'pending');   // pending | approved | rejected

$rows = DB::all(
    "SELECT p.id, p.method, p.sender_number, p.txn_id, p.amount, p.screenshot,
            p.status, p.created_at, p.reject_reason,
            u.username, u.email, u.avatar,
            sl.slot_number, s.title AS scrim_title, s.match_at, s.status AS scrim_status,
            s.prize_top1, s.prize_top2, s.prize_top3, t.name AS team_name
     FROM payments p
     JOIN bookings b ON b.id = p.booking_id
     JOIN slots  sl ON sl.id = b.slot_id
     JOIN scrims  s ON s.id = b.scrim_id
     JOIN teams   t ON t.id = b.team_id
     JOIN users   u ON u.id = p.user_id
     WHERE p.status = ?
     ORDER BY p.created_at ASC", [$status]);

foreach ($rows as &$r) {
    $r['amount_txt'] = money($r['amount']);
    $r['waiting']    = time_ago($r['created_at']);
    $r['screenshot_url'] = img_url($r['screenshot']);

    // Scrim-level prize pool / profit context (profit only means something once the scrim has started)
    $prizePool = (float)$r['prize_top1'] + (float)$r['prize_top2'] + (float)$r['prize_top3'];
    $r['prize_pool_txt'] = money($prizePool);
    $started = in_array($r['scrim_status'], ['live', 'completed'], true);
    $scrimRevenue = (float) DB::val(
        "SELECT COALESCE(SUM(p2.amount),0) FROM payments p2
         JOIN bookings b2 ON b2.id = p2.booking_id
         WHERE b2.scrim_id = (SELECT scrim_id FROM bookings WHERE id = (SELECT booking_id FROM payments WHERE id = ?)) AND p2.status='approved'",
        [$r['id']]);
    $r['scrim_profit_txt'] = $started ? money(round($scrimRevenue - $prizePool, 2)) : '—';
}
$stats = [
    'pending'  => (int) DB::val("SELECT COUNT(*) FROM payments WHERE status='pending'"),
    'approved_today' => (int) DB::val("SELECT COUNT(*) FROM payments WHERE status='approved' AND DATE(reviewed_at)=CURDATE()"),
    'rejected_today' => (int) DB::val("SELECT COUNT(*) FROM payments WHERE status='rejected' AND DATE(reviewed_at)=CURDATE()"),
];

ok(['payments' => $rows, 'stats' => $stats]);