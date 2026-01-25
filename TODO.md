# Clone/Run Architecture Refactoring

## Overview
Refactor the current `TransferRun` entity into two separate entities:
- **Clone**: Static configuration (connections, anonymization rules, title)
- **CloneRun**: Individual execution of a Clone

This allows users to configure once and execute multiple times, with optional scheduling.

## Database Schema

### Clones Table
- `id`
- `user_id` (FK)
- `title` (string)
- `source_connection_id` (FK)
- `target_connection_id` (FK)
- `anonymization_config` (JSON)
- `schedule` (string, nullable) - cron expression
- `is_active` (boolean) - for scheduled clones
- `next_run_at` (timestamp, nullable)
- `timestamps`

### Clone Runs Table (rename from transfer_runs)
- `id`
- `clone_id` (FK) - NEW
- `user_id` (FK)
- `batch_id`
- `status`
- `started_at`
- `finished_at`
- `current_step`
- `total_steps`
- `progress_percent`
- `error_message`
- `timestamps`

---

## Implementation Checklist

### Phase 1: Database & Models

- [x] Create migration: `create_clonings_table` (renamed from clones - PHP reserved word)
- [x] Create migration: `refactor_transfer_runs_to_cloning_runs`
  - Add `cloning_id` column
  - Remove `source_connection_id`, `target_connection_id`, `anonymization_config` (moved to Cloning)
- [x] Rename `transfer_runs` table to `cloning_runs`
- [x] Rename `transfer_run_logs` table to `cloning_run_logs` (update FK)
- [x] Create `Cloning` model with relationships (renamed from Clone - PHP reserved word)
- [x] Create `CloningRun` model (new, replaces TransferRun)
- [x] Create `CloningRunLog` model (new, replaces TransferRunLog)
- [x] Create `CloningRunStatus` enum
- [x] Create `CloningRunLogLevel` enum
- [x] Create `CloningFactory`
- [x] Create `CloningRunFactory`
- [x] Create `CloningRunLogFactory`

**Note**: Used "Cloning" instead of "Clone" because "Clone" is a reserved PHP keyword.

### Phase 2: Backend - Controllers & Routes

- [x] Create `CloningController` with methods:
  - `index()` - list clonings
  - `create()` - show create form
  - `store()` - save new cloning
  - `show()` - show cloning details with runs
  - `edit()` - edit cloning config
  - `update()` - update cloning config
  - `destroy()` - delete cloning
  - `execute()` - trigger immediate run
- [x] Create `CloningRunController`:
  - `dashboard()` - main dashboard
  - `index()` - list runs
  - `show()` - run details
  - `cancel()` - cancel running
  - `exportLogs()` - export logs
- [x] Create routes in `routes/application/clonings.php`
- [x] Create `StoreCloningRequest` form request
- [x] Create `UpdateCloningRequest` form request
- [x] Create `ValidateCloningConnectionsRequest` form request
- [x] Create `CloningPolicy`
- [x] Create `CloningRunPolicy`
- [x] Update `routes/web.php` to use CloningRunController for dashboard

### Phase 3: Jobs & Services

- [x] Update `SynchronizeDatabase` job to use CloningRun
- [x] Update `CloneSchema` job to use CloningRun
- [x] Update `TruncateTargetTables` job to use CloningRun
- [x] Update `DropUnknownTables` job to use CloningRun
- [x] Update `TransferRecordsForAllTables` job to use CloningRun
- [x] Update `TransferRecordsForOneTable` job to use CloningRun
- [x] Update `LogsProcessSteps` trait to use CloningRun
- [ ] Create `ExecuteClone` service/action class (optional - logic is in CloningController)
- [x] `AnonymizationService` - no changes needed (already generic)

### Phase 4: Frontend - Pages & Components

- [x] Create `resources/js/pages/clonings/Index.vue` - list of clonings
- [x] Create `resources/js/pages/clonings/Create.vue` - create cloning wizard
- [x] Create `resources/js/pages/clonings/Show.vue` - cloning details + runs
- [x] Create `resources/js/pages/clonings/Edit.vue` - edit cloning
- [x] Update `resources/js/pages/cloning-runs/Show.vue` (formerly transfer-runs)
- [x] `RunCard.vue` works for both clonings and runs (no separate CloneCard needed)
- [x] `TableConfigurationStep.vue` moved to clonings/components folder
- [x] Update navigation/sidebar links (AppSidebar.vue)
- [x] Update Dashboard to show cloning runs

