---
title: PII Matchers
excerpt: Customise how Clonio auto-detects PII columns during cloning:dump using the matchers commands and clonio.pii-matchers.yaml.
---

# PII Matchers

Clonio detects personally identifiable information (PII) columns automatically by matching column names against a set of rules called **matchers**. When a column matches, the configured transformation strategy is applied during `cloning:dump`.

The binary ships a **baseline** set of matchers. Customise detection by initialising a `clonio.pii-matchers.yaml` file in your project root and editing it — this file contains no credentials and is safe to commit.

---

## `matchers init`

Write the full baseline matcher configuration to `clonio.pii-matchers.yaml`.

```bash
clonio matchers init [--force] [--path=<path>]
```

| Option | Description |
|---|---|
| `--force` | Overwrite an existing file without confirmation |
| `--path=<path>` | Output path (default: `clonio.pii-matchers.yaml` in cwd) |

Run this once per project to start customising PII detection. After initialisation, the YAML file is the sole source of truth — the baseline is only used when the file is absent.

---

## `matchers update`

Sync an existing `clonio.pii-matchers.yaml` with the current baseline by adding new matchers and reporting orphans.

```bash
clonio matchers update [--dry-run] [--path=<path>]
```

| Option | Description |
|---|---|
| `--dry-run` | Show what would be added without writing anything |
| `--path=<path>` | Path to `clonio.pii-matchers.yaml` |

Run after upgrading Clonio to pick up any new baseline matchers. Your existing customisations are never overwritten.

---

## `matchers list`

Show the full effective matcher set.

```bash
clonio matchers list [--path=<path>]
```

Outputs a table with name, group, sensitivity level, transformation strategy, and source (`baseline` or `file`).

### Sensitivity levels

| Level | Meaning |
|---|---|
| `critical` | Direct disclosure causes substantial harm — SSN, credit card, password, medical record |
| `high` | Direct personal identifiers — name, email, phone, DOB, address, session token |
| `medium` | Indirect identifiers — IP address, postal code, device ID, gender |
| `low` | Contextual data — city, country, job title, employer |

---

## `matchers check`

Test a column name against the active matcher set.

```bash
clonio matchers check <column> [value]
```

| Argument | Description |
|---|---|
| `column` | Column name to test |
| `value` | *(optional)* A value to run through the transformation |

```bash
clonio matchers check credit_card
```

```
  Column "credit_card" matched:

    Matcher:        credit_card
    Group:          financial
    Sensitivity:    critical

    Transformation:
      strategy:       mask
      visible_chars:  4
      mask_char:      "*"

    Example:
      Input:   4242424242424242
      Output:  4242************
```

```bash
clonio matchers check created_at
# Column "created_at" — no matcher found
# This column will be treated as strategy: keep by cloning:dump.
```

---

## `clonio.pii-matchers.yaml` format

```yaml
# yaml-language-server: $schema=https://schema.clonio.dev/pii-matchers/v1.json
version: "1"

groups:
  contact:
    name: "Contact Information"
    matchers:
      email_address:
        name: "Email Address"
        enabled: true
        patterns:
          - "/^(e[-_]?mail|email[-_]?addr(ess)?)$/i"
          - reply_to
          - "*_email"
        transformation:
          strategy: fake
          faker_method: safeEmail
          faker_arguments: []
```

### Pattern syntax

| Form | Example | Behaviour |
|---|---|---|
| Regex | `/^email$/i` | Matched via `preg_match()`. Must start and end with `/`. |
| Glob | `*_email` | `*` matches any characters. Case-insensitive. |
| Literal | `reply_to` | Exact case-insensitive match. |

---

## Built-in matcher groups

The binary ships **10 matcher groups**:

| Group | Description | Sensitivity |
|---|---|---|
| `government_ids` | SSN, passport, driver's license, tax ID | critical |
| `personal_identity` | Name, DOB, gender, nationality | high / medium |
| `contact` | Email, phone, username | high / medium |
| `location` | Address, city, postal code, lat/lon | high–low |
| `financial` | Credit card, IBAN, routing number, salary¹ | critical / high |
| `medical` | Medical record ID, insurance ID, diagnosis¹ | critical / high |
| `biometric` | Fingerprint, face encoding, DNA¹ | critical |
| `professional` | Company name, job title, employee ID | low / medium |
| `digital_identity` | IP address, device ID, session ID, MAC address | high / medium |
| `authentication` | Password, OAuth token, API key¹, private key¹ | critical |

¹ Disabled by default — enable in `clonio.pii-matchers.yaml` when relevant to your schema.

---

## Recommended workflow

```bash
# 1. Initialise matcher file in your project
clonio matchers init

# 2. Review and edit clonio.pii-matchers.yaml
#    Enable disabled matchers relevant to your data (medical, biometric, salary, etc.)

# 3. Commit clonio.pii-matchers.yaml to version control

# 4. Test specific columns
clonio matchers check user_email
clonio matchers check ssn 123-45-6789

# 5. After upgrading Clonio, sync new baseline matchers
clonio matchers update
```
