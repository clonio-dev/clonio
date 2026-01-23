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

- [ ] Create `resources/js/pages/clones/Index.vue` - list of clones
- [ ] Create `resources/js/pages/clones/Create.vue` - create clone wizard
- [ ] Create `resources/js/pages/clones/Show.vue` - clone details + runs
- [ ] Create `resources/js/pages/clones/Edit.vue` - edit clone
- [ ] Update `resources/js/pages/clone-runs/Show.vue` (formerly transfer-runs)
- [ ] Create `resources/js/components/clones/CloneCard.vue`
- [ ] Create `resources/js/components/clones/CloneRunsList.vue`
- [ ] Move `TableConfigurationStep.vue` to clones folder
- [ ] Update navigation/sidebar links
- [ ] Update Dashboard to show clones instead of transfer runs

### Phase 5: Wayfinder & TypeScript

- [ ] Run `php artisan wayfinder:generate`
- [ ] Update TypeScript types/interfaces
- [ ] Fix any TypeScript errors

### Phase 6: Tests

- [ ] Create/Update `tests/Feature/Models/CloneTest.php`
- [ ] Create/Update `tests/Feature/Models/CloneRunTest.php`
- [ ] Create/Update `tests/Feature/Http/Controllers/CloneControllerTest.php`
- [ ] Update `tests/Feature/Http/Controllers/CloneRunControllerTest.php`
- [ ] Update `tests/Feature/Jobs/SynchronizeDatabaseTest.php`
- [ ] Update `tests/Feature/Jobs/TransferRecordsForOneTableTest.php`
- [ ] Update `tests/Feature/Services/AnonymizationServiceTest.php`
- [ ] Run full test suite and fix failures

### Phase 7: Cleanup

- [ ] Remove old `TransferRun` references
- [ ] Remove old routes
- [ ] Remove unused components
- [ ] Run `vendor/bin/pint`
- [ ] Run `npx eslint --fix`
- [ ] Final test run

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
Last Updated: 2026-01-23

### Notes
- Keep backward compatibility during migration if possible
- The "script" field was used for table selection, this is now part of anonymization_config
- Schedule feature can be implemented in a later phase (mark as optional)
