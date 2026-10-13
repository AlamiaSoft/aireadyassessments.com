# Build from repository root: docker compose -f deploy/portainer/stack.yml build api
# FrankenPHP is the production HTTP runtime; start in classic mode first.
# Octane worker mode can be enabled later after dependency locking and load tests.
FROM dunglas/frankenphp:1-php8.3-bookworm

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    APP_ENV=production \
    APP_DEBUG=false

RUN install-php-extensions \
    pdo_pgsql \
    zip \
    dom \
    mbstring \
    intl \
    pcntl \
    redis

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app

COPY apps/api/ ./
RUN mkdir -p bootstrap/cache storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs storage/app/private /config/caddy /data/caddy \
    && composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && chown -R www-data:www-data storage bootstrap/cache /config /data \
    && chmod -R ug+rwX storage bootstrap/cache /config /data

COPY --chown=root:root apps/api/Caddyfile /etc/caddy/Caddyfile

USER www-data
EXPOSE 8000
CMD ["--config", "/etc/caddy/Caddyfile", "--adapter", "caddyfile"]
