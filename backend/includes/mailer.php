<?php
/**
 * Resend email wrapper — used for signup verification codes.
 * Never throws: a failed send shouldn't break the request that triggered it,
 * errors are logged via error_log() instead. Callers should still surface a
 * clear message to the user when sendVerificationEmail() returns false.
 */
require_once __DIR__ . '/functions.php';

function sendResendEmail(string $to, string $subject, string $textBody, ?string $htmlBody = null): bool {
    if (!RESEND_API_KEY) {
        error_log('sendResendEmail: RESEND_API_KEY is not set, skipping send.');
        return false;
    }

    $payload = [
        'from'    => RESEND_FROM_EMAIL,
        'to'      => [$to],
        'subject' => $subject,
        'text'    => $textBody,
    ];
    if ($htmlBody !== null) $payload['html'] = $htmlBody;

    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . RESEND_API_KEY,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT    => 10,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        error_log('sendResendEmail: cURL error: ' . $curlErr);
        return false;
    }
    if ($httpCode < 200 || $httpCode >= 300) {
        error_log('sendResendEmail: Resend API returned ' . $httpCode . ' — ' . $response);
        return false;
    }
    return true;
}

function sendVerificationEmail(string $to, string $username, string $code): bool {
    $subject = 'Verify your Infinity Scrims account';
    $text = "Hi {$username},\n\nYour verification code is: {$code}\n\nThis code expires in 10 minutes.";
    $html = "
      <div style=\"font-family:sans-serif; max-width:480px; margin:0 auto;\">
        <h2 style=\"margin-bottom:4px;\">Verify your account</h2>
        <p>Hi {$username},</p>
        <p>Your verification code is:</p>
        <p style=\"font-size:28px; font-weight:700; letter-spacing:6px; background:#f2f3f7; padding:14px 18px; border-radius:10px; text-align:center;\">{$code}</p>
        <p style=\"color:#6c6f7c; font-size:13px;\">This code expires in 10 minutes.</p>
      </div>";
    return sendResendEmail($to, $subject, $text, $html);
}

/** Generates + stores a fresh 6-digit OTP for a user, invalidating older unused ones. */
function issueEmailOtp(int $userId): string {
    DB::run("UPDATE email_otps SET used = 1 WHERE user_id = ? AND used = 0", [$userId]);
    $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    DB::insert('email_otps', [
        'user_id'    => $userId,
        'otp_code'   => $code,
        'expires_at' => date('Y-m-d H:i:s', time() + 600), // 10 minutes
    ]);
    return $code;
}
