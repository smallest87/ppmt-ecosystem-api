<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->date('entry_date');
            $table->string('type', 20); // income, expense
            $table->string('category', 50); // iuran_sampah, pengangkutan_sampah, keamanan, listrik_pju, perawatan_fasum, sumbangan, lainnya
            $table->unsignedBigInteger('amount');
            $table->string('payment_method', 20)->default('cash'); // cash, transfer
            $table->string('description', 255);
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['entry_date', 'type']);
            $table->index('category');
        });

        Schema::create('dues_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->string('dues_type', 40)->default('iuran_sampah');
            $table->string('period_month', 7); // YYYY-MM (contoh: 2026-11)
            $table->unsignedBigInteger('amount')->default(20000);
            $table->string('status', 20)->default('paid'); // paid, exempt
            $table->string('payment_method', 20)->nullable(); // cash, transfer
            $table->date('paid_at')->nullable();
            $table->string('notes', 200)->nullable();
            $table->foreignId('cash_ledger_entry_id')->nullable()->constrained('cash_ledger_entries')->nullOnDelete();
            $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['unit_id', 'dues_type', 'period_month'], 'dues_unit_type_month_unique');
            $table->index(['dues_type', 'period_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dues_payments');
        Schema::dropIfExists('cash_ledger_entries');
    }
};