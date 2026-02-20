#!/bin/sh
set -e
# Persist .env in a volume so key:generate and config survive restarts
if [ ! -f /var/www/html/env/.env ]; then
  cp /var/www/html/.env.example /var/www/html/env/.env 2>/dev/null || true
fi
ln -sf /var/www/html/env/.env /var/www/html/.env 2>/dev/null || true
# So nginx can serve static files: copy public dir into shared volume
cp -r /var/www/html/public/. /public_assets/ 2>/dev/null || true
exec php-fpm
