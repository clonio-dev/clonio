<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\ForeignKeySchema;
use App\Data\TableSchema;
use App\Services\SchemaInspector\SchemaInspectorFactory;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * DependencyResolver
 *
 * Resolves table processing order based on Foreign Key dependencies
 * using Kahn's Algorithm for topological sorting.
 */
class DependencyResolver
{
    /**
     * Get processing order for tables based on FK dependencies
     *
     * @param  string[]  $tables  List of table names
     * @param  Connection  $connection  Database connection
     * @param  bool  $ignoreNullableFKs  If true, nullable FKs are ignored for dependency graph (helps with quasi-circular deps)
     * @return array{dependency_levels: array<int, string[]>, dependency_graph: array<string, string[]>, insert_order: array<int, string>, delete_order: array<int, string>} ['insert_order' => [...], 'delete_order' => [...], 'dependency_levels' => [...], 'dependency_graph' => [...]]
     */
    public function getProcessingOrder(array $tables, Connection $connection, bool $ignoreNullableFKs = false): array
    {
        // 1. Analyze FK dependencies
        $dependencies = $this->analyzeDependencies($tables, $connection, false);

        // 2. Topological sort (parents first)
        $insertOrder = $this->topologicalSort($dependencies);

        // 3. DELETE order is reversed (children first)
        $deleteOrder = array_reverse($insertOrder);

        // 4. Calculate dependency levels for visualization
        $levels = $this->calculateLevels($dependencies);

        Log::debug('Dependency resolution completed', [
            'tables' => count($tables),
            'insert_order' => $insertOrder,
            'delete_order' => $deleteOrder,
            'levels' => $levels,
        ]);

        return [
            'insert_order' => $insertOrder,
            'delete_order' => $deleteOrder,
            'dependency_levels' => $levels,
            'dependency_graph' => $dependencies,
        ];
    }

    /**
     * Analyze FK dependencies for all tables
     *
     * Optionally ignores nullable FKs to handle quasi-circular dependencies
     * where both FKs are NULL-able (e.g., employees.department_id NULL → departments,
     * departments.manager_id NULL → employees)
     *
     * @param string[] $tables Table names
     * @param Connection $connection Database connection
     * @param bool $ignoreNullableFKs If true, nullable FKs are ignored for dependency graph
     * @return array<string, string[]> ['table_name' => ['referenced_table1', 'referenced_table2', ...]]
     */
    private function analyzeDependencies(
        array $tables,
        Connection $connection,
        bool $ignoreNullableFKs = true,
    ): array
    {
        $inspector = SchemaInspectorFactory::create($connection);
        $dependencies = [];

        foreach ($tables as $table) {
            // Get foreign keys for this table
            $tableSchema = $inspector->getTableSchema($connection, $table);

            $referencedTables = [];

            foreach ($tableSchema->foreignKeys as $fk) {
                // Skip if referenced table is not in our list
                if (!in_array($fk->referencedTable, $tables)) {
                    continue;
                }

                // Check if FK columns are nullable
                if ($ignoreNullableFKs && $this->isForeignKeyNullable($tableSchema, $fk)) {
                    Log::debug("Ignoring nullable FK for dependency graph", [
                        'table' => $table,
                        'fk' => $fk->name,
                        'references' => $fk->referencedTable,
                        'columns' => $fk->columns,
                    ]);
                    continue;
                }

                $referencedTables[] = $fk->referencedTable;
            }

            $dependencies[$table] = array_unique($referencedTables);
        }

        return $dependencies;
    }

    /**
     * Topological sort using Kahn's Algorithm
     *
     * @param  array<string, array<int, string>>  $dependencies  ['table' => ['dependency1', 'dependency2', ...]]
     * @return string[] Sorted list of tables (parents first)
     *
     * @throws RuntimeException If circular dependency detected
     */
    public function topologicalSort(array $dependencies): array
    {
        // 1. Calculate in-degree (how many tables depend on each table)
        // We need to INVERT the graph: from "table needs X" to "X is needed by table"
        $inDegree = [];
        $dependents = []; // Inverted graph: who depends on me?

        // Initialize all tables with in-degree 0
        foreach (array_keys($dependencies) as $table) {
            $inDegree[$table] = 0;
            $dependents[$table] = [];
        }

        // Build inverted graph and count in-degrees
        foreach ($dependencies as $table => $deps) {
            foreach ($deps as $dependency) {
                if (isset($inDegree[$table])) {
                    // This table depends on $dependency
                    // So $table's in-degree increases
                    $inDegree[$table]++;

                    // And $dependency is needed by $table
                    if (isset($dependents[$dependency])) {
                        $dependents[$dependency][] = $table;
                    }
                }
            }
        }

        // 2. Queue with tables that have no dependencies (in_degree == 0)
        $queue = [];
        foreach ($inDegree as $table => $degree) {
            if ($degree === 0) {
                $queue[] = $table;
            }
        }

        // 3. Process queue
        $result = [];

        while (! empty($queue)) {
            // Remove table with in_degree 0 (no dependencies)
            $table = array_shift($queue);
            $result[] = $table;

            // Reduce in-degree of tables that depend on this table
            foreach ($dependents[$table] ?? [] as $dependent) {
                $inDegree[$dependent]--;

                if ($inDegree[$dependent] === 0) {
                    $queue[] = $dependent;
                }
            }
        }

        // 4. Check for cycles
        if (count($result) !== count($dependencies)) {
            $missing = array_diff(array_keys($dependencies), $result);

            throw new RuntimeException(
                'Circular dependency detected! Tables involved: ' . implode(', ', $missing)
            );
        }

        return $result;
    }

