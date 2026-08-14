<?php
/** Owner approve ya reject karta hai */
require_once __DIR__ . '/../_bootstrap.php';
require_method('POST');
$owner = Auth::requireOwner();
Auth::verifyCsrf();

$d = require_fields(['payment_id', 'action']);   // action: approve | reject
$payId  = (int)$d['payment_id'];
$action = $d['action'];

$p = DB::one(
    "SELECT p.*, b.id AS booking_id, b.slot_id, b.scrim_id, b.team_id, b.booked_by,
            sl.slot_number, s.title
     FROM payments p
     JOIN bookings b ON b.id=p.booking_id
     JOIN slots sl ON sl.id=b.slot_id
     JOIN scrims s ON s.id=b.scrim_id
     WHERE p.id=?", [$payId]);

if (!$p) fail('Payment not found.', 404);
if ($p['status'] !== 'pending') fail('This payment has already been reviewed.', 409);

DB::begin();
try {
    if ($action === 'approve') {
        DB::update('payments', [
            'status' => 'approved', 'reviewed_by' => $owner['id'], 'reviewed_at' => date('Y-m-d H:i:s'),
        ], 'id = :id', ['id' => $payId]);

        DB::update('bookings', ['status' => 'confirmed'], 'id = :id', ['id' => $p['booking_id']]);
        DB::run("UPDATE slots SET status='booked', held_until=NULL WHERE id=?", [$p['slot_id']]);

        // Scrim full ho gayi?
        $left = (int) DB::val("SELECT COUNT(*) FROM slots WHERE scrim_id=? AND status='available'", [$p['scrim_id']]);
        if ($left === 0) DB::run("UPDATE scrims SET status='full' WHERE id=?", [$p['scrim_id']]);

        notify_team((int)$p['team_id'], 'booking', 'Slot confirm ho gaya!',
            $p['title'] . " — Slot #{$p['slot_number']} confirm hai.", 'my-bookings.php');

        $msg = 'Payment approve ho gaya. Slot confirm.';
    } elseif ($action === 'reject') {
        $reason = input('reason', 'Payment proof verify nahi ho saka.');
        DB::update('payments', [
            'status' => 'rejected', 'reviewed_by' => $owner['id'],
            'reviewed_at' => date('Y-m-d H:i:s'), 'reject_reason' => $reason,
        ], 'id = :id', ['id' => $payId]);

        DB::update('bookings', ['status' => 'rejected'], 'id = :id', ['id' => $p['booking_id']]);
        DB::run("UPDATE slots SET status='available', team_id=NULL, held_until=NULL WHERE id=?", [$p['slot_id']]);
        DB::run("UPDATE scrims SET status='open' WHERE id=? AND status='full'", [$p['scrim_id']]);

        notify((int)$p['booked_by'], 'payment', 'Payment reject ho gaya',
            $p['title'] . ' — ' . $reason, 'my-bookings.php');

        $msg = 'Payment reject kar diya. Slot dobara available hai.';
    } else {
        DB::rollback();
        fail('Action sirf approve ya reject ho sakta hai.', 422);
    }
    DB::commit();
} catch (Throwable $e) {
    DB::rollback();
    fail(DEBUG ? $e->getMessage() : 'Could not save review.', 500);
}

ok([], $msg);
