---
title: Installation
excerpt: Set up Clonio in your environment — from a local laptop to a production server on Laravel Cloud, Forge, or AWS.
---

# Installation

Clonio is a self-hosted Laravel application. It runs wherever PHP 8.4 and a relational database are available. This page covers every supported environment from local development to managed cloud hosting.

---

## Environment Variables Reference

Every environment needs the following variables configured. Sensible defaults are provided in `.env.example`.

| Variable | Example | Description |
|---|---|---|
| `APP_NAME` | `Clonio` | Application name shown in UI and emails |
| `APP_ENV` | `production` | `local` for dev, `production` for live |
| `APP_KEY` | `base64:…` | 32-byte encryption key — generate with `php artisan key:generate` |
| `APP_DEBUG` | `false` | Never `true` in production |
| `APP_MODE` | `application` | Always `application` for self-hosted installs |
| `APP_URL` | `https://clonio.example.com` | Public URL of your Clonio instance |
| `AUDIT_SECRET` | `s3cr3t-rand0m-str1ng` | Shared secret for public audit log URLs |
| `DB_CONNECTION` | `mysql` | `mysql`, `pgsql`, or `sqlite` |
| `DB_HOST` | `127.0.0.1` | Database host |
| `DB_PORT` | `3306` | Database port |
| `DB_DATABASE` | `clonio` | Database name |
| `DB_USERNAME` | `clonio` | Database user |
| `DB_PASSWORD` | `secret` | Database password |
| `SESSION_DRIVER` | `database` | `database` or `redis` |
| `QUEUE_CONNECTION` | `database` | `database` or `redis` — use `redis` for high volume |
| `CACHE_STORE` | `database` | `database` or `redis` |
| `MAIL_MAILER` | `smtp` | Mail transport — `smtp`, `ses`, `postmark`, `log` |
| `MAIL_HOST` | `smtp.example.com` | SMTP host |
| `MAIL_PORT` | `587` | SMTP port |
| `MAIL_USERNAME` | `apikey` | SMTP username |
| `MAIL_PASSWORD` | `…` | SMTP password |
| `MAIL_FROM_ADDRESS` | `clonio@example.com` | Sender address for system emails |
| `MAIL_FROM_NAME` | `Clonio` | Sender name for system emails |

---

## Local Development

### Laravel Herd

