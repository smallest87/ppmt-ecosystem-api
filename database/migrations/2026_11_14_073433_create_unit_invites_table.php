<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_invites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->enum('entity_type', ['owner', 'resident']);
            $table->enum('sub_role', [
                'co_owner',
                'primary_resident',
                'family_member',
                'co_resident',
                'tenant',
                'caretaker',
            ]);
            $table->date('member_valid_until')->nullable(); // Batas waktu sewa untuk tenant
            $table->timestamp('expires_at'); // Batas waktu masa berlaku tautan undangan (misal 48 jam)
            $table->foreignId('used_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_invites');
    }
};