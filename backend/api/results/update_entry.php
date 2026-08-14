<?php
/** Publish se pehle owner AI ki ghalti theek kar sakta hai */
require_once __DIR__ . '/../_bootstrap.php';
require_method('POST');
Auth::requireOwner();
Auth::verifyCsrf();

$d = require_fields(['entry_id']);
$entry = DB::one("SELECT * FROM result_entries WHERE id=?", [(int)$d['entry_id']]);
if (!$entry) fail('Entry not found.', 404);

$fields = [];
foreach (['team_id','position','kills','placement_pts','slot_number'] as $f) {
    $v = input($f);
    if ($v !== null && $v !== '') $fields[$f] = (int)$v;
}
if (input('prize_won') !== null && input('prize_won') !== '') {
    $fields['prize_won'] = (float) input('prize_won');
}

// If the owner directly overrides Points, trust that number as-is — don't
// recalculate it from kills. Otherwise keep the normal auto-calculation.
$manualPoints = input('total_points');
if ($manualPoints !== null && $manualPoints !== '') {
    $fields['total_points'] = (int) $manualPoints;
} else {
    $pos   = $fields['position']      ?? $entry['position'];
    $kills = $fields['kills']         ?? $entry['kills'];
    $place = $fields['placement_pts'] ?? $entry['placement_pts'];
    $killPt = (int) DB::setting('kill_point', 1);
    $fields['total_points'] = $place + ($kills * $killPt);
}

if (!$fields) fail('Provide something to change.', 422);
// If position changed and owner didn't explicitly set prize_won, re-sync from Top1/2/3.
if (isset($fields['position']) && !isset($fields['prize_won'])) {
    $scrim = DB::one("SELECT prize_top1, prize_top2, prize_top3 FROM scrims WHERE id=?", [$entry['scrim_id']]);
    $fields['prize_won'] = $pos === 1 ? (float)$scrim['prize_top1']
                          : ($pos === 2 ? (float)$scrim['prize_top2']
                          : ($pos === 3 ? (float)$scrim['prize_top3'] : 0));
}
$fields['is_verified']  = 1;

DB::update('result_entries', $fields, 'id = :id', ['id' => $entry['id']]);
ok(['total_points' => $fields['total_points']], 'Entry updated.');
