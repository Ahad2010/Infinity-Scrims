<?php
/** Issues and sends a fresh OTP for a not-yet-verified account. */
require_once __DIR__ . '/../_bootstrap.php';
require_once dirname(dirname(__DIR__)) . '/includes/mailer.php';
require_method('POST');

$d = require_fields(['email']);
$email = strtolower(trim($d['email']));

$user = DB::one("SELECT id, username, email_verified FROM users WHERE email = ?", [$email]);
if (!$user) fail('Account not found.', 404);
if ((int)$user['email_verified'] === 1) fail('This email is already verified.', 409);

// Simple rate limit — one resend per 30 seconds per account.
$last = DB::val("SELECT created_at FROM email_otps WHERE user_id=? ORDER BY id DESC LIMIT 1", [$user['id']]);
if ($last && (time() - strtotime($last)) < 30) {
    fail('Please wait a moment and try again.', 429);
}

$code = issueEmailOtp((int)$user['id']);
$sent = sendVerificationEmail($email, $user['username'], $code);

ok(['email_sent' => $sent], $sent ? 'New code sent.' : 'Failed to send email, please try again.');
