<?php
/** Check karo ke user is room ka member hai */
function can_access_room(int $roomId): bool
{
    if (Auth::isOwner()) return true;
    $uid = Auth::id();
    $r = DB::one("SELECT * FROM chat_rooms WHERE id=?", [$roomId]);
    if (!$r) return false;

    if ($r['type'] === 'team') {
        return (bool) DB::val("SELECT id FROM team_members WHERE team_id=? AND user_id=?", [$r['team_id'], $uid]);
    }
    // scrim room: confirmed booking wali team ka koi bhi member
    return (bool) DB::val(
        "SELECT b.id FROM bookings b
         JOIN team_members tm ON tm.team_id=b.team_id
         WHERE b.scrim_id=? AND b.status='confirmed' AND tm.user_id=?", [$r['scrim_id'], $uid]);
}
