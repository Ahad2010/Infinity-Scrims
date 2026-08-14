<?php
require_once __DIR__ . '/../_bootstrap.php';
$u = Auth::requireLogin();

$ticketId = (int) input('ticket_id');
if ($ticketId) {
    if (!DB::val("SELECT id FROM support_tickets WHERE id=? AND user_id=?", [$ticketId, $u['id']])) {
        fail('Ticket not found.', 404);
    }
    $msgs = DB::all("SELECT sender, message, created_at FROM support_messages
                     WHERE ticket_id=? ORDER BY id ASC", [$ticketId]);
    foreach ($msgs as &$m) $m['time'] = date('h:i A', strtotime($m['created_at']));
    ok(['messages' => $msgs]);
}

$tickets = DB::all("SELECT id, subject, status, created_at FROM support_tickets
                    WHERE user_id=? ORDER BY id DESC LIMIT 20", [$u['id']]);
ok(['tickets' => $tickets]);
