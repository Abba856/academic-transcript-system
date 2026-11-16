<?php
/**
 * Result Model
 */

namespace Models;

use Helpers\Database;
use PDO;

class Result {
    public static function format(array $row): array {
        $studentName = trim(($row['first_name'] ?? '') . ' ' . (!empty($row['middle_name']) ? $row['middle_name'] . ' ' : '') . ($row['last_name'] ?? ''));

        return [
            'id'                   => (int)$row['id'],
            'student_id'           => (int)$row['student_id'],
            'studentId'            => (int)$row['student_id'],
            'matric_number'        => $row['matric_number'] ?? '',
            'matricNumber'         => $row['matric_number'] ?? '',
            'student_name'         => $studentName,
            'studentName'          => $studentName,
            'course_id'            => (int)$row['course_id'],
            'courseId'             => (int)$row['course_id'],
            'course_code'          => $row['course_code'] ?? '',
            'courseCode'           => $row['course_code'] ?? '',
            'course_title'         => $row['course_title'] ?? '',
            'courseTitle'          => $row['course_title'] ?? '',
            'credit_unit'          => (int)$row['credit_unit'],
            'creditUnit'           => (int)$row['credit_unit'],
            'ca_score'             => (float)($row['ca_score'] ?? 0),
            'caScore'              => (float)($row['ca_score'] ?? 0),
            'examination_score'    => (float)$row['examination_score'],
            'examScore'            => (float)$row['examination_score'],
            'total_score'          => (float)$row['total_score'],
            'score'                => (float)$row['total_score'],
            'letter_grade'         => $row['letter_grade'],
            'grade'                => $row['letter_grade'],
            'grade_point'          => (float)$row['grade_point'],
            'gradePoint'           => (float)$row['grade_point'],
            'quality_points'       => (float)$row['quality_points'],
            'qualityPoint'         => (float)$row['quality_points'],
            'academic_session_id'  => (int)$row['academic_session_id'],
            'sessionId'            => (int)$row['academic_session_id'],
            'session_name'         => $row['session_name'] ?? '',
            'sessionName'          => $row['session_name'] ?? '',
            'semester_name'        => $row['semester_name'],
            'semester'             => $row['semester_name'],
            'academic_level'       => $row['academic_level'],
            'level'                => $row['academic_level'],
            'approval_status'      => $row['approval_status'],
            'status'               => $row['approval_status'],
            'created_at'           => $row['created_at'] ?? null,
            'updated_at'           => $row['updated_at'] ?? null
        ];
    }

    public static function all(array $filters = []): array {
        $db = Database::getInstance();
        $sql = "
            SELECT r.*,
                   s.matric_number, s.first_name, s.middle_name, s.last_name,
                   c.course_code, c.course_title,
                   sess.session_name
            FROM `results` r
            JOIN `students` s ON r.student_id = s.id
            JOIN `courses` c ON r.course_id = c.id
            LEFT JOIN `academic_sessions` sess ON r.academic_session_id = sess.id
            WHERE 1=1
        ";
        $params = [];

        $sid = $filters['student_id'] ?? ($filters['studentId'] ?? null);
        if (!empty($sid)) {
            $sql .= " AND r.student_id = :sid";
            $params[':sid'] = (int)$sid;
        }

        $courseCode = $filters['course_code'] ?? ($filters['courseCode'] ?? null);
        if (!empty($courseCode)) {
            $sql .= " AND c.course_code = :cc";
            $params[':cc'] = strtoupper(trim($courseCode));
        }

        if (!empty($filters['level']) && $filters['level'] !== 'All') {
            $sql .= " AND r.academic_level = :lvl";
            $params[':lvl'] = $filters['level'];
        }

        if (!empty($filters['semester']) && $filters['semester'] !== 'All') {
            $sql .= " AND r.semester_name = :sem";
            $params[':sem'] = $filters['semester'];
        }

        $sessionName = $filters['session_name'] ?? ($filters['sessionName'] ?? null);
        if (!empty($sessionName) && $sessionName !== 'All') {
            $sql .= " AND sess.session_name = :sess";
            $params[':sess'] = $sessionName;
        }

        if (!empty($filters['status']) && $filters['status'] !== 'All') {
            $sql .= " AND r.approval_status = :stat";
            $params[':stat'] = ucfirst(strtolower($filters['status']));
        }

        $sql .= " ORDER BY sess.id DESC, r.semester_name ASC, c.course_code ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        return array_map([self::class, 'format'], $rows);
    }

    public static function findById(int $id): ?array {
        $db = Database::getInstance();
        $sql = "
            SELECT r.*,
                   s.matric_number, s.first_name, s.middle_name, s.last_name,
                   c.course_code, c.course_title,
                   sess.session_name
            FROM `results` r
            JOIN `students` s ON r.student_id = s.id
            JOIN `courses` c ON r.course_id = c.id
            LEFT JOIN `academic_sessions` sess ON r.academic_session_id = sess.id
            WHERE r.id = :id
            LIMIT 1
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ? self::format($row) : null;
    }

    public static function getByStudent(int $studentId): array {
        return self::all(['studentId' => $studentId]);
    }

