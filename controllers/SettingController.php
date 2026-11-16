<?php
/**
 * Settings Controller
 */

namespace Controllers;

use Helpers\Response;
use Helpers\Validator;
use Models\Setting;
use Models\GradingScale;
use Models\AuditLog;
use Middleware\AuthMiddleware;
use Middleware\PermissionMiddleware;

class SettingController {
    public function index(): void {
        $settings = Setting::getStructuredSettings();
        Response::success($settings);
    }

    public function update(): void {
        $user = AuthMiddleware::authenticate();
        PermissionMiddleware::check('settings.manage');

        $body = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $body = Validator::sanitize($body);

        $section = $_GET['section'] ?? $body['section'] ?? 'institution';

        if ($section === 'institution') {
            $data = $body['data'] ?? $body;
            foreach ($data as $key => $val) {
                if (is_scalar($val)) {
                    Setting::set("institution_{$key}", (string)$val, 'institution');
                }
            }
            AuditLog::log($user['id'], 'SETTINGS_UPDATED', 'Updated institution settings', 'SETTINGS');
        } elseif ($section === 'gradingScale') {
            $scaleItems = $body['data'] ?? $body;
            if (is_array($scaleItems)) {
                GradingScale::updateScale($scaleItems);
                AuditLog::log($user['id'], 'GRADING_SCALE_UPDATED', 'Updated academic grading scale rules', 'GRADING_SYSTEM');
            }
        }

        Response::success(Setting::getStructuredSettings(), 'Settings updated successfully');
    }
}
