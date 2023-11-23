FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    curl \
    wget \
    git \
    unzip \
    mariadb-client \
    pv \
    sudo

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN chmod +x /usr/local/bin/install-php-extensions && install-php-extensions \
    hash \
    bcmath \
    exif \
    fileinfo \
    gd \
    iconv \
    intl \
    mbstring \
    opcache \
    pcntl \
    pdo \
    pdo_mysql \
    simplexml \
    xml \
    zip \
    sodium \
    redis

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Yarn
RUN apt-get update && apt-get install -y gnupg
RUN curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | apt-key add -
RUN echo "deb https://dl.yarnpkg.com/debian/ stable main" | tee /etc/apt/sources.list.d/yarn.list
RUN apt-get update && sudo apt-get install -y yarn

# Opcache
COPY ./opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Permissions
RUN usermod -u 1000 www-data

# Create partage/composer dir
RUN mkdir -p /var/www/partage /var/www/.composer /var/www/.local
RUN chown -R www-data:www-data /var/www

# Workdir
WORKDIR /var/www/html
