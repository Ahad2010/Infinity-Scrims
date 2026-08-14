<?php
/**
 * PDO Database wrapper — prepared statements only (SQL injection safe)
 */
require_once __DIR__ . '/config.php';

class DB
{
    private static ?PDO $pdo = null;

    public static function conn(): PDO
    {
        if (self::$pdo === null) {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            try {
                self::$pdo = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                error_log('DB Connection failed: ' . $e->getMessage());
                if (!headers_sent()) {
                    http_response_code(500);
                    header('Content-Type: application/json; charset=utf-8');
                }
                echo json_encode([
                    'success' => false,
                    'message' => 'Could not connect to the database. Please check the server settings.',
                    'debug'   => DEBUG ? $e->getMessage() : null,
                ]);
                exit;
            }
        }
        return self::$pdo;
    }

    /** Run a query, return PDOStatement */
    public static function run(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::conn()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /** Single row (assoc array) or null */
    public static function one(string $sql, array $params = []): ?array
    {
        $row = self::run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    /** All rows */
    public static function all(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    /** Single scalar value */
    public static function val(string $sql, array $params = [])
    {
        $v = self::run($sql, $params)->fetchColumn();
        return $v === false ? null : $v;
    }

    /** INSERT helper — returns new id */
    public static function insert(string $table, array $data): int
    {
        $cols = array_keys($data);
        $ph   = array_map(fn($c) => ':' . $c, $cols);
        $sql  = "INSERT INTO `$table` (`" . implode('`,`', $cols) . "`) VALUES (" . implode(',', $ph) . ")";
        self::run($sql, $data);
        return (int) self::conn()->lastInsertId();
    }

    /** UPDATE helper — returns affected rows */
    public static function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $set = implode(', ', array_map(fn($c) => "`$c` = :$c", array_keys($data)));
        $sql = "UPDATE `$table` SET $set WHERE $where";
        return self::run($sql, array_merge($data, $whereParams))->rowCount();
    }

    public static function begin(): void  { self::conn()->beginTransaction(); }
    public static function commit(): void { self::conn()->commit(); }
    public static function rollback(): void
    {
        if (self::conn()->inTransaction()) self::conn()->rollBack();
    }

    /** settings table se value */
    public static function setting(string $key, $default = null)
    {
        $v = self::val("SELECT setting_value FROM settings WHERE setting_key = ?", [$key]);
        return $v === null ? $default : $v;
    }
}