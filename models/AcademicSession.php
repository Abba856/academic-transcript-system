<?php
/**
 * Academic Session Model
 */

namespace Models;

use Helpers\Database;
use PDO;

class AcademicSession {
    public static function format(array $row): array {
        return [
            'id'           => (int)$row['id'],
            'session_name' => $row['session_name'],
            'name'         => $row['session_name'],
            'start_date'   => $row['start_date'],
            'startDate'    => $row['start_date'],
            'end_date'     => $row['end_date'],
            'endDate'      => $row['end_date'],
            'status'       => $row['status'],
            'is_active'    => (bool)$row['is_active'],
            'isActive'     => (bool)$row['is_active'],
            'created_at'   => $row['created_at'] ?? null,
        ];
    }

    public static function all(): array {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM `academic_sessions` ORDER BY `session_name` DESC");
        return array_map([self::class, 'format'], $stmt->fetchAll());
    }

    public static function getActive(): ?array {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM `academic_sessions` WHERE is_active = 1 LIMIT 1");
        $row = $stmt->fetch();
        return $row ? self::format($row) : null;
    }

    public static function findById(int $id): ?array {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM `academic_sessions` WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ? self::format($row) : null;
    }

    public static function create(array $data): int {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO `academic_sessions` (`session_name`, `start_date`, `end_date`, `status`, `is_active`)
            VALUES (:name, :start, :end, :status, :active)
        ");
        $stmt->execute([
            ':name'   => trim($data['session_name'] ?? $data['name']),
            ':start'  => !empty($data['start_date'] ?? $data['startDate']) ? ($data['start_date'] ?? $data['startDate']) : null,
            ':end'    => !empty($data['end_date'] ?? $data['endDate']) ? ($data['end_date'] ?? $data['endDate']) : null,
            ':status' => $data['status'] ?? 'Upcoming',
            ':active' => !empty($data['is_active'] ?? $data['isActive']) ? 1 : 0
        ]);
        return (int)$db->lastInsertId();
    }

    public static function update(int $id, array $data): bool {
        $db = Database::getInstance();
        $fields = [
            'session_name' => $data['session_name'] ?? $data['name'] ?? null,
            'start_date'   => $data['start_date'] ?? $data['startDate'] ?? null,
            'end_date'     => $data['end_date'] ?? $data['endDate'] ?? null,
            'status'       => $data['status'] ?? null,
        ];

        $setParts = [];
        $params = [':id' => $id];
        foreach ($fields as $col => $val) {
            if ($val !== null) {
                $setParts[] = "`{$col}` = :{$col}";
                $params[":{$col}"] = $val;
            }
        }

        if (empty($setParts)) return true;

        $sql = "UPDATE `academic_sessions` SET " . implode(', ', $setParts) . " WHERE `id` = :id";
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    }

    public static function setActive(int $id): bool {
        $db = Database::getInstance();
        // Deactivate all, then set active
        $db->query("UPDATE `academic_sessions` SET `is_active` = 0, `status` = 'Closed' WHERE `status` = 'Active'");
        $stmt = $db->prepare("UPDATE `academic_sessions` SET `is_active` = 1, `status` = 'Active' WHERE `id` = :id");
        return $stmt->execute([':id' => $id]);
    }
}
