<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CloningRun;
use Illuminate\Support\Facades\DB;

class KeyMappingRepository
{
    /**
     * @param  array<string, string>  $mappings  Map of old_value => new_value
     */
    public function insertMappings(CloningRun $run, string $tableName, string $columnName, array $mappings): void
    {
        $rows = [];
        foreach ($mappings as $oldValue => $newValue) {
            $rows[] = [
                'run_id' => $run->id,
                'table_name' => $tableName,
                'column_name' => $columnName,
                'old_value' => (string) $oldValue,
                'new_value' => (string) $newValue,
            ];
        }

        if ($rows !== []) {
            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('cloning_run_key_mappings')->insert($chunk);
            }
        }
    }

    public function lookupNewValue(CloningRun $run, string $tableName, string $columnName, mixed $oldValue): ?string
    {
        if ($oldValue === null) {
            return null;
        }

        return DB::table('cloning_run_key_mappings')
            ->where('run_id', $run->id)
            ->where('table_name', $tableName)
            ->where('column_name', $columnName)
            ->where('old_value', (string) $oldValue)
            ->value('new_value');
    }

    /**
     * @return array<string, string> Map of old_value => new_value for the given table/column
     */
    public function getAllMappings(CloningRun $run, string $tableName, string $columnName): array
    {
        return DB::table('cloning_run_key_mappings')
            ->where('run_id', $run->id)
            ->where('table_name', $tableName)
            ->where('column_name', $columnName)
            ->pluck('new_value', 'old_value')
            ->all();
    }

    public function deleteByRun(CloningRun $run): void
    {
        DB::table('cloning_run_key_mappings')
            ->where('run_id', $run->id)
            ->delete();
    }
}
