---
title: Key Remapping
excerpt: Remap primary keys and their foreign key references during a cloning run to avoid ID collisions between environments.
---

# Key Remapping

Key remapping rewrites primary key values during transfer and automatically updates all foreign key references that point to those keys. This prevents ID collisions when multiple environments share downstream services, caches, or queues that reference database IDs.

## Why Remap Keys?

In long-lived staging or development environments, records accumulate over time. When you clone production data on top of existing records, the source IDs often conflict with IDs already present on the target — even after truncation, sequences or auto-increment counters may be out of sync.

Key remapping solves this by:

- Assigning a new, non-conflicting ID to every transferred primary key
- Updating every foreign key column that references that primary key, keeping the relational graph intact
- Cleaning up the temporary mapping table after the run completes

## Configuring Key Remapping

Key remapping is configured per table in **Step 2: Configure Tables** of the cloning wizard. Each table's primary key column shows a strategy selector instead of the standard transformation dropdown.

### Remapping Strategies

| Strategy | Description |
|----------|-------------|
| **Keep identical** | Primary keys are copied as-is. No remapping is performed. |
| **Random Integer** | Each primary key is replaced with a random integer within a configurable range. |
| **New UUID** | Each primary key is replaced with a freshly generated UUID v7. Available for UUID-typed columns. |

The strategy is auto-detected based on the column type when creating a new cloning:

- Integer-typed PK columns default to **Random Integer**
- UUID/CHAR(36)-typed PK columns default to **New UUID** (UUID v7)

When editing an existing cloning, the saved strategy is always restored. No auto-detection is applied on edit.

### Configuring the Range (Random Integer)

When **Random Integer** is selected, you can define the minimum and maximum bounds for the generated values:

- **Min** — Lower bound (default: 100,000)
- **Max** — Upper bound (default: 9,999,999)

Clonio guarantees uniqueness within a single run by tracking used values in-memory during generation.

## Junction Tables and Composite Primary Keys

Tables where every primary key column is also a foreign key (e.g. `post_tag` with a composite PK of `post_id, tag_id`) are handled automatically — their PK columns are not offered as remappable keys. Instead, their values are updated via the foreign key resolution of the parent tables they reference.

## How It Works Internally

Key remapping runs in three phases inside a cloning run:

### Phase 1: Mapping Generation

Before any rows are transferred, Clonio reads the source table and builds a mapping of old → new primary key values. For **Random Integer**, a random value is drawn from the configured range; for **New UUID**, a UUID v4 is generated. Collisions with already-used values within the same run are avoided.

The mapping is stored in the `cloning_run_key_mappings` table, scoped to the current run, and automatically deleted when the run is removed.

### Phase 2: Row Transfer with Key Rewriting

During transfer, each row's primary key value is replaced with its mapped counterpart. For tables that reference remapped tables via foreign keys, the FK columns are rewritten using the mapping of the referenced table. This means the entire relational graph is rewritten consistently, regardless of transfer order.

### Phase 3: Cleanup

After all tables have been transferred, the temporary mapping entries are removed from `cloning_run_key_mappings`. This step runs before the audit log is finalized, so the audit log reflects the completed and cleaned state.

## Constraint Handling

During transfer, Clonio handles constraint violations gracefully:

- **Unique constraint violations** — The conflicting row is skipped. Its primary key mapping is removed so that foreign key references to it are not rewritten to a non-existent target row.
- **Foreign key violations** — The row is skipped. This can happen when a referenced parent row was itself skipped due to a unique constraint violation.

Skipped rows are counted and reported in the run log under `data_copy_completed`.

## Audit Trail

The key remapping configuration is included in the cloning run's audit log, which is signed after cleanup completes. The log records:

- Which tables had key remapping enabled
- The remapping strategy used per table
- The configured range (for Random Integer)
- How many rows were skipped due to constraint violations

## Limitations

- Remapping is currently supported for single-column primary keys. Composite primary keys that are not pure FK junction columns are not yet supported.
- The Random Integer range must be large enough to accommodate all rows being transferred. If the range is too narrow, generation may fail with a collision exhaustion error.
- Cross-table key chains (A → B → C, where A's PK feeds B's FK which feeds C's FK) are fully supported as long as all tables are included in the cloning configuration.

## Compliance Relevance

Key remapping is a core technical control for regulatory compliance. Replacing primary keys with non-reversible random values prevents re-identification of records in test environments, which is required by several data protection frameworks:

- **GDPR / DSGVO** — Identifier remapping supports pseudonymisation under Art. 4(5). Combined with field-level anonymization, it helps prevent re-identification in test environments. See [Compliance Reference: Germany](../6-references/02-compliance-reference-de.md).
- **HIPAA, PCI DSS, SOC 2** — Identifier remapping satisfies the requirement to remove or replace direct identifiers before using data outside production. See [Compliance Reference: U.S.](../6-references/01-compliance-reference-us.md).

The mapping table is never persisted to the target environment and is deleted after each run, satisfying the EDPB requirement that pseudonymisation keys must not be accessible within the pseudonymisation domain.

## Next Steps

Learn how to [manage existing clonings](04-managing-clonings.md) or review the [Audit Log](../3-cloning-runs/02-audit-log.md) to verify that remapping completed correctly.
