# syntax=docker/dockerfile:1

# ---------------------------------------------------------------------------
# Stage 1: vendor — install PHP dependencies with Composer.
# Isolated from the runtime stage so Composer, dev tooling, and the zip
# extension it needs never ship in the final image.
# ---------------------------------------------------------------------------
FROM composer:2 AS vendor

WORKDIR /app

# Copy only the dependency manifests first so this (expensive) layer is
# cached and skipped on rebuilds unless composer.json/composer.lock change.
COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --no-interaction \
    --ignore-platform-reqs

# Now bring in the full application source and finish the install so that
# package auto-discovery (which needs the app's own classes) can run, and
# so the autoloader is generated against the real code, not a stub.
COPY . .

RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --ignore-platform-reqs

# ---------------------------------------------------------------------------
# Stage 2: runtime — slim PHP-FPM image with only what the app needs to run.
# No PostgreSQL server, no Redis server, no Composer, no build toolchain.
# ---------------------------------------------------------------------------
FROM php:8.5-fpm-alpine AS runtime

WORKDIR /var/www/html

# Build-only OS packages: compilers/headers needed to compile PHP extensions.
# Installed as a virtual group so they can be removed in one shot after use,
# keeping the final layer small.
#
# Note on pcntl: this image is shared between two roles, selected at
# runtime by the Compose command — `php-fpm` for the
# HTTP-serving container, and `php artisan queue:work` for the queue
# worker (needed by app/Listeners/RecordAuditLog.php, which is queued).
# pcntl is native to PHP and needs no extra system library, so keeping it
# in this single image is effectively free.
RUN apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        postgresql-dev \
        oniguruma-dev \
        curl-dev \
    && apk add --no-cache \
        postgresql-libs \
        curl \
    && docker-php-ext-install pdo_pgsql \
    && docker-php-ext-install mbstring \
    && docker-php-ext-install curl \
    && docker-php-ext-install pcntl \
    && curl -fsSL -o /usr/local/bin/pie https://github.com/php/pie/releases/latest/download/pie.phar \
    && chmod +x /usr/local/bin/pie \
    && pie install phpredis/phpredis \
    && rm -f /usr/local/bin/pie \
    && apk del .build-deps \
    && rm -rf /tmp/pear /var/cache/apk/*

# Baseline OPcache config for #65 (extension present and enabled).
# Aggressive production tuning (validate_timestamps=0, preload, etc.)
# is intentionally deferred to #68 Optimize Production Build.
RUN { \
        echo 'opcache.enable=1'; \
        echo 'opcache.memory_consumption=128'; \
        echo 'opcache.max_accelerated_files=10000'; \
    } > /usr/local/etc/php/conf.d/opcache-baseline.ini

# Bring in vendor/ and the application code already assembled in the
# vendor stage, so the runtime image never runs `composer install` itself.
COPY --from=vendor /app /var/www/html

# Laravel needs to write to these at runtime (cache, sessions, logs,
# compiled views, framework cache). www-data is the user PHP-FPM's worker
# processes run as, so it needs ownership, not just permissive bits.
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

COPY docker/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Deliberately NOT switching to USER www-data here. The entrypoint needs
# root to re-chown storage/ if a mounted volume (added in #66) overrides
# build-time ownership. php-fpm's own master process runs as root by
# design and drops each worker process to www-data via the pool config
# (www.conf) — that privilege drop is not something this Dockerfile needs
# to replicate.
EXPOSE 9000

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]
