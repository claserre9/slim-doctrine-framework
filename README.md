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