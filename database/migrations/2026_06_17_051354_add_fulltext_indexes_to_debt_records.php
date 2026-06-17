<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add indexes for search and performance optimization
        if (Schema::hasTable('debt_records')) {
            // Use raw SQL to add FULLTEXT index
            DB::statement('ALTER TABLE debt_records ADD FULLTEXT ft_description (description)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('debt_records')) {
            // Drop FULLTEXT index
            DB::statement('ALTER TABLE debt_records DROP INDEX ft_description');
        }
    }
};
