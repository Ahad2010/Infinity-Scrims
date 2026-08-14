<?php
require_once __DIR__ . '/../_bootstrap.php';
$u = Auth::requireLogin();

$unread = (int) DB::val("SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0", [$u['id']]);
$teams  = DB::all("SELECT t.id, t.name, t.tag, t.logo, tm.role
                   FROM team_members tm JOIN teams t ON t.id=tm.team_id
                   WHERE tm.user_id=? AND t.status='active'", [$u['id']]);

ok(['user' => $u, 'unread' => $unread, 'teams' => $teams]);
