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
            $table->json('trigger_config')->nullable()->default(null)->after('schedule');
            $table->string('api_trigger_token', 64)->nullable()->unique()->after('trigger_config');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clonings', function (Blueprint $table): void {
            $table->dropColumn(['trigger_config', 'api_trigger_token']);
        });
    }
};
