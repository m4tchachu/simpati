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
        Schema::create('debt_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_id')->constrained('users')->onDelete('cascade')->comment('User pembuat catatan');
            $table->foreignId('counterpart_id')->constrained('users')->onDelete('cascade')->comment('User penerima catatan');
            $table->enum('type', ['debt', 'receivable'])->comment('Tipe: debt (hutang) atau receivable (piutang)');
            $table->decimal('amount', 12, 2)->comment('Nominal transaksi');
            $table->text('description')->comment('Deskripsi transaksi');
            $table->dateTime('transaction_date')->comment('Tanggal transaksi');
            $table->dateTime('due_date')->comment('Tanggal jatuh tempo');
            $table->enum('status', ['pending', 'active', 'rejected', 'settled'])->default('pending')->comment('Status transaksi');
            $table->timestamp('confirmed_at')->nullable()->comment('Timestamp konfirmasi');
            $table->timestamp('rejected_at')->nullable()->comment('Timestamp penolakan');
            $table->text('rejection_reason')->nullable()->comment('Alasan penolakan');
            $table->timestamp('settled_at')->nullable()->comment('Timestamp pelunasan');
            $table->timestamps();

            $table->index('creator_id');
            $table->index('counterpart_id');
            $table->index('status');
            $table->index('due_date');
            $table->index(['status', 'due_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('debt_records');
    }
};
