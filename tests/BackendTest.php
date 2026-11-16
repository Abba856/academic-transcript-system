<?php
/**
 * Automated Backend Test Suite
 * Kano State Polytechnic - Academic Transcript Generating System
 */

require_once dirname(__DIR__) . '/bootstrap.php';

use Helpers\Database;
use Helpers\Security;
use Models\User;
use Models\Student;
use Models\Course;
use Models\AcademicSession;
use Models\Semester;
use Models\Result;
use Models\Transcript;
use Models\Verification;
use Models\GradingScale;
use Models\Setting;
use Services\GPAService;
use Services\CGPAService;
use Services\TranscriptService;
use Services\QRCodeService;
use Services\PDFService;

class BackendTestSuite {
    private int $passed = 0;
    private int $failed = 0;
    private array $errors = [];

    public function run(): void {
        echo "====================================================================\n";
        echo " KANO STATE POLYTECHNIC - ACADEMIC TRANSCRIPT SYSTEM TEST SUITE\n";
        echo "====================================================================\n\n";

        $this->testDatabaseConnection();
        $this->testAuthenticationAndHashing();
        $this->testStudentModel();
        $this->testCourseModel();
        $this->testSessionsAndSemesters();
        $this->testGPACalculationEngine();
        $this->testCGPAAndClassificationEngine();
        $this->testResultManagement();
        $this->testQRCodeGeneration();
        $this->testPDFGeneration();
        $this->testTranscriptGenerationService();
        $this->testPublicVerificationModule();
        $this->testTranscriptRevocation();
        $this->testSystemSettings();

        echo "\n--------------------------------------------------------------------\n";
        echo " TEST RESULTS: {$this->passed} Passed, {$this->failed} Failed\n";
        echo "--------------------------------------------------------------------\n";

        if ($this->failed > 0) {
            echo "\nFailed Assertions:\n";
            foreach ($this->errors as $err) {
                echo " - " . $err . "\n";
            }
            exit(1);
        } else {
            echo " ALL BACKEND TESTS PASSED SUCCESSFULLY!\n\n";
            exit(0);
        }
    }

    private function assert(string $testName, bool $condition, string $detail = ''): void {
        if ($condition) {
            $this->passed++;
            echo " [PASS] {$testName}\n";
        } else {
            $this->failed++;
            $msg = "{$testName} - " . ($detail ?: 'Assertion failed');
            $this->errors[] = $msg;
            echo " [FAIL] {$msg}\n";
        }
    }

    private function testDatabaseConnection(): void {
        try {
            $db = Database::getInstance();
            $stmt = $db->query("SELECT 1");
            $this->assert("Database PDO Connection", $stmt->fetchColumn() === 1);

            $tbls = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            $this->assert("Database Tables Present", count($tbls) >= 15, "Found " . count($tbls) . " tables");
        } catch (\Throwable $e) {
            $this->assert("Database PDO Connection", false, $e->getMessage());
        }
    }

    private function testAuthenticationAndHashing(): void {
        $user = User::findByEmailOrUsername('admin');
        $this->assert("Super Admin User Exists", $user !== null && $user['username'] === 'admin');

        $valid = Security::verifyPassword('admin123', $user['password']);
        $this->assert("Password Verification (admin123)", $valid === true);

        $invalid = Security::verifyPassword('wrongpassword', $user['password']);
        $this->assert("Invalid Password Rejected", $invalid === false);

        $token = Security::generateToken(['user_id' => $user['id'], 'role' => 'Super Admin'], 3600);
        $this->assert("JWT Token Generation", !empty($token));

        $payload = Security::verifyToken($token);
        $this->assert("JWT Token Verification", $payload !== null && $payload['user_id'] === $user['id']);
    }