    /**
     * Check if a foreign key is nullable
     *
     * A FK is considered nullable if ALL its columns accept NULL values
     *
     * @param  TableSchema  $tableSchema  Table containing the FK
     * @param  ForeignKeySchema  $fk  Foreign key to check
     * @return bool True if all FK columns are nullable
     */
    private function isForeignKeyNullable(TableSchema $tableSchema, ForeignKeySchema $fk): bool
    {
        foreach ($fk->columns as $columnName) {
            $column = $tableSchema->getColumn($columnName);

            // If any column is NOT nullable, the FK is required
            if ($column && ! $column->nullable) {
                return false;
            }
        }

        // All columns are nullable
        return true;
    }

    /**
     * Calculate dependency levels for visualization
     *
     * Level 0 = no dependencies (parent tables)
     * Level 1 = direct dependencies on level 0
     * Level 2 = dependencies on level 1, etc.
     *
     * @param  array<string, string[]>  $dependencies  Dependency graph
     * @return array<int, string[]> ['level' => ['table1', 'table2', ...]]
     */
    private function calculateLevels(array $dependencies): array
    {
        $levels = [];
        $processed = [];
        $level = 0;

        while (count($processed) < count($dependencies)) {
            $currentLevel = [];

            foreach ($dependencies as $table => $deps) {
                if (in_array($table, $processed)) {
                    continue;
                }

                // Check if all dependencies are already processed
                $allDepsProcessed = true;
                foreach ($deps as $dep) {
                    if (! in_array($dep, $processed)) {
                        $allDepsProcessed = false;
                        break;
                    }
                }

                if ($allDepsProcessed) {
                    $currentLevel[] = $table;
                }
            }

            if (empty($currentLevel)) {
                // No progress made → circular dependency
                throw new RuntimeException(
                    'Circular dependency detected! Cannot calculate levels.'
                );
            }

            // Add current level tables to processed AFTER the iteration
            // to prevent dependent tables from being placed at the same level
            foreach ($currentLevel as $table) {
                $processed[] = $table;
            }

            $levels[$level] = $currentLevel;
            $level++;
        }

        return $levels;
    }

    /**
     * Format dependency analysis for logging/display
     *
     * @param array{dependency_levels: array<int, string[]>, dependency_graph: array<string, string[]>, insert_order: array<int, string>, delete_order: array<int, string>} $order Result from getProcessingOrder()
     * @return string Formatted output
     */
    public function formatDependencyAnalysis(array $order): string
    {
        $output = "=== DEPENDENCY ANALYSIS ===\n\n";

        // Show levels
        foreach ($order['dependency_levels'] as $level => $tables) {
            $output .= "Level {$level}";

            if ($level === 0) {
                $output .= " (Parent Tables - no dependencies):\n";
            } else {
                $output .= " (Children - {$level} level deep):\n";
            }

            foreach ($tables as $table) {
                $deps = $order['dependency_graph'][$table] ?? [];

                if (empty($deps)) {
                    $output .= "  • {$table}\n";
                } else {
                    $output .= "  • {$table} → depends on [" . implode(', ', $deps) . "]\n";
                }
            }

            $output .= "\n";
        }

        // Show insert order
        $output .= "=== INSERT ORDER (Top-Down) ===\n";
        foreach ($order['insert_order'] as $index => $table) {
            $level = $this->findLevel($table, $order['dependency_levels']);
            $output .= ($index + 1) . ". {$table} (Level {$level})\n";
        }

        $output .= "\n";

        // Show delete order
        $output .= "=== DELETE ORDER (Bottom-Up) ===\n";
        foreach ($order['delete_order'] as $index => $table) {
            $level = $this->findLevel($table, $order['dependency_levels']);
            $output .= ($index + 1) . ". {$table} (Level {$level})\n";
        }

        return $output;
    }

    /**
     * Find which level a table belongs to
     *
     * @param  array<int, string[]>  $levels
     */
    private function findLevel(string $table, array $levels): int
    {
        foreach ($levels as $level => $tables) {
            if (in_array($table, $tables)) {
                return $level;
            }
        }

        return -1;
    }
}
