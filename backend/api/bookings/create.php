<?php
/**
 * Book a slot. Only the TEAM CAPTAIN can do this.
 * The slot number never comes from the client — at booking time, the
 * next AVAILABLE slot for the scrim is found and locked at the row level
 * (FOR UPDATE) and assigned automatically, so two teams can never grab
 * the same slot.
 */
require_once __DIR__ . '/../_bootstrap.php';
require_method('POST');
$u = Auth::requireLogin();
Auth::verifyCsrf();

$d = require_fields(['scrim_id', 'team_id']);
$scrimId = (int)$d['scrim_id'];
$teamId  = (int)$d['team_id'];

// 1. Captain check
if (!Auth::isCaptain($teamId)) {
    fail('Only the team captain can book a slot.', 403);
}

$scrim = DB::one("SELECT * FROM scrims WHERE id=?", [$scrimId]);
if (!$scrim) fail('Scrim not found.', 404);
if ($scrim['status'] !== 'open') fail('Booking is closed for this scrim.', 409);
if (strtotime($scrim['match_at']) < time()) fail('The match time has already passed.', 409);
if (strtotime($scrim['match_at']) - time() < 30 * 60) fail('Booking has closed for this scrim (starts within 30 minutes).', 409);

// 2. One team = one slot per scrim
if (DB::val("SELECT id FROM bookings WHERE scrim_id=? AND team_id=? AND status IN ('pending','confirmed')",
            [$scrimId, $teamId])) {
    fail('Your team already has a slot booked for this scrim.', 409);
}

$amount = (float)$scrim['price_per_slot'];
$holdMin = (int) DB::setting('slot_hold_minutes', SLOT_HOLD_MINUTES);

DB::begin();
try {
    // 3. Find the next available slot and lock it in the same query —
    //    FOR UPDATE means if two requests arrive at the same time, the
    //    second one waits until the first one commits/rolls back, so no
    //    two teams can ever end up with the same slot.
    $slot = DB::one(
        "SELECT * FROM slots WHERE scrim_id=? AND status='available'
         ORDER BY slot_number ASC LIMIT 1 FOR UPDATE", [$scrimId]);

    if (!$slot) {
        DB::rollback();
        // No slots left — mark the scrim as full so it immediately shows
        // "Full" in listings too, instead of only failing this one attempt.
        DB::run("UPDATE scrims SET status='full' WHERE id=? AND status='open'", [$scrimId]);
        fail('No slots left — this scrim is now full.', 409);
    }

    $slotNo = (int) $slot['slot_number'];

    // 4. Hold the slot
    DB::run("UPDATE slots SET status='held', team_id=?, held_until=DATE_ADD(NOW(), INTERVAL ? MINUTE)
             WHERE id=?", [$teamId, $holdMin, $slot['id']]);

    // 5. Pending booking
    $bookingId = DB::insert('bookings', [
        'scrim_id'  => $scrimId,
        'slot_id'   => $slot['id'],
        'team_id'   => $teamId,
        'booked_by' => $u['id'],
        'amount'    => $amount,
        'status'    => 'pending',
    ]);

    DB::commit();
} catch (Throwable $e) {
    DB::rollback();
    fail(DEBUG ? $e->getMessage() : 'Booking could not be completed.', 500);
}

$accounts = DB::all("SELECT method, account_title, account_number FROM payout_accounts WHERE is_active=1");

ok([
    'booking_id'   => $bookingId,
    'slot_number'  => $slotNo,
    'amount'       => $amount,
    'amount_txt'   => money($amount),
    'expires_in'   => $holdMin * 60,   // seconds — frontend countdown
    'pay_to'       => $accounts,
], "Slot #$slotNo held. Upload your payment proof within $holdMin minutes.");