<?php
/** Owner manually ek team ka result entry add karta hai (jab AI ne detect na kiya ho) */
require_once __DIR__ . '/../_bootstrap.php';
require_method('POST');
Auth::requireOwner();
Auth::verifyCsrf();

$d = require_fields(['result_id', 'position']);
$resultId = (int) $d['result_id'];

$result = DB::one("SELECT id, scrim_id, status FROM results WHERE id=?", [$resultId]);
if (!$result) fail('Result not found.', 404);
if ($result['status'] === 'published') fail('This result is already published.', 409);

$teamId = input('team_id') ? (int) input('team_id') : null;
$kills  = (int) input('kills', 0);
$killPt = (int) DB::setting('kill_point', 1);
$placeMap = json_decode(DB::setting('placement_points', '{}'), true) ?: [];
$position = (int) $d['position'];
$placePts = (int) ($placeMap[(string) $position] ?? 0);

// Prize auto-fills from the scrim's Top1/2/3 based on rank — owner can override with prize_won.
$scrim = DB::one("SELECT prize_top1, prize_top2, prize_top3 FROM scrims WHERE id=?", [$result['scrim_id']]);
$autoPrize = $position === 1 ? (float)$scrim['prize_top1']
           : ($position === 2 ? (float)$scrim['prize_top2']
           : ($position === 3 ? (float)$scrim['prize_top3'] : 0));
$prizeWon = input('prize_won') !== null && input('prize_won') !== '' ? (float) input('prize_won') : $autoPrize;

$id = DB::insert('result_entries', [
    'result_id'     => $resultId,
    'scrim_id'      => $result['scrim_id'],
    'team_id'       => $teamId,
    'team_name_raw' => input('team_name_raw'),
    'slot_number'   => input('slot_number') ? (int) input('slot_number') : null,
    'position'      => $position,
    'kills'         => $kills,
    'placement_pts' => $placePts,
    'total_points'  => $placePts + ($kills * $killPt),
    'prize_won'     => $prizeWon,
    'is_verified'   => 1,
]);
ok(['entry_id' => $id, 'total_points' => $placePts + ($kills * $killPt)], 'Entry added.');
