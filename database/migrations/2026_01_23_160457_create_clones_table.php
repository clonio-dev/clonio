<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clonings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->string('title');
            $table->foreignId('source_connection_id')
                ->constrained('database_connections')
                ->onDelete('cascade');
            $table->foreignId('target_connection_id')
                ->constrained('database_connections')
                ->onDelete('cascade');
            $table->json('anonymization_config')->nullable();
            $table->string('schedule')->nullable();
            $table->boolean('is_scheduled')->default(false);
            $table->timestamp('next_run_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('is_scheduled');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clonings');
    }
};
