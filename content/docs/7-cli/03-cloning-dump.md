---
title: cloning:dump
excerpt: Inspect a live database and generate a .cloning.yaml configuration file with auto-detected PII transformations.
---

# `cloning:dump`

Inspects a live database and generates a `.cloning.yaml` configuration file that describes how to anonymize each column when cloning to a target environment. PII columns are detected automatically by matching column names against built-in patterns.

## Usage

```bash
clonio cloning:dump [options]
```

## Options

| Option | Description |
|---|---|
| `--connection=<name>` | Name of the saved connection to inspect |
| `--output=<path>` | Output file path (default: `<connection-name>.cloning.yaml`) |
| `--force` | Overwrite an existing file without asking |
| `--only-pii` | Omit tables and columns with no PII match |
| `--all-columns` | Include every column in the YAML (not just PII-detected) |
| `--locale=<locale>` | FakerPHP locale for `options.faker_locale` (default: `en_US`) |
| `--enforce-column-types` | Set `enforce_column_types: true` in the generated YAML |
| `--drop-unknown-tables` | Set `drop_unknown_tables: true` in the generated YAML |
| `--drop-extra-columns` | Set `drop_extra_columns: true` in the generated YAML |
| `--no-disable-fk-checks` | Set `disable_foreign_key_checks: false` in the generated YAML |
| `--ci` | CI mode — suppress non-error output; `--connection` is required |

## Prerequisites

1. Run `clonio init` to set up `APP_KEY`
2. Add connections with `clonio connection:add`
3. Optionally customise PII detection with `clonio matchers:init`

## Examples

### Basic usage

```bash
clonio cloning:dump --connection production-db
```

Connects to `production-db`, inspects the schema, runs PII detection, and writes `production-db.cloning.yaml` in the current directory.

### Interactive connection selection

```bash
clonio cloning:dump
```

```
 Select a connection to inspect:
  [0] production-db
  [1] staging-db
 > 0

  Inspecting "production-db" (pgsql @ db.prod.io:5432) ...

  Schema fetched: 24 tables, 187 columns

  Transfer options:
    Enforce column types on target? (no)
    Drop unknown tables on target? (no)
    Drop extra columns on target? (no)
    Disable foreign key checks? (yes)

  PII auto-detection:
    ✓  12 columns matched across 5 tables
    ○  175 columns set to keep

  Written: ./production-db.cloning.yaml

  Review the file, adjust strategies as needed, then run:
    clonio cloning:run production-db.cloning.yaml --target <name>
```

### Only include PII columns

```bash
clonio cloning:dump --connection production-db --only-pii
```

Generates a minimal config listing only tables and columns where PII was detected. Useful for large schemas where most tables need no transformation.

### Custom locale

```bash
clonio cloning:dump --connection production-db --locale de_DE
```

### CI mode

```bash
clonio cloning:dump --connection production-db --ci
```

In `--ci` mode, the schema-transfer prompts are skipped — pass the corresponding flags directly:

```bash
clonio cloning:dump --connection production-db --ci \
  --enforce-column-types \
  --drop-unknown-tables
```

## Generated file format

```yaml
# yaml-language-server: $schema=https://clonio.dev/schema/cloning-v1.json
version: "1"
connection: production-db

options:
  chunk_size: 1000
  enforce_column_types: false
  drop_unknown_tables: false
  drop_extra_columns: false
  disable_foreign_key_checks: true
  faker_locale: en_US

tables:
  users:
    rows:
      strategy: full
    columns:
      # PII: Email Address
      email:
        strategy: fake
        faker_method: safeEmail
        faker_arguments: []
      # PII: Password / Secret
      password:
        strategy: hash
        algorithm: sha256
        salt: ""

  orders:
    rows:
      strategy: full
    # no PII detected — no columns listed; all kept as-is
```

The generated file is meant to be **reviewed, adjusted, and committed** to your repository. See the [`.cloning.yaml` Reference](05-cloning-yaml-reference.md) for all available strategies and options.

## PII detection

Clonio ships with built-in matchers covering all major PII categories:

| Category | Examples |
|---|---|
| Personal identity | `name`, `first_name`, `last_name`, `date_of_birth` |
| Contact | `email`, `phone`, `address`, `city` |
| Location | `latitude`, `longitude`, `postcode` |
| Financial | `iban`, `credit_card_number`, `bank_account` |
| Authentication | `password`, `secret_key`, `api_key` |
| Network | `ip_address`, `mac_address` |

To customise which columns are detected, use the [PII Matchers](06-pii-matchers.md) commands.

## Recommended workflow

```
1.  cloning:dump --connection production-db
    → generates production-db.cloning.yaml

2.  Review the file:
    - Adjust strategies (e.g. change hash to fake for passwords)
    - Add missing PII columns
    - Tune row strategies (full vs first/last with limit)
    - Set salt values for hash strategies

3.  Commit to your repository

4.  cloning:run production-db.cloning.yaml --target local-dev
    → applies the config, cloning production → local-dev
```

## Exit codes

| Code | Meaning |
|---|---|
| `0` | File written successfully |
| `2` | `clonio.json` not found, or no connections defined |
| `3` | Connection not found, or database connection failed |
| `4` | `--connection` required in `--ci` mode but not provided |
