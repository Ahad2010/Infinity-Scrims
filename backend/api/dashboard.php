<?php
/** User dashboard ke stats + upcoming bookings + recent activity */
require_once __DIR__ . '/_bootstrap.php';
$u = Auth::requireLogin();

if ($u['role'] === 'owner') {
    $ownerId = $u['id'];

    // Status breakdown donut
    $statusCounts = DB::all(
        "SELECT status, COUNT(*) AS cnt FROM scrims WHERE created_by=? GROUP BY status", [$ownerId]);
    $breakdown = ['open' => 0, 'full' => 0, 'live' => 0, 'completed' => 0, 'cancelled' => 0, 'draft' => 0];
    foreach ($statusCounts as $sc) $breakdown[$sc['status']] = (int)$sc['cnt'];
    $totalScrims = array_sum($breakdown);

    // Earnings trend — last 7 days
    $trend = DB::all(
        "SELECT DATE(p.reviewed_at) AS d, COALESCE(SUM(p.amount),0) AS total
         FROM payments p JOIN bookings b ON b.id=p.booking_id JOIN scrims s ON s.id=b.scrim_id
         WHERE s.created_by=? AND p.status='approved' AND p.reviewed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
         GROUP BY DATE(p.reviewed_at) ORDER BY d ASC", [$ownerId]);
    $trendMap = [];
    foreach ($trend as $t) $trendMap[$t['d']] = (float)$t['total'];
    $earningsTrend = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $earningsTrend[] = ['date' => date('d M', strtotime($date)), 'amount' => $trendMap[$date] ?? 0];
    }

    // Upcoming scrims (next 7 days)
    $upcomingScrims = DB::all(
        "SELECT s.id, s.title, s.match_at, s.total_slots, s.status,
                (SELECT COUNT(*) FROM slots WHERE scrim_id=s.id AND status IN ('booked','held')) AS booked
         FROM scrims s WHERE s.created_by=? AND s.match_at >= NOW() AND s.match_at <= DATE_ADD(NOW(), INTERVAL 7 DAY)
         ORDER BY s.match_at ASC LIMIT 5", [$ownerId]);

    // Recent scrims table
    $recentScrims = DB::all(
        "SELECT s.id, s.title, s.match_at, s.total_slots, s.status,
                (SELECT COUNT(*) FROM slots WHERE scrim_id=s.id AND status IN ('booked','held')) AS booked,
                (SELECT COALESCE(SUM(p.amount),0) FROM payments p JOIN bookings b ON b.id=p.booking_id
                 WHERE b.scrim_id=s.id AND p.status='approved') AS revenue
         FROM scrims s WHERE s.created_by=? ORDER BY s.created_at DESC LIMIT 8", [$ownerId]);
    foreach ($recentScrims as &$rs) $rs['revenue_txt'] = money($rs['revenue']);

    // Recent activity (approvals/rejections/new scrims)
// Recent activity (approvals/rejections/new scrims)
    $recentActivity = DB::all(
        "SELECT title, body, type, created_at FROM notifications WHERE user_id=? ORDER BY id DESC LIMIT 6", [$ownerId]);
    foreach ($recentActivity as &$ra) $ra['ago'] = time_ago($ra['created_at']);

    // Earnings breakdown — real revenue split by where the scrim currently stands,
    // plus payments still awaiting owner review (escrow, not yet counted as earned).
    $earnCompleted = (float) DB::val(
        "SELECT COALESCE(SUM(p.amount),0) FROM payments p JOIN bookings b ON b.id=p.booking_id JOIN scrims s ON s.id=b.scrim_id
         WHERE s.created_by=? AND s.status='completed' AND p.status='approved'", [$ownerId]);
    $earnOngoing = (float) DB::val(
        "SELECT COALESCE(SUM(p.amount),0) FROM payments p JOIN bookings b ON b.id=p.booking_id JOIN scrims s ON s.id=b.scrim_id
         WHERE s.created_by=? AND s.status IN ('open','full','live') AND p.status='approved'", [$ownerId]);
    $earnPending = (float) DB::val(
        "SELECT COALESCE(SUM(p.amount),0) FROM payments p JOIN bookings b ON b.id=p.booking_id JOIN scrims s ON s.id=b.scrim_id
         WHERE s.created_by=? AND p.status='pending'", [$ownerId]);

    ok(['role' => 'owner', 'stats' => [
        'pending_approvals' => (int) DB::val("SELECT COUNT(*) FROM payments WHERE status='pending'"),
        'active_scrims'     => $breakdown['open'] + $breakdown['full'] + $breakdown['live'],
        'total_users'       => (int) DB::val("SELECT COUNT(*) FROM users WHERE role='user'"),
        'revenue_total'     => money(DB::val("SELECT COALESCE(SUM(p.amount),0) FROM payments p JOIN bookings b ON b.id=p.booking_id JOIN scrims s ON s.id=b.scrim_id WHERE s.created_by=$ownerId AND p.status='approved'")),
        'upcoming_count'    => count($upcomingScrims),
    ],
        'status_breakdown'   => $breakdown,
        'total_scrims'       => $totalScrims,
        'earnings_trend'     => $earningsTrend,
        'earnings_breakdown' => ['completed' => $earnCompleted, 'ongoing' => $earnOngoing, 'pending' => $earnPending],
        'upcoming_scrims'    => $upcomingScrims,
        'recent_scrims'      => $recentScrims,
        'recent_activity'    => $recentActivity,
    ]);
}

$stats = [
    'upcoming'  => (int) DB::val("SELECT COUNT(*) FROM bookings b JOIN scrims s ON s.id=b.scrim_id
                                  WHERE b.booked_by=? AND b.status='confirmed' AND s.match_at>=NOW()", [$u['id']]),
    'confirmed' => (int) DB::val("SELECT COUNT(*) FROM bookings WHERE booked_by=? AND status='confirmed'", [$u['id']]),
    'pending'   => (int) DB::val("SELECT COUNT(*) FROM bookings WHERE booked_by=? AND status='pending'", [$u['id']]),
    'played'    => (int) DB::val("SELECT COUNT(*) FROM bookings b JOIN scrims s ON s.id=b.scrim_id
                                  WHERE b.booked_by=? AND b.status='confirmed' AND s.match_at<NOW()", [$u['id']]),
    'spent'     => money(DB::val("SELECT COALESCE(SUM(amount),0) FROM payments WHERE user_id=? AND status='approved'", [$u['id']])),
];

$upcoming = DB::all(
    "SELECT b.id, b.status, sl.slot_number, s.id AS scrim_id, s.title, s.banner, s.mode, s.map, s.match_at
     FROM bookings b JOIN slots sl ON sl.id=b.slot_id JOIN scrims s ON s.id=b.scrim_id
     WHERE b.booked_by=? AND b.status IN ('pending','confirmed') AND s.match_at>=NOW()
     ORDER BY s.match_at ASC LIMIT 5", [$u['id']]);
foreach ($upcoming as &$up) if ($up['banner']) $up['banner'] = UPLOAD_URL . '/' . $up['banner'];

$activity = DB::all("SELECT type, title, body, created_at FROM notifications
                     WHERE user_id=? ORDER BY id DESC LIMIT 6", [$u['id']]);
foreach ($activity as &$a) $a['ago'] = time_ago($a['created_at']);

$explore = DB::all(
    "SELECT s.id, s.title, s.banner, s.mode, s.map, s.match_at, s.total_slots, s.price_per_slot,
            g.name AS game_name,
            (SELECT COUNT(*) FROM slots WHERE scrim_id=s.id AND status IN ('booked','held')) AS booked
     FROM scrims s JOIN games g ON g.id=s.game_id
     WHERE s.status='open' AND s.match_at>=NOW()
     ORDER BY s.match_at ASC LIMIT 4");
foreach ($explore as &$ex) if ($ex['banner']) $ex['banner'] = UPLOAD_URL . '/' . $ex['banner'];

// Latest scrims the owner has posted — dashboard headline cards. A scrim
// stays in this list until its result is published (results/publish.php
// flips scrims.status to 'completed', so excluding that status here is
// all that's needed — no separate "hide it" logic required). Each card
// carries the CURRENT USER'S TEAM's real booking status (null / pending /
// confirmed) — team-membership based, so every member sees the same
// status and who on the team actually booked it, not just whoever clicked.
$latestScrims = DB::all(
    "SELECT s.id, s.title, s.banner, s.mode, s.map, s.match_at, s.total_slots, s.price_per_slot,
            g.name AS game_name,
            (SELECT COUNT(*) FROM slots WHERE scrim_id=s.id AND status IN ('booked','held')) AS booked
     FROM scrims s JOIN games g ON g.id=s.game_id
     WHERE s.status IN ('open','full','live')
     ORDER BY s.created_at DESC LIMIT 4");
foreach ($latestScrims as &$ls) {
    if ($ls['banner']) $ls['banner'] = UPLOAD_URL . '/' . $ls['banner'];
    $myBooking = DB::one(
        "SELECT b.status, b.booked_by, bu.username AS booked_by_username
         FROM bookings b JOIN team_members tm ON tm.team_id = b.team_id
         JOIN users bu ON bu.id = b.booked_by
         WHERE b.scrim_id=? AND tm.user_id=? AND b.status IN ('pending','confirmed')
         ORDER BY b.id DESC LIMIT 1", [$ls['id'], $u['id']]);
    $ls['my_booking_status'] = $myBooking ? $myBooking['status'] : null;
    $ls['booked_by_username'] = $myBooking ? $myBooking['booked_by_username'] : null;
    $ls['booked_by_me'] = $myBooking ? ((int)$myBooking['booked_by'] === (int)$u['id']) : false;
}

ok(['role' => 'user', 'stats' => $stats, 'upcoming' => $upcoming,
    'activity' => $activity, 'explore' => $explore, 'latest_scrims' => $latestScrims]);