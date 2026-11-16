<?php
/**
 * Dashboard Metrics & Analytics Controller
 */

namespace Controllers;

use Helpers\Response;
use Helpers\Database;
use Middleware\AuthMiddleware;
use Models\Student;
use Models\Transcript;
use Models\AcademicSession;
use Models\AuditLog;
use PDO;

class DashboardController {
    public function stats(): void {
        AuthMiddleware::authenticate();

        $db = Database::getInstance();

        // Summary counts
        $totalStudents = (int)$db->query("SELECT COUNT(*) FROM students")->fetchColumn();
        $totalCourses = (int)$db->query("SELECT COUNT(*) FROM courses WHERE status = 'active'")->fetchColumn();
        $totalSessions = (int)$db->query("SELECT COUNT(*) FROM academic_sessions")->fetchColumn();
        $totalResults = (int)$db->query("SELECT COUNT(*) FROM results")->fetchColumn();
        $totalTranscripts = (int)$db->query("SELECT COUNT(*) FROM transcripts")->fetchColumn();
        $verifiedTranscripts = (int)$db->query("SELECT COUNT(*) FROM transcripts WHERE transcript_status = 'Valid'")->fetchColumn();

        // Recent students
        $recentStudents = Student::all();
        $recentStudents = array_slice($recentStudents, 0, 5);

        // Recent transcripts
        $recentTranscripts = Transcript::all();
        $recentTranscripts = array_slice($recentTranscripts, 0, 5);

        // Active session & semester
        $activeSession = AcademicSession::getActive();
        $activeSemester = (new \Models\Semester())->getActive();

        // Grade distribution
        $gradeDistStmt = $db->query("
            SELECT letter_grade AS grade, COUNT(*) AS count
            FROM results
            GROUP BY letter_grade
            ORDER BY FIELD(letter_grade, 'A', 'AB', 'B', 'BC', 'C', 'CD', 'D', 'E', 'F')
        ");
        $gradeDistribution = $gradeDistStmt->fetchAll();

        // Recent activity logs
        $recentActivities = AuditLog::all(8);

        Response::success([
            'cards' => [
                'totalStudents'       => $totalStudents,
                'totalCourses'        => $totalCourses,
                'totalSessions'       => $totalSessions,
                'totalResults'        => $totalResults,
                'totalTranscripts'    => $totalTranscripts,
                'verifiedTranscripts' => $verifiedTranscripts,
            ],
            'activeSession'      => $activeSession,
            'activeSemester'     => $activeSemester,
            'recentStudents'     => $recentStudents,
            'recentTranscripts'  => $recentTranscripts,
            'gradeDistribution'  => $gradeDistribution,
            'recentActivities'   => $recentActivities
        ]);
    }
}
