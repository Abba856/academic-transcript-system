<?php
/**
 * Semester Controller
 */

namespace Controllers;

use Helpers\Response;
use Helpers\Validator;
use Models\Semester;
use Models\AuditLog;
use Middleware\AuthMiddleware;
use Middleware\PermissionMiddleware;

class SemesterController {
    public function index(): void {
        AuthMiddleware::authenticate();

        $semesters = Semester::all();
        Response::success($semesters);
    }

    public function show(int $id): void {
        AuthMiddleware::authenticate();

        $sem = Semester::findById($id);
        if (!$sem) {
            Response::notFound("Semester not found");
        }
        Response::success($sem);
    }

    public function store(): void {
        $user = AuthMiddleware::authenticate();
        PermissionMiddleware::check('semesters.manage');

        $body = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $body = Validator::sanitize($body);

        $validator = Validator::make($body, [
            'semester_name' => 'required',
            'semester_code' => 'required'
        ]);

        if ($validator->fails()) {
            Response::validationError($validator->getErrors());
        }

        $id = Semester::create($body);
        $newSem = Semester::findById($id);

        AuditLog::log($user['id'], 'SEMESTER_CREATED', "Created semester {$newSem['semester_name']}", "SEMESTER:{$id}");
        Response::success($newSem, 'Semester created successfully', 201);
    }

    public function update(int $id): void {
        $user = AuthMiddleware::authenticate();
        PermissionMiddleware::check('semesters.manage');

        $sem = Semester::findById($id);
        if (!$sem) {
            Response::notFound("Semester not found");
        }

        $body = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $body = Validator::sanitize($body);

        Semester::update($id, $body);
        $updated = Semester::findById($id);

        AuditLog::log($user['id'], 'SEMESTER_UPDATED', "Updated semester {$updated['semester_name']}", "SEMESTER:{$id}");
        Response::success($updated, 'Semester updated successfully');
    }

    public function setActive(int $id): void {
        $user = AuthMiddleware::authenticate();
        PermissionMiddleware::check('semesters.manage');

        $sem = Semester::findById($id);
        if (!$sem) {
            Response::notFound("Semester not found");
        }

        Semester::setActive($id);
        AuditLog::log($user['id'], 'SEMESTER_ACTIVATED', "Activated semester {$sem['semester_name']}", "SEMESTER:{$id}");
        Response::success(['active_semester_id' => $id], "Semester '{$sem['semester_name']}' is now active");
    }
}
