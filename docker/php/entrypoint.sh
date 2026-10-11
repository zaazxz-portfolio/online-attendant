#!/bin/sh
set -e

# Pastikan folder uploads ada dan writable oleh www-data (UID 33)
mkdir -p /var/www/html/public/uploads /var/www/html/public/assets/uploads
chown -R www-data:www-data /var/www/html/public/uploads /var/www/html/public/assets/uploads
chmod -R 777 /var/www/html/public/uploads /var/www/html/public/assets/uploads

# Jalankan perintah utama container (php-fpm)
exec "$@"
