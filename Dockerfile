FROM phpswoole/swoole:4.7.1-php8.0
#FROM phpswoole/swoole:4.7.1-php8.0-dev


RUN set -ex \
    && apt-get update \
    && apt-get install -y $PHPIZE_DEPS \
    inotify-tools \
    && pecl update-channels \
    && pecl install redis-stable \
    && docker-php-ext-enable redis \
#    && install-swoole-ext.sh async 4.4.16 \
#    && docker-php-ext-enable swoole_async \
    && docker-php-ext-install pcntl \
    && apt-get purge -y $PHPIZE_DEPS \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY ./rootfilesystem/ /
