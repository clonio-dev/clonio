# Schema Inspector & Schema Replicator

## ⚡ Quick Start Guide

## 📖 Sofort loslegen

### 1. Schema inspizieren

```php
use App\Services\SchemaInspector\SchemaInspectorFactory;
use Illuminate\Support\Facades\DB;

// Connection holen
$connection = DB::connection('mysql');

// Inspector erstellen
$inspector = SchemaInspectorFactory::create($connection);

// Komplettes Schema holen
$schema = $inspector->getDatabaseSchema($connection);

// Tabellen anzeigen
foreach ($schema->tables as $table) {
    echo "Table: {$table->name}\n";
    echo "Columns: {$table->getColumnNames()->count()}\n";
}
```

### 2. Einzelne Tabelle inspizieren

```php
$table = $inspector->getTableSchema($connection, 'users');

// Alle Spalten
foreach ($table->columns as $column) {
    echo "{$column->name}: {$column->getFullType()}";
    echo $column->nullable ? " NULL" : " NOT NULL";
    echo "\n";
}

// Primary Key
$pk = $table->getPrimaryKey();
echo "Primary Key: " . implode(', ', $pk->columns) . "\n";

// Foreign Keys
foreach ($table->foreignKeys as $fk) {
    echo "FK: {$fk->name} → {$fk->referencedTable}\n";
}
```

### 3. Schema replizieren

```php
use App\Services\SchemaReplicator;

$source = DB::connection('production');
$target = DB::connection('dev');

$replicator = new SchemaReplicator();

// Komplette DB replizieren
$replicator->replicateDatabase($source, $target);

// ODER: Nur eine Tabelle
$replicator->replicateTable($source, $target, 'users');
```

### 4. Deltas finden

```php
$diff = $replicator->getSchemaDiff($source, $target);

// Fehlende Tabellen
foreach ($diff['missing_tables'] as $table) {
    echo "Missing: {$table}\n";
}

// Tabellen-Unterschiede
foreach ($diff['table_diffs'] as $table => $tableDiff) {
    echo "Table {$table}:\n";
    
    foreach ($tableDiff['missing_columns'] as $col) {
        echo "  Missing column: {$col}\n";
    }
}
```

---

## 🎯 Integration in Clonio

### In ProcessTableJob

```php
// app/Jobs/ProcessTableJob.php

public function handle(
    ConnectionService $connectionService,
    AnonymizationService $anonymizationService,
    SchemaReplicator $schemaReplicator  // ← DI
): void {
    // ...
    
    // Schema replizieren (wenn enabled)
    if ($this->config->options['replicate_schema'] ?? true) {
        $schemaReplicator->replicateTable(
            $sourceConn, 
            $targetConn, 
            $this->table
        );
    }
    
    // ... weiter mit Transfer
}
```

### In TransferService

```php
// app/Services/TransferService.php

use App\Services\SchemaInspector\SchemaInspectorFactory;

public function startTransfer(Config $config): TransferRun
{
    // Schema-Analyse vor Transfer
    $sourceInspector = SchemaInspectorFactory::create(
        $connectionService->getConnection($config->sourceConnection)
    );
    
    $sourceSchema = $sourceInspector->getDatabaseSchema($sourceConn);
    
    // Log Schema-Info
    $run->log('source_schema', [
        'tables' => $sourceSchema->getTableNames()->toArray(),
        'total_tables' => $sourceSchema->getTableCount(),
    ]);
    
    // ... weiter mit Batch-Jobs
}
```

### In Config-Creation (UI)

```php
// app/Http/Controllers/ConfigController.php

public function store(StoreConfigRequest $request)
{
    // ...
    
    // Schema-Vergleich für UI-Warnung
    $sourceConn = $connectionService->getConnection($request->source_connection_id);
    $targetConn = $connectionService->getConnection($request->target_connection_id);
    
    $replicator = new SchemaReplicator();
    $diff = $replicator->getSchemaDiff($sourceConn, $targetConn);
    
    // Warnung bei Unterschieden
    if (!empty($diff['extra_tables'])) {
        session()->flash('warning', 
            'Target DB has ' . count($diff['extra_tables']) . 
            ' extra tables (probably dev changes)'
        );
    }
    
    // Config speichern...
}
```

