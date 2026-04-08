---
title: Other Commands
excerpt: Reference for the about, update, fake:data, and cloning:verify-audit commands.
---

# Other Commands

---

## `about`

Displays a short introduction to Clonio — who it is for, what it does, and where to learn more.

```bash
clonio about
```

No arguments or options. Exit code is always `0`.

---

## `update`

Updates the Clonio binary or PHAR to the latest release from GitHub.

```bash
clonio update [version] [--no-verify-ssl]
```

| Argument | Description |
|---|---|
| `version` | Target version to install (e.g. `1.2.0` or `v1.2.0`). Omit for latest. |

| Option | Description |
|---|---|
| `--no-verify-ssl` | Skip SSL certificate verification. Use behind corporate VPNs with SSL inspection. |

The command detects the current runtime (Linux x86_64, Linux aarch64, macOS aarch64, PHAR) and downloads the correct file. The replacement is atomic — a partial download is cleaned up on failure.

If installed in a system path, elevated permissions may be required:

```bash
sudo clonio update
```

### Exit codes

| Code | Meaning |
|---|---|
| `0` | Already at target version, or updated successfully |
| `1` | GitHub unreachable, version not found, download failed, or binary could not be replaced |

---

## `fake:data`

Seeds a configured database with realistic fake data for local testing. Useful for benchmarking Clonio against large data volumes.

```bash
clonio fake:data <connection> [<rows>] [--fresh]
```

| Argument | Default | Description |
|---|---|---|
| `connection` | — | Name of the connection from `clonio.json` |
| `rows` | `1000` | Number of rows to insert per table |

| Option | Description |
|---|---|
| `--fresh` | Drop all demo tables and recreate the schema before seeding |

### Schema created

The command creates two groups of tables automatically:

**Task management:** `users`, `user_login_history`, `projects`, `issues`, `comments`

**Product catalog:** `categories`, `tags`, `products`, `product_tags`

This schema covers UUID, bigint auto-increment, and composite primary keys plus all relationship types (one-to-many, self-referencing, many-to-many), exercising the full breadth of Clonio's transfer logic.

### Examples

```bash
# Default 1 000 rows per table
clonio fake:data local-mysql

# 1 million rows per table (benchmarking)
clonio fake:data local-pgsql 1000000

# Reset and reseed from scratch
clonio fake:data local-sqlite 50000 --fresh
```

Data is inserted in batches of 500 rows. Supported on all five drivers: `mysql`, `mariadb`, `pgsql`, `sqlsrv`, `sqlite`.

### Exit codes

| Code | Meaning |
|---|---|
| `0` | All rows inserted successfully |
| `1` | Schema creation or seeding failure |
| `2` | Connection name not found in `clonio.json` |
| `3` | Could not open the database connection |
| `4` | `rows` argument is not a positive integer |

---

## `cloning:verify-audit`

Verifies the integrity of a Clonio audit log by re-deriving its HMAC-SHA256 signature and comparing it against the stored `.sig` sidecar file.

```bash
clonio cloning:verify-audit <file> [--sig=<path>]
```

| Argument | Description |
|---|---|
| `file` | Path to the `.html` audit log file to verify |

| Option | Description |
|---|---|
| `--sig=<path>` | Path to the `.sig` file (default: `<file>.sig` in the same directory) |

The command does not connect to any database or network — it only reads the two local files and the local `APP_KEY`.

### Example

```bash
clonio cloning:verify-audit production-db_staging_2026-04-01T14-32-00Z_audit.html
```

```
  ✓  Audit log verified — signature matches
     File:      production-db_staging_2026-04-01T14-32-00Z_audit.html
     SHA-256:   e3b0c44298fc1c149afb...
     Signed at: 2026-04-01T14:34:12Z
```

If the document has been tampered with:

```
  ✗  Audit log verification FAILED — document may have been tampered with
```

### How signing works

1. Clonio serialises the audit record to canonical JSON.
2. Computes SHA-256 of the canonical JSON.
3. Computes `HMAC-SHA256(sha256Hash, APP_KEY)`.
4. Writes the signature to the `.sig` sidecar file as `sha256:<signature>`.

Verification reverses steps 1–3 using the same `APP_KEY` and compares both values using `hash_equals()`.

### Audit log file naming

```
{source}_{target}_{timestamp}_audit.html
{source}_{target}_{timestamp}_audit.sig
```

Both files must be present for verification.

### Exit codes

| Code | Meaning |
|---|---|
| `0` | Signature verified successfully |
| `1` | Signature does not match |
| `2` | `APP_KEY` not set or not readable |
| `5` | Audit log file or `.sig` file not found |
