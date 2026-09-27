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

## Routes
- GET / and GET /health → {"status":"ok"}
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
- public/index.php: HTTP front controller
- bin/console: Doctrine ORM + Migrations CLI
- config/bootstrap.php: Shared bootstrap (env, container, middleware, routes) used by the front controller, the console and the tests
- config/settings.php: Application settings, built from environment variables
- config/container.php: Container definitions
- config/routes.php: Routes
- src/: Application code (controllers, entities, config helpers)
- migrations/: Doctrine migrations
- var/: Runtime/cache (created on demand)

## Console & Migrations
- php bin/console list
- php bin/console migrations:diff      # generate a migration from entity mapping
- php bin/console migrations:migrate   # apply migrations

## Running Tests & Static Analysis
- composer test
- composer analyse   # PHPStan level 6

## License
MIT (or your preferred license).