# Stage 1: Composer
FROM composer:2.0 as composer

ARG TESTING=false
ENV TESTING=$TESTING

WORKDIR /usr/local/src/

COPY composer.lock /usr/local/src/
COPY composer.json /usr/local/src/

RUN composer install \
    --ignore-platform-reqs \
    --optimize-autoloader \
    --no-plugins  \
    --no-scripts \
    --prefer-dist

# Stage 2: PHP
FROM php:8.0-cli-alpine

WORKDIR /usr/local/src/

COPY --from=composer /usr/local/src/vendor /usr/local/src/vendor
COPY . /usr/local/src/

# Install PHPUnit
RUN wget -O phpunit https://phar.phpunit.de/phpunit-9.phar && \
    chmod +x phpunit && \
    mv phpunit /usr/local/bin/phpunit

CMD [ "tail", "-f", "/dev/null" ]
