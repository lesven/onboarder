FROM php:8.2-fpm-alpine

# PHP Erweiterungen und Tools installieren
RUN apk add --no-cache \
    curl \
    freetype-dev \
    git \
    libjpeg-turbo-dev \
    libpng-dev \
    libzip-dev \
    netcat-openbsd \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        gd \
        opcache \
        pdo_mysql \
        zip

# Composer installieren
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Working directory setzen
WORKDIR /var/www/html

# Benutzer für www-data erstellen
RUN addgroup -g 1000 www && \
    adduser -u 1000 -G www -s /bin/sh -D www

# Port freigeben
EXPOSE 8000

# Startup-Skript
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

CMD ["/start.sh"]
