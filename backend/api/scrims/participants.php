<?php
/** Owner: kisi scrim ke saare participants (teams) + payment status */
require_once __DIR__ . '/../_bootstrap.php';
Auth::requireOwner();

$scrimId = (int) input('scrim_id');
if (!$scrimId) fail('scrim_id is required.', 422);

$scrim = DB::one("SELECT id, title FROM scrims WHERE id=?", [$scrimId]);
if (!$scrim) fail('Scrim not found.', 404);

$rows = DB::all(
    "SELECT b.id AS booking_id, b.status AS booking_status, b.amount, b.created_at,
            sl.slot_number, t.id AS team_id, t.name AS team_name, t.tag, t.logo,
            u.username AS captain, u.email, u.phone,
            p.status AS payment_status, p.method, p.screenshot,
            sw.position AS winner_position, sw.prize_won
     FROM bookings b
     JOIN slots sl ON sl.id = b.slot_id
     JOIN teams  t ON t.id = b.team_id
     JOIN users  u ON u.id = b.booked_by
     LEFT JOIN payments p ON p.booking_id = b.id AND p.status IN ('pending','approved')
     LEFT JOIN scrim_winners sw ON sw.scrim_id = b.scrim_id AND sw.team_id = b.team_id
     WHERE b.scrim_id = ? AND b.status <> 'cancelled'
     ORDER BY sl.slot_number ASC", [$scrimId]);

foreach ($rows as &$r) {
    $r['amount_txt'] = money($r['amount']);
    $r['screenshot_url'] = $r['screenshot'] ? UPLOAD_URL . '/' . $r['screenshot'] : null;
    $r['logo_url'] = $r['logo'] ? UPLOAD_URL . '/' . $r['logo'] : null;
    $r['prize_won'] = $r['prize_won'] !== null ? (float)$r['prize_won'] : null;
    $r['prize_won_txt'] = $r['prize_won'] !== null ? money($r['prize_won']) : null;
}

ok(['scrim' => $scrim, 'participants' => $rows, 'total' => count($rows)]);