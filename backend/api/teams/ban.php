<?php
/** Owner: ban or unban an entire team — bans every member's account too,
 *  which reuses the existing login-block check (users.status='banned'). */
require_once __DIR__ . '/../_bootstrap.php';
require_method('POST');
Auth::requireOwner();
Auth::verifyCsrf();

$d = require_fields(['team_id', 'action']); // action: ban | unban
$teamId = (int) $d['team_id'];
$action = $d['action'] === 'unban' ? 'unban' : 'ban';

$team = DB::one("SELECT * FROM teams WHERE id=?", [$teamId]);
if (!$team) fail('Team not found.', 404);

$newTeamStatus = $action === 'ban' ? 'banned' : 'active';
$newUserStatus = $action === 'ban' ? 'banned' : 'active';

DB::begin();
try {
    DB::update('teams', ['status' => $newTeamStatus], 'id = :id', ['id' => $teamId]);

    $memberIds = DB::all("SELECT user_id FROM team_members WHERE team_id=?", [$teamId]);
    foreach ($memberIds as $m) {
        DB::update('users', ['status' => $newUserStatus], 'id = :id', ['id' => (int)$m['user_id']]);
    }

    DB::commit();
} catch (Throwable $e) {
    DB::rollback();
    fail(DEBUG ? $e->getMessage() : 'Could not update team status.', 500);
}

ok([], $action === 'ban' ? 'Team and all its members have been banned.' : 'Team and all its members have been unbanned.');