#!/bin/sh
set -e

# Re-assert ownership of the directories Laravel writes to at runtime.
# Necessary because a bind mount or named volume (introduced in #66 Docker
# Compose for storage persistence) can override the ownership that was set
# at build time in the Dockerfile.
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

php artisan optimize:clear
php artisan optimize

# Deliberately NOT running migrations, key:generate, or storage:link here.
# Those are deployment-time actions, not container-startup actions — baking
# them into the entrypoint risks race conditions if this image is ever
# scaled to multiple replicas. That belongs to #69 Production Deployment
# Guide / the release process, not this image's startup.

exec "$@"
