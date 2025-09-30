# Slim + Doctrine Skeleton

A minimal Slim 4 + Doctrine ORM project with PHP-DI, Dotenv, and a basic API. Includes a health endpoint and environment-driven error display.

## Requirements
- PHP 8.2+
- Composer
- SQLite (default) or MySQL/PostgreSQL (optional)

## Getting Started

1. Clone the repository

2. Install dependencies
   - composer install

3. Configure environment
   - cp .env.example .env
   - Edit .env to match your local setup (DB settings, environment, debug flags)

4. Run the app locally (PHP built-in server)
   - php -S localhost:8080 -t public
   - Open http://localhost:8080/health to verify: you should see {"status":"ok"}

## Environment Variables
Core flags:
- APP_ENV: development | production (default: development)
- APP_DEBUG: 1/0, true/false (default: true when APP_ENV != production)

Database (Doctrine DBAL):
- DB_DRIVER: pdo_sqlite | pdo_mysql | pdo_pgsql (default: pdo_sqlite)
- For SQLite:
  - DB_PATH: absolute/relative path to SQLite file (default: var/data/database.sqlite)
- For MySQL:
  - DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASSWORD
- For PostgreSQL:
  - DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASSWORD

## Routes
- GET /health → {"status":"ok"}
- GET /api → Example JSON payload

## Error Handling
- Error display is controlled by environment:
  - In development (or when APP_DEBUG=true), displayErrorDetails is enabled and PHP displays errors.
  - In production (APP_ENV=production and APP_DEBUG not set/false), user-facing error details are hidden.

## Development Tips
- Update TASKS.md for improvement ideas and backlog.
- Add tests under tests/ and run: composer test
- Consider adding Xdebug for step debugging in development.

## Project Structure
- public/index.php: Front controller, routes, middleware
- config/: Container definitions
- src/: Application code (controllers, entities, config helpers)
- var/: Runtime/cache (created on demand)

## Running Tests
- composer test

## License
MIT (or your preferred license).

---

## Local Development with Docker

This project ships with a Dockerized dev stack: PHP-FPM + Nginx + Postgres.

Prerequisites:
- Docker Desktop (or Docker Engine)
- Make (optional, but recommended)

Steps:
1. Copy environment file
   - cp .env.example .env
2. Build and start services
   - make up
   - Open http://localhost:8080/health → {"status":"ok"}
3. Install PHP dependencies (inside the container)
   - make install

Common commands:
- make up          # start stack in background
- make down        # stop and remove containers
- make logs        # tail logs from all services
- make bash        # shell into PHP container (bash)
- make test        # run PHPUnit in container
- make cs          # coding standards check (PHP-CS-Fixer dry-run)
- make cs-fix      # auto-fix coding standards
- make static      # static analysis (PHPStan)
- make migrate     # update DB schema from metadata (fallback without migrations)
- make seed        # seed data (placeholder)

Service endpoints:
- App (Nginx): http://localhost:8080
- Postgres: localhost:5432 (user: app, password: secret)

Notes:
- Source code is mounted into the container; edits are reflected immediately.
- If you prefer MySQL or SQLite, adjust .env accordingly and update docker-compose.yml.

## Xdebug in Docker

Xdebug is pre-installed in the PHP-FPM image and configured via environment variables.

- Default dev settings (in docker-compose):
  - XDEBUG_MODE=debug,develop
  - XDEBUG_START_WITH_REQUEST=trigger
  - XDEBUG_CLIENT_PORT=9003
  - XDEBUG_CLIENT_HOST=host.docker.internal (and Linux fallback via host-gateway)
- To change, edit .env (recommended) or docker-compose.yml.

How to use with PhpStorm (or similar IDE):
- Ensure your IDE is listening for PHP Debug connections on port 9003.
- Set a Path Mapping: map your local project folder to /var/www/html in the container.
- Trigger a debug session:
  - Use the IDE browser helper extension to enable the Xdebug session cookie, OR
  - Append ?XDEBUG_TRIGGER=1 to your request, OR
  - Set an environment variable XDEBUG_TRIGGER=1 in the request context.

Troubleshooting:
- If breakpoints are not hit, verify that the container can reach your host:
  - docker compose exec app ping -c1 host.docker.internal
  - If unreachable, set XDEBUG_DISCOVER_CLIENT_HOST=1 or set XDEBUG_CLIENT_HOST to your host IP.
- Check logs by raising XDEBUG_LOG_LEVEL to 7.

## Makefile Targets

A Makefile is included to simplify common tasks:
- build, up, down, restart, logs
- bash, sh, composer, install
- test, cs, cs-fix, static
- migrate, seed, psql

Use `make <target>`; some targets accept variables, for example:
- make composer args="require some/package"

## Production Deployment

This repository is not a full production image, but here are recommended steps:

1. Environment variables
   - APP_ENV=production
   - APP_DEBUG=0
   - DB_DRIVER= pdo_pgsql | pdo_mysql | pdo_sqlite
   - If Postgres/MySQL: DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASSWORD
   - If SQLite: DB_PATH

2. Build an optimized PHP-FPM image
   - Use the provided Dockerfile as a base.
   - Run composer install with --no-dev and optimize autoloader:
     - composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
   - Configure PHP Opcache (already enabled in Dockerfile) and set appropriate memory limits.

3. Web server
   - Use Nginx (example config under docker/nginx/default.conf) or your preferred reverse proxy.
   - Point the document root to public/.
   - Ensure HTTPS is terminated at the load balancer or proxy; set trusted proxies if needed.

4. Database migrations
   - Recommended: add doctrine/migrations and manage schema via migrations.
     - composer require doctrine/migrations
     - vendor/bin/doctrine-migrations migrate --no-interaction --allow-no-migration
   - Fallback (not ideal for production): ORM schema-tool update (used by `make migrate`).

5. Cache warmup and readiness
   - Composer autoload optimization (see step 2).
   - Pre-generate any metadata caches your app uses (e.g., Doctrine metadata cache if configured).
   - Run a health check endpoint (/health) and DB connectivity check during deployment.

6. Observability & logging
   - Aggregate PHP-FPM and Nginx logs to your central logging (stdout/stderr for containers).
   - Configure application logs via PSR-3 (e.g., Monolog) with production handlers.

7. Security
   - Ensure APP_DEBUG=0 and errors are not displayed to end users.
   - Set strong DB credentials and rotate secrets via your secret manager.
   - Add security headers at the proxy layer (CSP, HSTS, etc.).

Deployment outline (containerized):
- Build: docker build -t your-org/your-app:TAG .
- Run DB migrations as a pre-deploy step.
- Deploy containers behind your ingress/load balancer.
- Use health checks and readiness gates before shifting traffic.
