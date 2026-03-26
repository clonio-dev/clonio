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

## PostgreSQL → PostgreSQL

```bash
# Create source DB with schema + seed data
psql -U postgres -f tests/dumps/pgsql_source.sql

# Create empty target DB (CloneSchema fills the schema during the run)
psql -U postgres -f tests/dumps/pgsql_target.sql
```

Expected counts after a clean run (same as MySQL baseline):
| Table    | Rows |
|----------|------|
| users    | 10   |
| tags     | 5    |
| posts    | 15   |
| post_tag | 24   |

**Notes:**
- Source uses a `user_status` ENUM type — CloneSchema must replicate it before creating `users`
- Sequences are reset after explicit ID inserts so future inserts don't collide
- `posts_slug_unique` constraint on target will cause skips if re-run without truncating first

## Adding more test scenarios

- `mysql_source_with_duplicates.sql` — source with intentional duplicate emails (tests skip behavior)
- `mysql_to_pgsql_source.sql` — larger dataset for cross-DBMS tests
