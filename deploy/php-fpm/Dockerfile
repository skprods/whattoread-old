FROM php:8.0-fpm

ARG user
ARG uid

RUN apt-get update && apt-get install -y git \
    zip unzip zlib1g-dev libpng-dev libzip-dev lsof \
    supervisor \
    && docker-php-ext-install pdo_mysql pcntl zip \
    && pecl install redis \
    && pecl install xdebug \
    && docker-php-ext-enable redis\
    && docker-php-ext-enable xdebug

RUN groupadd -g $uid www
RUN useradd -u $uid -ms /bin/bash -g www $user

ENV PATH="$PATH:/opt/www/vendor/bin"
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /opt/www
RUN chown $user ./

USER $user

COPY composer.json composer.lock ./
RUN composer install --no-scripts --no-interaction
COPY --chown=$user:www . ./
RUN composer dump-autoload

RUN chmod -R 775 /opt/www/bootstrap
RUN chmod -R 775 /opt/www/storage