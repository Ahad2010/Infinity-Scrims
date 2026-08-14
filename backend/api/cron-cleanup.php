<?php
/**
 * Vercel Cron hits this every 5 minutes (see /vercel.json -> "crons").
 * Same logic as cron/cleanup.php, wrapped as an HTTP endpoint since there's
 * no CLI cron on serverless.
 *
 * Optional: set CRON_SECRET in Vercel env vars — Vercel then sends it as
 * "Authorization: Bearer <CRON_SECRET>" automatically on cron calls.
 */
require_once dirname(__DIR__) . '/includes/functions.php';

$cronSecret = DotEnv::get('CRON_SECRET', '');
if ($cronSecret !== '') {
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if ($auth !== 'Bearer ' . $cronSecret) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
}

header('Content-Type: application/json');

$freed = DB::run("UPDATE slots SET status='available', team_id=NULL, held_until=NULL
                  WHERE status='held' AND held_until < NOW()")->rowCount();

DB::run("UPDATE bookings b
         JOIN slots sl ON sl.id = b.slot_id
         SET b.status='cancelled'
         WHERE b.status='pending' AND sl.status='available'
           AND NOT EXISTS (SELECT 1 FROM payments p WHERE p.booking_id=b.id AND p.status='pending')");

DB::run("UPDATE scrims SET status='live' WHERE status IN ('open','full') AND match_at <= NOW()");

DB::run("UPDATE scrims s SET s.status='open'
         WHERE s.status='full'
           AND (SELECT COUNT(*) FROM slots WHERE scrim_id=s.id AND status='available') > 0");

echo json_encode(['success' => true, 'message' => 'Cleanup done', 'freed_slots' => $freed]);
