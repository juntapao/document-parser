FROM alpine:3.20

RUN apk add --no-cache \
        ca-certificates \
        curl \
        git \
        unzip \
        zip \
        tesseract-ocr \
        composer \
        php83 \
        php83-cli \
        php83-ctype \
        php83-curl \
        php83-dom \
        php83-fileinfo \
        php83-iconv \
        php83-intl \
        php83-mbstring \
        php83-openssl \
        php83-phar \
        php83-session \
        php83-simplexml \
        php83-tokenizer \
        php83-xml \
        php83-zip \
        php83-xdebug

RUN printf "%s\n" \
    "zend_extension=xdebug.so" \
    "xdebug.mode=debug,develop" \
    "xdebug.start_with_request=yes" \
    "xdebug.discover_client_host=0" \
    "xdebug.client_host=host.docker.internal" \
    "xdebug.client_port=9003" \
    "xdebug.idekey=VSCODE" \
    "xdebug.log=/tmp/xdebug.log" \
    "xdebug.log_level=10" \
    > /etc/php83/conf.d/99-xdebug.ini

RUN ln -sf /usr/bin/php83 /usr/local/bin/php

WORKDIR /app

COPY composer.json composer.lock* ./
RUN composer install --no-interaction --prefer-dist --no-scripts

COPY . .

EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
