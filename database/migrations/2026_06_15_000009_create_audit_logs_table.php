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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->comment('User yang melakukan aksi');
            $table->string('action')->comment('Aksi yang dilakukan (create, update, delete, confirm, reject, settle)');
            $table->string('table_name')->comment('Nama tabel yang diaffect');
            $table->unsignedBigInteger('record_id')->comment('ID record yang diaffect');
            $table->json('old_values')->nullable()->comment('Nilai sebelum perubahan');
            $table->json('new_values')->nullable()->comment('Nilai sesudah perubahan');
            $table->string('ip_address')->nullable()->comment('IP address user');
            $table->text('user_agent')->nullable()->comment('User agent browser');
            $table->timestamps();

            $table->index('user_id');
            $table->index('action');
            $table->index('table_name');
            $table->index(['table_name', 'record_id']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
