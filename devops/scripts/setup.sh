#!/bin/bash
# Setup development environment

echo "🚀 Setting up Smart AutoParts Ultimate..."

# Check requirements
command -v docker >/dev/null 2>&1 || { echo "Docker is required but not installed. Aborting." >&2; exit 1; }
command -v docker-compose >/dev/null 2>&1 || { echo "Docker Compose is required but not installed. Aborting." >&2; exit 1; }

# Copy environment file
if [ ! -f .env ]; then
    cp .env.example .env
    echo "✓ Created .env file"
fi

# Build containers
docker-compose build

# Install dependencies
docker-compose run --rm app composer install
docker-compose run --rm web npm install

# Generate key
docker-compose run --rm app php artisan key:generate

# Run migrations
docker-compose run --rm app php artisan migrate

# Seed database
docker-compose run --rm app php artisan db:seed

echo "✅ Setup complete! Run 'make up' to start the application."
