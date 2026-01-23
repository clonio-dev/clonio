<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transfer_runs', function (Blueprint $table): void {
            $table->json('anonymization_config')->nullable()->after('script');
        });
    }

    public function down(): void
    {
        Schema::table('transfer_runs', function (Blueprint $table): void {
            $table->dropColumn('anonymization_config');
        });
    }
};
