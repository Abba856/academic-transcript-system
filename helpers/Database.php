<?php
/**
 * Database Singleton Helper
 * Handles resilient PDO connection to MySQL / MariaDB via Unix Socket or TCP/IP.
 */

namespace Helpers;

use PDO;
use PDOException;

class Database {
    private static ?PDO $instance = null;
    private static ?array $config = null;

    private function __construct() {}
    private function __clone() {}

    public static function getInstance(): PDO {
        if (self::$instance === null) {
            self::connect();
        }
        return self::$instance;
    }

    private static function connect(): void {
        if (self::$config === null) {
            self::$config = require dirname(__DIR__) . '/config/database.php';
        }

        $cfg = self::$config;
        $dsn = null;

        // Try Unix socket first if available (standard in linux / sandbox environment)
        if (!empty($cfg['socket']) && file_exists($cfg['socket'])) {
            $dsn = sprintf(
                'mysql:unix_socket=%s;dbname=%s;charset=%s',
                $cfg['socket'],
                $cfg['database'],
                $cfg['charset']
            );
        } else {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $cfg['host'],
                $cfg['port'],
                $cfg['database'],
                $cfg['charset']
            );
        }

        try {
            self::$instance = new PDO(
                $dsn,
                $cfg['username'],
                $cfg['password'],
                $cfg['options']
            );
        } catch (PDOException $e) {
            // Fallback: If socket failed, try TCP host/port or vice-versa
            if (!empty($cfg['socket']) && file_exists($cfg['socket'])) {
                $fallbackDsn = sprintf(
                    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                    $cfg['host'],
                    $cfg['port'],
                    $cfg['database'],
                    $cfg['charset']
                );
                try {
                    self::$instance = new PDO($fallbackDsn, $cfg['username'], $cfg['password'], $cfg['options']);
                    return;
                } catch (PDOException $e2) {
                    // re-throw original
                }
            }

            error_log("Database Connection Error: " . $e->getMessage());
            header('Content-Type: application/json; charset=UTF-8');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Database connection failed: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    public static function rawQuery(string $sql, array $params = []): array {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function execute(string $sql, array $params = []): bool {
        $stmt = self::getInstance()->prepare($sql);
        return $stmt->execute($params);
    }
}
