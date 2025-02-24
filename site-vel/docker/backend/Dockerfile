ARG IMAGE_PHP="php:8.3.17-apache-bookworm"
ARG COMPOSER_VERSION="2.8.5"

FROM composer:${COMPOSER_VERSION} AS composer-local


FROM ${IMAGE_PHP} AS base

ENV	DATE_TIMEZONE=Europe/Paris

RUN apt-get update  \
      && apt-get install -y --no-install-recommends \
        libicu-dev \
        libpq-dev \
        libzip-dev \
        postgresql-common \
        unzip \
        && /usr/share/postgresql-common/pgdg/apt.postgresql.org.sh -y \
        && apt-get update && apt-get install -y --no-install-recommends postgresql-client-16 \
        && apt-get autoremove -y && apt-get clean \
        && rm -r /var/lib/apt/lists/*

RUN docker-php-ext-configure zip \
    && docker-php-ext-install zip \
    && docker-php-ext-configure opcache \
    && docker-php-ext-install opcache \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install pdo_pgsql pgsql

RUN a2enmod rewrite vhost_alias headers expires deflate \
    && rm -f /etc/apache2/sites-enabled/000-default.conf

COPY --from=composer-local /usr/bin/composer /usr/local/bin/composer

RUN curl -sf 'https://dl.cloudsmith.io/public/symfony/stable/setup.deb.sh' | bash \
    && apt-get update && apt-get install -y --no-install-recommends symfony-cli git && apt-get clean

COPY ./docker/backend/conf/php.ini "$PHP_INI_DIR/php.ini"
COPY ./docker/backend/conf/php.cli.ini "$PHP_INI_DIR/php-cli.ini"
COPY ./docker/backend/conf/vhost.conf /etc/apache2/sites-available/000-vel.conf
RUN a2ensite 000-vel.conf