## ⚡ Schnellreferenz

### Utility-Methoden

```php
// ColumnSchema
$column->isNumeric()      // true für int, decimal, etc.
$column->isString()       // true für varchar, text, etc.
$column->isDateTime()     // true für date, timestamp, etc.
$column->getFullType()    // "VARCHAR(255) UNSIGNED"

// TableSchema
$table->hasColumn('email')
$table->getColumn('email')
$table->getPrimaryKey()
$table->getForeignKeys()

// IndexSchema
$index->isPrimary()
$index->isUnique()
$index->isComposite()

// ForeignKeySchema
$fk->cascadesOnDelete()
$fk->cascadesOnUpdate()
```

---

## 📋 Übersicht

### 1️⃣ SchemaInspector Service

**Zweck:** Analysiert Datenbankstruktur und gibt strukturierte DTOs zurück

**Unterstützte Datenbanken:**
- ✅ MySQL 8.0+
- ✅ PostgreSQL 14+
- ✅ SQLite 3+
- ✅ SQL Server (Microsoft)

### 2️⃣ SchemaReplicator Service

**Zweck:** Nutzt SchemaInspector-Daten zur Replikation von DB-Strukturen

**Features:**
- Komplette DB-Replikation
- Einzelne Tabellen replizieren
- Delta-Erkennung (Source vs. Target)
- Automatisches Hinzufügen fehlender Spalten

---

## 📦 Erstellte Komponenten

### DTOs (Data Transfer Objects)
Alle immutable, mit Serialisierung und Utility-Methoden:

| DTO | Datei | Zweck |
|-----|-------|-------|
| `DatabaseSchema` | `/app/Data/DatabaseSchema.php` | Komplettes DB-Schema |
| `TableSchema` | `/app/Data/TableSchema.php` | Einzelne Tabelle |
| `ColumnSchema` | `/app/Data/ColumnSchema.php` | Spalten-Details |
| `IndexSchema` | `/app/Data/IndexSchema.php` | Indizes (PK, Unique, etc.) |
| `ForeignKeySchema` | `/app/Data/ForeignKeySchema.php` | Foreign Keys |
| `ConstraintSchema` | `/app/Data/ConstraintSchema.php` | Constraints (CHECK, etc.) |

**Beispiel:**
```php
$column = new ColumnSchema(
    name: 'email',
    type: 'varchar',
    nullable: false,
    length: 255
);

echo $column->getFullType(); // "VARCHAR(255)"
```

---

### SchemaInspector Service

#### Interface
- `SchemaInspectorInterface` (`/app/Contracts/`)

#### Abstract Base
- `AbstractSchemaInspector` (`/app/Services/SchemaInspector/`)

#### DB-spezifische Implementierungen
| Klasse | Datei | DB-Typ |
|--------|-------|--------|
| `MySQLSchemaInspector` | `/app/Services/SchemaInspector/MySQLSchemaInspector.php` | MySQL |
| `PostgreSQLSchemaInspector` | `/app/Services/SchemaInspector/PostgreSQLSchemaInspector.php` | PostgreSQL |
| `SQLiteSchemaInspector` | `/app/Services/SchemaInspector/SQLiteSchemaInspector.php` | SQLite |
| `SQLServerSchemaInspector` | `/app/Services/SchemaInspector/SQLServerSchemaInspector.php` | SQL Server |

#### Factory
- `SchemaInspectorFactory` (`/app/Services/SchemaInspector/`)

