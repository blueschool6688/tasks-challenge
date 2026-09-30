# ==============================================================================
# Team Task Manager - Automation Makefile
# Full-Stack: Laravel 11 REST API + Vue 3 SPA (Vuetify 3 & TypeScript)
# ==============================================================================

.DEFAULT_GOAL := help

# Colors for terminal output
BLUE    := \033[36m
GREEN   := \033[32m
YELLOW  := \033[33m
RED     := \033[31m
RESET   := \033[0m

# Executables (resolves in PATH, fallback to default)
PHP      ?= php
COMPOSER ?= composer
NPM      ?= npm

.PHONY: help
help: ## Show this help message with available commands
	@echo ""
	@echo "$(BLUE)=====================================================================$(RESET)"
	@echo "$(GREEN)              Team Task Manager - Management Makefile$(RESET)"
	@echo "$(BLUE)=====================================================================$(RESET)"
	@echo ""
	@echo "$(YELLOW)Usage:$(RESET) make [target]"
	@echo ""
	@echo "$(YELLOW)Available targets:$(RESET)"
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  $(GREEN)%-18s$(RESET) %s\n", $$1, $$2}' $(MAKEFILE_LIST)
	@echo ""

# ------------------------------------------------------------------------------
# 1. Project Initialization & Setup
# ------------------------------------------------------------------------------

.PHONY: install
install: install-backend install-frontend ## Install dependencies for both Backend & Frontend

.PHONY: install-backend
install-backend: ## Install backend Composer dependencies
	@echo "$(BLUE)Installing Backend Composer dependencies...$(RESET)"
	cd backend && $(COMPOSER) install

.PHONY: install-frontend
install-frontend: ## Install frontend NPM dependencies
	@echo "$(BLUE)Installing Frontend NPM dependencies...$(RESET)"
	cd frontend && $(NPM) install

.PHONY: setup
setup: install setup-env migrate-fresh ## One-step complete project initial setup
	@echo ""
	@echo "$(GREEN)=====================================================================$(RESET)"
	@echo "$(GREEN) Setup completed successfully! Run 'make dev' to start development. $(RESET)"
	@echo "$(GREEN)=====================================================================$(RESET)"

.PHONY: setup-env
setup-env: ## Copy .env.example to .env and generate application key if needed
	@if [ ! -f backend/.env ]; then \
		echo "$(BLUE)Creating backend/.env from .env.example...$(RESET)"; \
		cp backend/.env.example backend/.env; \
		cd backend && $(PHP) artisan key:generate; \
	else \
		echo "$(YELLOW)backend/.env already exists, skipping creation.$(RESET)"; \
	fi

# ------------------------------------------------------------------------------
# 2. Development Servers
# ------------------------------------------------------------------------------

.PHONY: dev-backend
dev-backend: ## Start Laravel API server on http://localhost:8000
	@echo "$(GREEN)Starting Laravel API on http://localhost:8000...$(RESET)"
	cd backend && $(PHP) artisan serve --host=127.0.0.1 --port=8000

.PHONY: dev-frontend
dev-frontend: ## Start Vue 3 Vite dev server on http://localhost:5173
	@echo "$(GREEN)Starting Vue 3 SPA on http://localhost:5173...$(RESET)"
	cd frontend && $(NPM) run dev

.PHONY: dev
dev: ## Display instructions for running both Backend & Frontend in parallel
	@echo ""
	@echo "$(YELLOW)To run both services simultaneously, open two terminal tabs:$(RESET)"
	@echo "  Terminal 1 (Backend API):  $(GREEN)make dev-backend$(RESET)"
	@echo "  Terminal 2 (Frontend SPA): $(GREEN)make dev-frontend$(RESET)"
	@echo ""
	@echo "$(BLUE)Backend API:  http://localhost:8000$(RESET)"
	@echo "$(BLUE)Frontend SPA: http://localhost:5173$(RESET)"
	@echo ""

# ------------------------------------------------------------------------------
# 3. Database Operations
# ------------------------------------------------------------------------------

.PHONY: migrate
migrate: ## Run pending database migrations
	@echo "$(BLUE)Running database migrations...$(RESET)"
	cd backend && $(PHP) artisan migrate --force

.PHONY: migrate-fresh
migrate-fresh: ## Drop all tables, re-run all migrations and seed test data
	@echo "$(BLUE)Recreating database tables and seeding test data...$(RESET)"
	cd backend && $(PHP) artisan migrate:fresh --seed --force

.PHONY: seed
seed: ## Run database seeder (idempotent)
	@echo "$(BLUE)Seeding database...$(RESET)"
	cd backend && $(PHP) artisan db:seed --force

# ------------------------------------------------------------------------------
# 4. Testing & Quality Assurance
# ------------------------------------------------------------------------------

.PHONY: test
test: test-backend test-frontend ## Run both Backend tests and Frontend type-check

.PHONY: test-backend
test-backend: ## Run PHPUnit / Pest automated test suite
	@echo "$(BLUE)Running Backend PHPUnit test suite...$(RESET)"
	cd backend && $(PHP) artisan test

.PHONY: test-frontend
test-frontend: ## Run TypeScript strict type-checking on frontend
	@echo "$(BLUE)Running Frontend TypeScript check...$(RESET)"
	cd frontend && $(NPM) run type-check

# ------------------------------------------------------------------------------
# 5. Code Formatting & Linting
# ------------------------------------------------------------------------------

.PHONY: format
format: format-backend ## Format code across the repository (Laravel Pint)

.PHONY: format-backend
format-backend: ## Fix code style issues in backend using Laravel Pint
	@echo "$(BLUE)Formatting Backend code with Laravel Pint...$(RESET)"
	cd backend && ./vendor/bin/pint || $(PHP) vendor/bin/pint

.PHONY: lint
lint: ## Inspect code style and types without modifying files
	@echo "$(BLUE)Checking Backend code style with Laravel Pint...$(RESET)"
	cd backend && ./vendor/bin/pint --test || $(PHP) vendor/bin/pint --test
	@echo "$(BLUE)Checking Frontend TypeScript types...$(RESET)"
	cd frontend && $(NPM) run type-check

# ------------------------------------------------------------------------------
# 6. Production Build & Deployment
# ------------------------------------------------------------------------------

.PHONY: build
build: build-frontend build-backend ## Build frontend bundle and optimize backend for production

.PHONY: build-frontend
build-frontend: ## Build Vue 3 production bundle to frontend/dist
	@echo "$(BLUE)Building Frontend SPA production bundle...$(RESET)"
	cd frontend && $(NPM) run build

.PHONY: build-backend
build-backend: ## Cache configuration, routes, and events for production
	@echo "$(BLUE)Optimizing Backend caches...$(RESET)"
	cd backend && $(PHP) artisan config:cache
	cd backend && $(PHP) artisan route:cache
	cd backend && $(PHP) artisan view:cache

.PHONY: preview
preview: ## Preview the production frontend build locally
	@echo "$(GREEN)Previewing production frontend build...$(RESET)"
	cd frontend && $(NPM) run preview

# ------------------------------------------------------------------------------
# 7. Cleanup & Maintenance
# ------------------------------------------------------------------------------

.PHONY: clean
clean: clean-cache ## Clean application cache, logs, and temporary build outputs
	@echo "$(BLUE)Removing frontend build artifacts...$(RESET)"
	rm -rf frontend/dist

.PHONY: clean-cache
clean-cache: ## Clear Laravel cache, views, and route caches
	@echo "$(BLUE)Clearing Backend caches...$(RESET)"
	cd backend && $(PHP) artisan optimize:clear
