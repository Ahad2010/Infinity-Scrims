<?php
/**
 * Common helpers
 */
require_once dirname(__DIR__) . '/config/database.php';

// ---------- Output / escaping ----------
function e(?string $s): string { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

function json_out($data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function ok($data = [], string $msg = 'Success'): void
{
    json_out(['success' => true, 'message' => $msg, 'data' => $data]);
}

function fail(string $msg = 'Something went wrong', int $code = 400, $errors = null): void
{
    json_out(['success' => false, 'message' => $msg, 'errors' => $errors], $code);
}

// ---------- Input ----------
function input(string $key, $default = null)
{
    static $body = null;
    if ($body === null) {
        $raw  = file_get_contents('php://input');
        $body = json_decode($raw, true) ?: [];
    }
    if (isset($_POST[$key])) return is_string($_POST[$key]) ? trim($_POST[$key]) : $_POST[$key];
    if (isset($body[$key]))  return is_string($body[$key]) ? trim($body[$key]) : $body[$key];
    if (isset($_GET[$key]))  return is_string($_GET[$key]) ? trim($_GET[$key]) : $_GET[$key];
    return $default;
}

function require_method(string $method): void
{
    if ($_SERVER['REQUEST_METHOD'] !== strtoupper($method)) {
        fail('Method not allowed. Use ' . strtoupper($method), 405);
    }
}

function require_fields(array $fields): array
{
    $out = [];
    $missing = [];
    foreach ($fields as $f) {
        $v = input($f);
        if ($v === null || $v === '') $missing[] = $f;
        $out[$f] = $v;
    }
    if ($missing) fail('These fields are required: ' . implode(', ', $missing), 422, $missing);
    return $out;
}

// ---------- Format ----------
function money($amount): string
{
    return CURRENCY . ' ' . number_format((float)$amount, 0);
}

function time_ago(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 60)     return 'abhi abhi';
    if ($diff < 3600)   return floor($diff / 60) . ' min ago';
    if ($diff < 86400)  return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    return date('d M Y', strtotime($datetime));
}

function random_code(int $len = 8): string
{
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // confusing chars hata diye
    $out = '';
    for ($i = 0; $i < $len; $i++) $out .= $chars[random_int(0, strlen($chars) - 1)];
    return $out;
}

// ---------- File upload (Cloudinary) ----------
// Railway's filesystem is ephemeral (no volume needed, no lost files on
// redeploy), so uploads go straight to Cloudinary instead of local disk.
// upload_image() now returns a full https:// URL, which is stored directly
// in the DB (see img_url() below for how it's read back).
function upload_image(string $field, string $folder): string
{
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        fail('File upload failed. Please try again.', 422);
    }
    $file = $_FILES[$field];

    if ($file['size'] > MAX_UPLOAD_MB * 1024 * 1024) {
        fail('File bohat bari hai. Max ' . MAX_UPLOAD_MB . 'MB.', 422);
    }

    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!isset($allowed[$mime])) fail('Only JPG, PNG or WEBP are allowed.', 422);

    if (!CLOUDINARY_CLOUD_NAME || !CLOUDINARY_API_KEY || !CLOUDINARY_API_SECRET) {
        fail('Image upload is not configured (Cloudinary env vars missing).', 500);
    }

    $timestamp = time();
    $cloudFolder = 'infinity-scrims/' . $folder;
    $params = ['folder' => $cloudFolder, 'timestamp' => $timestamp];
    ksort($params);
    $paramString = '';
    foreach ($params as $k => $v) $paramString .= ($paramString !== '' ? '&' : '') . $k . '=' . $v;
    $signature = sha1($paramString . CLOUDINARY_API_SECRET);

    $postFields = [
        'file'      => new CURLFile($file['tmp_name'], $mime, $field),
        'api_key'   => CLOUDINARY_API_KEY,
        'timestamp' => $timestamp,
        'folder'    => $cloudFolder,
        'signature' => $signature,
    ];

    $ch = curl_init('https://api.cloudinary.com/v1_1/' . CLOUDINARY_CLOUD_NAME . '/image/upload');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $response = curl_exec($ch);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) fail('Image upload failed (network): ' . $curlErr, 500);

    $data = json_decode($response, true);
    if (!isset($data['secure_url'])) {
        fail('Image upload failed: ' . ($data['error']['message'] ?? 'unknown Cloudinary error'), 500);
    }

    return $data['secure_url'];   // full URL, stored as-is in DB
}

// Reads an image field back out — handles both new Cloudinary URLs (full
// https://...) and any older rows still holding a local relative path from
// before the Cloudinary switch.
function img_url(?string $path): ?string
{
    if (!$path) return null;
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) return $path;
    return UPLOAD_URL . '/' . $path;
}

// ---------- Notifications ----------
function notify(int $userId, string $type, string $title, ?string $body = null, ?string $link = null): void
{
    DB::insert('notifications', [
        'user_id' => $userId,
        'type'    => $type,
        'title'   => $title,
        'body'    => $body,
        'link'    => $link,
    ]);
}

/** Poori team ko notify karo */
function notify_team(int $teamId, string $type, string $title, ?string $body = null, ?string $link = null): void
{
    $members = DB::all("SELECT user_id FROM team_members WHERE team_id = ?", [$teamId]);
    foreach ($members as $m) notify((int)$m['user_id'], $type, $title, $body, $link);
}

// ---------- Chat ----------
function system_message(int $roomId, int $byUserId, string $text): void
{
    DB::insert('chat_messages', [
        'room_id'   => $roomId,
        'user_id'   => $byUserId,
        'message'   => $text,
        'is_system' => 1,
    ]);
}

// ---------- Slot hold cleanup ----------
/** Expired holds ko wapas available karo (har request pe sasta cleanup) */
function release_expired_holds(): void
{
    DB::run("UPDATE slots SET status='available', team_id=NULL, held_until=NULL
             WHERE status='held' AND held_until IS NOT NULL AND held_until < NOW()");
}