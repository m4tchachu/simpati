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
        Schema::create('reminder_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('debt_record_id')->constrained()->onDelete('cascade')->comment('Referensi debt_records');
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->comment('User penerima reminder');
            $table->integer('days_before')->comment('Pengingat H-X hari sebelum due_date');
            $table->timestamp('sent_at')->comment('Timestamp reminder dikirim');
            $table->timestamps();

            $table->index('debt_record_id');
            $table->index('user_id');
            $table->index(['debt_record_id', 'days_before']);
            $table->unique(['debt_record_id', 'user_id', 'days_before']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reminder_logs');
    }
};
