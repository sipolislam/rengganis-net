<?php
/**
 * Database-backed Session Handler for Rengganis Net
 * Fully compatible with Stateless / Multi-Instance Serverless Deployment (Vercel + TiDB Cloud)
 * and Local Development (Laragon / Apache).
 */

require_once __DIR__ . '/../config/database.php';

class PdoSessionHandler implements SessionHandlerInterface
{
    private PDO $pdo;
    private static bool $tableChecked = false;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function open(string $path, string $name): bool
    {
        if (!self::$tableChecked) {
            try {
                $this->pdo->exec("
                    CREATE TABLE IF NOT EXISTS `sessions` (
                        `id` VARCHAR(128) NOT NULL PRIMARY KEY,
                        `data` MEDIUMTEXT NOT NULL,
                        `last_activity` INT UNSIGNED NOT NULL,
                        KEY `idx_sessions_last_activity` (`last_activity`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
                self::$tableChecked = true;
            } catch (\Throwable $e) {
                error_log('PdoSessionHandler::open warning: ' . $e->getMessage());
            }
        }
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        try {
            $stmt = $this->pdo->prepare("SELECT data FROM sessions WHERE id = ? AND last_activity > ?");
            // Allow sessions within gc_maxlifetime (default 24h = 86400s)
            $lifetime = (int)ini_get('session.gc_maxlifetime') ?: 86400;
            $stmt->execute([$id, time() - $lifetime]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && isset($row['data'])) {
                return (string)$row['data'];
            }
            return '';
        } catch (\Throwable $e) {
            error_log('PdoSessionHandler::read error: ' . $e->getMessage());
            return '';
        }
    }

    public function write(string $id, string $data): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO sessions (id, data, last_activity)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE data = VALUES(data), last_activity = VALUES(last_activity)
            ");
            return $stmt->execute([$id, $data, time()]);
        } catch (\Throwable $e) {
            error_log('PdoSessionHandler::write error: ' . $e->getMessage());
            return false;
        }
    }

    public function destroy(string $id): bool
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (\Throwable $e) {
            error_log('PdoSessionHandler::destroy error: ' . $e->getMessage());
            return false;
        }
    }

    public function gc(int $max_lifetime): int|false
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE last_activity < ?");
            $stmt->execute([time() - $max_lifetime]);
            return $stmt->rowCount();
        } catch (\Throwable $e) {
            error_log('PdoSessionHandler::gc error: ' . $e->getMessage());
            return 0;
        }
    }
}

/**
 * Initialize secure application session with reverse proxy awareness and database storage.
 */
function initAppSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    // Detect HTTPS including Vercel reverse proxy / edge headers
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        || (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on')
        || (!empty($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);

    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.gc_maxlifetime', '86400'); // 24 hours

    // Explicit cookie parameters for broad compatibility
    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params([
            'lifetime' => 86400 * 7, // 7 days persistent cookie
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    } else {
        session_set_cookie_params(
            86400 * 7,
            '/; samesite=Lax',
            '',
            $isHttps,
            true
        );
    }

    // Register database session handler if database connection is available
    try {
        $pdo = getDB();
        if ($pdo instanceof PDO) {
            $handler = new PdoSessionHandler($pdo);
            session_set_save_handler($handler, true);
        }
    } catch (\Throwable $e) {
        error_log('initAppSession fallback to file sessions: ' . $e->getMessage());
    }

    session_start();
}
