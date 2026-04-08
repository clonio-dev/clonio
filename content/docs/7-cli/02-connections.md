---
title: Managing Connections
excerpt: Add, list, test, update, and delete database connections stored in clonio.json.
---

# Managing Connections

Connections are stored in `clonio.json` in the current working directory. Passwords are encrypted with the project's `APP_KEY`. The file contains no plaintext credentials and you should still add it to `.gitignore`.

Run `clonio init` before adding connections to ensure `APP_KEY` is set.

---

## `connection:add`

Adds a new database connection to `clonio.json`.

```bash
clonio connection:add [<name>] [options]
```

All arguments and options are optional — any value not supplied via a flag is collected interactively.

### Interactive flow

When run without flags the command walks through each field in order: name, driver, host, port, database, schema (PostgreSQL only), username, password, and whether the connection is a production environment.

A summary table is displayed before writing. The operation can be cancelled at the final confirmation prompt.

### Options

| Option | Description |
|---|---|
| `name` | Connection name (argument) |
| `--type=` | Driver: `mysql`, `mariadb`, `pgsql`, `sqlsrv`, `sqlite` |
| `--host=` | Database host (default: `localhost`) |
| `--port=` | TCP port (1–65535) |
| `--database=` | Database name or file path |
| `--schema=` | Schema name (PostgreSQL only, default: `public`) |
| `--username=` | Database username |
| `--password=` | Database password (stored encrypted) |
| `--production` | Mark as a production environment |

### Exit codes

| Code | Meaning |
|---|---|
| `0` | Added successfully, or cancelled |
| `2` | `APP_KEY` missing or encryption failed |
| `4` | Invalid name, duplicate name, invalid port, or unknown driver |
| `5` | Could not write to `clonio.json` |

---

## `connection:list`

Lists all configured connections.

```bash
clonio connection:list
```

Outputs a table showing name, driver, host, database, and whether the connection is marked as production. Passwords are never displayed.

If no connections are configured, a hint to run `connection:add` is printed.

---

## `connection:test`

Tests connectivity for one or all connections.

```bash
# Test a specific connection
clonio connection:test <name>

# Test all connections
clonio connection:test

# CI mode — suppress table output, errors to stderr
clonio connection:test --ci
```

### What "tested" means

| Driver | Method |
|---|---|
| SQLite | Checks the file exists, is readable, and writable |
| MySQL, MariaDB, PostgreSQL, SQL Server | Opens a real TCP connection via PDO |

### Options

| Option | Description |
|---|---|
| `--ci` | Suppress output table; summary line still printed; errors go to stderr |

### Exit codes

| Code | Meaning |
|---|---|
| `0` | All tested connections succeeded |
| `2` | Config error — connection not found or `APP_KEY` missing |
| `3` | One or more connections failed |

---

## `connection:update`

Interactively updates an existing connection.

```bash
clonio connection:update [<name>]
```

All fields are re-prompted with current values pre-filled. Press `Enter` to keep a value unchanged. The existing password is never shown — leave the prompt blank to keep it, or enter a new one to replace it.

A diff table shows only the fields that changed before the final confirmation.

### Notes

- **Name change** — the old entry is removed and the new one written atomically.
- **Driver change** — resets fields that do not apply to the new driver.
- **Password** — stored encrypted; leaving the prompt blank preserves the existing value.

### Exit codes

| Code | Meaning |
|---|---|
| `0` | Updated successfully, or cancelled |
| `2` | Connection not found or `clonio.json` missing |
| `4` | New name conflicts with an existing connection |
| `5` | Could not write to `clonio.json` |

---

## `connection:delete`

Deletes a connection from `clonio.json`.

```bash
clonio connection:delete [<name>] [--force]
```

A summary of the connection is shown before deletion. Production connections display an additional warning. The confirmation prompt defaults to **No** — press Enter to cancel.

### Options

| Option | Description |
|---|---|
| `--force` | Skip the confirmation prompt |

### Exit codes

| Code | Meaning |
|---|---|
| `0` | Deleted successfully, or cancelled |
| `2` | No connections exist, or connection not found |
| `5` | Could not write to `clonio.json` |
