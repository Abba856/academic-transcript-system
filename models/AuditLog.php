<?php
/**
 * Audit Log Model
 */

namespace Models;

use Helpers\Database;
use Helpers\Security;
use PDO;

class AuditLog {
    public static function log(?int $userId, string $action, string $description, ?string $affectedRecord = null, ?string $ip = null): void {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                INSERT INTO `audit_logs` (`user_id`, `action`, `description`, `affected_record`, `ip_address`, `created_at`)
                VALUES (:uid, :act, :desc, :rec, :ip, NOW())
            ");
            $stmt->execute([
                ':uid'  => $userId,
                ':act'  => $action,
                ':desc' => $description,
                ':rec'  => $affectedRecord,
                ':ip'   => $ip ?: Security::getClientIp()
            ]);
        } catch (\Throwable $e) {
            // Ignore audit log failure
        }
    }

    public static function all(int $limit = 50): array {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT a.*, u.full_name, u.username
            FROM `audit_logs` a
            LEFT JOIN `users` u ON a.user_id = u.id
            ORDER BY a.id DESC
            LIMIT :lim
        ");
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
