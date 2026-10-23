#!/bin/sh
set -e

# 1. Salin .env.example jika .env belum ada di dalam container
if [ ! -f .env ]; then
    if [ -f .env.example ]; then
        echo "Menyalin .env.example ke .env..."
        cp .env.example .env
    fi
fi

# 2. Pastikan DB_HOST mengarah ke service 'database' di jaringan Docker Compose
if [ -f .env ]; then
    sed -i 's/DB_HOST=127.0.0.1/DB_HOST=database/g' .env 2>/dev/null || true
    sed -i 's/DB_HOST=localhost/DB_HOST=database/g' .env 2>/dev/null || true
fi

# 3. Generate APP_KEY jika belum terisi
if [ -z "$APP_KEY" ]; then
    if [ ! -f .env ] || ! grep -q "APP_KEY=base64:" .env 2>/dev/null; then
        echo "Mengenerate application key..."
        php artisan key:generate --force
    fi
fi

# 4. Tunggu database MySQL siap menerima koneksi (disesuaikan dengan backend/.env)
echo "Menunggu koneksi database MySQL..."
MAX_TRIES=30
COUNT=0
READY=0

while [ $COUNT -lt $MAX_TRIES ]; do
    COUNT=$((COUNT + 1))
    RESULT=$(php -r '
    try {
        $host = getenv("DB_HOST") ?: "database";
        if ($host === "127.0.0.1" || $host === "localhost") {
            $host = "database";
        }
        $port = getenv("DB_PORT") ?: "3306";
        $db   = getenv("DB_DATABASE") ?: "uts-kepl";
        $user = getenv("DB_USERNAME") ?: "root";
        $pass = getenv("DB_PASSWORD") !== false ? getenv("DB_PASSWORD") : "";

        $pdo = new PDO("mysql:host=$host;port=$port", $user, $pass, [
            PDO::ATTR_TIMEOUT => 2,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
        echo "SUCCESS";
    } catch (Throwable $e) {
        echo "ERROR: " . $e->getMessage();
    }
    ' 2>&1)

    if [ "$RESULT" = "SUCCESS" ]; then
        echo "Koneksi database berhasil dan database siap!"
        READY=1
        break
    else
        echo "MySQL belum siap ($COUNT/$MAX_TRIES): $RESULT"
        sleep 2
    fi
done

if [ $READY -eq 0 ]; then
    echo "Peringatan: Melebihi batas waktu tunggu MySQL, melanjutkan proses..."
fi

echo "Menjalankan migrasi database..."
php artisan migrate --force --no-interaction

echo "Menjalankan database seeder..."
php artisan db:seed --force --no-interaction || true

echo "Memulai server FrankenPHP..."
exec "$@"
