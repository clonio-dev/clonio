<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('clonings', function (Blueprint $table): void {
            $table->boolean('is_paused')->default(false)->after('is_scheduled');
            $table->unsignedTinyInteger('consecutive_failures')->default(0)->after('is_paused');
            $table->index(['is_scheduled', 'is_paused']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clonings', function (Blueprint $table): void {
            $table->dropIndex(['is_scheduled', 'is_paused']);
            $table->dropColumn(['is_paused', 'consecutive_failures']);
        });
    }
};
