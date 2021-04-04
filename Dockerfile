FROM php:8.0-fpm-alpine3.13

RUN apk add --no-cache libzip-dev openssl-dev gcc libc-dev autoconf make && \
    docker-php-ext-install -j$(nproc) zip && \
    pecl install xdebug-3.0.0 && \
    docker-php-ext-enable xdebug

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer

ENV PATH /var/app/bin:/var/app/vendor/bin:$PATH

WORKDIR /var/app
