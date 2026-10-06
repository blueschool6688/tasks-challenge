# ==============================================================================
# Team Task Manager - Cross-Platform Makefile
# Full-Stack: Laravel 11 REST API + Vue 3 SPA (Vuetify 3 & TypeScript)
# Compatible with Git Bash, WSL, Linux, macOS, and Windows CMD/PowerShell
# ==============================================================================

.DEFAULT_GOAL := help

# Executables
PHP      ?= php
COMPOSER ?= composer
NPM      ?= npm
NODE     ?= node

.PHONY: help
help: ## Show this help message with available commands
	@echo "====================================================================="
	@echo "              Team Task Manager - Management Makefile"
	@echo "====================================================================="
	@echo "Usage: make [target]"
	@echo ""
	@echo "Available targets:"
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  %-18s %s\n", $$1, $$2}' $(MAKEFILE_LIST)
	@echo ""

# ------------------------------------------------------------------------------
# 1. Development Servers
# ------------------------------------------------------------------------------

.PHONY: dev
dev: ## Run both Backend API and Frontend SPA concurrently in one terminal
	@echo "Starting Backend API: http://localhost:8000 | Frontend SPA: http://localhost:5173"
	@npx --yes concurrently --kill-others --prefix "[{name}]" --names "BACKEND,FRONTEND" --prefix-colors "blue,green" \
		"cd backend && $(PHP) artisan serve --host=127.0.0.1 --port=8000" \
		"cd frontend && $(NPM) run dev"

.PHONY: dev-backend
dev-backend: ## Start only Laravel API server on http://localhost:8000
	@echo "Starting Laravel API on http://localhost:8000..."
	cd backend && $(PHP) artisan serve --host=127.0.0.1 --port=8000

.PHONY: dev-frontend
dev-frontend: ## Start only Vue 3 Vite dev server on http://localhost:5173
	@echo "Starting Vue 3 SPA on http://localhost:5173..."
	cd frontend && $(NPM) run dev

# ------------------------------------------------------------------------------
# 2. Project Initialization & Setup
# ------------------------------------------------------------------------------

.PHONY: install
install: install-backend install-frontend ## Install dependencies for both Backend & Frontend

.PHONY: install-backend
install-backend: ## Install backend Composer dependencies
	@echo "Installing Backend Composer dependencies..."
	cd backend && $(COMPOSER) install

.PHONY: install-frontend
install-frontend: ## Install frontend NPM dependencies
	@echo "Installing Frontend NPM dependencies..."
	cd frontend && $(NPM) install

.PHONY: setup
setup: install setup-env migrate-fresh ## One-step complete project initial setup
	@echo "====================================================================="
	@echo " Setup completed successfully! Run 'make dev' to start development."
	@echo "====================================================================="

.PHONY: setup-env
setup-env: ## Copy .env.example to .env and generate application key if needed
	@$(NODE) -e "const fs = require('fs'); if (!fs.existsSync('backend/.env')) { fs.copyFileSync('backend/.env.example', 'backend/.env'); console.log('Created backend/.env from .env.example'); } else { console.log('backend/.env already exists.'); }"
	cd backend && $(PHP) artisan key:generate

# ------------------------------------------------------------------------------
# 3. Database Operations
# ------------------------------------------------------------------------------

.PHONY: migrate
migrate: ## Run pending database migrations
	@echo "Running database migrations..."
	cd backend && $(PHP) artisan migrate --force

.PHONY: migrate-fresh
migrate-fresh: ## Drop all tables, re-run all migrations and seed test data
	@echo "Recreating database tables and seeding test data..."
	cd backend && $(PHP) artisan migrate:fresh --seed --force

.PHONY: seed
seed: ## Run database seeder (idempotent)
	@echo "Seeding database..."
	cd backend && $(PHP) artisan db:seed --force

.PHONY: seed-million
seed-million: ## Seed 1,000,000 tasks for performance benchmarking
	@echo "Seeding 1,000,000 tasks for performance benchmarking..."
	cd backend && $(PHP) artisan tasks:seed-million

# ------------------------------------------------------------------------------
# 4. Testing & Quality Assurance
# ------------------------------------------------------------------------------

.PHONY: test
test: test-backend test-frontend ## Run both Backend tests and Frontend type-check

.PHONY: test-backend
test-backend: ## Run PHPUnit / Pest automated test suite
	@echo "Running Backend PHPUnit test suite..."
	cd backend && $(PHP) artisan test

.PHONY: test-frontend
test-frontend: ## Run TypeScript strict type-checking on frontend
	@echo "Running Frontend TypeScript check..."
	cd frontend && $(NPM) run type-check

# ------------------------------------------------------------------------------
# 5. Code Formatting & Linting
# ------------------------------------------------------------------------------

.PHONY: format
format: format-backend ## Format code across the repository (Laravel Pint)

.PHONY: format-backend
format-backend: ## Fix code style issues in backend using Laravel Pint
	@echo "Formatting Backend code with Laravel Pint..."
	cd backend && $(PHP) vendor/bin/pint

.PHONY: lint
lint: ## Inspect code style and types without modifying files
	@echo "Checking Backend code style with Laravel Pint..."
	cd backend && $(PHP) vendor/bin/pint --test
	@echo "Checking Frontend TypeScript types..."
	cd frontend && $(NPM) run type-check

# ------------------------------------------------------------------------------
# 6. Production Build & Deployment
# ------------------------------------------------------------------------------

.PHONY: build
build: build-frontend build-backend ## Build frontend bundle and optimize backend for production

.PHONY: build-frontend
build-frontend: ## Build Vue 3 production bundle to frontend/dist
	@echo "Building Frontend SPA production bundle..."
	cd frontend && $(NPM) run build

.PHONY: build-backend
build-backend: ## Cache configuration, routes, and events for production
	@echo "Optimizing Backend caches..."
	cd backend && $(PHP) artisan config:cache
	cd backend && $(PHP) artisan route:cache
	cd backend && $(PHP) artisan view:cache

.PHONY: preview
preview: ## Preview the production frontend build locally
	@echo "Previewing production frontend build..."
	cd frontend && $(NPM) run preview

# ------------------------------------------------------------------------------
# 7. Cleanup & Maintenance
# ------------------------------------------------------------------------------

.PHONY: clean
clean: clean-cache ## Clean application cache, logs, and temporary build outputs
	@echo "Removing frontend build artifacts..."
	@$(NODE) -e "const fs = require('fs'); fs.rmSync('frontend/dist', { recursive: true, force: true });"

.PHONY: clean-cache
clean-cache: ## Clear Laravel cache, views, and route caches
	@echo "Clearing Backend caches..."
	cd backend && $(PHP) artisan optimize:clear
