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
        Schema::create('debt_status_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('debt_record_id')->constrained()->onDelete('cascade')->comment('Referensi debt_records');
            $table->foreignId('changed_by_user_id')->constrained('users')->onDelete('cascade')->comment('User yang melakukan perubahan');
            $table->enum('old_status', ['pending', 'active', 'rejected', 'settled'])->comment('Status sebelumnya');
            $table->enum('new_status', ['pending', 'active', 'rejected', 'settled'])->comment('Status sesudahnya');
            $table->text('reason')->nullable()->comment('Alasan perubahan status');
            $table->timestamps();

            $table->index('debt_record_id');
            $table->index('changed_by_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('debt_status_changes');
    }
};
