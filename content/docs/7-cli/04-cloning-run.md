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
| `--break-on-failure` | Abort run immediately on first table failure (schema or data). Default behaviour continues processing remaining tables. |
| `--skip-schema` | Skip schema replication; assume target schema already matches |
| `--skip-tables=<list>` | Comma-separated table names to exclude from this run |
| `--only-tables=<list>` | Comma-separated table names to include; all others are skipped |
| `--audit-channel=<list>` | Comma-separated channel names to use (overrides `deliver_to` in `clonio.json`) |
| `--skip-remapping-keys` | Skip key mapping generation and FK rewriting |
| `--no-memory-limit` | Remove PHP's `memory_limit` before generating key mappings |
| `--file-based` | Store key mappings in AES-256-CBC encrypted temp files instead of RAM |
| `--enforce-column-types` / `--no-enforce-column-types` | Override `enforce_column_types` for this run |
| `--drop-unknown-tables` / `--no-drop-unknown-tables` | Override `drop_unknown_tables` for this run |
| `--drop-extra-columns` / `--no-drop-extra-columns` | Override `drop_extra_columns` for this run |
| `--disable-fk-checks` / `--no-disable-fk-checks` | Override `disable_foreign_key_checks` for this run |

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

When a table is excluded, all tables with a foreign-key dependency on it (directly or transitively) are also excluded. Cascaded tables appear in the audit log with status `skipped_by_cascade`.

Tables can also be skipped permanently in YAML — see [Skipping in YAML](#skipping-in-yaml) below.

---

## Schema Synchronization

Before transferring data, Clonio can synchronize the target schema to match the source. Four options in the `options:` block control this behaviour, each overridable per run via CLI flags:

| YAML option | Default | CLI override | Effect |
|---|---|---|---|
| `enforce_column_types` | `false` | `--enforce-column-types` / `--no-enforce-column-types` | Add columns to target tables present in source but missing from target |
| `drop_extra_columns` | `false` | `--drop-extra-columns` / `--no-drop-extra-columns` | Drop columns from target tables that exist in target but not in source |
| `drop_unknown_tables` | `false` | `--drop-unknown-tables` / `--no-drop-unknown-tables` | Drop tables from target that do not exist in source |
| `disable_foreign_key_checks` | `true` | `--disable-fk-checks` / `--no-disable-fk-checks` | Disable FK constraint checks on target during transfer |

```yaml
options:
  chunk_size: 1000
  enforce_column_types: true
  drop_extra_columns: true
  drop_unknown_tables: false
  disable_foreign_key_checks: true
  faker_locale: en_US
```

All schema-sync options are applied during **Phase 4 — Schema Replication**, before any data is transferred. Pass `--skip-schema` to skip this phase entirely. Missing tables are **always** created — no option needed.

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

## Pipeline phases

`cloning:run` executes 9 phases sequentially. Each phase must complete before the next begins.

```
Phase 1  — YAML Validation
Phase 2  — Connection Checks
Phase 3  — Dry-run                 (only if --dry-run; exits here)
Phase 4  — Schema Replication      (skipped if --skip-schema)
Phase 5  — Dependency Resolution
Phase 5b — Key Mapping Generation  (when remapping columns are defined)
Phase 6  — Data Transfer           (chunked; row-by-row fallback on FK/unique violation)
Phase 7  — Key Mapping Cleanup     (when remapping columns are defined)
Phase 8  — Audit Log & Process Log
Phase 9  — Summary
```

Audit log is written even on early `--break-on-failure` abort.

---

## Skipping in YAML

Two YAML mechanisms beyond `--skip-tables`:

**Top-level `skip:` list** — tables that need no anonymisation config:

```yaml
skip:
  - audit_logs
  - telescope_entries
  - failed_jobs
```

**`rows.strategy: skip`** — for tables already declared in `tables:`:

```yaml
tables:
  audit_logs:
    rows:
      strategy: skip
  users:
    rows:
      strategy: full
```

YAML-level skips and `--skip-tables` are **additive** (merged at runtime). Same FK cascade rules apply.

---

## Per-table run statuses

Each table is recorded with one of:

| Status | Meaning |
|---|---|
| `transferred` | Table transferred successfully |
| `skipped_by_flag` | Excluded via `--skip-tables` / `--only-tables` |
| `skipped_by_cascade` | Excluded due to FK dependency on a skipped table |
| `skipped_by_schema_failure` | Schema creation in target failed; data step skipped. Overall `success: false`. |
| `not_found` | Listed in YAML but missing from source |
| `failed` | Transfer attempted, hit unrecoverable error |

`not_found` is **non-fatal** — run succeeds if every found table transferred.

---

## Output modes

| Level | Flag | Output |
|---|---|---|
| quiet | `-q` / `--ci` | No stdout; errors → stderr; exit code only |
| normal | (default) | Dot indicators (`.FE?S`) + summary |
| verbose | `-v` | One line per table with status and row count |
| very verbose | `-vv` | Live streaming of run-log events to stderr |
| debug | `-vvv` | Schema diff, per-table progress bars, chunk-level events |

**Dot indicators:**

| Char | Meaning |
|---|---|
| `.` | Table transferred successfully |
| `F` | Transferred with skipped rows |
| `E` | Transfer failed |
| `?` | Not found in source |
| `S` | Skipped due to schema replication failure |

Progress bars are suppressed under `--ci` regardless of verbosity.

---

## Key remapping recovery

When the column type cannot host the source row count (e.g. a `TINYINT` PK with 300 source rows), the run aborts in Phase 5b with a *key remapping exhausted* error.

**Interactive mode** prints a summary (column type, ceiling, rows requested, slots available) and prompts whether to switch the offending column's strategy to `keep`. Accepting invokes `cloning:column:edit ... --strategy=keep` in-process; the YAML is rewritten and the original `cloning:run` is echoed as a re-run hint. Exit `0` either way.

**`--ci` mode** prints the same summary plus a hint command, then exits `1` (`GeneralError`) without prompting.

If widening the column type is the right fix, run a schema migration on the source — Clonio picks up the new ceiling automatically on the next run.

---

## Exit Codes

| Code | Meaning |
|---|---|
| `0` | Run completed successfully (or `--allow-failure` was passed) |
| `1` | Run failed — one or more tables had unrecoverable errors |
| `2` | Config error — `clonio.json` missing or `APP_KEY` not set |
| `3` | Connection error — source or target unreachable |
| `4` | Validation error — invalid YAML, `--skip-tables` + `--only-tables` combined, or CI without `--target` |
| `5` | I/O error — YAML file not found or not readable |

---

## Related

- [`.cloning.yaml` Reference](05-cloning-yaml-reference.md) — full config format and all column strategies
- [`cloning:dump`](03-cloning-dump.md) — generate a config from a live database
- [Pipeline Integration](../3-cloning-runs/03-pipeline-integration.md) — CI/CD integration patterns
