<?php

declare(strict_types=1);

namespace Kicks\Api;

use Kicks\App\JsonResponse;

// Double-submit cookie CSRF token
$token = bin2hex(random_bytes(16));

// Set cookie accessible to JavaScript for double-submit pattern
setcookie('XSRF-TOKEN', $token, [
    'expires' => time() + 3600,
    'path' => '/',
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Lax',
]);

JsonResponse::send(['ok' => true, 'data' => ['token' => $token]]);
