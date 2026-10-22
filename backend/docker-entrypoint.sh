#!/bin/sh
set -e

# Generate APP_KEY if not configured
if [ -z "$APP_KEY" ]; then
    if [ ! -f .env ] || ! grep -q "APP_KEY=base64:" .env 2>/dev/null; then
        echo "Mengenerate application key..."
        php artisan key:generate --force
    fi
fi

# Tunggu database MySQL siap menerima koneksi
echo "Menunggu koneksi MySQL di ${DB_HOST:-database}:${DB_PORT:-3306}..."
MAX_TRIES=30
COUNT=0
until php -r "
try {
    \$host = getenv('DB_HOST') ?: 'database';
    \$port = getenv('DB_PORT') ?: '3306';
    \$db   = getenv('DB_DATABASE') ?: 'kost_db';
    \$user = getenv('DB_USERNAME') ?: 'kost_user';
    \$pass = getenv('DB_PASSWORD') ?: 'kost_password';
    new PDO(\"mysql:host=\$host;port=\$port;dbname=\$db\", \$user, \$pass, [PDO::ATTR_TIMEOUT => 2]);
    exit(0);
} catch (Exception \$e) {
    exit(1);
}
" 2>/dev/null; do
    COUNT=$((COUNT + 1))
    if [ $COUNT -ge $MAX_TRIES ]; then
        echo "Batas waktu tunggu koneksi MySQL tercapai!"
        break
    fi
    echo "MySQL belum siap, menunggu 2 detik... ($COUNT/$MAX_TRIES)"
    sleep 2
done

echo "Koneksi database berhasil!"
echo "Menjalankan migrasi database..."
php artisan migrate --force --no-interaction

echo "Menjalankan database seeder..."
php artisan db:seed --force --no-interaction || true

echo "Memulai server FrankenPHP..."
exec "$@"
