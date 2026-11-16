<?php
/**
 * Transcript Generation and Management Service
 */

namespace Services;

use Helpers\Database;
use Helpers\Security;
use Models\Student;
use Models\Result;
use Models\Transcript;
use Models\Setting;
use Models\AuditLog;
use Exception;
use PDO;

class TranscriptService {
    /**
     * Generate official transcript snapshot, QR verification token, and PDF document
     *
     * @param int $studentId
     * @param array $options Remarks, graduation status, etc.
     * @param int $userId ID of user performing generation
     * @return array Full transcript object
     * @throws Exception
     */
    public static function generate(int $studentId, array $options = [], int $userId = 1): array {
        $db = Database::getInstance();

        $student = Student::findById($studentId);
        if (!$student) {
            throw new Exception("Student not found with ID {$studentId}");
        }

        // Get student's approved results only; drafts must never appear on an official transcript
        $results = Result::all(['studentId' => $studentId, 'status' => 'Approved']);
        if (empty($results)) {
            throw new Exception("Cannot generate transcript: No examination results recorded for this student.");
        }

        // Compute CGPA and semester breakdown
        $cgpaData = CGPAService::calculateForStudent($studentId);
        $totalUnits = $cgpaData['total_credit_units'];
        $totalQualityPoints = $cgpaData['total_quality_points'];
        $cgpa = $cgpaData['cgpa'];
        $classification = $cgpaData['classification'];

        // Determine session range
        $startSession = $student['admission_session'] ?: '2023/2024';
        $endSession = !empty($cgpaData['semesters'])
            ? end($cgpaData['semesters'])['session_name']
            : $startSession;

        // Generate unique reference number: KSP/CST/TR/{YEAR}/{SERIAL}
        $year = date('Y');
        $randomSerial = str_pad((string)random_int(1000, 9999), 4, '0', STR_PAD_LEFT);
        $prefix = Setting::get('transcript_reference_prefix', 'KSP/CST/TR/');
        $referenceNumber = "{$prefix}{$year}/{$randomSerial}";

        // Ensure unique
        $refCheck = $db->prepare("SELECT id FROM transcripts WHERE transcript_reference = :ref LIMIT 1");
        $refCheck->execute([':ref' => $referenceNumber]);
        if ($refCheck->fetchColumn()) {
            $referenceNumber = "{$prefix}{$year}/" . str_pad((string)random_int(1000, 9999), 4, '0', STR_PAD_LEFT);
        }

        $remarks = $options['remarks'] ?? "Official Academic Transcript issued upon completion of coursework. Performance: {$classification}.";

        $db->beginTransaction();
        try {
            // 1. Insert transcript record
            $stmt = $db->prepare("
                INSERT INTO `transcripts` (
                    `student_id`, `transcript_reference`, `academic_start_session`, `academic_end_session`,
                    `issue_date`, `total_credit_units`, `total_quality_points`, `cgpa`,
                    `classification`, `remarks`, `generated_by`, `transcript_status`
                ) VALUES (
                    :sid, :ref, :start_sess, :end_sess,
                    CURDATE(), :units, :points, :cgpa,
                    :class, :remarks, :gen_by, 'Valid'
                )
            ");
            $stmt->execute([
                ':sid'        => $studentId,
                ':ref'        => $referenceNumber,
                ':start_sess' => $startSession,
                ':end_sess'   => $endSession,
                ':units'      => $totalUnits,
                ':points'     => $totalQualityPoints,
                ':cgpa'       => $cgpa,
                ':class'      => $classification,
                ':remarks'    => $remarks,
                ':gen_by'     => $userId
            ]);
            $transcriptId = (int)$db->lastInsertId();

            // 2. Insert immutable results snapshot into transcript_details
            $detailStmt = $db->prepare("
                INSERT INTO `transcript_details` (
                    `transcript_id`, `course_code`, `course_title`, `credit_unit`,
                    `examination_score`, `letter_grade`, `grade_point`, `quality_points`,
                    `academic_session`, `semester`, `academic_level`, `semester_gpa`, `cumulative_cgpa`
                ) VALUES (
                    :tid, :code, :title, :unit,
                    :score, :grade, :gp, :qp,
                    :sess, :sem, :lvl, :sem_gpa, :cgpa
                )
            ");

            // Calculate running cumulative stats for each semester
            $runningUnits = 0;
            $runningPoints = 0.0;
            $snapshotDetails = [];

            foreach ($cgpaData['semesters'] as $semData) {
                $semGpa = $semData['gpa'];
                foreach ($semData['courses'] as $c) {
                    $runningUnits += $c['credit_unit'];
                    $runningPoints += $c['quality_points'];
                    $runningCgpa = $runningUnits > 0 ? round($runningPoints / $runningUnits, 2) : 0.0;

                    $detailStmt->execute([
                        ':tid'     => $transcriptId,
                        ':code'    => $c['course_code'],
                        ':title'   => $c['course_title'],
                        ':unit'    => $c['credit_unit'],
                        ':score'   => $c['total_score'],
                        ':grade'   => $c['letter_grade'],
                        ':gp'      => $c['grade_point'],
                        ':qp'      => $c['quality_points'],
                        ':sess'    => $semData['session_name'],
                        ':sem'     => $semData['semester_name'],
                        ':lvl'     => $semData['level'],
                        ':sem_gpa' => $semGpa,
                        ':cgpa'    => $runningCgpa
                    ]);

                    $snapshotDetails[] = [
                        'course_code'          => $c['course_code'],
                        'course_title'         => $c['course_title'],
                        'credit_unit'          => $c['credit_unit'],
                        'examination_score'    => $c['total_score'],
                        'letter_grade'         => $c['letter_grade'],
                        'grade_point'          => $c['grade_point'],
                        'quality_points'       => $c['quality_points'],
                        'weighted_grade_point' => $c['quality_points'],
                        'academic_session'     => $semData['session_name'],
                        'semester'             => $semData['semester_name'],
                        'academic_level'       => $semData['level']
                    ];
                }
            }

            // 3. Cryptographic Verification Token Generation
            $rawSecurityToken = bin2hex(random_bytes(32)); // 64-char unpredictable token
            $tokenHash = Security::hashSha256($rawSecurityToken);
            $tokenPreview = substr($rawSecurityToken, 0, 16);

            $verStmt = $db->prepare("
                INSERT INTO `transcript_verifications` (
                    `transcript_id`, `verification_token_hash`, `verification_token_preview`,
                    `verification_status`, `issued_at`
                ) VALUES (
                    :tid, :hash, :preview, 'Valid', NOW()
                )
            ");
            $verStmt->execute([
                ':tid'     => $transcriptId,
                ':hash'    => $tokenHash,
                ':preview' => $tokenPreview
            ]);

            // 4. Generate QR Code
            $baseUrl = Setting::get('verification_base_url', 'http://localhost:8000/verify.html?token=');
            $verificationUrl = $baseUrl . $rawSecurityToken;
            $qrFilename = str_replace('/', '_', $referenceNumber) . '_qr';
            $qrPath = QRCodeService::generate($verificationUrl, $qrFilename);

            // 5. Generate Official PDF Document
            $transcriptDataForPdf = [
                'transcript_reference' => $referenceNumber,
                'referenceNumber'      => $referenceNumber,
                'student_name'         => $student['full_name'],
                'studentName'          => $student['full_name'],
                'matric_number'        => $student['matric_number'],
                'matricNumber'         => $student['matric_number'],
                'programme'            => $student['programme'],
                'department'           => $student['department'],
                'academic_start_session' => $startSession,
                'academic_end_session'   => $endSession,
                'issue_date'           => date('Y-m-d'),
                'issueDate'            => date('Y-m-d'),
                'total_credit_units'   => $totalUnits,
                'totalCreditUnits'     => $totalUnits,
                'total_quality_points' => $totalQualityPoints,
                'totalQualityPoints'   => $totalQualityPoints,
                'cgpa'                 => $cgpa,
                'classification'       => $classification,
                'remarks'              => $remarks,
                'qr_code_path'         => $qrPath,
                'details'              => $snapshotDetails
            ];

            $pdfFilename = str_replace('/', '_', $referenceNumber) . '.pdf';
            $pdfPath = PDFService::generateTranscript($transcriptDataForPdf, $pdfFilename);

            // Update transcript with file paths
            $updateStmt = $db->prepare("UPDATE `transcripts` SET `pdf_file_path` = :pdf WHERE `id` = :id");
            $updateStmt->execute([':pdf' => $pdfPath, ':id' => $transcriptId]);

            // 6. Audit Log
            AuditLog::log(
                $userId,
                'TRANSCRIPT_GENERATED',
                "Generated transcript ref {$referenceNumber} for {$student['full_name']} ({$student['matric_number']})",
                "TRANSCRIPT:{$transcriptId}"
            );

            $db->commit();

            $fullTranscript = Transcript::findById($transcriptId);
            $fullTranscript['raw_verification_token'] = $rawSecurityToken;
            $fullTranscript['qr_code_path'] = $qrPath;
            return $fullTranscript;

        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public static function revoke(int|string $id, string $reason, int $userId = 1): array {
        $transcript = Transcript::findById($id);
        if (!$transcript) {
            throw new Exception("Transcript not found");
        }

        Transcript::revoke($transcript['id'], $reason);

        AuditLog::log(
            $userId,
            'TRANSCRIPT_REVOKED',
            "Revoked transcript {$transcript['referenceNumber']}. Reason: {$reason}",
            "TRANSCRIPT:{$transcript['id']}"
        );

        return Transcript::findById($transcript['id']);
    }
}
