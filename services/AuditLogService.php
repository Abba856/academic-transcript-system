<?php
/**
 * Audit Log Service
 */

namespace Services;

use Models\AuditLog;

class AuditLogService {
    public static function log(string $action, string $description, ?string $affectedRecord = null, ?int $userId = null): void {
        AuditLog::log($userId, $action, $description, $affectedRecord);
    }

    public static function getRecent(int $limit = 20): array {
        return AuditLog::all($limit);
    }
}
