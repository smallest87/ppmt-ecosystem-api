<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verification_vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('target_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('voucher_user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('method', [
                'genesis_seed',
                'invite_link',
                'qr_scan',
                'owner_approval',
                'coordinator_approval',
                'neighbor_consensus',
            ]);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_vouchers');
    }
};