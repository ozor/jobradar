# Dev-образ PHP-FPM для JobRadar.
# Исходники в образ не копируются: проект монтируется volume (см. compose.yaml).

FROM composer:2.11.1 AS composer

FROM php:8.4.27-fpm-trixie

# Версии PECL-расширений: обновлять здесь.
ARG PHPREDIS_VERSION=6.3.0
ARG AMQP_VERSION=2.2.0

# UID/GID пользователя хоста, чтобы файлы в /app принадлежали ему.
ARG UID=1000
ARG GID=1000

# 1. Системные пакеты
#    libfcgi-bin  - cgi-fcgi для healthcheck php-fpm
#    git, unzip   - нужны composer
#    *-dev        - заголовки для сборки расширений
RUN apt-get update \
 && apt-get install -y --no-install-recommends \
        libfcgi-bin \
        git \
        unzip \
        libpq-dev \
        libicu-dev \
        libzip-dev \
        librabbitmq-dev \
 && rm -rf /var/lib/apt/lists/*

# 2. Встроенные расширения PHP
RUN docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql \
        intl \
        zip \
        opcache

# 3. Расширения из PECL
RUN pecl install \
        redis-${PHPREDIS_VERSION} \
        amqp-${AMQP_VERSION} \
 && docker-php-ext-enable redis amqp \
 && rm -rf /tmp/pear

# 4. Composer
COPY --from=composer /usr/bin/composer /usr/local/bin/composer
ENV COMPOSER_HOME=/home/app/.composer

# 5. Конфигурация PHP и FPM
RUN cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
COPY docker/php/conf.d/app.ini $PHP_INI_DIR/conf.d/zz-app.ini
COPY docker/php/php-fpm.d/zz-app.conf /usr/local/etc/php-fpm.d/zz-app.conf

# 6. Непривилегированный пользователь
RUN groupadd -g ${GID} app \
 && useradd -u ${UID} -g app -m -s /bin/bash app

WORKDIR /app
RUN chown app:app /app
USER app