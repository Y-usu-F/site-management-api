#!/usr/bin/env sh
set -eu

APP_ROOT="/var/www/html"

if [ -f "$APP_ROOT/composer.json" ] && [ ! -f "$APP_ROOT/vendor/autoload.php" ]; then
  echo "[entrypoint] Installing composer dependencies..."
  composer install --working-dir="$APP_ROOT" --no-interaction --prefer-dist --no-progress
fi

if [ "${DB_CONNECTION:-}" = "MySQLi" ] || [ "${database_default_DBDriver:-}" = "MySQLi" ]; then
  DB_HOST="${DB_HOST:-${database_default_hostname:-mysql}}"
  DB_PORT="${DB_PORT:-${database_default_port:-3306}}"
  DB_USER="${DB_USERNAME:-${database_default_username:-root}}"
  DB_PASS="${DB_PASSWORD:-${database_default_password:-}}"

  echo "[entrypoint] Waiting MySQL at ${DB_HOST}:${DB_PORT}..."
  until mysqladmin ping -h"${DB_HOST}" -P"${DB_PORT}" -u"${DB_USER}" -p"${DB_PASS}" --silent; do
    sleep 2
  done
  echo "[entrypoint] MySQL is ready."
fi

if [ "${DOCKER_AUTO_MIGRATE:-0}" = "1" ] || [ "${DOCKER_AUTO_MIGRATE:-false}" = "true" ]; then
  echo "[entrypoint] Running migrations..."
  php "$APP_ROOT/spark" migrate --all --no-header || {
    echo "[entrypoint] Migration failed."
    exit 1
  }
fi

exec "$@"