**Verwendung:**
```php
use App\Services\SchemaInspector\SchemaInspectorFactory;

$connection = DB::connection('mysql');
$inspector = SchemaInspectorFactory::create($connection);

// Komplettes Schema
$schema = $inspector->getDatabaseSchema($connection);

// Einzelne Tabelle
$table = $inspector->getTableSchema($connection, 'users');

// Spalten-Info
$emailColumn = $table->getColumn('email');
echo $emailColumn->type;      // "varchar"
echo $emailColumn->length;    // 255
echo $emailColumn->nullable;  // false
```

---

### SchemaReplicator Service

#### Main Service
- `SchemaReplicator` (`/app/Services/SchemaReplicator.php`)

#### Schema Builder Interface
- `SchemaBuilderInterface` (`/app/Contracts/`)

#### DB-spezifische Builder (SQL-Generator)
| Klasse | Datei | DB-Typ |
|--------|-------|--------|
| `MySQLSchemaBuilder` | `/app/Services/SchemaReplicator/MySQLSchemaBuilder.php` | MySQL |
| `PostgreSQLSchemaBuilder` | `/app/Services/SchemaReplicator/PostgreSQLSchemaBuilder.php` | PostgreSQL |
| `SQLiteSchemaBuilder` | `/app/Services/SchemaReplicator/SQLiteSchemaBuilder.php` | SQLite |
| `SQLServerSchemaBuilder` | `/app/Services/SchemaReplicator/SQLServerSchemaBuilder.php` | SQL Server |

**Verwendung:**
```php
use App\Services\SchemaReplicator;

$source = DB::connection('production');
$target = DB::connection('dev');

$replicator = new SchemaReplicator();

// Komplette DB replizieren
$replicator->replicateDatabase($source, $target);

// Einzelne Tabelle
$replicator->replicateTable($source, $target, 'users');

// Deltas finden
$diff = $replicator->getSchemaDiff($source, $target);

print_r($diff['missing_tables']);  // Fehlen im Target
print_r($diff['extra_tables']);    // Nur im Target
print_r($diff['table_diffs']);     // Unterschiede pro Tabelle
```

---

## 🧪 Tests

| Test | Datei | Tested |
|------|-------|--------|
| `ColumnSchemaTest` | `/tests/Unit/Data/ColumnSchemaTest.php` | ColumnSchema DTO |
| `SchemaInspectorFactoryTest` | `/tests/Unit/Services/SchemaInspectorFactoryTest.php` | Factory Logic |
| `SchemaInspectorIntegrationTest` | `/tests/Integration/Services/SchemaInspectorIntegrationTest.php` | End-to-End Flow mit SQLite |

**Test-Szenarien:**
- ✅ Komplettes Schema inspizieren
- ✅ Tabelle von Source zu Target replizieren
- ✅ Schema-Deltas erkennen
- ✅ Foreign Keys korrekt handhaben
- ✅ Mehrere Tabellen replizieren
- ✅ Bestehende Tabellen updaten (neue Spalten hinzufügen)

---

## 📚 Dokumentation

**Inhalte:**
- ✅ Übersicht aller DTOs
- ✅ SchemaInspector Usage
- ✅ SchemaReplicator Usage
- ✅ Use Cases in Clonio
- ✅ Testing-Guide
- ✅ API-Referenz
- ✅ Beispiele (15+ Code-Beispiele)

---

## 🎯 Integration in Clonio

### 1. Schema Replication vor Transfer

```php
// In ProcessTableJob::handle()

if ($this->config->options['replicate_schema'] ?? true) {
    $replicator = app(SchemaReplicator::class);
    $replicator->replicateTable($sourceConn, $targetConn, $this->table);
}
```

### 2. Delta-Erkennung für UI

```php
// In Config-Creation
$inspector = SchemaInspectorFactory::create($sourceConn);
$schema = $inspector->getDatabaseSchema($sourceConn);

$replicator = new SchemaReplicator();
$diff = $replicator->getSchemaDiff($sourceConn, $targetConn);

// UI zeigt Warnung:
if (!empty($diff['extra_tables'])) {
    session()->flash('warning', 
        'Target DB has ' . count($diff['extra_tables']) . ' extra tables (probably dev changes)'
    );
}
```

