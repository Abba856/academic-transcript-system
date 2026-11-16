<?php
/**
 * Course Model
 */

namespace Models;

use Helpers\Database;
use PDO;

class Course {
    public static function format(array $row): array {
        return [
            'id'                 => (int)$row['id'],
            'course_code'        => $row['course_code'],
            'code'               => $row['course_code'],
            'course_title'       => $row['course_title'],
            'title'              => $row['course_title'],
            'credit_unit'        => (int)$row['credit_unit'],
            'unit'               => (int)$row['credit_unit'],
            'academic_level'     => $row['academic_level'],
            'level'              => $row['academic_level'],
            'semester_name'      => $row['semester_name'],
            'semester'           => $row['semester_name'],
            'semester_id'        => (int)($row['semester_id'] ?? 0),
            'course_description' => $row['course_description'] ?? '',
            'description'        => $row['course_description'] ?? '',
            'department_id'      => (int)($row['department_id'] ?? 1),
            'status'             => $row['status'] ?? 'active',
            'created_at'         => $row['created_at'] ?? null,
        ];
    }

    public static function all(array $filters = []): array {
        $db = Database::getInstance();
        $sql = "SELECT c.* FROM `courses` c WHERE 1=1";
        $params = [];

        if (!empty($filters['search'])) {
            $q = '%' . trim($filters['search']) . '%';
            $sql .= " AND (c.course_code LIKE :s1 OR c.course_title LIKE :s2)";
            $params[':s1'] = $q;
            $params[':s2'] = $q;
        }

        if (!empty($filters['level']) && $filters['level'] !== 'All') {
            $sql .= " AND c.academic_level = :lvl";
            $params[':lvl'] = $filters['level'];
        }

        if (!empty($filters['semester']) && $filters['semester'] !== 'All') {
            $sql .= " AND c.semester_name = :sem";
            $params[':sem'] = $filters['semester'];
        }

        if (!empty($filters['status']) && $filters['status'] !== 'All') {
            $sql .= " AND c.status = :stat";
            $params[':stat'] = $filters['status'];
        }

        $sql .= " ORDER BY c.academic_level ASC, c.semester_name ASC, c.course_code ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        return array_map([self::class, 'format'], $rows);
    }

    public static function findById(int|string $id): ?array {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM `courses` WHERE id = :id OR course_code = :code LIMIT 1");
        $stmt->execute([
            ':id'   => is_numeric($id) ? (int)$id : 0,
            ':code' => (string)$id
        ]);
        $row = $stmt->fetch();
        return $row ? self::format($row) : null;
    }

    public static function create(array $data): int {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO `courses` (
                `course_code`, `course_title`, `credit_unit`, `academic_level`,
                `semester_name`, `course_description`, `department_id`, `status`
            ) VALUES (
                :code, :title, :unit, :level,
                :semester, :description, :dept_id, :status
            )
        ");

        $stmt->execute([
            ':code'        => strtoupper(trim($data['course_code'] ?? $data['code'])),
            ':title'       => trim($data['course_title'] ?? $data['title']),
            ':unit'        => (int)($data['credit_unit'] ?? $data['unit'] ?? 3),
            ':level'       => $data['academic_level'] ?? $data['level'] ?? 'ND I',
            ':semester'    => $data['semester_name'] ?? $data['semester'] ?? 'First Semester',
            ':description' => trim($data['course_description'] ?? $data['description'] ?? ''),
            ':dept_id'     => (int)($data['department_id'] ?? 1),
            ':status'      => $data['status'] ?? 'active'
        ]);

        return (int)$db->lastInsertId();
    }

    public static function update(int $id, array $data): bool {
        $db = Database::getInstance();
        $fields = [
            'course_code'        => isset($data['code']) || isset($data['course_code']) ? strtoupper(trim($data['course_code'] ?? $data['code'])) : null,
            'course_title'       => $data['course_title'] ?? $data['title'] ?? null,
            'credit_unit'        => isset($data['unit']) || isset($data['credit_unit']) ? (int)($data['credit_unit'] ?? $data['unit']) : null,
            'academic_level'     => $data['academic_level'] ?? $data['level'] ?? null,
            'semester_name'      => $data['semester_name'] ?? $data['semester'] ?? null,
            'course_description' => $data['course_description'] ?? $data['description'] ?? null,
            'status'             => $data['status'] ?? null,
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

        $sql = "UPDATE `courses` SET " . implode(', ', $setParts) . " WHERE `id` = :id";
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    }

    public static function delete(int $id): bool {
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM `courses` WHERE `id` = :id");
        return $stmt->execute([':id' => $id]);
    }
}
