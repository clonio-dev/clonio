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
        Schema::table('cloning_runs', function (Blueprint $table): void {
            $table->string('public_token', 64)->nullable()->unique()->after('audit_signed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cloning_runs', function (Blueprint $table): void {
            $table->dropColumn('public_token');
        });
    }
};
