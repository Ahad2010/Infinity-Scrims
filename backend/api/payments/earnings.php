<?php
/** Owner: earnings summary — revenue collected vs prize pool paid out, with net profit/loss. */
require_once __DIR__ . '/../_bootstrap.php';
Auth::requireOwner();

$total = (float) DB::val("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='approved'");
$today = (float) DB::val("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='approved' AND DATE(reviewed_at)=CURDATE()");
$month = (float) DB::val("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='approved' AND YEAR(reviewed_at)=YEAR(NOW()) AND MONTH(reviewed_at)=MONTH(NOW())");

// Prize pool committed — only counted once a scrim has actually started, so an
// upcoming open scrim's announced prize isn't treated as "already paid" too early.
$prizePaid = (float) DB::val(
    "SELECT COALESCE(SUM(prize_top1 + prize_top2 + prize_top3),0) FROM scrims WHERE status IN ('live','completed')");
$prizePaidMonth = (float) DB::val(
    "SELECT COALESCE(SUM(prize_top1 + prize_top2 + prize_top3),0) FROM scrims
     WHERE status IN ('live','completed') AND YEAR(match_at)=YEAR(NOW()) AND MONTH(match_at)=MONTH(NOW())");

$netProfit = $total - $prizePaid;

$byScrim = DB::all(
    "SELECT s.id, s.title, s.match_at, s.status,
            s.prize_top1, s.prize_top2, s.prize_top3,
            COUNT(p.id) AS paid_slots, COALESCE(SUM(p.amount),0) AS revenue
     FROM scrims s
     LEFT JOIN bookings b ON b.scrim_id=s.id
     LEFT JOIN payments p ON p.booking_id=b.id AND p.status='approved'
     GROUP BY s.id ORDER BY s.match_at DESC LIMIT 20");

foreach ($byScrim as &$row) {
    $row['prize_pool'] = (float)$row['prize_top1'] + (float)$row['prize_top2'] + (float)$row['prize_top3'];
    $row['prize_pool_txt'] = money($row['prize_pool']);
    $row['revenue'] = (float) $row['revenue'];
    $started = in_array($row['status'], ['live', 'completed'], true);
    $row['profit'] = $started ? round($row['revenue'] - $row['prize_pool'], 2) : null;
    $row['profit_txt'] = $row['profit'] === null ? '—' : money($row['profit']);
}
unset($row);

ok([
    'total' => $total, 'total_txt' => money($total),
    'today' => $today, 'today_txt' => money($today),
    'month' => $month, 'month_txt' => money($month),
    'prize_paid' => $prizePaid, 'prize_paid_txt' => money($prizePaid),
    'net_profit' => $netProfit, 'net_profit_txt' => money($netProfit),
    'is_profit' => $netProfit >= 0,
    'by_scrim' => $byScrim,
]);