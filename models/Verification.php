<?php
/**
 * Verification Model
 */

namespace Models;

use Helpers\Database;
use Helpers\Security;
use PDO;

class Verification {
    public static function verify(string $identifier, ?string $ip = null, ?string $userAgent = null): array {
        $db = Database::getInstance();
        $ip = $ip ?: Security::getClientIp();
        $userAgent = $userAgent ?: substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 200);

        $cleanId = trim($identifier);
        $tokenHash = Security::hashSha256($cleanId);

        // Find transcript matching reference OR token hash OR token preview
        $sql = "
            SELECT t.*,
                   s.matric_number, s.first_name, s.middle_name, s.last_name, s.programme,
                   d.department_name,
                   tv.verification_token_hash, tv.verification_status, tv.issued_at,
                   u.full_name AS generated_by_name
            FROM `transcripts` t
            JOIN `students` s ON t.student_id = s.id
            LEFT JOIN `departments` d ON s.department_id = d.id
            LEFT JOIN `users` u ON t.generated_by = u.id
            LEFT JOIN `transcript_verifications` tv ON tv.transcript_id = t.id
            WHERE t.transcript_reference = :ref
               OR tv.verification_token_hash = :hash
               OR tv.verification_token_hash = :raw_hash
               OR tv.verification_token_preview = :preview
            LIMIT 1
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':ref'      => $cleanId,
            ':hash'     => $tokenHash,
            ':raw_hash' => $cleanId,
            ':preview'  => substr($cleanId, 0, 16)
        ]);

        $row = $stmt->fetch();

        if (!$row) {
            self::logAttempt($cleanId, $tokenHash, 'NOT_FOUND', $ip, $userAgent);
            return [
                'verified' => false,
                'status'   => 'NOT_FOUND',
                'message'  => 'No authentic transcript record was found matching this reference number or security token. Please confirm the credentials with Kano State Polytechnic Academic Affairs.'
            ];
        }

        $studentName = trim("{$row['first_name']} " . (!empty($row['middle_name']) ? "{$row['middle_name']} " : "") . "{$row['last_name']}");
        $ref = $row['transcript_reference'];

        if ($row['transcript_status'] === 'Revoked' || ($row['verification_status'] ?? '') === 'Revoked') {
            self::logAttempt($ref, $tokenHash, 'REVOKED', $ip, $userAgent);
            return [
                'verified'         => false,
                'status'           => 'REVOKED',
                'message'          => 'WARNING: This transcript was officially revoked by Kano State Polytechnic administration and is NO LONGER VALID.',
                'revocationReason' => $row['revocation_reason'] ?: 'Revoked by departmental authority.',
                'transcript'       => [
                    'referenceNumber'  => $ref,
                    'studentName'      => $studentName,
                    'matricNumber'     => $row['matric_number'],
                    'department'       => $row['department_name'] ?? 'Department of Computer Science',
                    'programme'        => $row['programme'],
                    'status'           => 'Revoked',
                    'revokedAt'        => $row['revoked_at']
                ]
            ];
        }

        self::logAttempt($ref, $tokenHash, 'VERIFIED', $ip, $userAgent);
        return [
            'verified'   => true,
            'status'     => 'VERIFIED',
            'message'    => 'Transcript record successfully confirmed genuine, authentic, and officially issued by Kano State Polytechnic.',
            'transcript' => [
                'referenceNumber'  => $ref,
                'studentName'      => $studentName,
                'matricNumber'     => $row['matric_number'],
                'department'       => $row['department_name'] ?? 'Department of Computer Science',
                'programme'        => $row['programme'],
                'cgpa'             => (float)$row['cgpa'],
                'classification'   => $row['classification'],
                'issueDate'        => $row['issue_date'],
                'generatedBy'      => $row['generated_by_name'] ?? 'Academic Registry',
                'status'           => 'Valid',
                'institution'      => 'Kano State Polytechnic'
            ]
        ];
    }

    public static function logAttempt(string $ref, ?string $tokenHash, string $status, string $ip, string $userAgent): void {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                INSERT INTO `verification_logs` (`reference_number`, `token_hash`, `status`, `ip_address`, `user_agent`, `created_at`)
                VALUES (:ref, :hash, :status, :ip, :ua, NOW())
            ");
            $stmt->execute([
                ':ref'    => $ref,
                ':hash'   => $tokenHash,
                ':status' => $status,
                ':ip'     => $ip,
                ':ua'     => substr($userAgent, 0, 255)
            ]);
        } catch (\Throwable $e) {
            // Silently ignore log write errors
        }
    }

    public static function getLogs(int $limit = 50): array {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT vl.*,
                   t.student_id,
                   s.first_name, s.middle_name, s.last_name, s.matric_number
            FROM `verification_logs` vl
            LEFT JOIN `transcripts` t ON vl.reference_number = t.transcript_reference
            LEFT JOIN `students` s ON t.student_id = s.id
            ORDER BY vl.id DESC
            LIMIT :lim
        ");
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        return array_map(function($r) {
            $name = trim(($r['first_name'] ?? '') . ' ' . (!empty($r['middle_name']) ? $r['middle_name'] . ' ' : '') . ($r['last_name'] ?? ''));
            return [
                'id'              => (int)$r['id'],
                'referenceNumber' => $r['reference_number'],
                'studentName'     => $name ?: 'N/A',
                'matricNumber'    => $r['matric_number'] ?? 'N/A',
                'status'          => $r['status'],
                'ipAddress'       => $r['ip_address'] ?? '127.0.0.1',
                'userAgent'       => $r['user_agent'] ?? '',
                'verifiedAt'      => $r['created_at'],
                'created_at'      => $r['created_at']
            ];
        }, $rows);
    }
}
