<?php
/** Team captain apni team ki details edit kare — naam, tag, logo, phone, whatsapp, discord */
require_once __DIR__ . '/../_bootstrap.php';
require_method('POST');
$u = Auth::requireLogin();
Auth::verifyCsrf();

$d = require_fields(['team_id']);
$teamId = (int) $d['team_id'];

$team = DB::one("SELECT * FROM teams WHERE id = ?", [$teamId]);
if (!$team) fail('Team not found.', 404);
if ((int)$team['captain_id'] !== (int)$u['id']) fail('Only the team captain can edit this.', 403);

$update = [];

$name = input('name');
if ($name !== null && $name !== '' && $name !== $team['name']) {
    if (mb_strlen($name) < 3 || mb_strlen($name) > 60) fail('Team name must be 3-60 characters.', 422);
    if (DB::val("SELECT id FROM teams WHERE name = ? AND id != ?", [$name, $teamId])) {
        fail('This team name is already taken.', 409);
    }
    $update['name'] = $name;
}

$tag = input('tag');
if ($tag !== null) $update['tag'] = $tag === '' ? null : $tag;

$phone = input('phone');
if ($phone !== null) $update['phone'] = $phone === '' ? null : $phone;

$whatsapp = input('whatsapp');
if ($whatsapp !== null) $update['whatsapp'] = $whatsapp === '' ? null : $whatsapp;

$discordId = input('discord_id');
if ($discordId !== null) $update['discord_id'] = $discordId === '' ? null : $discordId;

if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
    $update['logo'] = upload_image('logo', 'teams');
}

if (empty($update)) fail('Nothing was provided to update.', 422);

DB::update('teams', $update, 'id = :id', ['id' => $teamId]);

$fresh = DB::one("SELECT id, name, tag, logo, phone, whatsapp, discord_id, join_code, created_at FROM teams WHERE id = ?", [$teamId]);
if ($fresh['logo']) $fresh['logo_url'] = img_url($fresh['logo']);

ok(['team' => $fresh], 'Team updated.');