<?php
/** Owner result screenshot upload karta hai → AI parse karta hai → draft banta hai */
require_once __DIR__ . '/../_bootstrap.php';
require_once dirname(dirname(__DIR__)) . '/includes/ai.php';
require_method('POST');
$owner = Auth::requireOwner();
Auth::verifyCsrf();

$d = require_fields(['scrim_id']);
$scrimId = (int)$d['scrim_id'];

$scrim = DB::one("SELECT id, title FROM scrims WHERE id=?", [$scrimId]);
if (!$scrim) fail('Scrim not found.', 404);

$path = upload_image('screenshot', 'results');

$resultId = DB::insert('results', [
    'scrim_id'    => $scrimId,
    'screenshot'  => $path,
    'uploaded_by' => $owner['id'],
]);

// ---- AI se scoreboard parse karao ----
$parsed = AI::readResultImage(UPLOAD_PATH . '/' . $path);

if ($parsed === null) {
    DB::update('results', ['ai_status' => 'failed'], 'id = :id', ['id' => $resultId]);
    ok(['result_id' => $resultId, 'entries' => [], 'ai_status' => 'failed'],
       'Screenshot upload ho gaya lekin AI padh nahi saka. Aap manually entries add karein.');
}

DB::update('results', [
    'ai_status'   => 'parsed',
    'ai_raw_json' => json_encode($parsed, JSON_UNESCAPED_UNICODE),
], 'id = :id', ['id' => $resultId]);

// Points settings
$killPt   = (int) DB::setting('kill_point', 1);
$placeMap = json_decode(DB::setting('placement_points', '{}'), true) ?: [];

// Is scrim ki teams (AI naam match karne ke liye)
$scrimTeams = DB::all(
    "SELECT b.team_id, t.name, sl.slot_number
     FROM bookings b JOIN teams t ON t.id=b.team_id JOIN slots sl ON sl.id=b.slot_id
     WHERE b.scrim_id=? AND b.status='confirmed'", [$scrimId]);

$entries = [];
foreach ($parsed as $row) {
    $pos   = (int)($row['position'] ?? 0);
    $kills = (int)($row['kills'] ?? 0);
    $name  = trim((string)($row['team_name'] ?? ''));
    $slot  = isset($row['slot']) ? (int)$row['slot'] : null;

    // Team match: pehle slot number se, phir naam se (fuzzy)
    $teamId = null;
    foreach ($scrimTeams as $st) {
        if ($slot && (int)$st['slot_number'] === $slot) { $teamId = (int)$st['team_id']; break; }
    }
    if (!$teamId && $name !== '') {
        $best = 0;
        foreach ($scrimTeams as $st) {
            similar_text(mb_strtolower($name), mb_strtolower($st['name']), $pct);
            if ($pct > $best && $pct >= 70) { $best = $pct; $teamId = (int)$st['team_id']; }
        }
    }

    $placePts = (int)($placeMap[(string)$pos] ?? 0);
    $total    = $placePts + ($kills * $killPt);

    $id = DB::insert('result_entries', [
        'result_id'     => $resultId,
        'scrim_id'      => $scrimId,
        'team_id'       => $teamId,
        'team_name_raw' => $name,
        'slot_number'   => $slot,
        'position'      => $pos,
        'kills'         => $kills,
        'placement_pts' => $placePts,
        'total_points'  => $total,
        'is_verified'   => 0,
    ]);

    $entries[] = [
        'id' => $id, 'position' => $pos, 'team_name_raw' => $name,
        'matched_team_id' => $teamId, 'slot_number' => $slot,
        'kills' => $kills, 'placement_pts' => $placePts, 'total_points' => $total,
        'needs_review' => $teamId === null,
    ];
}

ok(['result_id' => $resultId, 'entries' => $entries, 'ai_status' => 'parsed'],
   'AI ne ' . count($entries) . ' teams detect ki. Review karke publish karein.');
