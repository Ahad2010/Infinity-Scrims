<?php
require_once __DIR__ . '/../_bootstrap.php';
Auth::logout();
ok([], 'Logged out.');