### 3. Auto-Suggest für Transformation-Script

```php
// In Script-Editor
$table = $inspector->getTableSchema($connection, 'users');

$suggestions = [];
foreach ($table->columns as $column) {
    if (str_contains($column->name, 'email')) {
        $suggestions[$column->name] = "fake()->email()";
    } elseif (str_contains($column->name, 'phone')) {
        $suggestions[$column->name] = "fake()->phoneNumber()";
    }
}

// Frontend zeigt: "💡 Auto-Suggest available for 2 columns"
```

---

## 📊 Feature-Matrix

| Feature | MySQL | PostgreSQL | SQLite | SQL Server |
|---------|-------|------------|--------|------------|
| **Tables** | ✅ | ✅ | ✅ | ✅ |
| **Columns** | ✅ | ✅ | ✅ | ✅ |
| **Primary Keys** | ✅ | ✅ | ✅ | ✅ |
| **Foreign Keys** | ✅ | ✅ | ❌ | ✅ |
| **Indexes** | ✅ | ✅ | ✅ | ✅ |
| **Unique Constraints** | ✅ | ✅ | ✅ | ✅ |
| **CHECK Constraints** | ✅ | ✅ | ✅ | ✅ |
| **Auto-Increment** | ✅ | ✅ (SERIAL) | ✅ | ✅ (IDENTITY) |
| **Column Comments** | ✅ | ✅ | ❌ | ✅ |
| **Table Comments** | ✅ | ✅ | ❌ | ✅ |
| **Default Values** | ✅ | ✅ | ✅ | ✅ |
| **Nullable** | ✅ | ✅ | ✅ | ✅ |
| **Length/Precision** | ✅ | ✅ | ✅ | ✅ |
| **Unsigned** | ✅ | ❌ | ❌ | ❌ |
| **Charset/Collation** | ✅ | ✅ | ❌ | ✅ |

---

## 💡 Highlights

### 1. Konsistente DTO-Struktur
Alle 4 Datenbanksysteme geben identische DTOs zurück → einfache Verarbeitung

### 2. DB-Agnostik durch Abstraction
Dank Factory + Interface kannst du beliebige DBs mixen:
```php
$mysqlSource = DB::connection('mysql');
$postgresTarget = DB::connection('pgsql');

$replicator->replicateDatabase($mysqlSource, $postgresTarget);
```

### 3. Erweiterbar
Neue DB-Typen (MongoDB, Oracle) einfach hinzufügbar:
```php
class MongoDBSchemaInspector extends AbstractSchemaInspector { ... }
```

---

## Overview

Two powerful services for database schema analysis and replication:

1. **SchemaInspector** - Analyzes database schema and returns structured DTOs
2. **SchemaReplicator** - Replicates schema structure from source to target database

Both services support: **MySQL**, **PostgreSQL**, **SQLite**, and **SQL Server**

---

## 📦 Data Transfer Objects (DTOs)

All schema information is returned as immutable DTOs:

### DatabaseSchema
- `databaseName: string` - Name of the database
- `databaseType: string` - Type (mysql, pgsql, sqlite, sqlsrv)
- `tables: Collection<TableSchema>` - All tables
- `metadata: array` - Version, charset, etc.

### TableSchema
- `name: string` - Table name
- `columns: Collection<ColumnSchema>` - All columns
- `indexes: Collection<IndexSchema>` - All indexes
- `foreignKeys: Collection<ForeignKeySchema>` - All foreign keys
- `constraints: Collection<ConstraintSchema>` - All constraints
- `metadata: array` - Engine, collation, etc.

### ColumnSchema
- `name, type, nullable, default, length, scale`
- `autoIncrement, unsigned, charset, collation, comment`

### IndexSchema
- `name, type (primary|unique|index|fulltext|spatial), columns`

### ForeignKeySchema
- `name, table, columns, referencedTable, referencedColumns`
- `onUpdate, onDelete (CASCADE|RESTRICT|SET NULL|NO ACTION)`

