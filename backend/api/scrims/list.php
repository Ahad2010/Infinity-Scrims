<?php
require_once __DIR__ . '/../_bootstrap.php';

$game   = input('game');            // slug
$status = input('status', 'open');  // open | live | completed | all
$search = input('search');
$page   = max(1, (int) input('page', 1));
$limit  = 12;
$offset = ($page - 1) * $limit;

$where  = ["s.status <> 'draft'"];
$params = [];

if ($status !== 'all') { $where[] = "s.status = ?"; $params[] = $status; }
if ($game)   { $where[] = "g.slug = ?";        $params[] = $game; }
if ($search) { $where[] = "s.title LIKE ?";    $params[] = '%' . $search . '%'; }

$w = implode(' AND ', $where);

$total = (int) DB::val("SELECT COUNT(*) FROM scrims s JOIN games g ON g.id=s.game_id WHERE $w", $params);

$scrims = DB::all(
    "SELECT s.id, s.title, s.banner, s.mode, s.map, s.match_at, s.total_slots,
            s.price_per_slot, s.status, g.name AS game_name, g.slug AS game_slug,
            (SELECT COUNT(*) FROM slots WHERE scrim_id=s.id AND status IN ('booked','held')) AS booked_slots
     FROM scrims s JOIN games g ON g.id = s.game_id
     WHERE $w
     ORDER BY s.match_at ASC
     LIMIT $limit OFFSET $offset", $params);

foreach ($scrims as &$s) {
    $s['remaining'] = (int)$s['total_slots'] - (int)$s['booked_slots'];
    $s['is_full']   = $s['remaining'] <= 0;
    $s['price_txt'] = money($s['price_per_slot']);
    if ($s['banner']) $s['banner'] = img_url($s['banner']);
}

ok(['scrims' => $scrims, 'page' => $page, 'total' => $total, 'pages' => (int)ceil($total / $limit)]);