    private function testStudentModel(): void {
        $students = Student::all();
        $this->assert("Retrieve Student Directory", count($students) >= 8);

        $student = Student::findById(1);
        $this->assert("Find Student by ID (1)", $student !== null && $student['matric_number'] === 'KSP/CS/2023/001');

        $byMatric = Student::findById('KSP/CS/2023/001');
        $this->assert("Find Student by Matric Number", $byMatric !== null && $byMatric['id'] === 1);
        $this->assert("Student camelCase & snake_case Compatibility", isset($byMatric['firstName']) && isset($byMatric['first_name']));
    }

    private function testCourseModel(): void {
        $courses = Course::all();
        $this->assert("Retrieve Course Catalog", count($courses) >= 18);

        $c = Course::findById('COM 111');
        $this->assert("Find Course by Code (COM 111)", $c !== null && $c['credit_unit'] === 3);

        $nd1Courses = Course::all(['level' => 'ND I', 'semester' => 'First Semester']);
        $this->assert("Filter Courses by Level & Semester", count($nd1Courses) === 5);
    }

    private function testSessionsAndSemesters(): void {
        $activeSession = AcademicSession::getActive();
        $this->assert("Retrieve Active Academic Session", $activeSession !== null && $activeSession['session_name'] === '2024/2025');

        $activeSem = Semester::getActive();
        $this->assert("Retrieve Active Semester", $activeSem !== null && $activeSem['semester_name'] === 'First Semester');
    }

    private function testGPACalculationEngine(): void {
        // Test theoretical scenario:
        // COM 111: 3 units, Grade A (4.00) => 12.00 QP
        // COM 112: 3 units, Grade AB (3.50) => 10.50 QP
        // COM 113: 3 units, Grade A (4.00) => 12.00 QP
        // GNS 101: 2 units, Grade B (3.25) => 6.50 QP
        // MTH 111: 2 units, Grade AB (3.50) => 7.00 QP
        // Total Units = 13, Total Points = 48.00 => GPA = 48.00 / 13 = 3.6923 => 3.69
        $courses = [
            ['credit_unit' => 3, 'grade_point' => 4.00],
            ['credit_unit' => 3, 'grade_point' => 3.50],
            ['credit_unit' => 3, 'grade_point' => 4.00],
            ['credit_unit' => 2, 'grade_point' => 3.25],
            ['credit_unit' => 2, 'grade_point' => 3.50],
        ];

        $res = GPAService::calculate($courses);
        $this->assert("GPA Math: Total Credit Units (13)", $res['total_units'] === 13);
        $this->assert("GPA Math: Total Quality Points (48.00)", $res['total_quality_points'] === 48.0);
        $this->assert("GPA Math: Accurate Division (3.69)", $res['gpa'] === 3.69);
    }

    private function testCGPAAndClassificationEngine(): void {
        $perf = CGPAService::calculateForStudent(1);
        $this->assert("Student 1 CGPA Calculation", $perf['cgpa'] > 0);
        $this->assert("Student 1 Performance Classification", in_array($perf['classification'], ['Distinction', 'Upper Credit']));
        $this->assert("Semester Breakdown Generated", count($perf['semesters']) >= 2);
    }

    private function testResultManagement(): void {
        $results = Result::all(['studentId' => 1]);
        $this->assert("Fetch Student Results", count($results) >= 10);

        // Test saving a new result for student 3 (Abubakar Sani)
        $newResult = Result::save([
            'student_id'          => 3,
            'course_code'         => 'COM 111',
            'academic_session_id' => 3,
            'semester_name'       => 'First Semester',
            'academic_level'      => 'ND I',
            'ca_score'            => 30.00,
            'examination_score'   => 48.00,
            'status'              => 'Draft'
        ], 1);

        $this->assert("Save Examination Result", $newResult !== null && $newResult['total_score'] === 78.0);
        $this->assert("Result Grade Derived Dynamically (A)", $newResult['letter_grade'] === 'A');
        $this->assert("Result Quality Point Computed (12.00)", $newResult['quality_points'] === 12.0);

        // Approve it
        $approved = Result::approve([$newResult['id']], 1);
        $this->assert("Approve Examination Result", $approved === true);
    }

