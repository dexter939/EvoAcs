#!/bin/bash
set -e

echo "🚀 ACS Deployment Build Phase Starting..."
echo "=========================================="

echo "📦 Running migrations..."
php artisan migrate --force --no-interaction

echo "🔄 Running system auto-update..."
php artisan system:update --force --env=production

echo "💾 Caching configuration..."
php artisan config:cache

echo "🛣️  Caching routes..."
php artisan route:cache

echo "🎨 Caching views..."
php artisan view:cache

echo "✅ Build phase completed successfully!"
