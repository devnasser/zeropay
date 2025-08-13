#!/bin/bash

# Smart AutoParts Deployment Script
# Usage: ./deploy.sh [environment]

set -e

ENVIRONMENT=${1:-production}
echo "🚀 Deploying Smart AutoParts to $ENVIRONMENT..."

# Pull latest changes
echo "📥 Pulling latest changes..."
git pull origin main

# Install dependencies
echo "📦 Installing dependencies..."
composer install --optimize-autoloader --no-dev
npm install

# Build assets
echo "🎨 Building assets..."
npm run build

# Run migrations
echo "🗄️ Running migrations..."
php artisan migrate --force

# Clear and cache
echo "🧹 Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Clear old cache
php artisan cache:clear
php artisan queue:restart

# Set permissions
echo "🔐 Setting permissions..."
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Health check
echo "🏥 Running health check..."
php artisan health:check

echo "✅ Deployment complete!"

# Optional: Send notification
# curl -X POST https://api.slack.com/... -d "Deployment complete"