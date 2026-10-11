#!/bin/sh
set -e

# One-time preparation only for the web container; other commands (scheduler,
# artisan one-offs) run as-is.
if [ "$1" = "apache2-foreground" ]; then
    if [ -z "$APP_KEY" ]; then
        echo "APP_KEY kosong. Buat dengan: docker compose run --rm --no-deps app php artisan key:generate --show" >&2
        echo "lalu salin hasilnya ke APP_KEY di file .env." >&2
        exit 1
    fi

    # The storage volume may be older than this image.
    mkdir -p storage/app/private storage/framework/cache/data storage/framework/sessions \
        storage/framework/views storage/logs

    # Forward-only migrations (never fresh/rollback). Disable with RUN_MIGRATIONS=false.
    if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
        php artisan migrate --force
    fi

    php artisan optimize

    # Commands above ran as root; Apache serves as www-data.
    find storage bootstrap/cache ! -user www-data -exec chown www-data:www-data {} +
fi

exec "$@"
