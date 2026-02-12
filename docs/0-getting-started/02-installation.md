---
title: Installation
introduction: Set up Clonio using Docker and Laravel Sail, configure your environment, and log in for the first time.
---

# Installation

Clonio is a self-hosted Laravel application. The recommended way to run it is with Docker via Laravel Sail.

## Requirements

- Docker Desktop (or Docker Engine + Docker Compose on Linux)
- Git

## Quick Start

Clone the repository and start the application:

```bash
git clone <repository-url> clonio
cd clonio
```

Copy the example environment file and generate an application key:

```bash
cp .env.example .env
```

Install dependencies and start the Docker environment:

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
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
```

## Environment Configuration

The `.env` file controls the application's behavior. Key settings:

```env
APP_NAME=Clonio
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=clonio
DB_USERNAME=sail
DB_PASSWORD=password
```

The application database stores Clonio's own data (users, clonings, connections, run logs). The source and target databases for cloning are configured separately through the web interface.

### Queue Worker

Clonio uses queued jobs for cloning execution. Make sure the queue worker is running:

```bash
./vendor/bin/sail artisan queue:work
```

For production, configure a process manager like Supervisor to keep the worker running.

### Scheduler

If you plan to use scheduled clonings (cron-based triggers), make sure Laravel's scheduler is running:

```bash
./vendor/bin/sail artisan schedule:work
```

## First Login

After installation, create your user account by visiting the application in your browser. The registration page is available on first access.

Once registered, you will see the Dashboard showing an overview of active runs, completed runs, failed runs, and recent activity.

## Next Steps

With Clonio running, proceed to [Managing Connections](/docs/1-connections/01-managing-connections) to add your first database connections.
