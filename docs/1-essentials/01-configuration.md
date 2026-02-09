---
title: Configuration
introduction: Learn how to configure Clonio's cloning profiles, anonymization rules, and row selection strategies.
---

# Configuration

Clonio provides a flexible configuration system that lets you control exactly how your database is cloned.

## Configuration File

The main configuration file is located at `config/clonio.php`. Here you can set global defaults:

```php
return [
    'source_connection' => env('CLONIO_SOURCE_CONNECTION', 'mysql'),
    'target_connection' => env('CLONIO_TARGET_CONNECTION', 'mysql_staging'),
    'chunk_size' => env('CLONIO_CHUNK_SIZE', 1000),
    'keep_unknown_tables' => false,
];
```

## Anonymization Rules

Anonymization rules define how sensitive data should be transformed during the cloning process. Each rule maps a column to a mutation strategy.

### Available Mutations

| Mutation | Description | Example Output |
|----------|-------------|---------------|
| `faker.email` | Generates a random email | `john.doe@example.com` |
| `faker.name` | Generates a random full name | `Jane Smith` |
| `faker.phone` | Generates a random phone number | `+1-555-0123` |
| `static:value` | Replaces with a static value | `value` |
| `null` | Sets the column to NULL | `NULL` |

### Example Configuration

```json
{
    "tables": [
        {
            "tableName": "users",
            "columnMutations": [
                {"column": "email", "mutation": "faker.email"},
                {"column": "name", "mutation": "faker.name"},
                {"column": "phone", "mutation": "faker.phone"}
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

## Row Selection

Row selection strategies control how many rows are copied from each table.

### Full Table

Copies all rows from the source table. This is the default behavior.

```json
{
    "strategy": "FullTable"
}
```

### First X Rows

Copies the first X rows from the source table, ordered by primary key.

```json
{
    "strategy": "FirstX",
    "count": 500
}
```

### Last X Rows

Copies the last X rows from the source table, ordered by primary key descending.

```json
{
    "strategy": "LastX",
    "count": 1000
}
```

## Foreign Key Handling

When a parent table uses row selection (FirstX or LastX), Clonio automatically filters child tables to only include rows that reference the copied parent rows. This maintains referential integrity.

For example, if you copy only the last 1000 users, the `orders` table will automatically be filtered to only include orders belonging to those 1000 users.
