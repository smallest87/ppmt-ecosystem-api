<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('entity_type', ['owner', 'resident']);
            $table->enum('sub_role', [
                'primary_owner',
                'co_owner',
                'primary_resident',
                'family_member',
                'co_resident',
                'tenant',
                'caretaker',
            ]);
            $table->boolean('is_primary_contact')->default(false);
            $table->boolean('is_approved')->default(false)->index();
            $table->date('valid_until')->nullable();
            $table->timestamps();

            $table->unique(['unit_id', 'user_id', 'entity_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_memberships');
    }
};