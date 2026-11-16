<?php
/**
 * API Response Helper
 * Standardized JSON envelope and HTTP status code handler.
 */

namespace Helpers;

class Response {
    public static function json(
        mixed $data = null,
        int $statusCode = 200,
        string $message = '',
        bool $success = true,
        ?array $errors = null
    ): void {
        // Clear any previous output buffering
        if (ob_get_length()) {
            ob_clean();
        }

        http_response_code($statusCode);
        header('Content-Type: application/json; charset=UTF-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

        $response = [
            'success' => $success,
            'message' => $message,
            'data'    => $data
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function success(mixed $data = null, string $message = 'Operation successful', int $statusCode = 200): void {
        self::json($data, $statusCode, $message, true);
    }

    public static function error(string $message = 'An error occurred', int $statusCode = 400, ?array $errors = null): void {
        self::json(null, $statusCode, $message, false, $errors);
    }

    public static function validationError(array $errors, string $message = 'Validation failed'): void {
        self::json(null, 422, $message, false, $errors);
    }

    public static function notFound(string $message = 'Resource not found'): void {
        self::json(null, 404, $message, false);
    }

    public static function unauthorized(string $message = 'Unauthorized access'): void {
        self::json(null, 401, $message, false);
    }

    public static function forbidden(string $message = 'Access forbidden'): void {
        self::json(null, 403, $message, false);
    }
}
