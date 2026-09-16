#!/bin/sh
set -e

# Re-assert ownership of the directories Laravel writes to at runtime.
# Necessary because a bind mount or named volume can override the
# ownership that was set at build time in the Dockerfile.
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

if [ "$APP_ENV" = "production" ]; then
    # Unlike docker/docker-entrypoint.sh (used by compose/VPS), migrations
    # DO run here, deliberately. Render's free web service plan has no
    # Pre-Deploy Command (that's paid-only), and this service runs as a
    # single instance with no autoscaling, so there's no multi-replica
    # race condition to worry about yet. `migrate --force` is safe to
    # re-run on every boot — Laravel skips migrations that already ran.
    #
    # If this service is ever scaled to more than one instance, move this
    # back out to a proper release step (Render Pre-Deploy Command on a
    # paid plan, or a one-off job) instead of leaving it here.
    php artisan migrate --force
    php artisan optimize:clear
    php artisan optimize
fi

exec "$@"
