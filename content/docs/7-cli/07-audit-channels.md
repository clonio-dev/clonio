---
title: Audit Channels
excerpt: Configure where Clonio delivers audit logs and run logs after each cloning operation — local files, S3, email, Slack, Teams, and ntfy.
---

# Audit Channels

Audit channels define where Clonio sends the signed audit log and run log after each `cloning:run`. Channels are stored in `clonio.json` and can be managed with the `audit:*` commands.

Secrets (S3 keys, SMTP passwords, webhook URLs) are stored encrypted using `APP_KEY`.

---

## Supported channel types

| Type | Description |
|---|---|
| `local` | Write files to a local directory |
| `s3` | Upload to any S3-compatible object storage |
| `email` | Send via SMTP |
| `ms_teams` | Post a card to a Microsoft Teams incoming webhook |
| `slack` | Post a message to a Slack incoming webhook |
| `ntfy` | Push a notification via [ntfy.sh](https://ntfy.sh) or self-hosted ntfy |
| `stack` | Fan-out composite — deliver to multiple channels at once |

### What each channel delivers

Each channel decides independently whether it carries the audit log, the run log, or both. Defaults:

| Channel type | Audit log | Run log |
|---|:---:|:---:|
| `local` | ✓ | ✓ |
| `s3` | ✓ | ✓ |
| `email` | ✓ | ✗ |
| `ms_teams` | ✓ | ✗ |
| `slack` | ✓ | ✗ |
| `ntfy` | ✓ | ✗ |
| `stdout` / `stderr` | ✓ | ✗ |

Override per channel using the `delivers_audit` and `delivers_process_log` boolean keys directly in `clonio.json`. The `audit:add` flags `--deliver-audit-log` / `--deliver-run-log` (and their `--no-…` counterparts) set these at creation time; `audit:update` does not change them — edit `clonio.json` afterwards.

---

## `audit:add`

Adds a new audit delivery channel to `clonio.json`.

```bash
clonio audit:add [<name>] [options]
```

Run without flags for interactive mode — the command prompts for name, type, and type-specific fields in order. A summary table is shown before writing.

### Options by channel type

**Local:**

| Option | Description |
|---|---|
| `--local-path=` | Directory path (supports [path templates](#path-templates)) |

**S3:**

| Option | Description |
|---|---|
| `--endpoint=` | S3 endpoint URL |
| `--bucket=` | Bucket name |
| `--region=` | Region |
| `--access-key=` | Access key ID |
| `--secret-key=` | Secret key (stored encrypted) |
| `--path-prefix=` | Object key prefix (supports path templates) |

**Email:**

| Option | Description |
|---|---|
| `--host=` | SMTP host |
| `--port=` | SMTP port |
| `--encryption=` | `tls`, `ssl`, or `none` |
| `--username=` | SMTP username |
| `--password=` | SMTP password (stored encrypted) |
| `--from-address=` | Sender address |
| `--from-name=` | Sender display name |
| `--to=` | Comma-separated recipient addresses |

**Microsoft Teams / Slack (`--type=ms_teams` or `--type=slack`):**

| Option | Description |
|---|---|
| `--webhook-url=` | Incoming webhook URL (stored encrypted) |

**ntfy:**

| Option | Description |
|---|---|
| `--url=` | ntfy server base URL (default: `https://ntfy.sh`) |
| `--topic=` | ntfy topic name |
| `--priority=` | `min`, `low`, `default`, `high`, or `max` |
| `--tags=` | Comma-separated tag strings (optional) |
| `--token=` | Bearer token for authenticated servers (stored encrypted, optional) |

**Stack (`--type=stack`):**

| Option | Description |
|---|---|
| `--channels=` | Comma-separated list of child channel names to deliver to |

**Common:**

| Option | Description |
|---|---|
| `--set-default` | Set this channel as `audit.default` in `clonio.json` |
| `--deliver-audit-log` / `--no-deliver-audit-log` | Toggle audit-log delivery for this channel |
| `--deliver-run-log` / `--no-deliver-run-log` | Toggle run-log delivery for this channel |

### Examples

```bash
# Interactive
clonio audit:add

# Local channel
clonio audit:add logs --type=local --local-path=./clonio-logs/{year}/{month}

# S3 channel
clonio audit:add s3-backup --type=s3 --endpoint=https://s3.amazonaws.com \
  --bucket=my-bucket --region=eu-west-1 --access-key=AKIA... \
  --path-prefix=clonio/{year}/{month}/

# Slack (audit log only, no run log)
clonio audit:add slack-alerts --type=slack \
  --deliver-audit-log --no-deliver-run-log

# Microsoft Teams
clonio audit:add teams-alerts --type=ms_teams --webhook-url=https://...

# Email with multiple recipients
clonio audit:add email-report --type=email --host=smtp.example.com \
  --port=587 --encryption=tls --username=bot@example.com \
  --from-address=bot@example.com --from-name="Clonio" \
  --to="alice@example.com,bob@example.com"
```

---

## `audit:update`

Updates an existing channel. Channel type cannot be changed — delete and re-add to switch types.

```bash
clonio audit:update [<name>]
```

All fields are re-prompted with current values pre-filled. Secrets display as `••••••••` — press Enter to keep, or enter a new value to replace. A diff table is shown before the final confirmation.

---

## `audit:delete`

Deletes a channel. Also removes it from any `deliver_to` lists.

```bash
clonio audit:delete [<name>] [--force]
```

A warning is displayed if the channel is currently active in a `deliver_to` list.

| Option | Description |
|---|---|
| `--force` | Skip the confirmation prompt |

---

## `audit:list`

Lists all configured channels and their delivery assignments.

```bash
clonio audit:list
```

Outputs a table with: Name, Type, **Default** (★ for the default channel), Audit Log (✓/✗), Run Log (✓/✗), and a Details summary appropriate to the channel type (path / `s3://bucket/prefix` / first email recipient / webhook host / `server-url / topic`).

---

## Default channel & overrides

The `audit.default` key in `clonio.json` selects which channel receives artefacts by default:

```json
"audit": {
    "default": "local",
    "channels": { ... }
}
```

To deliver to multiple channels, point `default` at a `stack` channel.

The `--audit-channel=<list>` flag on `cloning:run` overrides the default for a single run (comma-separated channel names).

Use `--set-default` when adding a channel to set it as default in one step:

```bash
clonio audit:add logs --type=local --local-path=./ --set-default
```

---

## Stack channels

The `stack` type fans out delivery to multiple child channels:

```json
"production": {
    "type": "stack",
    "channels": ["local", "s3-backup", "slack-notify"]
}
```

---

## Path templates

Local and S3 path fields support these placeholders:

| Placeholder | Description |
|---|---|
| `{year}` | Four-digit year (e.g. `2025`) |
| `{month}` | Two-digit month (e.g. `04`) |
| `{day}` | Two-digit day (e.g. `15`) |
| `{source}` | Source connection name |
| `{target}` | Target connection name |
| `{timestamp}` | Unix timestamp at run start |

---

## Notes

- All secrets are stored encrypted with `APP_KEY`. Passing secrets via flags may expose them in shell history — use interactive mode instead.
- All commands read/write `clonio.json` in the **current working directory**.
- S3, email, Teams, Slack, and ntfy channels support optional retry configuration (max attempts, backoff) — configure interactively via `audit:add`, or edit `clonio.json` directly afterward.
