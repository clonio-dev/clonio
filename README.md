# Clonio

## Modes

Clonio runs in two modes controlled by the `APP_MODE` environment variable:

- **`application`** (default) — the self-hosted tool with authentication, database, and cloning features
- **`marketing`** — the public marketing website; requires no database, no queue, no authentication

## Local Development

### Application mode

Requires PHP 8.4, a MySQL or PostgreSQL database, and Node.js for creating the assets.

```bash
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
```

Start all services (PHP server, queue worker, Vite):

```bash
composer run dev
```

The application is available at [http://localhost:8000](http://localhost:8000).

### Marketing mode

No database required. Set `APP_MODE=marketing` in your `.env`, then:

```bash
composer run dev
```

### Laravel Herd

Place the project under `~/Herd/clonio` and it is served automatically at [http://clonio.test](http://clonio.test). Run `php artisan migrate` after copying `.env.example` to `.env`.

### Laravel Sail (Docker)

```bash
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php84-composer:latest \
    composer install --ignore-platform-reqs

./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
./vendor/bin/sail npm install && ./vendor/bin/sail npm run build
```

## Production

See the [Installation documentation](https://your-instance/docs/getting-started/installation) for deployment guides covering Laravel Cloud, Laravel Forge, AWS, and Docker Compose.
