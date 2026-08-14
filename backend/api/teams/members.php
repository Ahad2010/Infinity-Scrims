<?php
/** Owner: members of a specific team, with ban status */
require_once __DIR__ . '/../_bootstrap.php';
Auth::requireOwner();

$teamId = (int) input('team_id');
if (!$teamId) fail('team_id is required.', 422);

$team = DB::one("SELECT * FROM teams WHERE id=?", [$teamId]);
if (!$team) fail('Team not found.', 404);

$members = DB::all(
    "SELECT tm.role, tm.in_game_name, tm.in_game_id, tm.joined_at,
            u.id, u.username, u.email, u.avatar, u.status
     FROM team_members tm
     JOIN users u ON u.id = tm.user_id
     WHERE tm.team_id = ?
     ORDER BY tm.role='captain' DESC, tm.joined_at", [$teamId]);

ok(['team' => $team, 'members' => $members]);