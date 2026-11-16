<?php
/**
 * Semester Model
 */

namespace Models;

use Helpers\Database;
use PDO;

class Semester {
    public static function format(array $row): array {
        return [
            'id'                  => (int)$row['id'],
            'semester_name'       => $row['semester_name'],
            'name'                => $row['semester_name'],
            'semester_code'       => $row['semester_code'],
            'code'                => $row['semester_code'],
            'academic_session_id' => (int)$row['academic_session_id'],
            'sessionId'           => (int)$row['academic_session_id'],
            'session_name'        => $row['session_name'] ?? '',
            'start_date'          => $row['start_date'],
            'startDate'           => $row['start_date'],
            'end_date'            => $row['end_date'],
            'endDate'             => $row['end_date'],
            'status'              => $row['status'],
            'is_active'           => (bool)$row['is_active'],
            'isActive'            => (bool)$row['is_active'],
            'created_at'          => $row['created_at'] ?? null,
        ];
    }

    public static function all(): array {
        $db = Database::getInstance();
        $sql = "
            SELECT sem.*, sess.session_name
            FROM `semesters` sem
            LEFT JOIN `academic_sessions` sess ON sem.academic_session_id = sess.id
            ORDER BY sem.academic_session_id DESC, sem.id ASC
        ";
        $stmt = $db->query($sql);
        return array_map([self::class, 'format'], $stmt->fetchAll());
    }

    public static function getActive(): ?array {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM `semesters` WHERE is_active = 1 LIMIT 1");
        $row = $stmt->fetch();
        return $row ? self::format($row) : null;
    }

    public static function findById(int $id): ?array {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM `semesters` WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ? self::format($row) : null;
    }

    public static function create(array $data): int {
        $db = Database::getInstance();
        $sessionId = (int)($data['academic_session_id'] ?? $data['sessionId'] ?? 0);
        if ($sessionId === 0) {
            $sess = AcademicSession::getActive();
            $sessionId = $sess ? $sess['id'] : 1;
        }

        $stmt = $db->prepare("
            INSERT INTO `semesters` (`academic_session_id`, `semester_name`, `semester_code`, `start_date`, `end_date`, `status`, `is_active`)
            VALUES (:sess_id, :name, :code, :start, :end, :status, :active)
        ");
        $stmt->execute([
            ':sess_id' => $sessionId,
            ':name'    => trim($data['semester_name'] ?? $data['name']),
            ':code'    => strtoupper(trim($data['semester_code'] ?? $data['code'] ?? 'SEM-1')),
            ':start'   => !empty($data['start_date'] ?? $data['startDate']) ? ($data['start_date'] ?? $data['startDate']) : null,
            ':end'     => !empty($data['end_date'] ?? $data['endDate']) ? ($data['end_date'] ?? $data['endDate']) : null,
            ':status'  => $data['status'] ?? 'Upcoming',
            ':active'  => !empty($data['is_active'] ?? $data['isActive']) ? 1 : 0
        ]);
        return (int)$db->lastInsertId();
    }

    public static function update(int $id, array $data): bool {
        $db = Database::getInstance();
        $fields = [
            'semester_name' => $data['semester_name'] ?? $data['name'] ?? null,
            'semester_code' => isset($data['code']) || isset($data['semester_code']) ? strtoupper(trim($data['semester_code'] ?? $data['code'])) : null,
            'start_date'    => $data['start_date'] ?? $data['startDate'] ?? null,
            'end_date'      => $data['end_date'] ?? $data['endDate'] ?? null,
            'status'        => $data['status'] ?? null,
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

        $sql = "UPDATE `semesters` SET " . implode(', ', $setParts) . " WHERE `id` = :id";
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    }

    public static function setActive(int $id): bool {
        $db = Database::getInstance();
        $db->query("UPDATE `semesters` SET `is_active` = 0, `status` = 'Closed' WHERE `status` = 'Active'");
        $stmt = $db->prepare("UPDATE `semesters` SET `is_active` = 1, `status` = 'Active' WHERE `id` = :id");
        return $stmt->execute([':id' => $id]);
    }
}