### ConstraintSchema
- `name, type (check|default|unique|not_null), column, expression`

---

## 🔍 SchemaInspector Usage

### Basic Usage

```php
use App\Services\SchemaInspector\SchemaInspectorFactory;
use Illuminate\Support\Facades\DB;

// Get connection
$connection = DB::connection('mysql');

// Create inspector (automatically selects correct implementation)
$inspector = SchemaInspectorFactory::create($connection);

// Inspect entire database
$schema = $inspector->getDatabaseSchema($connection);

echo "Database: {$schema->databaseName}\n";
echo "Type: {$schema->databaseType}\n";
echo "Tables: {$schema->getTableCount()}\n";

// List all tables
foreach ($schema->tables as $table) {
    echo "Table: {$table->name} ({$table->getColumnNames()->count()} columns)\n";
}
```

### Inspect Single Table

```php
$table = $inspector->getTableSchema($connection, 'users');

echo "Table: {$table->name}\n";
echo "Columns: {$table->getColumnNames()->count()}\n";

// Inspect columns
foreach ($table->columns as $column) {
    echo "  {$column->name}: {$column->getFullType()}";
    echo $column->nullable ? " NULL" : " NOT NULL";
    echo $column->autoIncrement ? " AUTO_INCREMENT" : "";
    echo "\n";
}

// Primary Key
$primaryKey = $table->getPrimaryKey();
if ($primaryKey) {
    echo "Primary Key: " . implode(', ', $primaryKey->columns) . "\n";
}

// Foreign Keys
foreach ($table->foreignKeys as $fk) {
    echo "FK: {$fk->getColumnMapping()}\n";
    echo "  ON DELETE {$fk->onDelete}, ON UPDATE {$fk->onUpdate}\n";
}
```

### Check Column Properties

```php
$column = $table->getColumn('price');

if ($column->isNumeric()) {
    echo "Numeric column with precision: {$column->length}\n";
}

if ($column->isString()) {
    echo "String column with max length: {$column->length}\n";
}

if ($column->isDateTime()) {
    echo "DateTime column\n";
}
```

### Get Database Metadata

```php
$metadata = $inspector->getDatabaseMetadata($connection);

// MySQL example
echo "Version: {$metadata['version']}\n";
echo "Charset: {$metadata['charset']}\n";
echo "Collation: {$metadata['collation']}\n";
```

---

## 🔄 SchemaReplicator Usage

### Replicate Entire Database

```php
use App\Services\SchemaReplicator;
use Illuminate\Support\Facades\DB;

$source = DB::connection('production_mysql');
$target = DB::connection('dev_postgresql');

$replicator = new SchemaReplicator();

// Replicate all tables from source to target
$replicator->replicateDatabase($source, $target);
```

### Replicate Single Table

```php
// Option 1: By table name (string)
$replicator->replicateTable($source, $target, 'users');

// Option 2: Using TableSchema object
$inspector = SchemaInspectorFactory::create($source);
$tableSchema = $inspector->getTableSchema($source, 'users');

$replicator->replicateTable($source, $target, $tableSchema);
```

### Get Schema Differences

```php
$diff = $replicator->getSchemaDiff($source, $target);

// Missing tables (in source but not in target)
foreach ($diff['missing_tables'] as $tableName) {
    echo "Missing table: {$tableName}\n";
}

// Extra tables (in target but not in source)
foreach ($diff['extra_tables'] as $tableName) {
    echo "Extra table: {$tableName}\n";
}

// Table differences
foreach ($diff['table_diffs'] as $tableName => $tableDiff) {
    echo "Table {$tableName}:\n";
    
    foreach ($tableDiff['missing_columns'] as $column) {
        echo "  Missing column: {$column}\n";
    }
    
    foreach ($tableDiff['extra_columns'] as $column) {
        echo "  Extra column: {$column}\n";
    }
    
    foreach ($tableDiff['modified_columns'] as $column => $changes) {
        echo "  Modified column: {$column}\n";
        echo "    Source: " . json_encode($changes['source']) . "\n";
        echo "    Target: " . json_encode($changes['target']) . "\n";
    }
}
```

