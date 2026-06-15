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
        Schema::create('study_programs', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('Program kode unik');
            $table->string('name')->comment('Nama program studi');
            $table->string('faculty')->nullable()->comment('Nama fakultas');
            $table->timestamps();

            $table->index('code');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('study_program_id')->references('id')->on('study_programs')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('study_programs');
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['study_program_id']);
        });
    }
};
