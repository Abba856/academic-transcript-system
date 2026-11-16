<?php
/**
 * Verification Controller
 * Public QR Code and Reference Verification Endpoint
 */

namespace Controllers;

use Helpers\Response;
use Models\Verification;
use Middleware\AuthMiddleware;

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
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $logs = Verification::getLogs($limit);
        Response::success($logs);
    }
}
