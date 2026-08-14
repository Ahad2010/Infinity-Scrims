<?php
require_once __DIR__ . '/../_bootstrap.php';
require_method('POST');
$u = Auth::requireLogin();

$theme = input('theme') === 'dark' ? 'dark' : 'light';
DB::update('users', ['theme' => $theme], 'id = :id', ['id' => $u['id']]);
ok(['theme' => $theme], 'Theme updated.');
