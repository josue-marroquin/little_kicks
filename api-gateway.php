<?php

declare(strict_types=1);

$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/api-gateway.php');
$basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/.');
$basePath = $basePath === '/' ? '' : $basePath;
$requestUri = $_SERVER['REQUEST_URI'] ?? (($basePath === '' ? '' : $basePath) . '/api');
$parts = parse_url($requestUri);
$path = (string) ($parts['path'] ?? '/api');

if ($basePath !== '' && str_starts_with($path, $basePath)) {
    $path = substr($path, strlen($basePath)) ?: '/';
}

$query = isset($parts['query']) ? '?' . $parts['query'] : '';
$_SERVER['REQUEST_URI'] = $path . $query;

require __DIR__ . '/api/index.php';
