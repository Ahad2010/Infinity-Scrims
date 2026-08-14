<?php
require_once __DIR__ . '/../_bootstrap.php';
require_method('POST');
$u = Auth::requireOwner();
Auth::verifyCsrf();

$d = require_fields(['title', 'game_id', 'match_at', 'total_slots', 'price_per_slot', 'prize_top1']);

$slots = (int) $d['total_slots'];
if ($slots < 2 || $slots > 200) fail('Slots must be between 2 and 200.', 422);
if (strtotime($d['match_at']) === false) fail('Match date/time is not valid.', 422);

$banner = null;
if (!empty($_FILES['banner']['name'])) $banner = upload_image('banner', 'banners');

// Additional images (max 5)
$images = [];
if (!empty($_FILES['images']) && is_array($_FILES['images']['name'])) {
    $count = min(count($_FILES['images']['name']), 5);
    for ($i = 0; $i < $count; $i++) {
        if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) continue;
        $tmp = ['name' => $_FILES['images']['name'][$i], 'tmp_name' => $_FILES['images']['tmp_name'][$i],
                'error' => $_FILES['images']['error'][$i], 'size' => $_FILES['images']['size'][$i]];
        $_FILES['__single'] = $tmp;
        $images[] = upload_image('__single', 'banners');
    }
}

$visibility = input('visibility', 'public') === 'private' ? 'private' : 'public';
$slotType   = input('slot_type', 'team_slot') === 'player_slot' ? 'player_slot' : 'team_slot';
$status     = input('status', 'draft') === 'open' ? 'open' : 'draft'; // "Publish Now" -> open, "Save as Draft" -> draft

DB::begin();
try {
    $scrimId = DB::insert('scrims', [
        'title'                => $d['title'],
        'game_id'               => (int)$d['game_id'],
        'banner'                => $banner,
        'images'                => $images ? json_encode($images) : null,
        'description'           => input('description'),
        'rules'                 => input('rules'),
        'mode'                  => input('mode'),
        'team_size'             => input('team_size') ? (int)input('team_size') : null,
        'slot_type'             => $slotType,
        'max_players_per_slot'  => input('max_players_per_slot') ? (int)input('max_players_per_slot') : null,
        'map'                   => input('map'),
        'map_pool'              => input('map_pool'),
        'region'                => input('region', 'Asia - South Asia'),
        'platform'              => input('platform'),
        'match_at'              => date('Y-m-d H:i:s', strtotime($d['match_at'])),
        'total_slots'           => $slots,
'price_per_slot'        => (float)$d['price_per_slot'],
        'platform_fee'          => (float) input('platform_fee', DB::setting('platform_fee', 0)),
        'prize_top1'            => (float)$d['prize_top1'],
        'prize_top2'            => (float) input('prize_top2', 0),
        'prize_top3'            => (float) input('prize_top3', 0),
        'status'                => $status,
        'visibility'            => $visibility,
        'access_password'       => input('access_password') ?: null,
'group_link'            => input('group_link') ?: null,
        'live_stream_link'      => input('live_stream_link') ?: null,
                'created_by'            => $u['id'],
    ]);

    // Slots auto-generate (bulk insert)
    $values = [];
    for ($i = 1; $i <= $slots; $i++) $values[] = "($scrimId, $i)";
    DB::run("INSERT INTO slots (scrim_id, slot_number) VALUES " . implode(',', $values));

    // Scrim ka group chat
    DB::insert('chat_rooms', ['type' => 'scrim', 'scrim_id' => $scrimId, 'name' => $d['title'] . ' — Group']);

    DB::commit();
} catch (Throwable $e) {
    DB::rollback();
    fail(DEBUG ? $e->getMessage() : 'Could not create scrim.', 500);
}

ok(['scrim_id' => $scrimId, 'status' => $status],
   $status === 'draft' ? 'Scrim draft mein save ho gayi.' : "Scrim publish ho gayi with $slots slots.");