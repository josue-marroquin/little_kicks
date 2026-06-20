<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/JsonResponse.php';
require dirname(__DIR__) . '/app/Database.php';
require dirname(__DIR__) . '/app/SessionRepository.php';

use Kicks\App\JsonResponse;
use Kicks\App\Database;
use Kicks\App\SessionRepository;

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/api/sessions', PHP_URL_PATH);
$segments = array_values(array_filter(explode('/', $uri)));

$db = new Database();
$repo = new SessionRepository($db);

// Simple CSRF protection (double-submit cookie). All POST requests must include
// header 'X-XSRF-TOKEN' matching the cookie 'XSRF-TOKEN'.
if ($method === 'POST') {
    $header = $_SERVER['HTTP_X_XSRF_TOKEN'] ?? null;
    $cookie = $_COOKIE['XSRF-TOKEN'] ?? null;
    if (! $header || ! $cookie || !hash_equals((string) $cookie, (string) $header)) {
        JsonResponse::send(['ok' => false, 'error' => ['message' => 'csrf_failed']], 403);
    }
}

// POST /api/sessions  -> create session
if ($method === 'POST' && count($segments) === 2 && $segments[1] === 'sessions') {
    $data = json_decode(file_get_contents('php://input') ?: '{}', true);
    $id = $data['id'] ?? bin2hex(random_bytes(16));
    $startedAt = $data['startedAt'] ?? date('Y-m-d H:i:s');

    $repo->createSession($id, $startedAt);

    JsonResponse::send(['ok' => true, 'data' => ['id' => $id]] , 201);
}

// POST /api/sessions/{id}/movements -> add movement
if ($method === 'POST' && count($segments) === 4 && $segments[1] === 'sessions' && $segments[3] === 'movements') {
    $sessionId = $segments[2];
    $data = json_decode(file_get_contents('php://input') ?: '{}', true);
    $occurredAt = $data['occurredAt'] ?? date('Y-m-d H:i:s');

    $repo->addMovement($sessionId, $occurredAt);

    JsonResponse::send(['ok' => true], 201);
}

// POST /api/sessions/{id}/finish -> finish session
if ($method === 'POST' && count($segments) === 3 && $segments[1] === 'sessions' && $segments[2] !== '') {
    // path may be /api/sessions/{id} with action in body or query. Support /finish via query param.
    $sessionId = $segments[2];
    $data = json_decode(file_get_contents('php://input') ?: '{}', true);

    if (isset($data['action']) && $data['action'] === 'finish') {
        $endedAt = $data['endedAt'] ?? date('Y-m-d H:i:s');
        $notes = $data['notes'] ?? null;
        $repo->finishSession($sessionId, $endedAt, $notes);
        JsonResponse::send(['ok' => true]);
    }
}

// GET /api/sessions -> list sessions
if ($method === 'GET' && count($segments) === 2 && $segments[1] === 'sessions') {
    $list = $repo->listSessions();
    JsonResponse::send(['ok' => true, 'data' => $list]);
}

// GET /api/sessions/{id} -> get session with movements
if ($method === 'GET' && count($segments) === 3 && $segments[1] === 'sessions') {
    $sessionId = $segments[2];
    $session = $repo->getSession($sessionId);
    if (! $session) {
        JsonResponse::send(['ok' => false, 'error' => ['message' => 'not_found']], 404);
    }

    JsonResponse::send(['ok' => true, 'data' => $session]);
}

// Fallback
JsonResponse::send(['ok' => false, 'error' => ['message' => 'invalid_request']], 400);