### Get Table-Specific Differences

```php
$sourceTable = $sourceInspector->getTableSchema($source, 'products');
$targetTable = $targetInspector->getTableSchema($target, 'products');

$tableDiff = $replicator->getTableDiff($sourceTable, $targetTable);

if (empty($tableDiff)) {
    echo "Tables are identical\n";
} else {
    echo "Differences found:\n";
    print_r($tableDiff);
}
```

---

## 🎯 Use Cases in Clonio

### 1. Pre-Transfer Schema Validation

```php
// Before running a transfer, check if target has all required tables
$sourceInspector = SchemaInspectorFactory::create($sourceConn);
$targetInspector = SchemaInspectorFactory::create($targetConn);

$sourceSchema = $sourceInspector->getDatabaseSchema($sourceConn);

foreach ($sourceSchema->tables as $table) {
    if (!$targetInspector->tableExists($targetConn, $table->name)) {
        // Option 1: Warn user
        Log::warning("Table {$table->name} missing in target");
        
        // Option 2: Auto-create
        $replicator->replicateTable($sourceConn, $targetConn, $table);
    }
}
```

### 2. Schema Replication Before Transfer

```php
// In Config options: "replicate_schema" => true
if ($config->options['replicate_schema'] ?? false) {
    $replicator = new SchemaReplicator();
    $replicator->replicateDatabase($sourceConn, $targetConn);
}
```

### 3. Transformation Script Generation

```php
// Use schema info to generate transformation script template
$inspector = SchemaInspectorFactory::create($sourceConn);
$table = $inspector->getTableSchema($sourceConn, 'users');

$script = "table('users', function(\$row) {\n";

foreach ($table->columns as $column) {
    if ($column->name === 'email') {
        $script .= "  \$row->email = fake()->email();\n";
    } elseif ($column->name === 'name') {
        $script .= "  \$row->name = hash('Person', \$row->id);\n";
    }
}

$script .= "  return \$row;\n});";

echo $script;
```

### 4. Delta Detection (Target has different schema)

```php
// Development DB might have different columns than Production
$diff = $replicator->getSchemaDiff($sourceConn, $targetConn);

// Store diff in TransferRun for user info
$run->log('schema_diff', [
    'missing_tables' => $diff['missing_tables'],
    'extra_tables' => $diff['extra_tables'],
    'table_diffs' => $diff['table_diffs'],
], 'warning');

// UI shows: "⚠️ Target DB has 2 extra columns in 'users' table (probably dev changes)"
```

---

## 🧪 Testing

### Run All Tests

```bash
php artisan test
```

### Run Specific Test Suites

```bash
# Unit tests only
php artisan test --testsuite=Unit

# Integration tests only
php artisan test --testsuite=Integration

# Specific test file
php artisan test tests/Integration/Services/SchemaInspectorIntegrationTest.php
```

### Test Coverage

```bash
php artisan test --coverage --min=90
```

---

## 📝 Examples

### Example 1: Compare Production vs. Dev Schema

```php
$prodConn = DB::connection('production');
$devConn = DB::connection('dev');

$replicator = new SchemaReplicator();
$diff = $replicator->getSchemaDiff($prodConn, $devConn);

// Generate report
$report = "Schema Comparison Report\n";
$report .= "========================\n\n";

if (!empty($diff['missing_tables'])) {
    $report .= "Missing Tables in Dev:\n";
    foreach ($diff['missing_tables'] as $table) {
        $report .= "  - {$table}\n";
    }
}

if (!empty($diff['extra_tables'])) {
    $report .= "\nExtra Tables in Dev (not in Prod):\n";
    foreach ($diff['extra_tables'] as $table) {
        $report .= "  - {$table}\n";
    }
}

foreach ($diff['table_diffs'] as $table => $tableDiff) {
    $report .= "\nTable '{$table}' differences:\n";
    
    if ($tableDiff['missing_columns']) {
        $report .= "  Missing columns: " . implode(', ', $tableDiff['missing_columns']) . "\n";
    }
    
    if ($tableDiff['extra_columns']) {
        $report .= "  Extra columns: " . implode(', ', $tableDiff['extra_columns']) . "\n";
    }
}

echo $report;
```

