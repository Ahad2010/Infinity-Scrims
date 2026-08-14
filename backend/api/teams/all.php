<?php
/** Owner: all teams with member count, captain, and status */
require_once __DIR__ . '/../_bootstrap.php';
Auth::requireOwner();

$teams = DB::all(
    "SELECT t.id, t.name, t.tag, t.logo, t.status, t.created_at,
            u.id AS captain_id, u.username AS captain_name, u.email AS captain_email,
            (SELECT COUNT(*) FROM team_members WHERE team_id=t.id) AS member_count
     FROM teams t
     JOIN users u ON u.id = t.captain_id
     ORDER BY t.created_at DESC");

foreach ($teams as &$t) {
    if ($t['logo']) $t['logo_url'] = UPLOAD_URL . '/' . $t['logo'];
}

ok(['teams' => $teams]);