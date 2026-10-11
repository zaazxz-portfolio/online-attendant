#!/bin/sh
set -e

# Pastikan folder uploads ada dan writable oleh www-data (UID 33)
mkdir -p /var/www/html/public/uploads
chown -R www-data:www-data /var/www/html/public/uploads
chmod -R 775 /var/www/html/public/uploads

# Jalankan perintah utama container (php-fpm)
exec "$@"
