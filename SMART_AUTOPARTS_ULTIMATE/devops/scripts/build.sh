#!/bin/bash
# Build script for Smart AutoParts Ultimate

set -e

echo "🏗️ Building Smart AutoParts Ultimate..."

# Build monolith
echo "Building monolith..."
cd core/monolith
composer install --no-dev --optimize-autoloader
php artisan optimize
cd ../..

# Build microservices
echo "Building microservices..."
for service in core/microservices/*; do
    if [ -d "$service" ]; then
        echo "Building $(basename $service)..."
        cd "$service"
        if [ -f "package.json" ]; then
            npm ci --production
        elif [ -f "requirements.txt" ]; then
            pip install -r requirements.txt
        fi
        cd - > /dev/null
    fi
done

# Build frontend apps
echo "Building frontend apps..."
cd applications/web
npm ci
npm run build
cd ../..

echo "✅ Build complete!"
