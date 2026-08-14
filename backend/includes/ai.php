<?php
/**
 * Groq API wrapper — result screenshot reading + support chat.
 * Uses Groq's OpenAI-compatible /chat/completions endpoint.
 */
require_once __DIR__ . '/functions.php';

class AI
{
    /**
     * Raw call to Groq's chat completions endpoint. Returns text ya null on failure.
     * $system yahan ek normal message ban ke messages array ke shuru mein chala jata hai
     * (Groq/OpenAI format mein Anthropic jaisa alag top-level "system" field nahi hota).
     */
    public static function call(
        array $messages,
        string $system = '',
        int $maxTokens = 1500,
        ?string $model = null,
        bool $jsonMode = false
    ): ?string {
        if (!AI_ENABLED || AI_API_KEY === '') return null;

        if ($system !== '') {
            array_unshift($messages, ['role' => 'system', 'content' => $system]);
        }

        $payload = [
            'model'      => $model ?? AI_MODEL,
            'max_tokens' => $maxTokens,
            'messages'   => $messages,
        ];
        if ($jsonMode) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        $ch = curl_init(AI_API_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . AI_API_KEY,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
        ]);
        $res  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($res === false) {
            error_log('AI::call cURL error: ' . $err);
            return null;
        }
        if ($code !== 200) {
            error_log('AI::call Groq API error (' . $code . '): ' . $res);
            return null;
        }

