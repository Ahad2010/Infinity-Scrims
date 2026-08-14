<?php
/** Owner: existing scrim edit karta hai */
require_once __DIR__ . '/../_bootstrap.php';
require_method('POST');
$u = Auth::requireOwner();
Auth::verifyCsrf();

$d = require_fields(['scrim_id', 'title', 'game_id', 'match_at', 'total_slots', 'price_per_slot', 'prize_top1']);
$scrimId = (int)$d['scrim_id'];

$scrim = DB::one("SELECT * FROM scrims WHERE id=? AND created_by=?", [$scrimId, $u['id']]);
if (!$scrim) fail('Scrim not found.', 404);

$slots = (int)$d['total_slots'];
if ($slots < 2 || $slots > 200) fail('Slots must be between 2 and 200.', 422);
if (strtotime($d['match_at']) === false) fail('Match date/time is not valid.', 422);

// Slots kam nahi kar sakte agar utne slots already booked/held hain
$bookedCount = (int) DB::val(
    "SELECT COUNT(*) FROM slots WHERE scrim_id=? AND status IN ('booked','held')", [$scrimId]);
if ($slots < $bookedCount) {
    fail("Total slots can't be less than $bookedCount — that many slots are already booked.", 409);
}

$banner = $scrim['banner'];
if (!empty($_FILES['banner']['name'])) $banner = upload_image('banner', 'banners');

$images = $scrim['images'] ? json_decode($scrim['images'], true) : [];
if (!empty($_FILES['images']) && is_array($_FILES['images']['name'])) {
    $count = min(count($_FILES['images']['name']), 5);
    $images = [];
    for ($i = 0; $i < $count; $i++) {
        if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) continue;
        $tmp = ['name' => $_FILES['images']['name'][$i], 'tmp_name' => $_FILES['images']['tmp_name'][$i],
                'error' => $_FILES['images']['error'][$i], 'size' => $_FILES['images']['size'][$i]];
        $_FILES['__single'] = $tmp;
        $images[] = upload_image('__single', 'banners');
    }
}

$visibility = input('visibility', $scrim['visibility']) === 'private' ? 'private' : 'public';
$slotType   = input('slot_type', $scrim['slot_type']) === 'player_slot' ? 'player_slot' : 'team_slot';
$status     = input('status', $scrim['status']);
$allowedStatus = ['draft', 'open', 'full', 'live', 'completed', 'cancelled'];
if (!in_array($status, $allowedStatus, true)) $status = $scrim['status'];

DB::begin();
try {
    DB::update('scrims', [
        'title'                => $d['title'],
        'game_id'              => (int)$d['game_id'],
        'banner'               => $banner,
        'images'               => $images ? json_encode($images) : null,
        'description'          => input('description'),
        'rules'                => input('rules'),
        'mode'                 => input('mode'),
        'team_size'            => input('team_size') ? (int)input('team_size') : null,
        'slot_type'            => $slotType,
        'max_players_per_slot' => input('max_players_per_slot') ? (int)input('max_players_per_slot') : null,
        'map'                  => input('map'),
        'map_pool'             => input('map_pool'),
        'region'               => input('region', $scrim['region']),
        'platform'             => input('platform'),
        'match_at'             => date('Y-m-d H:i:s', strtotime($d['match_at'])),
        'total_slots'          => $slots,
        'price_per_slot'       => (float)$d['price_per_slot'],
        'prize_top1'           => (float)$d['prize_top1'],
        'prize_top2'           => (float) input('prize_top2', 0),
        'prize_top3'           => (float) input('prize_top3', 0),
        'status'               => $status,
        'visibility'           => $visibility,
        'access_password'      => input('access_password') ?: null,
'group_link'           => input('group_link') ?: null,
        'live_stream_link'     => input('live_stream_link') ?: null,
            ], 'id = :sid', ['sid' => $scrimId]);

    // Slots badhe hain to naye khaali slot rows add karo
    if ($slots > (int)$scrim['total_slots']) {
        $values = [];
        for ($i = (int)$scrim['total_slots'] + 1; $i <= $slots; $i++) $values[] = "($scrimId, $i)";
        if ($values) DB::run("INSERT INTO slots (scrim_id, slot_number) VALUES " . implode(',', $values));
    }
    // Slots kam hue hain to sirf khaali (unbooked) extra slots hatao
    if ($slots < (int)$scrim['total_slots']) {
        DB::run("DELETE FROM slots WHERE scrim_id=? AND slot_number > ? AND status='available'", [$scrimId, $slots]);
    }

    DB::commit();
} catch (Throwable $e) {
    DB::rollback();
    fail(DEBUG ? $e->getMessage() : 'Could not update scrim.', 500);
}

ok(['scrim_id' => $scrimId], 'Scrim updated.');