[Laravel Herd](https://herd.laravel.com) is the fastest way to get Clonio running locally on macOS or Windows. No Docker required.

**Requirements:** Herd with PHP 8.4 and a local MySQL or PostgreSQL instance.

```bash
git clone <repository-url> ~/Herd/clonio
cd ~/Herd/clonio
composer install
cp .env.example .env
php artisan key:generate
```

Edit `.env`:

```env
APP_URL=http://clonio.test
APP_MODE=application
APP_DEBUG=true

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=clonio
DB_USERNAME=root
DB_PASSWORD=

AUDIT_SECRET=change-me-to-a-random-string

MAIL_MAILER=log
```

```bash
php artisan migrate
npm install && npm run build
```

Herd automatically serves any directory under `~/Herd/` at `http://{dirname}.test`. Open [http://clonio.test](http://clonio.test) in your browser.

To process queued jobs during development:

```bash
php artisan queue:work
```

---

### Laravel Sail

Laravel Sail provides a Docker-based environment with no global PHP installation required.

**Requirements:** Docker Desktop.

```bash
git clone <repository-url> clonio
cd clonio

docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php84-composer:latest \
    composer install --ignore-platform-reqs
```

Copy and configure the environment:

```bash
cp .env.example .env
```

Edit `.env` for the Sail services:

```env
APP_URL=http://localhost
APP_MODE=application
APP_DEBUG=true

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=clonio
DB_USERNAME=sail
DB_PASSWORD=password

SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
CACHE_STORE=redis

REDIS_HOST=redis
REDIS_PORT=6379

AUDIT_SECRET=change-me-to-a-random-string

MAIL_MAILER=log
```

Start the environment:

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
```

Open [http://localhost](http://localhost). The queue worker runs automatically inside Sail's supervisor.

---

### Docker Compose (standalone)

Use this approach if you want a self-contained Docker setup without the Sail development tooling — suitable for local testing or lightweight server deployments.

Create a `docker-compose.yml`:

```yaml
services:
  app:
    image: php:8.4-fpm
    working_dir: /var/www/html
    volumes:
      - .:/var/www/html
    environment:
      APP_NAME: Clonio
      APP_ENV: production
      APP_KEY: "${APP_KEY}"
      APP_DEBUG: "false"
      APP_MODE: application
      APP_URL: "http://localhost"
      AUDIT_SECRET: "${AUDIT_SECRET}"
      DB_CONNECTION: mysql
      DB_HOST: db
      DB_PORT: 3306
      DB_DATABASE: clonio
      DB_USERNAME: clonio
      DB_PASSWORD: "${DB_PASSWORD}"
      SESSION_DRIVER: database
      QUEUE_CONNECTION: database
      CACHE_STORE: database
      MAIL_MAILER: log
    depends_on:
      - db
    networks:
      - clonio

  worker:
    image: php:8.4-fpm
    working_dir: /var/www/html
    volumes:
      - .:/var/www/html
    command: php artisan queue:work --sleep=3 --tries=3
    environment:
      APP_KEY: "${APP_KEY}"
      APP_MODE: application
      DB_CONNECTION: mysql
      DB_HOST: db
      DB_DATABASE: clonio
      DB_USERNAME: clonio
      DB_PASSWORD: "${DB_PASSWORD}"
      QUEUE_CONNECTION: database
    depends_on:
      - db
    networks:
      - clonio

  db:
    image: mysql:8.0
    environment:
      MYSQL_DATABASE: clonio
      MYSQL_USER: clonio
      MYSQL_PASSWORD: "${DB_PASSWORD}"
      MYSQL_ROOT_PASSWORD: "${DB_ROOT_PASSWORD}"
    volumes:
      - db-data:/var/lib/mysql
    networks:
      - clonio

networks:
  clonio:

volumes:
  db-data:
```

Create a `.env` file alongside `docker-compose.yml`:

```env
APP_KEY=base64:generate-with-artisan-key-generate
AUDIT_SECRET=a-random-secret-string
DB_PASSWORD=clonio_secret
DB_ROOT_PASSWORD=root_secret
```

Start everything:

```bash
docker compose up -d
docker compose exec app php artisan key:generate --force
docker compose exec app php artisan migrate --force
docker compose exec app npm install && npm run build
```

---

## Production Deployments

### Dockerfile

If you build and ship a container image, here is a minimal production `Dockerfile`:

```dockerfile
FROM php:8.4-fpm AS base

RUN apt-get update && apt-get install -y \
    curl git unzip libpq-dev libzip-dev libpng-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql zip gd \
    && apt-get clean

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

FROM base AS build

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction

COPY . .
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

COPY package.json package-lock.json ./
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && npm ci && npm run build

FROM base AS production

COPY --from=build /var/www/html /var/www/html

EXPOSE 9000
CMD ["php-fpm"]
```

Pass all required environment variables at runtime via your orchestrator's secret store (ECS task definition, Kubernetes secrets, Docker secrets, etc.):

```env
APP_ENV=production
APP_KEY=base64:…
APP_DEBUG=false
APP_MODE=application
APP_URL=https://clonio.example.com
AUDIT_SECRET=…
DB_CONNECTION=mysql
DB_HOST=…
DB_DATABASE=clonio
DB_USERNAME=clonio
DB_PASSWORD=…
QUEUE_CONNECTION=database
SESSION_DRIVER=database
CACHE_STORE=database
MAIL_MAILER=smtp
MAIL_HOST=…
MAIL_PORT=587
MAIL_USERNAME=…
MAIL_PASSWORD=…
MAIL_FROM_ADDRESS=clonio@example.com
MAIL_FROM_NAME=Clonio
```

Run migrations as part of your deployment pipeline:

```bash
php artisan migrate --force
```

Run a separate container for the queue worker using the same image:

```bash
php artisan queue:work --sleep=3 --tries=3 --max-time=3600
```

---

### Laravel Forge

[Laravel Forge](https://forge.laravel.com) automates server provisioning and deployment on AWS, DigitalOcean, Linode, or any VPS.

1. **Provision a server** — PHP 8.4, Nginx, MySQL or PostgreSQL.
2. **Create a site** — point the domain to your Clonio instance URL.
3. **Connect your repository** — Forge will clone and deploy on push.
4. **Set environment variables** in Forge → Site → Environment:

```env
APP_NAME=Clonio
APP_ENV=production
APP_KEY=base64:…
APP_DEBUG=false
APP_MODE=application
APP_URL=https://clonio.example.com

AUDIT_SECRET=your-random-secret

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=clonio
DB_USERNAME=clonio
DB_PASSWORD=your-db-password

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database

MAIL_MAILER=smtp
MAIL_HOST=smtp.postmarkapp.com
MAIL_PORT=587
MAIL_USERNAME=your-api-token
MAIL_PASSWORD=your-api-token
MAIL_FROM_ADDRESS=clonio@example.com
MAIL_FROM_NAME=Clonio
```

5. **Add a deployment script** in Forge → Site → Deployment Script:

```bash
cd /home/forge/clonio.example.com
git pull origin main
composer install --no-dev --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
npm ci && npm run build
( flock -w 10 9 || exit 1; echo 'Restarting FPM...'; sudo -S service php8.4-fpm reload ) 9>/tmp/fpmlock
```

6. **Configure a queue worker** in Forge → Server → Daemons:

```
Command: php /home/forge/clonio.example.com/artisan queue:work --sleep=3 --tries=3 --max-time=3600
User: forge
```

---

### AWS

Two common patterns on AWS:

#### EC2 (traditional VPS)

Provision an EC2 instance running Ubuntu 24.04, then follow the same steps as the Forge deployment above — install PHP 8.4 + Nginx + MySQL (or use RDS), clone the repo, configure `.env`, run `php artisan migrate`, and set up Supervisor for the queue worker.

For the database, use **Amazon RDS** (MySQL or PostgreSQL) for managed backups and failover:

```env
DB_CONNECTION=mysql
DB_HOST=your-rds-endpoint.rds.amazonaws.com
DB_PORT=3306
DB_DATABASE=clonio
DB_USERNAME=clonio
DB_PASSWORD=…
```

For mail, use **Amazon SES**:

```env
MAIL_MAILER=ses
AWS_ACCESS_KEY_ID=…
AWS_SECRET_ACCESS_KEY=…
AWS_DEFAULT_REGION=eu-west-1
MAIL_FROM_ADDRESS=clonio@example.com
MAIL_FROM_NAME=Clonio
```

#### ECS / Fargate (container-based)

Build and push your Docker image to Amazon ECR:

```bash
aws ecr get-login-password --region eu-west-1 | docker login --username AWS --password-stdin <account>.dkr.ecr.eu-west-1.amazonaws.com
docker build -t clonio .
docker tag clonio:latest <account>.dkr.ecr.eu-west-1.amazonaws.com/clonio:latest
docker push <account>.dkr.ecr.eu-west-1.amazonaws.com/clonio:latest
```

Create two ECS task definitions using the same image:
- **Web task** — runs `php-fpm` behind an Application Load Balancer
- **Worker task** — runs `php artisan queue:work`

Store all secrets in **AWS Secrets Manager** or **Parameter Store** and inject them as environment variables into the task definitions.

Run migrations as a one-off ECS task before deploying the new web task revision:

```bash
aws ecs run-task \
  --cluster clonio \
  --task-definition clonio-migrate \
  --overrides '{"containerOverrides":[{"name":"app","command":["php","artisan","migrate","--force"]}]}'
```

---

### Laravel Cloud

[Laravel Cloud](https://cloud.laravel.com) is a fully managed platform-as-a-service for Laravel applications. It handles provisioning, SSL, scaling, and deployments automatically.

1. **Import your repository** from GitHub in the Laravel Cloud dashboard.
2. **Create a new environment** (e.g., `production`).
3. **Add a managed database** — MySQL or PostgreSQL — from the Cloud dashboard. The connection variables (`DB_HOST`, `DB_DATABASE`, etc.) are injected automatically.
4. **Set environment variables** in Cloud → Environment → Variables:

```env
APP_NAME=Clonio
APP_ENV=production
APP_KEY=base64:…
APP_DEBUG=false
APP_MODE=application
APP_URL=https://your-project.laravel.cloud

AUDIT_SECRET=your-random-secret

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database

MAIL_MAILER=smtp
MAIL_HOST=smtp.postmarkapp.com
MAIL_PORT=587
MAIL_USERNAME=your-api-token
MAIL_PASSWORD=your-api-token
MAIL_FROM_ADDRESS=clonio@example.com
MAIL_FROM_NAME=Clonio
```

> `APP_KEY` can be generated locally with `php artisan key:generate --show` and pasted in.

5. **Add a worker** in Cloud → Workers:
   - Command: `php artisan queue:work --sleep=3 --tries=3 --max-time=3600`

6. **Deploy** — Cloud runs `composer install`, `php artisan migrate --force`, and `npm run build` automatically on each push.

---

## Post-Installation

### First Login

After the application is running and migrations are complete, open it in your browser. Register your first user account — this becomes the initial administrator.

### Required Background Processes

For cloning runs to execute, keep these two processes running at all times:

| Process | Command | Purpose |
|---|---|---|
| Queue worker | `php artisan queue:work` | Executes cloning jobs |
| Scheduler | `php artisan schedule:run` | Triggers scheduled runs |

In Forge and Laravel Cloud, both are configurable via the dashboard. In Docker environments, run each as a separate container or supervisor process.

### Next Steps

Proceed to [Managing Connections](../1-connections/01-managing-connections.md) to add your first database connections, then [Creating a Cloning](../2-clonings/01-creating-a-cloning.md) to configure your first cloning run.
