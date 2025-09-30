SHELL := /bin/sh

# Convenience variables
DC := docker compose
APP := app

.PHONY: build up down restart logs bash sh composer install test cs cs-fix static migrate seed db psql

build:
	$(DC) build

up:
	$(DC) up -d

restart: down up

down:
	$(DC) down

logs:
	$(DC) logs -f --tail=200

bash:
	$(DC) exec $(APP) bash

sh:
	$(DC) exec $(APP) sh

composer:
	$(DC) exec $(APP) composer $(args)

install:
	$(DC) exec $(APP) composer install

# Quality and tests
test:
	$(DC) exec $(APP) composer test

cs:
	$(DC) exec $(APP) composer cs:check

cs-fix:
	$(DC) exec $(APP) composer cs:fix

static:
	$(DC) exec $(APP) composer static

# Database helpers
migrate:
	# Fallback without migrations package: update schema from metadata
	$(DC) exec $(APP) php bin/console orm:schema-tool:update --force

seed:
	# Placeholder: implement your seeders here (e.g., a php script under bin/seed.php)
	@echo "No seeders implemented yet. Create bin/seed.php and adjust this target."

psql:
	# Connect to Postgres inside the db container
	$(DC) exec db psql -U $$DB_USER -d $$DB_NAME
