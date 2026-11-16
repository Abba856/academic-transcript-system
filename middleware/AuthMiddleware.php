<?php
/**
 * Authentication Middleware
 * Validates incoming Bearer token or session.
 */

namespace Middleware;

use Helpers\Response;
use Helpers\Security;
use Helpers\Database;
use PDO;

class AuthMiddleware {
    private static ?array $currentUser = null;

    public static function authenticate(): array {
        $token = self::getBearerToken();

        if (!$token) {
            Response::unauthorized('Authentication token is missing. Please log in.');
        }

        // Check if token is standard JWT
        $payload = Security::verifyToken($token);

        // Fallback: If token was a demo token or simple ID token
        $userId = null;
        if ($payload && isset($payload['user_id'])) {
            $userId = (int)$payload['user_id'];
        } elseif (preg_match('/^demo_jwt_token_(\d+)/', $token)) {
            // Default demo admin account
            $userId = 1;
        }

        if (!$userId) {
            Response::unauthorized('Invalid or expired authentication token.');
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT u.id, u.full_name, u.username, u.email, u.role_id, u.status,
                   r.role_name, r.description AS role_description
            FROM `users` u
            JOIN `roles` r ON u.role_id = r.id
            WHERE u.id = :id AND u.status = 'active'
            LIMIT 1
        ");
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch();

        if (!$user) {
            Response::unauthorized('User account not found or deactivated.');
        }

        // Fetch user permissions
        $permStmt = $db->prepare("
            SELECT p.permission_key
            FROM `role_permissions` rp
            JOIN `permissions` p ON rp.permission_id = p.id
            WHERE rp.role_id = :role_id
        ");
        $permStmt->execute([':role_id' => $user['role_id']]);
        $user['permissions'] = $permStmt->fetchAll(PDO::FETCH_COLUMN);

        self::$currentUser = $user;
        return $user;
    }

    public static function user(): ?array {
        if (self::$currentUser !== null) {
            return self::$currentUser;
        }
        $token = self::getBearerToken();
        if (!$token) return null;

        $payload = Security::verifyToken($token);
        $userId = $payload['user_id'] ?? (preg_match('/^demo_jwt_token_(\d+)/', $token) ? 1 : null);
        if (!$userId) return null;

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                SELECT u.id, u.full_name, u.username, u.email, u.role_id, u.status, r.role_name
                FROM `users` u
                JOIN `roles` r ON u.role_id = r.id
                WHERE u.id = :id AND u.status = 'active'
                LIMIT 1
            ");
            $stmt->execute([':id' => $userId]);
            self::$currentUser = $stmt->fetch() ?: null;
            return self::$currentUser;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function getBearerToken(): ?string {
        $headers = self::getRequestHeaders();
        if (isset($headers['Authorization'])) {
            $matches = [];
            if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
                return $matches[1];
            }
            return trim($headers['Authorization']);
        }
        if (isset($headers['authorization'])) {
            $matches = [];
            if (preg_match('/Bearer\s(\S+)/', $headers['authorization'], $matches)) {
                return $matches[1];
            }
            return trim($headers['authorization']);
        }
        if (isset($_GET['token'])) {
            return trim($_GET['token']);
        }
        return null;
    }

    private static function getRequestHeaders(): array {
        if (function_exists('getallheaders')) {
            $h = getallheaders();
            if ($h !== false) return $h;
        }
        $headers = [];
        foreach ($_SERVER as $k => $v) {
            if (substr($k, 0, 5) === 'HTTP_') {
                $headerName = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($k, 5)))));
                $headers[$headerName] = $v;
            }
        }
        return $headers;
    }
}
