<?php
/**
 * Verification Controller
 * Public QR Code and Reference Verification Endpoint
 */

namespace Controllers;

use Helpers\Response;
use Models\Verification;
use Middleware\AuthMiddleware;
use Middleware\PermissionMiddleware;

class VerificationController {
    public function verify(): void {
        $refOrToken = $_GET['ref'] ?? $_GET['token'] ?? null;

        if (!$refOrToken) {
            $body = json_decode(file_get_contents('php://input'), true);
            $refOrToken = $body['ref'] ?? $body['token'] ?? $body['referenceNumber'] ?? null;
        }

        if (!$refOrToken) {
            Response::error('Verification reference number or security token is required.', 400);
        }

        $result = Verification::verify(trim($refOrToken));

        // Returns HTTP 200 with result payload matching frontend structure
        Response::success($result);
    }

    public function logs(): void {
        AuthMiddleware::authenticate();
        PermissionMiddleware::check('verification.view');

        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $limit = max(1, min($limit, 200));

        $logs = Verification::getLogs($limit);
        Response::success($logs);
    }
}
