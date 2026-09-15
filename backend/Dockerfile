FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libpng-dev curl gnupg \
    && docker-php-ext-install pdo_mysql zip pcntl \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

RUN composer install --no-interaction --optimize-autoloader || true

# Playwright + браузер Chromium со всеми системными зависимостями.
RUN npm install playwright@^1.47 \
    && npx playwright install --with-deps chromium

CMD ["php-fpm"]
