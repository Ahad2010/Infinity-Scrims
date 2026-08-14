<?php
require_once __DIR__ . '/../_bootstrap.php';
require_method('POST');

$d = require_fields(['identity', 'password']);
$user = Auth::login($d['identity'], $d['password']);
$token = Auth::issueToken($user['id']);
if (!empty($user['avatar'])) $user['avatar_url'] = img_url($user['avatar']);

ok(['user' => $user, 'token' => $token], 'Welcome back, ' . $user['username'] . '!');