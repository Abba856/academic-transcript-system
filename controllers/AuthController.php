<?php
/**
 * Authentication Controller
 */

namespace Controllers;

use Helpers\Response;
use Helpers\Validator;
use Helpers\Security;
use Models\User;
use Middleware\AuthMiddleware;

class AuthController {
    public function login(): void {
        $body = Validator::getRequestBody();
        $body = Validator::sanitize($body);

        $validator = Validator::make($body, [
            'emailOrUsername' => 'required',
            'password'        => 'required'
        ]);

        if ($validator->fails()) {
            Response::validationError($validator->getErrors(), 'Username/email and password are required.');
        }

        $identifier = $body['emailOrUsername'];
        $password = $body['password'];
        $remember = !empty($body['remember']);

        // Brute-force rate limiting check
        if (Security::isRateLimited($identifier)) {
            Response::error('Too many failed login attempts. Please try again in 15 minutes.', 429);
        }

        $user = User::findByEmailOrUsername($identifier);

        if (!$user || !Security::verifyPassword($password, $user['password'])) {
            Security::recordLoginAttempt($identifier, false);
            Response::error('Invalid username/email or password.', 401);
        }

        if ($user['status'] !== 'active') {
            Response::forbidden('Your account is currently inactive or suspended. Please contact the administrator.');
        }

        // Record successful login
        Security::recordLoginAttempt($identifier, true);
        User::updateLastLogin($user['id']);

        $expiry = $remember ? (86400 * 30) : 86400;
        $token = Security::generateToken([
            'user_id'   => $user['id'],
            'username'  => $user['username'],
            'role'      => $user['role_name'],
            'role_id'   => $user['role_id']
        ], $expiry);

        $userData = [
            'id'         => (int)$user['id'],
            'name'       => $user['full_name'],
            'username'   => $user['username'],
            'email'      => $user['email'],
            'role'       => $user['role_name'],
            'title'      => $user['role_name'] === 'Super Admin' ? 'System Administrator' : ($user['role_name'] === 'Department Admin' ? 'Head of Department' : 'Academic Officer'),
            'department' => 'Computer Science',
            'status'     => $user['status'],
        ];

        Response::success([
            'token' => $token,
            'user'  => $userData
        ], 'Login successful');
    }

    public function me(): void {
        $user = AuthMiddleware::authenticate();
        $userData = [
            'id'         => (int)$user['id'],
            'name'       => $user['full_name'],
            'username'   => $user['username'],
            'email'      => $user['email'],
            'role'       => $user['role_name'],
            'title'      => $user['role_name'],
            'department' => 'Computer Science',
            'status'     => $user['status'],
            'permissions'=> $user['permissions'] ?? []
        ];
        Response::success($userData);
    }

    public function logout(): void {
        Response::success(['logged_out' => true], 'Successfully logged out');
    }

    public function changePassword(): void {
        $user = AuthMiddleware::authenticate();
        $body = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $body = Validator::sanitize($body);

        $validator = Validator::make($body, [
            'current_password' => 'required',
            'new_password'     => 'required|min:6'
        ]);

        if ($validator->fails()) {
            Response::validationError($validator->getErrors());
        }

        $dbUser = User::findById($user['id']);
        if (!Security::verifyPassword($body['current_password'], $dbUser['password'])) {
            Response::error('Current password does not match.', 400);
        }

        $newHash = Security::hashPassword($body['new_password']);
        User::updatePassword($user['id'], $newHash);

        Response::success(null, 'Password updated successfully');
    }
}
