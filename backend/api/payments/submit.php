<?php
/** User payment proof (screenshot) upload karta hai */
require_once __DIR__ . '/../_bootstrap.php';
require_method('POST');
$u = Auth::requireLogin();
Auth::verifyCsrf();

$d = require_fields(['booking_id', 'method', 'sender_number']);
$bookingId = (int)$d['booking_id'];

$b = DB::one("SELECT b.*, s.title FROM bookings b JOIN scrims s ON s.id=b.scrim_id WHERE b.id=?", [$bookingId]);
if (!$b) fail('Booking not found.', 404);
if ((int)$b['booked_by'] !== (int)$u['id']) fail('This is not your booking.', 403);
if ($b['status'] === 'confirmed') fail('This booking is already confirmed.', 409);
if (DB::val("SELECT id FROM payments WHERE booking_id=? AND status='pending'", [$bookingId])) {
    fail('Your payment proof is already under review.', 409);
}
if (!in_array($d['method'], ['jazzcash','easypaisa','bank'])) fail('Payment method is not valid.', 422);

$path = upload_image('screenshot', 'payments');

$payId = DB::insert('payments', [
    'booking_id'    => $bookingId,
    'user_id'       => $u['id'],
    'method'        => $d['method'],
    'sender_number' => $d['sender_number'],
    'txn_id'        => input('txn_id'),
    'amount'        => $b['amount'],
    'screenshot'    => $path,
]);

// Slot hold ko payment review tak barha do
DB::run("UPDATE slots SET held_until = DATE_ADD(NOW(), INTERVAL 24 HOUR) WHERE id=?", [$b['slot_id']]);

// Owners ko notify
foreach (DB::all("SELECT id FROM users WHERE role='owner'") as $o) {
    notify((int)$o['id'], 'payment', 'Naya payment proof',
        $u['username'] . ' — ' . $b['title'] . ' (' . money($b['amount']) . ')', 'approvals.php');
}

ok(['payment_id' => $payId], 'Payment proof submitted. The owner will review it (usually within 15 min).');