        $json = json_decode($res, true);
        $text = $json['choices'][0]['message']['content'] ?? null;
        return $text !== null && $text !== '' ? $text : null;
    }

    /**
     * Result screenshot se ranking nikalo (Groq vision model).
     * Returns array of ['team_name','slot','position','kills'] ya null.
     */
    public static function readResultImage(string $absPath): ?array
    {
        if (!is_file($absPath)) return null;

        $mime = mime_content_type($absPath);
        $b64  = base64_encode(file_get_contents($absPath));
        $dataUri = "data:{$mime};base64,{$b64}";

        $system = "You read esports scoreboard screenshots (PUBG Mobile, BGMI, Valorant, Free Fire, CODM). "
                . "Extract every team row. Respond with ONLY a JSON object with a single key \"rows\" "
                . "containing an array, no markdown, no explanation. "
                . "Each row object: {\"position\": int, \"team_name\": string, \"slot\": int|null, \"kills\": int}. "
                . "If a value is unreadable use null. Sort by position ascending.";

        $messages = [[
            'role'    => 'user',
            'content' => [
                ['type' => 'text', 'text' => 'Extract the scoreboard as JSON.'],
                ['type' => 'image_url', 'image_url' => ['url' => $dataUri]],
            ],
        ]];

        $text = self::call($messages, $system, 3000, AI_VISION_MODEL, true);
        if ($text === null) return null;

        // ```json fences saaf karo, defensive cleanup
        $text = trim(preg_replace('/```(json)?/', '', $text));
        $data = json_decode($text, true);

        // Model ne { "rows": [...] } bheja ho ya seedha [...] — dono handle karo
        if (is_array($data) && isset($data['rows']) && is_array($data['rows'])) {
            return $data['rows'];
        }
        if (is_array($data) && ($data === [] || array_keys($data) === range(0, count($data) - 1))) {
            return $data;
        }
        return null;
    }

    /**
     * Full, always-available knowledge of every page/feature on the site.
     * This is included on every support call regardless of what's in the
     * faqs table, so the assistant can guide users through the whole app —
     * not just whatever an admin happened to write an FAQ entry for.
     */
    private static function siteKnowledge(): string
    {
        return "=== INFINITY SCRIMS — FULL SITE GUIDE ===\n\n"
            . "ACCOUNT\n"
            . "- Sign Up: username, email, optional phone, password. After signing up, a 6-digit code is emailed — "
            . "enter it on the verification screen to activate the account (code expires in 10 minutes, can be resent after 60s).\n"
            . "- Login: email or username + password. Unverified accounts are sent back to the verification step automatically.\n\n"
            . "DASHBOARD (dashboard.html) — the home page after login. Shows:\n"
            . "- Stat cards: Upcoming Bookings, Confirmed Bookings, Pending Payments, Total Played, Total Spent.\n"
            . "- 'Upcoming Bookings' list with a View Details link per booking.\n"
            . "- 'Explore Upcoming Scrims' — open scrims you can book a slot on.\n"
            . "- A leaderboard preview with a link to the full Leaderboard page.\n\n"
            . "BOOKING A SLOT (scrim.html) — reached by clicking a scrim card on the Dashboard:\n"
            . "- Shows scrim details: map, mode, date/time, price per slot, total/booked/remaining slots.\n"
            . "- To book you must already be the captain of a team (see My Teams below) — pick your team from the "
            . "dropdown, then click an available (green) slot on the slot grid, then confirm.\n"
            . "- A booked slot is held for 15 minutes — payment must be submitted before it expires or it's released.\n"
            . "- Once payment is verified, the Room ID & Password appear at the top of this same page.\n\n"
            . "PAYMENT (payment.html) — the step right after booking a slot:\n"
            . "- Choose a method: JazzCash, Easypaisa, or Bank Transfer — each shows the account number/title to send to.\n"
            . "- Enter the sender number, optionally a transaction ID, and upload a screenshot of the payment (max 5MB).\n"
            . "- After submitting, the booking status becomes 'pending' until an admin verifies it — usually within 15 minutes.\n\n"
            . "MY BOOKINGS (my-bookings.html) — tracks every booking:\n"
            . "- Tabs: All, Pending, Confirmed, Past, Cancelled.\n"
            . "- Pending + unpaid bookings show a 'Complete Payment' button; pending + paid shows 'View Payment Status'.\n"
            . "- Confirmed bookings show 'View Details' (which reveals the Room ID/Password) and a Cancel option before the match.\n\n"
            . "MY TEAMS (my-teams.html) — has two tabs:\n"
            . "- 'My Teams' tab: Create a team (name, tag, your in-game name/ID) — you become captain and get a join code "
            . "to share. Or Join a team using someone else's code. Each team card can expand to show the full roster; "
            . "captains can remove members.\n"
            . "- 'Team Chat' tab: pick a team (or a scrim's group chat) from the left list to open a live chat thread — "
            . "teammates can talk here about strategy, timing, etc. Messages refresh automatically every few seconds.\n\n"
            . "MAP STRATEGY (map-strategy.html) — a drawing tool for planning rotations:\n"
            . "- Switch between three maps: Erangel, Miramar, Rondo (tabs at the top).\n"
            . "- Tools: freehand pen, rotation arrows, zone circles, area boxes, text labels, select/move, pan, zoom.\n"
            . "- 'Zone Analysis' button generates a realistic shrinking-zone sequence for the selected map instantly.\n"
            . "- Drawings and notes are saved automatically per map, per user, in the browser.\n"
            . "- Export button saves the current board as a PNG image.\n\n"
            . "LEADERBOARD (leaderboard.html) — ranks teams by Today / 7 Day / All Time, showing matches, kills, wins and "
            . "points, based on published match results.\n\n"
            . "PAYMENTS (payments.html) — full payment/transaction history: total spent, successful, pending and "
            . "rejected amounts, with method, sender number, date, and a link to view the uploaded proof for each.\n\n"
            . "NOTIFICATIONS (notifications.html) — filterable by All / Unread / Bookings / Payments / Teams / System, "
            . "with a 'mark all as read' option.\n\n"
            . "PROFILE (profile.html) — shows username, email, phone, member-since date, quick stats (confirmed/played/"
            . "teams). 'Edit Profile' lets you change username, phone, avatar, and password.\n\n"
            . "SETTINGS (settings.html) — Profile tab (same edits as above), Security tab (change password), "
            . "Appearance tab (light/dark theme toggle, applies across the whole site).\n";
    }

    /** Support widget ka jawab. Returns ['answer'=>..., 'resolved'=>bool, 'escalate'=>bool] */
    public static function support(string $question, array $history = []): array
    {
        $faqs = DB::all("SELECT question, answer FROM faqs WHERE is_active=1");
        $kb = '';
        foreach ($faqs as $f) $kb .= "Q: {$f['question']}\nA: {$f['answer']}\n\n";

$system = "You are the support assistant for " . APP_NAME . ", an esports scrim booking platform in Pakistan. "
                . "You ONLY help with questions about this platform — booking slots, payments, teams, scrims, "
                . "the leaderboard, account/profile settings, and anything else covered in the site guide below. "
                . "You must NOT answer general knowledge questions, coding/programming help, questions about other "
                . "apps, websites, or platforms, or anything unrelated to " . APP_NAME . ". If the user asks something "
                . "off-topic, politely reply (in English) that you can only help with things related to " . APP_NAME
                . " and ask if they have a question about booking, payments, teams, or the platform instead — do "
                . "not attempt to answer the off-topic question in any way. "
                . "Answer in the same language the user writes (Roman Urdu / English). Keep answers short and "
                . "practical (2-5 sentences) — point to the exact page/button names from the site guide below when "
                . "walking someone through a task. You can freely answer 'how do I / where is / what does X do' "
                . "questions using the site guide, and account-specific or FAQ questions using the knowledge base. "
                . "Only reply with exactly ESCALATE if the question is about a specific payment dispute, refund, "
                . "account ban, a bug, or anything that genuinely needs a human (not covered by the guide or FAQs).\n\n"
                . self::siteKnowledge() . "\n"
                . "ADDITIONAL FAQ KNOWLEDGE BASE (admin-managed):\n" . $kb;

        $messages = $history;
        $messages[] = ['role' => 'user', 'content' => $question];

        $answer = self::call($messages, $system, 700);

        if ($answer === null) {
            return ['answer' => null, 'resolved' => false, 'escalate' => true];
        }
        if (stripos($answer, 'ESCALATE') !== false) {
            return ['answer' => null, 'resolved' => false, 'escalate' => true];
        }
        return ['answer' => trim($answer), 'resolved' => true, 'escalate' => false];
    }

    /** WhatsApp fallback link */
    public static function whatsappLink(string $prefill = ''): string
    {
        $num = DB::setting('whatsapp_number', WHATSAPP_NUMBER);
        return 'https://wa.me/' . $num . ($prefill ? '?text=' . rawurlencode($prefill) : '');
    }
}