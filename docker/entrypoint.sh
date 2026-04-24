#!/bin/sh
set -e

cd /app

if [ "$APP_ENV" = "dev" ] && [ -f composer.json ]; then
    if [ ! -d vendor ] || [ ! -f vendor/autoload.php ]; then
        echo "[entrypoint] Installing composer dependencies (dev)..."
        composer install --prefer-dist --no-interaction --no-progress
    fi

    if [ -f bin/console ]; then
        if php bin/console list --raw 2>/dev/null | grep -qx 'importmap:install'; then
            echo "[entrypoint] Installing importmap..."
            php bin/console importmap:install
        else
            echo "[entrypoint] Skipping importmap installation: importmap:install command not available."
        fi
    fi
fi

if [ "$RUN_MIGRATIONS" = "1" ] && [ -f bin/console ]; then
    echo "[entrypoint] Running doctrine migrations..."
    php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
fi

exec "$@"
