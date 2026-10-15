# PHP-FPM with Chromium and fonts for Browsershot PDFs (Inter, Noto Sans Bengali).
FROM php:8.4-fpm-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libzip-dev libpng-dev libicu-dev libonig-dev libxml2-dev \
        chromium fonts-noto-core fonts-noto-ui-core fonts-beng fonts-inter \
        nodejs npm \
    && docker-php-ext-install pdo_mysql bcmath intl zip gd pcntl opcache \
    && pecl install redis && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

ENV BROWSERSHOT_CHROME_PATH=/usr/bin/chromium \
    PUPPETEER_SKIP_DOWNLOAD=true

RUN npm install -g puppeteer@23

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-dtmedix.ini

WORKDIR /var/www/html

RUN useradd -m -u 1000 app && chown -R app:app /var/www/html
USER app
