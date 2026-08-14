<?php
/** Har API file ke shuru mein include hota hai */
require_once dirname(__DIR__) . '/includes/auth.php';

// CORS_ORIGIN=* nahi chal sakta jab credentials:true bhejni ho, isliye jo bhi
// origin request bhejta hai wahi reflect kar dete hain — local dev mein frontend
// kabhi localhost:3000 se chalta hai, kabhi bina port ke, kabhi 127.0.0.1 se.
// Production mein isay ek fixed domain pe lock kar dena (CORS_ORIGIN .env se).
$allowedOrigin = DotEnv::get('CORS_ORIGIN', '');
$requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($allowedOrigin === '*' || $allowedOrigin === '') {
    header('Access-Control-Allow-Origin: ' . ($requestOrigin ?: '*'));
} elseif ($requestOrigin === $allowedOrigin) {
    header('Access-Control-Allow-Origin: ' . $allowedOrigin);
}
header('Vary: Origin');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

header('X-Content-Type-Options: nosniff');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

release_expired_holds();  // sasta background cleanup