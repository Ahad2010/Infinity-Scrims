<?php
/**
 * AI support widget.
 * AI jawab de sake to de. Warna ticket escalate + WhatsApp link return.
 */
require_once __DIR__ . '/../_bootstrap.php';
require_once dirname(dirname(__DIR__)) . '/includes/ai.php';
require_method('POST');
$u = Auth::requireLogin();

$d = require_fields(['message']);
$question = $d['message'];
$ticketId = (int) input('ticket_id', 0);

// Ticket lo ya banao
if ($ticketId) {
    $ticket = DB::one("SELECT * FROM support_tickets WHERE id=? AND user_id=?", [$ticketId, $u['id']]);
    if (!$ticket) fail('Ticket not found.', 404);
} else {
    $ticketId = DB::insert('support_tickets', [
        'user_id' => $u['id'],
        'subject' => mb_substr($question, 0, 120),
    ]);
}

DB::insert('support_messages', ['ticket_id' => $ticketId, 'sender' => 'user', 'message' => $question]);

// Pichli history (context ke liye, last 10)
$hist = DB::all("SELECT sender, message FROM support_messages
                 WHERE ticket_id=? AND sender IN ('user','ai') ORDER BY id DESC LIMIT 10", [$ticketId]);
$hist = array_reverse($hist);
array_pop($hist);   // abhi wala user message hata do (AI::support khud add karega)

$history = [];
foreach ($hist as $h) {
    $history[] = ['role' => $h['sender'] === 'ai' ? 'assistant' : 'user', 'content' => $h['message']];
}

$res = AI::support($question, $history);

if ($res['escalate']) {
    $prefill = "Assalam o Alaikum, main {$u['username']} hoon (" . APP_NAME . "). Mujhe madad chahiye: " . $question;
    $waLink  = AI::whatsappLink($prefill);

    $reply = "Is masle ka hal main khud nahi nikal saka. Hamari support team se WhatsApp par direct baat karein — "
           . "woh foran madad karenge.";

    DB::insert('support_messages', ['ticket_id' => $ticketId, 'sender' => 'ai', 'message' => $reply]);
    DB::update('support_tickets', [
        'status' => 'escalated', 'escalated_at' => date('Y-m-d H:i:s'),
    ], 'id = :id', ['id' => $ticketId]);

    // Owners ko bhi batao
    foreach (DB::all("SELECT id FROM users WHERE role='owner'") as $o) {
        notify((int)$o['id'], 'support', 'Support escalate hua',
            $u['username'] . ': ' . mb_substr($question, 0, 100), 'support.php');
    }

    ok([
        'ticket_id'     => $ticketId,
        'answer'        => $reply,
        'escalated'     => true,
        'whatsapp_link' => $waLink,
    ]);
}

DB::insert('support_messages', ['ticket_id' => $ticketId, 'sender' => 'ai', 'message' => $res['answer']]);
DB::update('support_tickets', ['ai_resolved' => 1], 'id = :id', ['id' => $ticketId]);

ok([
    'ticket_id' => $ticketId,
    'answer'    => $res['answer'],
    'escalated' => false,
]);
