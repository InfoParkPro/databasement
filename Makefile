.PHONY: help install start test test-filter test-coverage backup-test lint-check lint-fix lint migrate migrate-fresh db-seed setup clean

# Colors for output
GREEN  := \033[0;32m
YELLOW := \033[0;33m
NC     := \033[0m # No Color

# Docker / PHP helpers
DOCKER_COMPOSE := docker compose
PHP_SERVICE    := php
PHP_EXEC       := $(DOCKER_COMPOSE) exec $(PHP_SERVICE)
PHP_COMPOSER   := $(PHP_EXEC) composer
PHP_ARTISAN    := $(PHP_EXEC) php artisan
NPM_EXEC       := npm

##@ Help

help: ## Display this help message
	@echo "$(GREEN)Available commands:$(NC)"
	@awk 'BEGIN {FS = ":.*##"; printf "\n"} /^[a-zA-Z_-]+:.*?##/ { printf "  $(YELLOW)%-15s$(NC) %s\n", $$1, $$2 } /^##@/ { printf "\n$(GREEN)%s$(NC)\n", substr($$0, 5) } ' $(MAKEFILE_LIST)

##@ Development

install: ## Install dependencies (composer + npm)
	$(PHP_COMPOSER) install
	$(NPM_EXEC) install

setup: ## Full project setup (install, env, key, migrate, build)
	$(PHP_COMPOSER) setup

start: ## Start development server (all services: php, queue, mysql, postgres)
	docker compose up -d

##@ Testing

test: ## Run all tests
	$(PHP_ARTISAN) test

test-filter: ## Run tests with filter (usage: make test-filter FILTER=DatabaseServer)
	$(PHP_ARTISAN) test --filter=$(FILTER)

test-coverage: ## Run tests with coverage
	$(PHP_ARTISAN) test --coverage

backup-test: ## Run end-to-end backup and restore tests (mysql + postgres)
	$(PHP_ARTISAN) backup:test

##@ Code Quality

lint-check: ## Check code style with Laravel Pint
	$(PHP_EXEC) vendor/bin/pint --test

lint-fix: ## Fix code style with Laravel Pint
	$(PHP_EXEC) vendor/bin/pint

lint: lint-fix ## Alias for lint-fix

phpstan: ## Run PHPStan static analysis
	$(PHP_EXEC) vendor/bin/phpstan analyse --memory-limit=1G

pre-commit: lint-fix phpstan test

##@ Assets

build: ## Build production assets
	$(NPM_EXEC) run build

dev-assets: ## Start Vite dev server only
	$(NPM_EXEC) run dev

##@ Maintenance

clean: ## Clear all caches
	$(PHP_ARTISAN) cache:clear
	$(PHP_ARTISAN) config:clear
	$(PHP_ARTISAN) route:clear
	$(PHP_ARTISAN) view:clear

optimize: ## Optimize the application for production
	$(PHP_ARTISAN) config:cache
	$(PHP_ARTISAN) route:cache
	$(PHP_ARTISAN) view:cache
