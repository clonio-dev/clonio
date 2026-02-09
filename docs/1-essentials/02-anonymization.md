---
title: Anonymization
introduction: Understand how Clonio anonymizes sensitive data to ensure GDPR compliance and data privacy.
---

# Anonymization

Data anonymization is at the heart of Clonio. It ensures that sensitive production data is transformed before it reaches non-production environments.

## Why Anonymize?

Using real production data in development or staging environments poses significant risks:

- **GDPR compliance** — Personal data must be protected
- **Data breaches** — Non-production environments often have weaker security
- **Legal liability** — Mishandling personal data can result in fines

Clonio solves this by applying mutation rules during the cloning process, ensuring no sensitive data leaves the production environment in its original form.

## Column Mutations

Column mutations define how individual columns are transformed. Each mutation takes the original value and produces an anonymized replacement.

### Faker Mutations

Faker mutations use the [FakerPHP](https://fakerphp.org/) library to generate realistic but fake data:

```php
// Available faker mutations
'faker.email'       // jane.doe@example.com
'faker.name'        // John Smith
'faker.firstName'   // Alice
'faker.lastName'    // Johnson
'faker.phone'       // +1-555-0142
'faker.address'     // 123 Main St, Springfield
'faker.company'     // Acme Corp
'faker.text'        // Lorem ipsum dolor sit amet...
```

### Static Mutations

Static mutations replace column values with a fixed string:

```php
'static:redacted'      // Sets value to "redacted"
'static:test@test.com' // Sets value to "test@test.com"
```

### Null Mutation

The null mutation sets the column value to `NULL`:

```php
'null' // Sets value to NULL
```

## Per-Table Configuration

Each table in your cloning configuration can have its own set of column mutations:

```json
{
    "tableName": "customers",
    "columnMutations": [
        {"column": "email", "mutation": "faker.email"},
        {"column": "name", "mutation": "faker.name"},
        {"column": "phone", "mutation": "faker.phone"},
        {"column": "notes", "mutation": "null"}
    ]
}
```

## Best Practices

1. **Always anonymize PII columns** — Email, name, phone, address, and any other personally identifiable information
2. **Use faker mutations for realistic data** — This helps catch bugs that depend on data format
3. **Test your anonymization config** — Run a small clone first to verify the output
4. **Review regularly** — As your schema evolves, update your anonymization rules
