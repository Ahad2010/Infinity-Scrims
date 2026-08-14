<?php
/** Sirf team captain kisi member ko remove kar sakta hai */
require_once __DIR__ . '/../_bootstrap.php';
require_method('POST');
$u = Auth::requireLogin();
Auth::verifyCsrf();

$d = require_fields(['team_id', 'user_id']);
$teamId = (int)$d['team_id'];
$targetUserId = (int)$d['user_id'];

if (!Auth::isCaptain($teamId)) fail('Only the team captain can remove a member.', 403);
if ($targetUserId === $u['id']) fail('You cannot remove yourself. Delete the team or transfer leadership instead.', 422);

$member = DB::one("SELECT * FROM team_members WHERE team_id=? AND user_id=?", [$teamId, $targetUserId]);
if (!$member) fail('This member is not part of this team.', 404);

// Agar us member ki koi active (pending/confirmed) booking hai to remove na hone dein
$activeBooking = DB::val(
    "SELECT b.id FROM bookings b WHERE b.team_id=? AND b.booked_by=? AND b.status IN ('pending','confirmed')",
    [$teamId, $targetUserId]);
if ($activeBooking) fail('This member has an active booking — resolve it first.', 409);

DB::run("DELETE FROM team_members WHERE team_id=? AND user_id=?", [$teamId, $targetUserId]);

notify($targetUserId, 'team', 'Team se remove kar diya gaya',
    'Aapko team se remove kar diya gaya hai.');

ok([], 'Member removed.');
