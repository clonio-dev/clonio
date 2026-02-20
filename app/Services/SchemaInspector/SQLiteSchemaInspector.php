<?php

declare(strict_types=1);

namespace App\Services\SchemaInspector;

use App\Data\ColumnSchema;
use App\Data\ConstraintSchema;
use App\Data\ForeignKeySchema;
use App\Data\IndexSchema;
use App\Data\TableMetricsData;
use App\Data\TableSchema;
use Exception;
use Illuminate\Database\Connection;
use Illuminate\Support\Collection;
use Throwable;

/**
 * SQLite SchemaInspector
 *
 * Inspects SQLite database schema using PRAGMA commands and sqlite_master.
 */
class SQLiteSchemaInspector extends AbstractSchemaInspector
{
    public function getTableSchema(Connection $connection, string $tableName): TableSchema
    {
        return new TableSchema(
            name: $tableName,
            columns: $this->getColumns($connection, $tableName),
            indexes: $this->getIndexes($connection, $tableName),
            foreignKeys: $this->getForeignKeys($connection, $tableName),
            constraints: $this->getConstraints($connection, $tableName),
            metadata: [],
            metricsData: $this->getTableMetrics($connection, $tableName),
        );
    }

    public function getTableNames(Connection $connection): array
    {
        $result = $connection->select("
            SELECT name
            FROM sqlite_master
            WHERE type = 'table'
            AND name NOT LIKE 'sqlite_%'
            ORDER BY name
        ");

        return array_map(fn ($row) => $row->name, $result);
    }

    public function getDatabaseMetadata(Connection $connection): array
    {
        $metadata = [
            'version' => null,
            'encoding' => null,
        ];

        try {
            $metadata['version'] = $connection->selectOne('SELECT sqlite_version() as version')->version ?? null;
        } catch (Throwable) {
        }

        try {
            $metadata['encoding'] = $connection->selectOne('PRAGMA encoding')->encoding ?? null;
        } catch (Throwable) {
        }

        return $metadata;
    }

    protected function getColumns(Connection $connection, string $tableName): Collection
    {
        $result = $connection->select(sprintf('PRAGMA table_info(%s)', $tableName));

        return collect($result)->map(function ($column): ColumnSchema {
            // Parse type and length (e.g., "VARCHAR(255)")
            $type = $column->type;
            $length = null;
            $scale = null;

            if (preg_match('/^([A-Z]+)\((\d+)\)$/i', $type, $matches)) {
                $type = $matches[1];
                $length = (int) $matches[2];
            }

            if (preg_match('/^([A-Z]+)\((\d+),(\d+)\)$/i', $type, $matches)) {
                $type = $matches[1];
                $length = (int) $matches[2];
                $scale = (int) $matches[3];
            }

            // Check if auto increment (SQLite uses INTEGER PRIMARY KEY AUTOINCREMENT)
            $autoIncrement = $column->pk === 1 && mb_strtoupper($type) === 'INTEGER';

            return new ColumnSchema(
                name: $column->name,
                type: mb_strtolower($type),
                nullable: $column->notnull === 0,
                default: $this->parseDefaultValue($column->dflt_value),
                length: $length,
                scale: $scale,
                autoIncrement: $autoIncrement,
                unsigned: false,
                charset: null,
                collation: null,
                comment: null, // SQLite doesn't support column comments
                metadata: [
                    'cid' => $column->cid,
                    'pk' => $column->pk,
                ]
            );
        });
    }

    protected function getTableMetrics(Connection $connection, string $tableName): TableMetricsData
    {
        // Row Count (echte Zählung, da SQLite keine Statistiken hat)
        $rowCount = $connection->selectOne("
            SELECT COUNT(*) as row_count
            FROM {$tableName}
        ")->row_count ?? 0;

        // Data Size: SQLite hat keine direkte Table-Size-Query
        // Wir schätzen basierend auf:
        // 1. Page Size
        // 2. Anzahl Seiten, die die Tabelle belegt

        try {
            // Page Size (standardmäßig 4096 Bytes)
            $pageSize = $connection->selectOne('PRAGMA page_size')->page_size ?? 4096;

            // Anzahl Seiten für diese Tabelle
            // Hinweis: SQLite speichert Tabellen nicht isoliert
            // Wir nutzen eine grobe Schätzung basierend auf Row-Count

            // Alternative: Gesamte DB-Größe abfragen
            $dbPath = $connection->getDatabaseName();

            if (file_exists($dbPath)) {
                // Gesamte DB-Größe
                $totalDbSize = filesize($dbPath);

                // Anzahl aller Rows in allen Tabellen
                $totalRows = $connection->selectOne("
                    SELECT SUM(cnt) as total_rows
                    FROM (
                        SELECT COUNT(*) as cnt FROM sqlite_master WHERE type='table'
                    )
                ")->total_rows ?? 1;

                // Geschätzte Größe dieser Tabelle (proportional)
                $dataSize = $rowCount > 0
                    ? (int) (($rowCount / max($totalRows, 1)) * $totalDbSize)
                    : 0;
            } else {
                // Fallback: Grobe Schätzung (durchschnittlich 500 Bytes/Row)
                $dataSize = $rowCount * 500;
            }

        } catch (Exception) {
            // Fallback bei Fehler: Grobe Schätzung
            $dataSize = $rowCount * 500; // 500 Bytes durchschnittlich pro Row
        }

        return new TableMetricsData(
            rowsCount: (int) $rowCount,
            dataSizeInBytes: (int) $dataSize,
        );
    }

    protected function getIndexes(Connection $connection, string $tableName): Collection
    {
        // Get all indexes
        $indexes = $connection->select(sprintf('PRAGMA index_list(%s)', $tableName));

        return collect($indexes)->map(function ($index) use ($connection): IndexSchema {
            // Get columns for this index
            $indexInfo = $connection->select(sprintf('PRAGMA index_info(%s)', $index->name));
            $columns = array_map(fn ($col) => $col->name, $indexInfo);

            // Determine type
            $type = 'index';
            if ($index->origin === 'pk') {
                $type = 'primary';
            } elseif ($index->unique === 1) {
                $type = 'unique';
            }

            return new IndexSchema(
                name: $index->name,
                type: $type,
                columns: $columns,
                metadata: [
                    'origin' => $index->origin,
                ]
            );
        });
    }

    protected function getForeignKeys(Connection $connection, string $tableName): Collection
    {
        $result = $connection->select(sprintf('PRAGMA foreign_key_list(%s)', $tableName));

        // Group by id (foreign key constraint id)
        $grouped = collect($result)->groupBy('id');

        return $grouped->map(function ($fkColumns, $id) use ($tableName): ForeignKeySchema {
            $firstColumn = $fkColumns->first();

            return new ForeignKeySchema(
                name: sprintf('fk_%s_%s', $tableName, $id),
                table: $tableName,
                columns: $fkColumns->pluck('from')->all(),
                referencedTable: $firstColumn->table,
                referencedColumns: $fkColumns->pluck('to')->all(),
                onUpdate: $firstColumn->on_update ?? 'NO ACTION',
                onDelete: $firstColumn->on_delete ?? 'NO ACTION'
            );
        })->values();
    }

    protected function getConstraints(Connection $connection, string $tableName): Collection
    {
        // SQLite stores constraints in the CREATE TABLE statement
        $createTable = $connection->selectOne("
            SELECT sql
            FROM sqlite_master
            WHERE type = 'table' AND name = ?
        ", [$tableName]);

        if (! $createTable || ! $createTable->sql) {
            return collect();
        }

        // Parse CHECK constraints from CREATE TABLE SQL
        $constraints = collect();
        $sql = $createTable->sql;

        // Match CHECK constraints: CHECK (expression)
        if (preg_match_all('/CHECK\s*\((.*?)\)/i', (string) $sql, $matches)) {
            foreach ($matches[1] as $index => $expression) {
                $constraints->push(new ConstraintSchema(
                    name: sprintf('check_%s_%d', $tableName, $index),
                    type: 'check',
                    column: null,
                    expression: mb_trim($expression),
                    metadata: []
                ));
            }
        }

        return $constraints;
    }

    /**
     * Parse default value from SQLite format
     */
    private function parseDefaultValue(?string $default): mixed
    {
        if ($default === null || mb_strtoupper($default) === 'NULL') {
            return null;
        }

        // Remove quotes
        if (preg_match("/^'(.*)'$/", $default, $matches)) {
            return $matches[1];
        }

        // Handle numeric
        if (is_numeric($default)) {
            return str_contains($default, '.') ? (float) $default : (int) $default;
        }

        // Return as-is for functions
        return $default;
    }
}
