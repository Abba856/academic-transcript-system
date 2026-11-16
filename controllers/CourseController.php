<?php
/**
 * Course Controller
 */

namespace Controllers;

use Helpers\Response;
use Helpers\Validator;
use Models\Course;
use Models\AuditLog;
use Middleware\AuthMiddleware;
use Middleware\PermissionMiddleware;

class CourseController {
    public function index(): void {
        $filters = [
            'search'   => $_GET['search'] ?? null,
            'level'    => $_GET['level'] ?? null,
            'semester' => $_GET['semester'] ?? null,
            'status'   => $_GET['status'] ?? null,
        ];

        $courses = Course::all($filters);
        Response::success($courses);
    }

    public function show(int|string $id): void {
        $course = Course::findById($id);
        if (!$course) {
            Response::notFound("Course not found with ID/Code '{$id}'");
        }
        Response::success($course);
    }

    public function store(): void {
        $user = AuthMiddleware::authenticate();
        PermissionMiddleware::check('courses.create');

        $body = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $body = Validator::sanitize($body);

        $validator = Validator::make($body, [
            'course_code'  => 'required|unique:courses,course_code',
            'course_title' => 'required',
            'credit_unit'  => 'required|numeric|min:1|max:6',
            'academic_level' => 'required',
            'semester_name'  => 'required'
        ]);

        if ($validator->fails()) {
            Response::validationError($validator->getErrors());
        }

        $courseId = Course::create($body);
        $newCourse = Course::findById($courseId);

        AuditLog::log(
            $user['id'],
            'COURSE_CREATED',
            "Created course {$newCourse['course_code']} - {$newCourse['course_title']}",
            "COURSE:{$courseId}"
        );

        Response::success($newCourse, 'Course created successfully', 201);
    }

    public function update(int|string $id): void {
        $user = AuthMiddleware::authenticate();
        PermissionMiddleware::check('courses.edit');

        $course = Course::findById($id);
        if (!$course) {
            Response::notFound("Course not found with ID '{$id}'");
        }

        $body = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $body = Validator::sanitize($body);

        $validator = Validator::make($body, [
            'course_code'  => "required|unique:courses,course_code,id,{$course['id']}",
            'course_title' => 'required',
            'credit_unit'  => 'required|numeric'
        ]);

        if ($validator->fails()) {
            Response::validationError($validator->getErrors());
        }

        Course::update($course['id'], $body);
        $updatedCourse = Course::findById($course['id']);

        AuditLog::log(
            $user['id'],
            'COURSE_UPDATED',
            "Updated course {$updatedCourse['course_code']}",
            "COURSE:{$course['id']}"
        );

        Response::success($updatedCourse, 'Course updated successfully');
    }

    public function destroy(int|string $id): void {
        $user = AuthMiddleware::authenticate();
        PermissionMiddleware::check('courses.delete');

        $course = Course::findById($id);
        if (!$course) {
            Response::notFound("Course not found");
        }

        Course::delete($course['id']);

        AuditLog::log(
            $user['id'],
            'COURSE_DELETED',
            "Deleted course {$course['course_code']} ({$course['course_title']})",
            "COURSE:{$course['id']}"
        );

        Response::success(['deleted' => true], 'Course deleted successfully');
    }
}
