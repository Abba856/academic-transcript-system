<?php
/**
 * Permission Middleware
 * Ensures user has permission to access specific action or resource.
 */

namespace Middleware;

use Helpers\Response;

class PermissionMiddleware {
    public static function check(string $permissionKey): void {
        $user = AuthMiddleware::authenticate();

        // Super Admin has unrestricted access
        if ($user['role_name'] === 'Super Admin') {
            return;
        }

        $permissions = $user['permissions'] ?? [];
        if (!in_array($permissionKey, $permissions, true)) {
            Response::forbidden("Permission denied: You do not possess '{$permissionKey}' access.");
        }
    }
}
