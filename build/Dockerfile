FROM php:5-alpine

MAINTAINER James Zhu <fatindeed@gmail.com>

# Add packages
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS tzdata sqlite-dev \
 && apk add --no-cache git \
# Setup timezone
 && cp /usr/share/zoneinfo/Asia/Shanghai /etc/localtime \
 && echo "Asia/Shanghai" > /etc/timezone \
# Install PHP extensions
 && pecl install dbase-5.1.0 \
 && pecl bundle -d /usr/src/php/ext yaf-2.3.5 \
 && echo yaf >> /usr/src/php-available-exts \
 && docker-php-ext-install pdo_sqlite yaf \
 && docker-php-ext-enable dbase \
# Clean packages
 && pecl clear-cache \
 && rm -rf /tmp/pear \
 && apk del .build-deps \
 && rm -rf /var/cache/apk/*

# Add cron job
COPY crontab /var/spool/cron/crontabs/root

WORKDIR /home/www-data

CMD ["crond", "-f"]