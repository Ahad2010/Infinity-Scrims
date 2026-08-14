<?php
/** Owner result publish karta hai → leaderboard mein chala jata hai */
require_once __DIR__ . '/../_bootstrap.php';
require_method('POST');
Auth::requireOwner();
Auth::verifyCsrf();

$d = require_fields(['result_id']);
$r = DB::one("SELECT r.*, s.title FROM results r JOIN scrims s ON s.id=r.scrim_id WHERE r.id=?", [(int)$d['result_id']]);
if (!$r) fail('Result not found.', 404);
if ($r['status'] === 'published') fail('This result is already published.', 409);

$unmatched = (int) DB::val("SELECT COUNT(*) FROM result_entries WHERE result_id=? AND team_id IS NULL", [$r['id']]);
if ($unmatched > 0 && !input('force')) {
    fail("$unmatched entries could not be matched to a team. Fix them first or send force=1.", 422);
}

DB::begin();
try {
    DB::update('results', [
        'status' => 'published', 'published_at' => date('Y-m-d H:i:s'),
    ], 'id = :id', ['id' => $r['id']]);

    DB::run("UPDATE scrims SET status='completed' WHERE id=?", [$r['scrim_id']]);

    // Notify each team of their rank
    $entries = DB::all("SELECT team_id, position, total_points FROM result_entries
                        WHERE result_id=? AND team_id IS NOT NULL", [$r['id']]);
    foreach ($entries as $e) {
        notify_team((int)$e['team_id'], 'result', 'Result published!',
            $r['title'] . " — Your rank #{$e['position']} ({$e['total_points']} points)",
            'leaderboard.php');
    }
    DB::commit();
} catch (Throwable $e) {
    DB::rollback();
    fail(DEBUG ? $e->getMessage() : 'Could not publish.', 500);
}

ok([], 'Result published. Leaderboard is updated.');