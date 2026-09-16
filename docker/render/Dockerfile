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
# Stage 2: runtime — single container bundling nginx + php-fpm + supervisord.
#
# This intentionally diverges from docker/Dockerfile (used by compose/VPS):
# Render web services expose exactly one HTTP port per container and don't
# let you link a separate nginx service to this one over a private network
# the way compose does. So both processes have to live in the same
# container here, coordinated by supervisord. docker/Dockerfile stays
# php-fpm-only and untouched for the compose/VPS deployment path.
# ---------------------------------------------------------------------------
FROM php:8.5-fpm-alpine AS runtime

WORKDIR /var/www/html

# Build-only OS packages: compilers/headers needed to compile PHP extensions.
# Installed as a virtual group so they can be removed in one shot after use,
# keeping the final layer small. nginx and supervisor are NOT build-only,
# they stay in the final image.
RUN apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        postgresql-dev \
        oniguruma-dev \
        curl-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
    && apk add --no-cache \
        nginx \
        supervisor \
        postgresql-libs \
        curl \
        libpng \
        libjpeg-turbo \
        freetype \
    && docker-php-ext-install pdo_pgsql \
    && docker-php-ext-install mbstring \
    && docker-php-ext-install curl \
    && docker-php-ext-install pcntl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd \
    && curl -fsSL -o /usr/local/bin/pie https://github.com/php/pie/releases/latest/download/pie.phar \
    && chmod +x /usr/local/bin/pie \
    && pie install phpredis/phpredis:^6.3 \
    && rm -f /usr/local/bin/pie \
    && apk del .build-deps \
    && rm -rf /tmp/pear /var/cache/apk/*

# Baseline OPcache config (extension present and enabled). Aggressive
# production tuning is intentionally out of scope here, same as the
# compose image.
RUN { \
        echo 'opcache.enable=1'; \
        echo 'opcache.memory_consumption=128'; \
        echo 'opcache.max_accelerated_files=10000'; \
        echo 'opcache.validate_timestamps=0'; \
        echo 'opcache.interned_strings_buffer=16'; \
    } > /usr/local/etc/php/conf.d/opcache-baseline.ini

# Bring in vendor/ and the application code already assembled in the
# vendor stage, so the runtime image never runs `composer install` itself.
COPY --from=vendor /app /var/www/html

# Laravel needs to write to these at runtime (cache, sessions, logs,
# compiled views, framework cache). www-data is the user php-fpm's worker
# processes run as, so it needs ownership, not just permissive bits.
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

COPY docker/render/nginx.conf /etc/nginx/conf.d/default.conf
COPY docker/render/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/render/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh

RUN chmod +x /usr/local/bin/docker-entrypoint.sh \
    && mkdir -p /run/nginx

# Deliberately NOT switching to a non-root user here, same reasoning as the
# compose image: the entrypoint needs root to re-chown storage/ if a
# mounted volume ever overrides build-time ownership, and supervisord
# itself needs root to spawn php-fpm's master process, which drops each
# worker to www-data via php-fpm's own pool config (www.conf). nginx's
# master process also starts as root by design and drops its workers on
# its own.
EXPOSE 10000

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf", "-n"]
