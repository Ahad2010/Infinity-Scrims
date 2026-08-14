<?php
/**
 * Owner: mark a participating team as Top 1 / Top 2 / Top 3 winner directly
 * from the Participants page — purely for prize-pool / profit-loss tracking.
 * This is completely separate from the AI results/leaderboard system —
 * it does NOT touch the results or result_entries tables.
 */
require_once __DIR__ . '/../_bootstrap.php';
require_method('POST');
$u = Auth::requireOwner();
Auth::verifyCsrf();

$d = require_fields(['scrim_id', 'team_id', 'position']); // position: 1, 2, or 3
$scrimId  = (int) $d['scrim_id'];
$teamId   = (int) $d['team_id'];
$position = (int) $d['position'];

if (!in_array($position, [1, 2, 3], true)) fail('Position must be 1, 2, or 3.', 422);

$scrim = DB::one("SELECT * FROM scrims WHERE id=?", [$scrimId]);
if (!$scrim) fail('Scrim not found.', 404);

$booking = DB::one(
    "SELECT id FROM bookings WHERE scrim_id=? AND team_id=? AND status IN ('pending','confirmed')",
    [$scrimId, $teamId]);
if (!$booking) fail('This team does not have a booking for this scrim.', 404);

$prizeMap = [1 => (float)$scrim['prize_top1'], 2 => (float)$scrim['prize_top2'], 3 => (float)$scrim['prize_top3']];
$prizeWon = $prizeMap[$position];

DB::begin();
try {
    DB::run("DELETE FROM scrim_winners WHERE scrim_id=? AND position=?", [$scrimId, $position]);
    DB::run("DELETE FROM scrim_winners WHERE scrim_id=? AND team_id=?", [$scrimId, $teamId]);

    DB::insert('scrim_winners', [
        'scrim_id'  => $scrimId,
        'team_id'   => $teamId,
        'position'  => $position,
        'prize_won' => $prizeWon,
        'marked_by' => $u['id'],
    ]);

    DB::commit();
} catch (Throwable $e) {
    DB::rollback();
    fail(DEBUG ? $e->getMessage() : 'Could not mark winner.', 500);
}

ok(['prize_won' => $prizeWon, 'prize_won_txt' => money($prizeWon)],
   "Marked as Top $position — PKR " . number_format($prizeWon) . " assigned.");