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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->comment('User penerima notifikasi');
            $table->foreignId('notification_type_id')->constrained()->onDelete('cascade')->comment('Tipe notifikasi');
            $table->foreignId('debt_record_id')->nullable()->constrained()->onDelete('set null')->comment('Referensi debt_records (opsional)');
            $table->string('title')->comment('Judul notifikasi');
            $table->text('message')->comment('Isi notifikasi');
            $table->json('data')->nullable()->comment('Data tambahan JSON');
            $table->timestamp('read_at')->nullable()->comment('Timestamp dibaca');
            $table->timestamps();

            $table->index('user_id');
            $table->index('notification_type_id');
            $table->index('debt_record_id');
            $table->index(['user_id', 'read_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
