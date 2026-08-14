<?php
/**
 * Authentication & Authorization — JWT (stateless), not PHP sessions.
 * Each app (User frontend, Owner Panel) stores its own token in localStorage
 * and sends it as `Authorization: Bearer <token>`. No cookies, no shared
 * session — two apps on the same origin can never cross-contaminate logins.
 */
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/jwt.php';

class Auth
{
    /** Currently logged in user (array) ya null — verified against the DB every request. */
    public static function user(): ?array
    {
        static $cached = false; // false = not looked up yet this request, null = looked up, no user
        if ($cached !== false) return $cached;

        $payload = JWT::decode(JWT::fromRequest());
        if (!$payload || empty($payload['sub'])) { $cached = null; return null; }

        $cached = DB::one(
            "SELECT id, username, email, phone, avatar, role, status, theme
             FROM users WHERE id = ? AND status = 'active'",
            [(int) $payload['sub']]
        );
        return $cached;
    }

    public static function id(): ?int
    {
        $u = self::user();
        return $u ? (int)$u['id'] : null;
    }

    public static function check(): bool { return self::user() !== null; }

    public static function isOwner(): bool
    {
        $u = self::user();
        return $u && $u['role'] === 'owner';
    }

    /** Signs and returns a fresh JWT for this user id — call after a successful login/verify. */
    public static function issueToken(int $userId): string
    {
        return JWT::encode(['sub' => $userId]);
    }

    // ---------- Register ----------
    public static function register(string $username, string $email, string $password, ?string $phone = null): int
    {
        $username = trim($username);
        $email    = strtolower(trim($email));

        if (!preg_match('/^[a-zA-Z0-9_ ]{3,40}$/', $username)) {
            fail('Username must be 3-40 characters (letters, numbers, underscore).', 422);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            fail('Email is not valid.', 422);
        }
        if (strlen($password) < 6) {
            fail('Password must be at least 6 characters.', 422);
        }
        if (DB::val("SELECT id FROM users WHERE email = ?", [$email])) {
            fail('This email is already registered.', 409);
        }
        if (DB::val("SELECT id FROM users WHERE username = ?", [$username])) {
            fail('This username is already taken.', 409);
        }

        $id = DB::insert('users', [
            'username'      => $username,
            'email'         => $email,
            'phone'         => $phone,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role'          => 'user',
        ]);

        notify($id, 'system', 'Welcome to ' . APP_NAME . '!', 'Apni team banayein aur pehla scrim book karein.');
        return $id;
    }

    // ---------- Login ----------
    /** Validates credentials and returns the user row. Does NOT issue a token —
     *  callers should call Auth::issueToken($user['id']) and include it in the response. */
    public static function login(string $identity, string $password): array
    {
        $identity = trim($identity);
        $user = DB::one(
            "SELECT * FROM users WHERE (email = ? OR username = ?) LIMIT 1",
            [strtolower($identity), $identity]
        );

        if (!$user || !password_verify($password, $user['password_hash'])) {
            fail('Email/username ya password ghalat hai.', 401);
        }
if ($user['status'] === 'banned') {
            fail('Your account has been banned. Please contact support.', 403, ['banned' => true]);
        }
        if ((int)$user['email_verified'] === 0) {
            json_out(['success' => false, 'message' => 'Pehle apna email verify karein.',
                      'requires_verification' => true, 'email' => $user['email']], 403);
        }

        DB::update('users', ['last_login_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $user['id']]);

        unset($user['password_hash']);
        return $user;
    }

    /** Stateless — there's no server-side session to destroy. Kept so the
     *  frontend's existing logout call still gets a clean success response;
     *  the actual "logout" is the client discarding its stored token. */
    public static function logout(): void {}

    // ---------- Guards ----------
    public static function requireLogin(): array
    {
        $u = self::user();
        if (!$u) fail('Please log in first.', 401);
        return $u;
    }

    public static function requireOwner(): array
    {
        $u = self::requireLogin();
        if ($u['role'] !== 'owner') fail('You do not have permission to do this.', 403);
        return $u;
    }

    /** Kya yeh user is team ka captain hai? */
    public static function isCaptain(int $teamId): bool
    {
        return (bool) DB::val("SELECT id FROM teams WHERE id = ? AND captain_id = ?", [$teamId, self::id()]);
    }

    // ---------- CSRF ----------
    // Not needed with Bearer-token auth: a third-party site can't read this
    // app's localStorage or forge an Authorization header on the user's
    // behalf, which is the attack CSRF tokens exist to stop for cookie auth.
    // Kept as no-ops so every existing Auth::verifyCsrf() call site still works.
    public static function csrfToken(): string { return ''; }
    public static function verifyCsrf(): void {}
}
