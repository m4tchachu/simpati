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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Nama lengkap user');
            $table->string('email')->unique()->comment('Email unik');
            $table->string('password')->comment('Password terenkripsi');
            $table->enum('role', ['admin', 'mahasiswa'])->default('mahasiswa')->comment('Role user');
            $table->string('nim')->nullable()->unique()->comment('NIM mahasiswa (hanya mahasiswa)');
            $table->foreignId('study_program_id')->nullable()->constrained()->onDelete('set null')->comment('Program studi mahasiswa');
            $table->string('fcm_token')->nullable()->comment('Firebase Cloud Messaging token');
            $table->rememberToken();
            $table->timestamps();

            $table->index('email');
            $table->index('role');
            $table->index('nim');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
