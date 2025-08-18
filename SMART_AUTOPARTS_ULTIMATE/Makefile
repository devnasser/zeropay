# Smart AutoParts Ultimate - Makefile

.PHONY: help setup build up down test clean

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-15s\033[0m %s\n", $$1, $$2}'

setup: ## Setup development environment
	@echo "Setting up environment..."
	cp .env.example .env
	docker-compose build
	docker-compose run --rm app composer install
	docker-compose run --rm app php artisan key:generate

build: ## Build all services
	docker-compose build

up: ## Start all services
	docker-compose up -d

down: ## Stop all services
	docker-compose down

test: ## Run tests
	docker-compose run --rm app php artisan test
	docker-compose run --rm app npm test

logs: ## Show logs
	docker-compose logs -f

shell: ## Enter app shell
	docker-compose exec app bash

clean: ## Clean everything
	docker-compose down -v
	rm -rf vendor node_modules
	rm -rf storage/logs/*

deploy-dev: ## Deploy to development
	./devops/scripts/deploy.sh dev

deploy-prod: ## Deploy to production
	./devops/scripts/deploy.sh prod
