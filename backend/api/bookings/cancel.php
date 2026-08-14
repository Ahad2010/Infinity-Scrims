<?php
require_once __DIR__ . '/../_bootstrap.php';
require_method('POST');
$u = Auth::requireLogin();
Auth::verifyCsrf();

$d = require_fields(['booking_id']);
$b = DB::one("SELECT * FROM bookings WHERE id=?", [(int)$d['booking_id']]);
if (!$b) fail('Booking not found.', 404);
if ((int)$b['booked_by'] !== (int)$u['id'] && !Auth::isOwner()) fail('Not allowed.', 403);
if ($b['status'] === 'cancelled') fail('This booking is already cancelled.', 409);

DB::begin();
try {
    DB::update('bookings', ['status' => 'cancelled'], 'id = :id', ['id' => $b['id']]);
    DB::run("UPDATE slots SET status='available', team_id=NULL, held_until=NULL WHERE id=?", [$b['slot_id']]);
    DB::run("UPDATE scrims SET status='open' WHERE id=? AND status='full'", [$b['scrim_id']]);
    DB::commit();
} catch (Throwable $e) {
    DB::rollback();
    fail('Could not cancel.', 500);
}
ok([], 'Booking cancelled. Slot is available again.');
