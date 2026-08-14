<?php
require_once __DIR__ . '/../_bootstrap.php';
require_once dirname(dirname(__DIR__)) . '/includes/mailer.php';
require_method('POST');

$d = require_fields(['username', 'email', 'password']);
$userId = Auth::register($d['username'], $d['email'], $d['password'], input('phone'));

$email = strtolower(trim($d['email']));
$code = issueEmailOtp($userId);
$emailSent = sendVerificationEmail($email, $d['username'], $code);

ok([
    'email'       => $email,
    'email_sent'  => $emailSent,
], $emailSent
    ? 'Account ban gaya! Verification code aapke email pe bhej diya gaya hai.'
    : 'Account ban gaya, lekin verification email bhejne mein masla hua — Resend button use karein.');
