---
title: Installation
introduction: Step-by-step guide to installing and configuring Clonio in your Laravel application.
---

# Installation

This guide will walk you through installing Clonio and getting your first database clone running.

## Requirements

Before installing Clonio, make sure your environment meets the following requirements:

- PHP 8.4 or higher
- Laravel 12.x
- One of the supported database drivers: MySQL, PostgreSQL, or SQLite
- Composer

## Install via Composer

Install Clonio using Composer:

```bash
composer require clonio-dev/clonio
```

## Configuration

After installation, publish the configuration file:

```bash
php artisan vendor:publish --tag=clonio-config
```

This will create a `config/clonio.php` file where you can configure your source and target database connections.

### Database Connections

Clonio requires two database connections: a **source** (where data comes from) and a **target** (where data goes). Configure these in your `.env` file:

```env
CLONIO_SOURCE_CONNECTION=mysql
CLONIO_TARGET_CONNECTION=mysql_staging
```

Then add the target connection in your `config/database.php`:

```php
'mysql_staging' => [
    'driver' => 'mysql',
    'host' => env('DB_STAGING_HOST', '127.0.0.1'),
    'port' => env('DB_STAGING_PORT', '3306'),
    'database' => env('DB_STAGING_DATABASE', 'staging'),
    'username' => env('DB_STAGING_USERNAME', 'root'),
    'password' => env('DB_STAGING_PASSWORD', ''),
],
```

## Running Migrations

Run the migrations to create the necessary tables:

```bash
php artisan migrate
```

## Verify Installation

Verify that Clonio is installed correctly by running:

```bash
php artisan clonio:status
```

You should see output confirming the source and target connections are configured.

## Next Steps

With Clonio installed, head to [Configuration](/docs/1-essentials/01-configuration) to learn how to set up your first cloning profile.