    private function testQRCodeGeneration(): void {
        $url = "http://localhost:8000/verify.html?token=test_security_token_123456";
        $path = QRCodeService::generate($url, "test_qr_code");
        $fullPath = dirname(__DIR__) . '/' . $path;
        $this->assert("QR Code SVG Generated", file_exists($fullPath) && filesize($fullPath) > 100);
    }

    private function testPDFGeneration(): void {
        $transcript = Transcript::findById(1);
        $pdfPath = PDFService::generateTranscript($transcript, 'test_transcript_doc');
        $fullPath = dirname(__DIR__) . '/' . $pdfPath;
        $this->assert("Official PDF Document Created", file_exists($fullPath) && filesize($fullPath) > 500);

        // Verify PDF Magic Bytes
        $header = file_get_contents($fullPath, false, null, 0, 8);
        $this->assert("PDF 1.4 Binary Header Valid", str_starts_with($header, '%PDF-1.'));
    }

    private function testTranscriptGenerationService(): void {
        // Generate transcript for Student 2 (Fatima Bello Umar)
        $transcript = TranscriptService::generate(2, ['remarks' => 'Test transcript generation'], 1);
        $this->assert("Generate Official Transcript", !empty($transcript['referenceNumber']));
        $this->assert("Reference Prefix Format (KSP/CST/TR/)", str_starts_with($transcript['referenceNumber'], 'KSP/CST/TR/'));
        $this->assert("Transcript Status is Valid", $transcript['status'] === 'Valid');
        $this->assert("Cryptographic Token Created", !empty($transcript['raw_verification_token']));
        $this->assert("PDF File Generated", file_exists(dirname(__DIR__) . '/' . $transcript['pdf_file_path']));
    }

    private function testPublicVerificationModule(): void {
        // 1. Valid verification by Reference Number
        $res = Verification::verify('KSP/CST/TR/2025/0001');
        $this->assert("Public Verification: Authentic Reference Confirmed", $res['verified'] === true && $res['status'] === 'VERIFIED');

        // 2. Valid verification by cryptographic token
        $resToken = Verification::verify('ksp-valid-token-demo-2025');
        $this->assert("Public Verification: Genuine Token Validated", $resToken['verified'] === true && $resToken['status'] === 'VERIFIED');

        // 3. Invalid reference
        $resInvalid = Verification::verify('KSP/NON_EXISTENT_REF/9999');
        $this->assert("Public Verification: Fake Reference Rejected", $resInvalid['verified'] === false && $resInvalid['status'] === 'NOT_FOUND');

        // 4. Verification audit logs recorded
        $logs = Verification::getLogs(5);
        $this->assert("Verification Logs Preserved", count($logs) >= 3);
    }

    private function testTranscriptRevocation(): void {
        // Create and revoke a transcript
        $t = TranscriptService::generate(3, ['remarks' => 'Revocation test'], 1);
        $revoked = TranscriptService::revoke($t['id'], 'Suspected examination malpractice', 1);
        $this->assert("Transcript Marked Revoked in DB", $revoked['status'] === 'Revoked');

        // Public verification must now report REVOKED
        $res = Verification::verify($t['referenceNumber']);
        $this->assert("Public Verification: Revoked Transcript Flagged", $res['verified'] === false && $res['status'] === 'REVOKED');
    }

    private function testSystemSettings(): void {
        $instName = Setting::get('institution_name');
        $this->assert("Retrieve Institution Setting", $instName === 'Kano State Polytechnic');

        $allSettings = Setting::getStructuredSettings();
        $this->assert("Structured Settings Contain Grading Scale", count($allSettings['gradingScale']) === 9);
    }
}

$suite = new BackendTestSuite();
$suite->run();