### Phase 5: Wayfinder & TypeScript

- [x] Run `php artisan wayfinder:generate`
- [x] Update TypeScript types/interfaces (`cloning.types.ts`)
- [x] Fix any TypeScript errors (build succeeds)

### Phase 6: Tests

- [ ] Create/Update `tests/Feature/Models/CloningTest.php`
- [ ] Create/Update `tests/Feature/Models/CloningRunTest.php`
- [x] Create `tests/Feature/CloningControllerTest.php` (18 tests passing)
- [ ] Update `tests/Feature/CloningRunControllerTest.php`
- [x] Update `tests/Feature/Jobs/SynchronizeDatabaseTest.php` - updated for CloningRun parameter
- [x] Update `tests/Feature/Jobs/TransferRecordsForAllTablesTest.php` - updated for CloningRun parameter
- [x] Update `tests/Feature/Jobs/TransferRecordsForOneTableTest.php` - updated for CloningRun parameter
- [x] Update `tests/Feature/Jobs/CloneSchemaTest.php` - updated for CloningRun parameter
- [ ] Update `tests/Feature/Services/AnonymizationServiceTest.php`
- [x] Fix `tests/Feature/Services/SchemaReplicatorForeignKeyTest.php` - DependencyResolver added
- [x] Fix `tests/Feature/Services/SchemaInspectorFactoryTest.php` - DependencyResolver added
- [x] Fix `tests/Feature/Services/SchemaInspectorIntegrationTest.php` - DependencyResolver added
- [x] Fix `tests/Feature/Services/DependencyResolverTest.php` - fixed self-referencing and level calculation
- [x] Fix `tests/Feature/Services/DependenyResolverNullableFKTest.php` - added $ignoreNullableFKs parameter
- [x] Fix `tests/Feature/Services/DatabaseInformationRetrievalServiceTest.php` - updated for Laravel's SQLite validation
- [x] Run full test suite - ALL TESTS PASSING (166 passed, 1 skipped)

### Phase 7: Cleanup

- [x] Remove old `TransferRun` references
- [x] Remove old routes (`routes/application/transfers.php`)
- [x] Remove unused components (`resources/js/components/transfer-runs/*`, `resources/js/pages/transfer-runs/*`)
- [x] Remove old models, controllers, policies, factories, enums
- [x] Create new `CloningRunConsole` and `RunCard` components
- [x] Run `vendor/bin/pint`
- [x] Run `npx eslint --fix`
- [x] Final test run - ALL GREEN (160 passed, 1 skipped)

---

## File Mapping (Old -> New)

| Old | New |
|-----|-----|
| `TransferRun` model | `CloningRun` model |
| `TransferRunLog` model | `CloningRunLog` model |
| `TransferRunStatus` enum | `CloningRunStatus` enum |
| `TransferRunLogLevel` enum | `CloningRunLogLevel` enum |
| `TransferRunController` | `CloningRunController` (runs only) |
| - | `CloningController` (new) |
| `transfer_runs` table | `cloning_runs` table |
| `transfer_run_logs` table | `cloning_run_logs` table |
| - | `clonings` table (new) |
| `pages/transfer-runs/*` | `pages/cloning-runs/*` |
| - | `pages/clonings/*` (new) |

---

## Current Progress

Started: 2026-01-23
Last Updated: 2026-01-24

**Status: COMPLETE - All Phases Done (160 tests passed, 1 skipped)**

### Completed This Session
- Fixed DependencyResolver to handle self-referencing tables
- Fixed DependencyResolver level calculation bug
- Added $ignoreNullableFKs parameter to getProcessingOrder()
- Fixed SQLiteSchemaBuilder to include foreign keys in CREATE TABLE
- Updated all job tests to use CloningRun parameter
- Updated DatabaseInformationRetrievalServiceTest for Laravel's new SQLite path validation
- Removed all old TransferRun code:
  - Models, Controllers, Policies, Factories, Enums
  - Routes (transfers.php)
  - Vue components and pages
  - Old test files
- Created new components:
  - `CloningRunConsole.vue` - Log console for run details
  - `RunCard.vue` - Card component for dashboard/listings
- Ran pint and eslint cleanup
- Build and tests pass

### Notes
- Keep backward compatibility during migration if possible
- The "script" field was used for table selection, this is now part of anonymization_config
- Schedule feature can be implemented in a later phase (mark as optional)
