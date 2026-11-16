<?php
/**
 * Master REST API Router
 * Kano State Polytechnic - Academic Transcript Generating System
 */

require_once dirname(__DIR__) . '/bootstrap.php';

use Helpers\Response;

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($uri, PHP_URL_PATH);

// Strip prefix to get relative API endpoint path
$prefix = '/api';
$pos = strpos($path, $prefix);
if ($pos !== false) {
    $route = trim(substr($path, $pos + strlen($prefix)), '/');
} else {
    $route = trim($_GET['route'] ?? '', '/');
}

// Remove trailing .php if requested via router
$route = preg_replace('/\.php$/', '', $route);
$parts = array_values(array_filter(explode('/', $route)));

$resource = $parts[0] ?? 'dashboard';
$subAction = $parts[1] ?? '';
$id = $parts[2] ?? (is_numeric($subAction) ? $subAction : ($_GET['id'] ?? null));

switch ($resource) {
    case 'auth':
        $auth = new \Controllers\AuthController();
        if ($subAction === 'login' || ($method === 'POST' && empty($subAction))) $auth->login();
        elseif ($subAction === 'logout') $auth->logout();
        elseif ($subAction === 'me') $auth->me();
        elseif ($subAction === 'change-password') $auth->changePassword();
        else Response::notFound("Auth route not found");
        break;

    case 'students':
        $ctrl = new \Controllers\StudentController();
        if ($method === 'GET') {
            if ($subAction && !in_array($subAction, ['index', 'all'])) $ctrl->show($subAction);
            elseif ($id) $ctrl->show($id);
            else $ctrl->index();
        } elseif ($method === 'POST') {
            $ctrl->store();
        } elseif ($method === 'PUT' || $method === 'PATCH') {
            $ctrl->update($id ?: $subAction);
        } elseif ($method === 'DELETE') {
            $ctrl->destroy($id ?: $subAction);
        }
        break;

    case 'courses':
        $ctrl = new \Controllers\CourseController();
        if ($method === 'GET') {
            if ($subAction && !in_array($subAction, ['index', 'all'])) $ctrl->show($subAction);
            elseif ($id) $ctrl->show($id);
            else $ctrl->index();
        } elseif ($method === 'POST') {
            $ctrl->store();
        } elseif ($method === 'PUT' || $method === 'PATCH') {
            $ctrl->update($id ?: $subAction);
        } elseif ($method === 'DELETE') {
            $ctrl->destroy($id ?: $subAction);
        }
        break;

    case 'sessions':
        $ctrl = new \Controllers\SessionController();
        if ($subAction === 'set_active' || $subAction === 'active') {
            $ctrl->setActive($id ?: $_GET['id'] ?? 0);
        } elseif ($method === 'GET') {
            if ($id) $ctrl->show($id);
            else $ctrl->index();
        } elseif ($method === 'POST') {
            $ctrl->store();
        } elseif ($method === 'PUT') {
            $ctrl->update($id ?: $subAction);
        }
        break;

    case 'semesters':
        $ctrl = new \Controllers\SemesterController();
        if ($subAction === 'set_active' || $subAction === 'active') {
            $ctrl->setActive($id ?: $_GET['id'] ?? 0);
        } elseif ($method === 'GET') {
            if ($id) $ctrl->show($id);
            else $ctrl->index();
        } elseif ($method === 'POST') {
            $ctrl->store();
        } elseif ($method === 'PUT') {
            $ctrl->update($id ?: $subAction);
        }
        break;

    case 'results':
        $ctrl = new \Controllers\ResultController();
        if ($subAction === 'batch_save' || $subAction === 'batch') {
            $ctrl->batchSave();
        } elseif ($subAction === 'approve') {
            $ctrl->approve();
        } elseif ($subAction === 'save' || ($method === 'POST' && empty($subAction))) {
            $ctrl->save();
        } elseif ($method === 'GET') {
            if ($id) $ctrl->show($id);
            else $ctrl->index();
        } elseif ($method === 'DELETE') {
            $ctrl->destroy($id ?: $subAction);
        }
        break;

    case 'transcripts':
        $ctrl = new \Controllers\TranscriptController();
        if ($subAction === 'generate') {
            $ctrl->generate();
        } elseif ($subAction === 'revoke') {
            $ctrl->revoke($id ?: $_GET['id'] ?? 0);
        } elseif ($method === 'GET') {
            if ($subAction && !in_array($subAction, ['index', 'all'])) $ctrl->show($subAction);
            elseif ($id) $ctrl->show($id);
            else $ctrl->index();
        }
        break;

    case 'verification':
        $ctrl = new \Controllers\VerificationController();
        if ($subAction === 'logs') {
            $ctrl->logs();
        } else {
            $ctrl->verify();
        }
        break;

    case 'settings':
        $ctrl = new \Controllers\SettingController();
        if ($method === 'POST' || $method === 'PUT') {
            $ctrl->update();
        } else {
            $ctrl->index();
        }
        break;

    case 'dashboard':
        $ctrl = new \Controllers\DashboardController();
        $ctrl->stats();
        break;

    default:
        Response::notFound("Endpoint '/api/{$route}' not found");
        break;
}
