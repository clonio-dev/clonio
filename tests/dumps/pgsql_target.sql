-- =============================================================================
-- Clonio Test Dump: PostgreSQL Target Database (empty — schema applied by CloneSchema)
-- Usage: psql -U postgres -f tests/dumps/pgsql_target.sql
-- =============================================================================

DROP DATABASE IF EXISTS clonio_test_target;
CREATE DATABASE clonio_test_target ENCODING 'UTF8';
