# Hosting KathaAI (Laravel + Inertia/React)

This app is a **Laravel 12** backend with **Inertia.js + React** frontend, **Vite** for assets, and optional **Stripe**, **OAuth**, and **queues**. Below are practical ways to host it.

---

## Server requirements

- **PHP** 8.2+ with extensions: `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `json`, `mbstring`, `openssl`, `pdo`, `tokenizer`, `xml`, `gd` or `imagick`, `redis` (optional, for cache/queues)
- **Composer** 2
- **Node.js** 18+ (only for building assets; not required at runtime if you build before deploy)
- **Database**: MySQL 8+, PostgreSQL 15+, or SQLite
- **Web server**: Nginx or Apache (document root = `public/`)
- **Optional**: Redis (for cache/sessions/queues)

---

## Pre-deployment checklist

1. **Environment**
   - Copy `.env.example` to `.env` on the server.
   - Set `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://your-domain.com`.
   - Generate key: `php artisan key:generate`.

2. **Build frontend**
   - Run `npm ci && npm run build` (commit `public/build` or run this step in CI/CD or on the server).

3. **Database**
   - Configure `DB_*` in `.env` (e.g. MySQL/Postgres or keep SQLite for small setups).
   - Run `php artisan migrate --force`.

4. **Optional**
   - **Queues**: If using `QUEUE_CONNECTION=database`, run a worker: `php artisan queue:work --tries=3`.
   - **Stripe**: Set `STRIPE_KEY`, `STRIPE_SECRET`, and `STRIPE_WEBHOOK_SECRET`; point webhook to `https://your-domain.com/stripe/webhook` (or your Cashier route).
   - **OAuth**: Set Google/Apple env vars and callback URLs to your production domain.
   - **Scheduler**: Add cron: `* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1` if you use scheduled tasks.

---

## Option 1: Docker (recommended for portability)

Use the included Docker setup to run the app anywhere (VPS, Railway, Render, etc.).

```bash
# Copy env and set APP_URL, DB_*, etc.
cp .env.example .env

# Build and run (see docker-compose.yml for services)
docker compose up -d --build

# First-time setup (run once)
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --force
docker compose exec app php artisan storage:link
```

For **production**, remove the app volume mount in `docker-compose.yml` (the `.:/var/www/html` line) so the container uses the built image instead of the host directory.

- **Web**: Nginx in front of PHP-FPM, document root `public/`.
- **Queue**: Optional `queue` service runs `php artisan queue:work`.
- **Database**: MySQL 8 (or switch to `postgres` in `docker-compose.yml`).
- **Redis**: Optional; set `CACHE_STORE=redis`, `SESSION_DRIVER=redis`, `QUEUE_CONNECTION=redis` to use it.

See `Dockerfile` and `docker-compose.yml` in the repo.

---

## Option 2: Traditional VPS (Nginx + PHP-FPM)

1. **Server**: Ubuntu 22.04+ (or similar). Install Nginx, PHP 8.2+, Composer, MySQL/Postgres, and optionally Redis.

2. **Document root**: Point the site’s root to `/path/to/kathaai/public`.

3. **Nginx** (minimal):

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/kathaai/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 500 502 503 504 /50x.html;
    location = /50x.html { root /usr/share/nginx/html; }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }
}
```

4. **Deploy steps**: Clone repo, `composer install --no-dev`, `npm ci && npm run build`, run migrations, set up cron and queue worker as in the checklist.

---

## Option 3: Laravel Forge / Ploi

- **Forge**: [laravel.com/forge](https://laravel.com/forge) – connect a VPS (DigitalOcean, AWS, etc.), create a site, point root to `public/`, set PHP version and env. Use “Queue” and “Scheduler” for worker and cron.
- **Ploi**: [ploi.io](https://ploi.io) – similar: add server, create site, set document root to `public`, configure env and deploy script.

Deploy script (example):

```bash
cd /home/forge/your-site
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Option 4: Platform-as-a-Service (PaaS)

- **Laravel Vapor** (AWS): Serverless; use Vapor’s Laravel integration and set env, build command, and migrations in the dashboard.
- **Railway / Render / Fly.io**: Use the **Docker** path (Option 1): add a Dockerfile and point the service to the port Nginx or PHP listens on (e.g. 80 or 8080). Set env vars in the PaaS dashboard and run migrations in a release command.

---

## Security and performance in production

- Use **HTTPS** (e.g. Let’s Encrypt with Nginx or your host’s proxy).
- Keep `APP_DEBUG=false` and set `LOG_LEVEL=warning` or `error`.
- Restrict `storage/` and `bootstrap/cache/` to be writable only by the app; do not serve them directly.
- Run `php artisan config:cache`, `route:cache`, `view:cache` after deploy.
- If using file sessions, ensure `storage/framework/sessions` is writable; for multi-server, use database or Redis for sessions and cache.

---

## Quick reference: env for production

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database (MySQL example)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=kathaai
DB_USERNAME=...
DB_PASSWORD=...

# Optional: Redis for cache/session/queue
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

After changing config, run: `php artisan config:cache`.
