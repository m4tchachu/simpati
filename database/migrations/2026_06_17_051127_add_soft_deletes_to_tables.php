<?php

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
        // Add soft deletes to debt_records table
        if (Schema::hasTable('debt_records') && !Schema::hasColumn('debt_records', 'deleted_at')) {
            Schema::table('debt_records', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // Add soft deletes to users table
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'deleted_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // Add soft deletes to notifications table
        if (Schema::hasTable('notifications') && !Schema::hasColumn('notifications', 'deleted_at')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop soft deletes
        if (Schema::hasTable('debt_records') && Schema::hasColumn('debt_records', 'deleted_at')) {
            Schema::table('debt_records', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'deleted_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasTable('notifications') && Schema::hasColumn('notifications', 'deleted_at')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
