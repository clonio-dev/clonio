---
title: Supported Databases
introduction: Database engines supported by Clonio, their versions, and specific considerations for each.
---

# Supported Databases

Clonio supports four database engines as both source and target connections. Cross-engine cloning is supported -- for example, cloning from MySQL to PostgreSQL.

## MySQL

- **Supported versions:** 8.0+
- **Default port:** 3306
- **Also covers:** MariaDB 10.4+

MySQL is the most commonly used database with Clonio. Full support for all features including schema inspection, foreign key handling, column comments, charset/collation detection, and unsigned integer types.

### MySQL-Specific Notes

- Auto-increment columns are detected and preserved during schema replication
- `UNSIGNED` integer types are recognized and mapped correctly
- Character set and collation are inspected at both table and column level
- Clonio temporarily disables foreign key checks during data transfer (`SET FOREIGN_KEY_CHECKS = 0`) and re-enables them after each table completes

## MariaDB

- **Supported versions:** 10.4+
- **Default port:** 3306

MariaDB is treated as MySQL-compatible. The same schema inspector and schema builder are used. Clonio detects the MariaDB version string and handles any known differences automatically.

## PostgreSQL

- **Supported versions:** 14+
- **Default port:** 5432

PostgreSQL support includes full schema inspection, sequence handling, and cross-schema awareness.

### PostgreSQL-Specific Notes

- `SERIAL` / `BIGSERIAL` columns are detected as auto-increment equivalents
- PostgreSQL does not have `UNSIGNED` integer types; this attribute is ignored during cross-engine cloning to PostgreSQL
- Sequences are handled during schema replication
- Foreign key constraints are temporarily deferred during data transfer

## SQL Server

- **Supported versions:** SQL Server 2019+
- **Default port:** 1433

SQL Server (Microsoft) is supported with full schema inspection and replication capabilities.

### SQL Server-Specific Notes

- `IDENTITY` columns are detected as auto-increment equivalents
- `IDENTITY_INSERT` is temporarily enabled during data transfer for identity columns
- Column comments are stored via extended properties and are inspected accordingly
- Schema-qualified table names are supported

## Feature Matrix

| Feature | MySQL | PostgreSQL | SQL Server |
|---------|-------|------------|------------|
| Schema inspection | Yes | Yes | Yes |
| Foreign keys | Yes | Yes | Yes |
| Indexes | Yes | Yes | Yes |
| Auto-increment | Yes | Yes (SERIAL) | Yes (IDENTITY) |
| Column comments | Yes | Yes | Yes |
| Default values | Yes | Yes | Yes |
| Unsigned types | Yes | N/A | N/A |
| Charset/Collation | Yes | Yes | Yes |
| CHECK constraints | Yes | Yes | Yes |

## Cross-Engine Cloning

Clonio can clone data between different database engines. During schema replication, data types are automatically mapped to the closest equivalent on the target engine. For example:

- MySQL `TINYINT` maps to PostgreSQL `SMALLINT`
- MySQL `DATETIME` maps to PostgreSQL `TIMESTAMP`
- PostgreSQL `TEXT` maps to MySQL `LONGTEXT`

Type mapping is handled transparently. If a type cannot be mapped exactly, Clonio uses the closest compatible type and logs a notice.

## Next Steps

With your connections configured, proceed to [Creating a Cloning](/docs/2-clonings/01-creating-a-cloning) to set up your first data transfer.
