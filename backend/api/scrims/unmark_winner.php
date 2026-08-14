<?php
/** Owner: remove a team's winner mark for a scrim (undo). */
require_once __DIR__ . '/../_bootstrap.php';
require_method('POST');
Auth::requireOwner();
Auth::verifyCsrf();

$d = require_fields(['scrim_id', 'team_id']);
DB::run("DELETE FROM scrim_winners WHERE scrim_id=? AND team_id=?", [(int)$d['scrim_id'], (int)$d['team_id']]);

ok([], 'Winner mark removed.');