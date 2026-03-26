<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CloningRun;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class KeyMappingRepository
{
    public function tableName(CloningRun $run): string
    {
        return '_clonio_id_mapping_' . $run->id;
    }

    public function createTable(CloningRun $run): void
    {
        $tableName = $this->tableName($run);

        Schema::create($tableName, function (Blueprint $table): void {
            $table->id();
            $table->string('table_name');
            $table->string('column_name');
            $table->string('old_value');
            $table->string('new_value');
            $table->index(['table_name', 'column_name', 'old_value'], 'idx_lookup');
        });
    }

    /**
     * @param  array<string, string>  $mappings  Map of old_value => new_value
     */
    public function insertMappings(CloningRun $run, string $tableName, string $columnName, array $mappings): void
    {
        $rows = [];
        foreach ($mappings as $oldValue => $newValue) {
            $rows[] = [
                'table_name' => $tableName,
                'column_name' => $columnName,
                'old_value' => (string) $oldValue,
                'new_value' => (string) $newValue,
            ];
        }

        if ($rows !== []) {
            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table($this->tableName($run))->insert($chunk);
            }
        }
    }

    public function lookupNewValue(CloningRun $run, string $tableName, string $columnName, mixed $oldValue): ?string
    {
        if ($oldValue === null) {
            return null;
        }

        return DB::table($this->tableName($run))
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
        return DB::table($this->tableName($run))
            ->where('table_name', $tableName)
            ->where('column_name', $columnName)
            ->pluck('new_value', 'old_value')
            ->all();
    }

    public function dropTable(CloningRun $run): void
    {
        Schema::dropIfExists($this->tableName($run));
    }

    public function tableExists(CloningRun $run): bool
    {
        return Schema::hasTable($this->tableName($run));
    }
}
