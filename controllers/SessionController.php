<?php
/**
 * Academic Session Controller
 */

namespace Controllers;

use Helpers\Response;
use Helpers\Validator;
use Models\AcademicSession;
use Models\AuditLog;
use Middleware\AuthMiddleware;
use Middleware\PermissionMiddleware;

class SessionController {
    public function index(): void {
        AuthMiddleware::authenticate();

        $sessions = AcademicSession::all();
        Response::success($sessions);
    }

    public function show(int $id): void {
        AuthMiddleware::authenticate();

        $sess = AcademicSession::findById($id);
        if (!$sess) {
            Response::notFound("Session not found");
        }
        Response::success($sess);
    }

    public function store(): void {
        $user = AuthMiddleware::authenticate();
        PermissionMiddleware::check('sessions.manage');

        $body = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $body = Validator::sanitize($body);

        $validator = Validator::make($body, [
            'session_name' => 'required|unique:academic_sessions,session_name'
        ]);

        if ($validator->fails()) {
            Response::validationError($validator->getErrors());
        }

        $id = AcademicSession::create($body);
        $newSess = AcademicSession::findById($id);

        AuditLog::log($user['id'], 'SESSION_CREATED', "Created academic session {$newSess['session_name']}", "SESSION:{$id}");
        Response::success($newSess, 'Academic session created successfully', 201);
    }

    public function update(int $id): void {
        $user = AuthMiddleware::authenticate();
        PermissionMiddleware::check('sessions.manage');

        $sess = AcademicSession::findById($id);
        if (!$sess) {
            Response::notFound("Academic session not found");
        }

        $body = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $body = Validator::sanitize($body);

        AcademicSession::update($id, $body);
        $updated = AcademicSession::findById($id);

        AuditLog::log($user['id'], 'SESSION_UPDATED', "Updated academic session {$updated['session_name']}", "SESSION:{$id}");
        Response::success($updated, 'Academic session updated successfully');
    }

    public function setActive(int $id): void {
        $user = AuthMiddleware::authenticate();
        PermissionMiddleware::check('sessions.manage');

        $sess = AcademicSession::findById($id);
        if (!$sess) {
            Response::notFound("Academic session not found");
        }

        AcademicSession::setActive($id);
        AuditLog::log($user['id'], 'SESSION_ACTIVATED', "Activated academic session {$sess['session_name']}", "SESSION:{$id}");
        Response::success(['active_session_id' => $id], "Academic session '{$sess['session_name']}' is now active");
    }
}