    public static function save(array $data, int $userId = 1): array {
        $db = Database::getInstance();

        // Resolve student_id
        $studentId = (int)($data['student_id'] ?? $data['studentId'] ?? 0);
        if ($studentId === 0 && !empty($data['matric_number'] ?? $data['matricNumber'])) {
            $s = Student::findById($data['matric_number'] ?? $data['matricNumber']);
            if ($s) $studentId = $s['id'];
        }

        // Resolve course_id and credit_unit
        $courseId = (int)($data['course_id'] ?? $data['courseId'] ?? 0);
        $creditUnit = (int)($data['credit_unit'] ?? $data['creditUnit'] ?? 3);
        $academicLevel = $data['academic_level'] ?? $data['level'] ?? 'ND I';

        if ($courseId === 0 && !empty($data['course_code'] ?? $data['courseCode'])) {
            $c = Course::findById($data['course_code'] ?? $data['courseCode']);
            if ($c) {
                $courseId = $c['id'];
                $creditUnit = $c['credit_unit'];
                $academicLevel = $c['academic_level'];
            }
        }

        // Scores calculation
        $caScore = isset($data['ca_score']) || isset($data['caScore'])
            ? (float)($data['ca_score'] ?? $data['caScore'])
            : 0.0;

        $examScore = isset($data['examination_score']) || isset($data['examScore'])
            ? (float)($data['examination_score'] ?? $data['examScore'])
            : (float)($data['score'] ?? $data['total_score'] ?? 0);

        // If total score explicitly provided and CA not provided, use total_score
        $totalScore = isset($data['score']) && !isset($data['ca_score']) && !isset($data['caScore'])
            ? (float)$data['score']
            : ($caScore + $examScore);

        if ($totalScore > 100) $totalScore = 100.0;
        if ($totalScore < 0) $totalScore = 0.0;

        // Dynamic Grading Calculation via GradingScale Model
        $gradeInfo = GradingScale::getGradeForScore($totalScore);
        $letterGrade = $gradeInfo['grade'];
        $gradePoint = $gradeInfo['grade_point'];
        $qualityPoints = round($creditUnit * $gradePoint, 2);

        // Session and Semester
        $sessionId = (int)($data['academic_session_id'] ?? $data['sessionId'] ?? 0);
        if ($sessionId === 0) {
            $activeSess = AcademicSession::getActive();
            $sessionId = $activeSess ? $activeSess['id'] : 3;
        }

        $semesterName = $data['semester_name'] ?? $data['semester'] ?? 'First Semester';
        $status = ucfirst(strtolower($data['status'] ?? $data['approval_status'] ?? 'Draft'));

        // Check if student already has a record for this course in same session & semester
        $checkStmt = $db->prepare("
            SELECT id FROM `results`
            WHERE `student_id` = :sid AND `course_id` = :cid
              AND `academic_session_id` = :sess_id AND `semester_name` = :sem
            LIMIT 1
        ");
        $checkStmt->execute([
            ':sid'     => $studentId,
            ':cid'     => $courseId,
            ':sess_id' => $sessionId,
            ':sem'     => $semesterName
        ]);
        $existingId = $checkStmt->fetchColumn();

        if ($existingId) {
            $stmt = $db->prepare("
                UPDATE `results` SET
                    `ca_score` = :ca,
                    `examination_score` = :exam,
                    `total_score` = :total,
                    `letter_grade` = :grade,
                    `grade_point` = :gp,
                    `credit_unit` = :cu,
                    `quality_points` = :qp,
                    `approval_status` = :stat,
                    `academic_level` = :lvl
                WHERE `id` = :id
            ");
            $stmt->execute([
                ':ca'    => $caScore,
                ':exam'  => $examScore,
                ':total' => $totalScore,
                ':grade' => $letterGrade,
                ':gp'    => $gradePoint,
                ':cu'    => $creditUnit,
                ':qp'    => $qualityPoints,
                ':stat'  => $status,
                ':lvl'   => $academicLevel,
                ':id'    => $existingId
            ]);
            $resultId = (int)$existingId;
        } else {
            $stmt = $db->prepare("
                INSERT INTO `results` (
                    `student_id`, `course_id`, `academic_session_id`, `semester_name`,
                    `academic_level`, `ca_score`, `examination_score`, `total_score`,
                    `letter_grade`, `grade_point`, `credit_unit`, `quality_points`,
                    `approval_status`, `entered_by`
                ) VALUES (
                    :sid, :cid, :sess_id, :sem,
                    :lvl, :ca, :exam, :total,
                    :grade, :gp, :cu, :qp,
                    :stat, :entered_by
                )
            ");
            $stmt->execute([
                ':sid'        => $studentId,
                ':cid'        => $courseId,
                ':sess_id'    => $sessionId,
                ':sem'        => $semesterName,
                ':lvl'        => $academicLevel,
                ':ca'         => $caScore,
                ':exam'       => $examScore,
                ':total'      => $totalScore,
                ':grade'      => $letterGrade,
                ':gp'         => $gradePoint,
                ':cu'         => $creditUnit,
                ':qp'         => $qualityPoints,
                ':stat'       => $status,
                ':entered_by' => $userId
            ]);
            $resultId = (int)$db->lastInsertId();
        }

        return self::findById($resultId);
    }

    public static function approve(array $ids, int $approverId = 1): bool {
        if (empty($ids)) return true;
        $db = Database::getInstance();
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "UPDATE `results` SET `approval_status` = 'Approved', `approved_by` = ?, `approved_at` = NOW() WHERE `id` IN ($placeholders)";
        $stmt = $db->prepare($sql);
        return $stmt->execute(array_merge([$approverId], $ids));
    }

    public static function delete(int $id): bool {
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM `results` WHERE `id` = :id");
        return $stmt->execute([':id' => $id]);
    }
}
