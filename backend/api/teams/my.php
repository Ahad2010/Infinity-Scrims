<?php
require_once __DIR__ . '/../_bootstrap.php';
$u = Auth::requireLogin();

$teams = DB::all(
    "SELECT t.*, (SELECT COUNT(*) FROM team_members WHERE team_id=t.id) AS member_count,
            tm.role AS my_role
     FROM team_members tm
     JOIN teams t ON t.id = tm.team_id
     WHERE tm.user_id = ? AND t.status='active'
     ORDER BY t.created_at DESC", [$u['id']]);

foreach ($teams as &$t) {
    if ($t['logo']) $t['logo_url'] = img_url($t['logo']);
    $t['members'] = DB::all(
        "SELECT tm.role, tm.in_game_name, tm.in_game_id, u.id, u.username, u.avatar
         FROM team_members tm JOIN users u ON u.id = tm.user_id
         WHERE tm.team_id = ? ORDER BY tm.role='captain' DESC, tm.joined_at", [$t['id']]);
    // Sirf captain ko join code dikhega
    if ($t['my_role'] !== 'captain') unset($t['join_code']);
}
ok(['teams' => $teams]);