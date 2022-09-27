FROM composer:2.0 as composer

ARG TESTING=false
ENV TESTING=$TESTING

WORKDIR /usr/local/src/

COPY composer.lock /usr/local/src/
COPY composer.json /usr/local/src/

RUN composer update \
    --ignore-platform-reqs \
    --optimize-autoloader \
    --no-plugins  \
    --no-scripts \
    --prefer-dist

FROM php:8.0-cli-alpine

COPY --from=composer /usr/local/src/vendor /usr/local/src/vendor
COPY . /usr/local/src/

WORKDIR /usr/local/src/

CMD [ "tail", "-f", "/dev/null" ]