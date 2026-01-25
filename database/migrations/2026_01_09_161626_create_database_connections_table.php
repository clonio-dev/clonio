<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('database_connections', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            $table->string('name');
            $table->string('type'); // 'mysql', 'pgsql', 'sqlsrv', 'sqlite'
            $table->string('dbms_version')->nullable()->default(null);

            $table->string('host');
            $table->unsignedInteger('port');
            $table->string('database');
            $table->string('username');
            $table->text('password'); // encrypted
            $table->boolean('is_production_stage')->default(false);
            $table->timestamp('last_tested_at')->nullable()->default(null);
            $table->boolean('is_connectable')->default(false);
            $table->string('last_test_result')->nullable()->default(null);
            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('database_connections');
    }
};
