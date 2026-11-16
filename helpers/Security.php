<?php
/**
 * Security Helper
 * Cryptographic operations, JWT simulation/handling, CSRF, and brute-force protection.
 */

namespace Helpers;

use PDO;

class Security {
    private static ?array $securityConfig = null;

    private static function getConfig(): array {
        if (self::$securityConfig === null) {
            self::$securityConfig = require dirname(__DIR__) . '/config/security.php';
        }
        return self::$securityConfig;
    }

    public static function hashPassword(string $password): string {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    public static function verifyPassword(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }

    public static function generateToken(array $payload, int $expiry = 86400): string {
        $cfg = self::getConfig();
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $payload['exp'] = time() + $expiry;
        $payload['iat'] = time();

        $encodedHeader = self::base64UrlEncode(json_encode($header));
        $encodedPayload = self::base64UrlEncode(json_encode($payload));
        $signature = hash_hmac('sha256', "{$encodedHeader}.{$encodedPayload}", $cfg['jwt_secret'], true);
        $encodedSignature = self::base64UrlEncode($signature);

        return "{$encodedHeader}.{$encodedPayload}.{$encodedSignature}";
    }

    public static function verifyToken(string $token): ?array {
        $cfg = self::getConfig();
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;
        $signature = self::base64UrlDecode($encodedSignature);
        $expectedSignature = hash_hmac('sha256', "{$encodedHeader}.{$encodedPayload}", $cfg['jwt_secret'], true);

        if (!hash_equals($signature, $expectedSignature)) {
            return null;
        }

        $payload = json_decode(self::base64UrlDecode($encodedPayload), true);
        if (!$payload || !isset($payload['exp']) || $payload['exp'] < time()) {
            return null;
        }

        return $payload;
    }

    public static function generateRandomToken(int $bytes = 32): string {
        return bin2hex(random_bytes($bytes));
    }

    public static function hashSha256(string $data): string {
        return hash('sha256', $data);
    }

    public static function getClientIp(): string {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    public static function recordLoginAttempt(string $identifier, bool $success): void {
        try {
            $db = Database::getInstance();
            $ip = self::getClientIp();
            if ($success) {
                // Clear failed attempts on successful login
                $stmt = $db->prepare("DELETE FROM `login_attempts` WHERE `identifier` = :id OR `ip_address` = :ip");
                $stmt->execute([':id' => $identifier, ':ip' => $ip]);
            } else {
                $stmt = $db->prepare("INSERT INTO `login_attempts` (`ip_address`, `identifier`, `attempted_at`) VALUES (:ip, :id, NOW())");
                $stmt->execute([
                    ':ip' => $ip,
                    ':id' => $identifier
                ]);
            }
        } catch (\Throwable $e) {
            // Silently continue if attempt table is not ready
        }
    }

    public static function isRateLimited(string $identifier): bool {
        try {
            $cfg = self::getConfig();
            $db = Database::getInstance();
            $ip = self::getClientIp();
            $windowMinutes = (int)($cfg['lockout_duration_min'] ?? 15);
            $maxAttempts = (int)($cfg['max_login_attempts'] ?? 5);

            $stmt = $db->prepare("
                SELECT COUNT(*) FROM `login_attempts`
                WHERE (`identifier` = :id OR `ip_address` = :ip)
                  AND `attempted_at` > DATE_SUB(NOW(), INTERVAL :window MINUTE)
            ");
            $stmt->execute([
                ':id'     => $identifier,
                ':ip'     => $ip,
                ':window' => $windowMinutes
            ]);

            $fails = (int)$stmt->fetchColumn();
            return $fails >= $maxAttempts;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private static function base64UrlEncode(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
