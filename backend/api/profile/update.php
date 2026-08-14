<?php
/** User apni profile edit kare — username/phone/avatar, optionally password (PRD gap #2) */
require_once __DIR__ . '/../_bootstrap.php';
require_method('POST');
$u = Auth::requireLogin();
Auth::verifyCsrf();

$update = [];

// ---------- Username ----------
$username = input('username');
if ($username !== null && $username !== '' && $username !== $u['username']) {
    $username = trim($username);
    if (!preg_match('/^[a-zA-Z0-9_ ]{3,40}$/', $username)) {
        fail('Username must be 3-40 characters (letters, numbers, underscore).', 422);
    }
    if (DB::val("SELECT id FROM users WHERE username = ? AND id != ?", [$username, $u['id']])) {
        fail('This username is already taken.', 409);
    }
    $update['username'] = $username;
}

// ---------- Phone ----------
$phone = input('phone');
if ($phone !== null) {
    $phone = trim($phone);
    if ($phone !== '' && !preg_match('/^[0-9+ -]{7,20}$/', $phone)) {
        fail('Phone number is not valid.', 422);
    }
    $update['phone'] = $phone === '' ? null : $phone;
}

// ---------- Avatar (optional file upload) ----------
if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    $update['avatar'] = upload_image('avatar', 'avatars');
}

// ---------- Password change (optional — needs current password) ----------
$newPassword = input('new_password');
if ($newPassword !== null && $newPassword !== '') {
    $currentPassword = input('current_password');
    if (!$currentPassword) {
        fail('Current password is required to change your password.', 422);
    }
    $row = DB::one("SELECT password_hash FROM users WHERE id = ?", [$u['id']]);
    if (!$row || !password_verify($currentPassword, $row['password_hash'])) {
        fail('Current password ghalat hai.', 403);
    }
    if (strlen($newPassword) < 6) {
        fail('New password must be at least 6 characters.', 422);
    }
    $update['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
}

if (empty($update)) {
    fail('Nothing was provided to update.', 422);
}

DB::update('users', $update, 'id = :id', ['id' => $u['id']]);

$fresh = DB::one(
    "SELECT id, username, email, phone, avatar, role, status, theme FROM users WHERE id = ?",
    [$u['id']]
);
if ($fresh['avatar']) $fresh['avatar_url'] = img_url($fresh['avatar']);

ok(['user' => $fresh], 'Profile updated.');