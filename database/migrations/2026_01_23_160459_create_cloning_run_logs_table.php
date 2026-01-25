<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cloning_run_logs', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('run_id')
                ->constrained('cloning_runs')
                ->onDelete('cascade');

            $table->string('level', 20);
            $table->string('event_type', 100);
            $table->text('message');
            $table->json('data')->nullable();
            $table->timestamp('created_at');

            // Indexes
            $table->index('run_id');
            $table->index('level');
            $table->index('event_type');
            $table->index(['run_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cloning_run_logs');
    }
};
