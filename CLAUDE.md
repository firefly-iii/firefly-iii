# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

**Uang Pondok Bibit** — Firefly III v6.6.4 personal finance manager at `https://uang.jualayamsemarang.com`.

## Stack

- **PHP 8.5** (FPM via `unix:/run/php/php8.5-fpm.sock`)
- **Laravel** 13.x (shipped with Firefly III)
- **PostgreSQL** at `10.88.0.254:5432` (Docker container, db: `firefly`, user: `firefly`)
- **DragonflyDB** (Redis-compatible) at `10.88.0.77:6379` — cache (DB 2) and sessions (DB 3)
- **Nginx** config at `/etc/nginx/conf.d/33-uang-jualayam.conf`
- **SSL** via Let's Encrypt at `/etc/letsencrypt/live/uang.jualayamsemarang.com/`
- **Layout**: v1 (Laravel Mix assets at `public/v1/`)

## Common Commands

```bash
# Cache rebuild (after config/env changes)
php artisan config:cache && php artisan route:cache && php artisan view:cache

# Clear caches (for debugging)
php artisan config:clear && php artisan route:clear && php artisan view:clear

# Database
php artisan migrate --seed --force
php artisan firefly-iii:upgrade-database
php artisan firefly-iii:set-latest-version

# Cron (runs via /etc/cron.d/firefly-iii every minute)
php artisan schedule:run

# Check logs (LOG_CHANNEL=stdout, so errors go to nginx error log)
tail -f /var/log/nginx/error.log | grep uang.jualayamsemarang

# Nginx
nginx -t && systemctl reload nginx
```

## Key Files

| File | Purpose |
|---|---|
| `.env` | All configuration (copied from `/root/envfirefly`) |
| `/etc/nginx/conf.d/33-uang-jualayam.conf` | Nginx HTTPS + FastCGI cache |
| `/etc/cron.d/firefly-iii` | Recurring task scheduler |
| `storage/logs/` | Logs (stdout channel = nginx error log) |

## External Services

- **Data Importer**: Docker container at `10.88.0.95:8080` (needs Personal Access Token from Firefly III)
- **Mail**: Brevo SMTP relay (`smtp-relay.brevo.com:465`)
- **Admin**: `r@davidmafazi.com`

## Notes

- The v1 layout uses Laravel Mix (built to `public/v1/`). The install page uses `@vite` — `public/build/manifest.json` was manually created to satisfy this.
- Passport OAuth clients exist in the DB (created via `passport:install`).
- DO NOT run `passport:install` again — it publishes duplicate migration files that conflict with existing tables.
- PHP 8.5 was installed alongside existing PHP 8.4; `php` CLI defaults to 8.5.
