<?php
/** User ki apni payment/transaction history — dedicated endpoint (PRD gap #1) */
require_once __DIR__ . '/../_bootstrap.php';
$u = Auth::requireLogin();

$status = input('status');   // pending | approved | rejected | (empty = all)
$where  = ["p.user_id = ?"];
$params = [$u['id']];

if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
    $where[] = "p.status = ?";
    $params[] = $status;
}
$w = implode(' AND ', $where);

 
$rows = DB::all(
    "SELECT p.id, p.method, p.sender_number, p.txn_id, p.amount, p.screenshot,
            p.status, p.reject_reason, p.created_at, p.reviewed_at,
            b.id AS booking_id, b.team_id, sl.slot_number,
            s.id AS scrim_id, s.title AS scrim_title, s.match_at,
            g.name AS game_name, t.name AS team_name,
            res.id AS result_id, COALESCE(re.prize_won, 0) AS prize_won
     FROM payments p
     JOIN bookings b ON b.id = p.booking_id
     JOIN slots sl   ON sl.id = b.slot_id
     JOIN scrims s   ON s.id = b.scrim_id
     JOIN games  g   ON g.id = s.game_id
     JOIN teams  t   ON t.id = b.team_id
     LEFT JOIN results res ON res.scrim_id = s.id AND res.status = 'published'
     LEFT JOIN result_entries re ON re.result_id = res.id AND re.team_id = b.team_id
     WHERE $w
     ORDER BY p.created_at DESC",
    $params
);

$stats = DB::one(
    "SELECT
        COALESCE(SUM(amount), 0) AS total_spent,
        COALESCE(SUM(CASE WHEN status='approved' THEN amount ELSE 0 END), 0) AS successful,
        COALESCE(SUM(CASE WHEN status='pending'  THEN amount ELSE 0 END), 0) AS pending,
        COALESCE(SUM(CASE WHEN status='rejected' THEN amount ELSE 0 END), 0) AS rejected,
        COUNT(*) AS total_count,
        SUM(CASE WHEN status='approved' THEN 1 ELSE 0 END) AS successful_count,
        SUM(CASE WHEN status='pending'  THEN 1 ELSE 0 END) AS pending_count,
        SUM(CASE WHEN status='rejected' THEN 1 ELSE 0 END) AS rejected_count
     FROM payments WHERE user_id = ?",
    [$u['id']]
);

 
$totalWon = 0;
foreach ($rows as &$r) {
    $r['amount_txt']     = money($r['amount']);
    $r['screenshot_url'] = $r['screenshot'] ? UPLOAD_URL . '/' . $r['screenshot'] : null;
    $r['prize_won']      = (float) $r['prize_won'];
    $r['prize_won_txt']  = money($r['prize_won']);
    // Profit/loss only counts once the scrim's result is published — before that,
    // the outcome isn't decided yet, so it stays "pending" instead of showing as a loss.
    $r['result_published'] = $r['result_id'] !== null;
    if ($r['status'] === 'approved' && $r['result_published']) {
        $r['net'] = round($r['prize_won'] - (float)$r['amount'], 2);
        $totalWon += $r['prize_won'];
    } else {
        $r['net'] = null;
    }
    $r['net_txt'] = $r['net'] === null ? ($r['status'] === 'approved' && !$r['result_published'] ? 'Pending result' : '—') : money($r['net']);
}
unset($r);
// Only count spend from payments whose scrim result has already been published
$decidedSpend = 0;
foreach ($rows as $r) {
    if ($r['status'] === 'approved' && $r['result_published']) $decidedSpend += (float) $r['amount'];
}
$totalLoss   = max(0, $decidedSpend - $totalWon);
$totalProfit = max(0, $totalWon - $decidedSpend);

$stats['total_spent_txt'] = money($stats['total_spent']);
$stats['successful_txt']  = money($stats['successful']);
$stats['total_won']       = $totalWon;
$stats['total_won_txt']   = money($totalWon);
$stats['loss']            = $totalLoss;
$stats['loss_txt']        = money($totalLoss);
$stats['profit']          = $totalProfit;
$stats['profit_txt']      = money($totalProfit);

ok(['payments' => $rows, 'stats' => $stats]);
