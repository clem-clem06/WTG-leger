# Single-stage production Dockerfile for WorkTogether_LightClient
# Simpler than the multi-stage version: one FROM, top-to-bottom.
# Trade-off: larger image (keeps composer + build tools) and slower rebuilds
# (any source change reinstalls vendors).

FROM dunglas/frankenphp:1-php8.4-alpine

ENV APP_ENV=prod \
    APP_DEBUG=0 \
    SERVER_NAME=":80" \
    COMPOSER_ALLOW_SUPERUSER=1

# PHP extensions required by the app + opcache for prod perf
RUN install-php-extensions \
        @composer \
        intl \
        pdo_mysql \
        zip \
        sodium \
        opcache

COPY docker/php/app.prod.ini $PHP_INI_DIR/conf.d/app.ini

WORKDIR /app

# Copy the whole project (respecting .dockerignore)
COPY . .

# Install prod dependencies, warm cache, compile assets
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader \
 && php bin/console cache:clear \
 && php bin/console asset-map:compile \
 && chown -R www-data:www-data var public/assets

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
