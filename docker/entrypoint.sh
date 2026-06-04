#!/bin/sh
set -e

if [ -z "${APP_KEY}" ]; then
    echo "[entrypoint] ERROR: APP_KEY is not set. Set it in your environment." >&2
    exit 1
fi

echo "[entrypoint] Creating storage directories..."
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs storage/app/public

echo "[entrypoint] Caching config..."
php artisan config:cache || {
    echo "[entrypoint] ERROR: php artisan config:cache failed" >&2
    exit 1
}

echo "[entrypoint] Caching views..."
php artisan view:cache || {
    echo "[entrypoint] ERROR: php artisan view:cache failed" >&2
    exit 1
}

if [ -n "${DB_CONNECTION:-}" ] && [ "${RUN_MIGRATIONS:-true}" != "false" ]; then
    echo "[entrypoint] Running migrations..."
    php artisan migrate --force || {
        echo "[entrypoint] ERROR: php artisan migrate --force failed" >&2
        exit 1
    }
fi

echo "[entrypoint] Starting PHP-FPM..."
exec php-fpm
