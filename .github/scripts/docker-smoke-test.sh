#!/usr/bin/env sh
# Smoke-tests the Docker image and the docker-compose stack.
# Usage: IMAGE=<tag> [IMAGE_NO_XDEBUG=<tag>] .github/scripts/docker-smoke-test.sh
# Expects dependencies to be installed in ./vendor and port 8080 to be free.
set -eu

IMAGE="${IMAGE:?IMAGE is required}"
COMPOSE="docker compose"

fail() { echo "::error::$*" >&2; exit 1; }

echo "--- PHP extensions"
modules=$(docker run --rm "$IMAGE" php -m)
for ext in intl pdo_pgsql pdo_sqlite xdebug "Zend OPcache"; do
    echo "$modules" | grep -qx "$ext" || fail "missing PHP extension: $ext"
done

echo "--- Xdebug loads once and reads its settings at runtime"
warnings=$(docker run --rm "$IMAGE" php -v 2>&1 >/dev/null || true)
[ -z "$warnings" ] || fail "PHP startup warnings: $warnings"
port=$(docker run --rm -e XDEBUG_CLIENT_PORT=9010 "$IMAGE" php -r 'echo ini_get("xdebug.client_port");')
[ "$port" = "9010" ] || fail "xdebug.client_port should come from the environment, got '$port'"
mode=$(docker run --rm "$IMAGE" php -r 'echo ini_get("xdebug.mode");')
[ "$mode" = "off" ] || fail "xdebug.mode should default to off, got '$mode'"

if [ -n "${IMAGE_NO_XDEBUG:-}" ]; then
    echo "--- Image built with INSTALL_XDEBUG=0"
    # Assign first so that a failing `docker run` aborts the script (set -e)
    modules=$(docker run --rm "$IMAGE_NO_XDEBUG" php -m)
    if echo "$modules" | grep -qx xdebug; then
        fail "xdebug should not be installed"
    fi
fi

echo "--- docker-compose stack"
trap '$COMPOSE logs --no-color; $COMPOSE down -v' EXIT
$COMPOSE up -d --no-build

for i in $(seq 1 30); do
    curl -fsS http://localhost:8080/health >/dev/null 2>&1 && break
    [ "$i" -lt 30 ] || fail "stack did not become healthy"
    sleep 2
done

expect() {
    body=$(curl -sS "http://localhost:8080$1")
    [ "$body" = "$2" ] || fail "GET $1: expected $2, got $body"
    echo "GET $1 -> $body"
}
expect /health '{"status":"ok"}'
expect '/api?name=Docker' '{"message":"Hello Docker"}'

# Postgres may still be starting: retry the database check
for i in $(seq 1 15); do
    $COMPOSE exec -T app php bin/console migrations:status >/dev/null 2>&1 && break
    [ "$i" -lt 15 ] || { $COMPOSE exec -T app php bin/console migrations:status; fail "database unreachable"; }
    sleep 2
done
$COMPOSE exec -T app php bin/console migrations:status | grep -q 'PDO\\PgSQL\\Driver' || fail "app is not using Postgres"
echo "Doctrine -> Postgres OK"

echo "--- Migrations on Postgres"
$COMPOSE exec -T app php bin/console migrations:migrate --no-interaction
$COMPOSE exec -T app php bin/console orm:validate-schema || fail "database schema does not match the entities"

echo "--- Users and authentication"
status() { curl -s -o /dev/null -w '%{http_code}' "$@"; }

created=$(curl -sS -X POST http://localhost:8080/users -H 'Content-Type: application/json' \
    -d '{"email":"smoke@example.com","name":"Smoke Test","password":"correct horse"}')
echo "$created" | grep -q '"email":"smoke@example.com"' || fail "POST /users: $created"

login=$(curl -sS -X POST http://localhost:8080/auth/login -H 'Content-Type: application/json' \
    -d '{"email":"smoke@example.com","password":"correct horse"}')
token=$(echo "$login" | sed -n 's/.*"token":"\([0-9a-f]\{64\}\)".*/\1/p')
[ -n "$token" ] || fail "POST /auth/login: $login"

me=$(curl -sS http://localhost:8080/auth/me -H "Authorization: Bearer $token")
echo "$me" | grep -q '"email":"smoke@example.com"' || fail "GET /auth/me: $me"

[ "$(status http://localhost:8080/auth/me)" = 401 ] || fail "GET /auth/me without token should be 401"
[ "$(status -X POST http://localhost:8080/auth/logout -H "Authorization: Bearer $token")" = 204 ] || fail "logout failed"
[ "$(status http://localhost:8080/auth/me -H "Authorization: Bearer $token")" = 401 ] || fail "token still valid after logout"
echo "register -> login -> me -> logout OK"

trap - EXIT
$COMPOSE down -v
echo "Docker smoke test passed"
