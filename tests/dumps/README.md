# Test Dumps

SQL scripts for reproducible cloning test scenarios.

## MySQL → MySQL (same DBMS baseline test)

```bash
# Create source DB with schema + seed data
mysql -u root -p < tests/dumps/mysql_source.sql

# Create empty target DB (CloneSchema fills the schema during the run)
mysql -u root -p < tests/dumps/mysql_target.sql
```

Expected counts after a clean run:
| Table    | Rows |
|----------|------|
| users    | 10   |
| tags     | 5    |
| posts    | 15   |
| post_tag | 24   |

## Adding more test scenarios

- `mysql_source_with_duplicates.sql` — source with intentional duplicate emails (tests skip behavior)
- `pgsql_source.sql` — PostgreSQL source for pg→pg tests
- `mysql_to_pgsql_source.sql` — larger dataset for cross-DBMS tests
