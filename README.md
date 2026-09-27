# Slim + Doctrine Skeleton

A minimal Slim 4 + Doctrine ORM project with PHP-DI, Dotenv, Doctrine Migrations and a basic JSON API. Includes a health endpoint, CORS, request validation (symfony/validator), JSON error responses and a Dockerized dev stack.

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
   - composer start
   - Open http://localhost:8080/health to verify: you should see {"status":"ok"}

## Environment Variables
Core flags:
- APP_ENV: development | production (default: production)
- APP_DEBUG: 1/0, true/false (default: true when APP_ENV != production)

Database (Doctrine DBAL):
- DB_DRIVER: pdo_sqlite | pdo_mysql | pdo_pgsql (default: pdo_sqlite)
- For SQLite:
  - DB_PATH: absolute path, or path relative to the project root (default: var/data/database.sqlite)
- For MySQL:
  - DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASSWORD
- For PostgreSQL:
  - DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASSWORD

CORS (comma-separated lists):
- CORS_ORIGINS (default: *), CORS_METHODS, CORS_HEADERS, CORS_EXPOSE_HEADERS
- CORS_CREDENTIALS (default: 0), CORS_MAX_AGE (default: 600)

Per-environment overrides: create config/settings.{APP_ENV}.php returning a partial settings array.

## Routes
- GET / and GET /health → {"status":"ok"}
- GET /api?name=Ada → {"message":"Hello Ada"} (`name` is optional, min. 2 chars; invalid input returns 422)

## Error Handling
- All errors are returned as JSON: {"error": {"code": 404, "message": "404 Not Found"}}
- In debug (APP_DEBUG=1, or APP_ENV != production), a `details` key adds the exception type, message and trace.
- In production, details are hidden; errors are logged via PHP's error_log.

## Development Tips
- Update TASKS.md for improvement ideas and backlog.
- Add tests under tests/ and run: composer test
- Consider adding Xdebug for step debugging in development.

## Project Structure
- public/index.php: HTTP front controller
- bin/console: Doctrine ORM + Migrations CLI
- config/bootstrap.php: Shared bootstrap (env, container, middleware, routes) used by the front controller, the console and the tests
- config/settings.php: Application settings, built from environment variables
- config/container.php: Container definitions
- config/routes.php: Routes
- src/: Application code (Controllers, Requests, Validation, Middleware, Handlers, Entities, Config)
- migrations/: Doctrine migrations
- var/: Runtime/cache (created on demand)

## Request Validation
Request input is described by a DTO in src/Requests whose constructor parameters carry
symfony/validator constraints. Controllers map and validate it explicitly with `RequestMapper`:

```php
final class CreateUserRequest
{
    public function __construct(
        #[Assert\NotBlank, Assert\Length(min: 2)]
        public readonly string $name,
        #[Assert\Email]
        public readonly string $email,
        public readonly bool $newsletter = false,
    ) {
    }
}

// In a controller (RequestMapper is injected by the container)
$input = $this->mapper->map($request, CreateUserRequest::class);
```

- Input comes from the query params merged with the parsed body (the body wins); unknown keys are ignored.
- Values are cast to the declared scalar types (`int`, `float`, `bool`, `string`, `array`), so query strings work as expected.
- Missing parameters use their default value, or `null` when nullable; otherwise they are reported as required.
- Invalid input throws a `ValidationException`, rendered as a 422 with the errors per field, in every environment:
  `{"error": {"code": 422, "message": "422 Unprocessable Entity", "errors": {"name": ["This value is too short. It should have 2 characters or more."]}}}`

Entities keep their own invariants (constructor/method checks, database constraints): request DTOs validate
what the client sends, entities guarantee they can never be in an invalid state.

## Console & Migrations
- php bin/console list
- php bin/console migrations:diff      # generate a migration from entity mapping
- php bin/console migrations:migrate   # apply migrations

## Running Tests & Quality Checks
- composer test       # PHPUnit
- composer analyse    # PHPStan level 6
- composer cs:check   # PHP-CS-Fixer dry-run (composer cs:fix to apply)

The same checks run in CI (.github/workflows/ci.yml) on PHP 8.2 and 8.3.

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
- make analyse     # static analysis (PHPStan)
- make migrate     # run Doctrine migrations
- make seed        # seed data (placeholder)

Service endpoints:
- App (Nginx): http://localhost:8080
- Postgres: localhost:5432 (user: app, password: secret)

Notes:
- Source code is mounted into the container; edits are reflected immediately.
- The Docker stack always uses its Postgres service (DB_DRIVER/DB_HOST/DB_PORT are fixed in docker-compose.yml), so your local .env can keep SQLite for non-Docker runs.

## Xdebug in Docker

Xdebug is pre-installed in the PHP-FPM image and configured via environment variables.

- Default dev settings (in docker-compose):
  - XDEBUG_MODE=debug,develop
  - XDEBUG_START_WITH_REQUEST=trigger
  - XDEBUG_CLIENT_PORT=9003
  - XDEBUG_CLIENT_HOST=host.docker.internal (and Linux fallback via host-gateway)
- To change, edit .env (recommended) or docker-compose.yml, then restart the stack (`make restart`).
  Settings are read from the container environment when PHP starts (see docker/php/xdebug.ini), no rebuild needed.
- Outside docker-compose, Xdebug defaults to `mode=off`.

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
- test, cs, cs-fix, analyse
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
   - Use the provided Dockerfile as a base, built without Xdebug:
     - docker build --build-arg INSTALL_XDEBUG=0 -t your-org/your-app:TAG .
   - Run composer install with --no-dev and optimize autoloader:
     - composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
   - Configure PHP Opcache (already enabled in Dockerfile) and set appropriate memory limits.

3. Web server
   - Use Nginx (example config under docker/nginx/default.conf) or your preferred reverse proxy.
   - Point the document root to public/.
   - Ensure HTTPS is terminated at the load balancer or proxy; set trusted proxies if needed.

4. Database migrations
   - php bin/console migrations:migrate --no-interaction --allow-no-migration

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
