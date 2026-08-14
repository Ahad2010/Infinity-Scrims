<?php
/**
 * Infinity Scrims — Global Config
 * Values ab .env file se aati hain (config/dotenv.php).
 * .env na mile to yahan diye defaults use ho jate hain — kabhi crash nahi hoga.
 */
require_once __DIR__ . '/dotenv.php';
DotEnv::load(dirname(__DIR__) . '/.env');

// ---------- Database ----------
define('DB_HOST', DotEnv::get('DB_HOST', '127.0.0.1'));
define('DB_NAME', DotEnv::get('DB_NAME', 'infinity_scrims'));
define('DB_USER', DotEnv::get('DB_USER', 'root'));
define('DB_PASS', DotEnv::get('DB_PASS', ''));
define('DB_CHARSET', 'utf8mb4');

// ---------- App ----------
define('APP_NAME', DotEnv::get('APP_NAME', 'Infinity Scrims'));
define('BASE_URL', DotEnv::get('BASE_URL', 'http://localhost/infinity-scrims'));
define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_PATH', ROOT_PATH . '/uploads');
define('UPLOAD_URL',  BASE_URL . '/uploads');

// ---------- Business rules ----------
define('CURRENCY', 'PKR');
define('SLOT_HOLD_MINUTES', 15);   // itni der slot hold rahega payment ke liye
define('MAX_UPLOAD_MB', 5);

// ---------- AI Support ----------
define('AI_ENABLED', DotEnv::get('AI_ENABLED', true));
define('AI_API_URL', 'https://api.groq.com/openai/v1/chat/completions');
define('AI_API_KEY', DotEnv::get('AI_API_KEY', ''));   // .env mein daalein — Groq key, console.groq.com/keys se
define('AI_MODEL', DotEnv::get('AI_MODEL', 'llama-3.3-70b-versatile'));         // support chat (text-only)
define('AI_VISION_MODEL', DotEnv::get('AI_VISION_MODEL', 'meta-llama/llama-4-scout-17b-16e-instruct')); // result screenshot reading
define('WHATSAPP_NUMBER', DotEnv::get('WHATSAPP_NUMBER', '923000000000'));

// ---------- JWT (stateless auth — replaces PHP sessions) ----------
// Set a real value in .env for production. Falling back to a random per-request
// secret here would break every login, so this default is fixed but you should
// still override it — put a long random string in JWT_SECRET in .env.
define('JWT_SECRET', DotEnv::get('JWT_SECRET', 'change-this-to-a-long-random-string-in-env'));
define('JWT_TTL_SECONDS', 60 * 60 * 24 * 30); // 30 days

// ---------- Resend (email — verification codes) ----------
define('RESEND_API_KEY', DotEnv::get('RESEND_API_KEY', ''));
define('RESEND_FROM_EMAIL', DotEnv::get('RESEND_FROM_EMAIL', 'Infinity Scrims <onboarding@resend.dev>'));

// ---------- Errors ----------
// display_errors hamesha OFF — API kabhi bhi non-JSON output nahi bhejni chahiye,
// ek chhoti si PHP warning bhi JSON response ko corrupt kar deti thi ("Unexpected
// response from the server" jaisa error frontend pe). Errors ab file mein log
// hote hain (php_error.log), aur DEBUG=true hone par woh detail JSON ke "debug"
// field mein bhi aa jati hai — kabhi raw HTML/warning text response mein nahi jayega.
define('DEBUG', DotEnv::get('APP_DEBUG', true));
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', ROOT_PATH . '/php_error.log');

date_default_timezone_set('Asia/Karachi');

// ---------- Global safety net ----------
// Har API request pe: agar kahin bhi (kisi bhi file mein) ek uncaught exception
// ya fatal error ho jaye, ye handler use log kar deta hai aur ek clean JSON error
// response bhej deta hai — frontend ko kabhi raw PHP error/HTML nahi milta.
set_exception_handler(function (Throwable $e) {
    error_log('Uncaught exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode([
        'success' => false,
        'message' => 'Server mein masla ho gaya. Dobara koshish karein.',
        'debug'   => DEBUG ? $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine() : null,
    ]);
    exit;
});

register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        error_log('Fatal error: ' . $err['message'] . ' in ' . $err['file'] . ':' . $err['line']);
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Server mein masla ho gaya. Dobara koshish karein.',
                'debug'   => DEBUG ? $err['message'] . ' @ ' . $err['file'] . ':' . $err['line'] : null,
            ]);
        }
    }
});
