<?php
/**
 * Student Model
 */

namespace Models;

use Helpers\Database;
use PDO;

class Student {
    public static function format(array $row): array {
        return [
            'id'               => (int)$row['id'],
            'matric_number'    => $row['matric_number'],
            'matricNumber'     => $row['matric_number'],
            'first_name'       => $row['first_name'],
            'firstName'        => $row['first_name'],
            'middle_name'      => $row['middle_name'] ?? '',
            'middleName'       => $row['middle_name'] ?? '',
            'last_name'        => $row['last_name'],
            'lastName'         => $row['last_name'],
            'full_name'        => trim("{$row['first_name']} " . ($row['middle_name'] ? "{$row['middle_name']} " : "") . "{$row['last_name']}"),
            'fullName'         => trim("{$row['first_name']} " . ($row['middle_name'] ? "{$row['middle_name']} " : "") . "{$row['last_name']}"),
            'gender'           => $row['gender'],
            'date_of_birth'    => $row['date_of_birth'],
            'dob'              => $row['date_of_birth'],
            'email'            => $row['email'],
            'phone'            => $row['phone'] ?? '',
            'department_id'    => (int)($row['department_id'] ?? 1),
            'department'       => $row['department_name'] ?? 'Computer Science',
            'department_name'  => $row['department_name'] ?? 'Computer Science',
            'programme'        => $row['programme'],
            'admission_session_id' => (int)($row['admission_session_id'] ?? 1),
            'admission_session'=> $row['session_name'] ?? '2023/2024',
            'admissionSession' => $row['session_name'] ?? '2023/2024',
            'current_level'    => $row['current_level'],
            'currentLevel'     => $row['current_level'],
            'status'           => $row['status'],
            'photograph'       => $row['photograph'] ?? '',
            'avatar'           => $row['photograph'] ?? '',
            'created_at'       => $row['created_at'] ?? date('Y-m-d H:i:s'),
            'updated_at'       => $row['updated_at'] ?? null,
        ];
    }

