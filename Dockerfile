# syntax=docker/dockerfile:1.7
FROM php:8.3-fpm-alpine

# Install system dependencies and PHP extensions (incl. Xdebug)
RUN set -eux; \
    apk add --no-cache --update \
        bash \
        git \
        icu-dev \
        libpq \
        libzip-dev \
        oniguruma-dev \
        $PHPIZE_DEPS; \
    docker-php-ext-configure intl; \
    docker-php-ext-install -j"$(nproc)" \
        intl \
        pdo \
        pdo_pgsql \
        opcache; \
    pecl install xdebug; \
    docker-php-ext-enable xdebug; \
    apk del --no-network $PHPIZE_DEPS

# Copy Composer from official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Set recommended PHP.ini settings for production-like dev
RUN { \
        echo "opcache.enable=1"; \
        echo "opcache.enable_cli=1"; \
        echo "memory_limit=512M"; \
    } > /usr/local/etc/php/conf.d/custom.ini \
    && { \
        echo "; Xdebug configuration (values can be overridden via env)"; \
        echo "zend_extension=xdebug"; \
        echo "xdebug.mode=${XDEBUG_MODE}"; \
        echo "xdebug.start_with_request=${XDEBUG_START_WITH_REQUEST}"; \
        echo "xdebug.client_host=${XDEBUG_CLIENT_HOST}"; \
        echo "xdebug.client_port=${XDEBUG_CLIENT_PORT}"; \
        echo "xdebug.log_level=${XDEBUG_LOG_LEVEL}"; \
        echo "xdebug.discover_client_host=${XDEBUG_DISCOVER_CLIENT_HOST}"; \
    } > /usr/local/etc/php/conf.d/xdebug.ini

# Create non-root user (www-data is default in php-fpm)
RUN addgroup -g 1000 -S www && adduser -S www -G www -u 1000 && \
    chown -R www:www /var/www/html

USER www

# Default command
CMD ["php-fpm"]
