---
title: Anonymization
introduction: Understand Clonio's data anonymization strategies for PII protection and GDPR compliance.
---

# Anonymization

Data anonymization is central to Clonio's purpose. By applying transformation rules during the cloning process, sensitive production data never reaches non-production environments in its original form.

## Why Anonymize?

Using real production data in development or staging environments creates serious risks:

- **GDPR and privacy regulations** require that personal data is protected and not used beyond its original purpose
- **Non-production environments** typically have weaker security controls, wider access, and less monitoring
- **Data breaches** in test environments can expose real customer information
- **Legal liability** for mishandling personal data can result in significant fines

Clonio ensures compliance by transforming data at the point of transfer, so sensitive values never leave the production database in their original form.

## Transformation Strategies

Each column in a cloned table can be assigned one of the following transformation strategies:

### Keep Identical

The default. The value is copied as-is from the source to the target. Use this for non-sensitive data like IDs, timestamps, status flags, or product information.

### Fake

Generates a realistic but entirely synthetic value using the FakerPHP library. The generated data looks real, which helps catch format-dependent bugs, but contains no actual personal information.

Available faker methods:

| Method | Example Output |
|--------|---------------|
| Name | Jane Smith |
| Email | john.doe@example.com |
| First Name | Alice |
| Last Name | Johnson |
| Phone | +1-555-0142 |
| Address | 123 Main St, Springfield |
| Company | Acme Corp |
| Text | Lorem ipsum dolor sit amet |

Faker values are generated fresh for each row, so no two rows will have the same fake email or name (within statistical probability).

### Hash

Replaces the value with a hashed version. Useful when you need consistent anonymization -- the same input always produces the same hash -- but the original value should not be recoverable.

### Mask

Partially obscures the value while keeping its general format recognizable. For example, an email like `john.doe@company.com` might become `j*******@c******.com`. Useful when you need to see the shape of the data without exposing the actual content.

### Null

Sets the column value to `NULL`. Only works on nullable columns. Useful for columns that are not needed in test environments (e.g., free-text notes, internal comments).

## Configuration Example

A typical anonymization configuration in JSON format:

```json
{
    "tables": [
        {
            "tableName": "users",
            "columnMutations": [
                {"column": "email", "strategy": "fake", "options": {"fakerMethod": "email"}},
                {"column": "name", "strategy": "fake", "options": {"fakerMethod": "name"}},
                {"column": "phone", "strategy": "fake", "options": {"fakerMethod": "phone"}},
                {"column": "notes", "strategy": "null"}
            ]
        },
        {
            "tableName": "orders",
            "columnMutations": [],
            "rowSelection": {
                "strategy": "LastX",
                "count": 1000
            }
        }
    ],
    "keepUnknownTablesOnTarget": false,
    "version": 1
}
```

## Row Selection and Referential Integrity

When a parent table uses row selection (First X or Last X), Clonio automatically applies foreign key filters to child tables. This ensures that only rows referencing the copied parent rows are transferred.

For example, if you copy only the last 1,000 users, the `orders` table will automatically be filtered to include only orders belonging to those 1,000 users. This maintains referential integrity without manual configuration.

Tables with foreign key dependencies have their row selection disabled in the UI and show a notice explaining why.

## PII / GDPR Compliance Indicator

The cloning detail page displays a **PII / GDPR Compliance** section summarizing:

- How many columns across how many tables have transformations applied
- Which anonymization methods are in use (Fake, Hash, Mask, Null)
- Whether all personally identifiable information is being anonymized

This information is also included in the audit trail report for each cloning run.

## Best Practices

1. **Anonymize all PII columns** -- Email, name, phone, address, social security numbers, and any other personally identifiable information.
2. **Use fake data for realistic testing** -- Faker-generated values preserve data format, helping catch bugs that depend on email syntax, name length, etc.
3. **Review your configuration regularly** -- As your database schema evolves, new PII columns may be added. Update transformation rules accordingly.
4. **Start with a small test run** -- Use row selection to clone a small subset first and verify that transformations produce the expected output.
5. **Use the audit trail** -- After each run, review the audit report to confirm that all sensitive columns were transformed.

## Next Steps

Learn about [Triggers and Scheduling](03-triggers-and-scheduling) to automate your cloning runs.
