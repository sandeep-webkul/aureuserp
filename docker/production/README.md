# AureusERP — Production Docker Image

A single-container production image for [AureusERP](https://github.com/aureuserp/aureuserp):
**Ubuntu 24.04 + Nginx + PHP 8.4 FPM + MySQL 8.0 + Supervisor**.

AureusERP is **fully installed at build time** — migrations, seeders, roles &
permissions, and an admin user. One `docker run` gives you a ready-to-use ERP.

> This is the production image. For local development use Laravel Sail via the
> `docker-compose.yml` at the repository root.

## What's inside

| File | Purpose |
|---|---|
| `Dockerfile` | Single-stage image build (clones the app, installs everything) |
| `.dockerignore` | Build-context exclusions |
| `build-install.sh` | Runs during the build — installs AureusERP into a baked MySQL data dir |
| `entrypoint.sh` | Runtime: applies env overrides, waits for an external DB, refreshes caches |
| `supervisord.conf` | Process manager: mysql · php-fpm · nginx · queue worker · scheduler |
| `nginx.conf` | Virtual host, static caching, security headers, `/health` |
| `php.ini` | OPcache, resource limits, `max_input_vars` |
| `php-fpm.conf` | PHP-FPM pool tuning |
| `mysql-init.sql` | Creates the internal `aureus` database and user |

The application is installed to `/var/www/aureuserp` inside the container.

## How it works

The build context is **this directory** (`docker/production/`). The Dockerfile
fetches the application with `git clone` (configurable via build args), so the
image always builds **committed** code — uncommitted local changes are not
included. This mirrors the upstream reference layout.

## Quick start

```bash
# Build — the build context is docker/production/
docker build -t aureuserp:latest docker/production

# Run with named volumes so data persists
docker run -d --name aureuserp -p 80:80 \
  -v aureus-mysql:/var/lib/mysql \
  -v aureus-storage:/var/www/aureuserp/storage \
  aureuserp:latest
```

- Application: <http://localhost>
- Admin panel: <http://localhost/admin>
- Default admin login: `admin@example.com` / `password`

The build takes several minutes (it clones the repo, runs `composer install`,
compiles assets, and installs the ERP). The container itself boots in seconds.

## Build arguments

| Argument | Default | Description |
|---|---|---|
| `APP_REF` | `master` | Branch or tag to clone |
| `REPO_URL` | `https://github.com/aureuserp/aureuserp.git` | Repository to clone |
| `PHP_VERSION` | `8.4` | PHP version |
| `NODE_VERSION` | `22` | Node.js version (used only to compile assets) |
| `ADMIN_NAME` | `Administrator` | Admin account name baked at install |
| `ADMIN_EMAIL` | `admin@example.com` | Admin account email baked at install |
| `ADMIN_PASSWORD` | `password` | Admin account password baked at install |

Example:

```bash
docker build -t aureuserp:latest \
  --build-arg APP_REF=v1.0.0 \
  --build-arg ADMIN_EMAIL=ops@example.com \
  --build-arg ADMIN_PASSWORD='a-strong-password' \
  docker/production
```

## Runtime environment variables

| Variable | Default | Description |
|---|---|---|
| `APP_NAME` | _from .env_ | Application name |
| `APP_URL` | `http://localhost` | Public base URL |
| `APP_KEY` | _baked_ | Encryption key — override to pin a stable key |
| `APP_TIMEZONE` | `UTC` | Application timezone |
| `APP_LOCALE` | `en` | Default locale |
| `APP_CURRENCY` | `USD` | Default currency |
| `DB_HOST` | `127.0.0.1` | Database host — see *Database modes* |
| `DB_PORT` | `3306` | Database port |
| `DB_DATABASE` | `aureus` | Database name |
| `DB_USERNAME` | `aureus` | Database user |
| `DB_PASSWORD` | `aureus` | Database password |

```bash
docker run -d --name aureuserp -p 80:80 \
  -e APP_URL=https://erp.example.com \
  -e APP_NAME="My Company ERP" \
  -v aureus-mysql:/var/lib/mysql \
  -v aureus-storage:/var/www/aureuserp/storage \
  aureuserp:latest
```

## Database modes

- **Internal MySQL (default)** — when `DB_HOST` is unset, `127.0.0.1`, or
  `localhost`. MySQL runs inside the container under Supervisor; the
  pre-installed data directory is baked into the image.
- **External MySQL** — set `DB_HOST` to any other address. The internal MySQL
  stays off and the entrypoint waits for the external server. An external
  database is **not pre-installed** — run the installer against it once
  (`APP_ENV` is overridden so the migrations are not blocked by the production
  guard):

  ```bash
  docker exec -e APP_ENV=local aureuserp \
    php artisan erp:install --force --no-interaction \
    --admin-name=Administrator \
    --admin-email=admin@example.com \
    --admin-password=password
  ```

## Persistence

The image declares **no** `VOLUME` directives — persistence is opt-in. Use two
**named** volumes (named volumes receive a copy of the image's baked content;
bind mounts do not, and an empty bind mount would shadow the installed data):

- `aureus-mysql` → `/var/lib/mysql` — the database
- `aureus-storage` → `/var/www/aureuserp/storage` — uploads, logs, app state

## Upgrading

The image is immutable and `opcache.validate_timestamps=0`, so code changes
require **rebuilding the image**:

```bash
docker build --no-cache -t aureuserp:latest docker/production
```

When reusing the `aureus-mysql` volume, apply any new migrations:

```bash
docker exec aureuserp php artisan migrate --force
```

## Health check

`GET /health` returns `200 OK`; Docker's built-in `HEALTHCHECK` polls it.

## Troubleshooting

- **Build fails at `git clone`** — the repo/branch must be reachable; for a
  private repository pass an authenticated `REPO_URL` build arg.
- **`mysqld` fails to initialise during the build** — some hosts enforce
  AppArmor on `mysqld`; build on a host without that restriction.
- **Data lost after recreating the container** — you used a bind mount or no
  volume; always use the named volumes `aureus-mysql` and `aureus-storage`.
- **Queue worker restarts briefly on cold start** — expected for a few seconds
  until MySQL accepts connections; Supervisor retries automatically.

## Notes

- Image size is roughly 1.3–1.6 GB (bundled MySQL, PHP extensions, the
  application, and dependencies).
- `APP_DEBUG` is `false` and `display_errors` is off — keep it that way in
  production. Change the default admin password after the first login.
