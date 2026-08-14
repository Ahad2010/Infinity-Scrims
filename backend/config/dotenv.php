<?php
/**
 * DotEnv — bilkul chota, koi Composer package nahi.
 * .env file parse karke $_ENV / getenv() mein daal deta hai.
 */
class DotEnv
{
    public static function load(string $path): void
    {
        if (!is_file($path)) {
            // .env na ho to silently skip — config.php apne defaults use karega
            return;
        }

        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) continue;
            if (!str_contains($line, '=')) continue;

            [$key, $value] = explode('=', $line, 2);
            $key   = trim($key);
            $value = trim($value);

            // Quotes hatao agar "value" ya 'value' likha ho
            if (strlen($value) >= 2) {
                $first = $value[0]; $last = $value[-1];
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $value = substr($value, 1, -1);
                }
            }

            if ($key === '') continue;
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }

    /** getenv() se lo, na mile to $default */
    public static function get(string $key, $default = null)
    {
        $v = $_ENV[$key] ?? getenv($key);
        if ($v === false || $v === null || $v === '') return $default;

        // "true"/"false" string ko asal boolean bana do
        if (strtolower($v) === 'true')  return true;
        if (strtolower($v) === 'false') return false;

        return $v;
    }
}
