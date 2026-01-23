<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Rename transfer_runs to cloning_runs
        Schema::rename('transfer_runs', 'cloning_runs');

        // Step 2: Modify cloning_runs table
        Schema::table('cloning_runs', function (Blueprint $table): void {
            // Add cloning_id column after user_id
            $table->foreignId('cloning_id')
                ->nullable()
                ->after('user_id')
                ->constrained('clonings')
                ->onDelete('cascade');

            // Drop foreign key constraints first
            $table->dropForeign(['source_connection_id']);
            $table->dropForeign(['target_connection_id']);

            // Drop columns that are now in the clonings table
            $table->dropColumn([
                'source_connection_id',
                'target_connection_id',
                'anonymization_config',
            ]);
        });

        // Step 3: Rename transfer_run_logs to cloning_run_logs
        Schema::rename('transfer_run_logs', 'cloning_run_logs');

        // Step 4: Update foreign key in cloning_run_logs
        Schema::table('cloning_run_logs', function (Blueprint $table): void {
            // Drop old foreign key
            $table->dropForeign(['run_id']);

            // Re-add with new table reference
            $table->foreign('run_id')
                ->references('id')
                ->on('cloning_runs')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        // Step 1: Revert cloning_run_logs foreign key
        Schema::table('cloning_run_logs', function (Blueprint $table): void {
            $table->dropForeign(['run_id']);
        });

        // Step 2: Rename cloning_run_logs back to transfer_run_logs
        Schema::rename('cloning_run_logs', 'transfer_run_logs');

        // Step 3: Re-add foreign key for transfer_run_logs
        Schema::table('transfer_run_logs', function (Blueprint $table): void {
            $table->foreign('run_id')
                ->references('id')
                ->on('cloning_runs')
                ->onDelete('cascade');
        });

        // Step 4: Modify cloning_runs back to transfer_runs structure
        Schema::table('cloning_runs', function (Blueprint $table): void {
            // Re-add columns
            $table->foreignId('source_connection_id')
                ->after('user_id')
                ->constrained('database_connections')
                ->onDelete('cascade');
            $table->foreignId('target_connection_id')
                ->after('source_connection_id')
                ->constrained('database_connections')
                ->onDelete('cascade');
            $table->json('anonymization_config')->nullable()->after('target_connection_id');

            // Drop cloning_id
            $table->dropForeign(['cloning_id']);
            $table->dropColumn('cloning_id');
        });

        // Step 5: Update transfer_run_logs foreign key reference
        Schema::table('transfer_run_logs', function (Blueprint $table): void {
            $table->dropForeign(['run_id']);
            $table->foreign('run_id')
                ->references('id')
                ->on('transfer_runs')
                ->onDelete('cascade');
        });

        // Step 6: Rename cloning_runs back to transfer_runs
        Schema::rename('cloning_runs', 'transfer_runs');
    }
};
