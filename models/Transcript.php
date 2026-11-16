<?php
/**
 * Transcript Model
 */

namespace Models;

use Helpers\Database;
use PDO;

class Transcript {
    public static function format(array $row): array {
        $studentName = trim(($row['first_name'] ?? '') . ' ' . (!empty($row['middle_name']) ? $row['middle_name'] . ' ' : '') . ($row['last_name'] ?? ''));

        return [
            'id'                   => (int)$row['id'],
            'referenceNumber'      => $row['transcript_reference'],
            'transcript_reference' => $row['transcript_reference'],
            'studentId'            => (int)$row['student_id'],
            'student_id'           => (int)$row['student_id'],
            'studentName'          => $studentName ?: ($row['student_name'] ?? 'Student'),
            'student_name'         => $studentName ?: ($row['student_name'] ?? 'Student'),
            'matricNumber'         => $row['matric_number'] ?? '',
            'matric_number'        => $row['matric_number'] ?? '',
            'programme'            => $row['programme'] ?? 'National Diploma in Computer Science',
            'department'           => $row['department_name'] ?? 'Department of Computer Science',
            'admissionYear'        => !empty($row['academic_start_session']) ? explode('/', $row['academic_start_session'])[0] : '2022',
            'graduationYear'       => !empty($row['academic_end_session']) ? $row['academic_end_session'] : 'In Progress',
            'academic_start_session' => $row['academic_start_session'] ?? '',
            'academic_end_session'   => $row['academic_end_session'] ?? '',
            'issueDate'            => $row['issue_date'],
            'issue_date'           => $row['issue_date'],
            'cgpa'                 => (float)$row['cgpa'],
            'totalCreditUnits'     => (int)$row['total_credit_units'],
            'total_credit_units'   => (int)$row['total_credit_units'],
            'totalQualityPoints'   => (float)$row['total_quality_points'],
            'total_quality_points' => (float)$row['total_quality_points'],
            'classification'       => $row['classification'],
            'status'               => $row['transcript_status'],
            'transcript_status'    => $row['transcript_status'],
            'remarks'              => $row['remarks'] ?? '',
            'generatedBy'          => $row['generated_by_name'] ?? 'Academic Registry',
            'pdf_file_path'        => $row['pdf_file_path'] ?? '',
            'revocationReason'     => $row['revocation_reason'] ?? '',
            'revocation_reason'    => $row['revocation_reason'] ?? '',
            'revokedAt'            => $row['revoked_at'] ?? null,
            'revoked_at'           => $row['revoked_at'] ?? null,
            'verification_token'   => $row['verification_token_hash'] ?? null,
            'verification_token_preview' => $row['verification_token_preview'] ?? null,
            'qr_code_path'         => $row['qr_code_path'] ?? null,
            'created_at'           => $row['created_at'] ?? null
        ];
    }

    public static function all(array $filters = []): array {
        $db = Database::getInstance();
        $sql = "
            SELECT t.*,
                   s.matric_number, s.first_name, s.middle_name, s.last_name, s.programme,
                   d.department_name,
                   u.full_name AS generated_by_name,
                   tv.verification_token_hash, tv.verification_token_preview
            FROM `transcripts` t
            JOIN `students` s ON t.student_id = s.id
            LEFT JOIN `departments` d ON s.department_id = d.id
            LEFT JOIN `users` u ON t.generated_by = u.id
            LEFT JOIN `transcript_verifications` tv ON tv.transcript_id = t.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['search'])) {
            $q = '%' . trim($filters['search']) . '%';
            $sql .= " AND (t.transcript_reference LIKE :s1 OR s.matric_number LIKE :s2 OR s.first_name LIKE :s3 OR s.last_name LIKE :s4)";
            $params[':s1'] = $q;
            $params[':s2'] = $q;
            $params[':s3'] = $q;
            $params[':s4'] = $q;
        }

        if (!empty($filters['student_id'] ?? $filters['studentId'])) {
            $sql .= " AND t.student_id = :sid";
            $params[':sid'] = (int)($filters['student_id'] ?? $filters['studentId']);
        }

        if (!empty($filters['status']) && $filters['status'] !== 'All') {
            $sql .= " AND t.transcript_status = :stat";
            $params[':stat'] = ucfirst(strtolower($filters['status']));
        }

        $sql .= " ORDER BY t.id DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return array_map([self::class, 'format'], $stmt->fetchAll());
    }

    public static function findById(int|string $id): ?array {
        $db = Database::getInstance();
        $sql = "
            SELECT t.*,
                   s.matric_number, s.first_name, s.middle_name, s.last_name, s.programme, s.gender, s.date_of_birth,
                   d.department_name,
                   u.full_name AS generated_by_name,
                   tv.verification_token_hash, tv.verification_token_preview
            FROM `transcripts` t
            JOIN `students` s ON t.student_id = s.id
            LEFT JOIN `departments` d ON s.department_id = d.id
            LEFT JOIN `users` u ON t.generated_by = u.id
            LEFT JOIN `transcript_verifications` tv ON tv.transcript_id = t.id
            WHERE t.id = :id OR t.transcript_reference = :ref
            LIMIT 1
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':id'  => is_numeric($id) ? (int)$id : 0,
            ':ref' => (string)$id
        ]);
        $row = $stmt->fetch();
        if (!$row) return null;

        $transcript = self::format($row);

        // Fetch detailed results snapshot
        $detailStmt = $db->prepare("SELECT * FROM `transcript_details` WHERE `transcript_id` = :tid ORDER BY id ASC");
        $detailStmt->execute([':tid' => $transcript['id']]);
        $transcript['details'] = $detailStmt->fetchAll();

        return $transcript;
    }

    public static function revoke(int|string $id, string $reason, ?string $revokedBy = null): bool {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            UPDATE `transcripts`
            SET `transcript_status` = 'Revoked',
                `revocation_reason` = :reason,
                `revoked_at` = NOW()
            WHERE `id` = :id OR `transcript_reference` = :ref
        ");
        $success = $stmt->execute([
            ':reason' => $reason,
            ':id'     => is_numeric($id) ? (int)$id : 0,
            ':ref'    => (string)$id
        ]);

        if ($success) {
            // Also mark verification record as revoked
            $vStmt = $db->prepare("
                UPDATE `transcript_verifications` tv
                JOIN `transcripts` t ON tv.transcript_id = t.id
                SET tv.verification_status = 'Revoked', tv.revoked_at = NOW()
                WHERE t.id = :id OR t.transcript_reference = :ref
            ");
            $vStmt->execute([
                ':id'  => is_numeric($id) ? (int)$id : 0,
                ':ref' => (string)$id
            ]);
        }

        return $success;
    }
}
