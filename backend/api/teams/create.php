<?php
require_once __DIR__ . '/../_bootstrap.php';
require_method('POST');
$u = Auth::requireLogin();
Auth::verifyCsrf();

$d = require_fields(['name']);
$name = $d['name'];
$tag  = input('tag');
$phone = input('phone');
$whatsapp = input('whatsapp');
$discordId = input('discord_id');

if (mb_strlen($name) < 3 || mb_strlen($name) > 60) fail('Team name must be 3-60 characters.', 422);
if (DB::val("SELECT id FROM teams WHERE name = ?", [$name])) fail('This team name is already taken.', 409);

$logo = null;
if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
    $logo = upload_image('logo', 'teams');
}

DB::begin();
try {
    $code = random_code(8);
    $teamId = DB::insert('teams', [
        'name'       => $name,
        'tag'        => $tag,
        'logo'       => $logo,
        'phone'      => $phone,
        'whatsapp'   => $whatsapp,
        'discord_id' => $discordId,
        'captain_id' => $u['id'],
        'join_code'  => $code,
    ]);
    DB::insert('team_members', [
        'team_id'      => $teamId,
        'user_id'      => $u['id'],
        'role'         => 'captain',
        'in_game_name' => input('in_game_name'),
        'in_game_id'   => input('in_game_id'),
    ]);
    // Team ka apna group chat
    DB::insert('chat_rooms', ['type' => 'team', 'team_id' => $teamId, 'name' => $name . ' — Team Chat']);
    DB::commit();
} catch (Throwable $e) {
    DB::rollback();
    fail(DEBUG ? $e->getMessage() : 'Could not create team.', 500);
}

ok(['team_id' => $teamId, 'join_code' => $code],
   'Team ban gayi! Members ko yeh code dein: ' . $code);
