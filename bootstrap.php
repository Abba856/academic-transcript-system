<?php
/**
 * Application Bootstrap & Class Autoloader
 * Kano State Polytechnic - Academic Transcript Generating System
 */

// Global Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', '0');

// Default Timezone
date_default_timezone_set('Africa/Lagos');

// Auto-populate $_GET from QUERY_STRING if running via CLI / simulated SAPI
if (empty($_GET) && !empty($_SERVER['QUERY_STRING'])) {
    parse_str($_SERVER['QUERY_STRING'], $_GET);
}

// Register Autoloader for application namespaces
spl_autoload_register(function ($class) {
    $baseDir = __DIR__ . '/';
    $prefixes = [
        'Helpers\\'     => 'helpers/',
        'Models\\'      => 'models/',
        'Services\\'    => 'services/',
        'Controllers\\' => 'controllers/',
        'Middleware\\'  => 'middleware/',
    ];

    foreach ($prefixes as $prefix => $dir) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) === 0) {
            $relativeClass = substr($class, $len);
            $file = $baseDir . $dir . str_replace('\\', '/', $relativeClass) . '.php';
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    }
});

// Ensure storage directories exist
$storageDirs = [
    __DIR__ . '/storage',
    __DIR__ . '/storage/transcripts',
    __DIR__ . '/storage/qrcodes',
    __DIR__ . '/storage/uploads'
];
foreach ($storageDirs as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
}

// Global Exception Handler
set_exception_handler(function (\Throwable $e) {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json; charset=UTF-8');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error: ' . $e->getMessage(),
        'error'   => [
            'file' => basename($e->getFile()),
            'line' => $e->getLine()
        ]
    ]);
    exit;
});

// Handle CORS Preflight OPTIONS requests
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-Token');
    http_response_code(200);
    exit;
}
