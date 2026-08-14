<?php
/** Verifies the 6-digit signup code, marks the account verified, and logs them in. */
require_once __DIR__ . '/../_bootstrap.php';
require_method('POST');

$d = require_fields(['email', 'otp']);
$email = strtolower(trim($d['email']));
$otp = trim($d['otp']);

$user = DB::one("SELECT * FROM users WHERE email = ?", [$email]);
if (!$user) fail('Account not found.', 404);

if ((int)$user['email_verified'] === 1) {
    fail('This email is already verified. Please log in.', 409);
}

$row = DB::one(
    "SELECT id FROM email_otps WHERE user_id=? AND otp_code=? AND used=0 AND expires_at >= NOW()
     ORDER BY id DESC LIMIT 1",
    [$user['id'], $otp]
);
if (!$row) fail('Code ghalat ya expire ho chuka hai.', 422);

DB::run("UPDATE email_otps SET used = 1 WHERE id = ?", [$row['id']]);
DB::update('users', ['email_verified' => 1], 'id = :id', ['id' => $user['id']]);
DB::update('users', ['last_login_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $user['id']]);

$token = Auth::issueToken((int) $user['id']);
unset($user['password_hash']);
$user['email_verified'] = 1;

ok(['user' => $user, 'token' => $token], 'Email verified!');
