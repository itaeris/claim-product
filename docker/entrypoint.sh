#!/bin/sh
set -eu

cd /var/www/html

if [ -f /run/secrets/backend.env ]; then
    cp /run/secrets/backend.env /var/www/html/.env
    chown www-data:www-data /var/www/html/.env
    chmod 640 /var/www/html/.env
fi

mkdir -p \
    storage/app/public \
    storage/app/private \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

php artisan package:discover --ansi --no-interaction
php artisan storage:link --force --no-interaction

echo "Menjalankan migrasi. Tabel baru ditambahkan, migrasi yang sudah jalan dilewati."
php artisan migrate --force --no-interaction
php artisan db:seed --class=Database\\Seeders\\AdminSeeder --force --no-interaction

php artisan optimize --no-interaction

chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache

exec /usr/bin/supervisord -c /etc/supervisor/supervisord.conf
