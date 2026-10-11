# syntax=docker/dockerfile:1

# ---------------------------------------------------------------------------
# Runtime base: PHP 8.4 + Apache (composer.lock requires PHP >= 8.4.1).
# The official image already ships every extension the lock needs except pdo_mysql.
# ---------------------------------------------------------------------------
FROM php:8.4-apache AS base

RUN docker-php-ext-install pdo_mysql \
    && a2enmod rewrite \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && printf 'ServerName localhost\nServerTokens Prod\nServerSignature Off\n' > /etc/apache2/conf-enabled/zz-security.conf \
    && sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

COPY docker/php.ini "$PHP_INI_DIR/conf.d/zz-zi-terpadu.ini"

WORKDIR /var/www/html

# ---------------------------------------------------------------------------
# PHP dependencies (production only). Platform requirements are checked later
# against the real runtime PHP, not the composer image's PHP.
# ---------------------------------------------------------------------------
FROM composer:2 AS composer-deps

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --no-scripts --no-autoloader --prefer-dist --ignore-platform-reqs

# ---------------------------------------------------------------------------
# Application code + optimized autoloader (also runs package:discover).
# ---------------------------------------------------------------------------
FROM base AS backend

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY --from=composer-deps /app/vendor ./vendor
COPY . .
RUN composer check-platform-reqs --no-dev \
    && composer dump-autoload --no-dev --optimize \
    && rm /usr/local/bin/composer

# ---------------------------------------------------------------------------
# Frontend build. Vite's Wayfinder plugin runs `php artisan wayfinder:generate`,
# so this stage needs PHP (base) plus Node.
# ---------------------------------------------------------------------------
FROM base AS assets

COPY --from=node:22-trixie-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:22-trixie-slim /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s ../lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm

COPY package.json package-lock.json .npmrc ./
RUN npm ci
COPY --from=backend /var/www/html ./
RUN npm run build

# ---------------------------------------------------------------------------
# Final image: code, vendor and built assets only (no Node, no Composer).
# ---------------------------------------------------------------------------
FROM base AS app

COPY --from=backend --chown=www-data:www-data /var/www/html ./
COPY --from=assets --chown=www-data:www-data /var/www/html/public/build ./public/build
COPY --chmod=755 docker/entrypoint.sh /usr/local/bin/zi-entrypoint

ENTRYPOINT ["zi-entrypoint"]
CMD ["apache2-foreground"]
