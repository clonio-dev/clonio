---
title: cloning:run
excerpt: Execute a cloning transfer — validate config, verify connectivity, transfer and anonymize data, produce a signed audit log.
---

# `cloning:run`

Executes a cloning transfer: validates the `.cloning.yaml` configuration, verifies connectivity, and transfers data from the source database to the target with all configured anonymization transformations applied. Produces a signed audit log and a structured run log delivered to configured channels.

## Usage

```bash
clonio cloning:run <file> [options]
```

## Arguments

| Argument | Description |
|---|---|
| `file` | Path to the `.cloning.yaml` configuration file |

## Options

| Option | Description |
|---|---|
| `--target=<name>` | Name of the target connection (from `clonio.json`) |
| `--dry-run` | Validate, test connections, and count rows — no data transferred |
| `--ci` | CI mode — suppress all non-error output; `--target` is required |
| `--allow-failure` | Exit with code `0` even if the run fails (for optional CI steps) |
| `--skip-schema` | Skip schema replication; assume target schema already matches |
| `--skip-tables=<list>` | Comma-separated table names to exclude from this run |
| `--only-tables=<list>` | Comma-separated table names to include; all others are skipped |
| `--audit-channel=<list>` | Comma-separated channel names to use (overrides `deliver_to` in `clonio.json`) |

`--skip-tables` and `--only-tables` are mutually exclusive. Verbosity flags `-v` / `-vv` / `-vvv` are also supported.

## Prerequisites

1. Run `clonio init` to set up `APP_KEY`
2. Add connections with `clonio connection:add`
3. Generate and review a config file with `clonio cloning:dump`

## Examples

### Basic transfer

```bash
clonio cloning:run production-db.cloning.yaml --target local-dev
```

### Dry run

Validates the config, tests connectivity, estimates row counts, and shows the schema diff — without moving any data:

```bash
clonio cloning:run production-db.cloning.yaml --target staging --dry-run
```

```
  Dry-run: production-db

  Schema diff: source → target

  Missing tables (1):   audit_logs
  Modified table:       users: +1 cols (phone)

  Table                   Rows (est.)   Strategy   Transformations
  ─────────────────────────────────────────────────────────────────
  users                    12 340       last 5000   email, first_name, password
  orders                   48 201       full        shipping_address
  audit_logs              NOT FOUND     —           —
  product_catalog             923       full        (none)

  No data will be transferred. Run without --dry-run to execute.
```

### CI mode

```bash
clonio cloning:run production-db.cloning.yaml --target staging --ci
```

### Optional CI step (always exits 0)

```yaml
# GitHub Actions
- name: Sync staging (optional)
  run: clonio cloning:run prod.cloning.yaml --target staging --ci --allow-failure
```

### Filter tables

```bash
# Exclude specific tables
clonio cloning:run prod.cloning.yaml --target staging --skip-tables=audit_logs,sessions

# Only transfer specific tables
clonio cloning:run prod.cloning.yaml --target staging --only-tables=users,orders
```

---

## Schema Synchronization

Before transferring data, Clonio can synchronize the target schema to match the source. Three options in the `options:` block control this behaviour:

| Option | Default | Effect |
|---|---|---|
| `enforce_column_types` | `false` | Add columns to target tables that are present in source but missing from target |
| `drop_extra_columns` | `false` | Drop columns from target tables that exist in target but not in source |
| `drop_unknown_tables` | `false` | Drop tables from target that do not exist in source |

```yaml
options:
  chunk_size: 1000
  enforce_column_types: true
  drop_extra_columns: true
  drop_unknown_tables: false
  disable_foreign_key_checks: true
  faker_locale: en_US
```

All three options are applied during **Phase 4 — Schema Replication**, before any data is transferred. Pass `--skip-schema` to skip this phase entirely.

### Behaviour matrix

| Source table | Target table | Action |
|---|---|---|
| Exists | Missing | Always created |
| Exists | Exists, missing columns | Columns added when `enforce_column_types: true` |
| Exists | Exists, extra columns | Columns dropped when `drop_extra_columns: true` |
| Missing | Exists | Table dropped when `drop_unknown_tables: true` |

> **Caution — `drop_extra_columns`:** Dropping columns is irreversible. Only enable on ephemeral environments (e.g. a fresh CI database) or when confirmed safe.

---

## Key Remapping

Key remapping assigns new primary key values to transferred rows and rewrites all foreign key references that point to those IDs, preventing ID collisions on the target.

### Inline remapping (recommended)

Add `strategy: remapping` to any column in the `columns` block:

```yaml
tables:
  users:
    rows:
      strategy: full
    columns:
      id:
        strategy: remapping
        arguments:
          - use: random_integer
          - min: 100000
          - max: 9999999
          - foreign_keys:
              - table: orders
                column: user_id
              - table: employees
                column: manager_id
                self_referential: true   # employees.manager_id → employees.id
      email:
        strategy: fake
        faker_method: safeEmail
        faker_arguments: []

  orders:
    rows:
      strategy: full
    columns:
      id:
        strategy: remapping
        arguments:
          - use: random_integer
          - min: 100000
          - max: 9999999
          - foreign_keys:
              - table: order_items
                column: order_id
    # orders.user_id is rewritten automatically because users.id declared it as a FK
```

### Remapping arguments

| Key | Required | Values | Description |
|---|---|---|---|
| `use` | yes | `random_integer` \| `new_uuid` | Strategy for generating new PK values |
| `min` | `random_integer` only | integer ≥ 1 | Lower bound (default: `100000`) |
| `max` | `random_integer` only | integer | Upper bound (default: `9999999`) |
| `foreign_keys` | yes | list | FK columns on other tables that reference this column |

Each foreign key entry:

| Field | Required | Description |
|---|---|---|
| `table` | yes | Table that holds the FK column |
| `column` | yes | Name of the FK column |
| `self_referential` | no | `true` when the FK points back to the same table. Rows are inserted with `null` first, then updated in a second pass |

---

## Exit Codes

| Code | Meaning |
|---|---|
| `0` | Run completed successfully (or `--allow-failure` was passed) |
| `1` | Run failed |
| `2` | Config error — `clonio.json` missing or invalid |
| `3` | Connection error — source or target database unreachable |
| `4` | Validation error — invalid YAML or missing required fields |

---

## Related

- [`.cloning.yaml` Reference](05-cloning-yaml-reference.md) — full config format and all column strategies
- [`cloning:dump`](03-cloning-dump.md) — generate a config from a live database
- [Pipeline Integration](../3-cloning-runs/03-pipeline-integration.md) — CI/CD integration patterns
