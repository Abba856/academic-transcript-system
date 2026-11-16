<?php
/**
 * Result Management Controller
 */

namespace Controllers;

use Helpers\Response;
use Helpers\Validator;
use Models\Result;
use Models\AuditLog;
use Middleware\AuthMiddleware;
use Middleware\PermissionMiddleware;

class ResultController {
    public function index(): void {
        AuthMiddleware::authenticate();
        PermissionMiddleware::check('results.view');

        $filters = [
            'studentId'   => $_GET['studentId'] ?? $_GET['student_id'] ?? null,
            'courseCode'  => $_GET['courseCode'] ?? $_GET['course_code'] ?? null,
            'level'       => $_GET['level'] ?? null,
            'semester'    => $_GET['semester'] ?? null,
            'sessionName' => $_GET['sessionName'] ?? $_GET['session_name'] ?? null,
            'status'      => $_GET['status'] ?? null,
        ];

        $results = Result::all($filters);
        Response::success($results);
    }

    public function show(int $id): void {
        AuthMiddleware::authenticate();
        PermissionMiddleware::check('results.view');

        $res = Result::findById($id);
        if (!$res) {
            Response::notFound("Result record not found");
        }
        Response::success($res);
    }

    public function save(): void {
        $user = AuthMiddleware::authenticate();
        PermissionMiddleware::check('results.enter');

        $body = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $body = Validator::sanitize($body);

        $validator = Validator::make($body, [
            'studentId'  => 'required',
            'courseCode' => 'required'
        ]);

        if ($validator->fails()) {
            Response::validationError($validator->getErrors());
        }

        $result = Result::save($body, $user['id']);

        AuditLog::log(
            $user['id'],
            'RESULT_SAVED',
            "Recorded result for Student ID {$result['student_id']}, Course {$result['course_code']} (Grade: {$result['letter_grade']})",
            "RESULT:{$result['id']}"
        );

        Response::success($result, 'Result saved successfully');
    }

    public function batchSave(): void {
        $user = AuthMiddleware::authenticate();
        PermissionMiddleware::check('results.enter');

        $body = json_decode(file_get_contents('php://input'), true);
        if (!is_array($body)) {
            Response::error('Invalid request body. Expected an array of result objects.', 400);
        }

        $savedResults = [];
        foreach ($body as $item) {
            if (is_array($item)) {
                $item = Validator::sanitize($item);
                $savedResults[] = Result::save($item, $user['id']);
            }
        }

        AuditLog::log(
            $user['id'],
            'RESULT_BATCH_SAVED',
            "Batch saved " . count($savedResults) . " student examination results",
            "RESULTS_BATCH"
        );

        Response::success([
            'count'   => count($savedResults),
            'results' => $savedResults
        ], count($savedResults) . ' results successfully saved');
    }

    public function approve(): void {
        $user = AuthMiddleware::authenticate();
        PermissionMiddleware::check('results.approve');

        $body = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $ids = $body['ids'] ?? [];

        if (!is_array($ids) || empty($ids)) {
            Response::error('Array of result IDs is required for approval.', 400);
        }

        Result::approve($ids, $user['id']);

        AuditLog::log(
            $user['id'],
            'RESULTS_APPROVED',
            "Approved " . count($ids) . " examination results",
            "RESULTS_APPROVAL"
        );

        Response::success(['approved_count' => count($ids)], count($ids) . ' results approved successfully');
    }

    public function destroy(int $id): void {
        $user = AuthMiddleware::authenticate();
        PermissionMiddleware::check('results.enter');

        $result = Result::findById($id);
        if (!$result) {
            Response::notFound("Result not found");
        }

        Result::delete($id);

        AuditLog::log(
            $user['id'],
            'RESULT_DELETED',
            "Deleted result ID {$id} for Student {$result['student_id']}",
            "RESULT:{$id}"
        );

        Response::success(['deleted' => true], 'Result deleted successfully');
    }
}
