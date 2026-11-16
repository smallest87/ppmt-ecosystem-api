<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('verification_vouchers', function (Blueprint $table) {
            $table->enum('method', [
                'genesis_seed',
                'invite_link',
                'qr_scan',
                'owner_approval',
                'coordinator_approval',
                'neighbor_consensus',
                'in_person_vouch',
            ])->change();
        });
    }

    public function down(): void
    {
        Schema::table('verification_vouchers', function (Blueprint $table) {
            $table->enum('method', [
                'genesis_seed',
                'invite_link',
                'qr_scan',
                'owner_approval',
                'coordinator_approval',
                'neighbor_consensus',
            ])->change();
        });
    }
};