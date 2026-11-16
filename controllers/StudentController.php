<?php
/**
 * Student Controller
 */

namespace Controllers;

use Helpers\Response;
use Helpers\Validator;
use Models\Student;
use Models\Result;
use Models\Transcript;
use Services\CGPAService;
use Models\AuditLog;
use Middleware\AuthMiddleware;
use Middleware\PermissionMiddleware;

class StudentController {
    public function index(): void {
        $filters = [
            'search'    => $_GET['search'] ?? null,
            'level'     => $_GET['level'] ?? null,
            'session'   => $_GET['session'] ?? null,
            'programme' => $_GET['programme'] ?? null,
            'status'    => $_GET['status'] ?? null,
        ];

        $students = Student::all($filters);
        Response::success($students);
    }

    public function show(int|string $id): void {
        $student = Student::findById($id);
        if (!$student) {
            Response::notFound("Student record not found with ID/Matric '{$id}'");
        }

        // Attach academic history
        $student['results'] = Result::getByStudent($student['id']);
        $student['transcripts'] = Transcript::all(['student_id' => $student['id']]);
        $student['performance'] = CGPAService::calculateForStudent($student['id']);

        Response::success($student);
    }

    public function store(): void {
        $user = AuthMiddleware::authenticate();
        PermissionMiddleware::check('students.create');

        $body = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $body = Validator::sanitize($body);

        $validator = Validator::make($body, [
            'matric_number' => 'required|unique:students,matric_number',
            'first_name'    => 'required',
            'last_name'     => 'required',
            'email'         => 'required|email|unique:students,email',
            'programme'     => 'required',
            'current_level' => 'required'
        ]);

        if ($validator->fails()) {
            Response::validationError($validator->getErrors());
        }

        $studentId = Student::create($body);
        $newStudent = Student::findById($studentId);

        AuditLog::log(
            $user['id'],
            'STUDENT_REGISTERED',
            "Registered student {$newStudent['matric_number']} ({$newStudent['full_name']})",
            "STUDENT:{$studentId}"
        );

        Response::success($newStudent, 'Student registered successfully', 201);
    }

    public function update(int|string $id): void {
        $user = AuthMiddleware::authenticate();
        PermissionMiddleware::check('students.edit');

        $student = Student::findById($id);
        if (!$student) {
            Response::notFound("Student not found with ID {$id}");
        }

        $body = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $body = Validator::sanitize($body);

        $validator = Validator::make($body, [
            'first_name'    => 'required',
            'last_name'     => 'required',
            'email'         => "required|email|unique:students,email,id,{$student['id']}",
            'matric_number' => "required|unique:students,matric_number,id,{$student['id']}"
        ]);

        if ($validator->fails()) {
            Response::validationError($validator->getErrors());
        }

        Student::update($student['id'], $body);
        $updatedStudent = Student::findById($student['id']);

        AuditLog::log(
            $user['id'],
            'STUDENT_UPDATED',
            "Updated student profile for {$updatedStudent['matric_number']}",
            "STUDENT:{$student['id']}"
        );

        Response::success($updatedStudent, 'Student updated successfully');
    }

    public function destroy(int|string $id): void {
        $user = AuthMiddleware::authenticate();
        PermissionMiddleware::check('students.delete');

        $student = Student::findById($id);
        if (!$student) {
            Response::notFound("Student not found");
        }

        Student::delete($student['id']);

        AuditLog::log(
            $user['id'],
            'STUDENT_DELETED',
            "Deleted student {$student['matric_number']} ({$student['full_name']})",
            "STUDENT:{$student['id']}"
        );

        Response::success(['deleted' => true], 'Student deleted successfully');
    }
}