### Example 2: Auto-Generate Script from Schema

```php
$inspector = SchemaInspectorFactory::create($connection);
$table = $inspector->getTableSchema($connection, 'customers');

$script = "table('{$table->name}', function(\$row) {\n";

foreach ($table->columns as $column) {
    // Auto-suggest anonymization based on column name/type
    if (str_contains($column->name, 'email')) {
        $script .= "  \$row->{$column->name} = fake()->email();\n";
    } elseif (str_contains($column->name, 'phone')) {
        $script .= "  \$row->{$column->name} = fake()->phoneNumber();\n";
    } elseif (str_contains($column->name, 'name') && $column->isString()) {
        $script .= "  \$row->{$column->name} = hash('Person', \$row->id);\n";
    } elseif (str_contains($column->name, 'address')) {
        $script .= "  \$row->{$column->name} = fake()->address();\n";
    }
}

$script .= "  return \$row;\n});";

// This can be used as "Auto-Suggest" feature in UI
```

### Example 3: Validate Foreign Keys Before Transfer

```php
$inspector = SchemaInspectorFactory::create($connection);
$table = $inspector->getTableSchema($connection, 'orders');

foreach ($table->foreignKeys as $fk) {
    // Check if referenced table exists
    if (!$inspector->tableExists($connection, $fk->referencedTable)) {
        throw new \Exception(
            "Foreign key {$fk->name} references non-existent table {$fk->referencedTable}"
        );
    }
    
    // Warn if cascading deletes
    if ($fk->cascadesOnDelete()) {
        Log::warning("Table {$table->name} has CASCADE DELETE on {$fk->referencedTable}");
    }
}
```

---

## 🔧 Advanced Usage

### Custom Type Mapping

If you need to map types between different databases:

```php
// In your Config or Service
$typeMap = [
    'mysql' => [
        'TINYINT' => 'SMALLINT',  // MySQL TINYINT -> PostgreSQL SMALLINT
        'DATETIME' => 'TIMESTAMP',
    ],
];

// Apply mapping when replicating
$column = $sourceTable->getColumn('created_at');

$targetType = $typeMap['mysql'][$column->type] ?? $column->type;
```

---

## 🚨 Error Handling

### SQLite Limitations

```php
try {
    $builder = new \App\Services\SchemaReplicator\SQLiteSchemaBuilder();
    $builder->buildAddForeignKey('orders', $fk);
} catch (\RuntimeException $e) {
    // SQLite doesn't support adding FKs to existing tables
    echo "Warning: {$e->getMessage()}\n";
    
    // Alternative: Recreate table with FK
}
```

### Handle Missing Tables

```php
try {
    $table = $inspector->getTableSchema($connection, 'nonexistent');
} catch (\Exception $e) {
    Log::error("Table not found", ['table' => 'nonexistent']);
    
    // Create table first
    $replicator->replicateTable($source, $target, 'nonexistent');
}
```

---

## 📚 API Reference

See inline PHPDoc for complete method signatures and parameter descriptions.

**Key Interfaces:**
- `SchemaInspectorInterface` - `/app/Contracts/SchemaInspectorInterface.php`
- `SchemaBuilderInterface` - `/app/Contracts/SchemaBuilderInterface.php`

**Key Services:**
- `SchemaInspector` - `/app/Services/SchemaInspector/`
- `SchemaReplicator` - `/app/Services/SchemaReplicator.php`

**DTOs:**
- All DTOs in `/app/Data/`
