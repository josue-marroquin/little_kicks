<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/JsonResponse.php';

use Kicks\App\JsonResponse;

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/api', PHP_URL_PATH);

// Health check
if ($method === 'GET' && in_array($path, ['/api', '/api/', '/api/health'], true)) {
    JsonResponse::send([
        'ok' => true,
        'data' => [
            'service' => 'kicks-api',
            'status' => 'available',
        ],
    ]);
}

// Route sessions API
if (str_starts_with((string) $path, '/api/sessions')) {
    require __DIR__ . '/sessions.php';
}

if (str_starts_with((string) $path, '/api/csrf')) {
    require __DIR__ . '/csrf.php';
}

// Fallback
JsonResponse::send([
    'ok' => false,
    'error' => [
        'code' => 'not_found',
        'message' => 'El recurso solicitado no existe.',
    ],
], 404);
