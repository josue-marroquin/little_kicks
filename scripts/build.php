<?php

declare(strict_types=1);

/**
 * Build script for Kicks — copies source assets to public/assets/
 * 
 * Usage: php scripts/build.php
 * 
 * This script replaces the Node.js build process, making the application
 * compatible with PHP/Apache environments without requiring Node.js.
 */

$root = dirname(__DIR__);
$src = $root . '/src';
$output = $root . '/public/assets';

// List of files to copy from src to public/assets
$files = [
    'app.js',
    'session-store.js',
    'styles.css',
];

// Ensure output directory exists
if (!is_dir($output)) {
    if (!mkdir($output, 0755, true)) {
        fwrite(STDERR, "Error: Could not create directory: $output\n");
        exit(1);
    }
}

// Copy each file
foreach ($files as $file) {
    $srcFile = $src . '/' . $file;
    $dstFile = $output . '/' . $file;

    if (!is_file($srcFile)) {
        fwrite(STDERR, "Warning: Source file not found: $srcFile\n");
        continue;
    }

    if (!copy($srcFile, $dstFile)) {
        fwrite(STDERR, "Error: Could not copy $srcFile to $dstFile\n");
        exit(1);
    }

    echo "Copied: src/$file → public/assets/$file\n";
}

echo "\n✓ Build complete. Assets ready in public/assets/\n";
exit(0);
