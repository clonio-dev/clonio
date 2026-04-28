---
title: CLI Overview & Installation
excerpt: Install the Clonio CLI and set it up in your project — standalone binary, PHAR, or Composer package.
---

# CLI Overview & Installation

The [Clonio CLI](https://github.com/clonio-dev/clonio-cli) is a standalone command-line tool that lets you run database clonings directly from your terminal or CI pipeline — without opening the web UI. It reads a `.cloning.yaml` configuration file you commit to your repository and connects to your databases using a local `clonio.json` credentials store.

```bash
clonio cloning:run production-db.cloning.yaml --target local-dev
```

## What the CLI Does

- **Manage connections** — add, test, update, and remove database connections stored locally in `clonio.json`
- **Generate cloning configs** — inspect a live database and auto-detect PII columns with `cloning:dump`
- **Run clonings** — transfer and anonymize data with full schema synchronization
- **Verify audit logs** — cryptographically verify the integrity of signed audit reports offline
- **CI/CD integration** — `--ci` mode suppresses interactive output; exit codes signal success/failure to your pipeline

## Installation

Current version:

[![Tests](https://github.com/clonio-dev/clonio-cli/actions/workflows/tests.yml/badge.svg)](https://github.com/clonio-dev/clonio-cli/actions/workflows/tests.yml)
[![Latest Release](https://img.shields.io/github/v/release/clonio-dev/clonio-cli)](https://github.com/clonio-dev/clonio-cli/releases/latest)
[![Packagist Version](https://img.shields.io/packagist/v/clonio-dev/clonio-cli)](https://packagist.org/packages/clonio-dev/clonio-cli)


### Standalone Binary (recommended)

Download the latest prebuilt binary for your platform from the [GitHub releases page](https://github.com/clonio-dev/clonio-cli/releases/latest):

| File | Platform |
|---|---|
| `clonio-linux-x86_64` | Linux (Intel/AMD 64-bit) |
| `clonio-linux-aarch64` | Linux (ARM64) |
| `clonio-macos-aarch64` | macOS Apple Silicon |
| `clonio.phar` | Any platform with PHP 8.5 |

```bash
# macOS Apple Silicon example
curl -L https://github.com/clonio-dev/clonio-cli/releases/latest/download/clonio-macos-aarch64 -o clonio
chmod +x clonio
sudo mv clonio /usr/local/bin/clonio
clonio --version
```

> **macOS Gatekeeper note:** If macOS blocks the binary, run `xattr -d com.apple.quarantine clonio` to remove the quarantine flag.

### Composer (dev dependency)

Install as a Composer dev dependency in any PHP project:

```bash
composer require --dev clonio-dev/clonio-cli
```

This makes `vendor/bin/clonio` available with the pinned release version:

```bash
vendor/bin/clonio --version
```

Useful for CI pipelines that already run `composer install`:

```yaml
# GitHub Actions
- name: Clone staging database
  run: vendor/bin/clonio cloning:run production.cloning.yaml --target staging --ci
  env:
    APP_KEY: ${{ secrets.CLONIO_APP_KEY }}
```

### PHAR

Download `clonio.phar` from the [releases page](https://github.com/clonio-dev/clonio-cli/releases/latest) and run it with PHP 8.5:

```bash
php clonio.phar --version
```

### Self-updating

Once installed as a binary or PHAR, update to the latest release with:

```bash
clonio update
```

---

## Initialisation

Before using any commands that need database credentials, run `init` once per project directory to set up the encryption key:

```bash
clonio init
```

This checks for an `APP_KEY` in the following order:

1. **System environment variable** — `APP_KEY` already set in the shell
2. **Local `.env` file** — `.env` in the current working directory

If no key is found, a new key is generated and written to `.env` with permissions set to `0600`:

```
$ clonio init

  Checking for APP_KEY ...

  No APP_KEY found. Generating .env with a new key ...

  ✓  Created .env with APP_KEY in /path/to/project
```

> **`.gitignore` handling:** On every `init`, Clonio checks whether `.env` and `clonio.json` are protected by `.gitignore`. When a `.gitignore` exists, missing entries are appended automatically and reported. When no `.gitignore` exists, an info note is printed without creating the file.

### Force regeneration

```bash
clonio init --force
```

Regenerates the `APP_KEY`. Any encrypted passwords already stored in `clonio.json` will become unreadable — you will need to re-enter them via `connection:update`.

### Production environments

In production (Docker, Kubernetes, CI), set `APP_KEY` as a system environment variable. `clonio init` completes immediately without writing any file.

---

## Typical Workflow

```
1.  clonio init
    → set up APP_KEY

2.  clonio connection:add
    → add a source (production) and target (dev/staging) connection

3.  clonio cloning:dump --connection production-db
    → inspect schema, auto-detect PII, generate production-db.cloning.yaml

4.  Review and edit production-db.cloning.yaml

5.  clonio cloning:run production-db.cloning.yaml --target local-dev
    → transfer and anonymize data
```

---

## Next Steps

- [Connections](02-connections.md) — manage database connections
- [cloning:dump](03-cloning-dump.md) — generate a cloning configuration from a live database
- [cloning:run](04-cloning-run.md) — execute a cloning transfer
- [`.cloning.yaml` Reference](05-cloning-yaml-reference.md) — full configuration format reference