    public static function all(array $filters = []): array {
        $db = Database::getInstance();
        $sql = "
            SELECT s.*, d.department_name, sess.session_name
            FROM `students` s
            LEFT JOIN `departments` d ON s.department_id = d.id
            LEFT JOIN `academic_sessions` sess ON s.admission_session_id = sess.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['search'])) {
            $q = '%' . trim($filters['search']) . '%';
            $sql .= " AND (s.first_name LIKE :s1 OR s.last_name LIKE :s2 OR s.matric_number LIKE :s3 OR s.email LIKE :s4)";
            $params[':s1'] = $q;
            $params[':s2'] = $q;
            $params[':s3'] = $q;
            $params[':s4'] = $q;
        }

        if (!empty($filters['level']) && $filters['level'] !== 'All') {
            $sql .= " AND s.current_level = :lvl";
            $params[':lvl'] = $filters['level'];
        }

        if (!empty($filters['session']) && $filters['session'] !== 'All') {
            $sql .= " AND (sess.session_name = :sess OR s.admission_session_id = :sess_id)";
            $params[':sess'] = $filters['session'];
            $params[':sess_id'] = is_numeric($filters['session']) ? (int)$filters['session'] : 0;
        }

        if (!empty($filters['programme']) && $filters['programme'] !== 'All') {
            $sql .= " AND s.programme = :prog";
            $params[':prog'] = $filters['programme'];
        }

        if (!empty($filters['status']) && $filters['status'] !== 'All') {
            $sql .= " AND s.status = :stat";
            $params[':stat'] = $filters['status'];
        }

        $sql .= " ORDER BY s.id DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        return array_map([self::class, 'format'], $rows);
    }

    public static function findById(int|string $id): ?array {
        $db = Database::getInstance();
        $sql = "
            SELECT s.*, d.department_name, sess.session_name
            FROM `students` s
            LEFT JOIN `departments` d ON s.department_id = d.id
            LEFT JOIN `academic_sessions` sess ON s.admission_session_id = sess.id
            WHERE s.id = :id OR s.matric_number = :matric
            LIMIT 1
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':id'     => is_numeric($id) ? (int)$id : 0,
            ':matric' => (string)$id
        ]);
        $row = $stmt->fetch();
        return $row ? self::format($row) : null;
    }

    public static function create(array $data): int {
        $db = Database::getInstance();

        // Resolve admission_session_id if passed as name
        $sessionId = (int)($data['admission_session_id'] ?? $data['admissionSessionId'] ?? 0);
        if ($sessionId === 0 && !empty($data['admissionSession'])) {
            $sessStmt = $db->prepare("SELECT id FROM academic_sessions WHERE session_name = :sn LIMIT 1");
            $sessStmt->execute([':sn' => $data['admissionSession']]);
            $sessionId = (int)$sessStmt->fetchColumn() ?: 1;
        }
        if ($sessionId === 0) $sessionId = 1;

        $deptId = (int)($data['department_id'] ?? 1);

        $stmt = $db->prepare("
            INSERT INTO `students` (
                `matric_number`, `first_name`, `middle_name`, `last_name`, `gender`,
                `date_of_birth`, `email`, `phone`, `department_id`, `programme`,
                `admission_session_id`, `current_level`, `status`, `photograph`
            ) VALUES (
                :matric, :fname, :mname, :lname, :gender,
                :dob, :email, :phone, :dept_id, :programme,
                :session_id, :level, :status, :photo
            )
        ");

        $stmt->execute([
            ':matric'     => strtoupper(trim($data['matric_number'] ?? $data['matricNumber'])),
            ':fname'      => trim($data['first_name'] ?? $data['firstName']),
            ':mname'      => trim($data['middle_name'] ?? $data['middleName'] ?? ''),
            ':lname'      => trim($data['last_name'] ?? $data['lastName']),
            ':gender'     => $data['gender'] ?? 'Male',
            ':dob'        => !empty($data['date_of_birth'] ?? $data['dob']) ? ($data['date_of_birth'] ?? $data['dob']) : null,
            ':email'      => strtolower(trim($data['email'])),
            ':phone'      => trim($data['phone'] ?? ''),
            ':dept_id'    => $deptId,
            ':programme'  => $data['programme'] ?? 'National Diploma in Computer Science',
            ':session_id' => $sessionId,
            ':level'      => $data['current_level'] ?? $data['currentLevel'] ?? 'ND I',
            ':status'     => $data['status'] ?? 'Active',
            ':photo'      => $data['photograph'] ?? $data['avatar'] ?? null
        ]);

        return (int)$db->lastInsertId();
    }

    public static function update(int $id, array $data): bool {
        $db = Database::getInstance();

        $sessionId = (int)($data['admission_session_id'] ?? $data['admissionSessionId'] ?? 0);
        if ($sessionId === 0 && !empty($data['admissionSession'])) {
            $sessStmt = $db->prepare("SELECT id FROM academic_sessions WHERE session_name = :sn LIMIT 1");
            $sessStmt->execute([':sn' => $data['admissionSession']]);
            $sessionId = (int)$sessStmt->fetchColumn() ?: null;
        }

        $fields = [
            'first_name'    => $data['first_name'] ?? $data['firstName'] ?? null,
            'middle_name'   => $data['middle_name'] ?? $data['middleName'] ?? null,
            'last_name'     => $data['last_name'] ?? $data['lastName'] ?? null,
            'gender'        => $data['gender'] ?? null,
            'date_of_birth' => $data['date_of_birth'] ?? $data['dob'] ?? null,
            'email'         => isset($data['email']) ? strtolower(trim($data['email'])) : null,
            'phone'         => $data['phone'] ?? null,
            'programme'     => $data['programme'] ?? null,
            'current_level' => $data['current_level'] ?? $data['currentLevel'] ?? null,
            'status'        => $data['status'] ?? null,
        ];

        if ($sessionId) {
            $fields['admission_session_id'] = $sessionId;
        }
        if (!empty($data['matric_number'] ?? $data['matricNumber'])) {
            $fields['matric_number'] = strtoupper(trim($data['matric_number'] ?? $data['matricNumber']));
        }

        $setParts = [];
        $params = [':id' => $id];
        foreach ($fields as $col => $val) {
            if ($val !== null) {
                $setParts[] = "`{$col}` = :{$col}";
                $params[":{$col}"] = $val;
            }
        }

        if (empty($setParts)) return true;

        $sql = "UPDATE `students` SET " . implode(', ', $setParts) . " WHERE `id` = :id";
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    }

    public static function delete(int $id): bool {
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM `students` WHERE `id` = :id");
        return $stmt->execute([':id' => $id]);
    }
}
