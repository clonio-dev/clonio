<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cloning_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            $table->foreignId('cloning_id')
                ->nullable()
                ->constrained('clonings')
                ->onDelete('cascade');

            $table->string('batch_id')->nullable();
            $table->string('status');

            $table->timestamp('started_at')->nullable()->default(null);
            $table->timestamp('finished_at')->nullable()->default(null);

            $table->unsignedInteger('current_step')->default(0);
            $table->unsignedInteger('total_steps')->default(0);
            $table->unsignedInteger('progress_percent')->default(0);

            $table->text('error_message')->nullable()->default(null);
            $table->timestamps();

            // Indexes
            $table->index('batch_id');
            $table->index('status');
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cloning_runs');
    }
};
