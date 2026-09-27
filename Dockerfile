# syntax=docker/dockerfile:1.7
FROM php:8.3-fpm-alpine

# Build without Xdebug (e.g. for production) with: --build-arg INSTALL_XDEBUG=0
ARG INSTALL_XDEBUG=1

# Runtime libraries are kept; build-only dependencies are removed in the same layer
RUN set -eux; \
    apk add --no-cache bash git icu-libs libpq; \
    apk add --no-cache --virtual .build-deps $PHPIZE_DEPS icu-dev postgresql-dev linux-headers; \
    docker-php-ext-install -j"$(nproc)" intl pdo_pgsql opcache; \
    if [ "$INSTALL_XDEBUG" = "1" ]; then \
        pecl install xdebug; \
        docker-php-ext-enable xdebug; \
    fi; \
    pecl clear-cache; \
    rm -rf /tmp/pear; \
    apk del --no-network .build-deps

# Copy Composer from official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Set recommended PHP.ini settings for production-like dev
RUN { \
        echo "opcache.enable=1"; \
        echo "opcache.enable_cli=1"; \
        echo "memory_limit=512M"; \
    } > /usr/local/etc/php/conf.d/custom.ini

# Ignored by PHP when the extension is not installed
COPY docker/php/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

# Expose container environment variables (docker-compose) to PHP-FPM workers
RUN echo "clear_env = no" >> /usr/local/etc/php-fpm.d/www.conf

# Create non-root user (www-data is default in php-fpm)
RUN addgroup -g 1000 -S www && adduser -S www -G www -u 1000 && \
    chown -R www:www /var/www/html

USER www

# Default command
CMD ["php-fpm"]
