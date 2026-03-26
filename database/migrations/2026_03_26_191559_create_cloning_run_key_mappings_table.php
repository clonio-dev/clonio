<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cloning_run_key_mappings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('run_id')->constrained('cloning_runs')->cascadeOnDelete();
            $table->string('table_name');
            $table->string('column_name');
            $table->string('old_value');
            $table->string('new_value');

            $table->index(['run_id', 'table_name', 'column_name', 'old_value'], 'idx_key_mappings_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cloning_run_key_mappings');
    }
};
