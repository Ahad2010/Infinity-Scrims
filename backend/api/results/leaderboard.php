<?php
/** Leaderboard: today | week (7 day) | all */
require_once __DIR__ . '/../_bootstrap.php';

$range = input('range', 'today');   // today | week | all
$limit = min(100, max(5, (int) input('limit', 50)));

$dateWhere = match ($range) {
    'today' => "AND DATE(r.published_at) = CURDATE()",
    'week'  => "AND r.published_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
    default => "",
};

$rows = DB::all(
    "SELECT t.id AS team_id, t.name AS team_name, t.tag, t.logo,
            COUNT(DISTINCT re.scrim_id) AS matches,
            SUM(re.kills)        AS total_kills,
            SUM(re.total_points) AS points,
            SUM(CASE WHEN re.position = 1 THEN 1 ELSE 0 END) AS wins
     FROM result_entries re
     JOIN results r ON r.id = re.result_id AND r.status='published'
     JOIN teams   t ON t.id = re.team_id
     WHERE re.team_id IS NOT NULL $dateWhere
     GROUP BY t.id
     ORDER BY points DESC, total_kills DESC, wins DESC
     LIMIT $limit");

$rank = 0;
foreach ($rows as &$row) {
    $row['rank']        = ++$rank;
    $row['points']      = (int)$row['points'];
    $row['total_kills'] = (int)$row['total_kills'];
    $row['matches']     = (int)$row['matches'];
    $row['wins']        = (int)$row['wins'];
    if ($row['logo']) $row['logo'] = UPLOAD_URL . '/' . $row['logo'];
}

ok(['range' => $range, 'leaderboard' => $rows, 'updated_at' => date('d M Y, h:i A')]);
