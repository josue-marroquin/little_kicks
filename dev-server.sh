#!/bin/bash
# Development start script for Kicks
# This starts a local PHP development server for testing

set -e

cd "$(dirname "$0")"

echo "🔨 Building assets..."
php scripts/build.php

echo ""
echo "🚀 Starting PHP development server..."
echo "📍 URL: http://127.0.0.1:8086"
echo "Press Ctrl+C to stop"
echo ""

php -S 127.0.0.1:8086 -t public public/router.php
