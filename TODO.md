# Open todos

## Overview
Add some more details to a tested database connection. Add a schedule form on a Clone. Anonymize options have to be improved. Improve UI.

## Implementation Checklist

### Phase 1: The easy things

- [x] /clonings/create and edit: Do not provide "Set to NULL" for columns which are not nullable

### Phase 2: improve

- [x] Testing a database connection should also check the database management system and the version.
- [x] The database type should by updated in case of mysql or mariadb, because they relate together and a user could configure that wrong. So on retrieving the version there will by "MariaDB" within the version string. so the type should be updated accordingly (vice-versa)
  - [x] the version of the dbms is a new property. so add migration and all the necessary stuff to make that an optional nullable string
- [x] Creating/Editing a Cloning should get a schedule configuration on the new third step
  - [x] support a new third step: the immediate execution should be there as a checkbox and there should be the schedule section be added with an easy to use interface for a user - so provide some help for the possible selection of cronjob-like schedules.
  - [x] create a console command or scheduler call that finds all the runs requested via schedule and execute it so we can have automatically running cloning-runs.

---

## Current Progress

Started: 2026-01-25
Last Updated: 2026-01-25

### Completed This Session
- Phase 1: Filter "Set to NULL" option from non-nullable columns in TableConfigurationStep
- Phase 2: Added DBMS version detection to TestConnection job with MySQL/MariaDB auto-correction
- Phase 2: Added schedule configuration step (Step 3) to Create/Edit cloning wizard
- Phase 2: Created `clonings:run-scheduled` console command with scheduler registration

### Notes
- Run `php artisan migrate` to add the `dbms_version` column to database_connections
- The scheduler runs `clonings:run-scheduled` every minute to check for due clonings
- Make sure to run `php artisan schedule:work` or set up cron for the scheduler 
