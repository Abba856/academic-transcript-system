<?php
/**
 * CSRF Protection Middleware
 */

namespace Middleware;

use Helpers\Response;

class CSRFMiddleware {
    public static function check(): void {
        // Safe read methods do not require CSRF token
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'])) {
            return;
        }

        // For API token based authentication, Bearer token inherently prevents standard browser CSRF
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!empty($authHeader) && stripos($authHeader, 'Bearer') !== false) {
            return;
        }

        // Session-based fallback check
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? null;
        if (!$token || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            Response::forbidden('Invalid or missing CSRF token.');
        }
    }
}
