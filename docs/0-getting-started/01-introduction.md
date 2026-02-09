---
title: Introduction
introduction: Learn what Clonio is and how it helps you manage database cloning workflows efficiently.
---

# Introduction

Clonio is a powerful database cloning tool that helps development teams create safe, anonymized copies of their production databases for development, testing, and staging environments.

## Why Clonio?

Managing database copies across environments is a common challenge for development teams. You need fresh data to test against, but production data contains sensitive information that cannot be used in non-production environments.

Clonio solves this by providing:

- **Automated cloning** — Schedule and run database clones with a single click
- **Data anonymization** — Automatically mask sensitive data like emails, names, and addresses
- **Selective copying** — Choose which tables and how many rows to copy
- **Foreign key awareness** — Maintains referential integrity across related tables

## How It Works

The following diagram shows the high-level cloning flow:

![Cloning Flow](cloning-flow.svg)

Clonio connects to your source database, reads the schema, and transfers data to your target database while applying any configured anonymization rules.

```php
// Example: Configuring a cloning run
$cloning = Cloning::create([
    'source' => 'production',
    'target' => 'staging',
    'anonymization_config' => [
        'tables' => [
            ['tableName' => 'users', 'columnMutations' => [
                ['column' => 'email', 'mutation' => 'faker.email'],
                ['column' => 'name', 'mutation' => 'faker.name'],
            ]],
        ],
    ],
]);
```

## Architecture Overview

Clonio is built on top of Laravel and uses a pipeline-based architecture:

1. **Schema Inspection** — Reads table definitions from the source database
2. **Dependency Resolution** — Determines the correct order to copy tables
3. **Record Transfer** — Copies data in chunks with optional anonymization
4. **Verification** — Validates the cloned data matches expectations

## Demo

Watch a quick walkthrough of the cloning process:

<video controls width="100%">
  <source src="demo.mp4" type="video/mp4">
  Your browser does not support the video tag.
</video>

## Getting Started

Ready to get started? Head over to the [Installation](/docs/0-getting-started/02-installation) guide to set up Clonio in your project.
