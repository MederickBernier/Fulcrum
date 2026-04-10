ARG PHP_VERSION=8.3
FROM php:${PHP_VERSION}-fpm-alpine

# Build args for WSL2 user matching
ARG USER_ID=1000
ARG GROUP_ID=1000

#
# System dependencies
#
RUN apk add --no-cache \
    bash \
    git \
    curl \
    zip \
    unzip \
    libpq-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    freetype-dev \
    icu-dev \
    oniguruma-dev \
    linux-headers \
    $PHPIZE_DEPS

#
# PHP extensions
#
RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg \
    --with-webp \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_pgsql \
    pgsql \
    zip \
    intl \
    mbstring \
    opcache \
    pcntl \
    gd \
    exif \
    bcmath

#
# Redis via PECL
#
RUN pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /tmp/pear

#
# Xdebug
#
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && rm -rf /tmp/pear

#
# Clean up build deps
#
RUN apk del $PHPIZE_DEPS \
    && rm -rf /var/cache/apk/*

#
# Composer
#
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

#
# Non-root user matching WSL2 UID/GID
# Prevents file permission headaches
#
RUN addgroup -g ${GROUP_ID} fulcrum \
    && adduser \
    -u ${USER_ID} \
    -G fulcrum \
    -s /bin/bash \
    -D fulcrum \
    && chown -R fulcrum:fulcrum /var/www

WORKDIR /var/www/fulcrum

USER fulcrum

EXPOSE 9000