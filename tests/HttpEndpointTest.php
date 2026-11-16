<?php
/**
 * HTTP Endpoint Dispatcher Test Suite
 * Tests full request/response cycle for all API endpoint scripts and router.
 */

class HttpEndpointTest {
    private int $passed = 0;
    private int $failed = 0;
    private string $apiDir;

    public function __construct() {
        $this->apiDir = dirname(__DIR__) . '/api';
    }

    public function run(): void {
        echo "====================================================================\n";
        echo " API ENDPOINTS & DISPATCHER INTEGRATION TEST\n";
        echo "====================================================================\n\n";

        // 1. Auth Login
        $loginRes = $this->runEndpoint("auth/login.php", "POST", [], ['emailOrUsername' => 'admin', 'password' => 'admin123']);
        $this->assert("POST /api/auth/login.php", $loginRes['success'] === true && !empty($loginRes['data']['token']));
        $token = $loginRes['data']['token'] ?? '';

        // 2. Auth Login Failure
        $failRes = $this->runEndpoint("auth/login.php", "POST", [], ['emailOrUsername' => 'admin', 'password' => 'wrongpass']);
        $this->assert("POST /api/auth/login.php (Invalid credentials rejected)", $failRes['success'] === false);

        // 2b. Protected endpoints reject anonymous and unsigned demo tokens
        $anonRes = $this->runEndpoint("students/index.php", "GET");
        $this->assert("GET /api/students/index.php (Anonymous rejected)", $anonRes['success'] === false);

        $demoRes = $this->runEndpoint("students/index.php", "GET", [], null, 'demo_jwt_token_1');
        $this->assert("GET /api/students/index.php (Unsigned demo token rejected)", $demoRes['success'] === false);

        // 3. Students Index
        $studentsRes = $this->runEndpoint("students/index.php", "GET", ['level' => 'ND II'], null, $token);
        $this->assert("GET /api/students/index.php (Filtered by level)", $studentsRes['success'] === true && is_array($studentsRes['data']) && count($studentsRes['data']) >= 1);

        // 4. Students Show
        $studentShow = $this->runEndpoint("students/show.php", "GET", ['id' => 1], null, $token);
        $this->assert("GET /api/students/show.php?id=1", $studentShow['success'] === true && $studentShow['data']['matric_number'] === 'KSP/CS/2023/001');

        // 5. Courses Index
        $coursesRes = $this->runEndpoint("courses/index.php", "GET", [], null, $token);
        $this->assert("GET /api/courses/index.php", $coursesRes['success'] === true && is_array($coursesRes['data']) && count($coursesRes['data']) >= 18);

        // 6. Sessions Index
        $sessRes = $this->runEndpoint("sessions/index.php", "GET", [], null, $token);
        $this->assert("GET /api/sessions/index.php", $sessRes['success'] === true && count($sessRes['data']) >= 4);

        // 7. Semesters Index
        $semRes = $this->runEndpoint("semesters/index.php", "GET", [], null, $token);
        $this->assert("GET /api/semesters/index.php", $semRes['success'] === true && count($semRes['data']) >= 6);

        // 8. Results Index
        $resultsRes = $this->runEndpoint("results/index.php", "GET", ['studentId' => 1], null, $token);
        $this->assert("GET /api/results/index.php?studentId=1", $resultsRes['success'] === true && count($resultsRes['data']) >= 10);

        // 9. Transcripts Index
        $trRes = $this->runEndpoint("transcripts/index.php", "GET", [], null, $token);
        $this->assert("GET /api/transcripts/index.php", $trRes['success'] === true && count($trRes['data']) >= 1);

        // 10. Verification Endpoint (Public)
        $verRes = $this->runEndpoint("verification/verify.php", "GET", ['ref' => 'KSP/CST/TR/2025/0001']);
        $this->assert("GET /api/verification/verify.php (Authentic Ref)", $verRes['success'] === true && $verRes['data']['verified'] === true);

        // 11. Verification Logs (authenticated staff only)
        $anonLogs = $this->runEndpoint("verification/logs.php", "GET");
        $this->assert("GET /api/verification/logs.php (Anonymous rejected)", $anonLogs['success'] === false);

        $logsRes = $this->runEndpoint("verification/logs.php", "GET", [], null, $token);
        $this->assert("GET /api/verification/logs.php", $logsRes['success'] === true && is_array($logsRes['data']));

        // 12. Dashboard Stats
        $dashRes = $this->runEndpoint("dashboard/stats.php", "GET", [], null, $token);
        $this->assert("GET /api/dashboard/stats.php", $dashRes['success'] === true && isset($dashRes['data']['cards']['totalStudents']));

        // 13. Settings Index
        $settRes = $this->runEndpoint("settings/index.php", "GET", [], null, $token);
        $this->assert("GET /api/settings/index.php", $settRes['success'] === true && isset($settRes['data']['institution']));

        // 14. Master REST Router
        $routerRes = $this->runEndpoint("index.php", "GET", ['route' => 'courses'], null, $token);
        $this->assert("Master Router /api/index.php?route=courses", $routerRes['success'] === true && count($routerRes['data']) >= 18);

        echo "\n--------------------------------------------------------------------\n";
        echo " HTTP ENDPOINT TEST RESULTS: {$this->passed} Passed, {$this->failed} Failed\n";
        echo "--------------------------------------------------------------------\n";

        if ($this->failed > 0) {
            exit(1);
        }
    }

    private function assert(string $name, bool $pass): void {
        if ($pass) {
            $this->passed++;
            echo " [PASS] {$name}\n";
        } else {
            $this->failed++;
            echo " [FAIL] {$name}\n";
        }
    }

    private function runEndpoint(string $file, string $method = 'GET', array $get = [], ?array $post = null, ?string $token = null): array {
        $scriptPath = $this->apiDir . '/' . $file;
        $phpBinary = PHP_BINARY;

        $env = [];
        $env['REQUEST_METHOD'] = $method;
        if (!empty($get)) {
            $env['QUERY_STRING'] = http_build_query($get);
        }
        if ($token) {
            $env['HTTP_AUTHORIZATION'] = "Bearer {$token}";
        }

        $inputData = $post !== null ? json_encode($post) : '';

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];

        $cmd = "{$phpBinary} " . escapeshellarg($scriptPath);
        if (!empty($get)) {
            $cmd .= ' ' . escapeshellarg('?' . http_build_query($get));
        }

        $proc = proc_open($cmd, $descriptors, $pipes, null, array_merge($_ENV, $env));
        if (!is_resource($proc)) {
            return ['success' => false, 'message' => 'Failed to start process'];
        }

        if ($inputData) {
            fwrite($pipes[0], $inputData);
        }
        fclose($pipes[0]);

        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        proc_close($proc);

        $json = json_decode($output, true);
        if ($json === null) {
            return ['success' => false, 'raw' => $output, 'stderr' => $stderr];
        }
        return $json;
    }
}

$test = new HttpEndpointTest();
$test->run();
