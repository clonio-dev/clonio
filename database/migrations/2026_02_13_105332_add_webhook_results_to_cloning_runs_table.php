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
        Schema::table('cloning_runs', function (Blueprint $table) {
            $table->json('webhook_results')->nullable()->after('audit_signed_at');
        });
    }

    public function down(): void
    {
        Schema::table('cloning_runs', function (Blueprint $table) {
            $table->dropColumn('webhook_results');
        });
    }
};
