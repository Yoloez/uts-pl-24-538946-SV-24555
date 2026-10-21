# Image dasar menggunakan versi eksplisit FrankenPHP (bukan latest)
FROM dunglas/frankenphp:1.4-php8.2-alpine

# Set environment FrankenPHP
ENV SERVER_NAME=":8000"

# Pasang ekstensi PHP yang dibutuhkan Laravel melalui install-php-extensions bawaan
RUN install-php-extensions \
    pdo_mysql \
    bcmath \
    intl \
    opcache \
    pcntl \
    zip

# Pasang Composer versi spesifik (bukan latest)
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

# Direktori kerja di /app
WORKDIR /app

# 1. Salin composer.json dan composer.lock terlebih dahulu untuk memanfaatkan cache layer Docker
COPY composer.json composer.lock ./

# 2. Pasang dependensi Composer (mode produksi)
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --optimize-autoloader --no-scripts

# 3. Salin seluruh kode aplikasi
COPY . .

# 4. Dump autoload Composer yang telah dioptimasi
RUN composer dump-autoload --optimize

# 5. Atur hak akses direktori storage dan cache
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache \
    && chmod -R 775 /app/storage /app/bootstrap/cache

# 6. Salin konfigurasi Caddyfile dan entrypoint script
COPY Caddyfile /etc/caddy/Caddyfile
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 8000

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
