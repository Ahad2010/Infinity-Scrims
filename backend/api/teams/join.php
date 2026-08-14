<?php
require_once __DIR__ . '/../_bootstrap.php';
require_method('POST');
$u = Auth::requireLogin();
Auth::verifyCsrf();

$d = require_fields(['join_code']);
$team = DB::one("SELECT * FROM teams WHERE join_code = ? AND status='active'", [strtoupper($d['join_code'])]);
if (!$team) fail('Join code ghalat hai.', 404);

if (DB::val("SELECT id FROM team_members WHERE team_id=? AND user_id=?", [$team['id'], $u['id']])) {
    fail('You are already in this team.', 409);
}

DB::insert('team_members', [
    'team_id'      => $team['id'],
    'user_id'      => $u['id'],
    'in_game_name' => input('in_game_name'),
    'in_game_id'   => input('in_game_id'),
]);

notify((int)$team['captain_id'], 'team', 'Naya member', $u['username'] . ' aapki team mein shamil ho gaya.');
ok(['team' => $team], 'You joined ' . $team['name'] . '!');
