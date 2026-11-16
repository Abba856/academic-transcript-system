<?php
/**
 * Transcript Controller
 */

namespace Controllers;

use Helpers\Response;
use Helpers\Validator;
use Models\Transcript;
use Services\TranscriptService;
use Models\AuditLog;
use Middleware\AuthMiddleware;
use Middleware\PermissionMiddleware;
use Exception;

class TranscriptController {
    public function index(): void {
        AuthMiddleware::authenticate();

        $filters = [
            'search'     => $_GET['search'] ?? null,
            'status'     => $_GET['status'] ?? null,
            'student_id' => $_GET['student_id'] ?? $_GET['studentId'] ?? null,
        ];

        $transcripts = Transcript::all($filters);
        Response::success($transcripts);
    }

    public function show(int|string $id): void {
        AuthMiddleware::authenticate();

        $transcript = Transcript::findById($id);
        if (!$transcript) {
            Response::notFound("Transcript record not found with ID/Reference '{$id}'");
        }
        Response::success($transcript);
    }

    public function generate(): void {
        $user = AuthMiddleware::authenticate();
        PermissionMiddleware::check('transcripts.generate');

        $body = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $body = Validator::sanitize($body);

        $studentId = (int)($body['studentId'] ?? $body['student_id'] ?? 0);
        if ($studentId === 0) {
            Response::validationError(['studentId' => 'Student ID is required']);
        }

        $options = $body['options'] ?? [];
        if (!is_array($options)) $options = [];

        try {
            $transcript = TranscriptService::generate($studentId, $options, $user['id']);
            Response::success($transcript, "Transcript {$transcript['referenceNumber']} generated successfully", 201);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function revoke(int|string $id): void {
        $user = AuthMiddleware::authenticate();
        PermissionMiddleware::check('transcripts.revoke');

        $body = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $reason = trim($body['reason'] ?? 'Revoked by departmental administration');

        try {
            $updated = TranscriptService::revoke($id, $reason, $user['id']);
            Response::success($updated, "Transcript {$updated['referenceNumber']} was successfully revoked");
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }
}
