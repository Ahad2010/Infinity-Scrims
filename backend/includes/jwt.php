<?php
/**
 * Minimal JWT (HS256) — encode/decode/verify. No external library needed.
 * This replaces PHP's native cookie-based $_SESSION for auth, so the User
 * app and Owner Panel (same origin, different folders) never again share
 * a login just because they share a browser session cookie.
 */
class JWT
{
    private static function b64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function b64urlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', (4 - strlen($data) % 4) % 4));
    }

    /** Builds a signed token. $payload should include at least 'sub' (user id). */
    public static function encode(array $payload): string
    {
        $header = self::b64url(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload['iat'] = time();
        $payload['exp'] = time() + JWT_TTL_SECONDS;
        $body = self::b64url(json_encode($payload));
        $sig = self::b64url(hash_hmac('sha256', "$header.$body", JWT_SECRET, true));
        return "$header.$body.$sig";
    }

    /** Verifies signature + expiry. Returns the decoded payload array, or null if invalid/expired. */
    public static function decode(?string $token): ?array
    {
        if (!$token) return null;
        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;
        [$header, $body, $sig] = $parts;

        $expectedSig = self::b64url(hash_hmac('sha256', "$header.$body", JWT_SECRET, true));
        if (!hash_equals($expectedSig, $sig)) return null;

        $payload = json_decode(self::b64urlDecode($body), true);
        if (!is_array($payload)) return null;
        if (!isset($payload['exp']) || time() > (int) $payload['exp']) return null;

        return $payload;
    }

    /** Pulls the Bearer token out of the Authorization header, if present. */
    public static function fromRequest(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']  // some Apache/FastCGI setups strip it otherwise
            ?? '';

        if ($header === '' && function_exists('getallheaders')) {
            foreach (getallheaders() as $name => $value) {
                if (strcasecmp($name, 'Authorization') === 0) { $header = $value; break; }
            }
        }

        if (preg_match('/Bearer\s+(\S+)/i', $header, $m)) return $m[1];
        return null;
    }
}
