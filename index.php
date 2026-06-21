<?php

declare(strict_types=1);

$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
$basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/.');
$basePath = $basePath === '/' ? '' : $basePath;
$assetBase = ($basePath === '' ? '' : $basePath) . '/public/assets';
$apiUrl = ($basePath === '' ? '' : $basePath) . '/sessions.php';

ob_start();
require __DIR__ . '/public/index.php';
$html = (string) ob_get_clean();

$cssTag = 'href="/assets/styles.css"';
$jsTag = 'src="/assets/app.js"';
$cssVersion = (string) (filemtime(__DIR__ . '/public/assets/styles.css') ?: time());
$jsVersion = (string) (filemtime(__DIR__ . '/public/assets/app.js') ?: time());
$resolvedCssTag = sprintf(
    'href="%s/styles.css?v=%s"',
    htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'),
    rawurlencode($cssVersion)
);
$resolvedJsTag = sprintf(
    'src="%s/app.js?v=%s"',
    htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'),
    rawurlencode($jsVersion)
);
$apiBootstrap = sprintf(
    '    <script>window.KICKS_API_URL = %s;</script>',
    json_encode($apiUrl, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES)
);

$html = str_replace($cssTag, $resolvedCssTag, $html);
$html = str_replace($jsTag, $resolvedJsTag, $html);
$html = str_replace(
    sprintf('<script type="module" %s></script>', $resolvedJsTag),
    $apiBootstrap . PHP_EOL . '    ' . sprintf('<script type="module" %s></script>', $resolvedJsTag),
    $html
);

echo $html;
